<?php
//db/unit_barcodes_migration.php: the table that holds one row per printed unit, and the
//shop's rules for building a unit code.
final class UnitBarcodesMigrationTest extends DatabaseTestCase
{
    protected $migrate = false; //this test runs the migration itself

    private function columns($table)
    {
        $stmt = $this->pdo->prepare("SELECT COLUMN_NAME, COLUMN_DEFAULT FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?;");
        $stmt->execute([$table]);
        //MariaDB reports a string default with its quotes: ''{ITEM}''
        return array_map(function ($default) { return trim((string) $default, "'"); }, $stmt->fetchAll(PDO::FETCH_KEY_PAIR));
    }

    public function test_it_creates_the_table_and_the_shop_rules()
    {
        $report = (new UnitBarcodesMigration($this->pdo))->run();

        $units = $this->columns('productunits');
        $this->assertNotEmpty($units);
        foreach (['UnitBarcode', 'ItemBarcode', 'products_PDID', 'shop_SHID', 'ProducedDate', 'SeqNo',
            'UnitStat', 'PrintRef', 'GRNHeader_GHID', 'ReceivedAt'] as $column) {
            $this->assertArrayHasKey($column, $units, $column . ' is missing');
        }

        $settings = $this->columns('barcodesettings');
        $this->assertSame('0', $settings['UnitMode'], 'unit barcodes start switched off');
        $this->assertSame('{ITEM}{YY}{MM}{SEQ}', $settings['UnitPattern']);
        $this->assertSame('4', $settings['UnitSeqLength']);
        $this->assertStringContainsString('[ok] table productunits', implode("\n", $report));
    }

    public function test_running_it_again_does_nothing()
    {
        (new UnitBarcodesMigration($this->pdo))->run();
        $report = (new UnitBarcodesMigration($this->pdo))->run();

        $this->assertSame(5, substr_count(implode("\n", $report), '[skip]'));
        $this->assertStringNotContainsString('[ok]', implode("\n", $report));
    }

    public function test_the_same_unit_code_cannot_be_stored_twice()
    {
        (new UnitBarcodesMigration($this->pdo))->run();
        $row = ['UnitBarcode' => 'COO0000125090001', 'ItemBarcode' => 'COO00001', 'products_PDID' => 1,
            'shop_SHID' => 1, 'ProducedDate' => '2025-09-12', 'SeqNo' => 1, 'PrintRef' => 'UP_000001',
            'PrintedAt' => date('Y-m-d H:i:s'), 'PrintedBy' => 1];
        $this->insert('productunits', $row);

        $this->expectException(PDOException::class);
        $this->insert('productunits', $row);
    }
}
