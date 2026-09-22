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
        if (preg_match('/id="(?:shop_login|assign)_csrf_token"[^>]*value="([0-9a-f]{64})"/', $this->body, $m)) {
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
    check('bob, with one shop, still lands on the shop screen', $b->isOn('Public/dashboard.php') && $b->has('e2e Showroom'), $b);
    $b->get('Public/home.php');
    check('and cannot open a page before signing in to the shop', $b->isOn('Public/dashboard.php'), $b);
    shopLogin($b, $S, 'e2e_bob', $pw);
    check('he enters his shop by signing in to it', $b->isOn('Public/home.php'), $b);
    signIn($b, 'e2e_carol', $pw);
    check('carol, whose only role is inactive, is told so', $b->isOn('Public/login.php') && $b->has('Inactive userrole'), $b);
    $fx->setActive('bob', 'S', 0);
    signIn($b, 'e2e_bob', $pw);
    check('bob, whose only shop was revoked, has no shops', $b->isOn('Public/login.php') && $b->has('No shops assigned'), $b);
    $fx->setActive('bob', 'S', 1);

    echo "Remember me\n";
    $b->get('Public/logout.php');
    $b->post('Controller/userController.php', ['user_name' => 'e2e_bob', 'user_pwd' => $pw, 'btn_log_in' => 'Sign In', 'remember_me' => 'on']);
    shopLogin($b, $S, 'e2e_bob', $pw);
    $b->dropSession();
    $b->get('Public/login.php');
    check('a remembered user comes back signed in, to the shop screen', $b->isOn('Public/dashboard.php') && $b->has('e2e Showroom'), $b);
    $b->get('Public/home.php');
    check('the shop he was in is not reopened without its password', $b->isOn('Public/dashboard.php'), $b);
    check('no remembered shop is stored in the browser', !$b->hasCookie('remember_me_shop_token'), $b);

    $b->get('Public/logout.php');
    $b->post('Controller/userController.php', ['user_name' => 'e2e_alice', 'user_pwd' => $pw, 'btn_log_in' => 'Sign In', 'remember_me' => 'on']);
    shopLogin($b, $W, 'e2e_alice', $pw);
    $b->dropSession();
    $b->get('Public/home.php');
    check('a remembered user opening a shop page is sent to the shop screen', $b->isOn('Public/dashboard.php'), $b);
    shopLogin($b, $W, 'e2e_alice', $pw);
    check('and gets back in by signing in to the shop', $b->isOn('Public/home.php') && sees($b, 'store.php'), $b);
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
    check('bob is back to one shop, offered on the shop screen', $bob->isOn('Public/dashboard.php') && !$bob->has('e2e Warehouse') && $bob->has('e2e Showroom'), $bob);

    $b->post($endpoint, ['action' => 'delete', 'suid' => $bobW, 'csrf_token' => $token]);
    check('an assignment without history can be deleted', $b->json('ok') === true && $fx->suid('bob', 'W') === 0, $b);

    echo "Users page\n";
    $b->get('Public/users.php');
    check('the users page calls the role the Default Role', $b->has('<th>Default Role</th>') && $b->has('Assign Users to Shops.</small>'), $b);
    checkClean('the users page', $b);
    $usersToken = $b->csrf();
    $b->post('Controller/userController.php', ['add-user' => '1', 'username' => 'e2e_dave', 'userEmail' => 'e2e_dave@example.com',
        'userContact' => '0000000000', 'password' => $pw, 'userRole' => $fx->roles['keeper']]);
    check('the admin\'s add-user without the CSRF token is refused', $fx->userId('dave') === 0 && $b->has('Your session expired.'), $b);
    $b->post('Controller/userController.php', ['add-user' => '1', 'username' => 'e2e_dave', 'userEmail' => 'e2e_dave@example.com',
        'userContact' => '0000000000', 'password' => $pw, 'userRole' => $fx->roles['keeper'], 'csrf_token' => $usersToken]);
    check('a new user joins the current shop with the role chosen for them', $fx->roleOf('dave', 'W') === $fx->roles['keeper'], $b);
    $b->post('Controller/userController.php', ['chng-pwd' => '1', 'euid' => $fx->userId('dave'), 'epassword' => 'Dave-New-1', 'e-cpassword' => 'Dave-New-1', 'csrf_token' => $usersToken]);
    check('the admin resets dave\'s password', $fx->passwordIs('dave', 'Dave-New-1'), $b);

    echo "Users and roles: system admin only\n";
    $anon = new E2EBrowser($base);
    $anon->post('Controller/userController.php', ['add-user' => '1', 'username' => 'e2e_mallory', 'userEmail' => 'e2e_mallory@example.com',
        'userContact' => '0000000000', 'password' => $pw, 'userRole' => $fx->roles['keeper'], 'superadmin' => 'on']);
    check('someone not signed in cannot create a user, let alone a super admin (403)', $anon->status === 403 && $fx->userId('mallory') === 0, $anon);
    $anon->post('Controller/userController.php', ['chng-pwd' => '1', 'euid' => $fx->users['admin'], 'epassword' => 'owned', 'e-cpassword' => 'owned']);
    check('someone not signed in cannot reset the admin\'s password', $anon->status === 403 && $fx->passwordIs('admin', $pw), $anon);
    $anon->post('Controller/userController.php', ['uchng-pwd' => '1', 'ueuid' => $fx->users['admin'], 'uepassword' => 'owned', 'ue_cpassword' => 'owned']);
    check('nor change it through the profile form', $anon->isOn('Public/login.php') && $fx->passwordIs('admin', $pw), $anon);
    $anon->post('AJAX/UserRole/data.php', ['id' => $fx->roles['keeper'], 'status' => 0]);
    check('nor switch a role off (403)', $anon->status === 403 && $fx->roleActive('keeper'), $anon);

    $a = new E2EBrowser($base);
    signIn($a, 'e2e_alice', $pw);
    shopLogin($a, $W, 'e2e_alice', $pw);
    check('a normal user has no Users or User Role menu', !sees($a, 'users.php') && !sees($a, 'user-roles.php'), $a);
    $aliceToken = $a->csrf();
    foreach (['users.php', 'user-roles.php', 'add-role.php', 'edit-role.php?id=' . $fx->roles['keeper'], 'AssignUsersToShops.php'] as $page) {
        $a->get('Public/' . $page);
        check('a normal user is turned away from ' . strtok($page, '?') . ' on the server', $a->isOn('Public/home.php'), $a);
    }
    $a->post('Controller/userController.php', ['chng-pwd' => '1', 'euid' => $fx->users['bob'], 'epassword' => 'owned', 'e-cpassword' => 'owned', 'csrf_token' => $aliceToken]);
    check('a normal user cannot reset another user\'s password (403)', $a->status === 403 && $fx->passwordIs('bob', $pw), $a);
    $a->post('Controller/userController.php', ['edit-user' => '1', 'euserid' => $fx->users['alice'], 'username' => 'e2e_alice', 'userEmail' => 'e2e_alice@example.com',
        'userContact' => '0000000000', 'userRole' => $fx->roles['keeper'], 'status' => 'on', 'eprofile' => 'avator.svg', 'csrf_token' => $aliceToken]);
    check('nor edit users (403)', $a->status === 403, $a);
    $a->post('Controller/userrolecontrol.php', ['delete-role' => '1', 'delete_role_id' => $fx->roles['keeper'], 'csrf_token' => $aliceToken]);
    check('nor switch a role off through the role page (403)', $a->status === 403 && $fx->roleActive('keeper'), $a);
    $a->post('AJAX/UserRole/data.php', ['id' => $fx->roles['keeper'], 'status' => 0, 'csrf_token' => $aliceToken]);
    check('nor through the role editor\'s status switch (403)', $a->status === 403 && $fx->roleActive('keeper'), $a);

    echo "Own password\n";
    $a->get('Public/home.php');
    $aliceToken = $a->csrf();
    check('the profile password form asks for the current password', $a->has('name="ue_current_password"') && $aliceToken !== '', $a);
    $a->post('Controller/userController.php', ['uchng-pwd' => '1', 'ue_current_password' => 'wrong', 'uepassword' => 'Alice-New-1', 'ue_cpassword' => 'Alice-New-1', 'csrf_token' => $aliceToken]);
    check('a wrong current password changes nothing', $a->has('Your current password is incorrect.') && $fx->passwordIs('alice', $pw), $a);
    $a->post('Controller/userController.php', ['uchng-pwd' => '1', 'ue_current_password' => $pw, 'uepassword' => 'Alice-New-1', 'ue_cpassword' => 'Alice-New-1']);
    check('a form without the CSRF token changes nothing', $a->has('Your session expired.') && $fx->passwordIs('alice', $pw), $a);
    $a->post('Controller/userController.php', ['uchng-pwd' => '1', 'ueuid' => $fx->users['bob'], 'ue_current_password' => $pw, 'uepassword' => 'Alice-New-1', 'ue_cpassword' => 'Alice-New-1', 'csrf_token' => $aliceToken]);
    check('with the current password she changes her own', $a->has('Password Updated!') && $fx->passwordIs('alice', 'Alice-New-1'), $a);
    check('never someone else\'s, whatever id the form names', $fx->passwordIs('bob', $pw), $a);
    $fx->setPassword('alice', $pw);

    echo "Users and roles: the admin\n";
    $b->get('Public/user-roles.php');
    $rolesToken = $b->csrf();
    check('the admin opens User Roles', $b->isOn('Public/user-roles.php') && $rolesToken !== '', $b);
    checkClean('the user roles page', $b);
    $b->post('Controller/userrolecontrol.php', ['activate-role' => '1', 'activate_role_id' => $fx->roles['retired']]);
    check('a role change without the CSRF token is refused', !$fx->roleActive('retired') && $b->has('Your session expired.'), $b);
    $b->post('Controller/userrolecontrol.php', ['activate-role' => '1', 'activate_role_id' => $fx->roles['retired'], 'csrf_token' => $rolesToken]);
    check('the admin switches a role on', $fx->roleActive('retired'), $b);
    $b->post('AJAX/UserRole/data.php', ['id' => $fx->roles['retired'], 'status' => 0, 'csrf_token' => $rolesToken]);
    check('and off again from the role editor', $b->status === 200 && !$fx->roleActive('retired'), $b);
    $b->get('Public/edit-role.php?id=' . $fx->roles['keeper']);
    check('the role editor no longer offers Add Users or Add User Role', $b->isOn('Public/edit-role.php') && $b->has('<td>Price Change<input') && !$b->has('<td>Add Users<input') && !$b->has('<td>Add User Role<input'), $b);
    checkClean('the role editor', $b);

    echo "Forgot password\n";
    $anon->get('Public/forget-password.php');
    check('Forgot Password tells the user to contact the system admin', $anon->has('Please contact your system admin to reset your password.'), $anon);
    $anon->post('Controller/userController.php', ['btn_password_change' => 'Sign In', 'user_name' => 'e2e_alice']);
    check('the old reset-by-email request does nothing', $anon->isOn('Public/login.php') && $fx->resetToken('alice') === null, $anon);

    //scenarios of later tasks are added above this line
} finally {
    $fx->down();
}

echo $failures === 0 ? "\nAll checks passed.\n" : "\n" . $failures . " check(s) FAILED.\n";
exit($failures === 0 ? 0 : 1);
