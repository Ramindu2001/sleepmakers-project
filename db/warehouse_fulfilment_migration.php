<?php
/**
 * POS to warehouse fulfilment - the schema change.
 * -----------------------------------------------------------------------------
 * A cashier bills a whole customer order in POS: what is on the shelf, what only the warehouse
 * has, and items that are not made yet. The lines the shop cannot give become a job for the
 * warehouse, and the goods leave warehouse stock only when they have been scanned into a
 * dispatch.
 *
 * The order tables built for the (unused) hand-typed Customer Orders module are reused:
 *
 *   customerorders.InvoiceHeader_IHID   the POS invoice that billed it - the money is never
 *                                       copied, it is read from the invoice every time
 *   customerorders.DeliverTo            1 the customer's address, 2 collect at the shop
 *   customerorderlines.LineStat         what THIS line is doing: a line given over the counter
 *                                       can never be picked in the warehouse again
 *   customerorderlines.SupplierProductID the warehouse's product; products_PDID is the shop's
 *                                       own copy, the one the invoice line carries
 *   orderdispatches / orderdispatchlines one row per scan, so the same sticker cannot go out
 *                                       twice and every unit is traceable to its customer
 *   productunits.UnitStat = 3           dispatched to a customer
 *
 * ADDITIVE ONLY and idempotent: every step is skipped when it is already in place.
 * Run through db/warehouse_fulfilment_install.php (the tests call it directly).
 * See db/WAREHOUSE_FULFILMENT_MODULE.md.
 */
class WarehouseFulfilmentMigration
{
    //1 pending, 2 preparing, 3 ready, 4 dispatched, 5 completed, 6 cancelled.
    //The old numbering was 1 requested, 2 accepted, 3 rejected, 4 cancelled, 5 handed over.
    const STATUS_REMAP = "UPDATE customerorders SET OrderStat = CASE OrderStat
            WHEN 3 THEN 6 WHEN 4 THEN 6 ELSE OrderStat END WHERE OrderStat IN (3, 4);";

    const ORDER_COLUMNS = array(
        'InvoiceHeader_IHID' => "ALTER TABLE customerorders ADD COLUMN InvoiceHeader_IHID INT(11) DEFAULT NULL
            COMMENT 'the POS invoice this order was billed on' AFTER SupplierShopID,
            ADD KEY idx_customerorders_invoice (InvoiceHeader_IHID);",
        'customers_CTID' => "ALTER TABLE customerorders ADD COLUMN customers_CTID INT(11) DEFAULT NULL
            COMMENT 'the saved customer the order is for' AFTER InvoiceHeader_IHID;",
        'DeliverTo' => "ALTER TABLE customerorders ADD COLUMN DeliverTo TINYINT(4) NOT NULL DEFAULT 1
            COMMENT '1 the customer address, 2 collect at the shop' AFTER CustAddress;",
        'DeliveryAddress' => "ALTER TABLE customerorders ADD COLUMN DeliveryAddress VARCHAR(255) DEFAULT NULL
            COMMENT 'where the warehouse delivers it' AFTER DeliverTo;",
        'DeliveryPhone' => "ALTER TABLE customerorders ADD COLUMN DeliveryPhone VARCHAR(25) DEFAULT NULL AFTER DeliveryAddress;",
        'DeliveryNote' => "ALTER TABLE customerorders ADD COLUMN DeliveryNote TEXT DEFAULT NULL
            COMMENT 'directions, landmarks, when to come' AFTER DeliveryPhone;",
    );

    const LINE_COLUMNS = array(
        'LineStat' => "ALTER TABLE customerorderlines ADD COLUMN LineStat TINYINT(4) NOT NULL DEFAULT 2
            COMMENT '1 given at shop, 2 pending, 3 ready, 4 dispatched, 5 delivered, 6 cancelled' AFTER LineSource;",
        'SupplierProductID' => "ALTER TABLE customerorderlines ADD COLUMN SupplierProductID INT(11) DEFAULT NULL
            COMMENT 'the warehouse product; products_PDID is the shop copy the invoice carries' AFTER products_PDID;",
        'DispatchedQty' => "ALTER TABLE customerorderlines ADD COLUMN DispatchedQty DECIMAL(12,3) NOT NULL DEFAULT 0.000
            COMMENT 'how much of Qty has left the warehouse' AFTER Qty;",
        'DeliveredQty' => "ALTER TABLE customerorderlines ADD COLUMN DeliveredQty DECIMAL(12,3) NOT NULL DEFAULT 0.000
            COMMENT 'how much of Qty reached the customer' AFTER DispatchedQty;",
        'UnitPrice' => "ALTER TABLE customerorderlines ADD COLUMN UnitPrice DECIMAL(12,2) NOT NULL DEFAULT 0.00
            COMMENT 'what the customer was billed, for the job sheet' AFTER DeliveredQty;",
        'LineTotal' => "ALTER TABLE customerorderlines ADD COLUMN LineTotal DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER UnitPrice;",
        'CancelReason' => "ALTER TABLE customerorderlines ADD COLUMN CancelReason VARCHAR(255) DEFAULT NULL
            COMMENT 'why the warehouse could not supply it' AFTER Notes;",
    );

    const UNIT_COLUMNS = array(
        'orderdispatches_DSID' => "ALTER TABLE productunits ADD COLUMN orderdispatches_DSID INT(11) DEFAULT NULL
            COMMENT 'the dispatch this unit left on' AFTER ReceivedBy;",
        'DispatchedAt' => "ALTER TABLE productunits ADD COLUMN DispatchedAt DATETIME DEFAULT NULL AFTER orderdispatches_DSID;",
        'DispatchedBy' => "ALTER TABLE productunits ADD COLUMN DispatchedBy INT(11) DEFAULT NULL AFTER DispatchedAt;",
    );

    const DISPATCHES_TABLE = "CREATE TABLE `orderdispatches` (
        `DSID` int(11) NOT NULL AUTO_INCREMENT,
        `DispatchNo` varchar(20) NOT NULL COMMENT 'DS_000001, per shop',
        `customerorders_COID` int(11) NOT NULL,
        `shop_SHID` int(11) NOT NULL COMMENT 'the warehouse sending it',
        `DispatchStat` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1 open, 2 sent, 3 cancelled',
        `DeliverTo` tinyint(4) NOT NULL DEFAULT 1,
        `DeliveryNote` text DEFAULT NULL COMMENT 'vehicle, driver, who took it',
        `CreatedBy` int(11) NOT NULL,
        `CreatedAt` datetime NOT NULL,
        `SentBy` int(11) DEFAULT NULL,
        `SentAt` datetime DEFAULT NULL,
        `DeliveredBy` int(11) DEFAULT NULL,
        `DeliveredAt` datetime DEFAULT NULL,
        PRIMARY KEY (`DSID`),
        UNIQUE KEY `uq_orderdispatches_no` (`shop_SHID`, `DispatchNo`),
        KEY `idx_orderdispatches_order` (`customerorders_COID`, `DispatchStat`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    //One row per scan. UnitBarcode is NULL when a plain product barcode was scanned, and MySQL
    //lets a unique key hold many NULLs - so a sticker cannot be scanned twice into one dispatch
    //while product barcodes can be scanned as often as the line needs.
    const DISPATCH_LINES_TABLE = "CREATE TABLE `orderdispatchlines` (
        `DDID` bigint(20) NOT NULL AUTO_INCREMENT,
        `orderdispatches_DSID` int(11) NOT NULL,
        `customerorderlines_COLID` int(11) NOT NULL,
        `products_PDID` int(11) NOT NULL COMMENT 'the warehouse product actually issued',
        `Qty` decimal(12,3) NOT NULL DEFAULT 1.000,
        `UnitBarcode` varchar(64) DEFAULT NULL COMMENT 'NULL when a product barcode was scanned',
        `productunits_PUID` bigint(20) DEFAULT NULL,
        `InventoryID` int(11) DEFAULT NULL COMMENT 'the batch it came out of, filled when sent',
        `Batch_ID` varchar(45) DEFAULT NULL,
        `ScannedAt` datetime NOT NULL,
        `ScannedBy` int(11) NOT NULL,
        PRIMARY KEY (`DDID`),
        UNIQUE KEY `uq_orderdispatchlines_unit` (`orderdispatches_DSID`, `UnitBarcode`),
        KEY `idx_orderdispatchlines_line` (`customerorderlines_COLID`),
        KEY `idx_orderdispatchlines_unit` (`productunits_PUID`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    private $pdo;
    private $report = array();

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }//construct

    //apply every step; returns one report line per step
    public function run()
    {
        $this->report = array();
        //the line state is what this module adds to an existing order line, so its absence is
        //what "this database has not been through this migration yet" means
        $fresh = !$this->hasColumn('customerorderlines', 'LineStat');

        foreach (self::ORDER_COLUMNS as $column => $sql) {
            $this->step('customerorders.' . $column, !$this->hasColumn('customerorders', $column), $sql);
        }//each order column

        foreach (self::LINE_COLUMNS as $column => $sql) {
            $this->step('customerorderlines.' . $column, !$this->hasColumn('customerorderlines', $column), $sql);
        }//each line column

        foreach (self::UNIT_COLUMNS as $column => $sql) {
            $this->step('productunits.' . $column, !$this->hasColumn('productunits', $column), $sql);
        }//each unit column

        $this->step('table orderdispatches', !$this->hasTable('orderdispatches'), self::DISPATCHES_TABLE);
        $this->step('table orderdispatchlines', !$this->hasTable('orderdispatchlines'), self::DISPATCH_LINES_TABLE);

        //only on the run that introduces the line state: afterwards these numbers mean the new
        //things and rewriting them would move live orders backwards
        $this->step('customerorders statuses carried over', $fresh, self::STATUS_REMAP);
        $this->step('lines already given marked as given', $fresh,
            "UPDATE customerorderlines SET LineStat = 1 WHERE LineSource = 'GIVEN';");

        return $this->report;
    }//run

    private function step($label, $needed, $sql)
    {
        if (!$needed) {
            $this->say('skip', $label . ' - already in place');
            return;
        }//nothing to do

        $this->pdo->exec($sql);
        $this->say('ok', $label);
    }//step

    private function say($status, $text)
    {
        $this->report[] = '[' . $status . '] ' . $text;
    }//say

    private function hasTable($table)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?;");
        $stmt->execute(array($table));
        return (int) $stmt->fetchColumn() > 0;
    }//hasTable

    private function hasColumn($table, $column)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?;");
        $stmt->execute(array($table, $column));
        return (int) $stmt->fetchColumn() > 0;
    }//hasColumn
}//WarehouseFulfilmentMigration
