<?php
//End-to-end check of the shop login and per-shop access against a running copy of the
//application - by default the local XAMPP site:
//
//   C:/xampp/php/php.exe tests/e2e/shop_login_e2e.php [base-url]
//
//It creates its own company, shops, roles and users (all named "e2e..."), drives the site over
//HTTP like a browser (cookies, redirects, CSRF tokens) and removes everything it created at the
//end, even when a check fails. It uses the application's own database settings
//(Includes/config.php): run it only against a development database.
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
        return preg_match('/id="(?:shop_login|assign)_csrf_token"[^>]*value="([0-9a-f]{64})"/', $this->body, $m) ? $m[1] : '';
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

$fx = new E2EFixtures((new E2EDb())->pdo());
$fx->up();
$pw = $fx->password;
$W = $fx->shops['W'];
$S = $fx->shops['S'];

try {
    $b = new E2EBrowser($base);

    echo "Shop login\n";
    signIn($b, 'e2e_alice', $pw);
    check('alice with two shops lands on the shop screen', $b->isOn('Public/dashboard.php'), $b);
    check('both of her shops are offered', $b->has('e2e Warehouse') && $b->has('e2e Showroom'), $b);
    check('the shop login dialog is on the page', $b->has('id="shop_login_modal"') && $b->csrf() !== '', $b);
    checkClean('the shop screen', $b);

    shopLogin($b, $W, 'e2e_alice', 'wrong-password');
    check('wrong password is refused', $b->isOn('Public/dashboard.php') && $b->has('Invalid username or password'), $b);
    checkClean('the refused shop login', $b);

    shopLogin($b, $W, 'e2e_bob', $pw);
    check('bob cannot log into a shop he is not assigned to', $b->has('You do not have access to this shop.'), $b);

    shopLogin($b, $W, 'e2e_alice', $pw, str_repeat('0', 64));
    check('a forged form (bad CSRF token) is refused', $b->has('Your session expired. Please try again.'), $b);

    $b->post('Controller/shopController.php', ['btn_continue' => '1', 'cmb_shops' => $W]);
    $b->get('Public/home.php');
    check('the old shop picker post no longer opens a shop', $b->isOn('Public/dashboard.php'), $b);

    shopLogin($b, $W, 'e2e_alice', $pw);
    check('alice logs into the warehouse', $b->isOn('Public/home.php') && $b->status === 200, $b);
    check('as Store Keeper there she sees Store', sees($b, 'store.php'), $b);
    checkClean('home as a Store Keeper', $b);

    $b->get('Public/switchshop.php');
    check('Switch Shop returns to the shop screen', $b->isOn('Public/dashboard.php'), $b);
    shopLogin($b, $S, 'e2e_alice', $pw);
    check('alice logs into the showroom', $b->isOn('Public/home.php'), $b);
    check('as Cashier there she does not see Store', !sees($b, 'store.php'), $b);

    $b->get('Public/switchshop.php');
    $before = $fx->lastLogin('bob');
    shopLogin($b, $S, 'e2e_bob', $pw);
    check('bob takes over the counter in the showroom', $b->isOn('Public/home.php'), $b);
    check('the session is now bob\'s (Store Keeper in the showroom sees Store)', sees($b, 'store.php'), $b);
    check('bob\'s login is logged', $fx->lastLogin('bob') === $before + 1);

    echo "Access re-checked\n";
    signIn($b, 'e2e_alice', $pw);
    shopLogin($b, $W, 'e2e_alice', $pw);
    $fx->setActive('alice', 'W', 0);
    $b->get('Public/home.php');
    check('revoked access sends alice back to the shop screen on her next click', $b->isOn('Public/dashboard.php'), $b);
    check('and tells her why', $b->has('Your access to this shop has been removed.'), $b);
    check('the revoked shop is no longer offered', !$b->has('e2e Warehouse') && $b->has('e2e Showroom'), $b);
    $fx->setActive('alice', 'W', 1);

    shopLogin($b, $W, 'e2e_alice', $pw);
    $fx->setActive('alice', 'W', 0);
    $b->post('Includes/newauthcheck.php', []);
    check('an idle screen\'s poll also reports the lost access (-1)', trim($b->body) === '-1', $b);
    $fx->setActive('alice', 'W', 1);

    signIn($b, 'e2e_alice', $pw);
    shopLogin($b, $W, 'e2e_alice', $pw);
    //the menu label, not the URL: the session poll's JavaScript names switchshop.php on every page
    check('Switch Shop is offered to a normal user', $b->has('>Switch Shop</p>'), $b);

    echo "Main login\n";
    signIn($b, 'e2e_bob', $pw);
    check('bob, with one shop, goes straight into it', $b->isOn('Public/home.php'), $b);
    signIn($b, 'e2e_carol', $pw);
    check('carol, whose only role is inactive, is told so', $b->isOn('Public/login.php') && $b->has('Inactive userrole'), $b);
    $fx->setActive('bob', 'S', 0);
    signIn($b, 'e2e_bob', $pw);
    check('bob, whose only shop was revoked, has no shops', $b->isOn('Public/login.php') && $b->has('No shops assigned'), $b);
    $fx->setActive('bob', 'S', 1);

    echo "Remember me\n";
    $b->get('Public/logout.php');
    $b->post('Controller/userController.php', ['user_name' => 'e2e_bob', 'user_pwd' => $pw, 'btn_log_in' => 'Sign In', 'remember_me' => 'on']);
    $b->dropSession();
    $b->get('Public/login.php');
    check('a remembered single-shop user comes straight back into his shop', $b->isOn('Public/home.php'), $b);

    $b->get('Public/logout.php');
    $b->post('Controller/userController.php', ['user_name' => 'e2e_alice', 'user_pwd' => $pw, 'btn_log_in' => 'Sign In', 'remember_me' => 'on']);
    shopLogin($b, $W, 'e2e_alice', $pw);
    $b->dropSession();
    $b->get('Public/login.php');
    check('a remembered user comes back into the shop she signed into', $b->isOn('Public/home.php') && sees($b, 'store.php'), $b);
    $fx->setActive('alice', 'W', 0);
    $b->dropSession();
    $b->get('Public/login.php');
    check('but not once her access to it was revoked', $b->isOn('Public/dashboard.php'), $b);
    $fx->setActive('alice', 'W', 1);
    $b->get('Public/logout.php');

    echo "Assign Users to Shops\n";
    $endpoint = 'Controller/AddUsersToShopsController.php';
    signIn($b, 'e2e_alice', $pw);
    shopLogin($b, $W, 'e2e_alice', $pw);
    $b->post($endpoint, ['action' => 'set_active', 'suid' => $fx->suid('alice', 'W'), 'active' => 0]);
    check('a normal user cannot change shop access (403)', $b->status === 403, $b);
    $b->get('Public/AssignUsersToShops.php');
    check('a normal user cannot open the admin screen', $b->isOn('Public/home.php') && !$b->has('<th>Access</th>'), $b);

    signIn($b, 'e2e_admin', $pw);
    shopLogin($b, $W, 'e2e_admin', $pw);
    $b->get('Public/AssignUsersToShops.php');
    $token = $b->csrf();
    check('the admin screen shows role and access columns', $b->has('<th>Role</th>') && $b->has('<th>Access</th>') && $token !== '', $b);
    checkClean('the admin screen', $b);

    $b->post($endpoint, ['action' => 'save', 'shop_id' => $W, 'user_id' => $fx->users['bob'], 'role_id' => $fx->roles['cashier']]);
    check('a request without the CSRF token is refused (400)', $b->status === 400, $b);

    $b->post($endpoint, ['action' => 'save', 'shop_id' => $W, 'user_id' => $fx->users['bob'], 'role_id' => $fx->roles['cashier'], 'csrf_token' => $token]);
    check('admin assigns bob to the warehouse as Cashier', $b->status === 200 && $b->json('ok') === true, $b);
    $b->post($endpoint, ['action' => 'save', 'shop_id' => $W, 'user_id' => $fx->users['bob'], 'role_id' => $fx->roles['keeper'], 'csrf_token' => $token]);
    check('assigning him twice is refused', $b->status === 409, $b);
    $b->post($endpoint, ['action' => 'save', 'shop_id' => $S, 'user_id' => $fx->users['carol'], 'role_id' => $fx->roles['retired'], 'csrf_token' => $token]);
    check('an inactive role cannot be given', $b->status === 422, $b);

    $bobW = $fx->suid('bob', 'W');
    $b->post($endpoint, ['action' => 'update_role', 'suid' => $bobW, 'role_id' => $fx->roles['keeper'], 'csrf_token' => $token]);
    check('admin changes bob\'s warehouse role', $b->json('ok') === true, $b);
    $b->post($endpoint, ['action' => 'set_active', 'suid' => $bobW, 'active' => 0, 'csrf_token' => $token]);
    check('admin revokes bob\'s warehouse access', $b->json('message') === 'Access revoked.', $b);

    $bob = new E2EBrowser($base);
    signIn($bob, 'e2e_bob', $pw);
    check('bob is back to one shop and goes straight in', $bob->isOn('Public/home.php'), $bob);

    $b->post($endpoint, ['action' => 'delete', 'suid' => $bobW, 'csrf_token' => $token]);
    check('an assignment without history can be deleted', $b->json('ok') === true && $fx->suid('bob', 'W') === 0, $b);

    //scenarios of later tasks are added above this line
} finally {
    $fx->down();
}

echo $failures === 0 ? "\nAll checks passed.\n" : "\n" . $failures . " check(s) FAILED.\n";
exit($failures === 0 ? 0 : 1);
