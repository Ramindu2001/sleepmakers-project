# Scanner Upload for GRN and Transfer Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Staff upload a batch of scanned barcodes (from the company's scanner cradle) into a GRN, into a transfer being sent, or into a transfer being received, and get one checked line per product with its quantity.

**Architecture:** A browser dialog captures the cradle's keystrokes (no field to click) and sends the raw text to one JSON endpoint. The server parses it (`ScanParser`, pure), resolves products and builds a preview (`GrnScan`, `TransferScan` on a shared `ScanDocument` base), and applies it in one transaction, recording every applied batch in `scanbatches` (duplicate guard + audit).

**Tech Stack:** PHP 8.2 (XAMPP locally, ea-php82 on the server), MariaDB, PDO shared connection (`Dbh`), jQuery + Bootstrap 5 (bundle), PHPUnit 11 (`tools/phpunit.phar`), curl-based E2E, headless Chrome over the DevTools protocol from Node 24.

**Spec:** `docs/superpowers/specs/2026-09-22-scanner-upload-design.md`

## Global Constraints

- Branch `development`; commit and push after every task (`git push origin development`).
- Commit messages end with `Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>`.
- Keep each touched file's line endings (many PHP files are CRLF; use the Edit tool or byte-exact scripts).
- Manual GRN / transfer entry must behave exactly as before.
- Limits: 200 000 characters and 5 000 codes per upload.
- Rights: GRN = feature 2 (*Goods Received*) `is_create` or `is_edit`; transfer send = feature 4 (*Transfer Note*) `is_edit`; transfer receive = feature 4 `is_edit` or `is_verify`; a super admin (`UserType = 1`) has every right.
- Every endpoint answer is JSON `{ok, message, preview?, confirm?, result?}`; refusals use 400 (CSRF), 403 (rights / not signed in), 404 (not this shop's document), 409 (document closed, or a confirmation needed), 422 (lines to fix).
- Tests: `C:/xampp/php/php.exe tools/phpunit.phar` (database `sleepmakers_test`), E2E against `http://localhost/sleepmakers`.

---

### Task 1: Test schema, `scanbatches` table and feature rights

**Files:**
- Create: `tests/fixtures/stock_schema.sql` (already exported from the local database: products, units, variations, grnheader, grndetails, inventory, pricehistory, transferheader, transferdetails, rack, sections)
- Create: `db/scan_upload_migration.php`, `db/scan_upload_install.php`, `db/scan_upload.sql`
- Modify: `.gitignore` (re-include the three db files and `db/SCAN_UPLOAD_MODULE.md`)
- Modify: `tests/DatabaseTestCase.php` (load the stock schema, run the new migration, stock helpers)
- Modify: `tests/bootstrap.php` (require the migration)
- Modify: `Model/shop_access_class.php` (`hasFeatureRight`)
- Test: `tests/ScanUploadMigrationTest.php`, `tests/ShopAccessTest.php`

**Interfaces:**
- Produces: `ScanUploadMigration(PDO)->run(): string[]`; `ShopAccess->hasFeatureRight($user_id, $shop_id, $feature_id, array $rights): bool`; test helpers `createProduct($shop_id, $barcode, $name, array $overrides = []): int`, `createGrn($shop_id, $user_id, $stat = 0): int`, `addStock($product_id, $shop_id, $qty, $batch, $purchase, $selling, array $overrides = []): int` (returns INID), `createTransfer($from, $to, $user_id, $stat = 0): int`.

- [ ] **Step 1: Write the failing tests**

`tests/ScanUploadMigrationTest.php`:

```php
<?php
final class ScanUploadMigrationTest extends DatabaseTestCase
{
    protected $migrate = false; //runs the migration itself

    public function test_creates_the_scanbatches_table_once()
    {
        $first = (new ScanUploadMigration($this->pdo))->run();
        $second = (new ScanUploadMigration($this->pdo))->run();

        $this->assertSame(['[ok] scanbatches table'], $first);
        $this->assertSame(['[skip] scanbatches table - already in place'], $second);
        $this->assertSame(
            ['SBID', 'DocType', 'DocID', 'shop_SHID', 'user_USID', 'Fingerprint', 'ScanCount', 'LineCount', 'Summary', 'CreatedAt'],
            $this->pdo->query('SHOW COLUMNS FROM scanbatches')->fetchAll(PDO::FETCH_COLUMN)
        );
    }
}
```

Append to `tests/ShopAccessTest.php` (before `test_unavailable_reason_follows_company_state`):

```php
    // ---- hasFeatureRight -------------------------------------------------------------------

    public function test_feature_rights_come_from_the_role_held_in_that_shop()
    {
        $alice = $this->createUser('alice', 'x', $this->cashier);
        $this->assign($alice, $this->warehouse, $this->storeKeeper);
        $this->assign($alice, $this->showroom, $this->cashier);
        $this->grant($this->storeKeeper, 2, ['is_edit']);
        $this->grant($this->cashier, 2, ['is_view']);

        $this->assertTrue($this->access->hasFeatureRight($alice, $this->warehouse, 2, ['is_create', 'is_edit']));
        $this->assertFalse($this->access->hasFeatureRight($alice, $this->showroom, 2, ['is_create', 'is_edit']));
        $this->assertFalse($this->access->hasFeatureRight($alice, $this->warehouse, 4, ['is_edit']));
        $this->assertFalse($this->access->hasFeatureRight($alice, $this->warehouse, 2, ['is_edit = 1 OR 1']));
    }

    public function test_a_super_admin_has_every_right_and_a_revoked_user_none()
    {
        $admin = $this->admin();
        $bob = $this->createUser('bob', 'x', $this->storeKeeper);
        $this->assign($bob, $this->warehouse, $this->storeKeeper, false);
        $this->grant($this->storeKeeper, 2, ['is_edit']);

        $this->assertTrue($this->access->hasFeatureRight($admin, $this->showroom, 4, ['is_edit']));
        $this->assertFalse($this->access->hasFeatureRight($bob, $this->warehouse, 2, ['is_edit']));
    }
```

- [ ] **Step 2: Run them to see them fail**

Run: `C:/xampp/php/php.exe tools/phpunit.phar --filter "ScanUploadMigration|feature_right|every_right"`
Expected: errors - `Class "ScanUploadMigration" not found`, `Call to undefined method ShopAccess::hasFeatureRight()`.

- [ ] **Step 3: Implement**

`db/scan_upload_migration.php`:

```php
<?php
/**
 * Scanner upload - the schema change (db/SCAN_UPLOAD_MODULE.md).
 * -----------------------------------------------------------------------------
 * scanbatches: one row per scanner upload applied to a GRN or a transfer - who, where, when
 * and what. Its fingerprint lets the app refuse the same scan batch twice on one document
 * (a scanner whose memory was not cleared uploads it again).
 *
 * ADDITIVE ONLY and idempotent. Run through db/scan_upload_install.php (the tests call it
 * directly).
 */
class ScanUploadMigration
{
    const CREATE_TABLE = "CREATE TABLE `scanbatches` (
        `SBID` int(11) NOT NULL AUTO_INCREMENT,
        `DocType` varchar(8) NOT NULL COMMENT 'GRN, TRF_OUT or TRF_IN',
        `DocID` int(11) NOT NULL COMMENT 'grnheader.GHID or transferheader.THID',
        `shop_SHID` int(11) NOT NULL,
        `user_USID` int(11) NOT NULL,
        `Fingerprint` char(64) NOT NULL COMMENT 'SHA-256 of the applied lines',
        `ScanCount` int(11) NOT NULL COMMENT 'codes read',
        `LineCount` int(11) NOT NULL COMMENT 'products applied',
        `Summary` text NOT NULL COMMENT 'JSON: barcode => quantity applied',
        `CreatedAt` datetime NOT NULL,
        PRIMARY KEY (`SBID`),
        KEY `idx_scanbatches_doc` (`DocType`, `DocID`, `Fingerprint`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }//construct

    //apply every step; returns one report line per step
    public function run()
    {
        $exists = (int) $this->pdo->query("SELECT COUNT(*) FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scanbatches'")->fetchColumn() > 0;
        if ($exists) {
            return ['[skip] scanbatches table - already in place'];
        }//done before

        $this->pdo->exec(self::CREATE_TABLE);
        return ['[ok] scanbatches table'];
    }//run
}//ScanUploadMigration
```

`db/scan_upload_install.php`:

```php
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
```

`db/scan_upload.sql` (same table for a manual install):

```sql
-- Scanner upload (db/SCAN_UPLOAD_MODULE.md): the same change as db/scan_upload_install.php.
CREATE TABLE IF NOT EXISTS `scanbatches` (
  `SBID` int(11) NOT NULL AUTO_INCREMENT,
  `DocType` varchar(8) NOT NULL COMMENT 'GRN, TRF_OUT or TRF_IN',
  `DocID` int(11) NOT NULL COMMENT 'grnheader.GHID or transferheader.THID',
  `shop_SHID` int(11) NOT NULL,
  `user_USID` int(11) NOT NULL,
  `Fingerprint` char(64) NOT NULL COMMENT 'SHA-256 of the applied lines',
  `ScanCount` int(11) NOT NULL COMMENT 'codes read',
  `LineCount` int(11) NOT NULL COMMENT 'products applied',
  `Summary` text NOT NULL COMMENT 'JSON: barcode => quantity applied',
  `CreatedAt` datetime NOT NULL,
  PRIMARY KEY (`SBID`),
  KEY `idx_scanbatches_doc` (`DocType`, `DocID`, `Fingerprint`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

`.gitignore`, after `!db/SHOP_ACCESS_MODULE.md`:

```
!db/scan_upload_migration.php
!db/scan_upload_install.php
!db/scan_upload.sql
!db/SCAN_UPLOAD_MODULE.md
```

`Model/shop_access_class.php`, after `isSuperAdmin`:

```php
    //does the role this user holds in this shop grant one of these rights on a feature
    //(rights: the userroleaccess flags is_create, is_edit, is_view, is_delete, is_verify,
    //is_print)? A super admin has every right; a user who may not enter the shop has none
    public function hasFeatureRight($user_id, $shop_id, $feature_id, array $rights)
    {
        $access = $this->findAccess($user_id, $shop_id);
        if($access === null)
        {
            return false;
        }//may not enter the shop
        if((int)$access['UserType'] === 1)
        {
            return true;
        }//super admin

        $flags = array_values(array_intersect($rights, ['is_create', 'is_edit', 'is_view', 'is_delete', 'is_verify', 'is_print']));
        if(empty($flags))
        {
            return false;
        }//no known right asked for

        $stmt = $this->connect()->prepare("SELECT COUNT(*) FROM userroleaccess
            WHERE UserRolls_URID = ? AND SysFeatures_SFID = ? AND (" . implode(' = 1 OR ', $flags) . " = 1);");
        $stmt->execute([$access['UserRoles_URID'], (int)$feature_id]);
        return (int)$stmt->fetchColumn() > 0;
    }//has feature right
```

(`findAccess` already returns `UserType` and `UserRoles_URID`; check it does before relying on it.)

`tests/bootstrap.php`, after the shop access migration line:

```php
require_once __DIR__ . '/../db/scan_upload_migration.php';
```

`tests/DatabaseTestCase.php`:
- `resetSchema()` loads `legacy_schema.sql` then `stock_schema.sql` (loop over both files with the same comment-strip / split code).
- `setUp()` runs `(new ScanUploadMigration($this->pdo))->run();` after the shop access migration (inside `if ($this->migrate)`).
- Add the helpers:

```php
    protected function createProduct($shop_id, $barcode, $name, array $overrides = [])
    {
        return $this->insert('products', $overrides + [
            'Barcode' => $barcode,
            'ItemName' => $name,
            'ProdPurchasePrice' => 100,
            'ProdSellPrice' => 150,
            'ProductStat' => 1,
            'ItemType' => 'P',
            'user_USID' => 1,
            'Subcategories_SCID' => 1,
            'shop_SHID' => $shop_id,
            'PurchaseUnit' => 1,
            'UnitConversion' => 1,
            'SellingUnit' => 1,
            'prodFlatDiscount' => 0,
        ]);
    }//createProduct

    protected function createGrn($shop_id, $user_id, $stat = 0)
    {
        return $this->insert('grnheader', [
            'GRNHeaderNo' => 'GRN_TEST', 'EffectiveDate' => date('Y-m-d'), 'InvoiceNo' => 'INV', 'ItemCount' => 0,
            'TotalPurchasePrice' => 0, 'TotalSellPrice' => 0, 'GRNStat' => $stat, 'user_USID' => $user_id,
            'shop_SHID' => $shop_id, 'Suppliers_SPID' => 1, 'SuppPayment' => 0, 'SuppBalance' => 0,
            'excessAmount' => 0, 'refference' => '',
        ]);
    }//createGrn

    //a stock batch: an inventory row with its price history row; returns the inventory id
    protected function addStock($product_id, $shop_id, $qty, $batch, $purchase, $selling, array $overrides = [])
    {
        $inventory_id = $this->insert('inventory', [
            'CurrentQty' => $qty, 'BillQty' => 0, 'ReturnQty' => 0, 'TransferInQty' => 0, 'TransferOutQty' => 0,
            'products_PDID' => $product_id, 'shop_SHID' => $shop_id, 'RackID' => 1, 'BatchID' => $batch,
        ]);
        $this->insert('pricehistory', $overrides + [
            'ProductID' => $product_id, 'VariationID' => 0, 'EffectiveDate' => date('Y-m-d'),
            'PurchasePrice' => $purchase, 'SellingPrice' => $selling, 'labelPrice' => $selling,
            'MnfDate' => null, 'ExpDate' => null, 'BatchID' => $batch, 'Inventory_INID' => $inventory_id,
        ]);
        return $inventory_id;
    }//addStock

    protected function createTransfer($from_shop_id, $to_shop_id, $user_id, $stat = 0)
    {
        return $this->insert('transferheader', [
            'TransferNo' => 'TR_TEST', 'EffectiveDate' => date('Y-m-d'), 'TransferFrom' => $from_shop_id,
            'TransferTo' => $to_shop_id, 'TransferTotalCount' => 0, 'TransferTotalAmount' => 0,
            'TransferStat' => $stat, 'shop_SHID' => $from_shop_id, 'user_USID' => $user_id,
        ]);
    }//createTransfer
```

- [ ] **Step 4: Run the whole suite**

Run: `C:/xampp/php/php.exe tools/phpunit.phar`
Expected: `OK` (47 existing + 3 new tests).

- [ ] **Step 5: Install locally and check it is idempotent**

Run: `C:/xampp/php/php.exe db/scan_upload_install.php` twice.
Expected: `[ok] scanbatches table`, then `[skip] scanbatches table - already in place`.

- [ ] **Step 6: Commit and push**

```bash
git add .gitignore db/scan_upload_migration.php db/scan_upload_install.php db/scan_upload.sql tests/fixtures/stock_schema.sql tests/DatabaseTestCase.php tests/bootstrap.php Model/shop_access_class.php tests/ScanUploadMigrationTest.php tests/ShopAccessTest.php
git commit -m "feat(scan): scanbatches table, stock test schema and per-shop feature rights"
git push origin development
```

---

### Task 2: Reading the codes - `ScanParser`, `ScanRefused`, `ScanBatches`

**Files:**
- Create: `Model/scan_parser_class.php`, `Model/scan_refused_class.php`, `Model/scan_batch_class.php`
- Modify: `tests/bootstrap.php` (require them)
- Test: `tests/ScanParserTest.php`, `tests/ScanBatchesTest.php`

**Interfaces:**
- Produces: `ScanParser::parse(string $raw, array $knownBarcodes): array{items: array<string,int>, unknown: array<string,int>, scans: int, truncated: bool}`; `ScanRefused(int $status, string $message, ?array $preview = null, ?string $confirm = null)` with public `$status`, `$preview`, `$confirm`; `ScanBatches::GRN|TRANSFER_OUT|TRANSFER_IN`, `ScanBatches::fingerprint(array $lines): string`, `->findDuplicate($doc_type, $doc_id, $fingerprint): ?array{UserName, CreatedAt}`, `->record($doc_type, $doc_id, $shop_id, $user_id, array $lines, int $scan_count): int`.

- [ ] **Step 1: Write the failing tests**

`tests/ScanParserTest.php`:

```php
<?php
use PHPUnit\Framework\TestCase;

final class ScanParserTest extends TestCase
{
    private const KNOWN = ['COO00001', 'COO00002', 'LIN00001'];

    public function test_one_code_per_line_is_counted()
    {
        $r = ScanParser::parse("COO00001\nCOO00001\nCOO00002\n", self::KNOWN);
        $this->assertSame(['COO00001' => 2, 'COO00002' => 1], $r['items']);
        $this->assertSame([], $r['unknown']);
        $this->assertSame(3, $r['scans']);
        $this->assertFalse($r['truncated']);
    }

    public function test_crlf_tabs_spaces_commas_and_semicolons_separate_codes()
    {
        $r = ScanParser::parse("COO00001\r\nCOO00002\tCOO00001, COO00002;COO00002", self::KNOWN);
        $this->assertSame(['COO00001' => 2, 'COO00002' => 3], $r['items']);
    }

    public function test_codes_typed_with_no_separator_are_split_on_the_known_codes()
    {
        $r = ScanParser::parse('COO00001COO00001LIN00001', self::KNOWN);
        $this->assertSame(['COO00001' => 2, 'LIN00001' => 1], $r['items']);
        $this->assertSame(3, $r['scans']);
    }

    public function test_a_run_that_does_not_split_cleanly_is_unknown_never_guessed()
    {
        $this->assertSame(['COO00001XYZ' => 1], ScanParser::parse('COO00001XYZ', self::KNOWN)['unknown']);
        //1234 is 12+34 or 123+4: two ways, so it is not guessed
        $this->assertSame(['1234' => 1], ScanParser::parse('1234', ['12', '123', '34', '4'])['unknown']);
    }

    public function test_a_code_with_a_quantity()
    {
        $r = ScanParser::parse("COO00001,10\nCOO00002 3\nCOO00001\t2\nLIN00001,0", self::KNOWN);
        $this->assertSame(['COO00001' => 12, 'COO00002' => 3], $r['items']);
        $this->assertSame(15, $r['scans']);
    }

    public function test_a_second_code_that_is_numeric_is_a_code_not_a_quantity()
    {
        $r = ScanParser::parse('COO00001 12345', ['COO00001', '12345']);
        $this->assertSame(['COO00001' => 1, '12345' => 1], $r['items']);
    }

    public function test_matching_ignores_case_and_leading_zeros_and_returns_the_stored_code()
    {
        $this->assertSame(['COO00001' => 1], ScanParser::parse('coo00001', self::KNOWN)['items']);
        $this->assertSame(['0123456' => 1], ScanParser::parse('123456', ['0123456'])['items']);
        $this->assertSame(['123456' => 1], ScanParser::parse('00123456', ['123456'])['items']);
    }

    public function test_control_characters_are_removed_and_empty_input_reads_nothing()
    {
        $this->assertSame(['COO00001' => 1], ScanParser::parse("\x02COO00001\x03\n", self::KNOWN)['items']);
        $this->assertSame(['items' => [], 'unknown' => [], 'scans' => 0, 'truncated' => false], ScanParser::parse("  \n\n", self::KNOWN));
    }

    public function test_an_upload_is_capped_at_5000_codes()
    {
        $r = ScanParser::parse(str_repeat("COO00001\n", 5001), self::KNOWN);
        $this->assertSame(5000, $r['scans']);
        $this->assertTrue($r['truncated']);
    }
}
```

`tests/ScanBatchesTest.php`:

```php
<?php
final class ScanBatchesTest extends DatabaseTestCase
{
    public function test_the_fingerprint_ignores_order_and_case_but_not_counts()
    {
        $this->assertSame(ScanBatches::fingerprint(['A1' => 2, 'B2' => 1]), ScanBatches::fingerprint(['b2' => 1, 'a1' => 2]));
        $this->assertNotSame(ScanBatches::fingerprint(['A1' => 2]), ScanBatches::fingerprint(['A1' => 3]));
    }

    public function test_a_recorded_batch_is_found_again_only_on_the_same_document()
    {
        $user = $this->createUser('ramindu', 'x', $this->createRole('Store Keeper'));
        $batches = new ScanBatches();

        $this->assertGreaterThan(0, $batches->record(ScanBatches::GRN, 12, 3, $user, ['A1' => 2], 2));

        $fingerprint = ScanBatches::fingerprint(['A1' => 2]);
        $duplicate = $batches->findDuplicate(ScanBatches::GRN, 12, $fingerprint);
        $this->assertSame('ramindu', $duplicate['UserName']);
        $this->assertNull($batches->findDuplicate(ScanBatches::GRN, 13, $fingerprint));
        $this->assertNull($batches->findDuplicate(ScanBatches::TRANSFER_OUT, 12, $fingerprint));

        $row = $this->pdo->query('SELECT DocType, DocID, shop_SHID, user_USID, ScanCount, LineCount, Summary FROM scanbatches')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(['DocType' => 'GRN', 'DocID' => 12, 'shop_SHID' => 3, 'user_USID' => $user, 'ScanCount' => 2, 'LineCount' => 1, 'Summary' => '{"A1":2}'], $row);
    }
}
```

- [ ] **Step 2: Run them to see them fail**

Run: `C:/xampp/php/php.exe tools/phpunit.phar --filter "ScanParser|ScanBatches"`
Expected: errors - `Class "ScanParser" not found`, `Class "ScanBatches" not found`.

- [ ] **Step 3: Implement**

`Model/scan_parser_class.php`:

```php
<?php
//Reads what a barcode scanner typed into the upload box
//(docs/superpowers/specs/2026-09-22-scanner-upload-design.md, section 5).
//
//In inventory mode the scanner stores every scan and its cradle types them all at once, each
//followed by Enter (or Tab, or nothing, depending on the scanner's settings). parse() turns
//that text into a count per barcode:
//  - one code per line, or several separated by spaces, Tabs, commas or semicolons
//  - "CODE,10" / "CODE 10" / "CODE<Tab>10": the code with a quantity
//  - codes typed with no separator at all are split against the known barcodes, but only when
//    there is exactly one way to do it - anything else is reported as unknown, never guessed
//  - codes match case-insensitively, with or without leading zeros (as the POS scan does)
//It never touches the database: the caller passes the barcodes that may be matched.
class ScanParser
{
    const MAX_CHARS = 200000;
    const MAX_CODES = 5000;

    //['items' => [stored barcode => count], 'unknown' => [token => count], 'scans' => codes
    //counted, 'truncated' => the upload was longer than the limits]
    public static function parse($raw, array $knownBarcodes)
    {
        $index = [];
        foreach($knownBarcodes as $code)
        {
            $code = trim((string)$code);
            if($code !== '' && !isset($index[strtoupper($code)]))
            {
                $index[strtoupper($code)] = $code;
            }
        }//upper case => stored code
        $lengths = array_values(array_unique(array_map('strlen', array_keys($index))));

        $raw = (string)$raw;
        $result = ['items' => [], 'unknown' => [], 'scans' => 0, 'truncated' => strlen($raw) > self::MAX_CHARS];
        $raw = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', substr($raw, 0, self::MAX_CHARS));

        $read = 0;
        foreach(preg_split('/\r\n|\r|\n/', $raw) as $line)
        {
            $tokens = preg_split('/[\s,;]+/', trim($line), -1, PREG_SPLIT_NO_EMPTY);
            if(count($tokens) === 2 && preg_match('/^\d{1,5}$/', $tokens[1]) && self::lookup($tokens[1], $index) === null)
            {
                $entries = [[$tokens[0], (int)$tokens[1]]];
            }//code + quantity
            else
            {
                $entries = array_map(function($token) { return [$token, 1]; }, $tokens);
            }//one or more codes

            foreach($entries as [$token, $qty])
            {
                if($read >= self::MAX_CODES)
                {
                    $result['truncated'] = true;
                    break 2;
                }//limit reached
                $read++;
                if($qty > 0)
                {
                    self::count($result, $token, $qty, $index, $lengths);
                }
            }//each entry
        }//each line

        return $result;
    }//parse

    private static function count(array &$result, $token, $qty, array $index, array $lengths)
    {
        $match = self::lookup($token, $index);
        $codes = $match !== null ? [$match] : self::split($token, $index, $lengths);
        if($codes === null)
        {
            $result['unknown'][$token] = (isset($result['unknown'][$token]) ? $result['unknown'][$token] : 0) + $qty;
            $result['scans'] += $qty;
            return;
        }//not a code we know

        foreach($codes as $code)
        {
            $result['items'][$code] = (isset($result['items'][$code]) ? $result['items'][$code] : 0) + $qty;
            $result['scans'] += $qty;
        }
    }//count

    //the stored code a token stands for - as scanned, with a leading 0 added, or with its
    //leading zeros removed - or null
    private static function lookup($token, array $index)
    {
        $token = strtoupper($token);
        $stripped = ltrim($token, '0');
        foreach([$token, '0' . $token, $stripped === '' ? '0' : $stripped] as $candidate)
        {
            if(isset($index[$candidate]))
            {
                return $index[$candidate];
            }
        }
        return null;
    }//lookup

    //codes typed with no separator: the known codes that make up the token when there is
    //exactly one way to split all of it, else null
    private static function split($token, array $index, array $lengths)
    {
        $text = strtoupper($token);
        $n = strlen($text);
        if($n === 0 || empty($lengths))
        {
            return null;
        }

        //ways[i]: how many ways text[i..] splits into known codes (counted up to 2)
        $ways = array_fill(0, $n + 1, 0);
        $ways[$n] = 1;
        $next = [];
        for($i = $n - 1; $i >= 0; $i--)
        {
            foreach($lengths as $length)
            {
                if($i + $length <= $n && $ways[$i + $length] > 0 && isset($index[substr($text, $i, $length)]))
                {
                    $ways[$i] = min(2, $ways[$i] + $ways[$i + $length]);
                    $next[$i] = $length;
                }
            }
        }
        if($ways[0] !== 1)
        {
            return null;
        }//no way, or more than one

        $codes = [];
        for($i = 0; $i < $n; $i += $next[$i])
        {
            $codes[] = $index[substr($text, $i, $next[$i])];
        }
        return $codes;
    }//split
}//ScanParser
```

`Model/scan_refused_class.php`:

```php
<?php
//A scanner upload that cannot go ahead: the HTTP status to answer with, the message for the
//user, the preview to show again (when there is one) and what the user may confirm to go
//ahead anyway ('duplicate' or 'short'; null when nothing can be confirmed).
class ScanRefused extends RuntimeException
{
    public $status;
    public $preview;
    public $confirm;

    public function __construct($status, $message, array $preview = null, $confirm = null)
    {
        parent::__construct($message);
        $this->status = $status;
        $this->preview = $preview;
        $this->confirm = $confirm;
    }//construct
}//ScanRefused
```

`Model/scan_batch_class.php`:

```php
<?php
//The scanner uploads applied to GRNs and transfers (table scanbatches): who, where, when and
//what. The fingerprint of the applied lines finds the same batch uploaded twice to one
//document - a scanner whose memory was not cleared sends it again.
class ScanBatches extends Dbh
{
    const GRN = 'GRN';
    const TRANSFER_OUT = 'TRF_OUT';
    const TRANSFER_IN = 'TRF_IN';

    //SHA-256 of the lines (barcode => quantity), whatever their order or letter case
    public static function fingerprint(array $lines)
    {
        $rows = [];
        foreach($lines as $code => $qty)
        {
            $rows[] = strtoupper((string)$code) . "\t" . (0 + $qty);
        }
        sort($rows, SORT_STRING);
        return hash('sha256', implode("\n", $rows));
    }//fingerprint

    //the latest upload of this batch to this document (UserName, CreatedAt), or null
    public function findDuplicate($doc_type, $doc_id, $fingerprint)
    {
        $stmt = $this->connect()->prepare("SELECT user.UserName, scanbatches.CreatedAt FROM scanbatches
            LEFT JOIN user ON user.USID = scanbatches.user_USID
            WHERE scanbatches.DocType = ? AND scanbatches.DocID = ? AND scanbatches.Fingerprint = ?
            ORDER BY scanbatches.SBID DESC LIMIT 1;");
        $stmt->execute([$doc_type, (int)$doc_id, $fingerprint]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }//find duplicate

    //record an applied upload; returns its id
    public function record($doc_type, $doc_id, $shop_id, $user_id, array $lines, $scan_count)
    {
        $stmt = $this->connect()->prepare("INSERT INTO scanbatches
            (DocType, DocID, shop_SHID, user_USID, Fingerprint, ScanCount, LineCount, Summary, CreatedAt)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);");
        $stmt->execute([$doc_type, (int)$doc_id, (int)$shop_id, (int)$user_id, self::fingerprint($lines),
            (int)$scan_count, count($lines), json_encode($lines), date('Y-m-d H:i:s')]);
        return (int)$this->connect()->lastInsertId();
    }//record
}//ScanBatches
```

`tests/bootstrap.php`: require the three model files (before `DatabaseTestCase.php`).

- [ ] **Step 4: Run the suite**

Run: `C:/xampp/php/php.exe tools/phpunit.phar`
Expected: `OK` (11 more tests).

- [ ] **Step 5: Commit and push**

```bash
git add Model/scan_parser_class.php Model/scan_refused_class.php Model/scan_batch_class.php tests/ScanParserTest.php tests/ScanBatchesTest.php tests/bootstrap.php
git commit -m "feat(scan): read scanner uploads - separators, run-together codes, quantities, batches"
git push origin development
```

---

### Task 3: GRN preview and apply - `ScanDocument`, `GrnScan`

**Files:**
- Create: `Model/scan_document_class.php`, `Model/scan_grn_class.php`, `Includes/scan_upload.php`
- Modify: `tests/bootstrap.php` (require `Includes/scan_upload.php` instead of the single model files)
- Test: `tests/GrnScanTest.php`

**Interfaces:**
- Consumes: Task 1 `hasFeatureRight`, test helpers; Task 2 parser, batches, `ScanRefused`.
- Produces:
  - `ScanDocument` (abstract, extends `Dbh`): `errorLine($key, $qty, $message, $product = null)`, `finish(array $preview, array $decisions, ?string $doc_type)`, `assertCanApply(array $preview, array $decisions)`, `transaction(callable $work)`, `productsByBarcode(array $shop_ids)`, `knownBarcodes(array $byCode)`, `pickProduct(array $candidates, $shop_id)`, `static qty($number)`, `static money($value)`, `static validDate($value)`.
  - `GrnScan::FEATURE = 2`, `GrnScan::RIGHTS = ['is_create', 'is_edit']`, `->preview($grn_id, $shop_id, $user_id, $raw, array $decisions = []): array`, `->apply($grn_id, $shop_id, $user_id, $raw, array $decisions): array{message, result: {doc_id, lines, qty}}`.
  - Preview shape: `{context, doc_id, scans, truncated, lines[], options, rack_id, blocking, applied{barcode: qty}, duplicate: null|{user, at}, can_apply}`; each line `{key, barcode, product_id, name, qty, apply_qty, status: ok|warn|error, message, left_out, can_leave_out, editable, ...}`; GRN lines add `existing_id, existing_qty, prices_locked, purchase_price, selling_price, label_price, mnf_date, exp_date`.
  - Decisions: `leave_out[]`, `prices[product_id][purchase|selling|label]`, `dates[product_id][mnf|exp]`, `rack_id`, `confirm_duplicate`, `confirm_short`.

- [ ] **Step 1: Write the failing tests** - `tests/GrnScanTest.php`:

```php
<?php
final class GrnScanTest extends DatabaseTestCase
{
    private GrnScan $scan;
    private int $shop;
    private int $keeper;
    private int $alice;
    private int $grn;
    private int $bed;
    private int $sheet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scan = new GrnScan();
        $company = $this->createCompany();
        $this->shop = $this->createShop($company, ['ShopName' => 'Warehouse']);
        $this->keeper = $this->createRole('Store Keeper');
        $this->grant($this->keeper, 2, ['is_edit']);
        $this->alice = $this->createUser('alice', 'x', $this->keeper);
        $this->assign($this->alice, $this->shop, $this->keeper);
        $this->bed = $this->createProduct($this->shop, 'COO00001', 'Bed', ['ProdPurchasePrice' => 1000, 'ProdSellPrice' => 1500]);
        $this->sheet = $this->createProduct($this->shop, 'LIN00001', 'Bedsheet', ['ProdPurchasePrice' => 200, 'ProdSellPrice' => 350]);
        $this->createProduct($this->shop, 'SRV00001', 'Delivery', ['ItemType' => 'S']);
        $this->createProduct($this->shop, 'OLD00001', 'Old bed', ['ProductStat' => 0]);
        $this->grn = $this->createGrn($this->shop, $this->alice);
    }

    private function lines(array $preview)
    {
        $out = [];
        foreach ($preview['lines'] as $line) {
            $out[$line['key']] = $line;
        }
        return $out;
    }

    private function details()
    {
        return $this->pdo->query('SELECT products_PDID, InitQty, CurrentQty, UnitPurchasePrice, UnitSellPrice, UnitLabelPrice,
            TotalPurchasePrice, TotalSellPrice, MnfDate, ExpDate, GRNStat, VariationID, Rack_RKID FROM grndetails ORDER BY GDID')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function test_preview_counts_each_product_and_prefills_its_prices()
    {
        $p = $this->scan->preview($this->grn, $this->shop, $this->alice, "COO00001\nCOO00001\nLIN00001\n");
        $lines = $this->lines($p);

        $this->assertSame(3, $p['scans']);
        $this->assertSame(2, $lines['COO00001']['qty']);
        $this->assertSame('ok', $lines['COO00001']['status']);
        $this->assertSame('1000.00', $lines['COO00001']['purchase_price']);
        $this->assertSame('1500.00', $lines['COO00001']['selling_price']);
        $this->assertTrue($lines['COO00001']['editable']);
        $this->assertSame(1, $lines['LIN00001']['qty']);
        $this->assertSame(['COO00001' => 2, 'LIN00001' => 1], $p['applied']);
        $this->assertSame(0, $p['blocking']);
        $this->assertTrue($p['can_apply']);
        $this->assertNull($p['duplicate']);
    }

    public function test_prices_come_from_the_latest_batch_when_there_is_one()
    {
        $this->addStock($this->bed, $this->shop, 3, 'B1', 900, 1400);
        $lines = $this->lines($this->scan->preview($this->grn, $this->shop, $this->alice, 'COO00001'));
        $this->assertSame('900.00', $lines['COO00001']['purchase_price']);
        $this->assertSame('1400.00', $lines['COO00001']['selling_price']);
    }

    public function test_problem_codes_block_until_they_are_left_out()
    {
        $raw = "SRV00001\nOLD00001\nNOPE\nCOO00001";
        $lines = $this->lines($p = $this->scan->preview($this->grn, $this->shop, $this->alice, $raw));

        $this->assertSame('Service item - no stock', $lines['SRV00001']['message']);
        $this->assertSame('Inactive product', $lines['OLD00001']['message']);
        $this->assertSame('Not a product in this shop', $lines['NOPE']['message']);
        $this->assertSame(3, $p['blocking']);
        $this->assertFalse($p['can_apply']);

        $p = $this->scan->preview($this->grn, $this->shop, $this->alice, $raw, ['leave_out' => ['SRV00001', 'OLD00001', 'NOPE']]);
        $this->assertSame(0, $p['blocking']);
        $this->assertTrue($p['can_apply']);
        $this->assertTrue($this->lines($p)['NOPE']['left_out']);
    }

    public function test_run_together_codes_are_split()
    {
        $lines = $this->lines($this->scan->preview($this->grn, $this->shop, $this->alice, 'COO00001COO00001LIN00001'));
        $this->assertSame(2, $lines['COO00001']['qty']);
        $this->assertSame(1, $lines['LIN00001']['qty']);
    }

    public function test_apply_adds_new_lines_like_manual_entry()
    {
        $result = $this->scan->apply($this->grn, $this->shop, $this->alice, "COO00001\nCOO00001", []);

        $this->assertSame(['doc_id' => $this->grn, 'lines' => 1, 'qty' => 2], $result['result']);
        $this->assertSame([[
            'products_PDID' => $this->bed, 'InitQty' => '2.000', 'CurrentQty' => '2.000', 'UnitPurchasePrice' => '1000.00',
            'UnitSellPrice' => '1500.00', 'UnitLabelPrice' => '0.00', 'TotalPurchasePrice' => '2000.00', 'TotalSellPrice' => '3000.00',
            'MnfDate' => date('Y-m-d'), 'ExpDate' => date('Y-m-d'), 'GRNStat' => 0, 'VariationID' => 1, 'Rack_RKID' => 1,
        ]], $this->details());
        $this->assertSame(1, (int)$this->pdo->query('SELECT COUNT(*) FROM scanbatches')->fetchColumn());
    }

    public function test_apply_adds_to_the_existing_line_and_keeps_its_prices()
    {
        $this->insert('grndetails', ['InitQty' => 5, 'CurrentQty' => 5, 'UnitPurchasePrice' => 800, 'UnitLabelPrice' => 0,
            'UnitSellPrice' => 1200, 'TotalPurchasePrice' => 4000, 'TotalSellPrice' => 6000, 'MnfDate' => date('Y-m-d'),
            'ExpDate' => date('Y-m-d'), 'GRNStat' => 0, 'VariationID' => 1, 'products_PDID' => $this->bed,
            'GRNHeader_GHID' => $this->grn, 'Rack_RKID' => 1]);

        $line = $this->lines($this->scan->preview($this->grn, $this->shop, $this->alice, "COO00001\nCOO00001\nCOO00001"))['COO00001'];
        $this->assertSame('Adds to the existing line: 5 → 8', $line['message']);
        $this->assertTrue($line['prices_locked']);
        $this->assertSame('800.00', $line['purchase_price']);

        $this->scan->apply($this->grn, $this->shop, $this->alice, "COO00001\nCOO00001\nCOO00001", ['prices' => [$this->bed => ['purchase' => '1', 'selling' => '1']]]);
        $rows = $this->details();
        $this->assertCount(1, $rows);
        $this->assertSame(['8.000', '8.000', '800.00', '6400.00', '9600.00'],
            [$rows[0]['InitQty'], $rows[0]['CurrentQty'], $rows[0]['UnitPurchasePrice'], $rows[0]['TotalPurchasePrice'], $rows[0]['TotalSellPrice']]);
    }

    public function test_edited_prices_are_used_and_invalid_ones_block()
    {
        $bad = $this->lines($this->scan->preview($this->grn, $this->shop, $this->alice, 'COO00001', ['prices' => [$this->bed => ['purchase' => '950', 'selling' => '-1']]]))['COO00001'];
        $this->assertSame('error', $bad['status']);
        $this->assertSame('Enter a valid selling price', $bad['message']);

        $this->scan->apply($this->grn, $this->shop, $this->alice, 'COO00001', ['prices' => [$this->bed => ['purchase' => '950', 'selling' => '1450.5']]]);
        $this->assertSame(['950.00', '1450.50'], [$this->details()[0]['UnitPurchasePrice'], $this->details()[0]['UnitSellPrice']]);
    }

    public function test_apply_refuses_while_a_line_blocks_and_writes_nothing()
    {
        try {
            $this->scan->apply($this->grn, $this->shop, $this->alice, "COO00001\nNOPE", []);
            $this->fail('expected a refusal');
        } catch (ScanRefused $e) {
            $this->assertSame(422, $e->status);
            $this->assertSame(1, $e->preview['blocking']);
        }
        $this->assertSame([], $this->details());
    }

    public function test_the_same_batch_twice_needs_a_confirmation()
    {
        $this->scan->apply($this->grn, $this->shop, $this->alice, "COO00001\nCOO00001", []);
        try {
            $this->scan->apply($this->grn, $this->shop, $this->alice, "COO00001\nCOO00001", []);
            $this->fail('expected a refusal');
        } catch (ScanRefused $e) {
            $this->assertSame(409, $e->status);
            $this->assertSame('duplicate', $e->confirm);
            $this->assertStringContainsString('already added by alice', $e->getMessage());
        }
        $this->scan->apply($this->grn, $this->shop, $this->alice, "COO00001\nCOO00001", ['confirm_duplicate' => true]);
        $this->assertSame('4.000', $this->details()[0]['InitQty']);
    }

    public function test_closed_grns_other_shops_grns_and_users_without_rights_are_refused()
    {
        $cases = [
            [$this->createGrn($this->shop, $this->alice, 2), $this->shop, $this->alice, 409],
            [$this->createGrn($this->createShop($this->createCompany()), $this->alice), $this->shop, $this->alice, 404],
        ];
        $viewer = $this->createRole('Viewer');
        $this->grant($viewer, 2, ['is_view']);
        $bob = $this->createUser('bob', 'x', $viewer);
        $this->assign($bob, $this->shop, $viewer);
        $cases[] = [$this->grn, $this->shop, $bob, 403];

        foreach ($cases as [$grn, $shop, $user, $status]) {
            try {
                $this->scan->preview($grn, $shop, $user, 'COO00001');
                $this->fail('expected ' . $status);
            } catch (ScanRefused $e) {
                $this->assertSame($status, $e->status);
            }
        }
    }

    public function test_a_shop_that_tracks_expiry_labels_and_racks_gets_those_fields()
    {
        $this->pdo->exec("UPDATE shop SET is_expire = 1, is_labelprice = 1, is_racks = 1 WHERE SHID = {$this->shop}");
        $section = $this->insert('sections', ['SectionNo' => 'S1', 'SectionName' => 'Main', 'shop_SHID' => $this->shop]);
        $rack = $this->insert('rack', ['RackNo' => 'R1', 'RackName' => 'Top', 'Sections_SEID' => $section]);

        $p = $this->scan->preview($this->grn, $this->shop, $this->alice, 'COO00001');
        $this->assertSame(['label_price' => true, 'expiry' => true, 'racks' => true, 'racks_list' => [['id' => $rack, 'name' => 'Main - Top']]], $p['options']);
        $this->assertSame($rack, $p['rack_id']);
        $this->assertSame('Enter the Mnf and Exp dates', $this->lines($p)['COO00001']['message']);
        $this->assertSame('1500.00', $this->lines($p)['COO00001']['label_price']);

        $mnf = date('Y-m-d', strtotime('-10 days'));
        $exp = date('Y-m-d', strtotime('+1 year'));
        $this->scan->apply($this->grn, $this->shop, $this->alice, 'COO00001', [
            'dates' => [$this->bed => ['mnf' => $mnf, 'exp' => $exp]],
            'prices' => [$this->bed => ['purchase' => '1000', 'selling' => '1500', 'label' => '1600']],
            'rack_id' => $rack,
        ]);
        $row = $this->details()[0];
        $this->assertSame([$mnf, $exp, '1600.00', $rack], [$row['MnfDate'], $row['ExpDate'], $row['UnitLabelPrice'], $row['Rack_RKID']]);
    }

    public function test_products_with_variations_stay_on_manual_entry_in_shops_that_use_them()
    {
        $this->pdo->exec("UPDATE shop SET is_variation = 1 WHERE SHID = {$this->shop}");
        $this->insert('variations', ['VariationName' => 'King', 'products_PDID' => $this->bed]);
        $lines = $this->lines($this->scan->preview($this->grn, $this->shop, $this->alice, "COO00001\nLIN00001"));
        $this->assertSame('Has variations - use Add Products', $lines['COO00001']['message']);
        $this->assertSame('ok', $lines['LIN00001']['status']);
    }

    public function test_multi_category_companies_prefer_the_shops_own_product()
    {
        $this->pdo->exec('UPDATE company SET is_multicategory = 1');
        $company = (int)$this->pdo->query("SELECT Company_CMID FROM shop WHERE SHID = {$this->shop}")->fetchColumn();
        $other = $this->createShop($company, ['ShopName' => 'Showroom']);
        $this->createProduct($other, 'COO00001', 'Bed (showroom copy)');
        $pillow = $this->createProduct($other, 'PIL00001', 'Pillow');

        $lines = $this->lines($this->scan->preview($this->grn, $this->shop, $this->alice, "COO00001\nPIL00001"));
        $this->assertSame($this->bed, $lines['COO00001']['product_id']);
        $this->assertSame($pillow, $lines['PIL00001']['product_id']);
    }
}
```

- [ ] **Step 2: Run it to see it fail**

Run: `C:/xampp/php/php.exe tools/phpunit.phar --filter GrnScanTest`
Expected: errors - `Class "GrnScan" not found`.

- [ ] **Step 3: Implement**

`Model/scan_document_class.php`:

```php
<?php
//What every scanner upload shares (docs/superpowers/specs/2026-09-22-scanner-upload-design.md):
//finding products by barcode, the preview's bookkeeping (left-out lines, what blocks, the
//duplicate batch guard) and the all-or-nothing transaction. GrnScan and TransferScan build
//on it.
abstract class ScanDocument extends Dbh
{
    protected $access;
    protected $batches;

    public function __construct()
    {
        $this->access = new ShopAccess();
        $this->batches = new ScanBatches();
    }//construct

    //a preview line for a code that cannot be used as it is
    protected function errorLine($key, $qty, $message, array $product = null)
    {
        return [
            'key' => (string)$key,
            'barcode' => $product === null ? null : trim($product['Barcode']),
            'product_id' => $product === null ? null : (int)$product['PDID'],
            'name' => $product === null ? '' : $product['ItemName'],
            'qty' => $qty,
            'apply_qty' => 0,
            'status' => 'error',
            'message' => $message,
            'left_out' => false,
            'can_leave_out' => true,
            'editable' => false,
        ];
    }//error line

    //marks the left-out lines, counts what blocks, totals what apply() would write and looks
    //for the same batch already applied to this document ($doc_type null: no such guard)
    protected function finish(array $preview, array $decisions, $doc_type)
    {
        $leaveOut = [];
        foreach((isset($decisions['leave_out']) && is_array($decisions['leave_out'])) ? $decisions['leave_out'] : [] as $key)
        {
            $leaveOut[(string)$key] = true;
        }

        $blocking = 0;
        $applied = [];
        foreach($preview['lines'] as &$line)
        {
            $line['left_out'] = $line['can_leave_out'] && isset($leaveOut[$line['key']]);
            if($line['left_out'])
            {
                continue;
            }
            if($line['status'] === 'error')
            {
                $blocking++;
                continue;
            }
            if($line['barcode'] !== null && $line['apply_qty'] > 0)
            {
                $applied[$line['barcode']] = $line['apply_qty'];
            }
        }
        unset($line);

        $preview['blocking'] = $blocking;
        $preview['applied'] = $applied;
        $preview['duplicate'] = null;
        if($doc_type !== null && !empty($applied))
        {
            $earlier = $this->batches->findDuplicate($doc_type, $preview['doc_id'], ScanBatches::fingerprint($applied));
            if($earlier !== null)
            {
                $preview['duplicate'] = ['user' => (string)$earlier['UserName'], 'at' => $earlier['CreatedAt']];
            }
        }//same batch before?
        $preview['can_apply'] = $blocking === 0 && !empty($applied);
        return $preview;
    }//finish

    //apply() goes ahead only with nothing blocking and a duplicate batch confirmed
    protected function assertCanApply(array $preview, array $decisions)
    {
        if($preview['blocking'] > 0)
        {
            throw new ScanRefused(422, 'Please fix or leave out the lines marked in red.', $preview);
        }
        if(empty($preview['applied']))
        {
            throw new ScanRefused(422, 'There is nothing to add.', $preview);
        }
        if($preview['duplicate'] !== null && empty($decisions['confirm_duplicate']))
        {
            throw new ScanRefused(409, 'This scan batch was already added by ' . $preview['duplicate']['user'] . ' on '
                . $preview['duplicate']['at'] . '. Add it again?', $preview, 'duplicate');
        }
    }//assert can apply

    //runs $work in one database transaction: everything is written, or nothing
    protected function transaction(callable $work)
    {
        $pdo = $this->connect();
        $pdo->beginTransaction();
        try
        {
            $result = $work();
            $pdo->commit();
            return $result;
        }
        catch(Throwable $e)
        {
            if($pdo->inTransaction())
            {
                $pdo->rollBack();
            }
            throw $e;
        }
    }//transaction

    //the products of these shops by upper-case barcode (a code two products share lists both)
    protected function productsByBarcode(array $shop_ids)
    {
        $shop_ids = array_values(array_map('intval', $shop_ids));
        if(empty($shop_ids))
        {
            return [];
        }
        $stmt = $this->connect()->prepare("SELECT PDID, Barcode, ItemName, ItemType, ProductStat, shop_SHID, ProdPurchasePrice, ProdSellPrice
            FROM products WHERE shop_SHID IN (" . implode(',', array_fill(0, count($shop_ids), '?')) . ")
            AND Barcode IS NOT NULL AND TRIM(Barcode) <> '' ORDER BY PDID;");
        $stmt->execute($shop_ids);
        $byCode = [];
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row)
        {
            $byCode[strtoupper(trim($row['Barcode']))][] = $row;
        }
        return $byCode;
    }//products by barcode

    protected static function knownBarcodes(array $byCode)
    {
        $codes = [];
        foreach($byCode as $rows)
        {
            $codes[] = trim($rows[0]['Barcode']);
        }
        return $codes;
    }//known barcodes

    //[product, null] or [null, reason]: the shop's own product wins over another shop's
    protected function pickProduct(array $candidates, $shop_id)
    {
        $own = array_values(array_filter($candidates, function($row) use ($shop_id) {
            return (int)$row['shop_SHID'] === (int)$shop_id;
        }));
        $pool = count($own) > 0 ? $own : $candidates;
        if(count($pool) === 1)
        {
            return [$pool[0], null];
        }
        return [null, count($pool) === 0 ? 'Not a product in this shop' : 'Two products share this barcode'];
    }//pick product

    //a quantity for people: 5, 2.5 - never 5.000
    public static function qty($number)
    {
        return rtrim(rtrim(number_format((float)$number, 3, '.', ''), '0'), '.');
    }//qty

    //a price as the database stores it ("1450.50"), or null when it is not a number >= 0
    public static function money($value)
    {
        if(is_int($value) || is_float($value))
        {
            $value = (string)$value;
        }
        if(!is_string($value) || !preg_match('/^\d+(\.\d+)?$/', trim($value)))
        {
            return null;
        }
        return number_format(round((float)trim($value), 2), 2, '.', '');
    }//money

    //a real Y-m-d date, or null
    public static function validDate($value)
    {
        if(!is_string($value))
        {
            return null;
        }
        $date = DateTime::createFromFormat('!Y-m-d', trim($value));
        return ($date && $date->format('Y-m-d') === trim($value)) ? trim($value) : null;
    }//valid date
}//ScanDocument
```

`Model/scan_grn_class.php`:

```php
<?php
//Scanner upload for a GRN (docs/superpowers/specs/2026-09-22-scanner-upload-design.md, §6).
//preview() says what an upload would do; apply() does it in one transaction. The lines it
//writes are the ones manual entry (AJAX/GRN/addGRNDetails.php) would write.
class GrnScan extends ScanDocument
{
    const FEATURE = 2;                          //Goods Received
    const RIGHTS = ['is_create', 'is_edit'];

    public function preview($grn_id, $shop_id, $user_id, $raw, array $decisions = [])
    {
        return $this->build($this->header($grn_id, $shop_id, $user_id, false), $raw, $decisions);
    }//preview

    public function apply($grn_id, $shop_id, $user_id, $raw, array $decisions)
    {
        return $this->transaction(function() use ($grn_id, $shop_id, $user_id, $raw, $decisions) {
            $header = $this->header($grn_id, $shop_id, $user_id, true);
            $preview = $this->build($header, $raw, $decisions);
            $this->assertCanApply($preview, $decisions);

            $pdo = $this->connect();
            $lines = 0;
            $qty = 0;
            foreach($preview['lines'] as $line)
            {
                if($line['left_out'] || $line['status'] === 'error' || $line['apply_qty'] <= 0)
                {
                    continue;
                }
                if($line['existing_id'] !== null)
                {
                    $stmt = $pdo->prepare("SELECT InitQty, UnitPurchasePrice, UnitSellPrice FROM grndetails WHERE GDID = ? FOR UPDATE;");
                    $stmt->execute([$line['existing_id']]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $newQty = (float)$row['InitQty'] + $line['apply_qty'];
                    $pdo->prepare("UPDATE grndetails SET InitQty = ?, CurrentQty = ?, TotalPurchasePrice = ?, TotalSellPrice = ? WHERE GDID = ?;")
                        ->execute([$newQty, $newQty, round($newQty * (float)$row['UnitPurchasePrice'], 2),
                            round($newQty * (float)$row['UnitSellPrice'], 2), $line['existing_id']]);
                }//adds to the line already there
                else
                {
                    $pdo->prepare("INSERT INTO grndetails (InitQty, CurrentQty, UnitPurchasePrice, UnitLabelPrice, UnitSellPrice,
                        TotalPurchasePrice, TotalSellPrice, MnfDate, ExpDate, GRNStat, VariationID, products_PDID, GRNHeader_GHID, Rack_RKID)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?);")
                        ->execute([$line['apply_qty'], $line['apply_qty'], $line['purchase_price'], $line['label_price'],
                            $line['selling_price'], round($line['apply_qty'] * (float)$line['purchase_price'], 2),
                            round($line['apply_qty'] * (float)$line['selling_price'], 2), $line['mnf_date'], $line['exp_date'],
                            $preview['variation_id'], $line['product_id'], $header['GHID'], $preview['rack_id']]);
                }//new line
                $lines++;
                $qty += $line['apply_qty'];
            }//each line

            $this->batches->record(ScanBatches::GRN, $header['GHID'], $shop_id, $user_id, $preview['applied'], $preview['scans']);
            return [
                'message' => 'Added ' . $lines . ' product(s), ' . self::qty($qty) . ' item(s) to ' . $header['GRNHeaderNo'] . '.',
                'result' => ['doc_id' => (int)$header['GHID'], 'lines' => $lines, 'qty' => $qty],
            ];
        });
    }//apply

    //the GRN, when this user may add to it now (locked for update when $lock)
    private function header($grn_id, $shop_id, $user_id, $lock)
    {
        $stmt = $this->connect()->prepare("SELECT GHID, GRNHeaderNo, GRNStat, shop_SHID FROM grnheader WHERE GHID = ?" . ($lock ? " FOR UPDATE" : "") . ";");
        $stmt->execute([(int)$grn_id]);
        $header = $stmt->fetch(PDO::FETCH_ASSOC);
        if($header === false || (int)$header['shop_SHID'] !== (int)$shop_id)
        {
            throw new ScanRefused(404, 'This GRN is not in this shop.');
        }
        if(!$this->access->hasFeatureRight($user_id, $shop_id, self::FEATURE, self::RIGHTS))
        {
            throw new ScanRefused(403, 'You do not have the right to change GRNs in this shop.');
        }
        if(!in_array((int)$header['GRNStat'], [0, 1], true))
        {
            throw new ScanRefused(409, 'This GRN is already verified or cancelled.');
        }
        return $header;
    }//header

    private function build(array $header, $raw, array $decisions)
    {
        $pdo = $this->connect();
        $stmt = $pdo->prepare("SELECT shop.SHID, shop.is_variation, shop.is_labelprice, shop.is_expire, shop.is_racks,
            shop.Company_CMID, company.is_multicategory FROM shop INNER JOIN company ON company.CMID = shop.Company_CMID WHERE shop.SHID = ?;");
        $stmt->execute([$header['shop_SHID']]);
        $shop = $stmt->fetch(PDO::FETCH_ASSOC);

        $shop_ids = [(int)$shop['SHID']];
        if((int)$shop['is_multicategory'] === 1)
        {
            $stmt = $pdo->prepare("SELECT SHID FROM shop WHERE Company_CMID = ?;");
            $stmt->execute([$shop['Company_CMID']]);
            $shop_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }//the company's products, as the GRN product search offers
        $byCode = $this->productsByBarcode($shop_ids);
        $parsed = ScanParser::parse($raw, self::knownBarcodes($byCode));

        $options = [
            'label_price' => (int)$shop['is_labelprice'] === 1,
            'expiry' => (int)$shop['is_expire'] === 1,
            'racks' => (int)$shop['is_racks'] === 1,
        ];
        $rack_id = 1;
        if($options['racks'])
        {
            $options['racks_list'] = $this->racks($shop['SHID']);
            $ids = array_column($options['racks_list'], 'id');
            $wanted = isset($decisions['rack_id']) ? (int)$decisions['rack_id'] : 0;
            $rack_id = in_array($wanted, $ids, true) ? $wanted : (empty($ids) ? 1 : $ids[0]);
        }//one rack for the upload

        $withVariations = [];
        if((int)$shop['is_variation'] === 1)
        {
            $withVariations = array_flip(array_map('intval', $pdo->query("SELECT DISTINCT products_PDID FROM variations;")->fetchAll(PDO::FETCH_COLUMN)));
        }//products that need a variation picked

        $lines = [];
        foreach($parsed['items'] as $barcode => $qty)
        {
            [$product, $reason] = $this->pickProduct($byCode[strtoupper($barcode)], $shop['SHID']);
            if($product === null)
            {
                $lines[] = $this->errorLine($barcode, $qty, $reason);
            }
            elseif($product['ItemType'] !== 'P')
            {
                $lines[] = $this->errorLine($barcode, $qty, 'Service item - no stock', $product);
            }
            elseif((int)$product['ProductStat'] !== 1)
            {
                $lines[] = $this->errorLine($barcode, $qty, 'Inactive product', $product);
            }
            elseif(isset($withVariations[(int)$product['PDID']]))
            {
                $lines[] = $this->errorLine($barcode, $qty, 'Has variations - use Add Products', $product);
            }
            else
            {
                $lines[] = $this->line($header, $shop, $options, $product, $barcode, $qty, $decisions);
            }
        }//each code
        foreach($parsed['unknown'] as $token => $qty)
        {
            $lines[] = $this->errorLine($token, $qty, 'Not a product in this shop');
        }

        return $this->finish([
            'context' => 'grn',
            'doc_id' => (int)$header['GHID'],
            'scans' => $parsed['scans'],
            'truncated' => $parsed['truncated'],
            'lines' => $lines,
            'options' => $options,
            'rack_id' => $rack_id,
            'variation_id' => (int)$shop['is_variation'] === 1 ? 0 : 1,   //manual entry's "no variation"
        ], $decisions, ScanBatches::GRN);
    }//build

    private function line(array $header, array $shop, array $options, array $product, $barcode, $qty, array $decisions)
    {
        $pdo = $this->connect();
        $line = [
            'key' => $barcode, 'barcode' => $barcode, 'product_id' => (int)$product['PDID'], 'name' => $product['ItemName'],
            'qty' => $qty, 'apply_qty' => $qty, 'status' => 'ok', 'message' => 'New line', 'left_out' => false,
            'can_leave_out' => true, 'editable' => false, 'existing_id' => null, 'existing_qty' => null, 'prices_locked' => false,
        ];

        $stmt = $pdo->prepare("SELECT GDID, InitQty, UnitPurchasePrice, UnitSellPrice, UnitLabelPrice, MnfDate, ExpDate FROM grndetails
            WHERE GRNHeader_GHID = ? AND products_PDID = ? ORDER BY GDID DESC LIMIT 1;");
        $stmt->execute([$header['GHID'], $product['PDID']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if($existing !== false)
        {
            return array_merge($line, [
                'message' => 'Adds to the existing line: ' . self::qty($existing['InitQty']) . ' → ' . self::qty((float)$existing['InitQty'] + $qty),
                'existing_id' => (int)$existing['GDID'],
                'existing_qty' => self::qty($existing['InitQty']),
                'prices_locked' => true,
                'purchase_price' => self::money($existing['UnitPurchasePrice']),
                'selling_price' => self::money($existing['UnitSellPrice']),
                'label_price' => self::money($existing['UnitLabelPrice'] === null ? '0' : $existing['UnitLabelPrice']),
                'mnf_date' => $existing['MnfDate'],
                'exp_date' => $existing['ExpDate'],
            ]);
        }//adds to the product's line

        //new line: prices of the product's latest batch here, else the product's own prices
        $stmt = $pdo->prepare("SELECT pricehistory.PurchasePrice, pricehistory.SellingPrice, pricehistory.labelPrice FROM pricehistory
            INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
            WHERE inventory.products_PDID = ? AND inventory.shop_SHID = ? ORDER BY pricehistory.PHID DESC LIMIT 1;");
        $stmt->execute([$product['PDID'], $shop['SHID']]);
        $batch = $stmt->fetch(PDO::FETCH_ASSOC);
        $purchase = self::money($batch ? $batch['PurchasePrice'] : $product['ProdPurchasePrice']);
        $selling = self::money($batch ? $batch['SellingPrice'] : $product['ProdSellPrice']);
        $label = $options['label_price'] ? self::money($batch && $batch['labelPrice'] !== null ? $batch['labelPrice'] : $selling) : '0.00';

        $prices = ['purchase' => $purchase, 'selling' => $selling, 'label' => $label];
        $typed = (isset($decisions['prices'][$product['PDID']]) && is_array($decisions['prices'][$product['PDID']])) ? $decisions['prices'][$product['PDID']] : [];
        $problem = null;
        foreach(['purchase', 'selling', 'label'] as $field)
        {
            if($field === 'label' && !$options['label_price'])
            {
                continue;
            }//label prices not used here
            if(array_key_exists($field, $typed))
            {
                $value = self::money($typed[$field]);
                if($value === null)
                {
                    $problem = $problem ?: 'Enter a valid ' . $field . ' price';
                    $value = is_scalar($typed[$field]) ? (string)$typed[$field] : '';
                }
                $prices[$field] = $value;
            }//typed prices win
            elseif($prices[$field] === null)
            {
                $problem = $problem ?: 'Enter a valid ' . $field . ' price';
            }//no price anywhere
        }//each price

        $mnf = date('Y-m-d');
        $exp = date('Y-m-d');                   //manual entry stamps today when the shop has no expiry
        if($options['expiry'])
        {
            $dates = (isset($decisions['dates'][$product['PDID']]) && is_array($decisions['dates'][$product['PDID']])) ? $decisions['dates'][$product['PDID']] : [];
            $mnf = isset($dates['mnf']) ? self::validDate($dates['mnf']) : null;
            $exp = isset($dates['exp']) ? self::validDate($dates['exp']) : null;
            $today = date('Y-m-d');
            if($mnf === null || $exp === null)
            {
                $problem = $problem ?: 'Enter the Mnf and Exp dates';
            }
            elseif(!($mnf < $today && $exp > $today && $exp > $mnf))
            {
                $problem = $problem ?: 'Mnf must be before today; Exp after today and after Mnf';
            }
            $mnf = $mnf === null ? (isset($dates['mnf']) && is_string($dates['mnf']) ? $dates['mnf'] : '') : $mnf;
            $exp = $exp === null ? (isset($dates['exp']) && is_string($dates['exp']) ? $dates['exp'] : '') : $exp;
        }//expiry tracked

        $line['editable'] = true;
        $line['purchase_price'] = $prices['purchase'];
        $line['selling_price'] = $prices['selling'];
        $line['label_price'] = $prices['label'];
        $line['mnf_date'] = $mnf;
        $line['exp_date'] = $exp;
        if($problem !== null)
        {
            $line['status'] = 'error';
            $line['message'] = $problem;
        }
        return $line;
    }//line

    private function racks($shop_id)
    {
        $stmt = $this->connect()->prepare("SELECT rack.RKID, rack.RackName, sections.SectionName FROM rack
            INNER JOIN sections ON sections.SEID = rack.Sections_SEID WHERE sections.shop_SHID = ? ORDER BY sections.SEID, rack.RKID;");
        $stmt->execute([(int)$shop_id]);
        return array_map(function($row) {
            return ['id' => (int)$row['RKID'], 'name' => $row['SectionName'] . ' - ' . $row['RackName']];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }//racks
}//GrnScan
```


`Includes/scan_upload.php`:

```php
<?php
//Everything the scanner upload needs (docs/superpowers/specs/2026-09-22-scanner-upload-design.md).
require_once __DIR__ . '/../Model/shop_access_class.php';
require_once __DIR__ . '/../Model/scan_parser_class.php';
require_once __DIR__ . '/../Model/scan_refused_class.php';
require_once __DIR__ . '/../Model/scan_batch_class.php';
require_once __DIR__ . '/../Model/scan_document_class.php';
require_once __DIR__ . '/../Model/scan_grn_class.php';
```

`tests/bootstrap.php`: replace the three model requires from Task 2 with `require_once __DIR__ . '/../Includes/scan_upload.php';`.

- [ ] **Step 4: Run the suite**

Run: `C:/xampp/php/php.exe tools/phpunit.phar`
Expected: `OK` (12 more tests).

- [ ] **Step 5: Commit and push**

```bash
git add Model/scan_document_class.php Model/scan_grn_class.php Includes/scan_upload.php tests/GrnScanTest.php tests/bootstrap.php
git commit -m "feat(scan): GRN preview and apply - merge, prices, checks, duplicate guard"
git push origin development
```

---

### Task 4: The endpoint and the GRN end-to-end checks

**Files:**
- Create: `Controller/ScanUploadController.php`
- Create: `tests/e2e/lib.php` (moved out of `shop_login_e2e.php`: `E2EDb`, `E2EBrowser`, `E2EFixtures`, `check`, `checkClean`, `signIn`, `shopLogin`, `sees`; plus the new `E2EStock`)
- Modify: `tests/e2e/shop_login_e2e.php` (require `lib.php`, keep its checks)
- Create: `tests/e2e/scan_upload_e2e.php`

**Interfaces:**
- Consumes: Task 3 `GrnScan`, `ScanRefused`.
- Produces: `POST Controller/ScanUploadController.php` with `context`, `doc_id`, `action` (`check` | `apply`), `raw`, `csrf_token` and the decisions. Answers `{ok: true, message: '', preview}` for check, `{ok: true, message, result: {doc_id, lines, qty}}` for apply, or `{ok: false, message, preview?, confirm?}` with 400/403/404/409/422/500. `E2EStock($pdo, E2EFixtures)` with `up()`, `down()`, `$products['bed'|'sheet']`, `$grn['open'|'verified'|'showroom']`, `$transfer`, `$inventory['bed1'|'bed2'|'sheet']`.

- [ ] **Step 1: Move the E2E helpers into `tests/e2e/lib.php`** (unchanged code, plus `csrf()` also reading `id="scan_csrf_token"`), make `shop_login_e2e.php` `require __DIR__ . '/lib.php';`, and run it:

Run: `C:/xampp/php/php.exe tests/e2e/shop_login_e2e.php`
Expected: `All checks passed.` (81 checks).

- [ ] **Step 2: Write the E2E checks (they fail: no endpoint yet)**

`E2EStock` in `lib.php`:

```php
//stock for the scanner upload checks: products, batches, GRNs and a transfer in the e2e shops
//(everything found again by the e2e shops, so a crashed run is cleaned up next time)
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
        //Store Keeper may change GRNs (2) and transfers (4); Cashier may not
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
            VALUES ('E2E-GRN', CURDATE(), 'E2E', 0, 0, 0, ?, ?, ?, 1, 0, 0, 0, '')")
            ->execute([$stat, $this->fx->users['alice'], $shop]);
        return (int) $this->pdo->lastInsertId();
    }

    //the rows of a GRN or a transfer, for the checks
    public function grnLines($grn)
    {
        $stmt = $this->pdo->prepare("SELECT products_PDID, InitQty FROM grndetails WHERE GRNHeader_GHID = ? ORDER BY GDID");
        $stmt->execute([$grn]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

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
```

(MariaDB refuses `DELETE ... WHERE x IN (SELECT ... FROM same table)`; the `grnheader` / `transferheader` / `inventory` deletes above only sub-select other tables, so they are fine.)

`tests/e2e/scan_upload_e2e.php`:

```php
<?php
//End-to-end check of the scanner upload (GRN, transfer send, transfer receive) against a
//running copy of the application:
//
//   C:/xampp/php/php.exe tests/e2e/scan_upload_e2e.php [base-url]
//
//Like shop_login_e2e.php it creates its own e2e shops, users, products and documents and
//removes them at the end. Run it only against a development database.
require __DIR__ . '/lib.php';

$pdo = (new E2EDb())->pdo();
$fx = new E2EFixtures($pdo);
$stock = new E2EStock($pdo, $fx);
$fx->up();
$stock->up();
$pw = $fx->password;
$W = $fx->shops['W'];
$S = $fx->shops['S'];
$endpoint = 'Controller/ScanUploadController.php';

//one scanner upload request
function scan(E2EBrowser $b, $context, $doc, $action, $raw, array $more = [])
{
    global $endpoint;
    return $b->post($endpoint, $more + ['context' => $context, 'doc_id' => $doc, 'action' => $action, 'raw' => $raw, 'csrf_token' => $b->csrf()]);
}

try {
    $a = new E2EBrowser($base);
    echo "GRN\n";
    signIn($a, 'e2e_alice', $pw);
    shopLogin($a, $W, 'e2e_alice', $pw);
    $a->post('Public/grn-details.php', ['grn_header_id' => $stock->grn['open'], 'grn_header_stat' => 0]);
    check('the GRN page offers Scan / Upload', $a->has('btn-scan-upload') && $a->has('id="scan_upload_modal"') && $a->csrf() !== '', $a);
    checkClean('the GRN page with the scanner dialog', $a);
    $token = $a->csrf();

    $raw = "E2EBED01\nE2EBED01\nE2ESHT01\n";
    scan($a, 'grn', $stock->grn['open'], 'check', $raw);
    $preview = $a->json('preview');
    check('check previews one line per product with its count', $a->status === 200 && $preview['applied'] === ['E2EBED01' => 2, 'E2ESHT01' => 1] && $preview['can_apply'] === true, $a);

    $a->post($endpoint, ['context' => 'grn', 'doc_id' => $stock->grn['open'], 'action' => 'check', 'raw' => $raw]);
    check('a request without the CSRF token is refused (400)', $a->status === 400, $a);

    scan($a, 'grn', $stock->grn['open'], 'apply', "E2EBED01\nNOPE\n");
    check('apply refuses unknown codes that are not left out (422)', $a->status === 422 && $stock->grnLines($stock->grn['open']) === [], $a);

    scan($a, 'grn', $stock->grn['open'], 'apply', $raw);
    $lines = $stock->grnLines($stock->grn['open']);
    check('apply adds the lines to the GRN', $a->status === 200 && $a->json('ok') === true && count($lines) === 2 && $lines[0]['InitQty'] === '2.000', $a);

    scan($a, 'grn', $stock->grn['open'], 'apply', $raw);
    check('the same batch again asks for a confirmation (409)', $a->status === 409 && $a->json('confirm') === 'duplicate' && $stock->grnLines($stock->grn['open'])[0]['InitQty'] === '2.000', $a);
    scan($a, 'grn', $stock->grn['open'], 'apply', $raw, ['confirm_duplicate' => 1]);
    check('confirmed, it is added again', $a->status === 200 && $stock->grnLines($stock->grn['open'])[0]['InitQty'] === '4.000', $a);

    scan($a, 'grn', $stock->grn['verified'], 'check', $raw);
    check('a verified GRN is refused (409)', $a->status === 409, $a);
    scan($a, 'grn', $stock->grn['showroom'], 'check', $raw);
    check('another shop\'s GRN is refused (404)', $a->status === 404, $a);

    $a->get('Public/switchshop.php');
    shopLogin($a, $S, 'e2e_alice', $pw);
    $a->post('Public/grn-details.php', ['grn_header_id' => $stock->grn['showroom'], 'grn_header_stat' => 0]);
    scan($a, 'grn', $stock->grn['showroom'], 'check', $raw);
    check('a role without GRN rights is refused (403)', $a->status === 403, $a);

    $anon = new E2EBrowser($base);
    $anon->post($endpoint, ['context' => 'grn', 'doc_id' => $stock->grn['open'], 'action' => 'apply', 'raw' => $raw, 'csrf_token' => $token]);
    check('someone not signed in is refused (403)', $anon->status === 403, $anon);

    //scenarios of later tasks are added above this line
} finally {
    $stock->down();
    $fx->down();
}

echo $failures === 0 ? "\nAll checks passed.\n" : "\n" . $failures . " check(s) FAILED.\n";
exit($failures === 0 ? 0 : 1);
```

Run: `C:/xampp/php/php.exe tests/e2e/scan_upload_e2e.php`
Expected: FAIL from the first check (no button, no endpoint).

- [ ] **Step 3: Implement the endpoint** - `Controller/ScanUploadController.php`:

```php
<?php
//The scanner upload dialog's endpoint (Assets/jquery/scan_upload.js) - see
//docs/superpowers/specs/2026-09-22-scanner-upload-design.md, section 10.
//POST only, from a signed-in user still allowed in the session's shop, with the page's CSRF
//token. Every answer is JSON: {ok, message, preview?, confirm?, result?}.
include "../Includes/includes.php";
require_once "../Includes/csrf.php";
require_once "../Includes/scan_upload.php";

header('Content-Type: application/json; charset=utf-8');

function scan_respond($status, array $body)
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}//respond

//the user's choices in the dialog, in the shape the models expect
function scan_decisions()
{
    $list = function($key) {
        return (isset($_POST[$key]) && is_array($_POST[$key])) ? $_POST[$key] : [];
    };
    return [
        'leave_out' => array_values(array_filter($list('leave_out'), 'is_string')),
        'prices' => $list('prices'),
        'dates' => $list('dates'),
        'rack_id' => isset($_POST['rack_id']) ? (int)$_POST['rack_id'] : 0,
        'confirm_duplicate' => !empty($_POST['confirm_duplicate']),
        'confirm_short' => !empty($_POST['confirm_short']),
    ];
}//decisions

if(!isset($_SESSION['user_id'], $_SESSION['shop_id']) || !(new ShopAccess())->canAccessShop($_SESSION['user_id'], $_SESSION['shop_id']))
{
    scan_respond(403, ['ok' => false, 'message' => 'Please sign in to the shop again.']);
}//not signed in to this shop

if($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null))
{
    scan_respond(400, ['ok' => false, 'message' => 'Your session expired. Please reload the page and try again.']);
}//not a post from our page

$user_id = (int)$_SESSION['user_id'];
$shop_id = (int)$_SESSION['shop_id'];
$doc_id = isset($_POST['doc_id']) ? (int)$_POST['doc_id'] : 0;
$raw = (isset($_POST['raw']) && is_string($_POST['raw'])) ? $_POST['raw'] : '';
$action = isset($_POST['action']) ? $_POST['action'] : '';
$context = isset($_POST['context']) ? $_POST['context'] : '';
$decisions = scan_decisions();

try
{
    if($context === 'grn')
    {
        $model = new GrnScan();
        $check = function() use ($model, $doc_id, $shop_id, $user_id, $raw, $decisions) { return $model->preview($doc_id, $shop_id, $user_id, $raw, $decisions); };
        $apply = function() use ($model, $doc_id, $shop_id, $user_id, $raw, $decisions) { return $model->apply($doc_id, $shop_id, $user_id, $raw, $decisions); };
    }
    else
    {
        scan_respond(400, ['ok' => false, 'message' => 'Unknown upload.']);
    }//contexts of later tasks are added above this line

    if($action === 'check')
    {
        scan_respond(200, ['ok' => true, 'message' => '', 'preview' => $check()]);
    }
    if($action === 'apply')
    {
        $done = $apply();
        scan_respond(200, ['ok' => true, 'message' => $done['message'], 'result' => $done['result']]);
    }
    scan_respond(400, ['ok' => false, 'message' => 'Unknown action.']);
}
catch(ScanRefused $e)
{
    $body = ['ok' => false, 'message' => $e->getMessage()];
    if($e->preview !== null)
    {
        $body['preview'] = $e->preview;
    }
    if($e->confirm !== null)
    {
        $body['confirm'] = $e->confirm;
    }
    scan_respond($e->status, $body);
}
catch(Throwable $e)
{
    error_log('ScanUploadController: ' . $e->getMessage());
    scan_respond(500, ['ok' => false, 'message' => 'Something went wrong. Nothing was saved.']);
}//catch
```

(`scan_respond` exits, so the `$check` / `$apply` closures of an unknown context are never reached.)

- [ ] **Step 4: Add the button and dialog to the GRN page** - done in Task 5; until then the first E2E check still fails. Run the E2E and confirm every check after the first passes.

Run: `C:/xampp/php/php.exe tests/e2e/scan_upload_e2e.php`
Expected: only `the GRN page offers Scan / Upload` and its warnings check fail.

- [ ] **Step 5: Commit and push**

```bash
git add Controller/ScanUploadController.php tests/e2e/lib.php tests/e2e/shop_login_e2e.php tests/e2e/scan_upload_e2e.php
git commit -m "feat(scan): upload endpoint for GRNs, with end-to-end checks"
git push origin development
```

---

### Task 5: The scanner dialog on the GRN page

**Files:**
- Create: `View/modals/scan-upload.php`, `Assets/jquery/scan_upload.js`
- Modify: `Public/grn-details.php` (button, dialog, script), `Assets/jquery/grn_detail.js` (reload on `scanupload:applied`)
- Create: `tests/e2e/fixtures.php` (CLI `up` / `down` for the browser check), `tests/ui/scan_upload_ui.mjs`

**Interfaces:**
- Consumes: Task 4 endpoint.
- Produces: `$scan_upload = ['context', 'doc_id', 'title', 'apply_label']` read by the modal; buttons with class `btn-scan-upload` open it; `$(document).trigger('scanupload:applied', [result])` after an apply.

- [ ] **Step 1: The dialog** - `View/modals/scan-upload.php`:

```php
<?php
//The scanner upload dialog (Assets/jquery/scan_upload.js). The page sets $scan_upload first:
//  context      grn | transfer_send | transfer_receive
//  doc_id       the GRN or transfer header id
//  title        the document number, e.g. GRN_000012
//  apply_label  Add to GRN / Add to Transfer / Apply received quantities
//No "fade": the dialog is visible at once, so the first keystroke of an upload lands in it.
require_once __DIR__ . '/../../Includes/csrf.php';
?>
<div id="scan_upload_modal" class="modal" tabindex="-1" aria-labelledby="scan_upload_title" aria-hidden="true"
    data-context="<?= htmlspecialchars($scan_upload['context']) ?>" data-doc-id="<?= (int)$scan_upload['doc_id'] ?>">
    <div class="modal-dialog modal-dialog-scrollable modal-xl">
        <div class="modal-content">
            <div class="modal-header d-flex align-items-center">
                <h4 class="modal-title" id="scan_upload_title">
                    <i class="ti ti-barcode"></i> Upload from scanner - <?= htmlspecialchars($scan_upload['title']) ?>
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="scan_csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                <div class="alert alert-info py-2 mb-2">
                    Place the scanner in its cradle and press <strong>Upload</strong>. The codes are read here -
                    you don't need to click anything. Several uploads add up.
                </div>
                <textarea id="scan_capture" class="form-control font-monospace" rows="4" spellcheck="false" autocomplete="off"
                    placeholder="Scanned codes appear here, one per line"></textarea>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                    <span id="scan_count" class="fw-semibold">0 codes read</span>
                    <span id="scan_busy" class="text-muted" style="display:none;">Checking...</span>
                    <div class="ms-auto d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="scan_check">Check again</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="scan_toggle_raw">Show raw</button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="scan_clear">Clear</button>
                    </div>
                </div>
                <pre id="scan_raw" class="bg-light border rounded p-2 mt-2 small mb-0" style="display:none; max-height:160px; overflow:auto;"></pre>
                <div id="scan_message" class="mt-2"></div>
                <div id="scan_rack_row" class="row mt-2" style="display:none;">
                    <div class="col-md-6">
                        <label for="scan_rack" class="form-label">Section &amp; Rack for these items</label>
                        <select id="scan_rack" class="form-select"></select>
                    </div>
                </div>
                <div class="table-responsive mt-2">
                    <table class="table table-hover align-middle mb-0" id="scan_preview">
                        <thead></thead>
                        <tbody></tbody>
                    </table>
                </div>
                <p id="scan_summary" class="text-end fw-semibold mt-2 mb-0"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="scan_apply" disabled><?= htmlspecialchars($scan_upload['apply_label']) ?></button>
                <button type="button" class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 2: The script** - `Assets/jquery/scan_upload.js`:

```js
//Scanner upload dialog for GRNs and transfers - see
//docs/superpowers/specs/2026-09-22-scanner-upload-design.md, sections 4 and 11.
//
//The scanner's cradle types the stored codes like a keyboard. The dialog's box takes them
//(Enter and Tab become new lines, so codes never run together); the server reads, counts and
//checks them (Controller/ScanUploadController.php) and this script shows the preview and
//sends the user's choices back. Scanner-speed typing while no field has the focus opens the
//dialog by itself.
(function ($) {
    'use strict';

    var ENDPOINT = '../Controller/ScanUploadController.php';
    var modal, box, context, docId;
    var checkTimer = null;
    var request = 0;                    //only the newest check may draw the preview
    var preview = null;
    var leaveOut = {};                  //key => true
    var burst = { text: '', times: [] };

    var COLUMNS = {
        grn: function (o) {
            var c = ['Barcode', 'Product', 'Qty', 'Purchase Price', 'Selling Price'];
            if (o.label_price) { c.push('Label Price'); }
            if (o.expiry) { c.push('Mnf Date', 'Exp Date'); }
            return c.concat(['Status', '']);
        },
        transfer_send: function () { return ['Barcode', 'Product', 'Qty', 'In Stock', 'Batches', 'Status', '']; },
        transfer_receive: function () { return ['Barcode', 'Product', 'Sent', 'Scanned', 'Received', 'Status', '']; }
    };

    function esc(value) {
        return $('<div>').text(value === null || value === undefined ? '' : String(value)).html();
    }

    function isOpen() { return modal.classList.contains('show'); }

    function editable(el) {
        return !!el && (el.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(el.tagName));
    }

    function open(text) {
        bootstrap.Modal.getOrCreateInstance(modal).show();
        if (text) { box.value += text; }
        focusBox();
        updateCount();
        scheduleCheck();
    }

    function focusBox() {
        box.focus();
        box.setSelectionRange(box.value.length, box.value.length);
    }

    //---- capture ----------------------------------------------------------------------------

    function onBoxKey(e) {
        if (e.key === 'Tab') {                          //a Tab suffix would move the focus away
            e.preventDefault();
            var start = box.selectionStart, end = box.selectionEnd;
            box.value = box.value.slice(0, start) + '\n' + box.value.slice(end);
            box.setSelectionRange(start + 1, start + 1);
            onBoxInput();
        }
    }

    function onBoxInput() {
        updateCount();
        scheduleCheck();
    }

    function resetBurst() { burst.text = ''; burst.times = []; }

    //keys pressed while the dialog is closed and no field has the focus: a burst typed at
    //scanner speed and ended by Enter or Tab opens the dialog with it. While the dialog is
    //open, a key outside any field is sent to the box.
    function onDocumentKey(e) {
        if (e.ctrlKey || e.altKey || e.metaKey) { resetBurst(); return; }
        if (isOpen()) {
            if (!editable(e.target)) { focusBox(); }
            return;
        }
        if (editable(e.target)) { resetBurst(); return; }
        var now = performance.now();
        if (burst.times.length && now - burst.times[burst.times.length - 1] > 100) { resetBurst(); }
        if (e.key === 'Enter' || e.key === 'Tab') {
            var n = burst.times.length;
            var fast = burst.text.length >= 4 && (burst.times[n - 1] - burst.times[0]) / (n - 1) <= 35;
            var text = burst.text;
            resetBurst();
            if (fast) {
                e.preventDefault();
                e.stopPropagation();
                open(text + '\n');
            }
            return;
        }
        if (e.key.length === 1) {
            burst.text += e.key;
            burst.times.push(now);
        }
    }

    function updateCount() {
        var n = box.value.split(/[\s,;]+/).filter(function (t) { return t !== ''; }).length;
        $('#scan_count').text(n + (n === 1 ? ' code read' : ' codes read'));
        $('#scan_raw').text(box.value.replace(/\t/g, '⇥\t').replace(/\r?\n/g, '⏎\n'));
    }

    //---- server -----------------------------------------------------------------------------

    function scheduleCheck() {
        clearTimeout(checkTimer);
        checkTimer = setTimeout(check, 400);
    }

    function decisions(action) {
        var data = {
            context: context,
            doc_id: docId,
            action: action,
            raw: box.value,
            csrf_token: $('#scan_csrf_token').val(),
            leave_out: Object.keys(leaveOut)
        };
        $('#scan_preview [data-field]').each(function () {
            var f = $(this);
            data[f.attr('data-group') + '[' + f.attr('data-product') + '][' + f.attr('data-field') + ']'] = f.val();
        });
        if ($('#scan_rack_row').is(':visible')) { data.rack_id = $('#scan_rack').val(); }
        return data;
    }

    function post(data) {
        return $.ajax({ url: ENDPOINT, method: 'POST', data: data, dataType: 'json' });
    }

    function check() {
        clearTimeout(checkTimer);
        if ($.trim(box.value) === '') { render(null); return; }
        var mine = ++request;
        $('#scan_busy').show();
        post(decisions('check'))
            .done(function (res) {
                if (mine !== request) { return; }
                message('');
                render(res.preview);
            })
            .fail(function (xhr) { if (mine === request) { failed(xhr); } })
            .always(function () { if (mine === request) { $('#scan_busy').hide(); } });
    }

    function apply(extra) {
        var sent = $.extend({}, extra || {});
        $('#scan_apply').prop('disabled', true);
        post($.extend(decisions('apply'), sent))
            .done(function (res) {
                bootstrap.Modal.getOrCreateInstance(modal).hide();
                reset();
                $(document).trigger('scanupload:applied', [res.result]);
                alert(res.message);
            })
            .fail(function (xhr) {
                var res = xhr.responseJSON || {};
                if (xhr.status === 409 && res.confirm) {
                    if (res.preview) { render(res.preview); }
                    if (confirm(res.message)) {
                        sent['confirm_' + res.confirm] = 1;
                        apply(sent);
                        return;
                    }
                    message(res.message, 'warning');
                } else {
                    failed(xhr);
                }
                $('#scan_apply').prop('disabled', !(preview && preview.can_apply));
            });
    }

    function failed(xhr) {
        var res = xhr.responseJSON || {};
        if (res.preview) { render(res.preview); }
        message(res.message || 'Something went wrong. Nothing was saved.', 'danger');
    }

    function message(text, kind) {
        $('#scan_message').html(text ? '<div class="alert alert-' + kind + ' py-2 mb-0">' + esc(text) + '</div>' : '');
    }

    function reset() {
        box.value = '';
        leaveOut = {};
        message('');
        render(null);
        updateCount();
    }

    //---- preview ----------------------------------------------------------------------------

    function render(p) {
        preview = p;
        var head = $('#scan_preview thead').empty();
        var body = $('#scan_preview tbody').empty();
        if (!p) {
            $('#scan_summary').text('');
            $('#scan_rack_row').hide();
            $('#scan_apply').prop('disabled', true);
            return;
        }
        var opts = p.options || {};
        $('#scan_count').text(p.scans + (p.scans === 1 ? ' code read' : ' codes read')
            + (p.truncated ? ' - the upload was too long, only the first part was read' : ''));
        head.append('<tr>' + COLUMNS[context](opts).map(function (c) { return '<th>' + esc(c) + '</th>'; }).join('') + '</tr>');
        p.lines.forEach(function (line) { body.append(row(line, opts)); });
        rack(opts, p.rack_id);

        var products = 0, items = 0;
        $.each(p.applied || {}, function (code, qty) { products++; items += Number(qty); });
        $('#scan_summary').text(products + ' product(s), ' + items + ' item(s)'
            + (p.blocking ? ' - ' + p.blocking + ' line(s) need attention' : ''));
        $('#scan_apply').prop('disabled', !p.can_apply);
        if (p.duplicate) {
            message('This scan batch was already added by ' + p.duplicate.user + ' on ' + p.duplicate.at + '.', 'warning');
        }
    }

    function row(line, opts) {
        var cls = line.left_out ? 'table-secondary text-muted'
            : (line.status === 'error' ? 'table-danger' : (line.status === 'warn' ? 'table-warning' : ''));
        var cells = ['<td class="font-monospace">' + esc(line.barcode || line.key) + '</td>', '<td>' + esc(line.name) + '</td>'];
        if (context === 'grn') {
            cells.push(num(line.qty));
            cells.push(field(line, 'prices', 'purchase', line.purchase_price, 'number'));
            cells.push(field(line, 'prices', 'selling', line.selling_price, 'number'));
            if (opts.label_price) { cells.push(field(line, 'prices', 'label', line.label_price, 'number')); }
            if (opts.expiry) {
                cells.push(field(line, 'dates', 'mnf', line.mnf_date, 'date'));
                cells.push(field(line, 'dates', 'exp', line.exp_date, 'date'));
            }
        } else if (context === 'transfer_send') {
            cells.push(num(line.qty), num(line.available), '<td>' + esc((line.batches || []).map(function (b) {
                return b.batch_id + ' × ' + b.qty;
            }).join(', ')) + '</td>');
        } else {
            cells.push(num(line.sent), num(line.qty), num(line.received));
        }
        cells.push('<td>' + badge(line) + ' <span class="small">' + esc(line.message) + '</span></td>');
        cells.push('<td class="text-end">' + action(line) + '</td>');
        return '<tr class="' + cls + '">' + cells.join('') + '</tr>';
    }

    function num(value) {
        return '<td class="text-end">' + esc(value === null || value === undefined ? '' : value) + '</td>';
    }

    function field(line, group, name, value, type) {
        if (!line.editable || line.left_out) { return '<td>' + esc(value) + '</td>'; }
        return '<td><input type="' + type + '"' + (type === 'number' ? ' step="0.01" min="0"' : '')
            + ' class="form-control form-control-sm" style="min-width:110px" data-group="' + group
            + '" data-product="' + esc(line.product_id) + '" data-field="' + name + '" value="' + esc(value) + '"></td>';
    }

    function badge(line) {
        if (line.left_out) { return '<span class="badge text-bg-secondary">Left out</span>'; }
        if (line.status === 'error') { return '<span class="badge bg-danger">Problem</span>'; }
        if (line.status === 'warn') { return '<span class="badge text-bg-warning">Check</span>'; }
        return '<span class="badge text-bg-success">OK</span>';
    }

    function action(line) {
        if (line.left_out) {
            return '<button type="button" class="btn btn-sm btn-outline-secondary scan-leave-out" data-key="' + esc(line.key) + '">Undo</button>';
        }
        if (!line.can_leave_out) { return ''; }
        return '<button type="button" class="btn btn-sm btn-outline-danger scan-leave-out" data-key="' + esc(line.key)
            + '" title="Leave this code out of the upload">Leave out</button>';
    }

    function rack(opts, selected) {
        if (!opts.racks || !opts.racks_list || !opts.racks_list.length) { $('#scan_rack_row').hide(); return; }
        var select = $('#scan_rack').empty();
        opts.racks_list.forEach(function (r) { select.append($('<option>').val(r.id).text(r.name)); });
        select.val(String(selected));
        $('#scan_rack_row').show();
    }

    //---- wiring -----------------------------------------------------------------------------

    $(function () {
        modal = document.getElementById('scan_upload_modal');
        if (!modal) { return; }
        box = document.getElementById('scan_capture');
        context = modal.getAttribute('data-context');
        docId = modal.getAttribute('data-doc-id');

        $(document).on('click', '.btn-scan-upload', function (e) { e.preventDefault(); open(''); });
        box.addEventListener('keydown', onBoxKey);
        box.addEventListener('input', onBoxInput);
        modal.addEventListener('shown.bs.modal', focusBox);
        $('#scan_check').on('click', function () { check(); focusBox(); });
        $('#scan_clear').on('click', function () { reset(); focusBox(); });
        $('#scan_toggle_raw').on('click', function () {
            $('#scan_raw').toggle();
            $(this).text($('#scan_raw').is(':visible') ? 'Hide raw' : 'Show raw');
            focusBox();
        });
        $('#scan_preview').on('click', '.scan-leave-out', function () {
            var key = $(this).attr('data-key');
            if (leaveOut[key]) { delete leaveOut[key]; } else { leaveOut[key] = true; }
            check();
            focusBox();
        });
        $('#scan_preview').on('change', '[data-field]', function () { check(); });
        $('#scan_rack').on('change', function () { check(); focusBox(); });
        $('#scan_apply').on('click', function () { apply(); });
        document.addEventListener('keydown', onDocumentKey, true);
    });
})(jQuery);
```

- [ ] **Step 3: The GRN page.** In `Public/grn-details.php`:
  - After `include '../View/sidebar.php';` block, compute once:
    ```php
    require_once '../Includes/scan_upload.php';
    $grnScanAllowed = $grn_header_stat < 2 && (new ShopAccess())->hasFeatureRight($_SESSION['user_id'], $shop_id, GrnScan::FEATURE, GrnScan::RIGHTS);
    ```
  - Next to *Add Products* (inside the `<h5>` of the GRN Detail card header):
    ```php
    <?php if($grnScanAllowed) { ?>
    <button type="button" class="btn btn-outline-primary float-end me-2 btn-scan-upload"><i class="ti ti-barcode"></i> Scan / Upload</button>
    <?php } ?>
    ```
  - After the Bootstrap bundle `<script>` at the end of the page:
    ```php
    <?php
    if($grnScanAllowed)
    {
        $scan_upload = ['context' => 'grn', 'doc_id' => $grn_header_id, 'title' => $grn_no, 'apply_label' => 'Add to GRN'];
        include '../View/modals/scan-upload.php';
        ?>
        <script src="../Assets/jquery/scan_upload.js"></script>
        <?php
    }//scanner upload
    ?>
    ```
  - In `Assets/jquery/grn_detail.js`, inside the ready handler, before `//===================== Functions`:
    ```js
    //a scanner upload added lines (Assets/jquery/scan_upload.js): reload the table and totals
    $(document).on('scanupload:applied', function(){
        var grn_header_id = $("#hide_header_id").val();
        LoadTable(grn_header_id);
        setTimeout(() => {
            getFinalTotal();
        }, 1000);
    });
    ```

- [ ] **Step 4: Run the E2E**

Run: `C:/xampp/php/php.exe tests/e2e/scan_upload_e2e.php`
Expected: `All checks passed.`

- [ ] **Step 5: The browser check.** `tests/e2e/fixtures.php` (CLI): `up` runs `E2EFixtures::up()` + `E2EStock::up()` and prints JSON `{password, shops, users, grn, transfer, products}`; `down` runs `E2EStock::down()` + `E2EFixtures::down()`. `tests/ui/scan_upload_ui.mjs` (Node 24, `CHROME` / `PHP` env overrides, optional screenshot folder argument) drives headless Chrome like `scratchpad/ui_check.mjs` did:
  1. sign in as `e2e_alice`, enter the e2e Warehouse through the shop dialog;
  2. open the open GRN (post a form to `grn-details.php`);
  3. blur everything, then type `E2EBED01⏎E2EBED01⏎E2ESHT01⏎` with `Input.dispatchKeyEvent` - check the dialog opened by itself, the box holds three lines and, after 1 s, the preview shows 2 and 1 with *Add to GRN* enabled;
  4. *Clear*, type `E2EBED01⇥E2EBED01⇥` - the box holds two lines (Tab turned into new lines), preview 2;
  5. *Clear*, insert `E2EBED01E2EBED01E2ESHT01` and Enter - preview 2 and 1 (split on the known codes);
  6. type `NOPE123⏎` - a *Problem* row, *Add* disabled; *Leave out* - *Add* enabled;
  7. *Add to GRN* - accept the alert; the dialog closes and the GRN table shows `E2EBED01` with quantity 2;
  8. `down` in a `finally`, whatever happened.

Run: `node tests/ui/scan_upload_ui.mjs <scratchpad>/screens`
Expected: `All UI checks passed.`

- [ ] **Step 6: Run everything, commit and push**

Run: `C:/xampp/php/php.exe tools/phpunit.phar`, both E2E scripts, the UI check. All green.

```bash
git add View/modals/scan-upload.php Assets/jquery/scan_upload.js Public/grn-details.php Assets/jquery/grn_detail.js tests/e2e/fixtures.php tests/ui/scan_upload_ui.mjs tests/e2e/scan_upload_e2e.php
git commit -m "feat(scan): scanner upload dialog on the GRN page"
git push origin development
```

---

### Task 6: Transfer, sending side

**Files:**
- Create: `Model/scan_transfer_class.php`
- Modify: `Includes/scan_upload.php` (require it), `Controller/ScanUploadController.php` (`transfer_send`), `Public/transfer-details.php` (button, dialog, script), `Assets/jquery/transfer_details.js` (reload on `scanupload:applied`), `tests/e2e/scan_upload_e2e.php`, `tests/ui/scan_upload_ui.mjs`
- Test: `tests/TransferScanTest.php`

**Interfaces:**
- Consumes: Task 3 `ScanDocument`.
- Produces: `TransferScan::FEATURE = 4`, `SEND_RIGHTS = ['is_edit']`, `RECEIVE_RIGHTS = ['is_edit', 'is_verify']`, `->sendPreview($transfer_id, $shop_id, $user_id, $raw, array $decisions = [])`, `->sendApply(...)`; send lines add `available`, `batches: [{inventory_id, batch_id, qty, merge}]`.

- [ ] **Step 1: Write the failing tests** - `tests/TransferScanTest.php`:

```php
<?php
final class TransferScanTest extends DatabaseTestCase
{
    private TransferScan $scan;
    private int $warehouse;
    private int $showroom;
    private int $alice;
    private int $bob;
    private int $bed;
    private int $sheet;
    private int $bed1;
    private int $bed2;
    private int $sheet1;
    private int $transfer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scan = new TransferScan();
        $company = $this->createCompany();
        $this->warehouse = $this->createShop($company, ['ShopName' => 'Warehouse']);
        $this->showroom = $this->createShop($company, ['ShopName' => 'Valentino Italy']);
        $keeper = $this->createRole('Store Keeper');
        $this->grant($keeper, 4, ['is_edit']);
        $this->alice = $this->createUser('alice', 'x', $keeper);
        $this->assign($this->alice, $this->warehouse, $keeper);
        $this->bob = $this->createUser('bob', 'x', $keeper);
        $this->assign($this->bob, $this->showroom, $keeper);
        $this->bed = $this->createProduct($this->warehouse, 'COO00001', 'Bed');
        $this->sheet = $this->createProduct($this->warehouse, 'LIN00001', 'Bedsheet');
        $this->bed1 = $this->addStock($this->bed, $this->warehouse, 5, 'B1', 900, 1400);
        $this->bed2 = $this->addStock($this->bed, $this->warehouse, 5, 'B2', 950, 1450);
        $this->sheet1 = $this->addStock($this->sheet, $this->warehouse, 10, 'S1', 200, 350);
        $this->transfer = $this->createTransfer($this->warehouse, $this->showroom, $this->alice);
    }

    private function lines(array $preview)
    {
        $out = [];
        foreach ($preview['lines'] as $line) {
            $out[$line['key']] = $line;
        }
        return $out;
    }

    private function details()
    {
        return $this->pdo->query('SELECT InventoryID, products_PDID, TransferQty, ReceivedQty, UnitPurchasePrice, UnitSellingPrice,
            TransferTotalAmount, Batch_ID, VariationID, RackID, TransferStat FROM transferdetails ORDER BY TDID')->fetchAll(PDO::FETCH_ASSOC);
    }

    private function codes($code, $times)
    {
        return str_repeat($code . "\n", $times);
    }

    public function test_send_takes_the_oldest_stock_first_and_splits_across_batches()
    {
        $line = $this->lines($this->scan->sendPreview($this->transfer, $this->warehouse, $this->alice, $this->codes('COO00001', 7)))['COO00001'];
        $this->assertSame('ok', $line['status']);
        $this->assertSame(10, $line['available']);
        $this->assertSame([['inventory_id' => $this->bed1, 'batch_id' => 'B1', 'qty' => 5, 'merge' => false],
            ['inventory_id' => $this->bed2, 'batch_id' => 'B2', 'qty' => 2, 'merge' => false]], $line['batches']);

        $this->scan->sendApply($this->transfer, $this->warehouse, $this->alice, $this->codes('COO00001', 7), []);
        $this->assertSame([
            ['InventoryID' => $this->bed1, 'products_PDID' => $this->bed, 'TransferQty' => '5.000', 'ReceivedQty' => '5.000',
             'UnitPurchasePrice' => '900.00', 'UnitSellingPrice' => '1400.00', 'TransferTotalAmount' => '4500.00',
             'Batch_ID' => 'B1', 'VariationID' => 0, 'RackID' => 1, 'TransferStat' => 0],
            ['InventoryID' => $this->bed2, 'products_PDID' => $this->bed, 'TransferQty' => '2.000', 'ReceivedQty' => '2.000',
             'UnitPurchasePrice' => '950.00', 'UnitSellingPrice' => '1450.00', 'TransferTotalAmount' => '1900.00',
             'Batch_ID' => 'B2', 'VariationID' => 0, 'RackID' => 1, 'TransferStat' => 0],
        ], $this->details());
    }

    public function test_send_counts_what_the_transfer_already_takes_and_adds_to_its_line()
    {
        $this->insert('transferdetails', ['TransferQty' => 4, 'ReceivedQty' => 4, 'UnitPurchasePrice' => 900, 'UnitSellingPrice' => 1400,
            'TransferTotalAmount' => 3600, 'InventoryID' => $this->bed1, 'products_PDID' => $this->bed, 'VariationID' => 0, 'RackID' => 1,
            'TransferStat' => 0, 'TransferHeader_THID' => $this->transfer, 'Batch_ID' => 'B1']);

        $tooMany = $this->lines($this->scan->sendPreview($this->transfer, $this->warehouse, $this->alice, $this->codes('COO00001', 7)))['COO00001'];
        $this->assertSame('Only 6 in stock (4 already on this transfer)', $tooMany['message']);

        $this->scan->sendApply($this->transfer, $this->warehouse, $this->alice, $this->codes('COO00001', 3), []);
        $rows = $this->details();
        $this->assertCount(2, $rows);
        $this->assertSame(['5.000', '5.000', '4500.00'], [$rows[0]['TransferQty'], $rows[0]['ReceivedQty'], $rows[0]['TransferTotalAmount']]);
        $this->assertSame(['2.000', $this->bed2], [$rows[1]['TransferQty'], $rows[1]['InventoryID']]);
    }

    public function test_send_cannot_take_more_than_is_in_stock()
    {
        $line = $this->lines($this->scan->sendPreview($this->transfer, $this->warehouse, $this->alice, $this->codes('COO00001', 11)))['COO00001'];
        $this->assertSame(['error', 'Only 10 in stock (0 already on this transfer)'], [$line['status'], $line['message']]);
        try {
            $this->scan->sendApply($this->transfer, $this->warehouse, $this->alice, $this->codes('COO00001', 11), []);
            $this->fail('expected a refusal');
        } catch (ScanRefused $e) {
            $this->assertSame(422, $e->status);
        }
        $this->assertSame([], $this->details());
    }

    public function test_send_guards_against_the_same_batch_twice()
    {
        $this->scan->sendApply($this->transfer, $this->warehouse, $this->alice, $this->codes('LIN00001', 2), []);
        try {
            $this->scan->sendApply($this->transfer, $this->warehouse, $this->alice, $this->codes('LIN00001', 2), []);
            $this->fail('expected a refusal');
        } catch (ScanRefused $e) {
            $this->assertSame([409, 'duplicate'], [$e->status, $e->confirm]);
        }
        $this->scan->sendApply($this->transfer, $this->warehouse, $this->alice, $this->codes('LIN00001', 2), ['confirm_duplicate' => 1]);
        $this->assertSame('4.000', $this->details()[0]['TransferQty']);
    }

    public function test_send_is_refused_to_the_receiving_shop_closed_transfers_and_users_without_rights()
    {
        $viewer = $this->createRole('Viewer');
        $this->grant($viewer, 4, ['is_view']);
        $carol = $this->createUser('carol', 'x', $viewer);
        $this->assign($carol, $this->warehouse, $viewer);
        $closed = $this->createTransfer($this->warehouse, $this->showroom, $this->alice, 2);

        foreach ([[$this->transfer, $this->showroom, $this->bob, 404], [$closed, $this->warehouse, $this->alice, 409],
                  [$this->transfer, $this->warehouse, $carol, 403]] as [$transfer, $shop, $user, $status]) {
            try {
                $this->scan->sendPreview($transfer, $shop, $user, 'COO00001');
                $this->fail('expected ' . $status);
            } catch (ScanRefused $e) {
                $this->assertSame($status, $e->status);
            }
        }
    }
}
```

- [ ] **Step 2: Run it to see it fail**

Run: `C:/xampp/php/php.exe tools/phpunit.phar --filter TransferScanTest`
Expected: errors - `Class "TransferScan" not found`.

- [ ] **Step 3: Implement** - `Model/scan_transfer_class.php`:

```php
<?php
//Scanner upload for a transfer (docs/superpowers/specs/2026-09-22-scanner-upload-design.md,
//§7 and §8). The sending shop scans what it sends: the stock is taken from its batches,
//oldest first. The receiving shop scans what arrived (receive... methods).
class TransferScan extends ScanDocument
{
    const FEATURE = 4;                              //Transfer Note
    const SEND_RIGHTS = ['is_edit'];
    const RECEIVE_RIGHTS = ['is_edit', 'is_verify'];

    public function sendPreview($transfer_id, $shop_id, $user_id, $raw, array $decisions = [])
    {
        return $this->buildSend($this->header($transfer_id, $shop_id, $user_id, 'send', false), $raw, $decisions);
    }//send preview

    public function sendApply($transfer_id, $shop_id, $user_id, $raw, array $decisions)
    {
        return $this->transaction(function() use ($transfer_id, $shop_id, $user_id, $raw, $decisions) {
            $header = $this->header($transfer_id, $shop_id, $user_id, 'send', true);
            $preview = $this->buildSend($header, $raw, $decisions);
            $this->assertCanApply($preview, $decisions);

            $pdo = $this->connect();
            $qty = 0;
            foreach($preview['lines'] as $line)
            {
                if($line['left_out'] || $line['status'] === 'error' || $line['apply_qty'] <= 0)
                {
                    continue;
                }
                foreach($line['parts'] as $part)
                {
                    if($part['tdid'] !== null)
                    {
                        $stmt = $pdo->prepare("SELECT TransferQty, ReceivedQty, UnitPurchasePrice FROM transferdetails WHERE TDID = ? FOR UPDATE;");
                        $stmt->execute([$part['tdid']]);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        $received = (float)$row['ReceivedQty'] + $part['qty'];
                        $pdo->prepare("UPDATE transferdetails SET TransferQty = ?, ReceivedQty = ?, TransferTotalAmount = ? WHERE TDID = ?;")
                            ->execute([(float)$row['TransferQty'] + $part['qty'], $received, round($received * (float)$row['UnitPurchasePrice'], 2), $part['tdid']]);
                    }//adds to the batch's line
                    else
                    {
                        $pdo->prepare("INSERT INTO transferdetails (TransferQty, ReceivedQty, UnitPurchasePrice, UnitSellingPrice, MnfDate, ExpDate,
                            TransferTotalAmount, InventoryID, products_PDID, VariationID, RackID, TransferStat, TransferHeader_THID, Batch_ID)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, ?, ?);")
                            ->execute([$part['qty'], $part['qty'], $part['purchase'], $part['selling'], $part['mnf'], $part['exp'],
                                round($part['qty'] * (float)$part['purchase'], 2), $part['inventory_id'], $line['product_id'],
                                $part['variation_id'], $header['THID'], $part['batch_id']]);
                    }//new line for the batch
                }//each batch
                $qty += $line['apply_qty'];
            }//each product

            $this->batches->record(ScanBatches::TRANSFER_OUT, $header['THID'], $shop_id, $user_id, $preview['applied'], $preview['scans']);
            return [
                'message' => 'Added ' . count($preview['applied']) . ' product(s), ' . self::qty($qty) . ' item(s) to ' . $header['TransferNo'] . '.',
                'result' => ['doc_id' => (int)$header['THID'], 'lines' => count($preview['applied']), 'qty' => $qty],
            ];
        });
    }//send apply

    //the transfer, when this user may scan it from this side now (locked for update when $lock)
    private function header($transfer_id, $shop_id, $user_id, $side, $lock)
    {
        $stmt = $this->connect()->prepare("SELECT THID, TransferNo, TransferStat, TransferFrom, TransferTo FROM transferheader WHERE THID = ?"
            . ($lock ? " FOR UPDATE" : "") . ";");
        $stmt->execute([(int)$transfer_id]);
        $header = $stmt->fetch(PDO::FETCH_ASSOC);
        $column = $side === 'send' ? 'TransferFrom' : 'TransferTo';
        if($header === false || (int)$header[$column] !== (int)$shop_id)
        {
            throw new ScanRefused(404, $side === 'send' ? 'This transfer is not sent from this shop.' : 'This transfer is not coming to this shop.');
        }
        if(!$this->access->hasFeatureRight($user_id, $shop_id, self::FEATURE, $side === 'send' ? self::SEND_RIGHTS : self::RECEIVE_RIGHTS))
        {
            throw new ScanRefused(403, 'You do not have the right to change transfers in this shop.');
        }
        if(!in_array((int)$header['TransferStat'], [0, 1], true))
        {
            throw new ScanRefused(409, 'This transfer is already verified or cancelled.');
        }
        return $header;
    }//header

    private function buildSend(array $header, $raw, array $decisions)
    {
        $shop_id = (int)$header['TransferFrom'];
        $byCode = $this->productsByBarcode([$shop_id]);
        $parsed = ScanParser::parse($raw, self::knownBarcodes($byCode));

        //what this transfer already takes from each batch (inventory row)
        $stmt = $this->connect()->prepare("SELECT TDID, InventoryID, TransferQty FROM transferdetails WHERE TransferHeader_THID = ? ORDER BY TDID;");
        $stmt->execute([$header['THID']]);
        $taken = [];
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row)
        {
            $id = (int)$row['InventoryID'];
            $taken[$id] = ['tdid' => isset($taken[$id]) ? $taken[$id]['tdid'] : (int)$row['TDID'],
                'qty' => (isset($taken[$id]) ? $taken[$id]['qty'] : 0) + (float)$row['TransferQty']];
        }

        $lines = [];
        foreach($parsed['items'] as $barcode => $qty)
        {
            [$product, $reason] = $this->pickProduct($byCode[strtoupper($barcode)], $shop_id);
            if($product === null)
            {
                $lines[] = $this->errorLine($barcode, $qty, $reason);
                continue;
            }
            if($product['ItemType'] !== 'P')
            {
                $lines[] = $this->errorLine($barcode, $qty, 'Service item - no stock', $product);
                continue;
            }
            $lines[] = $this->sendLine($product, $barcode, $qty, $shop_id, $taken);
        }//each code
        foreach($parsed['unknown'] as $token => $qty)
        {
            $lines[] = $this->errorLine($token, $qty, 'Not a product in this shop');
        }

        return $this->finish([
            'context' => 'transfer_send',
            'doc_id' => (int)$header['THID'],
            'scans' => $parsed['scans'],
            'truncated' => $parsed['truncated'],
            'lines' => $lines,
            'options' => [],
        ], $decisions, ScanBatches::TRANSFER_OUT);
    }//build send

    //the product's batches with stock, oldest first, less what this transfer already takes
    private function sendLine(array $product, $barcode, $qty, $shop_id, array $taken)
    {
        $stmt = $this->connect()->prepare("SELECT inventory.INID, inventory.CurrentQty, pricehistory.BatchID, pricehistory.PurchasePrice,
            pricehistory.SellingPrice, pricehistory.MnfDate, pricehistory.ExpDate, pricehistory.VariationID
            FROM inventory
            INNER JOIN pricehistory ON pricehistory.PHID = (SELECT MAX(ph.PHID) FROM pricehistory ph WHERE ph.Inventory_INID = inventory.INID)
            WHERE inventory.products_PDID = ? AND inventory.shop_SHID = ? AND inventory.CurrentQty > 0
            ORDER BY inventory.INID ASC;");
        $stmt->execute([$product['PDID'], $shop_id]);

        $free = 0;
        $onTransfer = 0;
        $remaining = $qty;
        $parts = [];
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row)
        {
            $id = (int)$row['INID'];
            $already = isset($taken[$id]) ? $taken[$id]['qty'] : 0;
            $onTransfer += $already;
            $left = (float)$row['CurrentQty'] - $already;
            if($left <= 0)
            {
                continue;
            }
            $free += $left;
            $use = min($left, $remaining);
            if($use > 0)
            {
                $parts[] = [
                    'inventory_id' => $id, 'batch_id' => (string)$row['BatchID'], 'qty' => $use,
                    'tdid' => isset($taken[$id]) ? $taken[$id]['tdid'] : null,
                    'purchase' => $row['PurchasePrice'], 'selling' => $row['SellingPrice'],
                    'mnf' => self::validDate((string)$row['MnfDate']), 'exp' => self::validDate((string)$row['ExpDate']),
                    'variation_id' => empty($row['VariationID']) ? 0 : (int)$row['VariationID'],
                ];
                $remaining -= $use;
            }
        }//each batch

        $line = [
            'key' => $barcode, 'barcode' => $barcode, 'product_id' => (int)$product['PDID'], 'name' => $product['ItemName'],
            'qty' => $qty, 'apply_qty' => $qty, 'status' => 'ok', 'left_out' => false, 'can_leave_out' => true, 'editable' => false,
            'available' => $free + 0,
            'batches' => array_map(function($part) {
                return ['inventory_id' => $part['inventory_id'], 'batch_id' => $part['batch_id'], 'qty' => $part['qty'], 'merge' => $part['tdid'] !== null];
            }, $parts),
            'parts' => $parts,
        ];
        if($remaining > 0)
        {
            $line['status'] = 'error';
            $line['apply_qty'] = 0;
            $line['message'] = 'Only ' . self::qty($free) . ' in stock (' . self::qty($onTransfer) . ' already on this transfer)';
        }//not enough
        else
        {
            $merges = count(array_filter($parts, function($part) { return $part['tdid'] !== null; }));
            $line['message'] = 'From batch ' . implode(', ', array_map(function($part) {
                return $part['batch_id'] . ' × ' . self::qty($part['qty']);
            }, $parts)) . ($merges > 0 ? ' - adds to the lines already on this transfer' : '');
        }
        return $line;
    }//send line
}//TransferScan
```

(`available` and each part's `qty` are PHP numbers: `CurrentQty` is `decimal(12,3)` and comes back as a string, so `(float)` it before arithmetic, and cast whole numbers back with `$n == (int)$n ? (int)$n : $n` so the tests' `10` / `5` compare as ints - add a `private static function number($n)` for that and use it for `available` and part quantities.)

`Includes/scan_upload.php`: add `require_once __DIR__ . '/../Model/scan_transfer_class.php';`.

- [ ] **Step 4: Run the tests**

Run: `C:/xampp/php/php.exe tools/phpunit.phar --filter TransferScanTest`
Expected: OK (5 tests).

- [ ] **Step 5: Endpoint, page, script.**
  - `Controller/ScanUploadController.php`, before the `else` of the context switch:
    ```php
    elseif($context === 'transfer_send')
    {
        $model = new TransferScan();
        $check = function() use ($model, $doc_id, $shop_id, $user_id, $raw, $decisions) { return $model->sendPreview($doc_id, $shop_id, $user_id, $raw, $decisions); };
        $apply = function() use ($model, $doc_id, $shop_id, $user_id, $raw, $decisions) { return $model->sendApply($doc_id, $shop_id, $user_id, $raw, $decisions); };
    }
    ```
  - `Public/transfer-details.php`: after `include '../Includes/editPermission.php';` compute
    ```php
    require_once '../Includes/scan_upload.php';
    $scanAccess = new ShopAccess();
    $scanSend = $transfer_header_stat <= 1 && $headerCheck[0]['TransferFrom'] == $shop_id
        && $scanAccess->hasFeatureRight($_SESSION['user_id'], $shop_id, TransferScan::FEATURE, TransferScan::SEND_RIGHTS);
    ```
    Before `<!-- Add Transfer Items -->` add
    ```php
    <?php if($scanSend) { ?>
    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-outline-primary btn-scan-upload"><i class="ti ti-barcode"></i> Scan / Upload</button>
    </div>
    <?php } ?>
    ```
    and after the Bootstrap bundle script
    ```php
    <?php
    if($scanSend)
    {
        $scan_upload = ['context' => 'transfer_send', 'doc_id' => $transfer_header_id, 'title' => $headerCheck[0]['TransferNo'], 'apply_label' => 'Add to Transfer'];
        include '../View/modals/scan-upload.php';
        ?>
        <script src="../Assets/jquery/scan_upload.js"></script>
        <?php
    }//scanner upload
    ?>
    ```
  - `Assets/jquery/transfer_details.js`, after `getTransferDetail`:
    ```js
    //a scanner upload changed the lines (Assets/jquery/scan_upload.js): reload the table and totals
    $(document).on('scanupload:applied', function(e, result){
        getTransferDetail(result.doc_id);
        getFinalTotal(result.doc_id);
    });
    ```
  - E2E (`tests/e2e/scan_upload_e2e.php`, above the "later tasks" line):
    ```php
    echo "Transfer - sending\n";
    signIn($a, 'e2e_alice', $pw);
    shopLogin($a, $W, 'e2e_alice', $pw);
    $a->get('Public/transfer-details.php?id=' . $stock->transfer);
    check('the sending shop\'s transfer page offers Scan / Upload', $a->has('btn-scan-upload') && $a->has('data-context="transfer_send"'), $a);
    checkClean('the transfer page with the scanner dialog', $a);
    scan($a, 'transfer_send', $stock->transfer, 'check', str_repeat("E2EBED01\n", 7));
    $line = $a->json('preview')['lines'][0];
    check('check picks the oldest batches first', $line['available'] === 10 && array_column($line['batches'], 'qty') === [5, 2], $a);
    scan($a, 'transfer_send', $stock->transfer, 'apply', str_repeat("E2EBED01\n", 7) . "E2ESHT01\nE2ESHT01\nE2ESHT01\n");
    check('apply adds a line per batch', $a->status === 200 && array_column($stock->transferLines(), 'TransferQty') === ['5.000', '2.000', '3.000'], $a);
    scan($a, 'transfer_send', $stock->transfer, 'apply', str_repeat("E2EBED01\n", 4));
    check('more than the stock left is refused (422)', $a->status === 422 && count($stock->transferLines()) === 3, $a);
    ```
  - UI check: open the transfer as `e2e_alice`, type 3 codes with Enter, check the preview shows the batch column; do not apply.

- [ ] **Step 6: Run everything, commit and push**

```bash
git add Model/scan_transfer_class.php Includes/scan_upload.php Controller/ScanUploadController.php Public/transfer-details.php Assets/jquery/transfer_details.js tests/TransferScanTest.php tests/e2e/scan_upload_e2e.php tests/ui/scan_upload_ui.mjs
git commit -m "feat(scan): scanner upload for the sending side of a transfer"
git push origin development
```

---

### Task 7: Transfer, receiving side

**Files:**
- Modify: `Model/scan_transfer_class.php` (`receivePreview`, `receiveApply`), `Controller/ScanUploadController.php` (`transfer_receive`), `Public/transfer-details.php` (receive button + dialog), `tests/TransferScanTest.php`, `tests/e2e/scan_upload_e2e.php`
- Test: `tests/TransferScanTest.php`

**Interfaces:**
- Produces: `->receivePreview($transfer_id, $shop_id, $user_id, $raw, array $decisions = [])`, `->receiveApply(...)`; receive lines add `sent`, `received`, `can_leave_out: false`; the preview adds `short` (items sent but not scanned).

- [ ] **Step 1: Write the failing tests** (append to `TransferScanTest`):

```php
    private function sent()
    {
        //as the sending side left it: bed 5 from B1, bed 2 from B2, sheet 3
        foreach ([[$this->bed1, $this->bed, 5, 900, 'B1'], [$this->bed2, $this->bed, 2, 950, 'B2'], [$this->sheet1, $this->sheet, 3, 200, 'S1']] as [$inv, $product, $qty, $price, $batch]) {
            $this->insert('transferdetails', ['TransferQty' => $qty, 'ReceivedQty' => $qty, 'UnitPurchasePrice' => $price, 'UnitSellingPrice' => $price,
                'TransferTotalAmount' => $qty * $price, 'InventoryID' => $inv, 'products_PDID' => $product, 'VariationID' => 0, 'RackID' => 1,
                'TransferStat' => 0, 'TransferHeader_THID' => $this->transfer, 'Batch_ID' => $batch]);
        }
    }

    private function received()
    {
        return $this->pdo->query('SELECT ReceivedQty, TransferTotalAmount FROM transferdetails ORDER BY TDID')->fetchAll(PDO::FETCH_NUM);
    }

    public function test_receive_compares_the_scans_with_what_was_sent()
    {
        $this->sent();
        $p = $this->scan->receivePreview($this->transfer, $this->showroom, $this->bob, $this->codes('COO00001', 6) . $this->codes('LIN00001', 3) . "NOPE\n");
        $lines = $this->lines($p);

        $this->assertSame([7, 6, 6, 'warn', 'Short 1'], [$lines['COO00001']['sent'], $lines['COO00001']['qty'], $lines['COO00001']['received'], $lines['COO00001']['status'], $lines['COO00001']['message']]);
        $this->assertSame(['ok', 'Match', false], [$lines['LIN00001']['status'], $lines['LIN00001']['message'], $lines['LIN00001']['can_leave_out']]);
        $this->assertSame('Not on this transfer', $lines['NOPE']['message']);
        $this->assertSame(1, $p['blocking']);
        $this->assertSame(1, $p['short']);
    }

    public function test_receive_fills_the_lines_in_order_after_confirming_the_shortage()
    {
        $this->sent();
        $raw = $this->codes('COO00001', 6) . $this->codes('LIN00001', 3);
        try {
            $this->scan->receiveApply($this->transfer, $this->showroom, $this->bob, $raw, []);
            $this->fail('expected a refusal');
        } catch (ScanRefused $e) {
            $this->assertSame([409, 'short'], [$e->status, $e->confirm]);
            $this->assertSame('1 item(s) short. They stay in the sending shop. Apply the received quantities?', $e->getMessage());
        }
        $this->assertSame([['5.000', '4500.00'], ['2.000', '1900.00'], ['3.000', '600.00']], $this->received());

        $this->scan->receiveApply($this->transfer, $this->showroom, $this->bob, $raw, ['confirm_short' => 1]);
        $this->assertSame([['5.000', '4500.00'], ['1.000', '950.00'], ['3.000', '600.00']], $this->received());
    }

    public function test_extra_scans_are_capped_and_unscanned_products_are_received_as_none()
    {
        $this->sent();
        $line = $this->lines($this->scan->receivePreview($this->transfer, $this->showroom, $this->bob, $this->codes('COO00001', 9)))['COO00001'];
        $this->assertSame([7, 'warn', 'Extra 2 - only 7 can be received'], [$line['received'], $line['status'], $line['message']]);

        $this->scan->receiveApply($this->transfer, $this->showroom, $this->bob, $this->codes('LIN00001', 3), ['confirm_short' => 1]);
        $this->assertSame([['0.000', '0.00'], ['0.000', '0.00'], ['3.000', '600.00']], $this->received());
    }

    public function test_receiving_the_same_batch_again_just_sets_the_same_quantities()
    {
        $this->sent();
        $raw = $this->codes('COO00001', 7) . $this->codes('LIN00001', 3);
        $this->scan->receiveApply($this->transfer, $this->showroom, $this->bob, $raw, []);
        $this->scan->receiveApply($this->transfer, $this->showroom, $this->bob, $raw, []);
        $this->assertSame([['5.000', '4500.00'], ['2.000', '1900.00'], ['3.000', '600.00']], $this->received());
        $this->assertSame(2, (int)$this->pdo->query("SELECT COUNT(*) FROM scanbatches WHERE DocType = 'TRF_IN'")->fetchColumn());
    }

    public function test_receive_is_refused_to_the_sending_shop_and_allowed_with_verify_rights()
    {
        $this->sent();
        try {
            $this->scan->receivePreview($this->transfer, $this->warehouse, $this->alice, 'COO00001');
            $this->fail('expected 404');
        } catch (ScanRefused $e) {
            $this->assertSame(404, $e->status);
        }
        $checker = $this->createRole('Checker');
        $this->grant($checker, 4, ['is_verify']);
        $dave = $this->createUser('dave', 'x', $checker);
        $this->assign($dave, $this->showroom, $checker);
        $this->assertSame(7, $this->lines($this->scan->receivePreview($this->transfer, $this->showroom, $dave, 'COO00001'))['COO00001']['sent']);
    }
```

- [ ] **Step 2: Run them to see them fail**

Run: `C:/xampp/php/php.exe tools/phpunit.phar --filter TransferScanTest`
Expected: errors - `Call to undefined method TransferScan::receivePreview()`.

- [ ] **Step 3: Implement** (in `TransferScan`):

```php
    public function receivePreview($transfer_id, $shop_id, $user_id, $raw, array $decisions = [])
    {
        return $this->buildReceive($this->header($transfer_id, $shop_id, $user_id, 'receive', false), $raw, $decisions);
    }//receive preview

    //sets each line's received quantity; receiving sets rather than adds, so the same upload
    //twice changes nothing (no duplicate guard), but every upload is recorded
    public function receiveApply($transfer_id, $shop_id, $user_id, $raw, array $decisions)
    {
        return $this->transaction(function() use ($transfer_id, $shop_id, $user_id, $raw, $decisions) {
            $header = $this->header($transfer_id, $shop_id, $user_id, 'receive', true);
            $preview = $this->buildReceive($header, $raw, $decisions);
            $this->assertCanApply($preview, $decisions);
            if($preview['short'] > 0 && empty($decisions['confirm_short']))
            {
                throw new ScanRefused(409, self::qty($preview['short']) . ' item(s) short. They stay in the sending shop. Apply the received quantities?',
                    $preview, 'short');
            }

            $pdo = $this->connect();
            foreach($preview['groups'] as $group)
            {
                $left = $group['received'];
                foreach($group['tdids'] as $tdid)
                {
                    $stmt = $pdo->prepare("SELECT TransferQty, UnitPurchasePrice FROM transferdetails WHERE TDID = ? FOR UPDATE;");
                    $stmt->execute([$tdid]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $take = min((float)$row['TransferQty'], $left);
                    $left -= $take;
                    $pdo->prepare("UPDATE transferdetails SET ReceivedQty = ?, TransferTotalAmount = ? WHERE TDID = ?;")
                        ->execute([$take, round($take * (float)$row['UnitPurchasePrice'], 2), $tdid]);
                }//each line of the product, in order
            }//each product on the transfer

            $this->batches->record(ScanBatches::TRANSFER_IN, $header['THID'], $shop_id, $user_id, $preview['applied'], $preview['scans']);
            $received = array_sum(array_column($preview['groups'], 'received'));
            return [
                'message' => 'Received quantities set on ' . $header['TransferNo'] . ': ' . self::qty($received) . ' item(s) received'
                    . ($preview['short'] > 0 ? ', ' . self::qty($preview['short']) . ' short.' : '.'),
                'result' => ['doc_id' => (int)$header['THID'], 'lines' => count($preview['groups']), 'qty' => $received],
            ];
        });
    }//receive apply

    private function buildReceive(array $header, $raw, array $decisions)
    {
        $stmt = $this->connect()->prepare("SELECT transferdetails.TDID, transferdetails.products_PDID, transferdetails.TransferQty,
            products.Barcode, products.ItemName FROM transferdetails
            INNER JOIN products ON products.PDID = transferdetails.products_PDID
            WHERE transferdetails.TransferHeader_THID = ? ORDER BY transferdetails.TDID;");
        $stmt->execute([$header['THID']]);

        //the transfer's products by barcode: what was sent and the lines to fill, in order
        $groups = [];
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row)
        {
            $code = trim((string)$row['Barcode']);
            $key = $code === '' ? '#' . $row['products_PDID'] : strtoupper($code);
            if(!isset($groups[$key]))
            {
                $groups[$key] = ['barcode' => $code === '' ? null : $code, 'key' => $code === '' ? $key : $code,
                    'product_id' => (int)$row['products_PDID'], 'name' => $row['ItemName'], 'sent' => 0, 'tdids' => []];
            }
            $groups[$key]['sent'] += (float)$row['TransferQty'];
            $groups[$key]['tdids'][] = (int)$row['TDID'];
        }
        $parsed = ScanParser::parse($raw, array_values(array_filter(array_column($groups, 'barcode'))));

        $lines = [];
        $short = 0;
        foreach($groups as $key => &$group)
        {
            $scanned = ($group['barcode'] !== null && isset($parsed['items'][$group['barcode']])) ? $parsed['items'][$group['barcode']] : 0;
            $group['received'] = min($group['sent'], $scanned);
            $short += max(0, $group['sent'] - $scanned);
            $line = [
                'key' => $group['key'], 'barcode' => $group['barcode'], 'product_id' => $group['product_id'], 'name' => $group['name'],
                'qty' => $scanned, 'sent' => self::number($group['sent']), 'received' => self::number($group['received']),
                'apply_qty' => self::number($group['received']), 'status' => 'ok', 'message' => 'Match',
                'left_out' => false, 'can_leave_out' => false, 'editable' => false,
            ];
            if($scanned < $group['sent'])
            {
                $line['status'] = 'warn';
                $line['message'] = 'Short ' . self::qty($group['sent'] - $scanned);
            }
            elseif($scanned > $group['sent'])
            {
                $line['status'] = 'warn';
                $line['message'] = 'Extra ' . self::qty($scanned - $group['sent']) . ' - only ' . self::qty($group['sent']) . ' can be received';
            }
            $lines[] = $line;
        }
        unset($group);
        foreach($parsed['unknown'] as $token => $qty)
        {
            $lines[] = $this->errorLine($token, $qty, 'Not on this transfer');
        }

        $preview = $this->finish([
            'context' => 'transfer_receive',
            'doc_id' => (int)$header['THID'],
            'scans' => $parsed['scans'],
            'truncated' => $parsed['truncated'],
            'lines' => $lines,
            'options' => [],
        ], $decisions, null);
        $preview['short'] = self::number($short);
        $preview['groups'] = array_values(array_map(function($group) {
            return ['received' => $group['received'], 'tdids' => $group['tdids']];
        }, $groups));
        return $preview;
    }//build receive
```

(`groups` is only needed by `receiveApply`; the controller removes it and each send line's `parts` from the JSON it returns - `unset($preview['groups'])` and `unset($line['parts'])` in a small `scan_public_preview()` helper applied to every preview it sends.)

- [ ] **Step 4: Endpoint, page, E2E**
  - Controller: `transfer_receive` branch like `transfer_send`, calling `receivePreview` / `receiveApply`.
  - `Public/transfer-details.php`:
    ```php
    $scanReceive = $transfer_header_stat <= 1 && $headerCheck[0]['TransferTo'] == $shop_id
        && $scanAccess->hasFeatureRight($_SESSION['user_id'], $shop_id, TransferScan::FEATURE, TransferScan::RECEIVE_RIGHTS);
    ```
    The toolbar shows `<i class="ti ti-barcode"></i> Scan received items` (same `btn-scan-upload` class) when `$scanReceive`; the dialog include takes `['context' => 'transfer_receive', ..., 'apply_label' => 'Apply received quantities']` when `$scanReceive`, else the send settings when `$scanSend`.
  - E2E (as `e2e_bob` in the e2e Showroom, after the sending checks):
    ```php
    echo "Transfer - receiving\n";
    $bob = new E2EBrowser($base);
    signIn($bob, 'e2e_bob', $pw);
    shopLogin($bob, $S, 'e2e_bob', $pw);
    $bob->get('Public/transfer-details.php?id=' . $stock->transfer);
    check('the receiving shop\'s transfer page offers Scan received items', $bob->has('Scan received items') && $bob->has('data-context="transfer_receive"'), $bob);
    checkClean('the receiving transfer page', $bob);
    $raw = str_repeat("E2EBED01\n", 6) . str_repeat("E2ESHT01\n", 3);
    scan($bob, 'transfer_receive', $stock->transfer, 'apply', $raw);
    check('a shortage asks for a confirmation (409)', $bob->status === 409 && $bob->json('confirm') === 'short', $bob);
    scan($bob, 'transfer_receive', $stock->transfer, 'apply', $raw, ['confirm_short' => 1]);
    check('confirmed, the received quantities are set in line order', $bob->status === 200 && array_column($stock->transferLines(), 'ReceivedQty') === ['5.000', '1.000', '3.000'], $bob);
    scan($a, 'transfer_receive', $stock->transfer, 'check', $raw);
    check('the sending shop cannot receive (404)', $a->status === 404, $a);
    ```

- [ ] **Step 5: Run everything, commit and push**

```bash
git add Model/scan_transfer_class.php Controller/ScanUploadController.php Public/transfer-details.php tests/TransferScanTest.php tests/e2e/scan_upload_e2e.php
git commit -m "feat(scan): scanner upload for the receiving side of a transfer"
git push origin development
```

---

### Task 8: Documentation, regression crawl, hand-off

**Files:**
- Create: `db/SCAN_UPLOAD_MODULE.md`
- Modify: `README.md` (feature bullet, test commands), `docs/superpowers/specs/2026-09-22-scanner-upload-design.md` (additions found while building, if any), memory `server-deployment.md` (deploy step)

- [ ] **Step 1: `db/SCAN_UPLOAD_MODULE.md`** - install (`php db/scan_upload_install.php` before the code), how staff use it (GRN, send, receive; the dialog; Leave out; duplicate warning; scanner settings: Enter suffix recommended, Tab / none also work), rights, tests.
- [ ] **Step 2: `README.md`** - bullet under Inventory Management; add `scan_upload_e2e.php` and `node tests/ui/scan_upload_ui.mjs` to the Tests section.
- [ ] **Step 3: Regression crawl** - back up the local database, crawl every menu page on the previous commit and on this branch as admin and Owner (scratchpad `crawl2.php`), compare, restore the database and compare the table checksums.
- [ ] **Step 4: Memory** - add to `server-deployment.md`: the next redeploy also runs `php db/scan_upload_install.php` before uploading; do not upload `tests/` or `tools/`.
- [ ] **Step 5: Commit and push**

```bash
git add db/SCAN_UPLOAD_MODULE.md README.md docs/superpowers/specs/2026-09-22-scanner-upload-design.md
git commit -m "docs: scanner upload module guide and tests"
git push origin development
```

---

## Self-review

- **Spec coverage:** §4 capture → Task 5; §5 parser → Task 2; §6 GRN → Task 3 (+ Task 4 endpoint, Task 5 page); §7 send → Task 6; §8 receive → Task 7; §9 table → Task 1; §10 endpoint → Task 4 (+ 6, 7); §11 screens → Tasks 5-7; §12 testing → every task, crawl in Task 8; §13 delivery → commits per task, deploy note in Task 8.
- **Placeholders:** none; Task 6 names the `number()` helper with its exact rule.
- **Type consistency:** `preview()` / `apply()` (GRN), `sendPreview` / `sendApply` / `receivePreview` / `receiveApply` (transfer) are used with the same names in the controller and tests; decisions keys match between `scan_decisions()`, the models and `scan_upload.js`.
