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
