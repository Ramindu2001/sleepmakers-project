-- POS to warehouse fulfilment - the same change db/warehouse_fulfilment_install.php makes.
-- Additive only. Prefer the installer: it reports what it did and skips what is already there.
-- Run this file once, against the live database, BEFORE uploading the code.

-- 1. The order: which invoice billed it, which customer, and where it goes -------------------
ALTER TABLE customerorders
    ADD COLUMN InvoiceHeader_IHID INT(11) DEFAULT NULL COMMENT 'the POS invoice this order was billed on' AFTER SupplierShopID,
    ADD KEY idx_customerorders_invoice (InvoiceHeader_IHID);
ALTER TABLE customerorders
    ADD COLUMN customers_CTID INT(11) DEFAULT NULL COMMENT 'the saved customer the order is for' AFTER InvoiceHeader_IHID;
ALTER TABLE customerorders
    ADD COLUMN DeliverTo TINYINT(4) NOT NULL DEFAULT 1 COMMENT '1 the customer address, 2 collect at the shop' AFTER CustAddress;
ALTER TABLE customerorders
    ADD COLUMN DeliveryAddress VARCHAR(255) DEFAULT NULL COMMENT 'where the warehouse delivers it' AFTER DeliverTo;
ALTER TABLE customerorders
    ADD COLUMN DeliveryPhone VARCHAR(25) DEFAULT NULL AFTER DeliveryAddress;
ALTER TABLE customerorders
    ADD COLUMN DeliveryNote TEXT DEFAULT NULL COMMENT 'directions, landmarks, when to come' AFTER DeliveryPhone;

-- 2. The line: what it is doing, and both product ids ----------------------------------------
ALTER TABLE customerorderlines
    ADD COLUMN LineStat TINYINT(4) NOT NULL DEFAULT 2
    COMMENT '1 given at shop, 2 pending, 3 ready, 4 dispatched, 5 delivered, 6 cancelled' AFTER LineSource;
ALTER TABLE customerorderlines
    ADD COLUMN SupplierProductID INT(11) DEFAULT NULL
    COMMENT 'the warehouse product; products_PDID is the shop copy the invoice carries' AFTER products_PDID;
ALTER TABLE customerorderlines
    ADD COLUMN DispatchedQty DECIMAL(12,3) NOT NULL DEFAULT 0.000 COMMENT 'how much of Qty has left the warehouse' AFTER Qty;
ALTER TABLE customerorderlines
    ADD COLUMN DeliveredQty DECIMAL(12,3) NOT NULL DEFAULT 0.000 COMMENT 'how much of Qty reached the customer' AFTER DispatchedQty;
ALTER TABLE customerorderlines
    ADD COLUMN UnitPrice DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'what the customer was billed' AFTER DeliveredQty;
ALTER TABLE customerorderlines
    ADD COLUMN LineTotal DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER UnitPrice;
ALTER TABLE customerorderlines
    ADD COLUMN CancelReason VARCHAR(255) DEFAULT NULL COMMENT 'why the warehouse could not supply it' AFTER Notes;

-- 3. A unit remembers the dispatch it left on ------------------------------------------------
ALTER TABLE productunits
    ADD COLUMN orderdispatches_DSID INT(11) DEFAULT NULL COMMENT 'the dispatch this unit left on' AFTER ReceivedBy;
ALTER TABLE productunits
    ADD COLUMN DispatchedAt DATETIME DEFAULT NULL AFTER orderdispatches_DSID;
ALTER TABLE productunits
    ADD COLUMN DispatchedBy INT(11) DEFAULT NULL AFTER DispatchedAt;

-- 4. The dispatches ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orderdispatches` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- One row per scan. UnitBarcode is NULL for a plain product barcode, and a unique key holds
-- many NULLs - so a sticker cannot be scanned twice into one dispatch while product barcodes
-- can be scanned as often as the line needs.
CREATE TABLE IF NOT EXISTS `orderdispatchlines` (
    `DDID` bigint(20) NOT NULL AUTO_INCREMENT,
    `orderdispatches_DSID` int(11) NOT NULL,
    `customerorderlines_COLID` int(11) NOT NULL,
    `products_PDID` int(11) NOT NULL COMMENT 'the warehouse product actually issued',
    `Qty` decimal(12,3) NOT NULL DEFAULT 1.000 COMMENT 'what this row accounts for; 0 on a plan row',
    `PlannedQty` decimal(12,3) DEFAULT NULL COMMENT 'set on the one plan row per line: what this trip is to take',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Carry the old order statuses over, ONCE --------------------------------------------------
-- Old: 1 requested, 2 accepted, 3 rejected, 4 cancelled, 5 handed over.
-- New: 1 pending, 2 preparing, 3 ready, 4 dispatched, 5 completed, 6 cancelled.
UPDATE customerorders SET OrderStat = 6 WHERE OrderStat IN (3, 4);
UPDATE customerorderlines SET LineStat = 1 WHERE LineSource = 'GIVEN';
