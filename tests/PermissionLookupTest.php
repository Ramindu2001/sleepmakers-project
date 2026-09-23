<?php
require_once __DIR__ . '/../Includes/barcode_helper.php';

final class PermissionLookupTest extends DatabaseTestCase
{
    private int $warehouse;
    private int $showroom;
    private int $alice;

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $company = $this->createCompany();
        $this->warehouse = $this->createShop($company, ['ShopName' => 'Warehouse']);
        $this->showroom = $this->createShop($company, ['ShopName' => 'Valentino Italy']);
        $storeKeeper = $this->createRole('Store Keeper');
        $cashier = $this->createRole('Cashier');
        $this->grant($storeKeeper, 16, ['is_view', 'is_print'], $this->warehouse);   //Products feature
        $this->alice = $this->createUser('alice', 'x', $cashier);
        $this->assign($this->alice, $this->warehouse, $storeKeeper);
        $this->assign($this->alice, $this->showroom, $cashier);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function test_feature_rights_come_from_the_role_in_the_given_shop()
    {
        $user = new User();
        $inWarehouse = $user->getUserFeatureAccess($this->alice, 16, $this->warehouse);
        $inShowroom = $user->getUserFeatureAccess($this->alice, 16, $this->showroom);

        $this->assertSame(1, $inWarehouse[0]['is_print']);
        $this->assertSame([], $inShowroom);
    }

    public function test_revoked_assignment_has_no_feature_rights()
    {
        $this->pdo->exec('UPDATE shopusers SET is_active = 0 WHERE shop_SHID = ' . $this->warehouse);
        $this->assertSame([], (new User())->getUserFeatureAccess($this->alice, 16, $this->warehouse));
    }

    public function test_barcode_rights_follow_the_current_shop()
    {
        $db = new DBTransactions();

        $_SESSION['shop_id'] = $this->warehouse;
        $this->assertTrue(bcUserCanPrint($db, $this->alice));

        $_SESSION['shop_id'] = $this->showroom;
        $this->assertFalse(bcUserCanPrint($db, $this->alice));

        unset($_SESSION['shop_id']);
        $this->assertFalse(bcUserCanPrint($db, $this->alice));
    }

    public function test_remember_me_uses_the_same_access_rule()
    {
        require_once __DIR__ . '/../Includes/remember_me.php';
        $this->pdo->exec('UPDATE shopusers SET is_active = 0 WHERE shop_SHID = ' . $this->showroom);

        $this->assertTrue((new RememberMe())->canAccessShop($this->alice, $this->warehouse));
        $this->assertFalse((new RememberMe())->canAccessShop($this->alice, $this->showroom));
    }

    // ---- the same role, held in both shops, is ticked separately in each ---------------------

    //one person, one role, both shops: what they may do is whatever that role is ticked for HERE
    private function bothShops($name = 'Store Keeper')
    {
        $role = $this->createRole($name);
        $bob = $this->createUser('bob', 'x', $role);
        $this->assign($bob, $this->warehouse, $role);
        $this->assign($bob, $this->showroom, $role);
        return [$role, $bob];
    }//bothShops

    public function test_one_role_in_two_shops_carries_the_rights_of_each_shop_on_its_own()
    {
        [$role, $bob] = $this->bothShops();
        $this->grant($role, 16, ['is_view', 'is_print'], $this->warehouse);
        $user = new User();

        $this->assertSame(1, $user->getUserFeatureAccess($bob, 16, $this->warehouse)[0]['is_print']);
        $this->assertSame([], $user->getUserFeatureAccess($bob, 16, $this->showroom));
    }//test one role in two shops carries the rights of each shop on its own

    public function test_the_pages_read_the_ticks_of_the_shop_that_is_open()
    {
        [$role] = $this->bothShops();
        $this->grant($role, 16, ['is_view'], $this->warehouse);
        $user = new User();

        $_SESSION['shop_id'] = $this->warehouse;
        $this->assertSame(1, $user->userAcces($role, 16)[0]['is_view']);
        $this->assertSame(1, $user->getRoleViewAccess($role, 16));
        $this->assertCount(1, $user->getUserRoleFeatureAccess($role, 16));

        $_SESSION['shop_id'] = $this->showroom;
        //no row at all in this shop: the pages are told every right is off, never nothing
        $this->assertSame([User::NO_RIGHTS], $user->userAcces($role, 16));
        $this->assertSame(0, $user->getRoleViewAccess($role, 16));
        $this->assertSame([User::NO_RIGHTS], $user->getUserRoleFeatureAccess($role, 16));
    }//test the pages read the ticks of the shop that is open

    public function test_the_menu_of_a_role_is_the_menu_of_this_shop()
    {
        [$role] = $this->bothShops();
        $this->allowModule($role, 2, $this->warehouse);
        $user = new User();

        $_SESSION['shop_id'] = $this->warehouse;
        $this->assertSame([2], array_map('intval', array_column($user->getUserRoleModuleAccess($role), 'SysModules_SMID')));

        $_SESSION['shop_id'] = $this->showroom;
        $this->assertSame([], $user->getUserRoleModuleAccess($role));
    }//test the menu of a role is the menu of this shop

    public function test_barcode_rights_are_ticked_per_shop_as_well()
    {
        [$role, $bob] = $this->bothShops();
        $this->grant($role, 16, ['is_print'], $this->warehouse);
        $db = new DBTransactions();

        $_SESSION['shop_id'] = $this->warehouse;
        $this->assertTrue(bcUserCanPrint($db, $bob));

        $_SESSION['shop_id'] = $this->showroom;
        $this->assertFalse(bcUserCanPrint($db, $bob));
    }//test barcode rights are ticked per shop as well
}
