<?php
//Shared by the end-to-end checks (shop_login_e2e.php, scan_upload_e2e.php) and by
//fixtures.php: a curl browser, the e2e records they create and remove, and the check helpers.
//Every e2e record is named "e2e ..." so a crashed run is cleaned up by the next one. They use
//the application's own database settings (Includes/config.php): run them only against a
//development database.
if (PHP_SAPI !== 'cli') {
    exit("Command line only.\n");
}//never from a browser

date_default_timezone_set('Asia/Colombo');
$base = rtrim(isset($argv[1]) ? $argv[1] : 'http://localhost/sleepmakers', '/');

ob_start(); //config.php starts with a byte order mark
require_once __DIR__ . '/../../Includes/config.php';
ob_end_clean();

class E2EDb extends Dbh
{
    public function pdo() { return $this->connect(); }
}//E2EDb

//a browser: one cookie jar, follows redirects, remembers where it ended up
class E2EBrowser
{
    private $base;
    private $jar;
    public $url = '';
    public $status = 0;
    public $body = '';

    public function __construct($base)
    {
        $this->base = $base;
        $this->jar = tempnam(sys_get_temp_dir(), 'e2e');
    }

    public function get($path) { return $this->request($path, null); }
    public function post($path, array $fields) { return $this->request($path, $fields); }

    private function request($path, $fields)
    {
        $curl = curl_init($this->base . '/' . ltrim($path, '/'));
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $this->jar,
            CURLOPT_COOKIEFILE => $this->jar,
            CURLOPT_TIMEOUT => 30,
        ]);
        if ($fields !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($fields));
        }
        //every page starts with the byte order mark of Includes/config.php - a browser drops it
        $this->body = preg_replace('/^\xEF\xBB\xBF/', '', (string) curl_exec($curl));
        $this->status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $this->url = (string) curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
        curl_close($curl);
        return $this;
    }

    //the PHP session ends (browser closed, session expired) - the remember-me cookies stay
    public function dropSession()
    {
        $lines = file($this->jar);
        file_put_contents($this->jar, implode('', array_filter($lines, function ($line) {
            return strpos($line, "\tPHPSESSID\t") === false;
        })));
    }

    //one field of a JSON answer, or null when the answer is not JSON
    public function json($key)
    {
        $data = json_decode($this->body, true);
        return is_array($data) && array_key_exists($key, $data) ? $data[$key] : null;
    }

    //a cookie the browser holds (curl writes the jar when each request completes)
    public function hasCookie($name)
    {
        foreach (file($this->jar) as $line) {
            $fields = explode("\t", rtrim($line, "\r\n"));
            if (count($fields) === 7 && $fields[5] === $name) {
                return true;
            }
        }
        return false;
    }

    public function isOn($page) { return strpos(parse_url($this->url, PHP_URL_PATH), '/' . $page) !== false; }
    public function has($text) { return strpos($this->body, $text) !== false; }

    //PHP warnings, notices or errors printed into the page (pages run with display_errors on)
    public function phpErrors()
    {
        preg_match_all('/<b>(?:Warning|Notice|Deprecated|Fatal error)<\/b>:.*?(?:<br \/>|$)/m', $this->body, $m);
        return array_values(array_unique(array_map('strip_tags', $m[0])));
    }

    public function csrf()
    {
        if (preg_match('/id="(?:shop_login|assign|scan)_csrf_token"[^>]*value="([0-9a-f]{64})"/', $this->body, $m)) {
            return $m[1];
        }
        return preg_match('/name="csrf_token" value="([0-9a-f]{64})"/', $this->body, $m) ? $m[1] : '';
    }

    public function __destruct() { @unlink($this->jar); }
}//E2EBrowser

//the records this run creates, found again by name so a crashed run is cleaned up next time
class E2EFixtures
{
    private $pdo;
    public $password;
    public $company;
    public $shops = [];
    public $roles = [];
    public $users = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->password = 'E2e-' . bin2hex(random_bytes(6));
    }

    public function up()
    {
        $this->down();
        $this->pdo->prepare("INSERT INTO company (CompanyNo, ComName, ComExpireDate, ComStat, CompanyType_CTID)
            SELECT 'E2E', 'e2e Company', ?, 1, MIN(CTID) FROM companytype")
            ->execute([date('Y-m-d', strtotime('+1 year'))]);
        $this->company = (int) $this->pdo->lastInsertId();

        foreach (['W' => 'e2e Warehouse', 'S' => 'e2e Showroom'] as $key => $name) {
            $this->pdo->prepare("INSERT INTO shop (ShopNo, ShopName, WholesaleShop, RetailShop, is_prescription, ShopStat,
                Company_CMID, StockTypes_STID, emailAddress) VALUES (?, ?, 0, 1, 0, 1, ?, 1, 'e2e@example.com')")
                ->execute(['E2E' . $key, $name, $this->company]);
            $this->shops[$key] = (int) $this->pdo->lastInsertId();
        }

        //Store Keeper sees Store (module 1, feature 1); Cashier sees Invoice List (module 2, feature 56).
        //Like roles made in Settings -> User Roles, each role has a row for every feature.
        foreach (['keeper' => ['e2e Store Keeper', 1, 1, 1], 'cashier' => ['e2e Cashier', 1, 2, 56], 'retired' => ['e2e Retired', 0, 2, 56]] as $key => $role) {
            $this->pdo->prepare("INSERT INTO userroles (UserRoleName, ur_status, added_by) VALUES (?, ?, 1)")->execute([$role[0], $role[1]]);
            $this->roles[$key] = (int) $this->pdo->lastInsertId();
            $this->pdo->prepare("INSERT INTO usermoduleaccess (SysModules_SMID, UserRoles_URID) VALUES (?, ?)")->execute([$role[2], $this->roles[$key]]);
            $this->pdo->prepare("INSERT INTO userroleaccess (is_create, is_edit, is_view, is_delete, is_verify, is_print, UserRolls_URID, SysFeatures_SFID)
                SELECT 0, 0, IF(SFID = ?, 1, 0), 0, 0, 0, ?, SFID FROM sysfeatures")->execute([$role[3], $this->roles[$key]]);
        }

        $hash = password_hash($this->password, PASSWORD_DEFAULT);
        foreach (['alice' => 0, 'bob' => 0, 'carol' => 0, 'admin' => 1] as $name => $type) {
            $this->pdo->prepare("INSERT INTO user (UserProfile, UserName, UserEmail, ContactNo, UserPwd, UserStat, UserRoles_URID, UserType)
                VALUES ('avator.svg', ?, ?, '0000000000', ?, 1, ?, ?)")
                ->execute(['e2e_' . $name, 'e2e_' . $name . '@example.com', $hash, $this->roles['cashier'], $type]);
            $this->users[$name] = (int) $this->pdo->lastInsertId();
        }

        //alice: Store Keeper in the warehouse, Cashier in the showroom. bob: only the showroom,
        //as Store Keeper. carol: only the warehouse, with a retired role.
        $this->assign('alice', 'W', 'keeper');
        $this->assign('alice', 'S', 'cashier');
        $this->assign('bob', 'S', 'keeper');
        $this->assign('carol', 'W', 'retired');
    }

    public function assign($user, $shop, $role, $active = 1)
    {
        $this->pdo->prepare("INSERT INTO shopusers (shop_SHID, user_USID, UserRoles_URID, is_active) VALUES (?, ?, ?, ?)")
            ->execute([$this->shops[$shop], $this->users[$user], $this->roles[$role], $active]);
    }

    public function setActive($user, $shop, $active)
    {
        $this->pdo->prepare("UPDATE shopusers SET is_active = ? WHERE user_USID = ? AND shop_SHID = ?")
            ->execute([$active, $this->users[$user], $this->shops[$shop]]);
    }

    public function suid($user, $shop)
    {
        $stmt = $this->pdo->prepare("SELECT SUID FROM shopusers WHERE user_USID = ? AND shop_SHID = ?");
        $stmt->execute([$this->users[$user], $this->shops[$shop]]);
        return (int) $stmt->fetchColumn();
    }

    //the role a user (by name, without the e2e_ prefix) holds in a shop, or 0
    public function roleOf($name, $shop)
    {
        $stmt = $this->pdo->prepare("SELECT shopusers.UserRoles_URID FROM shopusers
            INNER JOIN user ON user.USID = shopusers.user_USID
            WHERE user.UserName = ? AND shopusers.shop_SHID = ?");
        $stmt->execute(['e2e_' . $name, $this->shops[$shop]]);
        return (int) $stmt->fetchColumn();
    }

    //a user's id by name (without the e2e_ prefix), 0 when there is no such user
    public function userId($name)
    {
        $stmt = $this->pdo->prepare("SELECT USID FROM user WHERE UserName = ?");
        $stmt->execute(['e2e_' . $name]);
        return (int) $stmt->fetchColumn();
    }

    public function passwordIs($name, $password)
    {
        $stmt = $this->pdo->prepare("SELECT UserPwd FROM user WHERE UserName = ?");
        $stmt->execute(['e2e_' . $name]);
        $hash = $stmt->fetchColumn();
        return is_string($hash) && password_verify($password, $hash);
    }

    public function setPassword($name, $password)
    {
        $this->pdo->prepare("UPDATE user SET UserPwd = ? WHERE UserName = ?")
            ->execute([password_hash($password, PASSWORD_DEFAULT), 'e2e_' . $name]);
    }

    //the password reset token the old forgot-password flow stored, or null
    public function resetToken($name)
    {
        $stmt = $this->pdo->prepare("SELECT PwdChange FROM user WHERE UserName = ?");
        $stmt->execute(['e2e_' . $name]);
        $token = $stmt->fetchColumn();
        return $token === false || $token === null || $token === '' ? null : $token;
    }

    public function roleActive($key)
    {
        $stmt = $this->pdo->prepare("SELECT ur_status FROM userroles WHERE URID = ?");
        $stmt->execute([$this->roles[$key]]);
        return (int) $stmt->fetchColumn() === 1;
    }

    public function lastLogin($user)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM userlog WHERE user_USID = ?");
        $stmt->execute([$this->users[$user]]);
        return (int) $stmt->fetchColumn();
    }

    public function down()
    {
        $users = "SELECT USID FROM user WHERE UserName LIKE 'e2e\\_%'";
        $roles = "SELECT URID FROM userroles WHERE UserRoleName LIKE 'e2e %'";
        $shops = "SELECT SHID FROM shop WHERE ShopName LIKE 'e2e %'";
        $this->pdo->exec("DELETE FROM shopusers WHERE user_USID IN ($users) OR shop_SHID IN ($shops)");
        $this->pdo->exec("DELETE FROM userlog WHERE user_USID IN ($users)");
        $this->pdo->exec("DELETE FROM userroleaccess WHERE UserRolls_URID IN ($roles)");
        $this->pdo->exec("DELETE FROM usermoduleaccess WHERE UserRoles_URID IN ($roles)");
        $this->pdo->exec("DELETE FROM user WHERE UserName LIKE 'e2e\\_%'");
        $this->pdo->exec("DELETE FROM userroles WHERE UserRoleName LIKE 'e2e %'");
        $this->pdo->exec("DELETE FROM shop WHERE ShopName LIKE 'e2e %'");
        $this->pdo->exec("DELETE FROM company WHERE ComName = 'e2e Company'");
    }
}//E2EFixtures

$failures = 0;
function check($label, $ok, E2EBrowser $browser = null)
{
    global $failures;
    echo ($ok ? "  PASS  " : "  FAIL  ") . $label . "\n";
    if (!$ok) {
        $failures++;
        if ($browser !== null) {
            echo "        at " . $browser->url . " (HTTP " . $browser->status . ")\n";
        }
    }
}//check

function checkClean($label, E2EBrowser $browser)
{
    $errors = $browser->phpErrors();
    check($label . ' renders without PHP warnings', empty($errors), $browser);
    foreach ($errors as $error) {
        echo "        " . trim($error) . "\n";
    }
}//checkClean

function signIn(E2EBrowser $browser, $user, $password)
{
    $browser->get('Public/logout.php');
    return $browser->post('Controller/userController.php', ['user_name' => $user, 'user_pwd' => $password, 'btn_log_in' => 'Sign In']);
}//signIn

function shopLogin(E2EBrowser $browser, $shop_id, $user, $password, $csrf = null)
{
    $browser->get('Public/dashboard.php');
    return $browser->post('Controller/shopController.php', [
        'btn_shop_login' => 'Sign In',
        'shop_id' => $shop_id,
        'user_name' => $user,
        'user_pwd' => $password,
        'csrf_token' => $csrf === null ? $browser->csrf() : $csrf,
    ]);
}//shopLogin

//a sidebar link only a role with that feature sees
function sees(E2EBrowser $browser, $page) { return $browser->has('href="../Public/' . $page); }

//stock for the scanner upload checks: products, batches, GRNs and a transfer in the e2e shops
//(found again by the e2e shops, so a crashed run is cleaned up by the next one)
class E2EStock
{
    private $pdo;
    private $fx;
    public $products = [];
    public $inventory = [];
    public $grn = [];
    public $transfer = 0;

    public function __construct(PDO $pdo, E2EFixtures $fx)
    {
        $this->pdo = $pdo;
        $this->fx = $fx;
    }

    public function up()
    {
        $this->down();
        $W = $this->fx->shops['W'];
        $S = $this->fx->shops['S'];
        //Store Keeper may change GRNs (feature 2) and transfers (4); Cashier may not
        $this->pdo->prepare("UPDATE userroleaccess SET is_create = 1, is_edit = 1, is_verify = 1
            WHERE UserRolls_URID = ? AND SysFeatures_SFID IN (2, 4)")->execute([$this->fx->roles['keeper']]);

        foreach (['bed' => ['E2EBED01', 'e2e Bed', 1000, 1500], 'sheet' => ['E2ESHT01', 'e2e Bedsheet', 200, 350]] as $key => $p) {
            $this->pdo->prepare("INSERT INTO products (Barcode, ItemName, ProdPurchasePrice, ProdSellPrice, ProductStat, ItemType,
                user_USID, Subcategories_SCID, shop_SHID, PurchaseUnit, UnitConversion, SellingUnit, prodFlatDiscount)
                VALUES (?, ?, ?, ?, 1, 'P', ?, 1, ?, 1, 1, 1, 0)")
                ->execute([$p[0], $p[1], $p[2], $p[3], $this->fx->users['admin'], $W]);
            $this->products[$key] = (int) $this->pdo->lastInsertId();
        }
        $this->inventory['bed1'] = $this->stock('bed', 5, 'E2EB1', 900, 1400);
        $this->inventory['bed2'] = $this->stock('bed', 5, 'E2EB2', 950, 1450);
        $this->inventory['sheet'] = $this->stock('sheet', 10, 'E2ES1', 200, 350);

        $this->grn['open'] = $this->grn($W, 0);
        $this->grn['verified'] = $this->grn($W, 2);
        $this->grn['showroom'] = $this->grn($S, 0);
        $this->pdo->prepare("INSERT INTO transferheader (TransferNo, EffectiveDate, TransferFrom, TransferTo, TransferTotalCount,
            TransferTotalAmount, TransferStat, shop_SHID, user_USID) VALUES ('E2E-T1', CURDATE(), ?, ?, 0, 0, 0, ?, ?)")
            ->execute([$W, $S, $W, $this->fx->users['alice']]);
        $this->transfer = (int) $this->pdo->lastInsertId();
    }

    private function stock($product, $qty, $batch, $purchase, $selling)
    {
        $this->pdo->prepare("INSERT INTO inventory (CurrentQty, BillQty, ReturnQty, TransferInQty, TransferOutQty, products_PDID,
            shop_SHID, RackID, BatchID) VALUES (?, 0, 0, 0, 0, ?, ?, 1, ?)")
            ->execute([$qty, $this->products[$product], $this->fx->shops['W'], $batch]);
        $id = (int) $this->pdo->lastInsertId();
        $this->pdo->prepare("INSERT INTO pricehistory (ProductID, VariationID, EffectiveDate, PurchasePrice, SellingPrice, labelPrice,
            BatchID, Inventory_INID) VALUES (?, 0, CURDATE(), ?, ?, ?, ?, ?)")
            ->execute([$this->products[$product], $purchase, $selling, $selling, $batch, $id]);
        return $id;
    }

    private function grn($shop, $stat)
    {
        $this->pdo->prepare("INSERT INTO grnheader (GRNHeaderNo, EffectiveDate, InvoiceNo, ItemCount, TotalPurchasePrice, TotalSellPrice,
            GRNStat, user_USID, shop_SHID, Suppliers_SPID, SuppPayment, SuppBalance, excessAmount, refference)
            VALUES ('E2E-GRN', CURDATE(), 'E2E', 0, 0, 0, ?, ?, ?, (SELECT MIN(SPID) FROM suppliers), 0, 0, 0, '')")
            ->execute([$stat, $this->fx->users['alice'], $shop]);
        return (int) $this->pdo->lastInsertId();
    }

    //the lines of a GRN, for the checks
    public function grnLines($grn)
    {
        $stmt = $this->pdo->prepare("SELECT products_PDID, InitQty FROM grndetails WHERE GRNHeader_GHID = ? ORDER BY GDID");
        $stmt->execute([$grn]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //the lines of the e2e transfer, for the checks
    public function transferLines()
    {
        $stmt = $this->pdo->prepare("SELECT InventoryID, TransferQty, ReceivedQty FROM transferdetails WHERE TransferHeader_THID = ? ORDER BY TDID");
        $stmt->execute([$this->transfer]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function down()
    {
        $shops = "SELECT SHID FROM shop WHERE ShopName LIKE 'e2e %'";
        $grns = "SELECT GHID FROM grnheader WHERE shop_SHID IN ($shops)";
        $transfers = "SELECT THID FROM transferheader WHERE TransferFrom IN ($shops) OR TransferTo IN ($shops)";
        $this->pdo->exec("DELETE FROM scanbatches WHERE shop_SHID IN ($shops)");
        $this->pdo->exec("DELETE FROM grndetails WHERE GRNHeader_GHID IN ($grns)");
        $this->pdo->exec("DELETE FROM grnheader WHERE shop_SHID IN ($shops)");
        $this->pdo->exec("DELETE FROM transferdetails WHERE TransferHeader_THID IN ($transfers)");
        $this->pdo->exec("DELETE FROM transferheader WHERE TransferFrom IN ($shops) OR TransferTo IN ($shops)");
        $this->pdo->exec("DELETE FROM pricehistory WHERE Inventory_INID IN (SELECT INID FROM inventory WHERE shop_SHID IN ($shops))");
        $this->pdo->exec("DELETE FROM inventory WHERE shop_SHID IN ($shops)");
        $this->pdo->exec("DELETE FROM products WHERE shop_SHID IN ($shops)");
    }
}//E2EStock
