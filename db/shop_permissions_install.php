<?php
/**
 * Per-shop permissions installer - see db/SHOP_PERMISSIONS_MODULE.md.
 * -----------------------------------------------------------------------------
 * Run it once on the server, BEFORE uploading the code:
 *      php db/shop_permissions_install.php
 * Idempotent and additive only - running it twice is harmless. Command line only (db/ is
 * closed to the web).
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}//never from a browser

ob_start(); //Includes/config.php starts with a byte order mark
require_once __DIR__ . '/../Includes/config.php';
ob_end_clean();
require_once __DIR__ . '/shop_permissions_migration.php';

//reaches the shared PDO handle; Dbh::connect() is protected by design
class ShopPermissionsInstaller extends Dbh
{
    public function pdo()
    {
        return $this->connect();
    }//pdo
}//ShopPermissionsInstaller

$pdo = (new ShopPermissionsInstaller())->pdo();

echo "Per-shop permissions installer\n";
echo "database: " . $pdo->query('SELECT DATABASE();')->fetchColumn() . "\n";
echo str_repeat('-', 60) . "\n";

try {
    foreach ((new ShopPermissionsMigration($pdo))->run() as $line) {
        echo $line . "\n";
    }//each step
    echo str_repeat('-', 60) . "\n";
    echo "Done. Every role now carries its own ticks in every shop, starting with the ticks it had\n";
    echo "before. Sign in to a shop and open Settings -> User Roles to set that shop's ticks.\n";
} catch (PDOException $e) {
    echo "[FAILED] " . $e->getMessage() . "\n";
    echo "The steps above were applied; nothing after the failing step was. Fix the cause and run the installer again.\n";
    exit(1);
}//catch
