<?php
/**
 * Unique barcode per unit installer - see db/UNIT_BARCODES_MODULE.md.
 * -----------------------------------------------------------------------------
 * Run it once on the server, BEFORE uploading the code:
 *      php db/unit_barcodes_install.php
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
require_once __DIR__ . '/unit_barcodes_migration.php';

//reaches the shared PDO handle; Dbh::connect() is protected by design
class UnitBarcodesInstaller extends Dbh
{
    public function pdo()
    {
        return $this->connect();
    }//pdo
}//UnitBarcodesInstaller

$pdo = (new UnitBarcodesInstaller())->pdo();

echo "Unique barcode per unit installer\n";
echo "database: " . $pdo->query('SELECT DATABASE();')->fetchColumn() . "\n";
echo str_repeat('-', 60) . "\n";

try {
    foreach ((new UnitBarcodesMigration($pdo))->run() as $line) {
        echo $line . "\n";
    }//each step
    echo str_repeat('-', 60) . "\n";
    echo "Done. Nothing changes until a shop switches unit barcodes on in\n";
    echo "Settings -> Barcode Settings -> Unique barcode per unit.\n";
} catch (PDOException $e) {
    echo "[FAILED] " . $e->getMessage() . "\n";
    echo "The steps above were applied; nothing after the failing step was. Fix the cause and run the installer again.\n";
    exit(1);
}//catch
