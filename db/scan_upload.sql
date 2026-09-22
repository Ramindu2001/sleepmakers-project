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
