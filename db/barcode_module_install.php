<?php
/**
 * Barcode module installer.
 * -----------------------------------------------------------------------------
 * Applies db/barcode_module.sql through information_schema checks instead of
 * "IF NOT EXISTS", so it works on MySQL 5.7 as well as MariaDB.
 *
 * Run it once after deploying the barcode module:
 *      browser : /Cloud_POS/db/barcode_module_install.php
 *      cli     : php db/barcode_module_install.php
 *
 * It is idempotent - every step is skipped when it is already in place, so
 * running it twice is harmless.
 *
 * ADDITIVE ONLY. It never drops, renames or re-types anything that exists.
 */

$bci_browser = (php_sapi_name() !== 'cli');

if ($bci_browser) {
    session_start();
    header('Content-Type: text/plain; charset=utf-8');

    if (!isset($_SESSION['user_id'])) {
        echo "Please log in to Cloud POS first, then reload this page.";
        exit;
    }//not logged in
}//browser

require_once __DIR__ . '/../Includes/config.php';
require_once __DIR__ . '/../Model/DB_Class.php';

/*
 * Changing the schema, and the duplicate barcode dump at the end, are both
 * administrator business - a logged in cashier must not be able to run this by
 * guessing the URL. Checked with a direct query because the helper that
 * normally answers this lives behind the very tables being created.
 */
if ($bci_browser) {
    $bci_check = (new DBTransactions())->getData(
        "SELECT UserType FROM user WHERE USID = " . (int) $_SESSION['user_id'] . ";"
    );

    if (empty($bci_check) || (int) $bci_check[0]['UserType'] !== 1) {
        echo "Only an administrator can run the barcode module installer.";
        exit;
    }//not an administrator
}//browser

/**
 * Small shim so the installer can reach the shared PDO handle that the rest of
 * the application uses. Dbh::connect() is protected by design.
 */
class BarcodeInstaller extends Dbh
{
    public function pdo()
    {
        return $this->connect();
    }//pdo
}//BarcodeInstaller

$pdo = (new BarcodeInstaller())->pdo();
$schema = $pdo->query('SELECT DATABASE();')->fetchColumn();

echo "Barcode module installer\n";
echo "database: " . $schema . "\n";
echo str_repeat('-', 60) . "\n";

/**
 * Does a table exist in the current schema?
 */
function bciHasTable($pdo, $schema, $table)
{
    $sql = "SELECT COUNT(*) FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?;";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array($schema, $table));

    return ((int) $stmt->fetchColumn() > 0);
}//bciHasTable

/**
 * Does a column exist on a table in the current schema?
 */
function bciHasColumn($pdo, $schema, $table, $column)
{
    $sql = "SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?;";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array($schema, $table, $column));

    return ((int) $stmt->fetchColumn() > 0);
}//bciHasColumn

/**
 * Does an index exist on a table in the current schema?
 */
function bciHasIndex($pdo, $schema, $table, $index)
{
    $sql = "SELECT COUNT(*) FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?;";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array($schema, $table, $index));

    return ((int) $stmt->fetchColumn() > 0);
}//bciHasIndex

/**
 * Run one migration step and report what happened.
 */
function bciStep($label, $needed, $pdo, $sql)
{
    if (!$needed) {
        echo "[ skip ] " . $label . " - already in place\n";
        return;
    }//nothing to do

    try {
        $pdo->exec($sql);
        echo "[  ok  ] " . $label . "\n";
    }//applied
    catch (Throwable $e) {
        echo "[ FAIL ] " . $label . " - " . $e->getMessage() . "\n";
    }//failed
}//bciStep

//----------------------------------------------------------- category codes
bciStep(
    'categories.CategoryCode',
    !bciHasColumn($pdo, $schema, 'categories', 'CategoryCode'),
    $pdo,
    "ALTER TABLE `categories`
        ADD COLUMN `CategoryCode` VARCHAR(12) NULL DEFAULT NULL AFTER `CategoryName`;"
);

bciStep(
    'subcategories.SubCatCode',
    !bciHasColumn($pdo, $schema, 'subcategories', 'SubCatCode'),
    $pdo,
    "ALTER TABLE `subcategories`
        ADD COLUMN `SubCatCode` VARCHAR(12) NULL DEFAULT NULL AFTER `SubCatName`;"
);

//------------------------------------------------------------ barcode index
/*
 * Deliberately NOT unique: duplicate barcodes already exist in live data and a
 * unique index would make the next product save fail.
 */
bciStep(
    'products.idx_products_barcode',
    !bciHasIndex($pdo, $schema, 'products', 'idx_products_barcode'),
    $pdo,
    "ALTER TABLE `products` ADD INDEX `idx_products_barcode` (`Barcode`);"
);

//--------------------------------------------------------------- settings
bciStep(
    'table barcodesettings',
    !bciHasTable($pdo, $schema, 'barcodesettings'),
    $pdo,
    "CREATE TABLE `barcodesettings` (
        `BSID`          INT(11)      NOT NULL AUTO_INCREMENT,
        `shop_SHID`     INT(11)      NOT NULL,
        `AutoGenerate`  TINYINT(4)   NOT NULL DEFAULT 1,
        `Pattern`       VARCHAR(160) NOT NULL DEFAULT '{PREFIX}{SUB}{SEQ}',
        `FixedPrefix`   VARCHAR(24)  NOT NULL DEFAULT '',
        `Suffix`        VARCHAR(24)  NOT NULL DEFAULT '',
        `ShopCode`      VARCHAR(12)  NOT NULL DEFAULT '',
        `Separator`     VARCHAR(4)   NOT NULL DEFAULT '',
        `CatCodeLength` TINYINT(4)   NOT NULL DEFAULT 3,
        `SubCodeLength` TINYINT(4)   NOT NULL DEFAULT 3,
        `SeqScope`      VARCHAR(16)  NOT NULL DEFAULT 'pattern',
        `SeqStart`      BIGINT(20)   NOT NULL DEFAULT 1,
        `SeqStep`       INT(11)      NOT NULL DEFAULT 1,
        `SeqLength`     TINYINT(4)   NOT NULL DEFAULT 5,
        `SeqPadChar`    VARCHAR(1)   NOT NULL DEFAULT '0',
        `Casing`        VARCHAR(8)   NOT NULL DEFAULT 'upper',
        `Symbology`     VARCHAR(12)  NOT NULL DEFAULT 'CODE128',
        `MaxLength`     TINYINT(4)   NOT NULL DEFAULT 45,
        `StripInvalid`  TINYINT(4)   NOT NULL DEFAULT 1,
        `LabelDefaults` TEXT         NULL DEFAULT NULL,
        `UpdatedDate`   DATETIME     NULL DEFAULT NULL,
        `UpdateUserID`  INT(11)      NULL DEFAULT NULL,
        PRIMARY KEY (`BSID`),
        UNIQUE KEY `uq_barcodesettings_shop` (`shop_SHID`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;"
);

//--------------------------------------------------------------- sequences
bciStep(
    'table barcodesequence',
    !bciHasTable($pdo, $schema, 'barcodesequence'),
    $pdo,
    "CREATE TABLE `barcodesequence` (
        `BQID`        BIGINT(20)   NOT NULL AUTO_INCREMENT,
        `shop_SHID`   INT(11)      NOT NULL,
        `ScopeKey`    VARCHAR(160) NOT NULL,
        `NextValue`   BIGINT(20)   NOT NULL DEFAULT 1,
        `UpdatedDate` DATETIME     NULL DEFAULT NULL,
        PRIMARY KEY (`BQID`),
        UNIQUE KEY `uq_barcodesequence` (`shop_SHID`, `ScopeKey`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;"
);

echo str_repeat('-', 60) . "\n";

//--------------------------------------------------- duplicate barcode report
/*
 * Not a migration step - just something the shop should know about. The module
 * never creates a duplicate, but it cannot un-create the ones already there.
 */
$dupes = $pdo->query(
    "SELECT Barcode, COUNT(*) AS c FROM products
     WHERE Barcode IS NOT NULL AND Barcode <> ''
     GROUP BY Barcode HAVING c > 1 ORDER BY c DESC LIMIT 20;"
)->fetchAll(PDO::FETCH_ASSOC);

if (empty($dupes)) {
    echo "No duplicate barcodes found in products.\n";
}//clean
else {
    echo "Existing duplicate barcodes (fix these by hand, the module leaves them alone):\n";
    foreach ($dupes as $row) {
        echo "   " . $row['Barcode'] . "  x" . $row['c'] . "\n";
    }//foreach
}//has duplicates

echo "\nDone. You can now open Settings > Barcode Settings.\n";
