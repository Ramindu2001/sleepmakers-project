<?php
/**
 * Shop access module installer - see db/SHOP_ACCESS_MODULE.md.
 * -----------------------------------------------------------------------------
 * Run it once, BEFORE uploading the module's code:
 *      cli     : php db/shop_access_install.php
 *      browser : /db/shop_access_install.php   (signed-in administrator only)
 *
 * Idempotent and additive only - running it twice is harmless.
 */

$sai_browser = (php_sapi_name() !== 'cli');

if ($sai_browser) {
    session_start();
    header('Content-Type: text/plain; charset=utf-8');

    if (!isset($_SESSION['user_id'])) {
        echo "Please log in to Cloud POS first, then reload this page.";
        exit;
    }//not logged in
}//browser

require_once __DIR__ . '/../Includes/config.php';
require_once __DIR__ . '/../Model/DB_Class.php';
require_once __DIR__ . '/shop_access_migration.php';

//changing the schema is administrator business - checked with a direct query
if ($sai_browser) {
    $sai_check = (new DBTransactions())->getMultipleData("SELECT UserType FROM user WHERE USID = ?;", array((int) $_SESSION['user_id']));

    if (empty($sai_check) || (int) $sai_check[0]['UserType'] !== 1) {
        echo "Only an administrator can run the shop access installer.";
        exit;
    }//not an administrator
}//browser

//reaches the shared PDO handle; Dbh::connect() is protected by design
class ShopAccessInstaller extends Dbh
{
    public function pdo()
    {
        return $this->connect();
    }//pdo
}//ShopAccessInstaller

$pdo = (new ShopAccessInstaller())->pdo();

echo "Shop access module installer\n";
echo "database: " . $pdo->query('SELECT DATABASE();')->fetchColumn() . "\n";
echo str_repeat('-', 60) . "\n";

try {
    foreach ((new ShopAccessMigration($pdo))->run() as $line) {
        echo $line . "\n";
    }//each step
    echo str_repeat('-', 60) . "\n";
    echo "Done.\n";
} catch (PDOException $e) {
    echo "[FAILED] " . $e->getMessage() . "\n";
    echo "The steps above were applied; nothing after the failing step was. Fix the cause and run the installer again.\n";
    exit(1);
}//catch
