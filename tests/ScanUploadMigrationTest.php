<?php
final class ScanUploadMigrationTest extends DatabaseTestCase
{
    protected $migrate = false; //runs the migration itself

    public function test_creates_the_scanbatches_table_once()
    {
        $first = (new ScanUploadMigration($this->pdo))->run();
        $second = (new ScanUploadMigration($this->pdo))->run();

        $this->assertSame(['[ok] scanbatches table'], $first);
        $this->assertSame(['[skip] scanbatches table - already in place'], $second);
        $this->assertSame(
            ['SBID', 'DocType', 'DocID', 'shop_SHID', 'user_USID', 'Fingerprint', 'ScanCount', 'LineCount', 'Summary', 'CreatedAt'],
            $this->pdo->query('SHOW COLUMNS FROM scanbatches')->fetchAll(PDO::FETCH_COLUMN)
        );
    }
}
