<?php
/**
 * Scanner upload installer - see db/SCAN_UPLOAD_MODULE.md.
 * -----------------------------------------------------------------------------
 * Run it once on the server, BEFORE uploading the code:
 *      php db/scan_upload_install.php
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
require_once __DIR__ . '/scan_upload_migration.php';

//reaches the shared PDO handle; Dbh::connect() is protected by design
class ScanUploadInstaller extends Dbh
{
    public function pdo()
    {
        return $this->connect();
    }//pdo
}//ScanUploadInstaller

$pdo = (new ScanUploadInstaller())->pdo();

echo "Scanner upload installer\n";
echo "database: " . $pdo->query('SELECT DATABASE();')->fetchColumn() . "\n";
echo str_repeat('-', 60) . "\n";

try {
    foreach ((new ScanUploadMigration($pdo))->run() as $line) {
        echo $line . "\n";
    }//each step
    echo str_repeat('-', 60) . "\n";
    echo "Done.\n";
} catch (PDOException $e) {
    echo "[FAILED] " . $e->getMessage() . "\n";
    exit(1);
}//catch
