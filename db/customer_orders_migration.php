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
