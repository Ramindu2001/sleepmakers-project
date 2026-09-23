<?php
//db/shop_permissions_migration.php: the ticks behind a role move from "the role" to
//"the role in one shop". Nobody's rights may change on the day of the upgrade.
final class ShopPermissionsMigrationTest extends DatabaseTestCase
{
    protected $migrate = false; //these tests run the migrations themselves

    private function migrate()
    {
        (new ShopAccessMigration($this->pdo))->run();
        return (new ShopPermissionsMigration($this->pdo))->run();
    }//run

    private function grants()
    {
        return $this->pdo->query('SELECT UserRolls_URID, shop_SHID, SysFeatures_SFID, is_view, is_print
            FROM userroleaccess ORDER BY shop_SHID, SysFeatures_SFID')->fetchAll(PDO::FETCH_ASSOC);
    }//grants

    private function modules()
    {
        return $this->pdo->query('SELECT UserRoles_URID, shop_SHID, SysModules_SMID
            FROM usermoduleaccess ORDER BY shop_SHID, SysModules_SMID')->fetchAll(PDO::FETCH_ASSOC);
    }//modules

    public function test_every_role_keeps_its_ticks_in_every_shop()
    {
        $company = $this->createCompany();
        $warehouse = $this->createShop($company, ['ShopName' => 'Warehouse']);
        $showroom = $this->createShop($company, ['ShopName' => 'Valentino Italy']);
        $cashier = $this->createRole('Cashier');
        $this->insert('userroleaccess', ['UserRolls_URID' => $cashier, 'SysFeatures_SFID' => 16,
            'is_view' => 1, 'is_print' => 1, 'is_create' => 0, 'is_edit' => 0, 'is_delete' => 0, 'is_verify' => 0]);

        $this->migrate();

        $this->assertSame([
            ['UserRolls_URID' => $cashier, 'shop_SHID' => $warehouse, 'SysFeatures_SFID' => 16, 'is_view' => 1, 'is_print' => 1],
            ['UserRolls_URID' => $cashier, 'shop_SHID' => $showroom, 'SysFeatures_SFID' => 16, 'is_view' => 1, 'is_print' => 1],
        ], $this->grants());
    }//test every role keeps its ticks in every shop

    public function test_menu_modules_are_copied_per_shop_too()
    {
        $company = $this->createCompany();
        $warehouse = $this->createShop($company);
        $showroom = $this->createShop($company);
        $cashier = $this->createRole('Cashier');
        $this->insert('usermoduleaccess', ['UserRoles_URID' => $cashier, 'SysModules_SMID' => 2]);

        $this->migrate();

        $this->assertSame([
            ['UserRoles_URID' => $cashier, 'shop_SHID' => $warehouse, 'SysModules_SMID' => 2],
            ['UserRoles_URID' => $cashier, 'shop_SHID' => $showroom, 'SysModules_SMID' => 2],
        ], $this->modules());
    }//test menu modules are copied per shop too

    public function test_running_it_again_changes_nothing()
    {
        $company = $this->createCompany();
        $this->createShop($company);
        $this->createShop($company);
        $cashier = $this->createRole('Cashier');
        $this->insert('userroleaccess', ['UserRolls_URID' => $cashier, 'SysFeatures_SFID' => 16, 'is_view' => 1, 'is_print' => 0]);
        $this->insert('usermoduleaccess', ['UserRoles_URID' => $cashier, 'SysModules_SMID' => 2]);

        $this->migrate();
        $after_first = [$this->grants(), $this->modules()];
        (new ShopPermissionsMigration($this->pdo))->run();

        $this->assertSame($after_first, [$this->grants(), $this->modules()]);
    }//test running it again changes nothing

    public function test_ticks_already_set_for_a_shop_are_left_alone()
    {
        $company = $this->createCompany();
        $warehouse = $this->createShop($company);
        $cashier = $this->createRole('Cashier');
        (new ShopAccessMigration($this->pdo))->run();
        (new ShopPermissionsMigration($this->pdo))->run(); //the column is there from here on

        //the shop's own tick, and a leftover shared row from an older page
        $this->insert('userroleaccess', ['UserRolls_URID' => $cashier, 'shop_SHID' => $warehouse,
            'SysFeatures_SFID' => 16, 'is_view' => 1, 'is_print' => 0]);
        $this->insert('userroleaccess', ['UserRolls_URID' => $cashier, 'shop_SHID' => 0,
            'SysFeatures_SFID' => 16, 'is_view' => 0, 'is_print' => 1]);

        (new ShopPermissionsMigration($this->pdo))->run();

        $this->assertSame([['UserRolls_URID' => $cashier, 'shop_SHID' => $warehouse,
            'SysFeatures_SFID' => 16, 'is_view' => 1, 'is_print' => 0]], $this->grants());
    }//test ticks already set for a shop are left alone

    public function test_nothing_is_thrown_away_when_there_is_no_shop_yet()
    {
        $cashier = $this->createRole('Cashier');
        $this->insert('userroleaccess', ['UserRolls_URID' => $cashier, 'SysFeatures_SFID' => 16, 'is_view' => 1, 'is_print' => 0]);

        $report = $this->migrate();

        $this->assertSame([['UserRolls_URID' => $cashier, 'shop_SHID' => 0, 'SysFeatures_SFID' => 16,
            'is_view' => 1, 'is_print' => 0]], $this->grants());
        $this->assertStringContainsString('no shops', implode("\n", $report));
    }//test nothing is thrown away when there is no shop yet

    public function test_duplicate_ticks_are_merged_into_the_one_the_pages_read()
    {
        $company = $this->createCompany();
        $warehouse = $this->createShop($company);
        $cashier = $this->createRole('Cashier');
        //the older row is the one every permission check reads today
        $this->insert('userroleaccess', ['UserRolls_URID' => $cashier, 'SysFeatures_SFID' => 16, 'is_view' => 1, 'is_print' => 0]);
        $this->insert('userroleaccess', ['UserRolls_URID' => $cashier, 'SysFeatures_SFID' => 16, 'is_view' => 0, 'is_print' => 1]);

        $this->migrate();

        $this->assertSame([['UserRolls_URID' => $cashier, 'shop_SHID' => $warehouse,
            'SysFeatures_SFID' => 16, 'is_view' => 1, 'is_print' => 0]], $this->grants());
    }//test duplicate ticks are merged into the one the pages read

    public function test_one_row_per_role_shop_and_feature_from_now_on()
    {
        $company = $this->createCompany();
        $warehouse = $this->createShop($company);
        $cashier = $this->createRole('Cashier');
        $this->migrate();
        $this->insert('userroleaccess', ['UserRolls_URID' => $cashier, 'shop_SHID' => $warehouse, 'SysFeatures_SFID' => 16, 'is_view' => 1]);

        $this->expectException(PDOException::class);
        $this->insert('userroleaccess', ['UserRolls_URID' => $cashier, 'shop_SHID' => $warehouse, 'SysFeatures_SFID' => 16, 'is_view' => 0]);
    }//test one row per role shop and feature from now on
}//ShopPermissionsMigrationTest
