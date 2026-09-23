<?php
use PHPUnit\Framework\TestCase;

//Reaches the application's shared PDO handle (Dbh::connect() is protected), so the tests use
//exactly the connection the code under test uses.
class TestDbh extends Dbh
{
    public function pdo()
    {
        return $this->connect();
    }//pdo
}//TestDbh

//Base class for tests that need the database. Before every test all tables of the test
//database are dropped and tests/fixtures/legacy_schema.sql and stock_schema.sql are loaded,
//so each test starts from the same known state.
abstract class DatabaseTestCase extends TestCase
{
    protected PDO $pdo;

    //run the shop access, scanner upload and customer orders migrations after loading the schema (the
    //migrations' own tests turn it off)
    protected $migrate = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = (new TestDbh())->pdo();
        $this->resetSchema();
        if ($this->migrate) {
            (new ShopAccessMigration($this->pdo))->run();
            (new ScanUploadMigration($this->pdo))->run();
            (new CustomerOrdersMigration($this->pdo))->run();
            (new ShopPermissionsMigration($this->pdo))->run();
        }//migrated schema
    }//setUp

    private function resetSchema()
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
            $this->pdo->exec('DROP TABLE `' . $table . '`');
        }//each table

        //tables may name tables created after them (as in a mysqldump); the keys apply from here on
        foreach (['legacy_schema.sql', 'stock_schema.sql'] as $file) {
            $sql = preg_replace('/^--.*$/m', '', file_get_contents(__DIR__ . '/fixtures/' . $file));
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
                $this->pdo->exec($statement);
            }//each statement
        }//each schema file
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }//resetSchema

    //insert one row and return its auto increment id
    protected function insert($table, array $row)
    {
        $columns = array_keys($row);
        $sql = 'INSERT INTO `' . $table . '` (`' . implode('`, `', $columns) . '`) VALUES ('
            . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $this->pdo->prepare($sql)->execute(array_values($row));
        return (int) $this->pdo->lastInsertId();
    }//insert

    protected function createCompany(array $overrides = [])
    {
        return $this->insert('company', $overrides + [
            'ComName' => 'Test Company',
            'ComStat' => 1,
            'ComExpireDate' => date('Y-m-d', strtotime('+1 year')),
            'CompanyType_CTID' => 1,
        ]);
    }//createCompany

    protected function createShop($company_id, array $overrides = [])
    {
        return $this->insert('shop', $overrides + [
            'ShopName' => 'Test Shop',
            'WholesaleShop' => 0,
            'RetailShop' => 1,
            'is_prescription' => 0,
            'ShopStat' => 1,
            'Company_CMID' => $company_id,
            'StockTypes_STID' => 1,
            'emailAddress' => 'shop@example.com',
        ]);
    }//createShop

    protected function createRole($name, $active = true)
    {
        return $this->insert('userroles', ['UserRoleName' => $name, 'ur_status' => $active ? 1 : 0, 'added_by' => 1]);
    }//createRole

    protected function createUser($name, $password, $role_id, array $overrides = [])
    {
        return $this->insert('user', $overrides + [
            'UserName' => $name,
            'UserEmail' => strtolower($name) . '@example.com',
            'UserPwd' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]),
            'UserStat' => 1,
            'UserRoles_URID' => $role_id,
            'UserType' => 0,
        ]);
    }//createUser

    //give a role rights on a feature IN ONE SHOP, e.g. grant($role, 1, ['is_view'], $shop)
    protected function grant($role_id, $feature_id, array $rights, $shop_id)
    {
        $flags = ['is_create' => 0, 'is_edit' => 0, 'is_view' => 0, 'is_delete' => 0, 'is_verify' => 0, 'is_print' => 0];
        foreach ($rights as $right) {
            $flags[$right] = 1;
        }//each right
        return $this->insert('userroleaccess', $flags + ['UserRolls_URID' => $role_id,
            'shop_SHID' => $shop_id, 'SysFeatures_SFID' => $feature_id]);
    }//grant

    //show a menu module to a role IN ONE SHOP
    protected function allowModule($role_id, $module_id, $shop_id)
    {
        return $this->insert('usermoduleaccess', ['SysModules_SMID' => $module_id,
            'UserRoles_URID' => $role_id, 'shop_SHID' => $shop_id]);
    }//allowModule

    //assign a user to a shop with a role (needs the migrated schema)
    protected function assign($user_id, $shop_id, $role_id, $active = true)
    {
        return $this->insert('shopusers', [
            'shop_SHID' => $shop_id,
            'user_USID' => $user_id,
            'UserRoles_URID' => $role_id,
            'is_active' => $active ? 1 : 0,
        ]);
    }//assign

    protected function createProduct($shop_id, $barcode, $name, array $overrides = [])
    {
        return $this->insert('products', $overrides + [
            'Barcode' => $barcode,
            'ItemName' => $name,
            'ProdPurchasePrice' => 100,
            'ProdSellPrice' => 150,
            'ProductStat' => 1,
            'ItemType' => 'P',
            'user_USID' => 1,
            'Subcategories_SCID' => 1,
            'shop_SHID' => $shop_id,
            'PurchaseUnit' => 1,
            'UnitConversion' => 1,
            'SellingUnit' => 1,
            'prodFlatDiscount' => 0,
        ]);
    }//createProduct

    protected function createGrn($shop_id, $user_id, $stat = 0)
    {
        return $this->insert('grnheader', [
            'GRNHeaderNo' => 'GRN_TEST', 'EffectiveDate' => date('Y-m-d'), 'InvoiceNo' => 'INV', 'ItemCount' => 0,
            'TotalPurchasePrice' => 0, 'TotalSellPrice' => 0, 'GRNStat' => $stat, 'user_USID' => $user_id,
            'shop_SHID' => $shop_id, 'Suppliers_SPID' => 1, 'SuppPayment' => 0, 'SuppBalance' => 0,
            'excessAmount' => 0, 'refference' => '',
        ]);
    }//createGrn

    //a stock batch: an inventory row with its price history row; returns the inventory id
    protected function addStock($product_id, $shop_id, $qty, $batch, $purchase, $selling, array $overrides = [])
    {
        $inventory_id = $this->insert('inventory', [
            'CurrentQty' => $qty, 'BillQty' => 0, 'ReturnQty' => 0, 'TransferInQty' => 0, 'TransferOutQty' => 0,
            'products_PDID' => $product_id, 'shop_SHID' => $shop_id, 'RackID' => 1, 'BatchID' => $batch,
        ]);
        $this->insert('pricehistory', $overrides + [
            'ProductID' => $product_id, 'VariationID' => 0, 'EffectiveDate' => date('Y-m-d'),
            'PurchasePrice' => $purchase, 'SellingPrice' => $selling, 'labelPrice' => $selling,
            'MnfDate' => null, 'ExpDate' => null, 'BatchID' => $batch, 'Inventory_INID' => $inventory_id,
        ]);
        return $inventory_id;
    }//addStock

    protected function createTransfer($from_shop_id, $to_shop_id, $user_id, $stat = 0)
    {
        return $this->insert('transferheader', [
            'TransferNo' => 'TR_TEST', 'EffectiveDate' => date('Y-m-d'), 'TransferFrom' => $from_shop_id,
            'TransferTo' => $to_shop_id, 'TransferTotalCount' => 0, 'TransferTotalAmount' => 0,
            'TransferStat' => $stat, 'shop_SHID' => $from_shop_id, 'user_USID' => $user_id,
        ]);
    }//createTransfer
}//DatabaseTestCase
