<?php
/**
 * Unique barcode per unit - the schema change.
 * -----------------------------------------------------------------------------
 * Sleep Makers print a sticker for every unit they make, not one code per product. Each
 * printed unit gets a row here:
 *
 *   productunits.UnitBarcode   the scanned code, UNIQUE - the same unit can never be
 *                              registered twice, whoever scans it and whenever
 *   productunits.ItemBarcode   the product barcode it resolves to, in any shop (a transfer
 *                              copies a product into the receiving shop with the same code)
 *   productunits.ProducedDate  the exact day it was made, so "how many on the 12th" is a
 *                              count even when the code itself only carries the month
 *
 * The rules for building the code live with the shop's other barcode rules
 * (barcodesettings.UnitPattern / UnitSeqLength / UnitMode).
 *
 * ADDITIVE ONLY and idempotent: every step is skipped when it is already in place, and with
 * UnitMode off the application behaves exactly as it did before.
 * Run through db/unit_barcodes_install.php (the tests call it directly).
 * See db/UNIT_BARCODES_MODULE.md.
 */
class UnitBarcodesMigration
{
    //the shop's rules for building a unit code, alongside the product barcode rules
    const SETTINGS_COLUMNS = array(
        'UnitMode' => "ALTER TABLE barcodesettings ADD COLUMN UnitMode TINYINT(4) NOT NULL DEFAULT 0
            COMMENT '1 = print a unique barcode on every unit' AFTER StripInvalid;",
        'UnitPattern' => "ALTER TABLE barcodesettings ADD COLUMN UnitPattern VARCHAR(160) NOT NULL DEFAULT '{ITEM}{YY}{MM}{SEQ}'
            COMMENT 'how a unit code is built' AFTER UnitMode;",
        'UnitSeqLength' => "ALTER TABLE barcodesettings ADD COLUMN UnitSeqLength TINYINT(4) NOT NULL DEFAULT 4
            COMMENT 'digits in the per-unit serial' AFTER UnitPattern;",
        'UnitSeparator' => "ALTER TABLE barcodesettings ADD COLUMN UnitSeparator VARCHAR(4) NOT NULL DEFAULT ''
            COMMENT 'between the parts of a unit code' AFTER UnitSeqLength;",
    );

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

        $this->step('table productunits', !$this->hasTable('productunits'),
            "CREATE TABLE productunits (
                PUID BIGINT(20) NOT NULL AUTO_INCREMENT,
                UnitBarcode VARCHAR(64) NOT NULL COMMENT 'the code on the sticker',
                ItemBarcode VARCHAR(45) NOT NULL COMMENT 'the product barcode it resolves to',
                products_PDID INT(11) NOT NULL COMMENT 'the product it was printed for',
                shop_SHID INT(11) NOT NULL COMMENT 'the shop that printed it',
                ProducedDate DATE NOT NULL COMMENT 'the day this unit was made',
                SeqNo INT(11) NOT NULL COMMENT 'the serial inside its number series',
                UnitStat TINYINT(4) NOT NULL DEFAULT 1 COMMENT '1 printed, 2 received, 0 voided',
                PrintRef VARCHAR(20) NOT NULL COMMENT 'the print job, so a reprint finds it again',
                PrintedAt DATETIME NOT NULL,
                PrintedBy INT(11) NOT NULL,
                PrintCount INT(11) NOT NULL DEFAULT 1,
                LastPrintedAt DATETIME DEFAULT NULL,
                GRNHeader_GHID INT(11) DEFAULT NULL,
                grndetails_GDID INT(11) DEFAULT NULL,
                ReceivedAt DATETIME DEFAULT NULL,
                ReceivedBy INT(11) DEFAULT NULL,
                PRIMARY KEY (PUID),
                UNIQUE KEY uq_productunits_code (UnitBarcode),
                KEY idx_productunits_item (ItemBarcode),
                KEY idx_productunits_produced (products_PDID, ProducedDate),
                KEY idx_productunits_print (shop_SHID, PrintRef),
                KEY idx_productunits_grn (GRNHeader_GHID)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        foreach (self::SETTINGS_COLUMNS as $column => $sql) {
            $this->step('barcodesettings.' . $column, !$this->hasColumn('barcodesettings', $column), $sql);
        }//each column

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
}//UnitBarcodesMigration
