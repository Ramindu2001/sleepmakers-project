<?php
/**
 * POS to warehouse fulfilment installer - see db/WAREHOUSE_FULFILMENT_MODULE.md.
 * -----------------------------------------------------------------------------
 * Run it once on the server, BEFORE uploading the code:
 *      php db/warehouse_fulfilment_install.php
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
require_once __DIR__ . '/warehouse_fulfilment_migration.php';

//reaches the shared PDO handle; Dbh::connect() is protected by design
class WarehouseFulfilmentInstaller extends Dbh
{
    public function pdo()
    {
        return $this->connect();
    }//pdo
}//WarehouseFulfilmentInstaller

$pdo = (new WarehouseFulfilmentInstaller())->pdo();

echo "POS to warehouse fulfilment installer\n";
echo "database: " . $pdo->query('SELECT DATABASE();')->fetchColumn() . "\n";
echo str_repeat('-', 60) . "\n";

try {
    foreach ((new WarehouseFulfilmentMigration($pdo))->run() as $line) {
        echo $line . "\n";
    }//each step
    echo str_repeat('-', 60) . "\n";
    echo "Done. Grant the Customer Orders right to the roles that need it in\n";
    echo "Settings -> User Roles: the shop's cashiers need View, the warehouse needs Verify.\n";
} catch (PDOException $e) {
    echo "[FAILED] " . $e->getMessage() . "\n";
    echo "The steps above were applied; nothing after the failing step was. Fix the cause and run the installer again.\n";
    exit(1);
}//catch
