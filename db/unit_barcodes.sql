-- Unique barcode per unit (db/UNIT_BARCODES_MODULE.md): the same change as
-- db/unit_barcodes_install.php, for a manual install (MariaDB). Safe to run twice.

CREATE TABLE IF NOT EXISTS `productunits` (
  `PUID` bigint(20) NOT NULL AUTO_INCREMENT,
  `UnitBarcode` varchar(64) NOT NULL COMMENT 'the code on the sticker',
  `ItemBarcode` varchar(45) NOT NULL COMMENT 'the product barcode it resolves to',
  `products_PDID` int(11) NOT NULL COMMENT 'the product it was printed for',
  `shop_SHID` int(11) NOT NULL COMMENT 'the shop that printed it',
  `ProducedDate` date NOT NULL COMMENT 'the day this unit was made',
  `SeqNo` int(11) NOT NULL COMMENT 'the serial inside its number series',
  `UnitStat` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1 printed, 2 received, 0 voided',
  `PrintRef` varchar(20) NOT NULL COMMENT 'the print job, so a reprint finds it again',
  `PrintedAt` datetime NOT NULL,
  `PrintedBy` int(11) NOT NULL,
  `PrintCount` int(11) NOT NULL DEFAULT 1,
  `LastPrintedAt` datetime DEFAULT NULL,
  `GRNHeader_GHID` int(11) DEFAULT NULL,
  `grndetails_GDID` int(11) DEFAULT NULL,
  `ReceivedAt` datetime DEFAULT NULL,
  `ReceivedBy` int(11) DEFAULT NULL,
  PRIMARY KEY (`PUID`),
  UNIQUE KEY `uq_productunits_code` (`UnitBarcode`),
  KEY `idx_productunits_item` (`ItemBarcode`),
  KEY `idx_productunits_produced` (`products_PDID`, `ProducedDate`),
  KEY `idx_productunits_print` (`shop_SHID`, `PrintRef`),
  KEY `idx_productunits_grn` (`GRNHeader_GHID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- the shop's rules for building a unit code, beside its product barcode rules
ALTER TABLE `barcodesettings`
  ADD COLUMN IF NOT EXISTS `UnitMode` tinyint(4) NOT NULL DEFAULT 0
  COMMENT '1 = print a unique barcode on every unit' AFTER `StripInvalid`;

ALTER TABLE `barcodesettings`
  ADD COLUMN IF NOT EXISTS `UnitPattern` varchar(160) NOT NULL DEFAULT '{ITEM}{YY}{MM}{SEQ}'
  COMMENT 'how a unit code is built' AFTER `UnitMode`;

ALTER TABLE `barcodesettings`
  ADD COLUMN IF NOT EXISTS `UnitSeqLength` tinyint(4) NOT NULL DEFAULT 4
  COMMENT 'digits in the per-unit serial' AFTER `UnitPattern`;

ALTER TABLE `barcodesettings`
  ADD COLUMN IF NOT EXISTS `UnitSeparator` varchar(4) NOT NULL DEFAULT ''
  COMMENT 'between the parts of a unit code' AFTER `UnitSeqLength`;
