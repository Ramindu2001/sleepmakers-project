# Customer Orders to the Warehouse Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A showroom records a customer order (lines already given from its stock + lines the warehouse must supply); the warehouse accepts it and fulfils it with a transfer created from the order; both sides follow it to *Handed over*.

**Architecture:** Two new tables and a link column on `transferheader`. One model (`CustomerOrders`) holds every rule and computes progress from the order's transfers; a JSON controller serves two server-rendered pages and a small script. Stock allocation (oldest batches first) is extracted from the scanner upload into `StockAllocator` and shared.

**Tech Stack:** PHP 8.2, MariaDB, PDO (`Dbh` shared connection), jQuery + Bootstrap 5 + select2 (already loaded by `View/head.php`), PHPUnit 11, curl E2E, headless Chrome (Node 24).

**Spec:** `docs/superpowers/specs/2026-09-22-customer-orders-design.md`

## Global Constraints

- Branch `development`; commit and push after every task; messages end with `Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>`.
- The role right is the `sysfeatures` row named **Customer Orders** (module 2), id >= 101, always looked up by name - never a hard-coded id.
- Rights sets: VIEW = any of `is_view, is_create, is_edit, is_verify`; PLACE = `is_create`; CHANGE = `is_create, is_edit`; PROCESS = `is_verify`. Super admin: all.
- Order statuses: 1 Requested, 2 Accepted, 3 Rejected, 4 Cancelled, 5 Handed over; shown statuses add *In transit* and *Arrived* (computed).
- Refusals: `CustomerOrderRefused($status, $message)` - 403 rights, 404 not this shop's order / wrong side, 409 the order moved on, 422 input.
- POS, the transfer Verify and manual transfer entry are unchanged.
- Pages must not load a second jQuery (it would drop select2).

---

### Task 1: Schema - tables, link column, role right

**Files:**
- Create: `db/customer_orders_migration.php`, `db/customer_orders_install.php`, `db/customer_orders.sql`
- Modify: `.gitignore` (re-include those three and `db/CUSTOMER_ORDERS_MODULE.md`), `tests/fixtures/legacy_schema.sql` (add `sysfeatures`), `tests/DatabaseTestCase.php` (run the migration), `tests/bootstrap.php`
- Test: `tests/CustomerOrdersMigrationTest.php`

**Interfaces:** Produces `CustomerOrdersMigration(PDO)->run(): string[]` and `CustomerOrdersMigration::FEATURE_NAME = 'Customer Orders'`.

- [ ] **Step 1: Failing test** - `tests/CustomerOrdersMigrationTest.php`:

```php
<?php
final class CustomerOrdersMigrationTest extends DatabaseTestCase
{
    protected $migrate = false;

    public function test_creates_the_tables_the_link_column_and_the_right_once()
    {
        $this->pdo->exec("INSERT INTO sysfeatures (SFID, FeatureName, SystemModules_SMID) VALUES (72, 'Batch wise sale', 4)");

        $first = (new CustomerOrdersMigration($this->pdo))->run();
        $second = (new CustomerOrdersMigration($this->pdo))->run();

        $this->assertSame(['[ok] customerorders table', '[ok] customerorderlines table', '[ok] transferheader.CustomerOrderID column',
            '[ok] role right "Customer Orders" (feature 101, Orders)'], $first);
        $this->assertSame(['[skip] customerorders table - already in place', '[skip] customerorderlines table - already in place',
            '[skip] transferheader.CustomerOrderID column - already in place', '[skip] role right "Customer Orders" - already in place (feature 101)'], $second);
        $this->assertSame([['SFID' => 101, 'SystemModules_SMID' => 2]],
            $this->pdo->query("SELECT SFID, SystemModules_SMID FROM sysfeatures WHERE FeatureName = 'Customer Orders'")->fetchAll(PDO::FETCH_ASSOC));
        $this->assertSame('YES', $this->pdo->query("SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'transferheader' AND COLUMN_NAME = 'CustomerOrderID'")->fetchColumn());
    }

    public function test_the_right_takes_the_next_id_when_ids_already_pass_100()
    {
        $this->pdo->exec("INSERT INTO sysfeatures (SFID, FeatureName, SystemModules_SMID) VALUES (150, 'Something', 4)");
        (new CustomerOrdersMigration($this->pdo))->run();
        $this->assertSame(151, (int)$this->pdo->query("SELECT SFID FROM sysfeatures WHERE FeatureName = 'Customer Orders'")->fetchColumn());
    }
}
```

Run: `C:/xampp/php/php.exe tools/phpunit.phar --filter CustomerOrdersMigration` - Expected: `Class "CustomerOrdersMigration" not found` (after adding `sysfeatures` to the fixture; before that: table missing).

- [ ] **Step 2: Implement**

`tests/fixtures/legacy_schema.sql` - append:

```sql
CREATE TABLE `sysfeatures` (
  `SFID` int(11) NOT NULL AUTO_INCREMENT,
  `FeatureName` varchar(45) DEFAULT NULL,
  `SystemModules_SMID` int(11) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 9999,
  PRIMARY KEY (`SFID`),
  KEY `fk_SysFeatures_SystemModules1_idx` (`SystemModules_SMID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
```

`db/customer_orders_migration.php`:

```php
<?php
/**
 * Customer orders - the schema change (db/CUSTOMER_ORDERS_MODULE.md).
 * -----------------------------------------------------------------------------
 *   customerorders                    a showroom's order for a customer, sent to the shop that supplies it
 *   customerorderlines                its lines: GIVEN from the showroom's stock, or WAREHOUSE (to supply)
 *   transferheader.CustomerOrderID    the order a transfer was created from
 *   sysfeatures "Customer Orders"     the role right (module 2, Orders); id >= 101, always found by name
 *
 * ADDITIVE ONLY and idempotent. Run through db/customer_orders_install.php (the tests call it
 * directly).
 */
class CustomerOrdersMigration
{
    const FEATURE_NAME = 'Customer Orders';

    const ORDERS_TABLE = "CREATE TABLE `customerorders` (
        `COID` int(11) NOT NULL AUTO_INCREMENT,
        `OrderNo` varchar(12) NOT NULL,
        `shop_SHID` int(11) NOT NULL COMMENT 'the showroom that took the order',
        `SupplierShopID` int(11) NOT NULL COMMENT 'the shop asked to supply it',
        `CustName` varchar(120) NOT NULL,
        `CustPhone` varchar(25) NOT NULL,
        `CustAddress` varchar(255) DEFAULT NULL,
        `NeededBy` date DEFAULT NULL,
        `AdvancePaid` decimal(12,2) NOT NULL DEFAULT 0.00,
        `Notes` text DEFAULT NULL,
        `OrderStat` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1 requested, 2 accepted, 3 rejected, 4 cancelled, 5 handed over',
        `RejectReason` varchar(255) DEFAULT NULL,
        `HandoverInvoiceNo` varchar(60) DEFAULT NULL,
        `user_USID` int(11) NOT NULL,
        `CreatedAt` datetime NOT NULL,
        `DecidedBy` int(11) DEFAULT NULL,
        `DecidedAt` datetime DEFAULT NULL,
        `ClosedBy` int(11) DEFAULT NULL,
        `ClosedAt` datetime DEFAULT NULL,
        PRIMARY KEY (`COID`),
        UNIQUE KEY `uq_customerorders_shop_no` (`shop_SHID`, `OrderNo`),
        KEY `idx_customerorders_supplier` (`SupplierShopID`, `OrderStat`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    const LINES_TABLE = "CREATE TABLE `customerorderlines` (
        `COLID` int(11) NOT NULL AUTO_INCREMENT,
        `customerorders_COID` int(11) NOT NULL,
        `LineSource` varchar(9) NOT NULL COMMENT 'GIVEN (from the showroom stock) or WAREHOUSE',
        `products_PDID` int(11) DEFAULT NULL COMMENT 'GIVEN: showroom product; WAREHOUSE: supplier product, NULL = custom-made',
        `Description` varchar(255) NOT NULL,
        `Qty` decimal(12,3) NOT NULL,
        `Notes` varchar(255) DEFAULT NULL,
        `InvoiceNo` varchar(60) DEFAULT NULL,
        `CustomSent` decimal(12,3) NOT NULL DEFAULT 0.000,
        `CustomNote` varchar(255) DEFAULT NULL,
        `SortOrder` int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (`COLID`),
        KEY `idx_customerorderlines_order` (`customerorders_COID`),
        CONSTRAINT `fk_customerorderlines_order` FOREIGN KEY (`customerorders_COID`) REFERENCES `customerorders` (`COID`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }//construct

    //apply every step; returns one report line per step
    public function run()
    {
        $report = [];
        $report[] = $this->step('customerorders table', !$this->hasTable('customerorders'), self::ORDERS_TABLE);
        $report[] = $this->step('customerorderlines table', !$this->hasTable('customerorderlines'), self::LINES_TABLE);
        $report[] = $this->step('transferheader.CustomerOrderID column', !$this->hasColumn('transferheader', 'CustomerOrderID'),
            "ALTER TABLE transferheader ADD COLUMN CustomerOrderID INT(11) NULL DEFAULT NULL COMMENT 'customer order it was created from',
             ADD KEY idx_transferheader_customerorder (CustomerOrderID)");

        $feature = $this->featureId();
        if ($feature === null) {
            //at least 101: the sidebar already refers to a feature 74 that does not exist here
            $feature = max(101, (int)$this->pdo->query("SELECT COALESCE(MAX(SFID), 0) + 1 FROM sysfeatures")->fetchColumn());
            $this->pdo->prepare("INSERT INTO sysfeatures (SFID, FeatureName, SystemModules_SMID, sort_order) VALUES (?, ?, 2, ?)")
                ->execute([$feature, self::FEATURE_NAME, $feature]);
            $report[] = '[ok] role right "' . self::FEATURE_NAME . '" (feature ' . $feature . ', Orders)';
        } else {
            $report[] = '[skip] role right "' . self::FEATURE_NAME . '" - already in place (feature ' . $feature . ')';
        }//role right
        return $report;
    }//run

    private function step($label, $needed, $sql)
    {
        if (!$needed) {
            return '[skip] ' . $label . ' - already in place';
        }
        $this->pdo->exec($sql);
        return '[ok] ' . $label;
    }//step

    private function hasTable($table)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
        $stmt->execute([$table]);
        return (int)$stmt->fetchColumn() > 0;
    }//has table

    private function hasColumn($table, $column)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    }//has column

    private function featureId()
    {
        $stmt = $this->pdo->prepare("SELECT SFID FROM sysfeatures WHERE FeatureName = ? ORDER BY SFID LIMIT 1");
        $stmt->execute([self::FEATURE_NAME]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int)$id;
    }//feature id
}//CustomerOrdersMigration
```

`db/customer_orders_install.php`: same shape as `db/scan_upload_install.php` (CLI only, BOM-safe config load, `CustomerOrdersInstaller extends Dbh` exposing `pdo()`, prints "Customer orders installer", the database name and each report line, `[FAILED]` + exit 1 on `PDOException`).

`db/customer_orders.sql`: the two `CREATE TABLE IF NOT EXISTS` statements, the `ALTER TABLE transferheader ADD COLUMN IF NOT EXISTS CustomerOrderID ...` plus `ADD KEY IF NOT EXISTS idx_transferheader_customerorder (CustomerOrderID)` (MariaDB syntax), and an `INSERT INTO sysfeatures ... SELECT GREATEST(101, COALESCE(MAX(SFID),0)+1), 'Customer Orders', 2, GREATEST(101, COALESCE(MAX(SFID),0)+1) FROM sysfeatures WHERE NOT EXISTS (SELECT 1 FROM sysfeatures WHERE FeatureName = 'Customer Orders')`.

`tests/bootstrap.php`: `require_once __DIR__ . '/../db/customer_orders_migration.php';`. `tests/DatabaseTestCase.php` `setUp()`: run `(new CustomerOrdersMigration($this->pdo))->run();` after the scanner migration. `.gitignore`: the four new `!db/...` lines.

- [ ] **Step 3: Run** `C:/xampp/php/php.exe tools/phpunit.phar` (all green), then install locally twice: `C:/xampp/php/php.exe db/customer_orders_install.php` → `[ok]` x4, then `[skip]` x4.

- [ ] **Step 4: Commit and push** `feat(orders): customer order tables, transfer link and the Customer Orders right`.

---

### Task 2: `StockAllocator` shared by the scanner upload and the orders

**Files:**
- Create: `Model/stock_allocator_class.php`, `tests/StockAllocatorTest.php`
- Modify: `Model/scan_transfer_class.php` (use it), `Includes/scan_upload.php` (require it before the transfer class)

**Interfaces:** Produces `StockAllocator->allocate($product_id, $shop_id, $qty, array $taken): array{parts, free, taken, short}` where `$taken` maps inventory id → `['tdid' => ?int, 'qty' => float]` and each part is `{inventory_id, batch_id, qty, tdid, purchase, selling, mnf, exp, variation_id}`.

- [ ] **Step 1: Failing test** - `tests/StockAllocatorTest.php`:

```php
<?php
final class StockAllocatorTest extends DatabaseTestCase
{
    public function test_takes_the_oldest_batches_first_and_reports_what_is_short()
    {
        $shop = $this->createShop($this->createCompany());
        $bed = $this->createProduct($shop, 'COO00001', 'Bed');
        $b1 = $this->addStock($bed, $shop, 5, 'B1', 900, 1400);
        $b2 = $this->addStock($bed, $shop, 5, 'B2', 950, 1450);
        $this->addStock($bed, $this->createShop($this->createCompany()), 50, 'X1', 1, 1);

        $a = (new StockAllocator())->allocate($bed, $shop, 12, []);
        $this->assertSame([[$b1, 'B1', 5], [$b2, 'B2', 5]], array_map(function ($p) { return [$p['inventory_id'], $p['batch_id'], $p['qty']]; }, $a['parts']));
        $this->assertSame([10, 0, 2], [$a['free'], $a['taken'], $a['short']]);
        $this->assertSame(['900.00', '1400.00', null, null, 0, null], [$a['parts'][0]['purchase'], $a['parts'][0]['selling'],
            $a['parts'][0]['mnf'], $a['parts'][0]['exp'], $a['parts'][0]['variation_id'], $a['parts'][0]['tdid']]);
    }

    public function test_what_is_already_taken_reduces_each_batch()
    {
        $shop = $this->createShop($this->createCompany());
        $bed = $this->createProduct($shop, 'COO00001', 'Bed');
        $b1 = $this->addStock($bed, $shop, 5, 'B1', 900, 1400);
        $b2 = $this->addStock($bed, $shop, 5, 'B2', 950, 1450);

        $a = (new StockAllocator())->allocate($bed, $shop, 3, [$b1 => ['tdid' => null, 'qty' => 5], $b2 => ['tdid' => 7, 'qty' => 1]]);
        $this->assertSame([[$b2, 3, 7]], array_map(function ($p) { return [$p['inventory_id'], $p['qty'], $p['tdid']]; }, $a['parts']));
        $this->assertSame([4, 6, 0], [$a['free'], $a['taken'], $a['short']]);
    }
}
```

Expected failure: `Class "StockAllocator" not found`.

- [ ] **Step 2: Implement** - `Model/stock_allocator_class.php`:

```php
<?php
//Takes a quantity of a product from a shop's stock batches, oldest first (inventory rows with
//stock, by INID), the way the transfer page takes it from a batch: each part carries its
//batch's prices, dates and variation. Used by the scanner upload (TransferScan) and by
//customer orders (CustomerOrders).
class StockAllocator extends Dbh
{
    //$taken: inventory id => ['tdid' => the line already taking from it on the transfer being
    //filled (or null), 'qty' => how much is already taken]. Returns
    //['parts' => [...], 'free' => stock left after $taken, 'taken' => already taken from these
    //batches, 'short' => what could not be allocated]
    public function allocate($product_id, $shop_id, $qty, array $taken)
    {
        $stmt = $this->connect()->prepare("SELECT inventory.INID, inventory.CurrentQty, pricehistory.BatchID, pricehistory.PurchasePrice,
            pricehistory.SellingPrice, pricehistory.MnfDate, pricehistory.ExpDate, pricehistory.VariationID
            FROM inventory
            INNER JOIN pricehistory ON pricehistory.PHID = (SELECT MAX(ph.PHID) FROM pricehistory ph WHERE ph.Inventory_INID = inventory.INID)
            WHERE inventory.products_PDID = ? AND inventory.shop_SHID = ? AND inventory.CurrentQty > 0
            ORDER BY inventory.INID ASC;");
        $stmt->execute([(int)$product_id, (int)$shop_id]);

        $free = 0;
        $already = 0;
        $remaining = (float)$qty;
        $parts = [];
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row)
        {
            $id = (int)$row['INID'];
            $takenHere = isset($taken[$id]) ? (float)$taken[$id]['qty'] : 0;
            $already += $takenHere;
            $left = (float)$row['CurrentQty'] - $takenHere;
            if($left <= 0)
            {
                continue;
            }//this batch is already all taken
            $free += $left;
            $use = min($left, $remaining);
            if($use > 0)
            {
                $parts[] = [
                    'inventory_id' => $id,
                    'batch_id' => (string)$row['BatchID'],
                    'qty' => self::number($use),
                    'tdid' => isset($taken[$id]['tdid']) ? $taken[$id]['tdid'] : null,
                    'purchase' => $row['PurchasePrice'],
                    'selling' => $row['SellingPrice'],
                    'mnf' => self::date($row['MnfDate']),
                    'exp' => self::date($row['ExpDate']),
                    'variation_id' => empty($row['VariationID']) ? 0 : (int)$row['VariationID'],
                ];
                $remaining -= $use;
            }
        }//each batch, oldest first

        return ['parts' => $parts, 'free' => self::number($free), 'taken' => self::number($already), 'short' => self::number(max(0, $remaining))];
    }//allocate

    private static function number($number)
    {
        $number = round((float)$number, 3);
        return $number == (int)$number ? (int)$number : $number;
    }//number

    private static function date($value)
    {
        $value = trim((string)$value);
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        return ($date && $date->format('Y-m-d') === $value) ? $value : null;
    }//date
}//StockAllocator
```

`Model/scan_transfer_class.php`: add `private $allocator;` and a constructor `parent::__construct(); $this->allocator = new StockAllocator();`; in `sendLine()` replace the query and loop with:

```php
        $allocation = $this->allocator->allocate($product['PDID'], $shop_id, $qty, $taken);
        $parts = $allocation['parts'];
        $free = $allocation['free'];
        $onTransfer = $allocation['taken'];
        $remaining = $allocation['short'];
```

(the rest of `sendLine()` is unchanged). `Includes/scan_upload.php`: `require_once __DIR__ . '/../Model/stock_allocator_class.php';` before the transfer class.

- [ ] **Step 3: Run** the whole suite - the 11 `TransferScanTest` tests and the 2 new ones pass. Run `tests/e2e/scan_upload_e2e.php` - passes.
- [ ] **Step 4: Commit and push** `refactor(scan): stock allocation in StockAllocator, shared with customer orders`.

---

### Task 3: `CustomerOrders` - place, edit, read

**Files:**
- Create: `Model/customer_order_refused_class.php`, `Model/customer_order_class.php`, `Includes/customer_orders.php`, `tests/CustomerOrdersTest.php`
- Modify: `tests/bootstrap.php` (require `Includes/customer_orders.php`)

**Interfaces:**
- Consumes: `ShopAccess::hasFeatureRight`, `StockAllocator`.
- Produces: `CustomerOrderRefused(int $status, string $message)` with public `$status`; `CustomerOrders` constants (statuses, rights sets, `FEATURE_NAME`); `featureId(): int`; `can($user_id, $shop_id, array $rights): bool`; `supplierShops($shop_id): array<{SHID, ShopName}>`; `searchProducts($shop_id, $term, $limit = 30): array<{id, barcode, name, in_stock}>`; `create($shop_id, $user_id, array $data): array{id, order_no}`; `update($id, $shop_id, $user_id, array $data)`; `get($id, $shop_id, $user_id): array` (order + `lines` + `transfers` + `status` + `side` + `can_*` flags); `listFor($shop_id, $user_id, $side): array`; `incomingCount($shop_id): int`.
- `$data`: `supplier_id, cust_name, cust_phone, cust_address, needed_by, advance, notes, lines[]` with each line `source (GIVEN|WAREHOUSE), product_id, description, qty, notes, invoice_no`.

- [ ] **Step 1: Failing tests** - `tests/CustomerOrdersTest.php` (Task 4 adds more to the same file):

```php
<?php
final class CustomerOrdersTest extends DatabaseTestCase
{
    private CustomerOrders $orders;
    private int $warehouse;
    private int $showroom;
    private int $alice;     //store keeper in the warehouse: processes orders
    private int $bob;       //sales in the showroom: places orders
    private int $bed;
    private int $bed1;
    private int $bed2;
    private int $sheet;     //the showroom's own bedsheet

    protected function setUp(): void
    {
        parent::setUp();
        $this->orders = new CustomerOrders();
        $company = $this->createCompany();
        $this->warehouse = $this->createShop($company, ['ShopName' => 'Warehouse']);
        $this->showroom = $this->createShop($company, ['ShopName' => 'Valentino Italy']);
        $feature = $this->orders->featureId();
        $sales = $this->createRole('Sales');
        $this->grant($sales, $feature, ['is_view', 'is_create', 'is_edit']);
        $keeper = $this->createRole('Store Keeper');
        $this->grant($keeper, $feature, ['is_view', 'is_verify']);
        $this->bob = $this->createUser('bob', 'x', $sales);
        $this->assign($this->bob, $this->showroom, $sales);
        $this->alice = $this->createUser('alice', 'x', $keeper);
        $this->assign($this->alice, $this->warehouse, $keeper);
        $this->bed = $this->createProduct($this->warehouse, 'COO00001', 'Bed');
        $this->bed1 = $this->addStock($this->bed, $this->warehouse, 5, 'B1', 900, 1400);
        $this->bed2 = $this->addStock($this->bed, $this->warehouse, 5, 'B2', 950, 1450);
        $this->sheet = $this->createProduct($this->showroom, 'LIN00001', 'Bedsheet');
    }

    //a bedsheet already given from the showroom, and a bed the warehouse must supply
    private function data(array $overrides = [], ?array $lines = null)
    {
        return $overrides + [
            'supplier_id' => $this->warehouse, 'cust_name' => 'Nimal Perera', 'cust_phone' => '0771234567',
            'cust_address' => 'Colombo 5', 'needed_by' => date('Y-m-d', strtotime('+7 days')), 'advance' => '5000', 'notes' => 'Call first',
            'lines' => $lines ?? [
                ['source' => 'GIVEN', 'product_id' => $this->sheet, 'description' => '', 'qty' => '1', 'notes' => '', 'invoice_no' => 'INV-10'],
                ['source' => 'WAREHOUSE', 'product_id' => $this->bed, 'description' => '', 'qty' => '2', 'notes' => 'King size, firm', 'invoice_no' => ''],
            ],
        ];
    }

    private function refused(callable $call, $status, $message = null)
    {
        try {
            $call();
            $this->fail('expected ' . $status);
        } catch (CustomerOrderRefused $e) {
            $this->assertSame($status, $e->status, $e->getMessage());
            if ($message !== null) {
                $this->assertSame($message, $e->getMessage());
            }
        }
    }

    public function test_a_showroom_places_an_order_with_given_and_warehouse_lines()
    {
        $placed = $this->orders->create($this->showroom, $this->bob, $this->data());
        $order = $this->orders->get($placed['id'], $this->showroom, $this->bob);

        $this->assertSame('CO_000001', $placed['order_no']);
        $this->assertSame(['CO_000001', 'Requested', 'ours', 'Nimal Perera', '5000.00', 'Warehouse'],
            [$order['OrderNo'], $order['status'], $order['side'], $order['CustName'], $order['AdvancePaid'], $order['SupplierName']]);
        $this->assertSame([['GIVEN', 'Bedsheet', 1, 'INV-10'], ['WAREHOUSE', 'Bed', 2, null]],
            array_map(function ($l) { return [$l['LineSource'], $l['Description'], $l['qty'], $l['InvoiceNo']]; }, $order['lines']));
        $this->assertSame('King size, firm', $order['lines'][1]['Notes']);
        $this->assertSame([true, true, false, false], [$order['can_edit'], $order['can_cancel'], $order['can_accept'], $order['can_handover']]);
    }

    public function test_order_numbers_count_per_shop()
    {
        $this->orders->create($this->showroom, $this->bob, $this->data());
        $this->assertSame('CO_000002', $this->orders->create($this->showroom, $this->bob, $this->data())['order_no']);
    }

    public function test_an_order_is_checked_before_it_is_saved()
    {
        $bob = $this->bob;
        $S = $this->showroom;
        $this->refused(function () use ($S, $bob) { $this->orders->create($S, $bob, $this->data(['cust_phone' => ' '])); }, 422, "Enter the customer's name and phone number.");
        $this->refused(function () use ($S, $bob) { $this->orders->create($S, $bob, $this->data(['supplier_id' => $S])); }, 422, 'Choose the shop to order from.');
        $this->refused(function () use ($S, $bob) { $this->orders->create($S, $bob, $this->data([], [
            ['source' => 'GIVEN', 'product_id' => $this->sheet, 'qty' => '1']])); }, 422, 'Add at least one item for Warehouse to supply.');
        $this->refused(function () use ($S, $bob) { $this->orders->create($S, $bob, $this->data([], [
            ['source' => 'WAREHOUSE', 'product_id' => $this->sheet, 'qty' => '1']])); }, 422, 'Line 1: that product is not in the catalog of Warehouse.');
        $this->refused(function () use ($S, $bob) { $this->orders->create($S, $bob, $this->data([], [
            ['source' => 'WAREHOUSE', 'product_id' => $this->bed, 'qty' => '0']])); }, 422, 'Line 1: enter a quantity above 0.');
        $this->refused(function () use ($S, $bob) { $this->orders->create($S, $bob, $this->data([], [
            ['source' => 'WAREHOUSE', 'product_id' => '', 'description' => ' ', 'qty' => '1']])); }, 422, 'Line 1: describe the custom-made item.');
        $this->refused(function () use ($S, $bob) { $this->orders->create($S, $bob, $this->data(['needed_by' => '2026-02-30'])); }, 422, 'Enter a valid "needed by" date.');

        $custom = $this->orders->create($S, $bob, $this->data([], [
            ['source' => 'WAREHOUSE', 'product_id' => '', 'description' => 'Headboard, walnut, 6ft', 'qty' => '1', 'notes' => 'Match the bed']]));
        $line = $this->orders->get($custom['id'], $S, $bob)['lines'][0];
        $this->assertSame([null, 'Headboard, walnut, 6ft', 'Match the bed'], [$line['products_PDID'], $line['Description'], $line['Notes']]);
    }

    public function test_only_a_role_with_the_right_places_orders()
    {
        $clerk = $this->createUser('carol', 'x', $viewer = $this->createRole('Viewer'));
        $this->grant($viewer, $this->orders->featureId(), ['is_view']);
        $this->assign($clerk, $this->showroom, $viewer);
        $this->refused(function () use ($clerk) { $this->orders->create($this->showroom, $clerk, $this->data()); }, 403);

        $admin = $this->createUser('admin', 'x', $viewer, ['UserType' => 1]);
        $this->assertSame('CO_000001', $this->orders->create($this->showroom, $admin, $this->data())['order_no']);
    }

    public function test_the_supplier_sees_it_as_incoming_and_other_shops_not_at_all()
    {
        $placed = $this->orders->create($this->showroom, $this->bob, $this->data());
        $incoming = $this->orders->get($placed['id'], $this->warehouse, $this->alice);
        $this->assertSame(['incoming', true, false, true], [$incoming['side'], $incoming['can_accept'], $incoming['can_edit'], $incoming['can_create_transfer']]);

        $other = $this->createShop($this->createCompany());
        $this->assign($this->alice, $other, $this->createRole('Other'));
        $this->refused(function () use ($placed, $other) { $this->orders->get($placed['id'], $other, $this->createUser('admin', 'x', 1, ['UserType' => 1])); }, 404);

        $this->assertSame([$placed['order_no']], array_column($this->orders->listFor($this->showroom, $this->bob, 'ours'), 'OrderNo'));
        $this->assertSame([$placed['order_no']], array_column($this->orders->listFor($this->warehouse, $this->alice, 'incoming'), 'OrderNo'));
        $this->assertSame([], $this->orders->listFor($this->warehouse, $this->alice, 'ours'));
        $this->assertSame(1, $this->orders->incomingCount($this->warehouse));
    }

    public function test_an_order_is_edited_while_it_waits_for_the_supplier()
    {
        $placed = $this->orders->create($this->showroom, $this->bob, $this->data());
        $this->orders->update($placed['id'], $this->showroom, $this->bob, $this->data(['cust_phone' => '0710000000'], [
            ['source' => 'WAREHOUSE', 'product_id' => $this->bed, 'qty' => '3', 'notes' => 'Queen']]));
        $order = $this->orders->get($placed['id'], $this->showroom, $this->bob);
        $this->assertSame(['0710000000', 1, 3, 'Queen'], [$order['CustPhone'], count($order['lines']), $order['lines'][0]['qty'], $order['lines'][0]['Notes']]);
    }

    public function test_suppliers_are_the_other_active_shops_of_the_company_and_their_catalog_shows_stock()
    {
        $this->createShop((int)$this->pdo->query("SELECT Company_CMID FROM shop WHERE SHID = {$this->showroom}")->fetchColumn(), ['ShopName' => 'Closed', 'ShopStat' => 0]);
        $this->assertSame([['SHID' => $this->warehouse, 'ShopName' => 'Warehouse']], $this->orders->supplierShops($this->showroom));
        $this->assertSame([['id' => $this->bed, 'barcode' => 'COO00001', 'name' => 'Bed', 'in_stock' => 10]], $this->orders->searchProducts($this->warehouse, 'bed'));
        $this->assertSame([], $this->orders->searchProducts($this->warehouse, '%'));
    }
}
```

Expected failure: `Class "CustomerOrders" not found`.

- [ ] **Step 2: Implement**

`Model/customer_order_refused_class.php`:

```php
<?php
//A customer order action that cannot go ahead: the HTTP status to answer with and the message
//for the user (Controller/CustomerOrderController.php).
class CustomerOrderRefused extends RuntimeException
{
    public $status;

    public function __construct($status, $message)
    {
        parent::__construct($message);
        $this->status = $status;
    }//construct
}//CustomerOrderRefused
```

`Includes/customer_orders.php`:

```php
<?php
//Everything customer orders need (docs/superpowers/specs/2026-09-22-customer-orders-design.md).
require_once __DIR__ . '/../Model/shop_access_class.php';
require_once __DIR__ . '/../Model/stock_allocator_class.php';
require_once __DIR__ . '/../Model/customer_order_refused_class.php';
require_once __DIR__ . '/../Model/customer_order_class.php';
```

`Model/customer_order_class.php` - this task writes the class with everything but the Task 4
actions (`cancel`, `accept`, `reject`, `createTransfer`, `markCustomSent`, `handover`); the full
class is in Task 4, Step 2 and replaces this one. For Task 3 write exactly the Task 4 class
**without** those six public methods (the private helpers they use - `lock`, `requireSide`,
`nextTransferNo`, `insertTransferLines` - come with them in Task 4).

- [ ] **Step 3: Run** `C:/xampp/php/php.exe tools/phpunit.phar --filter CustomerOrdersTest` - OK (7 tests); whole suite green.
- [ ] **Step 4: Commit and push** `feat(orders): place, edit and read customer orders`.

---

### Task 4: `CustomerOrders` - accept, reject, cancel, transfer, custom sent, hand-over

**Files:** Modify `Model/customer_order_class.php`, `tests/CustomerOrdersTest.php`.

- [ ] **Step 1: Failing tests** (append to `CustomerOrdersTest`):

```php
    private function transferLines($transfer)
    {
        $stmt = $this->pdo->prepare('SELECT InventoryID, products_PDID, TransferQty, ReceivedQty, UnitPurchasePrice, Batch_ID FROM transferdetails
            WHERE TransferHeader_THID = ? ORDER BY TDID');
        $stmt->execute([$transfer]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //what the transfer Verify would do to the order's view of it
    private function verify($transfer, array $received = null)
    {
        foreach ($this->transferLines($transfer) as $i => $line) {
            if ($received !== null) {
                $this->pdo->prepare('UPDATE transferdetails SET ReceivedQty = ? WHERE TransferHeader_THID = ? AND InventoryID = ?')
                    ->execute([$received[$i], $transfer, $line['InventoryID']]);
            }
        }
        $this->pdo->prepare('UPDATE transferheader SET TransferStat = 2 WHERE THID = ?')->execute([$transfer]);
    }

    public function test_the_supplier_accepts_or_rejects_with_a_reason()
    {
        $one = $this->orders->create($this->showroom, $this->bob, $this->data())['id'];
        $two = $this->orders->create($this->showroom, $this->bob, $this->data())['id'];

        $this->orders->accept($one, $this->warehouse, $this->alice);
        $this->assertSame('Accepted', $this->orders->get($one, $this->warehouse, $this->alice)['status']);
        $this->refused(function () use ($one) { $this->orders->accept($one, $this->warehouse, $this->alice); }, 409);
        $this->refused(function () use ($one) { $this->orders->update($one, $this->showroom, $this->bob, $this->data()); }, 409);

        $this->refused(function () use ($two) { $this->orders->reject($two, $this->warehouse, $this->alice, ' '); }, 422, 'Enter the reason for rejecting the order.');
        $this->orders->reject($two, $this->warehouse, $this->alice, 'Discontinued model');
        $order = $this->orders->get($two, $this->showroom, $this->bob);
        $this->assertSame(['Rejected', 'Discontinued model', 'alice'], [$order['status'], $order['RejectReason'], $order['DecidedByName']]);
    }

    public function test_each_side_only_does_its_own_part()
    {
        $id = $this->orders->create($this->showroom, $this->bob, $this->data())['id'];
        $this->refused(function () use ($id) { $this->orders->accept($id, $this->showroom, $this->bob); }, 404, 'This order was not sent to this shop.');
        $this->refused(function () use ($id) { $this->orders->cancel($id, $this->warehouse, $this->alice); }, 404, 'This order was not placed by this shop.');
        $this->refused(function () use ($id) { $this->orders->createTransfer($id, $this->showroom, $this->bob); }, 404);
    }

    public function test_the_transfer_carries_only_the_warehouse_catalog_lines_oldest_batches_first()
    {
        $id = $this->orders->create($this->showroom, $this->bob, $this->data([], [
            ['source' => 'GIVEN', 'product_id' => $this->sheet, 'qty' => '1', 'invoice_no' => 'INV-10'],
            ['source' => 'WAREHOUSE', 'product_id' => $this->bed, 'qty' => '7'],
            ['source' => 'WAREHOUSE', 'product_id' => '', 'description' => 'Headboard', 'qty' => '1']]))['id'];

        $made = $this->orders->createTransfer($id, $this->warehouse, $this->alice);

        $header = $this->pdo->query("SELECT TransferNo, TransferFrom, TransferTo, TransferStat, shop_SHID, user_USID, CustomerOrderID FROM transferheader WHERE THID = {$made['transfer_id']}")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(['TransferNo' => 'GT_000001', 'TransferFrom' => $this->warehouse, 'TransferTo' => $this->showroom, 'TransferStat' => 0,
            'shop_SHID' => $this->warehouse, 'user_USID' => $this->alice, 'CustomerOrderID' => $id], $header);
        $this->assertSame([
            ['InventoryID' => $this->bed1, 'products_PDID' => $this->bed, 'TransferQty' => '5.000', 'ReceivedQty' => '5.000', 'UnitPurchasePrice' => '900.00', 'Batch_ID' => 'B1'],
            ['InventoryID' => $this->bed2, 'products_PDID' => $this->bed, 'TransferQty' => '2.000', 'ReceivedQty' => '2.000', 'UnitPurchasePrice' => '950.00', 'Batch_ID' => 'B2'],
        ], $this->transferLines($made['transfer_id']));
        $this->assertSame('Transfer GT_000001 created with 7 item(s). Custom-made items are marked sent on the order.', $made['message']);

        $order = $this->orders->get($id, $this->warehouse, $this->alice);
        $this->assertSame(['In transit', 7, 0], [$order['status'], $order['lines'][1]['on_transfer'], $order['lines'][1]['arrived']]);
        $this->assertSame([['TransferNo' => 'GT_000001', 'status' => 'On hold']],
            array_map(function ($t) { return ['TransferNo' => $t['TransferNo'], 'status' => $t['status']]; }, $order['transfers']));
    }

    public function test_what_the_stock_cannot_cover_is_reported_and_sent_later()
    {
        $id = $this->orders->create($this->showroom, $this->bob, $this->data([], [['source' => 'WAREHOUSE', 'product_id' => $this->bed, 'qty' => '12']]))['id'];
        $first = $this->orders->createTransfer($id, $this->warehouse, $this->alice);
        $this->assertSame('Transfer GT_000001 created with 10 item(s). Not enough stock for: Bed (2).', $first['message']);

        $this->refused(function () use ($id) { $this->orders->createTransfer($id, $this->warehouse, $this->alice); }, 422,
            'Nothing left on this order is in stock at Warehouse right now.');
        $this->addStock($this->bed, $this->warehouse, 5, 'B3', 990, 1490);
        $second = $this->orders->createTransfer($id, $this->warehouse, $this->alice);
        $this->assertSame([2], array_map('intval', array_column($this->transferLines($second['transfer_id']), 'TransferQty')));
        $this->refused(function () use ($id) { $this->orders->createTransfer($id, $this->warehouse, $this->alice); }, 409,
            'Everything on this order is already on a transfer.');
    }

    public function test_stock_on_other_open_transfers_is_not_offered_twice()
    {
        $other = $this->createTransfer($this->warehouse, $this->showroom, $this->alice);
        $this->insert('transferdetails', ['TransferQty' => 8, 'ReceivedQty' => 8, 'UnitPurchasePrice' => 900, 'UnitSellingPrice' => 1400,
            'TransferTotalAmount' => 7200, 'InventoryID' => $this->bed1, 'products_PDID' => $this->bed, 'VariationID' => 0, 'RackID' => 1,
            'TransferStat' => 0, 'TransferHeader_THID' => $other, 'Batch_ID' => 'B1']);
        $id = $this->orders->create($this->showroom, $this->bob, $this->data([], [['source' => 'WAREHOUSE', 'product_id' => $this->bed, 'qty' => '5']]))['id'];

        $made = $this->orders->createTransfer($id, $this->warehouse, $this->alice);
        $this->assertSame([[$this->bed2, '2.000']], array_map(function ($l) { return [$l['InventoryID'], $l['TransferQty']]; }, $this->transferLines($made['transfer_id'])));
        $this->assertSame('Transfer GT_000002 created with 2 item(s). Not enough stock for: Bed (3).', $made['message']);
    }

    public function test_progress_follows_the_transfers_up_to_arrived()
    {
        $id = $this->orders->create($this->showroom, $this->bob, $this->data())['id'];
        $made = $this->orders->createTransfer($id, $this->warehouse, $this->alice);
        $this->verify($made['transfer_id'], [1]);
        $order = $this->orders->get($id, $this->showroom, $this->bob);
        $this->assertSame(['In transit', 2, 1, false], [$order['status'], $order['lines'][1]['on_transfer'], $order['lines'][1]['arrived'], $order['can_handover']]);

        $this->pdo->exec("UPDATE transferdetails SET ReceivedQty = 2");
        $order = $this->orders->get($id, $this->showroom, $this->bob);
        $this->assertSame(['Arrived', 2, true], [$order['status'], $order['lines'][1]['arrived'], $order['can_handover']]);
    }

    public function test_a_cancelled_transfer_no_longer_counts()
    {
        $id = $this->orders->create($this->showroom, $this->bob, $this->data())['id'];
        $made = $this->orders->createTransfer($id, $this->warehouse, $this->alice);
        $this->refused(function () use ($id) { $this->orders->cancel($id, $this->showroom, $this->bob); }, 409,
            'Items are already on the way. Ask the supplier to cancel the transfer first.');

        $this->pdo->exec("UPDATE transferheader SET TransferStat = 3 WHERE THID = {$made['transfer_id']}");
        $this->assertSame('Accepted', $this->orders->get($id, $this->showroom, $this->bob)['status']);
        $this->orders->cancel($id, $this->showroom, $this->bob);
        $this->assertSame('Cancelled', $this->orders->get($id, $this->showroom, $this->bob)['status']);
        $this->refused(function () use ($id) { $this->orders->accept($id, $this->warehouse, $this->alice); }, 409);
    }

    public function test_custom_made_lines_are_marked_sent_by_the_supplier()
    {
        $id = $this->orders->create($this->showroom, $this->bob, $this->data([], [
            ['source' => 'WAREHOUSE', 'product_id' => '', 'description' => 'Headboard', 'qty' => '2']]))['id'];
        $line = $this->orders->get($id, $this->warehouse, $this->alice)['lines'][0];
        $this->assertTrue($this->orders->get($id, $this->warehouse, $this->alice)['can_mark_custom']);

        $this->refused(function () use ($id, $line) { $this->orders->markCustomSent($id, $this->warehouse, $this->alice, $line['COLID'], '3', ''); }, 422,
            'Enter a quantity from 0 to 2.');
        $this->orders->markCustomSent($id, $this->warehouse, $this->alice, $line['COLID'], '1', 'Van 2');
        $this->assertSame(['In transit', 1, 'Van 2'], [$this->orders->get($id, $this->showroom, $this->bob)['status'],
            $this->orders->get($id, $this->showroom, $this->bob)['lines'][0]['arrived'], $this->orders->get($id, $this->showroom, $this->bob)['lines'][0]['CustomNote']]);
        $this->orders->markCustomSent($id, $this->warehouse, $this->alice, $line['COLID'], '2', 'Van 2');
        $this->assertSame('Arrived', $this->orders->get($id, $this->showroom, $this->bob)['status']);
    }

    public function test_the_showroom_hands_it_over_only_when_everything_arrived()
    {
        $id = $this->orders->create($this->showroom, $this->bob, $this->data())['id'];
        $this->refused(function () use ($id) { $this->orders->handover($id, $this->showroom, $this->bob, 'INV-11'); }, 409,
            'The order has not fully arrived yet.');
        $this->verify($this->orders->createTransfer($id, $this->warehouse, $this->alice)['transfer_id']);

        $this->orders->handover($id, $this->showroom, $this->bob, 'INV-11');
        $order = $this->orders->get($id, $this->showroom, $this->bob);
        $this->assertSame(['Handed over', 'INV-11', 'bob'], [$order['status'], $order['HandoverInvoiceNo'], $order['ClosedByName']]);
        $this->assertSame(0, $this->orders->incomingCount($this->warehouse));
    }
```

Expected failure: `Call to undefined method CustomerOrders::accept()` (and the others).

- [ ] **Step 2: Implement - the full class** (`Model/customer_order_class.php`):

```php
<?php
//Customer orders: a showroom asks the shop that supplies it (the warehouse) for what a
//customer bought but the showroom does not have - see
//docs/superpowers/specs/2026-09-22-customer-orders-design.md.
//
//GIVEN lines were handed over from the showroom's own stock and are never sent. WAREHOUSE lines
//are supplied: catalog products by a transfer created from the order, custom-made items marked
//sent by hand. Progress is read from those transfers, so the transfer Verify is unchanged.
//Billing stays in POS.
class CustomerOrders extends Dbh
{
    const REQUESTED = 1;
    const ACCEPTED = 2;
    const REJECTED = 3;
    const CANCELLED = 4;
    const HANDED_OVER = 5;

    const FEATURE_NAME = 'Customer Orders';
    const VIEW = ['is_view', 'is_create', 'is_edit', 'is_verify'];
    const PLACE = ['is_create'];
    const CHANGE = ['is_create', 'is_edit'];
    const PROCESS = ['is_verify'];
    const MAX_LINES = 100;

    const TRANSFER_STATUS = [0 => 'On hold', 1 => 'Pending', 2 => 'Verified', 3 => 'Cancelled'];

    private $access;
    private $allocator;
    private $featureId = null;

    public function __construct()
    {
        $this->access = new ShopAccess();
        $this->allocator = new StockAllocator();
    }//construct

    //the role right's id, found by name (0 while the module is not installed)
    public function featureId()
    {
        if($this->featureId === null)
        {
            $stmt = $this->connect()->prepare("SELECT SFID FROM sysfeatures WHERE FeatureName = ? ORDER BY SFID LIMIT 1;");
            $stmt->execute([self::FEATURE_NAME]);
            $this->featureId = (int)$stmt->fetchColumn();
        }
        return $this->featureId;
    }//feature id

    //does the role this user holds in this shop grant one of these rights on Customer Orders?
    public function can($user_id, $shop_id, array $rights)
    {
        $feature = $this->featureId();
        return $feature > 0 && $this->access->hasFeatureRight($user_id, $shop_id, $feature, $rights);
    }//can

    //where this shop can order from: the other active shops of its company
    public function supplierShops($shop_id)
    {
        $stmt = $this->connect()->prepare("SELECT s.SHID, s.ShopName FROM shop s INNER JOIN shop me ON me.Company_CMID = s.Company_CMID
            WHERE me.SHID = ? AND s.SHID <> me.SHID AND s.ShopStat = 1 ORDER BY s.SHID;");
        $stmt->execute([(int)$shop_id]);
        return array_map(function($row) {
            return ['SHID' => (int)$row['SHID'], 'ShopName' => $row['ShopName']];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }//supplier shops

    //a shop's active stock products matching the term, with the stock it holds
    public function searchProducts($shop_id, $term, $limit = 30)
    {
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim((string)$term)) . '%';
        $stmt = $this->connect()->prepare("SELECT p.PDID, p.Barcode, p.ItemName,
            COALESCE((SELECT SUM(i.CurrentQty) FROM inventory i WHERE i.products_PDID = p.PDID AND i.shop_SHID = p.shop_SHID AND i.CurrentQty > 0), 0) AS InStock
            FROM products p WHERE p.shop_SHID = ? AND p.ItemType = 'P' AND p.ProductStat = 1
            AND CONCAT(COALESCE(p.Barcode, ''), ' ', p.ItemName) LIKE ? ORDER BY p.ItemName LIMIT " . max(1, min(100, (int)$limit)) . ";");
        $stmt->execute([(int)$shop_id, $like]);
        return array_map(function($row) {
            return ['id' => (int)$row['PDID'], 'barcode' => (string)$row['Barcode'], 'name' => $row['ItemName'], 'in_stock' => self::number($row['InStock'])];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }//search products

    //---- placing and changing ------------------------------------------------------------------

    public function create($shop_id, $user_id, array $data)
    {
        if(!$this->can($user_id, $shop_id, self::PLACE))
        {
            throw new CustomerOrderRefused(403, 'You do not have the right to place customer orders in this shop.');
        }
        $clean = $this->validate($shop_id, $data);
        return $this->transaction(function() use ($shop_id, $user_id, $clean) {
            $pdo = $this->connect();
            for($attempt = 1; ; $attempt++)
            {
                $order_no = $this->nextOrderNo($shop_id);
                try
                {
                    $pdo->prepare("INSERT INTO customerorders (OrderNo, shop_SHID, SupplierShopID, CustName, CustPhone, CustAddress, NeededBy,
                        AdvancePaid, Notes, OrderStat, user_USID, CreatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?);")
                        ->execute([$order_no, (int)$shop_id, $clean['supplier_id'], $clean['cust_name'], $clean['cust_phone'], $clean['cust_address'],
                            $clean['needed_by'], $clean['advance'], $clean['notes'], (int)$user_id, date('Y-m-d H:i:s')]);
                    break;
                }
                catch(PDOException $e)
                {
                    if($attempt >= 3 || !isset($e->errorInfo[1]) || (int)$e->errorInfo[1] !== 1062)
                    {
                        throw $e;
                    }
                }//another order took the number at the same moment: take the next
            }
            $id = (int)$pdo->lastInsertId();
            $this->insertLines($id, $clean['lines']);
            return ['id' => $id, 'order_no' => $order_no];
        });
    }//create

    //while the supplier has not acted on it, the showroom may change everything
    public function update($id, $shop_id, $user_id, array $data)
    {
        $clean = $this->validate($shop_id, $data);
        $this->transaction(function() use ($id, $shop_id, $user_id, $clean) {
            $order = $this->lock($id, $shop_id);
            $this->requireSide($order, $shop_id, 'ours');
            $this->requireRight($user_id, $shop_id, self::CHANGE, 'change customer orders');
            if((int)$order['OrderStat'] !== self::REQUESTED)
            {
                throw new CustomerOrderRefused(409, 'The supplier has already acted on this order; it can no longer be edited.');
            }
            $this->connect()->prepare("UPDATE customerorders SET SupplierShopID = ?, CustName = ?, CustPhone = ?, CustAddress = ?, NeededBy = ?,
                AdvancePaid = ?, Notes = ? WHERE COID = ?;")
                ->execute([$clean['supplier_id'], $clean['cust_name'], $clean['cust_phone'], $clean['cust_address'], $clean['needed_by'],
                    $clean['advance'], $clean['notes'], (int)$id]);
            $this->connect()->prepare("DELETE FROM customerorderlines WHERE customerorders_COID = ?;")->execute([(int)$id]);
            $this->insertLines((int)$id, $clean['lines']);
        });
    }//update

    public function cancel($id, $shop_id, $user_id)
    {
        $this->transaction(function() use ($id, $shop_id, $user_id) {
            $order = $this->lock($id, $shop_id);
            $this->requireSide($order, $shop_id, 'ours');
            $this->requireRight($user_id, $shop_id, self::CHANGE, 'change customer orders');
            $this->requireOpen($order);
            if($this->progress($order)['moving'])
            {
                throw new CustomerOrderRefused(409, 'Items are already on the way. Ask the supplier to cancel the transfer first.');
            }
            $this->close($id, self::CANCELLED, $user_id);
        });
    }//cancel

    //---- the supplier ----------------------------------------------------------------------------

    public function accept($id, $shop_id, $user_id)
    {
        $this->transaction(function() use ($id, $shop_id, $user_id) {
            $order = $this->lock($id, $shop_id);
            $this->requireSide($order, $shop_id, 'incoming');
            $this->requireRight($user_id, $shop_id, self::PROCESS, 'process customer orders');
            if((int)$order['OrderStat'] !== self::REQUESTED)
            {
                throw new CustomerOrderRefused(409, 'This order is no longer waiting for an answer.');
            }
            $this->decide($id, self::ACCEPTED, $user_id);
        });
    }//accept

    public function reject($id, $shop_id, $user_id, $reason)
    {
        $reason = trim((string)$reason);
        if($reason === '' || strlen($reason) > 255)
        {
            throw new CustomerOrderRefused(422, 'Enter the reason for rejecting the order.');
        }
        $this->transaction(function() use ($id, $shop_id, $user_id, $reason) {
            $order = $this->lock($id, $shop_id);
            $this->requireSide($order, $shop_id, 'incoming');
            $this->requireRight($user_id, $shop_id, self::PROCESS, 'process customer orders');
            $this->requireOpen($order);
            if($this->progress($order)['moving'])
            {
                throw new CustomerOrderRefused(409, 'Items are already on the way. Cancel the transfer first.');
            }
            $this->decide($id, self::REJECTED, $user_id, $reason);
        });
    }//reject

    //a transfer on hold, from this shop to the showroom, for what the order still needs of the
    //catalog lines - taken from the batches oldest first, less what other open transfers take
    public function createTransfer($id, $shop_id, $user_id)
    {
        return $this->transaction(function() use ($id, $shop_id, $user_id) {
            $order = $this->lock($id, $shop_id);
            $this->requireSide($order, $shop_id, 'incoming');
            $this->requireRight($user_id, $shop_id, self::PROCESS, 'process customer orders');
            $this->requireOpen($order);
            $progress = $this->progress($order);

            $needed = [];
            $names = [];
            $custom = false;
            foreach($progress['lines'] as $line)
            {
                if($line['LineSource'] !== 'WAREHOUSE')
                {
                    continue;
                }
                if($line['products_PDID'] === null)
                {
                    $custom = $custom || $line['arrived'] < $line['qty'];
                    continue;
                }//custom-made: marked sent by hand
                $pdid = (int)$line['products_PDID'];
                $needed[$pdid] = (isset($needed[$pdid]) ? $needed[$pdid] : 0) + max(0, $line['qty'] - $line['on_transfer']);
                $names[$pdid] = $line['Description'];
            }
            $needed = array_filter($needed, function($qty) { return $qty > 0; });
            if(empty($needed))
            {
                throw new CustomerOrderRefused(409, 'Everything on this order is already on a transfer.');
            }

            //stock already taken by this shop's other open transfers is not offered twice
            $stmt = $this->connect()->prepare("SELECT td.InventoryID, SUM(td.TransferQty) AS Qty FROM transferdetails td
                INNER JOIN transferheader th ON th.THID = td.TransferHeader_THID
                WHERE th.TransferFrom = ? AND th.TransferStat IN (0, 1) GROUP BY td.InventoryID;");
            $stmt->execute([(int)$shop_id]);
            $taken = [];
            foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row)
            {
                $taken[(int)$row['InventoryID']] = ['tdid' => null, 'qty' => (float)$row['Qty']];
            }

            $parts = [];
            $short = [];
            $total = 0;
            foreach($needed as $pdid => $qty)
            {
                $allocation = $this->allocator->allocate($pdid, $shop_id, $qty, $taken);
                foreach($allocation['parts'] as $part)
                {
                    $parts[] = ['product_id' => $pdid] + $part;
                    $taken[$part['inventory_id']] = ['tdid' => null, 'qty' => (isset($taken[$part['inventory_id']]) ? $taken[$part['inventory_id']]['qty'] : 0) + $part['qty']];
                    $total += $part['qty'];
                }
                if($allocation['short'] > 0)
                {
                    $short[] = $names[$pdid] . ' (' . self::qtyText($allocation['short']) . ')';
                }
            }
            if(empty($parts))
            {
                throw new CustomerOrderRefused(422, 'Nothing left on this order is in stock at ' . $order['SupplierName'] . ' right now.');
            }

            $transfer_no = $this->nextTransferNo($shop_id);
            $pdo = $this->connect();
            $pdo->prepare("INSERT INTO transferheader (TransferNo, EffectiveDate, TransferFrom, TransferTo, TransferTotalCount, TransferTotalAmount,
                TransferStat, shop_SHID, user_USID, CustomerOrderID) VALUES (?, ?, ?, ?, 0, 0, 0, ?, ?, ?);")
                ->execute([$transfer_no, date('Y-m-d'), (int)$shop_id, (int)$order['shop_SHID'], (int)$shop_id, (int)$user_id, (int)$id]);
            $transfer_id = (int)$pdo->lastInsertId();
            foreach($parts as $part)
            {
                $pdo->prepare("INSERT INTO transferdetails (TransferQty, ReceivedQty, UnitPurchasePrice, UnitSellingPrice, MnfDate, ExpDate,
                    TransferTotalAmount, InventoryID, products_PDID, VariationID, RackID, TransferStat, TransferHeader_THID, Batch_ID)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, ?, ?);")
                    ->execute([$part['qty'], $part['qty'], $part['purchase'], $part['selling'], $part['mnf'], $part['exp'],
                        round($part['qty'] * (float)$part['purchase'], 2), $part['inventory_id'], $part['product_id'],
                        $part['variation_id'], $transfer_id, $part['batch_id']]);
            }
            if((int)$order['OrderStat'] === self::REQUESTED)
            {
                $this->decide($id, self::ACCEPTED, $user_id);
            }

            return [
                'transfer_id' => $transfer_id,
                'transfer_no' => $transfer_no,
                'message' => 'Transfer ' . $transfer_no . ' created with ' . self::qtyText($total) . ' item(s).'
                    . (empty($short) ? '' : ' Not enough stock for: ' . implode(', ', $short) . '.')
                    . ($custom ? ' Custom-made items are marked sent on the order.' : ''),
            ];
        });
    }//create transfer

    public function markCustomSent($id, $shop_id, $user_id, $line_id, $qty, $note)
    {
        $this->transaction(function() use ($id, $shop_id, $user_id, $line_id, $qty, $note) {
            $order = $this->lock($id, $shop_id);
            $this->requireSide($order, $shop_id, 'incoming');
            $this->requireRight($user_id, $shop_id, self::PROCESS, 'process customer orders');
            $this->requireOpen($order);
            $stmt = $this->connect()->prepare("SELECT COLID, Qty FROM customerorderlines WHERE COLID = ? AND customerorders_COID = ?
                AND LineSource = 'WAREHOUSE' AND products_PDID IS NULL;");
            $stmt->execute([(int)$line_id, (int)$id]);
            $line = $stmt->fetch(PDO::FETCH_ASSOC);
            if($line === false)
            {
                throw new CustomerOrderRefused(404, 'That line is not a custom-made item of this order.');
            }
            $sent = self::quantity($qty, true);
            if($sent === null || $sent > (float)$line['Qty'])
            {
                throw new CustomerOrderRefused(422, 'Enter a quantity from 0 to ' . self::qtyText($line['Qty']) . '.');
            }
            $note = trim((string)$note);
            if(strlen($note) > 255)
            {
                throw new CustomerOrderRefused(422, 'The note is too long.');
            }
            $this->connect()->prepare("UPDATE customerorderlines SET CustomSent = ?, CustomNote = ? WHERE COLID = ?;")
                ->execute([$sent, $note === '' ? null : $note, (int)$line_id]);
            if((int)$order['OrderStat'] === self::REQUESTED)
            {
                $this->decide($id, self::ACCEPTED, $user_id);
            }
        });
    }//mark custom sent

    //the customer took everything: the showroom closes the order (billing is done in POS)
    public function handover($id, $shop_id, $user_id, $invoice_no)
    {
        $invoice_no = trim((string)$invoice_no);
        if(strlen($invoice_no) > 60)
        {
            throw new CustomerOrderRefused(422, 'The invoice number is too long.');
        }
        $this->transaction(function() use ($id, $shop_id, $user_id, $invoice_no) {
            $order = $this->lock($id, $shop_id);
            $this->requireSide($order, $shop_id, 'ours');
            $this->requireRight($user_id, $shop_id, self::CHANGE, 'change customer orders');
            if(self::statusOf($order, $this->progress($order)) !== 'Arrived')
            {
                throw new CustomerOrderRefused(409, 'The order has not fully arrived yet.');
            }
            $this->connect()->prepare("UPDATE customerorders SET HandoverInvoiceNo = ? WHERE COID = ?;")
                ->execute([$invoice_no === '' ? null : $invoice_no, (int)$id]);
            $this->close($id, self::HANDED_OVER, $user_id);
        });
    }//handover

    //---- reading -------------------------------------------------------------------------------

    public function get($id, $shop_id, $user_id)
    {
        $this->requireRight($user_id, $shop_id, self::VIEW, 'see customer orders');
        $order = $this->find($id, $shop_id, false);
        $progress = $this->progress($order);
        $status = self::statusOf($order, $progress);
        $ours = (int)$order['shop_SHID'] === (int)$shop_id;
        $incoming = (int)$order['SupplierShopID'] === (int)$shop_id;
        $open = in_array((int)$order['OrderStat'], [self::REQUESTED, self::ACCEPTED], true);
        $change = $ours && $this->can($user_id, $shop_id, self::CHANGE);
        $process = $incoming && $this->can($user_id, $shop_id, self::PROCESS);

        $stmt = $this->connect()->prepare("SELECT THID, TransferNo, TransferStat, EffectiveDate FROM transferheader WHERE CustomerOrderID = ? ORDER BY THID;");
        $stmt->execute([(int)$id]);
        $transfers = array_map(function($row) {
            return ['THID' => (int)$row['THID'], 'TransferNo' => $row['TransferNo'], 'EffectiveDate' => $row['EffectiveDate'],
                'status' => isset(self::TRANSFER_STATUS[(int)$row['TransferStat']]) ? self::TRANSFER_STATUS[(int)$row['TransferStat']] : 'Unknown'];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));

        return $order + [
            'lines' => $progress['lines'],
            'transfers' => $transfers,
            'status' => $status,
            'side' => $ours ? 'ours' : 'incoming',
            'can_edit' => $change && (int)$order['OrderStat'] === self::REQUESTED,
            'can_cancel' => $change && $open && !$progress['moving'],
            'can_handover' => $change && $status === 'Arrived',
            'can_accept' => $process && (int)$order['OrderStat'] === self::REQUESTED,
            'can_reject' => $process && $open && !$progress['moving'],
            'can_create_transfer' => $process && $open && $progress['catalog_left'],
            'can_mark_custom' => $process && $open && $progress['has_custom'],
        ];
    }//get

    //this shop's orders: the ones it placed ('ours') or the ones sent to it ('incoming')
    public function listFor($shop_id, $user_id, $side)
    {
        $this->requireRight($user_id, $shop_id, self::VIEW, 'see customer orders');
        $column = $side === 'incoming' ? 'co.SupplierShopID' : 'co.shop_SHID';
        $stmt = $this->connect()->prepare(self::ORDER_SELECT . " WHERE " . $column . " = ? ORDER BY co.COID DESC LIMIT 500;");
        $stmt->execute([(int)$shop_id]);
        $rows = [];
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $order)
        {
            $progress = $this->progress($order);
            $summary = [];
            foreach($progress['lines'] as $line)
            {
                if($line['LineSource'] === 'WAREHOUSE')
                {
                    $summary[] = $line['Description'] . ' × ' . self::qtyText($line['qty']);
                }
            }
            $rows[] = $order + ['status' => self::statusOf($order, $progress), 'summary' => implode(', ', $summary)];
        }
        return $rows;
    }//list for

    //orders waiting for this shop's answer (the menu badge)
    public function incomingCount($shop_id)
    {
        $stmt = $this->connect()->prepare("SELECT COUNT(*) FROM customerorders WHERE SupplierShopID = ? AND OrderStat = 1;");
        $stmt->execute([(int)$shop_id]);
        return (int)$stmt->fetchColumn();
    }//incoming count

    //---- the rules -----------------------------------------------------------------------------

    const ORDER_SELECT = "SELECT co.*, s.ShopName AS ShopName, sup.ShopName AS SupplierName,
        cu.UserName AS CreatedByName, du.UserName AS DecidedByName, xu.UserName AS ClosedByName
        FROM customerorders co
        INNER JOIN shop s ON s.SHID = co.shop_SHID
        INNER JOIN shop sup ON sup.SHID = co.SupplierShopID
        LEFT JOIN user cu ON cu.USID = co.user_USID
        LEFT JOIN user du ON du.USID = co.DecidedBy
        LEFT JOIN user xu ON xu.USID = co.ClosedBy";

    //the lines with what is on a transfer and what arrived, and what that means for the order
    private function progress(array $order)
    {
        $stmt = $this->connect()->prepare("SELECT * FROM customerorderlines WHERE customerorders_COID = ? ORDER BY SortOrder, COLID;");
        $stmt->execute([(int)$order['COID']]);
        $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->connect()->prepare("SELECT td.products_PDID, SUM(td.TransferQty) AS OnTransfer,
            SUM(CASE WHEN th.TransferStat = 2 THEN td.ReceivedQty ELSE 0 END) AS Arrived
            FROM transferheader th INNER JOIN transferdetails td ON td.TransferHeader_THID = th.THID
            WHERE th.CustomerOrderID = ? AND th.TransferStat <> 3 GROUP BY td.products_PDID;");
        $stmt->execute([(int)$order['COID']]);
        $pool = [];
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row)
        {
            $pool[(int)$row['products_PDID']] = ['on' => (float)$row['OnTransfer'], 'arrived' => (float)$row['Arrived']];
        }

        $moving = false;
        $allArrived = true;
        $warehouseLines = 0;
        $catalogLeft = false;
        $hasCustom = false;
        foreach($lines as &$line)
        {
            $line['products_PDID'] = $line['products_PDID'] === null ? null : (int)$line['products_PDID'];
            $line['qty'] = self::number($line['Qty']);
            $line['on_transfer'] = null;
            $line['arrived'] = null;
            if($line['LineSource'] !== 'WAREHOUSE')
            {
                continue;
            }//given from the showroom's stock: nothing to follow
            $warehouseLines++;
            if($line['products_PDID'] === null)
            {
                $hasCustom = true;
                $on = (float)$line['CustomSent'];
                $arrived = $on;
            }//custom-made: sent by hand
            else
            {
                $pdid = $line['products_PDID'];
                $p = isset($pool[$pdid]) ? $pool[$pdid] : ['on' => 0, 'arrived' => 0];
                $on = min((float)$line['Qty'], $p['on']);
                $arrived = min((float)$line['Qty'], $p['arrived']);
                $pool[$pdid] = ['on' => $p['on'] - $on, 'arrived' => $p['arrived'] - $arrived];
                $catalogLeft = $catalogLeft || $on < (float)$line['Qty'];
            }//a catalog product: from the order's transfers, filled in line order
            $line['on_transfer'] = self::number($on);
            $line['arrived'] = self::number($arrived);
            $moving = $moving || $on > 0;
            $allArrived = $allArrived && $arrived >= (float)$line['Qty'];
        }
        unset($line);

        return ['lines' => $lines, 'moving' => $moving, 'arrived' => $warehouseLines > 0 && $allArrived,
            'catalog_left' => $catalogLeft, 'has_custom' => $hasCustom];
    }//progress

    private static function statusOf(array $order, array $progress)
    {
        switch((int)$order['OrderStat'])
        {
            case self::REQUESTED: return 'Requested';
            case self::REJECTED: return 'Rejected';
            case self::CANCELLED: return 'Cancelled';
            case self::HANDED_OVER: return 'Handed over';
        }
        if($progress['arrived'])
        {
            return 'Arrived';
        }
        return $progress['moving'] ? 'In transit' : 'Accepted';
    }//status of

    private function validate($shop_id, array $data)
    {
        $text = function($key) use ($data) {
            return (isset($data[$key]) && is_scalar($data[$key])) ? trim((string)$data[$key]) : '';
        };

        $suppliers = array_column($this->supplierShops($shop_id), 'ShopName', 'SHID');
        $supplier_id = (int)$text('supplier_id');
        if(!isset($suppliers[$supplier_id]))
        {
            throw new CustomerOrderRefused(422, 'Choose the shop to order from.');
        }
        $clean = [
            'supplier_id' => $supplier_id,
            'cust_name' => $text('cust_name'),
            'cust_phone' => $text('cust_phone'),
            'cust_address' => $text('cust_address') === '' ? null : $text('cust_address'),
            'notes' => $text('notes') === '' ? null : $text('notes'),
            'needed_by' => null,
            'advance' => '0.00',
            'lines' => [],
        ];
        if($clean['cust_name'] === '' || $clean['cust_phone'] === '')
        {
            throw new CustomerOrderRefused(422, "Enter the customer's name and phone number.");
        }
        if(strlen($clean['cust_name']) > 120 || strlen($clean['cust_phone']) > 25 || strlen((string)$clean['cust_address']) > 255 || strlen((string)$clean['notes']) > 2000)
        {
            throw new CustomerOrderRefused(422, 'The customer details are too long.');
        }
        if($text('needed_by') !== '')
        {
            $date = DateTime::createFromFormat('!Y-m-d', $text('needed_by'));
            if(!$date || $date->format('Y-m-d') !== $text('needed_by'))
            {
                throw new CustomerOrderRefused(422, 'Enter a valid "needed by" date.');
            }
            $clean['needed_by'] = $text('needed_by');
        }
        if($text('advance') !== '')
        {
            if(!preg_match('/^\d+(\.\d{1,2})?$/', $text('advance')) || (float)$text('advance') > 99999999.99)
            {
                throw new CustomerOrderRefused(422, 'Enter a valid advance amount.');
            }
            $clean['advance'] = number_format((float)$text('advance'), 2, '.', '');
        }

        $lines = (isset($data['lines']) && is_array($data['lines'])) ? array_values($data['lines']) : [];
        if(count($lines) > self::MAX_LINES)
        {
            throw new CustomerOrderRefused(422, 'An order can have at most ' . self::MAX_LINES . ' lines.');
        }
        $warehouseLines = 0;
        foreach($lines as $i => $line)
        {
            $n = 'Line ' . ($i + 1) . ': ';
            $line = is_array($line) ? $line : [];
            $get = function($key) use ($line) {
                return (isset($line[$key]) && is_scalar($line[$key])) ? trim((string)$line[$key]) : '';
            };
            $source = $get('source') === 'GIVEN' ? 'GIVEN' : ($get('source') === 'WAREHOUSE' ? 'WAREHOUSE' : null);
            if($source === null)
            {
                throw new CustomerOrderRefused(422, $n . 'unknown kind of line.');
            }
            $qty = self::quantity($get('qty'), false);
            if($qty === null)
            {
                throw new CustomerOrderRefused(422, $n . 'enter a quantity above 0.');
            }
            $product_id = (int)$get('product_id');
            $description = $get('description');
            if($product_id > 0)
            {
                $owner = $source === 'WAREHOUSE' ? $supplier_id : (int)$shop_id;
                $stmt = $this->connect()->prepare("SELECT ItemName FROM products WHERE PDID = ? AND shop_SHID = ? AND ItemType = 'P' AND ProductStat = 1;");
                $stmt->execute([$product_id, $owner]);
                $name = $stmt->fetchColumn();
                if($name === false)
                {
                    throw new CustomerOrderRefused(422, $n . ($source === 'WAREHOUSE'
                        ? 'that product is not in the catalog of ' . $suppliers[$supplier_id] . '.' : 'that product is not one of this shop\'s products.'));
                }
                $description = $name;
            }
            elseif($description === '')
            {
                throw new CustomerOrderRefused(422, $n . ($source === 'WAREHOUSE' ? 'describe the custom-made item.' : 'describe the item that was given.'));
            }
            if(strlen($description) > 255 || strlen($get('notes')) > 255 || strlen($get('invoice_no')) > 60)
            {
                throw new CustomerOrderRefused(422, $n . 'the text is too long.');
            }
            $warehouseLines += $source === 'WAREHOUSE' ? 1 : 0;
            $clean['lines'][] = [
                'source' => $source,
                'product_id' => $product_id > 0 ? $product_id : null,
                'description' => $description,
                'qty' => $qty,
                'notes' => $get('notes') === '' ? null : $get('notes'),
                'invoice_no' => ($source === 'GIVEN' && $get('invoice_no') !== '') ? $get('invoice_no') : null,
            ];
        }//each line
        if($warehouseLines === 0)
        {
            throw new CustomerOrderRefused(422, 'Add at least one item for ' . $suppliers[$supplier_id] . ' to supply.');
        }
        return $clean;
    }//validate

    private function insertLines($order_id, array $lines)
    {
        $stmt = $this->connect()->prepare("INSERT INTO customerorderlines (customerorders_COID, LineSource, products_PDID, Description, Qty, Notes,
            InvoiceNo, SortOrder) VALUES (?, ?, ?, ?, ?, ?, ?, ?);");
        foreach($lines as $i => $line)
        {
            $stmt->execute([(int)$order_id, $line['source'], $line['product_id'], $line['description'], $line['qty'], $line['notes'], $line['invoice_no'], $i + 1]);
        }
    }//insert lines

    //the order as this shop may see it, or 404. With $lock the order row (only that row - not
    //the joined shop and user rows) is locked for the rest of the transaction
    private function find($id, $shop_id, $lock)
    {
        if($lock)
        {
            $this->connect()->prepare("SELECT COID FROM customerorders WHERE COID = ? FOR UPDATE;")->execute([(int)$id]);
        }
        $stmt = $this->connect()->prepare(self::ORDER_SELECT . " WHERE co.COID = ?;");
        $stmt->execute([(int)$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if($order === false || ((int)$order['shop_SHID'] !== (int)$shop_id && (int)$order['SupplierShopID'] !== (int)$shop_id))
        {
            throw new CustomerOrderRefused(404, 'This order is not in this shop.');
        }
        return $order;
    }//find

    private function lock($id, $shop_id)
    {
        return $this->find($id, $shop_id, true);
    }//lock

    private function requireSide(array $order, $shop_id, $side)
    {
        if($side === 'ours' && (int)$order['shop_SHID'] !== (int)$shop_id)
        {
            throw new CustomerOrderRefused(404, 'This order was not placed by this shop.');
        }
        if($side === 'incoming' && (int)$order['SupplierShopID'] !== (int)$shop_id)
        {
            throw new CustomerOrderRefused(404, 'This order was not sent to this shop.');
        }
    }//require side

    private function requireRight($user_id, $shop_id, array $rights, $what)
    {
        if(!$this->can($user_id, $shop_id, $rights))
        {
            throw new CustomerOrderRefused(403, 'You do not have the right to ' . $what . ' in this shop.');
        }
    }//require right

    private function requireOpen(array $order)
    {
        if(!in_array((int)$order['OrderStat'], [self::REQUESTED, self::ACCEPTED], true))
        {
            throw new CustomerOrderRefused(409, 'This order is already closed.');
        }
    }//require open

    private function decide($id, $stat, $user_id, $reason = null)
    {
        $this->connect()->prepare("UPDATE customerorders SET OrderStat = ?, RejectReason = ?, DecidedBy = ?, DecidedAt = ? WHERE COID = ?;")
            ->execute([$stat, $reason, (int)$user_id, date('Y-m-d H:i:s'), (int)$id]);
    }//decide

    private function close($id, $stat, $user_id)
    {
        $this->connect()->prepare("UPDATE customerorders SET OrderStat = ?, ClosedBy = ?, ClosedAt = ? WHERE COID = ?;")
            ->execute([$stat, (int)$user_id, date('Y-m-d H:i:s'), (int)$id]);
    }//close

    private function nextOrderNo($shop_id)
    {
        $stmt = $this->connect()->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING(OrderNo, 4) AS UNSIGNED)), 0) + 1 FROM customerorders WHERE shop_SHID = ?;");
        $stmt->execute([(int)$shop_id]);
        return 'CO_' . str_pad((string)$stmt->fetchColumn(), 6, '0', STR_PAD_LEFT);
    }//next order no

    //the transfer number the Transfer page would give (Controller/transferController.php)
    private function nextTransferNo($shop_id)
    {
        $stmt = $this->connect()->prepare("SELECT MAX(THID) FROM transferheader WHERE shop_SHID = ?;");
        $stmt->execute([(int)$shop_id]);
        return 'GT_' . str_pad((string)((int)$stmt->fetchColumn() + 1), 6, '0', STR_PAD_LEFT);
    }//next transfer no

    private function transaction(callable $work)
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

    //a quantity > 0 (or >= 0 when $zero) with up to 3 decimals, or null
    private static function quantity($value, $zero)
    {
        $value = trim((string)$value);
        if(!preg_match('/^\d+(\.\d{1,3})?$/', $value) || (float)$value > 99999 || (!$zero && (float)$value <= 0))
        {
            return null;
        }
        return (float)$value;
    }//quantity

    public static function number($number)
    {
        $number = round((float)$number, 3);
        return $number == (int)$number ? (int)$number : $number;
    }//number

    public static function qtyText($number)
    {
        return rtrim(rtrim(number_format((float)$number, 3, '.', ''), '0'), '.');
    }//qty text
}//CustomerOrders
```

(`find()` joins the shop names so `SupplierName` is on the locked order used by `createTransfer`'s message. `ORDER_SELECT` is a class constant used before its declaration - fine in PHP.)

- [ ] **Step 3: Run** the whole suite: OK (all `CustomerOrdersTest` tests).
- [ ] **Step 4: Commit and push** `feat(orders): accept, reject, cancel, transfer from the order, custom items, hand-over`.

---

### Task 5: The endpoint and the end-to-end flow

**Files:**
- Create: `Controller/CustomerOrderController.php`, `tests/e2e/customer_orders_e2e.php`
- Modify: `tests/e2e/lib.php` (`E2EStock::up()` grants Customer Orders and module 2 to Store Keeper; `down()` also removes e2e customer orders and what a transfer Verify created in the e2e shops: categories, subcategories, variations)

**Interfaces:** `POST Controller/CustomerOrderController.php` with `csrf_token` and `action`:

| action | fields | answer |
|---|---|---|
| `create` | `order` (JSON of the `$data` shape) | `{ok, message, id, order_no}` |
| `update` | `id`, `order` | `{ok, message, id}` |
| `cancel`, `accept` | `id` | `{ok, message}` |
| `reject` | `id`, `reason` | `{ok, message}` |
| `create_transfer` | `id` | `{ok, message, transfer_id, transfer_no}` |
| `custom_sent` | `id`, `line_id`, `qty`, `note` | `{ok, message}` |
| `handover` | `id`, `invoice_no` | `{ok, message}` |
| `products` | `scope` (`supplier` + `supplier_id`, or `own`), `term` | `{ok, products: [{id, barcode, name, in_stock}]}` |

- [ ] **Step 1: The controller:**

```php
<?php
//Customer orders endpoint (Public/customer-order.php, Assets/jquery/customer_order.js) - see
//docs/superpowers/specs/2026-09-22-customer-orders-design.md. POST only, from a signed-in user
//still allowed in the session's shop, with the page's CSRF token; the rules live in
//Model/customer_order_class.php. Every answer is JSON: {ok, message, ...}.
include "../Includes/includes.php";
require_once "../Includes/csrf.php";
require_once "../Includes/customer_orders.php";

header('Content-Type: application/json; charset=utf-8');

function order_respond($status, array $body)
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}//respond

if(!isset($_SESSION['user_id'], $_SESSION['shop_id']) || !(new ShopAccess())->canAccessShop($_SESSION['user_id'], $_SESSION['shop_id']))
{
    order_respond(403, ['ok' => false, 'message' => 'Please sign in to the shop again.']);
}//not signed in to this shop
if($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null))
{
    order_respond(400, ['ok' => false, 'message' => 'Your session expired. Please reload the page and try again.']);
}//not a post from our page

$orders = new CustomerOrders();
$user_id = (int)$_SESSION['user_id'];
$shop_id = (int)$_SESSION['shop_id'];
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$field = function($key) {
    return (isset($_POST[$key]) && is_string($_POST[$key])) ? $_POST[$key] : '';
};
$order = function() use ($field) {
    $data = json_decode($field('order'), true);
    return is_array($data) ? $data : [];
};

try
{
    switch(isset($_POST['action']) ? $_POST['action'] : '')
    {
        case 'create':
            $placed = $orders->create($shop_id, $user_id, $order());
            order_respond(200, ['ok' => true, 'message' => 'Order ' . $placed['order_no'] . ' sent.', 'id' => $placed['id'], 'order_no' => $placed['order_no']]);
        case 'update':
            $orders->update($id, $shop_id, $user_id, $order());
            order_respond(200, ['ok' => true, 'message' => 'Order saved.', 'id' => $id]);
        case 'cancel':
            $orders->cancel($id, $shop_id, $user_id);
            order_respond(200, ['ok' => true, 'message' => 'Order cancelled.']);
        case 'accept':
            $orders->accept($id, $shop_id, $user_id);
            order_respond(200, ['ok' => true, 'message' => 'Order accepted.']);
        case 'reject':
            $orders->reject($id, $shop_id, $user_id, $field('reason'));
            order_respond(200, ['ok' => true, 'message' => 'Order rejected.']);
        case 'create_transfer':
            $made = $orders->createTransfer($id, $shop_id, $user_id);
            order_respond(200, ['ok' => true, 'message' => $made['message'], 'transfer_id' => $made['transfer_id'], 'transfer_no' => $made['transfer_no']]);
        case 'custom_sent':
            $orders->markCustomSent($id, $shop_id, $user_id, (int)$field('line_id'), $field('qty'), $field('note'));
            order_respond(200, ['ok' => true, 'message' => 'Marked sent.']);
        case 'handover':
            $orders->handover($id, $shop_id, $user_id, $field('invoice_no'));
            order_respond(200, ['ok' => true, 'message' => 'Order handed over.']);
        case 'products':
            if(!$orders->can($user_id, $shop_id, CustomerOrders::CHANGE))
            {
                order_respond(403, ['ok' => false, 'message' => 'You do not have the right to place customer orders in this shop.']);
            }
            $from = $shop_id;
            if($field('scope') === 'supplier')
            {
                $from = (int)$field('supplier_id');
                if(!in_array($from, array_column($orders->supplierShops($shop_id), 'SHID'), true))
                {
                    order_respond(422, ['ok' => false, 'message' => 'Choose the shop to order from.']);
                }
            }//the supplier's catalog, else this shop's own products
            order_respond(200, ['ok' => true, 'products' => $orders->searchProducts($from, $field('term'))]);
        default:
            order_respond(400, ['ok' => false, 'message' => 'Unknown action.']);
    }
}
catch(CustomerOrderRefused $e)
{
    order_respond($e->status, ['ok' => false, 'message' => $e->getMessage()]);
}
catch(Throwable $e)
{
    error_log('CustomerOrderController: ' . $e->getMessage());
    order_respond(500, ['ok' => false, 'message' => 'Something went wrong. Nothing was saved.']);
}//catch
```

- [ ] **Step 2: E2E fixtures** in `tests/e2e/lib.php`:
  - `E2EStock::up()`, after the GRN/transfer rights:
    ```php
    //Store Keeper may use Customer Orders (every right) and sees the Orders menu
    $this->pdo->prepare("UPDATE userroleaccess SET is_view = 1, is_create = 1, is_edit = 1, is_verify = 1
        WHERE UserRolls_URID = ? AND SysFeatures_SFID = (SELECT SFID FROM sysfeatures WHERE FeatureName = 'Customer Orders' LIMIT 1)")
        ->execute([$this->fx->roles['keeper']]);
    $this->pdo->prepare("INSERT INTO usermoduleaccess (SysModules_SMID, UserRoles_URID) VALUES (2, ?)")->execute([$this->fx->roles['keeper']]);
    ```
  - `E2EStock::down()`, first lines:
    ```php
    $this->pdo->exec("DELETE FROM customerorderlines WHERE customerorders_COID IN (SELECT COID FROM customerorders WHERE shop_SHID IN ($shops) OR SupplierShopID IN ($shops))");
    $this->pdo->exec("DELETE FROM customerorders WHERE shop_SHID IN ($shops) OR SupplierShopID IN ($shops)");
    ```
    and after the products delete:
    ```php
    $this->pdo->exec("DELETE FROM variations WHERE products_PDID IN (SELECT PDID FROM products WHERE shop_SHID IN ($shops))");  //before the products delete
    $this->pdo->exec("DELETE FROM subcategories WHERE categories_CTID IN (SELECT CTID FROM categories WHERE shop_SHID IN ($shops))");
    $this->pdo->exec("DELETE FROM categories WHERE shop_SHID IN ($shops)");
    ```

- [ ] **Step 3: `tests/e2e/customer_orders_e2e.php`** (same header and `try/finally` shape as `scan_upload_e2e.php`):

```php
function order(E2EBrowser $browser, $action, array $fields, $token)
{
    return $browser->post('Controller/CustomerOrderController.php', $fields + ['action' => $action, 'csrf_token' => $token]);
}

echo "The showroom places an order\n";
$bob = new E2EBrowser($base);
signIn($bob, 'e2e_bob', $pw);
shopLogin($bob, $S, 'e2e_bob', $pw);
$bob->get('Public/customer-order.php?new=1');
$bobToken = $bob->csrf();
check('the new order form offers the warehouse and both kinds of line', $bob->has('e2e Warehouse') && $bob->has('Already given from our stock') && $bobToken !== '', $bob);
checkClean('the new order form', $bob);

order($bob, 'products', ['scope' => 'supplier', 'supplier_id' => $W, 'term' => 'e2e Bed'], $bobToken);
check('the product search shows the warehouse catalog with its stock', $bob->json('products')[0]['name'] === 'e2e Bed' && $bob->json('products')[0]['in_stock'] === 10, $bob);

$data = ['supplier_id' => $W, 'cust_name' => 'e2e Customer', 'cust_phone' => '0770000000', 'lines' => [
    ['source' => 'GIVEN', 'description' => 'e2e Bedsheet', 'qty' => '1', 'invoice_no' => 'INV-E2E-1'],
    ['source' => 'WAREHOUSE', 'product_id' => $stock->products['bed'], 'qty' => '2', 'notes' => 'King size'],
]];
$bob->post('Controller/CustomerOrderController.php', ['action' => 'create', 'order' => json_encode($data)]);
check('without the CSRF token nothing is placed (400)', $bob->status === 400, $bob);
order($bob, 'create', ['order' => json_encode($data)], $bobToken);
$orderId = (int)$bob->json('id');
check('the order is placed', $bob->status === 200 && $orderId > 0 && $bob->json('order_no') === 'CO_000001', $bob);
$bob->get('Public/customer-order.php?id=' . $orderId);
check('the order page shows the given bedsheet as not to be sent', $bob->has('Already given - do not send') && $bob->has('e2e Bedsheet') && $bob->has('Requested'), $bob);
checkClean('the order page', $bob);

echo "The warehouse fulfils it\n";
$alice = new E2EBrowser($base);
signIn($alice, 'e2e_alice', $pw);
shopLogin($alice, $W, 'e2e_alice', $pw);
$alice->get('Public/customer-orders.php?tab=incoming');
$aliceToken = $alice->csrf();
check('the warehouse sees the order as incoming, with the menu badge', $alice->has('CO_000001') && $alice->has('Customer Orders <span class="badge'), $alice);
checkClean('the order list', $alice);
order($alice, 'cancel', ['id' => $orderId], $aliceToken);
check('the warehouse cannot cancel the showroom\'s order (404)', $alice->status === 404, $alice);
order($alice, 'accept', ['id' => $orderId], $aliceToken);
check('the warehouse accepts it', $alice->status === 200, $alice);
order($alice, 'create_transfer', ['id' => $orderId], $aliceToken);
$transfer = (int)$alice->json('transfer_id');
$lines = $pdo->query("SELECT products_PDID, TransferQty FROM transferdetails WHERE TransferHeader_THID = $transfer")->fetchAll(PDO::FETCH_ASSOC);
check('the transfer carries only the bed, oldest batch first', $alice->status === 200 && count($lines) === 1
    && (int)$lines[0]['products_PDID'] === $stock->products['bed'] && $lines[0]['TransferQty'] === '2.000', $alice);
order($bob, 'cancel', ['id' => $orderId], $bobToken);
check('once items are on the way the showroom cannot cancel (409)', $bob->status === 409, $bob);

$alice->post('Controller/transferController.php', ['btn_verify_transfer' => '1', 'hide_transferheader_id' => $transfer]);
$bob->get('Public/customer-order.php?id=' . $orderId);
check('after Verify the order shows Arrived', $bob->has('>Arrived<'), $bob);

echo "The showroom hands it over\n";
order($bob, 'handover', ['id' => $orderId, 'invoice_no' => 'INV-E2E-2'], $bobToken);
$bob->get('Public/customer-order.php?id=' . $orderId);
check('the order is handed over with its invoice number', $bob->has('Handed over') && $bob->has('INV-E2E-2'), $bob);

echo "Refusals\n";
$a2 = new E2EBrowser($base);
signIn($a2, 'e2e_alice', $pw);
shopLogin($a2, $S, 'e2e_alice', $pw);        //Cashier in the showroom: no Customer Orders right
$a2->get('Public/home.php');
order($a2, 'create', ['order' => json_encode($data)], $a2->csrf());
check('a role without the right cannot place orders (403)', $a2->status === 403, $a2);
$a2->get('Public/customer-orders.php');
check('and does not get the pages', $a2->isOn('Public/home.php'), $a2);
```

Run: `C:/xampp/php/php.exe tests/e2e/customer_orders_e2e.php` - fails until Task 6 adds the pages (the controller checks pass).

- [ ] **Step 4: Commit and push** `feat(orders): customer orders endpoint, with end-to-end checks`.

---

### Task 6: The pages, the menu and the browser check

**Files:**
- Create: `Public/customer-orders.php`, `Public/customer-order.php`, `View/menu-customer-orders.php`, `Assets/jquery/customer_order.js`, `tests/ui/customer_orders_ui.mjs`
- Modify: `View/sidebar.php` (include the menu item in both *Sales & Orders* blocks)

**Interfaces:** Consumes the Task 5 endpoint. The pages expose `#co_csrf_token` (hidden input) and, on the order page, `data-order-id` on `#co_order`.

- [ ] **Step 1: Menu item** - `View/menu-customer-orders.php`:

```php
<?php
//Sales & Orders -> Customer Orders, with a badge counting the orders waiting for this shop's
//answer (docs/superpowers/specs/2026-09-22-customer-orders-design.md). Included by
//View/sidebar.php in both of its Sales & Orders menus.
require_once __DIR__ . '/../Includes/customer_orders.php';
$menuOrders = new CustomerOrders();
if($menuOrders->featureId() > 0 && isset($_SESSION['user_id']) && $menuOrders->can($_SESSION['user_id'], $shop_id, CustomerOrders::VIEW))
{
    $menuWaiting = $menuOrders->incomingCount($shop_id);
    ?>
    <li class="sidebar-item">
      <a href="../Public/customer-orders.php" class="sidebar-link sidebar-link2">
        <div class="round-16 d-flex align-items-center justify-content-center">
          <i class="ti ti-circle"></i>
        </div>
        <span class="hide-menu">Customer Orders<?php if($menuWaiting > 0) { ?> <span class="badge bg-danger rounded-pill ms-1"><?= (int)$menuWaiting ?></span><?php } ?></span>
      </a>
    </li>
    <?php
}//may see customer orders
```

`View/sidebar.php`: `<?php include __DIR__ . '/menu-customer-orders.php'; ?>` right after the *Sales Return* item of the admin block and after the *Returns* item of the role block.

- [ ] **Step 2: The list page** - `Public/customer-orders.php`: `includes.php` + `authcheck.php` + `csrf.php` + `Includes/customer_orders.php`; redirect to `home.php` unless `can(VIEW)`; `$ours = listFor('ours')`, `$incoming = listFor('incoming')`; tab from `?tab=incoming`, defaulting to *Incoming* when the shop has incoming orders but none of its own. Layout: the standard `page-wrapper` / sidebar / `body-wrapper` / header / `container-fluid`; title *Customer Orders*; **New Order** (`btn btn-primary`, link to `customer-order.php?new=1`) when `can(PLACE)` and the shop has suppliers; Bootstrap `nav-tabs` *Our orders (n)* / *Incoming (n)*; a status filter `<select id="co_status_filter">` (All + the seven statuses) filtering rows client-side by `data-status`; table columns Order No | Customer | Phone | From / To (the other shop) | Needed by | Items (summary) | Status (badge) | Placed (date, by). Each row links to `customer-order.php?id=`. Empty state: "No orders yet." Status badge classes: Requested `bg-primary`, Accepted `text-bg-info`, In transit `text-bg-warning`, Arrived `text-bg-success`, Handed over `text-bg-secondary`, Rejected / Cancelled `bg-danger` - one PHP helper `co_badge($status)` in the page. Scripts at the end: bootstrap bundle, sidebarmenu, app.min, simplebar, `customer_order.js?v=20260922` (no second jQuery, no dashboard.js).

- [ ] **Step 3: The order page** - `Public/customer-order.php`:
  - Modes: `?new=1` (needs `can(PLACE)`), `?id=N&edit=1` (needs `can_edit`), `?id=N` (view). A refusal from `get()` → back to `customer-orders.php`.
  - **Form** (new / edit), inside `<div id="co_form" data-mode="new|edit" data-order-id="N">`:
    - card *Customer order* - `Order from` (`<select id="co_supplier">` of `supplierShops`), `Needed by` (date), `Customer name`, `Phone`, `Address`, `Advance paid`, `Notes`.
    - card *Already given from our stock* - table `#co_given` (Product (select2 over this shop, optional) | or Description | Qty | Invoice No | remove) and `+ Add item already given`.
    - card *From the warehouse* (heading updated with the chosen shop's name) - table `#co_warehouse` (Product (select2 over the supplier's catalog, showing *in stock*) or, for custom rows, a Description input | Qty | Note (size, colour...) | remove) and `+ Add product` / `+ Add custom-made item`.
    - an error line `#co_error` (`alert alert-danger`, hidden) and **Send to warehouse** / **Save** (`btn btn-primary`) + **Cancel** link.
    - edit mode renders the existing lines as rows (products preselected as `<option selected>`).
  - **View**, inside `<div id="co_order" data-order-id="N">`:
    - header card: order no., status badge, *From {showroom} to {supplier}*, customer name / phone / address, needed by, advance, notes; history list (Placed by X on T; Accepted/Rejected by Y on T with the reason; Cancelled / Handed over by Z on T with the invoice no.).
    - buttons from the `can_*` flags: Edit (link), Cancel order, Accept, Reject, Create transfer, Handed over.
    - card *Already given from {showroom}'s stock* with the note **Already given - do not send**: Description | Qty | Invoice No.
    - card *To be supplied by {supplier}*: Item (barcode · name, or *Custom-made:* text) | Note | Ordered | On transfer | Arrived (with a check when complete) | for custom lines when `can_mark_custom`: **Mark sent** button (`data-line-id`, `data-qty`, `data-sent`).
    - card *Transfers*: Transfer No | Date | Status | *Open* link to `transfer-details.php?id=` (shown when the transfer involves this shop).
    - one Bootstrap modal `#co_action_modal` (title, message, an optional textarea/input, OK) used for Reject (reason), Handed over (invoice no.), Mark sent (quantity + note), Cancel and Create transfer (confirmation).

- [ ] **Step 4: The script** - `Assets/jquery/customer_order.js`:
  - `post(action, fields)` → `$.ajax` POST to `../Controller/CustomerOrderController.php` with `csrf_token` from `#co_csrf_token`, `dataType: 'json'`; errors show `responseJSON.message`.
  - **Form:** row templates for the three kinds of row; `productSelect($select, scope)` sets up select2 with `ajax: {url, type: 'POST', dataType: 'json', delay: 250, data: {action: 'products', scope, supplier_id, term, csrf_token}, processResults: {results: products → {id, text: barcode + ' - ' + name + (scope === 'supplier' ? ' (in stock: ' + in_stock + ')' : '')}}}`, `minimumInputLength: 1`, `width: '100%'`; changing the supplier clears the warehouse product rows after a confirm; **Save** collects `{supplier_id, cust_name, cust_phone, cust_address, needed_by, advance, notes, lines: [...]}` (GIVEN rows: product_id or description, qty, invoice_no; WAREHOUSE rows: product_id or description, qty, notes) and posts `create` or `update` with `order: JSON.stringify(data)`; success → `location = 'customer-order.php?id=' + id`; failure → `#co_error`.
  - **View:** each button opens `#co_action_modal` configured for its action, then posts it; success → `location.reload()`; *Create transfer* success offers to open the new transfer (`confirm` → `transfer-details.php?id=`).
  - **List:** the status filter hides rows whose `data-status` differs.

- [ ] **Step 5: Run the E2E** - `C:/xampp/php/php.exe tests/e2e/customer_orders_e2e.php` → `All checks passed.`; also `scan_upload_e2e.php` and `shop_login_e2e.php`.

- [ ] **Step 6: Browser check** - `tests/ui/customer_orders_ui.mjs` (the same harness as `scan_upload_ui.mjs`: fixtures up/down, headless Chrome, dialogs auto-accepted, JS exceptions from `customer_order.js` must be none):
  1. `e2e_bob` signs in to the e2e Showroom; *Customer Orders* is in the menu; **New Order**.
  2. Fill customer name and phone; **+ Add item already given** → description `e2e Bedsheet`, qty 1, invoice `INV-UI-1`; **+ Add product** → open the select2, insert `E2EBED`, wait, press Enter → the option shows *(in stock: 10)*; qty 2, note `King size`; **+ Add custom-made item** → `Headboard, walnut`, qty 1.
  3. **Send to warehouse** → lands on the order page: status *Requested*, the given table shows `e2e Bedsheet` with *Already given - do not send*, the warehouse table shows `e2e Bed` and the custom line. Screenshot.
  4. Sign in as `e2e_alice` to the e2e Warehouse; the menu badge shows 1; *Incoming* lists the order; open it; **Accept** → *Accepted*; **Create transfer** (confirm, then decline opening it) → *In transit*, the Transfers card lists one transfer; **Mark sent** on the headboard (qty 1) → its Arrived shows 1. Screenshot.

Run: `node tests/ui/customer_orders_ui.mjs <screens>` → `All UI checks passed.`

- [ ] **Step 7: Commit and push** `feat(orders): customer order pages, menu and browser check`.

---

### Task 7: Documentation, regression crawl, hand-off

- [ ] `db/CUSTOMER_ORDERS_MODULE.md`: install (`php db/customer_orders_install.php` before the code), granting the right (role editor → *Orders* → *Customer Orders*; showroom roles: create/edit/view; warehouse roles: verify/view; the *Orders* module ticked), the workflow on both sides, statuses, what counts as arrived, billing in POS, tests.
- [ ] `README.md`: feature bullet and test commands.
- [ ] Spec: *Found while building* section.
- [ ] Regression crawl (every menu page, admin + Owner) on the commit before Task 1 vs now; restore the database; compare checksums.
- [ ] Memory `server-deployment.md`: the redeploy also runs `php db/customer_orders_install.php` first, then grant the right.
- [ ] Commit and push `docs: customer orders module guide and tests`.

---

## Self-review

- **Spec coverage:** §4 data → Task 1; §5 progress/status → Task 4 (`progress`, `statusOf`); §6 actions/rules → Tasks 3-4, endpoint Task 5; §7 screens → Task 6; §8 testing → Tasks 1-6, crawl Task 7; §9 delivery → every task + Task 7.
- **Placeholders:** Task 3 Step 2 points at the Task 4 class (written once, in full) instead of repeating ~500 lines; the pages in Task 6 are specified field by field (markup follows `Public/AssignUsersToShops.php`'s layout). No TBDs.
- **Type consistency:** `create/update/get/listFor/incomingCount/accept/reject/cancel/createTransfer/markCustomSent/handover/searchProducts/supplierShops/can/featureId` are used with the same names and arguments in the tests, the controller and the pages; line fields `on_transfer`, `arrived`, `qty` and flags `can_*` match between the model, the tests and the view.
