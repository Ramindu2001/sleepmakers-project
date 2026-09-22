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
//database are dropped and tests/fixtures/legacy_schema.sql is loaded, so each test starts
//from the same known state.
abstract class DatabaseTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = (new TestDbh())->pdo();
        $this->resetSchema();
    }//setUp

    private function resetSchema()
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
            $this->pdo->exec('DROP TABLE `' . $table . '`');
        }//each table
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        $sql = preg_replace('/^--.*$/m', '', file_get_contents(__DIR__ . '/fixtures/legacy_schema.sql'));
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            $this->pdo->exec($statement);
        }//each statement
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

    //give a role rights on a feature, e.g. grant($role, 1, ['is_view'])
    protected function grant($role_id, $feature_id, array $rights)
    {
        $flags = ['is_create' => 0, 'is_edit' => 0, 'is_view' => 0, 'is_delete' => 0, 'is_verify' => 0, 'is_print' => 0];
        foreach ($rights as $right) {
            $flags[$right] = 1;
        }//each right
        return $this->insert('userroleaccess', $flags + ['UserRolls_URID' => $role_id, 'SysFeatures_SFID' => $feature_id]);
    }//grant

    protected function allowModule($role_id, $module_id)
    {
        return $this->insert('usermoduleaccess', ['SysModules_SMID' => $module_id, 'UserRoles_URID' => $role_id]);
    }//allowModule
}//DatabaseTestCase
