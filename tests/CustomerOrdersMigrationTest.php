<?php
final class CustomerOrdersMigrationTest extends DatabaseTestCase
{
    protected $migrate = false; //runs the migration itself

    public function test_creates_the_tables_the_link_column_and_the_right_once()
    {
        $this->pdo->exec("INSERT INTO sysfeatures (SFID, FeatureName, SystemModules_SMID) VALUES (72, 'Batch wise sale', 4)");

        $first = (new CustomerOrdersMigration($this->pdo))->run();
        $second = (new CustomerOrdersMigration($this->pdo))->run();

        $this->assertSame(['[ok] customerorders table', '[ok] customerorderlines table', '[ok] transferheader.CustomerOrderID column',
            '[ok] role right "Customer Orders" (feature 101, Orders)'], $first);
        $this->assertSame(['[skip] customerorders table - already in place', '[skip] customerorderlines table - already in place',
            '[skip] transferheader.CustomerOrderID column - already in place', '[skip] role right "Customer Orders" - already in place (feature 101)'], $second);
        $this->assertSame([['SFID' => 101, 'SystemModules_SMID' => 2]],
            $this->pdo->query("SELECT SFID, SystemModules_SMID FROM sysfeatures WHERE FeatureName = 'Customer Orders'")->fetchAll(PDO::FETCH_ASSOC));
        $this->assertSame('YES', $this->pdo->query("SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'transferheader' AND COLUMN_NAME = 'CustomerOrderID'")->fetchColumn());
    }

    public function test_the_right_takes_the_next_id_when_ids_already_pass_100()
    {
        $this->pdo->exec("INSERT INTO sysfeatures (SFID, FeatureName, SystemModules_SMID) VALUES (150, 'Something', 4)");
        (new CustomerOrdersMigration($this->pdo))->run();
        $this->assertSame(151, (int)$this->pdo->query("SELECT SFID FROM sysfeatures WHERE FeatureName = 'Customer Orders'")->fetchColumn());
    }
}
