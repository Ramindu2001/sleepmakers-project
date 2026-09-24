<?php
//db/warehouse_fulfilment_migration.php: the storage a POS-created warehouse order needs - where
//it goes, what each line is doing, and the dispatches that take it to the customer.
final class WarehouseFulfilmentMigrationTest extends DatabaseTestCase
{
    protected $migrate = false;

    //the migrations this one builds on, then this one
    private function migrate()
    {
        $this->earlier();
        return (new WarehouseFulfilmentMigration($this->pdo))->run();
    }//migrate

    private function earlier()
    {
        (new ShopAccessMigration($this->pdo))->run();
        (new ScanUploadMigration($this->pdo))->run();
        (new CustomerOrdersMigration($this->pdo))->run();
        (new ShopPermissionsMigration($this->pdo))->run();
        (new UnitBarcodesMigration($this->pdo))->run();
    }//earlier

    private function columns($table)
    {
        $stmt = $this->pdo->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?;");
        $stmt->execute([$table]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }//columns

    public function test_the_order_carries_its_invoice_and_where_it_goes()
    {
        $this->migrate();

        foreach (['InvoiceHeader_IHID', 'customers_CTID', 'DeliverTo', 'DeliveryAddress',
            'DeliveryPhone', 'DeliveryNote'] as $column) {
            $this->assertContains($column, $this->columns('customerorders'), $column . ' is missing');
        }//each column
    }//the order carries its invoice

    public function test_a_line_carries_its_own_state_and_both_product_ids()
    {
        $this->migrate();

        foreach (['LineStat', 'SupplierProductID', 'DispatchedQty', 'DeliveredQty',
            'UnitPrice', 'LineTotal', 'CancelReason'] as $column) {
            $this->assertContains($column, $this->columns('customerorderlines'), $column . ' is missing');
        }//each column
    }//a line carries its own state

    public function test_dispatches_and_their_scanned_lines_exist()
    {
        $this->migrate();

        $this->assertNotEmpty($this->columns('orderdispatches'));
        $this->assertNotEmpty($this->columns('orderdispatchlines'));
    }//dispatches exist

    public function test_a_unit_remembers_the_dispatch_it_left_on()
    {
        $this->migrate();

        foreach (['orderdispatches_DSID', 'DispatchedAt', 'DispatchedBy'] as $column) {
            $this->assertContains($column, $this->columns('productunits'), $column . ' is missing');
        }//each column
    }//a unit remembers its dispatch

    public function test_the_same_sticker_cannot_be_scanned_twice_into_one_dispatch()
    {
        $this->migrate();
        $row = ['orderdispatches_DSID' => 1, 'customerorderlines_COLID' => 1, 'products_PDID' => 1,
            'Qty' => 1, 'UnitBarcode' => 'COO0000126090001', 'ScannedAt' => date('Y-m-d H:i:s'), 'ScannedBy' => 1];
        $this->insert('orderdispatchlines', $row);

        $this->expectException(PDOException::class);
        $this->insert('orderdispatchlines', $row);
    }//the same sticker twice

    public function test_a_product_barcode_may_be_scanned_many_times_into_one_dispatch()
    {
        $this->migrate();
        $row = ['orderdispatches_DSID' => 1, 'customerorderlines_COLID' => 1, 'products_PDID' => 1,
            'Qty' => 1, 'UnitBarcode' => null, 'ScannedAt' => date('Y-m-d H:i:s'), 'ScannedBy' => 1];

        $this->insert('orderdispatchlines', $row);
        $this->insert('orderdispatchlines', $row);

        $this->assertSame(2, (int) $this->pdo->query('SELECT COUNT(*) FROM orderdispatchlines')->fetchColumn());
    }//a product barcode many times

    public function test_a_dispatch_number_is_unique_within_its_shop_only()
    {
        $this->migrate();
        $row = ['DispatchNo' => 'DS_000001', 'customerorders_COID' => 1, 'shop_SHID' => 1,
            'CreatedBy' => 1, 'CreatedAt' => date('Y-m-d H:i:s')];
        $this->insert('orderdispatches', $row);
        $this->insert('orderdispatches', ['shop_SHID' => 2] + $row);

        $this->expectException(PDOException::class);
        $this->insert('orderdispatches', $row);
    }//a dispatch number is unique per shop

    public function test_old_order_statuses_are_carried_over()
    {
        $this->earlier();
        $company = $this->createCompany();
        $shop = $this->createShop($company);
        foreach ([1, 2, 3, 4, 5] as $stat) {
            $this->insert('customerorders', ['OrderNo' => 'CO_00000' . $stat, 'shop_SHID' => $shop,
                'SupplierShopID' => $shop, 'CustName' => 'x', 'CustPhone' => '1', 'OrderStat' => $stat,
                'user_USID' => 1, 'CreatedAt' => date('Y-m-d H:i:s')]);
        }//one order in each old status

        (new WarehouseFulfilmentMigration($this->pdo))->run();

        //1 requested -> pending and 2 accepted -> preparing keep their number; rejected and
        //cancelled both become cancelled; handed over becomes completed
        $this->assertSame([1, 2, 6, 6, 5], array_map('intval',
            $this->pdo->query('SELECT OrderStat FROM customerorders ORDER BY COID')->fetchAll(PDO::FETCH_COLUMN)));
    }//old statuses carried over

    public function test_lines_already_given_at_the_shop_start_as_given()
    {
        $this->earlier();
        $company = $this->createCompany();
        $shop = $this->createShop($company);
        $order = $this->insert('customerorders', ['OrderNo' => 'CO_000001', 'shop_SHID' => $shop,
            'SupplierShopID' => $shop, 'CustName' => 'x', 'CustPhone' => '1', 'OrderStat' => 1,
            'user_USID' => 1, 'CreatedAt' => date('Y-m-d H:i:s')]);
        foreach (['GIVEN', 'WAREHOUSE'] as $i => $source) {
            $this->insert('customerorderlines', ['customerorders_COID' => $order, 'LineSource' => $source,
                'Description' => $source, 'Qty' => 1, 'SortOrder' => $i + 1]);
        }//one line of each kind

        (new WarehouseFulfilmentMigration($this->pdo))->run();

        $this->assertSame([1, 2], array_map('intval',
            $this->pdo->query('SELECT LineStat FROM customerorderlines ORDER BY COLID')->fetchAll(PDO::FETCH_COLUMN)));
    }//given lines start as given

    public function test_running_it_twice_changes_nothing()
    {
        $this->migrate();

        $second = (new WarehouseFulfilmentMigration($this->pdo))->run();

        foreach ($second as $line) {
            $this->assertStringStartsWith('[skip]', $line, $line);
        }//every step
    }//running it twice
}//WarehouseFulfilmentMigrationTest
