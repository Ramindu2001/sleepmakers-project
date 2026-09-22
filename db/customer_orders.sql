-- Customer orders (db/CUSTOMER_ORDERS_MODULE.md): the same change as db/customer_orders_install.php,
-- for a manual install (MariaDB). Safe to run twice.
CREATE TABLE IF NOT EXISTS `customerorders` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `customerorderlines` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `transferheader`
  ADD COLUMN IF NOT EXISTS `CustomerOrderID` INT(11) NULL DEFAULT NULL COMMENT 'customer order it was created from',
  ADD KEY IF NOT EXISTS `idx_transferheader_customerorder` (`CustomerOrderID`);

-- the role right, id 101 or above (the sidebar already refers to a feature 74 that does not exist)
-- (the guard sits outside the MAX(): an aggregate returns a row even when nothing matches)
INSERT INTO `sysfeatures` (`SFID`, `FeatureName`, `SystemModules_SMID`, `sort_order`)
SELECT next_id, 'Customer Orders', 2, next_id
FROM (SELECT GREATEST(101, COALESCE(MAX(`SFID`), 0) + 1) AS next_id FROM `sysfeatures`) AS next_feature
WHERE NOT EXISTS (SELECT 1 FROM `sysfeatures` WHERE `FeatureName` = 'Customer Orders');
