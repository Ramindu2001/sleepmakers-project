# POS to Warehouse Fulfilment Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A cashier bills a whole customer order in POS — shelf stock, warehouse stock and custom-made items together — and the warehouse receives a queue of what it owes that customer, dispatching only what it has scanned.

**Architecture:** The unused `customerorders` / `customerorderlines` tables become POS-created fulfilment orders. POS skips stock for warehouse lines; the goods leave warehouse stock when a dispatch is scanned and completed. Dispatch scanning is a third `ScanDocument` subclass beside GRN and transfer.

**Tech Stack:** PHP 8.2, MariaDB, PDO through `Dbh`, PHPUnit 11 (`tools/phpunit.phar`), jQuery, Bootstrap. No new dependencies.

**Spec:** `docs/superpowers/specs/2026-09-24-pos-warehouse-fulfilment-design.md`

## Global Constraints

- **Every SQL statement written in this work uses prepared statements with bound parameters.** The legacy files being modified interpolate strings; new code never does, and a line being edited is converted where it is touched.
- **Additive schema only.** No column is dropped or renamed. The migration is idempotent: a second run reports `[skip]` for every step.
- `.gitignore` holds `db/*` with one `!` line per shipped file. **The four new `db/` files are invisible to git until their `!` lines are added** — this caught out both previous modules.
- Rights are checked with `ShopAccess::hasFeatureRight($user_id, $shop_id, 'Customer Orders', $right)`; the feature is always looked up **by name**, never by id. Super admin (`UserType = 1`) has every right.
- Refusal codes: **403** rights, **404** not this shop's order, **409** the order or unit moved on, **422** bad input. Thrown as `CustomerOrderRefused` / `ScanRefused`.
- Money is never copied onto the order. Net, paid and balance come from `invoiceheader` (`NetAmount`, `CustPayment`, `CustBalance`) at read time.
- Quantities use `CustomerOrders::quantity()` rules: up to 3 decimals, `> 0`, `<= 99999`.
- Run tests with `C:/xampp/php/php.exe tools/phpunit.phar`. **Never run two phpunit processes at once** — they share `sleepmakers_test` and drop each other's tables.
- Every task ends with its own commit **and push to `main`**, per the customer's instruction.

## Review Focus

Five things the spec implies, that the obvious tests miss, that would bite a real user. Each has a test in the task that owns the code.

1. **The invoice saves but the order does not.** A validation slip or a dead connection after `invoiceheader` is written leaves a paid sale with nothing telling the warehouse to send the bed. Order creation must be inside the sale's transaction, or refuse the sale. Only a real checkout proves this, so it is an end-to-end check. → Task 10, step 2.
2. **The sale is returned after the order exists.** The customer changes their mind next morning; the invoice goes through Sales Return, and the warehouse still has a Pending bed on its queue. The job sheet must show the invoice is cancelled and dispatch must refuse it. → Task 8.
3. **Two dispatchers scan the same sticker at the same moment.** Both previews look clean; whoever completes second must be refused by the unit's stored state, not by the preview. → Task 9.
4. **The same bed is on the cart twice** — one from the shelf, one from the warehouse, because the shop had 1 of 2. The given part must stay given and the warehouse part must not swallow it. → Task 4.
5. **A held bill is recalled after the warehouse product was deactivated or sold out.** The flags survive the hold, so the sale must be re-checked at recall rather than trusted. → Task 5.

---

## File Structure

| File | Responsibility |
|---|---|
| `db/warehouse_fulfilment_migration.php` | the schema change, idempotent |
| `db/warehouse_fulfilment_install.php` | CLI installer |
| `db/warehouse_fulfilment.sql` | the same change by hand |
| `db/WAREHOUSE_FULFILMENT_MODULE.md` | the guide for the customer |
| `Model/warehouse_order_class.php` | `WarehouseOrder`: create from a sale, read, status, line actions |
| `Model/order_dispatch_class.php` | `OrderDispatch`: open, cancel, complete (stock + units), delivered |
| `Model/scan_dispatch_class.php` | `DispatchScan extends ScanDocument`: scanned text → checked lines |
| `Controller/WarehouseOrderController.php` | JSON actions for the order and dispatch buttons |
| `Public/warehouse-orders.php` | the warehouse queue |
| `Public/customer-orders.php` | the shop's own orders (rewritten list) |
| `Public/customer-order.php` | the job sheet, both sides |
| `View/modals/dispatch-scan.php` | the scan dialog |
| `Assets/jquery/warehouse_order.js` | the job sheet buttons |
| `Assets/jquery/dispatch_scan.js` | the scan dialog |
| `tests/WarehouseFulfilmentMigrationTest.php`, `tests/WarehouseOrderTest.php`, `tests/DispatchScanTest.php`, `tests/e2e/warehouse_fulfilment_e2e.php` | |

Modified: `Controller/guiPosController.php` (three checkout paths), `Assets/jquery/guipos.js`, `Public/gui-pos.php`, `AJAX/guiPos/getproducts.php`, `AJAX/guiPos/getbarcodevalue.php`, `Controller/ScanUploadController.php`, `View/sidebar.php`, `View/menu-customer-orders.php`, `tests/DatabaseTestCase.php`, `.gitignore`.

---

## Task 1: The schema

**Files:**
- Create: `db/warehouse_fulfilment_migration.php`, `db/warehouse_fulfilment_install.php`, `db/warehouse_fulfilment.sql`
- Modify: `tests/DatabaseTestCase.php:25-37`, `.gitignore`
- Test: `tests/WarehouseFulfilmentMigrationTest.php`

**Interfaces:**
- Consumes: `UnitBarcodesMigration` as the template (`db/unit_barcodes_migration.php` — `step()`, `say()`, `hasTable()`, `hasColumn()` are copied verbatim).
- Produces: `class WarehouseFulfilmentMigration { __construct(PDO $pdo); run(): array }`, where each report row is `['status' => 'ok'|'skip', 'text' => string]`.

- [ ] **Step 1: Write the failing test**

`tests/WarehouseFulfilmentMigrationTest.php`:

```php
<?php
//db/warehouse_fulfilment_migration.php: the tables POS-created warehouse orders need.
final class WarehouseFulfilmentMigrationTest extends DatabaseTestCase
{
    protected $migrate = false;

    private function migrate()
    {
        (new ShopAccessMigration($this->pdo))->run();
        (new ScanUploadMigration($this->pdo))->run();
        (new CustomerOrdersMigration($this->pdo))->run();
        (new ShopPermissionsMigration($this->pdo))->run();
        (new UnitBarcodesMigration($this->pdo))->run();
        return (new WarehouseFulfilmentMigration($this->pdo))->run();
    }

    private function columns($table)
    {
        return $this->pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . $table . "'")->fetchAll(PDO::FETCH_COLUMN);
    }

    public function test_the_order_carries_its_invoice_and_where_it_goes()
    {
        $this->migrate();

        foreach (['InvoiceHeader_IHID', 'DeliverTo', 'DeliveryAddress', 'DeliveryPhone',
            'DeliveryNote', 'customers_CTID'] as $column) {
            $this->assertContains($column, $this->columns('customerorders'), $column . ' is missing');
        }
    }

    public function test_a_line_carries_its_own_state_and_both_product_ids()
    {
        $this->migrate();

        foreach (['LineStat', 'DispatchedQty', 'DeliveredQty', 'SupplierProductID',
            'UnitPrice', 'LineTotal', 'CancelReason'] as $column) {
            $this->assertContains($column, $this->columns('customerorderlines'), $column . ' is missing');
        }
    }

    public function test_dispatches_and_their_scanned_lines_exist()
    {
        $this->migrate();

        $this->assertNotEmpty($this->columns('orderdispatches'));
        $this->assertNotEmpty($this->columns('orderdispatchlines'));
    }

    public function test_a_unit_remembers_the_dispatch_it_left_on()
    {
        $this->migrate();

        foreach (['orderdispatches_DSID', 'DispatchedAt', 'DispatchedBy'] as $column) {
            $this->assertContains($column, $this->columns('productunits'), $column . ' is missing');
        }
    }

    public function test_the_same_sticker_cannot_be_scanned_twice_into_one_dispatch()
    {
        $this->migrate();
        $row = ['orderdispatches_DSID' => 1, 'customerorderlines_COLID' => 1, 'products_PDID' => 1,
            'Qty' => 1, 'UnitBarcode' => 'COO0000126090001', 'ScannedAt' => date('Y-m-d H:i:s'), 'ScannedBy' => 1];
        $this->insert('orderdispatchlines', $row);

        $this->expectException(PDOException::class);
        $this->insert('orderdispatchlines', $row);
    }

    public function test_a_product_barcode_may_be_scanned_many_times_into_one_dispatch()
    {
        $this->migrate();
        $row = ['orderdispatches_DSID' => 1, 'customerorderlines_COLID' => 1, 'products_PDID' => 1,
            'Qty' => 1, 'UnitBarcode' => null, 'ScannedAt' => date('Y-m-d H:i:s'), 'ScannedBy' => 1];

        $this->insert('orderdispatchlines', $row);
        $this->insert('orderdispatchlines', $row);

        $this->assertSame(2, (int) $this->pdo->query('SELECT COUNT(*) FROM orderdispatchlines')->fetchColumn());
    }

    public function test_old_order_statuses_are_carried_over()
    {
        (new ShopAccessMigration($this->pdo))->run();
        (new ScanUploadMigration($this->pdo))->run();
        (new CustomerOrdersMigration($this->pdo))->run();
        $company = $this->createCompany();
        $shop = $this->createShop($company);
        foreach ([1, 2, 3, 4, 5] as $stat) {
            $this->insert('customerorders', ['OrderNo' => 'CO_00000' . $stat, 'shop_SHID' => $shop,
                'SupplierShopID' => $shop, 'CustName' => 'x', 'CustPhone' => '1', 'OrderStat' => $stat,
                'user_USID' => 1, 'CreatedAt' => date('Y-m-d H:i:s')]);
        }
        (new ShopPermissionsMigration($this->pdo))->run();
        (new UnitBarcodesMigration($this->pdo))->run();
        (new WarehouseFulfilmentMigration($this->pdo))->run();

        //1 Pending, 2 Preparing stay; rejected and cancelled become Cancelled; handed over becomes Completed
        $this->assertSame(['1', '2', '6', '6', '5'],
            $this->pdo->query('SELECT OrderStat FROM customerorders ORDER BY COID')->fetchAll(PDO::FETCH_COLUMN));
    }

    public function test_running_it_twice_changes_nothing()
    {
        $this->migrate();
        $second = (new WarehouseFulfilmentMigration($this->pdo))->run();

        foreach ($second as $line) {
            $this->assertSame('skip', $line['status'], $line['text']);
        }
    }
}
```

- [ ] **Step 2: Run it and watch it fail**

Run: `C:/xampp/php/php.exe tools/phpunit.phar --filter WarehouseFulfilmentMigrationTest`
Expected: FAIL — `Class "WarehouseFulfilmentMigration" not found`.

- [ ] **Step 3: Write the migration**

`db/warehouse_fulfilment_migration.php`. Copy `step()`, `say()`, `hasTable()`, `hasColumn()` verbatim from `db/unit_barcodes_migration.php:84-114`. `run()` applies, in order:

```php
const ORDER_COLUMNS = array(
    'InvoiceHeader_IHID' => "ALTER TABLE customerorders ADD COLUMN InvoiceHeader_IHID INT(11) DEFAULT NULL
        COMMENT 'the POS invoice this order was billed on' AFTER SupplierShopID;",
    'customers_CTID' => "ALTER TABLE customerorders ADD COLUMN customers_CTID INT(11) DEFAULT NULL
        COMMENT 'the saved customer' AFTER InvoiceHeader_IHID;",
    'DeliverTo' => "ALTER TABLE customerorders ADD COLUMN DeliverTo TINYINT(4) NOT NULL DEFAULT 1
        COMMENT '1 the customer address, 2 collect at the shop' AFTER CustAddress;",
    'DeliveryAddress' => "ALTER TABLE customerorders ADD COLUMN DeliveryAddress VARCHAR(255) DEFAULT NULL AFTER DeliverTo;",
    'DeliveryPhone' => "ALTER TABLE customerorders ADD COLUMN DeliveryPhone VARCHAR(20) DEFAULT NULL AFTER DeliveryAddress;",
    'DeliveryNote' => "ALTER TABLE customerorders ADD COLUMN DeliveryNote TEXT DEFAULT NULL AFTER DeliveryPhone;",
);

const LINE_COLUMNS = array(
    'LineStat' => "ALTER TABLE customerorderlines ADD COLUMN LineStat TINYINT(4) NOT NULL DEFAULT 2
        COMMENT '1 given at shop, 2 pending, 3 ready, 4 dispatched, 5 delivered, 6 cancelled' AFTER LineSource;",
    'SupplierProductID' => "ALTER TABLE customerorderlines ADD COLUMN SupplierProductID INT(11) DEFAULT NULL
        COMMENT 'the warehouse product; products_PDID is the shop copy the invoice carries' AFTER products_PDID;",
    'DispatchedQty' => "ALTER TABLE customerorderlines ADD COLUMN DispatchedQty DECIMAL(10,3) NOT NULL DEFAULT 0 AFTER Qty;",
    'DeliveredQty' => "ALTER TABLE customerorderlines ADD COLUMN DeliveredQty DECIMAL(10,3) NOT NULL DEFAULT 0 AFTER DispatchedQty;",
    'UnitPrice' => "ALTER TABLE customerorderlines ADD COLUMN UnitPrice DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER DeliveredQty;",
    'LineTotal' => "ALTER TABLE customerorderlines ADD COLUMN LineTotal DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER UnitPrice;",
    'CancelReason' => "ALTER TABLE customerorderlines ADD COLUMN CancelReason VARCHAR(255) DEFAULT NULL AFTER Notes;",
);

const UNIT_COLUMNS = array(
    'orderdispatches_DSID' => "ALTER TABLE productunits ADD COLUMN orderdispatches_DSID INT(11) DEFAULT NULL
        COMMENT 'the dispatch this unit left on' AFTER ReceivedBy;",
    'DispatchedAt' => "ALTER TABLE productunits ADD COLUMN DispatchedAt DATETIME DEFAULT NULL AFTER orderdispatches_DSID;",
    'DispatchedBy' => "ALTER TABLE productunits ADD COLUMN DispatchedBy INT(11) DEFAULT NULL AFTER DispatchedAt;",
);
```

Then the two tables:

```sql
CREATE TABLE orderdispatches (
    DSID INT(11) NOT NULL AUTO_INCREMENT,
    DispatchNo VARCHAR(20) NOT NULL COMMENT 'DS_000001, per shop',
    customerorders_COID INT(11) NOT NULL,
    shop_SHID INT(11) NOT NULL COMMENT 'the warehouse sending it',
    DispatchStat TINYINT(4) NOT NULL DEFAULT 1 COMMENT '1 open, 2 sent, 3 cancelled',
    DeliverTo TINYINT(4) NOT NULL DEFAULT 1,
    DeliveryNote TEXT DEFAULT NULL COMMENT 'vehicle, driver, who took it',
    CreatedBy INT(11) NOT NULL, CreatedAt DATETIME NOT NULL,
    SentBy INT(11) DEFAULT NULL, SentAt DATETIME DEFAULT NULL,
    DeliveredBy INT(11) DEFAULT NULL, DeliveredAt DATETIME DEFAULT NULL,
    PRIMARY KEY (DSID),
    UNIQUE KEY uq_orderdispatches_no (shop_SHID, DispatchNo),
    KEY ix_orderdispatches_order (customerorders_COID, DispatchStat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE orderdispatchlines (
    DDID BIGINT(20) NOT NULL AUTO_INCREMENT,
    orderdispatches_DSID INT(11) NOT NULL,
    customerorderlines_COLID INT(11) NOT NULL,
    products_PDID INT(11) NOT NULL COMMENT 'the warehouse product actually issued',
    Qty DECIMAL(10,3) NOT NULL DEFAULT 1,
    UnitBarcode VARCHAR(64) DEFAULT NULL COMMENT 'NULL when a product barcode was scanned',
    productunits_PUID BIGINT(20) DEFAULT NULL,
    InventoryID INT(11) DEFAULT NULL COMMENT 'filled when the dispatch is completed',
    Batch_ID VARCHAR(45) DEFAULT NULL,
    ScannedAt DATETIME NOT NULL, ScannedBy INT(11) NOT NULL,
    PRIMARY KEY (DDID),
    UNIQUE KEY uq_orderdispatchlines_unit (orderdispatches_DSID, UnitBarcode),
    KEY ix_orderdispatchlines_line (customerorderlines_COLID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

And the status remap, guarded so it runs once (it is a no-op when no row is left in the old
numbering, which is how `skip` is reported):

```php
$this->step('customerorders statuses renumbered', $this->hasOldStatuses(),
    "UPDATE customerorders SET OrderStat = CASE OrderStat WHEN 3 THEN 6 WHEN 4 THEN 6 WHEN 5 THEN 5 ELSE OrderStat END
     WHERE OrderStat IN (3, 4, 5);");
```

`hasOldStatuses()` returns true when `customerorderlines.LineStat` did not exist before this
run — the migration records that in a local flag set while adding `LINE_COLUMNS`, so a second
run reports `skip` and never touches statuses again.

Existing `GIVEN` lines get their state: run once, right after `LineStat` is added —
`UPDATE customerorderlines SET LineStat = 1 WHERE LineSource = 'GIVEN';`

- [ ] **Step 4: Write the installer and the SQL file**

`db/warehouse_fulfilment_install.php` — copy `db/unit_barcodes_install.php` and swap the class
name, the title and the guide path. `db/warehouse_fulfilment.sql` — the same statements with
`IF NOT EXISTS` where MariaDB allows it, for a manual install.

- [ ] **Step 5: Run the migration in the test base class**

`tests/DatabaseTestCase.php`, after `(new UnitBarcodesMigration($this->pdo))->run();`:

```php
            (new WarehouseFulfilmentMigration($this->pdo))->run();
```

- [ ] **Step 6: Make the new files visible to git**

`.gitignore`, beside the existing `!db/unit_barcodes_*` lines:

```
!db/warehouse_fulfilment_migration.php
!db/warehouse_fulfilment_install.php
!db/warehouse_fulfilment.sql
!db/WAREHOUSE_FULFILMENT_MODULE.md
```

Verify with `git status --short db/` — all four must appear as untracked.

- [ ] **Step 7: Run the tests**

Run: `C:/xampp/php/php.exe tools/phpunit.phar`
Expected: the eight new tests pass and the whole suite stays green (167 tests before this work).

- [ ] **Step 8: Commit and push**

```bash
git add db/warehouse_fulfilment_migration.php db/warehouse_fulfilment_install.php db/warehouse_fulfilment.sql tests/WarehouseFulfilmentMigrationTest.php tests/DatabaseTestCase.php .gitignore
git commit -m "feat(fulfilment): storage for POS-created warehouse orders and their dispatches"
git push origin main
```

---

## Task 2: Reading an order

**Files:**
- Create: `Model/warehouse_order_class.php`
- Test: `tests/WarehouseOrderTest.php`

**Interfaces:**
- Consumes: `CustomerOrderRefused($status, $message)` from `Model/customer_order_refused_class.php`; `ShopAccess::hasFeatureRight()`.
- Produces:
  - `const PENDING = 1, PREPARING = 2, READY = 3, DISPATCHED = 4, COMPLETED = 5, CANCELLED = 6;`
  - `const LINE_GIVEN = 1, LINE_PENDING = 2, LINE_READY = 3, LINE_DISPATCHED = 4, LINE_DELIVERED = 5, LINE_CANCELLED = 6;`
  - `get($id, $shop_id, $user_id): array` — `['order' => [...], 'lines' => [...], 'dispatches' => [...], 'money' => ['net','paid','balance']]`
  - `listFor($shop_id, $user_id, $side, $status = null): array` where `$side` is `'ours'` or `'incoming'`
  - `pendingCount($shop_id): int`

- [ ] **Step 1: Write the failing test**

`tests/WarehouseOrderTest.php` — the fixture plus the read tests:

```php
<?php
//Model/warehouse_order_class.php: the order a POS sale leaves for the warehouse.
final class WarehouseOrderTest extends DatabaseTestCase
{
    private WarehouseOrder $orders;
    private int $shop;
    private int $warehouse;
    private int $cashier;
    private int $picker;
    private int $bedHere;      //the shop's copy of the bed
    private int $bedThere;     //the warehouse's bed
    private int $sheetHere;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orders = new WarehouseOrder();
        $company = $this->createCompany();
        $this->warehouse = $this->createShop($company, ['ShopName' => 'Warehouse']);
        $this->shop = $this->createShop($company, ['ShopName' => 'Valentino Italy']);
        $role = $this->createRole('Staff');
        $this->cashier = $this->createUser('cashier', 'x', $role);
        $this->picker = $this->createUser('picker', 'x', $role);
        $this->assign($this->cashier, $this->shop, $role);
        $this->assign($this->picker, $this->warehouse, $role);
        $feature = (int) $this->pdo->query("SELECT SFID FROM sysfeatures WHERE FeatureName = 'Customer Orders'")->fetchColumn();
        $this->grant($role, $feature, ['is_view', 'is_create', 'is_edit', 'is_verify'], $this->shop);
        $this->grant($role, $feature, ['is_view', 'is_create', 'is_edit', 'is_verify'], $this->warehouse);
        $this->bedThere = $this->createProduct($this->warehouse, 'COO00001', 'Cooler Bed');
        $this->bedHere = $this->createProduct($this->shop, 'COO00001', 'Cooler Bed');
        $this->sheetHere = $this->createProduct($this->shop, 'LIN00001', 'Bedsheet');
        $this->addStock($this->bedThere, $this->warehouse, 5, 'B1', 100, 150);
    }

    //an invoice as POS writes it, and the order that came from it
    private function order(array $lines = null, array $overrides = [])
    {
        $invoice = $this->insert('invoiceheader', ['InvoiceNo' => 'INV_0001', 'EffectiveDate' => date('Y-m-d'),
            'InvItemCount' => 2, 'GrossAmount' => 1000, 'NetAmount' => 1000, 'CustPayment' => 400,
            'CustBalance' => 600, 'InvStat' => 1, 'user_USID' => $this->cashier, 'shop_SHID' => $this->shop]);
        $lines = $lines ?? [
            ['source' => 'GIVEN', 'product_id' => $this->sheetHere, 'supplier_product_id' => null,
                'description' => 'Bedsheet', 'qty' => 2, 'notes' => '', 'unit_price' => 100],
            ['source' => 'WAREHOUSE', 'product_id' => $this->bedHere, 'supplier_product_id' => $this->bedThere,
                'description' => 'Cooler Bed', 'qty' => 1, 'notes' => 'Firm', 'unit_price' => 800],
        ];
        return $this->orders->createFromSale($this->shop, $this->cashier, $overrides + [
            'invoice_id' => $invoice, 'supplier_shop_id' => $this->warehouse, 'customer_id' => null,
            'cust_name' => 'Nimal', 'cust_phone' => '0771234567', 'cust_address' => '12 Galle Rd',
            'deliver_to' => 1, 'delivery_address' => '12 Galle Rd', 'delivery_phone' => '0771234567',
            'delivery_note' => '', 'needed_by' => null, 'notes' => '', 'lines' => $lines,
        ]);
    }

    public function test_the_order_shows_the_money_from_its_invoice()
    {
        $id = $this->order()['order_id'];

        $money = $this->orders->get($id, $this->warehouse, $this->picker)['money'];

        $this->assertSame([1000.0, 400.0, 600.0], [$money['net'], $money['paid'], $money['balance']]);
    }

    public function test_a_later_payment_shows_without_touching_the_order()
    {
        $view = $this->orders->get($this->order()['order_id'], $this->warehouse, $this->picker);
        $this->pdo->prepare('UPDATE invoiceheader SET CustPayment = 1000, CustBalance = 0 WHERE IHID = ?')
            ->execute([$view['order']['InvoiceHeader_IHID']]);

        $money = $this->orders->get($view['order']['COID'], $this->warehouse, $this->picker)['money'];

        $this->assertSame(0.0, $money['balance']);
    }

    public function test_both_shops_see_the_order_and_nobody_else_does()
    {
        $id = $this->order()['order_id'];
        $other = $this->createShop($this->createCompany(), ['ShopName' => 'Someone else']);

        $this->assertSame('CO_000001', $this->orders->get($id, $this->shop, $this->cashier)['order']['OrderNo']);
        $this->assertSame('CO_000001', $this->orders->get($id, $this->warehouse, $this->picker)['order']['OrderNo']);

        $this->expectException(CustomerOrderRefused::class);
        $this->orders->get($id, $other, $this->picker);
    }

    public function test_the_warehouse_queue_holds_orders_sent_to_it()
    {
        $this->order();

        $incoming = $this->orders->listFor($this->warehouse, $this->picker, 'incoming');
        $ours = $this->orders->listFor($this->shop, $this->cashier, 'ours');

        $this->assertCount(1, $incoming);
        $this->assertCount(1, $ours);
        $this->assertSame(1, $this->orders->pendingCount($this->warehouse));
    }
}
```

- [ ] **Step 2: Run it and watch it fail**

Run: `C:/xampp/php/php.exe tools/phpunit.phar --filter WarehouseOrderTest`
Expected: FAIL — `Class "WarehouseOrder" not found`.

- [ ] **Step 3: Write the class, with just enough of `createFromSale` to store a sale**

Task 3 hardens the validation; this step writes the reading side plus a `createFromSale` that
inserts the header (numbered by `nextOrderNo()`, `OrderStat = PENDING`) and its lines
(`GIVEN` → `LINE_GIVEN`, `WAREHOUSE` → `LINE_PENDING`), so the tests above have an order to read.

`ORDER_SELECT` joins the invoice so money comes with the
row; `get()` reuses the locking `find()` shape of `Model/customer_order_class.php:623-642`:

```php
const ORDER_SELECT = "SELECT co.*, s.ShopName AS ShopName, sup.ShopName AS SupplierName,
    ih.InvoiceNo, ih.NetAmount, ih.CustPayment, ih.CustBalance, ih.InvStat
    FROM customerorders co
    INNER JOIN shop s ON s.SHID = co.shop_SHID
    INNER JOIN shop sup ON sup.SHID = co.SupplierShopID
    LEFT JOIN invoiceheader ih ON ih.IHID = co.InvoiceHeader_IHID";
```

`get()` returns the order, its lines ordered by `SortOrder`, the dispatches (`orderdispatches`
with a scanned count), and `money` as three floats read from the joined invoice — `0.0` each
when the order has no invoice. `listFor()` filters on `shop_SHID` (ours) or `SupplierShopID`
(incoming) with an optional `OrderStat`, newest first. `pendingCount()` counts
`OrderStat IN (PENDING, PREPARING)` for the supplier shop.

- [ ] **Step 4: Run the tests**

Run: `C:/xampp/php/php.exe tools/phpunit.phar --filter WarehouseOrderTest`
Expected: PASS, all four.

- [ ] **Step 5: Commit and push**

```bash
git add Model/warehouse_order_class.php tests/WarehouseOrderTest.php
git commit -m "feat(fulfilment): read a warehouse order with its live invoice balance"
git push origin main
```

---

## Task 3: Creating an order from a sale

**Files:**
- Modify: `Model/warehouse_order_class.php`
- Test: `tests/WarehouseOrderTest.php`

**Interfaces:**
- Produces: `createFromSale($shop_id, $user_id, array $sale): array` returning
  `['order_id' => int, 'order_no' => string]`. `$sale` keys: `invoice_id`, `supplier_shop_id`,
  `customer_id`, `cust_name`, `cust_phone`, `cust_address`, `deliver_to`, `delivery_address`,
  `delivery_phone`, `delivery_note`, `needed_by`, `notes`, `lines`. Each line:
  `source` (`GIVEN`|`WAREHOUSE`), `product_id`, `supplier_product_id`, `description`, `qty`,
  `notes`, `unit_price`.

- [ ] **Step 1: Write the failing tests**

Added to `tests/WarehouseOrderTest.php`:

```php
    public function test_a_sale_leaves_the_warehouse_lines_pending_and_the_given_ones_given()
    {
        $view = $this->orders->get($this->order()['order_id'], $this->warehouse, $this->picker);

        $this->assertSame([WarehouseOrder::LINE_GIVEN, WarehouseOrder::LINE_PENDING],
            array_map('intval', array_column($view['lines'], 'LineStat')));
        $this->assertSame(WarehouseOrder::PENDING, (int) $view['order']['OrderStat']);
    }

    public function test_the_order_is_numbered_per_shop()
    {
        $this->assertSame('CO_000001', $this->order()['order_no']);
        $this->assertSame('CO_000002', $this->order()['order_no']);
    }

    public function test_a_line_keeps_the_shop_copy_and_the_warehouse_product_apart()
    {
        $lines = $this->orders->get($this->order()['order_id'], $this->warehouse, $this->picker)['lines'];

        $this->assertSame([$this->bedHere, $this->bedThere],
            [(int) $lines[1]['products_PDID'], (int) $lines[1]['SupplierProductID']]);
    }

    //Review Focus 4: the shop had 1 of 2 beds, so the bed is on the cart twice
    public function test_the_same_product_given_and_ordered_stays_two_lines()
    {
        $id = $this->order([
            ['source' => 'GIVEN', 'product_id' => $this->bedHere, 'supplier_product_id' => null,
                'description' => 'Cooler Bed', 'qty' => 1, 'notes' => '', 'unit_price' => 800],
            ['source' => 'WAREHOUSE', 'product_id' => $this->bedHere, 'supplier_product_id' => $this->bedThere,
                'description' => 'Cooler Bed', 'qty' => 1, 'notes' => '', 'unit_price' => 800],
        ])['order_id'];

        $lines = $this->orders->get($id, $this->warehouse, $this->picker)['lines'];

        $this->assertCount(2, $lines);
        $this->assertSame([1, 1], array_map(function ($l) { return (int) $l['Qty']; }, $lines));
        $this->assertSame([WarehouseOrder::LINE_GIVEN, WarehouseOrder::LINE_PENDING],
            array_map('intval', array_column($lines, 'LineStat')));
    }

    public function test_a_custom_made_line_has_no_product()
    {
        $id = $this->order([
            ['source' => 'WAREHOUSE', 'product_id' => null, 'supplier_product_id' => null,
                'description' => 'Headboard, walnut, 6ft', 'qty' => 1, 'notes' => 'Buttoned', 'unit_price' => 45000],
        ])['order_id'];

        $line = $this->orders->get($id, $this->warehouse, $this->picker)['lines'][0];

        $this->assertNull($line['products_PDID']);
        $this->assertSame('Headboard, walnut, 6ft', $line['Description']);
    }

    public function test_a_sale_with_nothing_for_the_warehouse_makes_no_order()
    {
        $this->expectException(CustomerOrderRefused::class);
        $this->order([['source' => 'GIVEN', 'product_id' => $this->sheetHere, 'supplier_product_id' => null,
            'description' => 'Bedsheet', 'qty' => 1, 'notes' => '', 'unit_price' => 100]]);
    }

    public function test_the_customer_name_and_phone_are_required()
    {
        foreach (['cust_name' => '', 'cust_phone' => ''] as $field => $value) {
            try {
                $this->order(null, [$field => $value]);
                $this->fail('expected a refusal for ' . $field);
            } catch (CustomerOrderRefused $e) {
                $this->assertSame(422, $e->status);
            }
        }
    }

    public function test_a_line_for_a_product_the_supplier_does_not_own_is_refused()
    {
        $this->expectException(CustomerOrderRefused::class);
        $this->order([['source' => 'WAREHOUSE', 'product_id' => $this->bedHere,
            'supplier_product_id' => $this->sheetHere, 'description' => 'Bedsheet', 'qty' => 1,
            'notes' => '', 'unit_price' => 100]]);
    }

    public function test_a_quantity_that_is_not_a_sensible_number_is_refused()
    {
        foreach ([0, -1, 100000] as $qty) {
            try {
                $this->order([['source' => 'WAREHOUSE', 'product_id' => $this->bedHere,
                    'supplier_product_id' => $this->bedThere, 'description' => 'Cooler Bed',
                    'qty' => $qty, 'notes' => '', 'unit_price' => 800]]);
                $this->fail('expected a refusal for quantity ' . $qty);
            } catch (CustomerOrderRefused $e) {
                $this->assertSame(422, $e->status);
            }
        }
        $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM customerorders')->fetchColumn());
    }
```

- [ ] **Step 2: Run them and watch them fail**

Run: `C:/xampp/php/php.exe tools/phpunit.phar --filter WarehouseOrderTest`
Expected: FAIL on the new tests.

- [ ] **Step 3: Implement `createFromSale`**

One transaction. Validate first, write nothing until everything passes:

- `cust_name` and `cust_phone` trimmed, non-empty, ≤ 100 and ≤ 20 characters — else 422.
- `supplier_shop_id` is another **active** shop of the same company — else 422.
- at least one `WAREHOUSE` line — else 422 (`'This sale has nothing for the warehouse.'`).
- each line's `qty` through `CustomerOrders::quantity($value, false)` — null means 422.
- a `WAREHOUSE` line with a `supplier_product_id` must be that supplier's own active product
  with `ItemType = 'P'`; a line with none is custom-made and needs a non-empty `description`.
- `deliver_to` is 1 or 2; when 1, `delivery_address` is required.

Then insert the header (`OrderStat = PENDING`, `OrderNo` from `nextOrderNo($shop_id)` —
copy `Model/customer_order_class.php:692-697`) and the lines, `GIVEN` lines with
`LineStat = LINE_GIVEN` and `WAREHOUSE` lines with `LINE_PENDING`, `SortOrder` in the order
given. Lines are **never merged**: the cart's split is the customer's reality.

- [ ] **Step 4: Run the tests**

Run: `C:/xampp/php/php.exe tools/phpunit.phar --filter WarehouseOrderTest`
Expected: PASS, all fourteen.

- [ ] **Step 5: Commit and push**

```bash
git add Model/warehouse_order_class.php tests/WarehouseOrderTest.php
git commit -m "feat(fulfilment): turn a POS sale into a warehouse order, line by line"
git push origin main
```

---

## Task 4: Status and the warehouse's line actions

**Files:**
- Modify: `Model/warehouse_order_class.php`
- Test: `tests/WarehouseOrderTest.php`

**Interfaces:**
- Produces: `startPreparing($id, $shop_id, $user_id)`, `markReady($id, $shop_id, $user_id, $line_id = null)`,
  `cannotSupply($id, $shop_id, $user_id, $line_id, $reason)`, `cancel($id, $shop_id, $user_id)`,
  and `refreshStatus($id)` (public, so `OrderDispatch` calls it inside its own transaction).

- [ ] **Step 1: Write the failing tests**

```php
    public function test_starting_and_readying_move_the_order_along()
    {
        $id = $this->order()['order_id'];

        $this->orders->startPreparing($id, $this->warehouse, $this->picker);
        $this->assertSame(WarehouseOrder::PREPARING, $this->statusOf($id));

        $this->orders->markReady($id, $this->warehouse, $this->picker);
        $this->assertSame(WarehouseOrder::READY, $this->statusOf($id));
    }

    public function test_a_half_ready_order_is_still_preparing()
    {
        $id = $this->order([
            ['source' => 'WAREHOUSE', 'product_id' => $this->bedHere, 'supplier_product_id' => $this->bedThere,
                'description' => 'Cooler Bed', 'qty' => 1, 'notes' => '', 'unit_price' => 800],
            ['source' => 'WAREHOUSE', 'product_id' => null, 'supplier_product_id' => null,
                'description' => 'Headboard', 'qty' => 1, 'notes' => '', 'unit_price' => 45000],
        ])['order_id'];
        $lines = $this->orders->get($id, $this->warehouse, $this->picker)['lines'];

        $this->orders->markReady($id, $this->warehouse, $this->picker, (int) $lines[0]['COLID']);

        $this->assertSame(WarehouseOrder::PREPARING, $this->statusOf($id));
    }

    public function test_a_line_the_warehouse_cannot_supply_is_closed_with_its_reason()
    {
        $id = $this->order()['order_id'];
        $line = (int) $this->orders->get($id, $this->warehouse, $this->picker)['lines'][1]['COLID'];

        $this->orders->cannotSupply($id, $this->warehouse, $this->picker, $line, 'Discontinued by the mill');

        $view = $this->orders->get($id, $this->warehouse, $this->picker);
        $this->assertSame(WarehouseOrder::LINE_CANCELLED, (int) $view['lines'][1]['LineStat']);
        $this->assertSame('Discontinued by the mill', $view['lines'][1]['CancelReason']);
    }

    //the bedsheet was handed over, so the customer was served even though the bed fell through
    public function test_an_order_whose_last_warehouse_line_is_cancelled_completes()
    {
        $id = $this->order()['order_id'];
        $line = (int) $this->orders->get($id, $this->warehouse, $this->picker)['lines'][1]['COLID'];

        $this->orders->cannotSupply($id, $this->warehouse, $this->picker, $line, 'None left');

        $this->assertSame(WarehouseOrder::COMPLETED, $this->statusOf($id));
    }

    public function test_an_order_with_nothing_given_and_nothing_supplied_is_cancelled()
    {
        $id = $this->order([['source' => 'WAREHOUSE', 'product_id' => $this->bedHere,
            'supplier_product_id' => $this->bedThere, 'description' => 'Cooler Bed', 'qty' => 1,
            'notes' => '', 'unit_price' => 800]])['order_id'];
        $line = (int) $this->orders->get($id, $this->warehouse, $this->picker)['lines'][0]['COLID'];

        $this->orders->cannotSupply($id, $this->warehouse, $this->picker, $line, 'None left');

        $this->assertSame(WarehouseOrder::CANCELLED, $this->statusOf($id));
    }

    public function test_a_reason_is_required_to_refuse_a_line()
    {
        $id = $this->order()['order_id'];
        $line = (int) $this->orders->get($id, $this->warehouse, $this->picker)['lines'][1]['COLID'];

        $this->expectException(CustomerOrderRefused::class);
        $this->orders->cannotSupply($id, $this->warehouse, $this->picker, $line, '   ');
    }

    public function test_a_given_line_can_never_be_readied_or_refused()
    {
        $id = $this->order()['order_id'];
        $given = (int) $this->orders->get($id, $this->warehouse, $this->picker)['lines'][0]['COLID'];

        $this->expectException(CustomerOrderRefused::class);
        $this->orders->markReady($id, $this->warehouse, $this->picker, $given);
    }

    public function test_the_shop_cannot_prepare_and_the_warehouse_cannot_cancel_the_order()
    {
        $id = $this->order()['order_id'];

        try {
            $this->orders->startPreparing($id, $this->shop, $this->cashier);
            $this->fail('the shop prepared its own order');
        } catch (CustomerOrderRefused $e) {
            $this->assertSame(404, $e->status);
        }

        $this->expectException(CustomerOrderRefused::class);
        $this->orders->cancel($id, $this->warehouse, $this->picker);
    }

    public function test_someone_without_the_right_cannot_prepare()
    {
        $id = $this->order()['order_id'];
        $outsider = $this->createUser('outsider', 'x', $this->createRole('No rights'));
        $this->assign($outsider, $this->warehouse, (int) $this->pdo->query(
            "SELECT URID FROM userroles WHERE UserRoleName = 'No rights'")->fetchColumn());

        try {
            $this->orders->startPreparing($id, $this->warehouse, $outsider);
            $this->fail('expected a refusal');
        } catch (CustomerOrderRefused $e) {
            $this->assertSame(403, $e->status);
        }
    }

    private function statusOf($id)
    {
        $stmt = $this->pdo->prepare('SELECT OrderStat FROM customerorders WHERE COID = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn();
    }
```

- [ ] **Step 2: Run them and watch them fail**

Run: `C:/xampp/php/php.exe tools/phpunit.phar --filter WarehouseOrderTest`
Expected: FAIL — `Call to undefined method WarehouseOrder::startPreparing()`.

- [ ] **Step 3: Implement the actions and `refreshStatus`**

Each action: `transaction()` → `lock()` → `requireSide('incoming')` (or `'ours'` for `cancel`)
→ `requireRight()` → check the line belongs to this order and is not `LINE_GIVEN`, not
`LINE_CANCELLED`, not fully dispatched → write → `refreshStatus($id)`.

`refreshStatus` reads the lines once and decides, in this order:

```php
$open = 0;          //warehouse lines with a remainder still to pick
$ready = 0;         //of those, marked ready
$dispatched = 0;    //lines with DispatchedQty > 0
$delivered = 0;     //lines fully delivered
$given = 0;         //given at the shop, or delivered
$cancelled = 0;
```

- every warehouse line cancelled **and** no `GIVEN` line and nothing delivered → `CANCELLED`;
- nothing open and nothing dispatched-but-undelivered → `COMPLETED`;
- nothing open, something out → `DISPATCHED`;
- open lines and all of them ready → `READY`;
- the order was started, or something is ready or dispatched → `PREPARING`;
- otherwise → `PENDING`.

`startPreparing` sets `PREPARING` directly and stamps `DecidedBy`/`DecidedAt`; the "was
started" test above is `DecidedAt IS NOT NULL`.

- [ ] **Step 4: Run the tests**

Run: `C:/xampp/php/php.exe tools/phpunit.phar`
Expected: PASS, whole suite green.

- [ ] **Step 5: Commit and push**

```bash
git add Model/warehouse_order_class.php tests/WarehouseOrderTest.php
git commit -m "feat(fulfilment): order status follows its lines, and the warehouse can prepare or refuse one"
git push origin main
```

---

## Task 5: POS finds, bills and orders warehouse items

**Files:**
- Modify: `Model/warehouse_order_class.php`, `AJAX/guiPos/getproducts.php`, `AJAX/guiPos/getbarcodevalue.php`, `Controller/guiPosController.php` (lines 22, 550, 707), `Assets/jquery/guipos.js`, `Public/gui-pos.php`
- Test: `tests/WarehouseOrderTest.php`

**Interfaces:**
- Produces: `supplierShop($shop_id): ?array` (the company's other active shop that holds stock),
  `searchSupplier($shop_id, $term, $limit = 20): array` (rows with `PDID`, `Barcode`,
  `ItemName`, `ProdSellPrice`, `Available`), `shopCopyOf($supplier_product_id, $shop_id, $user_id): int`
  (delegates to `Transfer::getDestinationProductID`), `customItemProduct($shop_id, $user_id): int`.

- [ ] **Step 1: Write the failing tests**

```php
    public function test_the_shop_finds_the_warehouse_bed_it_does_not_stock()
    {
        $found = $this->orders->searchSupplier($this->shop, 'Cooler');

        $this->assertSame([$this->bedThere], array_map('intval', array_column($found, 'PDID')));
        $this->assertSame(5.0, (float) $found[0]['Available']);
    }

    public function test_a_warehouse_item_with_no_stock_is_still_offered_for_a_custom_wait()
    {
        $this->pdo->prepare('UPDATE inventory SET CurrentQty = 0 WHERE products_PDID = ?')->execute([$this->bedThere]);

        $this->assertSame(0.0, (float) $this->orders->searchSupplier($this->shop, 'Cooler')[0]['Available']);
    }

    public function test_an_inactive_or_service_warehouse_item_is_never_offered()
    {
        $this->createProduct($this->warehouse, 'SRV00001', 'Delivery charge', ['ItemType' => 'S']);
        $this->createProduct($this->warehouse, 'OLD00001', 'Cooler Bed old', ['ProductStat' => 0]);

        $names = array_column($this->orders->searchSupplier($this->shop, 'Cooler'), 'ItemName');

        $this->assertSame(['Cooler Bed'], $names);
    }

    public function test_billing_a_warehouse_item_gives_the_shop_its_own_copy()
    {
        $fresh = $this->createProduct($this->warehouse, 'NEW00001', 'Divan Base');

        $copy = $this->orders->shopCopyOf($fresh, $this->shop, $this->cashier);

        $stmt = $this->pdo->prepare('SELECT shop_SHID, Barcode FROM products WHERE PDID = ?');
        $stmt->execute([$copy]);
        $this->assertSame([$this->shop, 'NEW00001'], [(int) $stmt->fetch()['shop_SHID'], $stmt->fetch()['Barcode'] ?? 'NEW00001']);
        $this->assertNotSame($fresh, $copy);
    }

    public function test_the_copy_is_made_once_however_often_it_is_billed()
    {
        $fresh = $this->createProduct($this->warehouse, 'NEW00002', 'Divan Base 2');

        $first = $this->orders->shopCopyOf($fresh, $this->shop, $this->cashier);
        $second = $this->orders->shopCopyOf($fresh, $this->shop, $this->cashier);

        $this->assertSame($first, $second);
    }

    public function test_custom_items_all_bill_against_one_service_product()
    {
        $first = $this->orders->customItemProduct($this->shop, $this->cashier);
        $second = $this->orders->customItemProduct($this->shop, $this->cashier);

        $stmt = $this->pdo->prepare('SELECT ItemType, ItemName FROM products WHERE PDID = ?');
        $stmt->execute([$first]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertSame($first, $second);
        $this->assertSame(['S', 'Custom-made item'], [$row['ItemType'], $row['ItemName']]);
    }

    //Review Focus 5: a held bill is recalled after the warehouse product was deactivated
    public function test_a_warehouse_line_whose_product_was_deactivated_is_refused_at_checkout()
    {
        $this->pdo->prepare('UPDATE products SET ProductStat = 0 WHERE PDID = ?')->execute([$this->bedThere]);

        $this->expectException(CustomerOrderRefused::class);
        $this->order();
    }
```

- [ ] **Step 2: Run them and watch them fail**

Run: `C:/xampp/php/php.exe tools/phpunit.phar --filter WarehouseOrderTest`
Expected: FAIL — `Call to undefined method WarehouseOrder::searchSupplier()`.

- [ ] **Step 3: Implement the lookups**

```php
public function searchSupplier($shop_id, $term, $limit = 20)
{
    $supplier = $this->supplierShop($shop_id);
    if($supplier === null) { return []; }
    $stmt = $this->connect()->prepare("SELECT p.PDID, p.Barcode, p.ItemName, p.ProdSellPrice,
        COALESCE(SUM(i.CurrentQty), 0) AS Available
        FROM products p LEFT JOIN inventory i ON i.products_PDID = p.PDID AND i.shop_SHID = p.shop_SHID
        WHERE p.shop_SHID = ? AND p.ProductStat = 1 AND p.ItemType = 'P'
          AND (p.ItemName LIKE ? OR p.Barcode LIKE ?)
        GROUP BY p.PDID ORDER BY p.ItemName LIMIT " . (int)$limit . ";");
    $stmt->execute([(int)$supplier['SHID'], '%' . $term . '%', $term . '%']);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

`supplierShop()` returns the other active shop of this shop's company, preferring one that has
stock; `shopCopyOf()` calls `(new Transfer())->getDestinationProductID($supplier_product_id,
$shop_id, $user_id)`; `customItemProduct()` finds or creates the shop's `Custom-made item`
service product with `ItemType = 'S'`, `ProductStat = 1`, price 0.

Add the deactivated-product check to `createFromSale`'s validation (it already rejects a
product the supplier does not own; extend it to `ProductStat = 1`).

- [ ] **Step 4: Offer warehouse items in POS**

`AJAX/guiPos/getproducts.php` — in the **search** branch only (not the tile grid), when the
shop's own search returns nothing for a term, append `WarehouseOrder::searchSupplier()` rows
with `"from_warehouse" => 1`. `AJAX/guiPos/getbarcodevalue.php` — after the existing product
and unit lookups fail, try the supplier's barcode and return the same shape with
`from_warehouse`.

`Assets/jquery/guipos.js` — a `from_warehouse` row is added to the cart with
`data-warehouse="1"` and a **Warehouse — delivered** badge; when the shop has some but not all
of a quantity, add two rows (shop qty, warehouse remainder). `Public/gui-pos.php` — a
**Custom-made item** button that adds a row with `data-warehouse="1" data-custom="1"`, a typed
name and price, and the warehouse-order step (customer, destination, address, needed by,
per-line specs) shown before payment when any `data-warehouse` row is present. The form posts
`wh_line[]`, `wh_notes[]`, `wh_custom[]` alongside the existing `item_id[]` arrays, plus
`wh_customer_id`, `wh_deliver_to`, `wh_address`, `wh_phone`, `wh_needed_by`, `wh_note`.

- [ ] **Step 5: Wire the three checkout paths**

`Controller/guiPosController.php` at lines 22 (`?cash=1`), 550 (`?invoiceHold=1`) and 707
(`?btn_submit_invoice=1`). In the two that bill:

- in the **validation** loop, `if(!empty($_POST['wh_line'][$i])) { continue; }` before the
  `ItemType == "P"` stock check — a warehouse line is not expected to be in stock;
- in the **consumption** loop, the same guard before `getInventory()` / `selectInventory()`, so
  no shop batch is touched;
- after the invoice header and details are written, and **inside the same transaction**, call
  `WarehouseOrder::createFromSale()` with the lines flagged `wh_line`. A
  `CustomerOrderRefused` rolls the sale back and is reported as
  `$alert["Error"][] = $e->getMessage();` — the sale never saves half-done (Review Focus 1).

The hold path stores the `wh_*` fields with the held bill and returns them on recall, so a
recalled bill still becomes an order — and is re-validated then, not trusted (Review Focus 5).

- [ ] **Step 6: Run the tests**

Run: `C:/xampp/php/php.exe tools/phpunit.phar`
Expected: PASS, whole suite green.

- [ ] **Step 7: Commit and push**

```bash
git add Model/warehouse_order_class.php AJAX/guiPos/getproducts.php AJAX/guiPos/getbarcodevalue.php Controller/guiPosController.php Assets/jquery/guipos.js Public/gui-pos.php tests/WarehouseOrderTest.php
git commit -m "feat(pos): bill warehouse and custom-made items, and leave the warehouse an order"
git push origin main
```

---

## Task 6: The queue and the job sheet

**Files:**
- Create: `Public/warehouse-orders.php`, `Assets/jquery/warehouse_order.js`, `Controller/WarehouseOrderController.php`
- Modify: `Public/customer-orders.php`, `Public/customer-order.php`, `View/sidebar.php`, `View/menu-customer-orders.php`

**Interfaces:**
- Consumes: `WarehouseOrder::listFor()`, `get()`, `pendingCount()`, and the Task 4 actions.
- Produces: `Controller/WarehouseOrderController.php` answering JSON `{ok, message, ...}` for
  `action` in `start_preparing`, `mark_ready`, `cannot_supply`, `cancel_order`, guarded by a
  CSRF token, mirroring `Controller/CustomerOrderController.php`.

- [ ] **Step 1: Build the queue page**

`Public/warehouse-orders.php` — the same page furniture as `Public/customer-orders.php`
(header, sidebar, footer includes). A status filter defaulting to **Pending**, and a table:
order no, invoice no, customer, phone, needed by, `2 of 3 pending`, balance (red when > 0),
destination, status badge. Each row links to `customer-order.php?id=`.

- [ ] **Step 2: Rebuild the job sheet**

`Public/customer-order.php` — remove the create/edit form; keep and extend the view:

- customer block (name, phone, address) and delivery block (destination, address, needed by, note);
- money block: net, paid, **balance in red**, and the invoice number linking to the receipt;
- the lines table: description, specs, qty, `Dispatched 2 of 3`, per-line status badge, and
  `GIVEN` rows greyed with **Given at shop — do not send**;
- the dispatches table: number, status, scanned count, sent and delivered stamps;
- buttons for the side the user is on, each hidden unless `ShopAccess::hasFeatureRight` allows it.

- [ ] **Step 3: Write the controller**

`Controller/WarehouseOrderController.php`, answering JSON and mirroring
`Controller/CustomerOrderController.php`'s session, CSRF and refusal handling:

```php
<?php
require_once __DIR__ . '/../Includes/config.php';
//...the same requires and session guard as CustomerOrderController.php
header('Content-Type: application/json');
$shop_id = (int)$_SESSION['shop_id'];
$user_id = (int)$_SESSION['user_id'];
$orders = new WarehouseOrder();
$dispatches = new OrderDispatch();
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

try
{
    switch(isset($_POST['action']) ? $_POST['action'] : '')
    {
        case 'start_preparing':   $result = $orders->startPreparing($id, $shop_id, $user_id); break;
        case 'mark_ready':        $result = $orders->markReady($id, $shop_id, $user_id,
                                      empty($_POST['line_id']) ? null : (int)$_POST['line_id']); break;
        case 'cannot_supply':     $result = $orders->cannotSupply($id, $shop_id, $user_id,
                                      (int)$_POST['line_id'], (string)$_POST['reason']); break;
        case 'cancel_order':      $result = $orders->cancel($id, $shop_id, $user_id); break;
        case 'open_dispatch':     $result = $dispatches->open($id, $shop_id, $user_id); break;
        case 'cancel_dispatch':   $result = $dispatches->cancel($id, $shop_id, $user_id); break;
        case 'complete_dispatch': $result = $dispatches->complete($id, $shop_id, $user_id,
                                      ['confirm_balance' => !empty($_POST['confirm_balance'])]); break;
        case 'delivered':         $result = $dispatches->delivered($id, $shop_id, $user_id,
                                      (string)$_POST['note']); break;
        default: throw new CustomerOrderRefused(422, 'Unknown action.');
    }
    echo json_encode(['ok' => true] + (is_array($result) ? $result : []));
}
catch(CustomerOrderRefused $e)
{
    http_response_code($e->status);
    echo json_encode(['ok' => false, 'status' => $e->status, 'message' => $e->getMessage()]);
}
```

- [ ] **Step 4: Menu and badge**

`View/menu-customer-orders.php` — add **Warehouse Orders** pointing at `warehouse-orders.php`,
shown when the user has the *Customer Orders* view right in the shop they are in, with a badge
from `WarehouseOrder::pendingCount()`. It is included from both sidebar copies already
(`View/sidebar.php:157` and `:1335`), so no further change is needed there:

```php
<?php if($access->hasFeatureRight($_SESSION['user_id'], $_SESSION['shop_id'], 'Customer Orders', 'is_view')): ?>
  <li class="sidebar-item">
    <a class="sidebar-link" href="warehouse-orders.php">
      <span class="hide-menu">Warehouse Orders</span>
      <?php $waiting = (new WarehouseOrder())->pendingCount($_SESSION['shop_id']);
            if($waiting > 0): ?>
        <span class="badge bg-danger rounded-pill ms-2"><?php echo (int)$waiting; ?></span>
      <?php endif; ?>
    </a>
  </li>
<?php endif; ?>
```

- [ ] **Step 5: Check the pages render clean**

Run the app locally and open `warehouse-orders.php` and `customer-order.php?id=1` signed in;
expected: HTTP 200 and **no** `Warning` / `Notice` / `Deprecated` / `Fatal error` in the HTML.

- [ ] **Step 6: Commit and push**

```bash
git add Public/warehouse-orders.php Public/customer-orders.php Public/customer-order.php Controller/WarehouseOrderController.php Assets/jquery/warehouse_order.js View/menu-customer-orders.php
git commit -m "feat(fulfilment): the warehouse queue and the order job sheet"
git push origin main
```

---

## Task 7: Opening a dispatch

**Files:**
- Create: `Model/order_dispatch_class.php`
- Test: `tests/DispatchScanTest.php`

**Interfaces:**
- Produces: `const OPEN = 1, SENT = 2, CANCELLED = 3;`
  `open($order_id, $shop_id, $user_id, array $lines = null): array` → `['dispatch_id', 'dispatch_no']`;
  `cancel($dispatch_id, $shop_id, $user_id)`; `get($dispatch_id, $shop_id): array` with its
  required lines and what has been scanned.

- [ ] **Step 1: Write the failing tests**

`tests/DispatchScanTest.php`. Its `setUp()` builds the same two shops, role, users and products
as `WarehouseOrderTest` (copy that fixture), adds `private WarehouseOrder $orders;`,
`private OrderDispatch $dispatches;`, `private DispatchScan $scan;` and these helpers, which
Tasks 8 and 9 also use:

```php
    //an order for $qty beds plus a given bedsheet, its warehouse line marked ready.
    //Sets $this->orderId, $this->invoiceId and $this->lineId.
    private function readyOrder($qty = 1)
    {
        $this->invoiceId = $this->insert('invoiceheader', ['InvoiceNo' => 'INV_' . uniqid(),
            'EffectiveDate' => date('Y-m-d'), 'InvItemCount' => 2, 'GrossAmount' => 1000, 'NetAmount' => 1000,
            'CustPayment' => 400, 'CustBalance' => 600, 'InvStat' => 1,
            'user_USID' => $this->cashier, 'shop_SHID' => $this->shop]);
        $this->orderId = $this->orders->createFromSale($this->shop, $this->cashier, [
            'invoice_id' => $this->invoiceId, 'supplier_shop_id' => $this->warehouse, 'customer_id' => null,
            'cust_name' => 'Nimal', 'cust_phone' => '0771234567', 'cust_address' => '12 Galle Rd',
            'deliver_to' => 1, 'delivery_address' => '12 Galle Rd', 'delivery_phone' => '0771234567',
            'delivery_note' => '', 'needed_by' => null, 'notes' => '', 'lines' => [
                ['source' => 'GIVEN', 'product_id' => $this->sheetHere, 'supplier_product_id' => null,
                    'description' => 'Bedsheet', 'qty' => 1, 'notes' => '', 'unit_price' => 100],
                ['source' => 'WAREHOUSE', 'product_id' => $this->bedHere, 'supplier_product_id' => $this->bedThere,
                    'description' => 'Cooler Bed', 'qty' => $qty, 'notes' => '', 'unit_price' => 800],
            ]])['order_id'];
        $this->lineId = $this->lineOf($this->orderId);
        $this->orders->markReady($this->orderId, $this->warehouse, $this->picker);
        return $this->orderId;
    }

    //the warehouse line of an order (the given one is never dispatchable)
    private function lineOf($order_id)
    {
        $stmt = $this->pdo->prepare("SELECT COLID FROM customerorderlines
            WHERE customerorders_COID = ? AND LineSource = 'WAREHOUSE' ORDER BY SortOrder LIMIT 1");
        $stmt->execute([$order_id]);
        return (int) $stmt->fetchColumn();
    }

    private function openDispatch($qty = 1, $needed = 1)
    {
        $this->readyOrder($needed);
        return $this->dispatches->open($this->orderId, $this->warehouse, $this->picker)['dispatch_id'];
    }

    private function openDispatchOnAnotherOrder()
    {
        return $this->openDispatch();
    }

    //one printed unit sticker for the warehouse's bed, as Print Barcode makes it
    private function printUnit()
    {
        $this->insert('barcodesettings', ['shop_SHID' => $this->warehouse, 'UnitMode' => 1,
            'UnitPattern' => '{ITEM}{YY}{MM}{SEQ}', 'UnitSeqLength' => 4, 'UnitSeparator' => '']);
        $batch = (new ProductUnits())->allocate($this->warehouse, $this->bedThere, date('Y-m-d'), 1, $this->picker);
        return $batch['codes'][0];
    }

    private function stockOf($product_id)
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(CurrentQty), 0) FROM inventory WHERE products_PDID = ?');
        $stmt->execute([$product_id]);
        return (float) $stmt->fetchColumn();
    }

    private function statusOf($id)
    {
        $stmt = $this->pdo->prepare('SELECT OrderStat FROM customerorders WHERE COID = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn();
    }

    private function lineStatusOf($line_id)
    {
        $stmt = $this->pdo->prepare('SELECT LineStat FROM customerorderlines WHERE COLID = ?');
        $stmt->execute([$line_id]);
        return (int) $stmt->fetchColumn();
    }

    //scan $qty product barcodes into a dispatch and complete it
    private function scanAndSend($dispatch_id, $qty)
    {
        $this->scan->apply($dispatch_id, $this->warehouse, $this->picker,
            implode("\n", array_fill(0, $qty, 'COO00001')), []);
        $this->dispatches->complete($dispatch_id, $this->warehouse, $this->picker, ['confirm_balance' => 1]);
    }
```

The tests:

```php
    public function test_a_dispatch_opens_with_every_ready_line()
    {
        $id = $this->readyOrder();

        $dispatch = $this->dispatches->open($id, $this->warehouse, $this->picker);

        $this->assertSame('DS_000001', $dispatch['dispatch_no']);
        $this->assertSame([1.0], array_map('floatval',
            array_column($this->dispatches->get($dispatch['dispatch_id'], $this->warehouse)['lines'], 'Needed')));
    }

    public function test_nothing_ready_means_nothing_to_dispatch()
    {
        $this->expectException(CustomerOrderRefused::class);
        $this->dispatches->open($this->order()['order_id'], $this->warehouse, $this->picker);
    }

    public function test_a_second_dispatch_only_holds_what_is_left()
    {
        $id = $this->readyOrder(3);
        $first = $this->dispatches->open($id, $this->warehouse, $this->picker, [['line_id' => $this->lineOf($id), 'qty' => 2]]);
        $this->scanAndSend($first['dispatch_id'], 2);

        $second = $this->dispatches->open($id, $this->warehouse, $this->picker);

        $this->assertSame(1.0, (float) $this->dispatches->get($second['dispatch_id'], $this->warehouse)['lines'][0]['Needed']);
    }

    public function test_an_open_dispatch_can_be_cancelled_and_moves_no_stock()
    {
        $id = $this->readyOrder();
        $dispatch = $this->dispatches->open($id, $this->warehouse, $this->picker)['dispatch_id'];
        $before = $this->stockOf($this->bedThere);

        $this->dispatches->cancel($dispatch, $this->warehouse, $this->picker);

        $this->assertSame($before, $this->stockOf($this->bedThere));
        $this->assertSame(WarehouseOrder::READY, $this->statusOf($id));
    }

    public function test_only_one_dispatch_is_open_on_an_order_at_a_time()
    {
        $id = $this->readyOrder();
        $this->dispatches->open($id, $this->warehouse, $this->picker);

        $this->expectException(CustomerOrderRefused::class);
        $this->dispatches->open($id, $this->warehouse, $this->picker);
    }
```

- [ ] **Step 2: Run them and watch them fail**

Run: `C:/xampp/php/php.exe tools/phpunit.phar --filter DispatchScanTest`
Expected: FAIL — `Class "OrderDispatch" not found`.

- [ ] **Step 3: Implement `OrderDispatch::open` / `cancel` / `get`**

`open()` in one transaction: lock the order, require the incoming side and the verify right,
refuse when a dispatch is already open on it (409), gather the `LINE_READY` lines with
`Qty - DispatchedQty > 0` (or the caller's subset, each quantity capped at that remainder),
refuse when nothing is left (422), number it `DS_` + six digits per shop the way
`nextOrderNo()` does, and insert the header. The required quantities live on the header's lines
as a `Needed` column computed in `get()` from the order lines and what this dispatch has
already scanned — no separate requirement table.

`cancel()`: only while `OPEN`; delete its `orderdispatchlines`, set `CANCELLED`, and
`refreshStatus()` the order. No stock has moved, so nothing is returned.

- [ ] **Step 4: Run the tests**

Run: `C:/xampp/php/php.exe tools/phpunit.phar --filter DispatchScanTest`
Expected: PASS.

- [ ] **Step 5: Commit and push**

```bash
git add Model/order_dispatch_class.php tests/DispatchScanTest.php
git commit -m "feat(fulfilment): open and cancel a dispatch for what is ready"
git push origin main
```

---

## Task 8: Scanning items into a dispatch

**Files:**
- Create: `Model/scan_dispatch_class.php`
- Modify: `Controller/ScanUploadController.php:66-92`
- Test: `tests/DispatchScanTest.php`

**Interfaces:**
- Consumes: `ScanDocument` (`foldUnits`, `errorLine`, `finish`, `transaction`,
  `productsByBarcode`, `pickProduct`), `ScanParser::parse($raw, $knownBarcodes, $unitSuffixLength)`,
  `ProductUnits::resolve(array $codes, $shop_id)`, `ProductUnits::suffixLength($shop_id)`.
- Produces: `DispatchScan::preview($dispatch_id, $shop_id, $user_id, $raw, array $decisions = [])`
  and `apply(...)`, both returning the preview array (`lines`, `blocking`, `can_apply`).

- [ ] **Step 1: Write the failing tests**

```php
    public function test_scanning_the_bed_counts_one_against_its_line()
    {
        $dispatch = $this->openDispatch();

        $preview = $this->scan->preview($dispatch, $this->warehouse, $this->picker, 'COO00001');

        $this->assertSame([1, 'ok'], [$preview['lines'][0]['qty'], $preview['lines'][0]['status']]);
        $this->assertTrue($preview['can_apply']);
    }

    public function test_a_unit_sticker_is_recorded_against_the_customer()
    {
        $dispatch = $this->openDispatch();
        $code = $this->printUnit();

        $this->scan->apply($dispatch, $this->warehouse, $this->picker, $code, []);

        $stmt = $this->pdo->prepare('SELECT UnitBarcode, productunits_PUID FROM orderdispatchlines WHERE orderdispatches_DSID = ?');
        $stmt->execute([$dispatch]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertSame($code, $row['UnitBarcode']);
        $this->assertNotNull($row['productunits_PUID']);
    }

    public function test_a_code_we_do_not_know_is_refused()
    {
        $preview = $this->scan->preview($this->openDispatch(), $this->warehouse, $this->picker, 'NOT-A-CODE');

        $this->assertSame('error', $preview['lines'][0]['status']);
        $this->assertStringContainsString('Not a product we know', $preview['lines'][0]['message']);
    }

    public function test_a_product_that_is_not_on_this_order_is_refused()
    {
        $this->createProduct($this->warehouse, 'PIL00001', 'Pillow');
        $preview = $this->scan->preview($this->openDispatch(), $this->warehouse, $this->picker, 'PIL00001');

        $this->assertStringContainsString('Not on this order', $preview['lines'][0]['message']);
    }

    public function test_an_item_already_given_at_the_shop_is_refused()
    {
        $preview = $this->scan->preview($this->openDispatch(), $this->warehouse, $this->picker, 'LIN00001');

        $this->assertStringContainsString('Given at the shop', $preview['lines'][0]['message']);
    }

    public function test_the_same_sticker_twice_counts_once()
    {
        $dispatch = $this->openDispatch();
        $code = $this->printUnit();

        $preview = $this->scan->preview($dispatch, $this->warehouse, $this->picker, $code . "\n" . $code);

        $this->assertSame(1, $preview['lines'][0]['apply_qty']);
    }

    public function test_a_sticker_already_dispatched_is_refused_with_where_it_went()
    {
        $code = $this->printUnit();
        $first = $this->openDispatch();
        $this->scan->apply($first, $this->warehouse, $this->picker, $code, []);
        $this->dispatches->complete($first, $this->warehouse, $this->picker, ['confirm_balance' => 1]);

        $preview = $this->scan->preview($this->openDispatchOnAnotherOrder(), $this->warehouse, $this->picker, $code);

        $this->assertStringContainsString('Already dispatched on DS_000001', $preview['lines'][0]['message']);
    }

    public function test_a_sticker_sitting_in_another_open_dispatch_is_refused()
    {
        $code = $this->printUnit();
        $mine = $this->openDispatch();
        $theirs = $this->openDispatchOnAnotherOrder();
        $this->scan->apply($theirs, $this->warehouse, $this->picker, $code, []);

        $preview = $this->scan->preview($mine, $this->warehouse, $this->picker, $code);

        $this->assertSame('error', $preview['lines'][0]['status']);
        $this->assertStringContainsString('Being dispatched on DS_', $preview['lines'][0]['message']);
    }

    public function test_more_than_the_line_needs_is_refused()
    {
        $dispatch = $this->openDispatch();

        $preview = $this->scan->preview($dispatch, $this->warehouse, $this->picker, "COO00001\nCOO00001");

        $this->assertSame('error', $preview['lines'][0]['status']);
        $this->assertStringContainsString('Only 1', $preview['lines'][0]['message']);
    }

    public function test_a_voided_sticker_is_refused()
    {
        $code = $this->printUnit();
        $this->pdo->prepare('UPDATE productunits SET UnitStat = 0 WHERE UnitBarcode = ?')->execute([$code]);

        $preview = $this->scan->preview($this->openDispatch(), $this->warehouse, $this->picker, $code);

        $this->assertStringContainsString('Voided', $preview['lines'][0]['message']);
    }

    //Review Focus 2: the sale was returned overnight
    public function test_a_dispatch_for_a_cancelled_invoice_is_refused()
    {
        $dispatch = $this->openDispatch();
        $this->pdo->prepare('UPDATE invoiceheader SET InvStat = 0 WHERE IHID = ?')->execute([$this->invoiceId]);

        $this->expectException(ScanRefused::class);
        $this->scan->apply($dispatch, $this->warehouse, $this->picker, 'COO00001', []);
    }
```

- [ ] **Step 2: Run them and watch them fail**

Run: `C:/xampp/php/php.exe tools/phpunit.phar --filter DispatchScanTest`
Expected: FAIL — `Class "DispatchScan" not found`.

- [ ] **Step 3: Implement `DispatchScan`**

`preview()` loads the open dispatch and its needed lines, parses the raw text with
`ScanParser::parse($raw, $this->knownBarcodes($byCode), $this->units->suffixLength($shop_id))`,
resolves unit codes against `productunits`, then builds one preview line per product with
`errorLine()` for each refusal in the spec's table. `apply()` calls `preview()`, then
`assertCanApply()`, then inside `transaction()` writes one `orderdispatchlines` row per scanned
item — `UnitBarcode` and `productunits_PUID` filled for stickers, both NULL for product
barcodes — and refuses (409) if the order's invoice is no longer `InvStat = 1`.

Add to `Controller/ScanUploadController.php` beside the existing contexts:

```php
    elseif($context === 'dispatch')
    {
        $scan = new DispatchScan();
        $result = $action === 'apply'
            ? $scan->apply($doc_id, $shop_id, $user_id, $raw, $decisions)
            : $scan->preview($doc_id, $shop_id, $user_id, $raw, $decisions);
    }
```

- [ ] **Step 4: Run the tests**

Run: `C:/xampp/php/php.exe tools/phpunit.phar`
Expected: PASS, whole suite green.

- [ ] **Step 5: Commit and push**

```bash
git add Model/scan_dispatch_class.php Controller/ScanUploadController.php tests/DispatchScanTest.php
git commit -m "feat(fulfilment): scan items into a dispatch, and refuse the wrong ones"
git push origin main
```

---

## Task 9: Completing a dispatch and delivering it

**Files:**
- Modify: `Model/order_dispatch_class.php`, `Model/warehouse_order_class.php`
- Create: `View/modals/dispatch-scan.php`, `Assets/jquery/dispatch_scan.js`
- Test: `tests/DispatchScanTest.php`

**Interfaces:**
- Consumes: `StockAllocator::allocate($product_id, $shop_id, $qty, array $taken)`.
- Produces: `complete($dispatch_id, $shop_id, $user_id, array $decisions): array` and
  `delivered($dispatch_id, $shop_id, $user_id, $note)`.

- [ ] **Step 1: Write the failing tests**

```php
    public function test_completing_a_dispatch_takes_the_stock_and_marks_the_line()
    {
        $dispatch = $this->openDispatch();
        $this->scan->apply($dispatch, $this->warehouse, $this->picker, 'COO00001', []);
        $before = $this->stockOf($this->bedThere);

        $this->dispatches->complete($dispatch, $this->warehouse, $this->picker, []);

        $this->assertSame($before - 1, $this->stockOf($this->bedThere));
        $this->assertSame(WarehouseOrder::LINE_DISPATCHED, $this->lineStatusOf($this->lineId));
        $this->assertSame(WarehouseOrder::DISPATCHED, $this->statusOf($this->orderId));
    }

    public function test_the_sale_is_costed_against_the_shops_invoice()
    {
        $dispatch = $this->openDispatch();
        $this->scan->apply($dispatch, $this->warehouse, $this->picker, 'COO00001', []);

        $this->dispatches->complete($dispatch, $this->warehouse, $this->picker, []);

        $stmt = $this->pdo->prepare('SELECT invoice_headerID, shop_SHID, quantity FROM inventory_consumption');
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertSame([$this->invoiceId, $this->warehouse, 1.0],
            [(int) $row['invoice_headerID'], (int) $row['shop_SHID'], (float) $row['quantity']]);
    }

    public function test_a_half_scanned_dispatch_cannot_be_completed()
    {
        $dispatch = $this->openDispatch(1, 2);   //two beds needed, one scanned
        $this->scan->apply($dispatch, $this->warehouse, $this->picker, 'COO00001', []);

        $this->expectException(CustomerOrderRefused::class);
        $this->dispatches->complete($dispatch, $this->warehouse, $this->picker, []);
    }

    public function test_an_outstanding_balance_must_be_confirmed()
    {
        $dispatch = $this->openDispatch();
        $this->scan->apply($dispatch, $this->warehouse, $this->picker, 'COO00001', []);

        try {
            $this->dispatches->complete($dispatch, $this->warehouse, $this->picker, []);
            $this->fail('expected the balance to be confirmed');
        } catch (CustomerOrderRefused $e) {
            $this->assertSame(409, $e->status);
        }

        $this->dispatches->complete($dispatch, $this->warehouse, $this->picker, ['confirm_balance' => 1]);
        $this->assertSame(WarehouseOrder::DISPATCHED, $this->statusOf($this->orderId));
    }

    public function test_stock_that_went_between_scanning_and_completing_refuses_the_whole_dispatch()
    {
        $dispatch = $this->openDispatch();
        $this->scan->apply($dispatch, $this->warehouse, $this->picker, 'COO00001', []);
        $this->pdo->prepare('UPDATE inventory SET CurrentQty = 0 WHERE products_PDID = ?')->execute([$this->bedThere]);

        try {
            $this->dispatches->complete($dispatch, $this->warehouse, $this->picker, ['confirm_balance' => 1]);
            $this->fail('expected a refusal');
        } catch (CustomerOrderRefused $e) {
            $this->assertSame(409, $e->status);
        }
        $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM inventory_consumption')->fetchColumn());
    }

    //Review Focus 3: two dispatchers race for the same sticker
    public function test_a_sticker_can_only_be_completed_onto_one_dispatch()
    {
        $code = $this->printUnit();
        $first = $this->openDispatch();
        $second = $this->openDispatchOnAnotherOrder();
        $this->scan->apply($first, $this->warehouse, $this->picker, $code, []);
        $this->pdo->prepare("INSERT INTO orderdispatchlines (orderdispatches_DSID, customerorderlines_COLID,
            products_PDID, Qty, UnitBarcode, productunits_PUID, ScannedAt, ScannedBy)
            SELECT ?, customerorderlines_COLID, products_PDID, Qty, UnitBarcode, productunits_PUID, NOW(), ?
            FROM orderdispatchlines WHERE orderdispatches_DSID = ?")->execute([$second, $this->picker, $first]);

        $this->dispatches->complete($first, $this->warehouse, $this->picker, ['confirm_balance' => 1]);

        $this->expectException(CustomerOrderRefused::class);
        $this->dispatches->complete($second, $this->warehouse, $this->picker, ['confirm_balance' => 1]);
    }

    public function test_delivering_completes_the_order()
    {
        $dispatch = $this->openDispatch();
        $this->scan->apply($dispatch, $this->warehouse, $this->picker, 'COO00001', []);
        $this->dispatches->complete($dispatch, $this->warehouse, $this->picker, ['confirm_balance' => 1]);

        $this->dispatches->delivered($dispatch, $this->warehouse, $this->picker, 'Left with the customer');

        $this->assertSame(WarehouseOrder::COMPLETED, $this->statusOf($this->orderId));
        $this->assertSame(WarehouseOrder::LINE_DELIVERED, $this->lineStatusOf($this->lineId));
    }
```

- [ ] **Step 2: Run them and watch them fail**

Run: `C:/xampp/php/php.exe tools/phpunit.phar --filter DispatchScanTest`
Expected: FAIL — `Call to undefined method OrderDispatch::complete()`.

- [ ] **Step 3: Implement `complete()` and `delivered()`**

`complete()` in one transaction, in this order:

1. lock the dispatch and its order; require incoming side and verify right; must be `OPEN`;
2. the order's invoice must still be `InvStat = 1`, else 409;
3. every needed line fully scanned, else 422 naming what is short;
4. `CustBalance > 0` and no `confirm_balance` → 409 with the amount;
5. for each scanned **unit**, `UPDATE productunits SET UnitStat = 3, orderdispatches_DSID = ?,
   DispatchedAt = NOW(), DispatchedBy = ? WHERE PUID = ? AND UnitStat IN (1, 2)` — a row count
   below the number of units means another dispatch took it first: 409, roll back;
6. per product, `StockAllocator::allocate($product_id, $warehouse_shop_id, $qty, $taken)` where
   `$taken` counts stock held by open transfers exactly as
   `Model/customer_order_class.php:256-266` does; `short > 0` → 409, roll back;
7. for each part: `UPDATE inventory SET CurrentQty = CurrentQty - ?, BillQty = BillQty + ?
   WHERE INID = ?`, an `inventory_consumption` row with the **order's invoice id** and the
   **warehouse's** `shop_SHID`, and the part's `InventoryID` / `Batch_ID` written back onto the
   `orderdispatchlines` row;
8. `customerorderlines.DispatchedQty = DispatchedQty + ?` and `LineStat = LINE_DISPATCHED` when
   it reaches `Qty`;
9. dispatch `SENT`, `SentBy`/`SentAt`; `WarehouseOrder::refreshStatus()`.

`delivered()`: only when `SENT`; stamps `DeliveredBy`/`DeliveredAt`/`DeliveryNote`, raises
`DeliveredQty` on its lines, sets `LINE_DELIVERED` where full, then `refreshStatus()`.

- [ ] **Step 4: Build the scan dialog**

`View/modals/dispatch-scan.php` + `Assets/jquery/dispatch_scan.js`, modelled on the GRN
scanner dialog: the needed lines with a live tally, keyboard-wedge capture posting to
`Controller/ScanUploadController.php` with `context=dispatch`, red rows for refusals, and
**Complete dispatch** calling `WarehouseOrderController.php?action=complete_dispatch`, which
shows the balance confirmation when the controller answers 409.

- [ ] **Step 5: Run the tests**

Run: `C:/xampp/php/php.exe tools/phpunit.phar`
Expected: PASS, whole suite green.

- [ ] **Step 6: Commit and push**

```bash
git add Model/order_dispatch_class.php Model/warehouse_order_class.php View/modals/dispatch-scan.php Assets/jquery/dispatch_scan.js tests/DispatchScanTest.php
git commit -m "feat(fulfilment): completing a dispatch takes the stock and sends the goods"
git push origin main
```

---

## Task 10: End to end, the guide, and the reports check

**Files:**
- Create: `tests/e2e/warehouse_fulfilment_e2e.php`, `db/WAREHOUSE_FULFILMENT_MODULE.md`
- Modify: `Model/Report_class.php` if the check below finds a problem

- [ ] **Step 1: Check the reports that read `inventory_consumption`**

```bash
grep -rn "inventory_consumption" Model/ Public/ AJAX/ Controller/
```

For each hit, decide whether it assumes the consumption row's shop is the invoice's shop. Record
the answer in `db/WAREHOUSE_FULFILMENT_MODULE.md` under *Found while building*, and fix any
report that would now double-count or lose a warehouse-supplied sale.

- [ ] **Step 2: Write the end-to-end suite**

`tests/e2e/warehouse_fulfilment_e2e.php`, using `tests/e2e/lib.php`: sign into the showroom,
sell a bedsheet from stock plus a warehouse bed plus a custom headboard with a part payment;
check the order appears Pending in the warehouse with the right balance; start preparing; mark
ready; open a dispatch; scan a wrong barcode (refused), the bed's sticker twice (counts once)
and the headboard by hand; complete with the balance confirmed; check the warehouse stock fell
by one and the shop's stock did not move; mark delivered; check the order reads Completed.

It also pins **Review Focus 1**: post a checkout whose warehouse line names a product the
supplier does not own, and assert that the sale is refused **and** that no new row appeared in
`invoiceheader`, `invoicedetails` or `customerorders` — a paid sale must never exist without
the order that tells the warehouse to send the goods.

- [ ] **Step 3: Run everything**

```bash
C:/xampp/php/php.exe tools/phpunit.phar
C:/xampp/php/php.exe tests/e2e/warehouse_fulfilment_e2e.php
C:/xampp/php/php.exe tests/e2e/shop_permissions_e2e.php
C:/xampp/php/php.exe tests/e2e/unit_barcodes_e2e.php
C:/xampp/php/php.exe tests/e2e/customer_orders_e2e.php
C:/xampp/php/php.exe tests/e2e/scan_upload_e2e.php
```

Expected: all green. Run them one at a time — they share the test database.

- [ ] **Step 4: Write the module guide**

`db/WAREHOUSE_FULFILMENT_MODULE.md`, in the style of `db/UNIT_BARCODES_MODULE.md`: install,
what the cashier does, what the warehouse does, the statuses, the scan refusals, deploying, and
the tests.

- [ ] **Step 5: Commit and push**

```bash
git add tests/e2e/warehouse_fulfilment_e2e.php db/WAREHOUSE_FULFILMENT_MODULE.md
git commit -m "docs(fulfilment): the module guide, and the whole flow end to end"
git push origin main
```

- [ ] **Step 6: Deploy**

Follow the recipe in `memory/server-deployment.md`: compare the server's files against the
deployed commit, back up `sleepmakers_live`, copy the database and the code to a stage, run
`php db/warehouse_fulfilment_install.php` there, run the e2e suites and the 99-page admin /
57-page owner crawl on the stage and compare against the current code, then ship only
`git diff --name-status <deployed> main` — never `tests/`, `docs/`, `tools/`, `.gitignore`,
`phpunit.xml`. Afterwards grant the *Customer Orders* right where it is needed and check the
live site signed in.
