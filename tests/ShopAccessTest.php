<?php
final class ShopAccessTest extends DatabaseTestCase
{
    private ShopAccess $access;
    private int $company;
    private int $warehouse;
    private int $showroom;
    private int $storeKeeper;
    private int $cashier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->access = new ShopAccess();
        $this->company = $this->createCompany(['ComName' => 'Sleep Makers']);
        $this->warehouse = $this->createShop($this->company, ['ShopName' => 'Warehouse']);
        $this->showroom = $this->createShop($this->company, ['ShopName' => 'Valentino Italy']);
        $this->storeKeeper = $this->createRole('Store Keeper');
        $this->cashier = $this->createRole('Cashier');
    }

    private function admin()
    {
        return $this->createUser('admin', 'admin-pass', $this->cashier, ['UserType' => 1]);
    }

    // ---- canAccessShop -------------------------------------------------------------------

    public function test_admin_enters_every_shop_without_an_assignment()
    {
        $admin = $this->admin();
        $this->assertTrue($this->access->canAccessShop($admin, $this->warehouse));
        $this->assertTrue($this->access->canAccessShop($admin, $this->showroom));
    }

    public function test_user_enters_only_shops_assigned_to_them()
    {
        $alice = $this->createUser('alice', 'x', $this->cashier);
        $this->assign($alice, $this->showroom, $this->cashier);

        $this->assertTrue($this->access->canAccessShop($alice, $this->showroom));
        $this->assertFalse($this->access->canAccessShop($alice, $this->warehouse));
    }

    public function test_revoked_assignment_keeps_the_user_out()
    {
        $alice = $this->createUser('alice', 'x', $this->cashier);
        $this->assign($alice, $this->showroom, $this->cashier, false);

        $this->assertFalse($this->access->canAccessShop($alice, $this->showroom));
    }

    public function test_inactive_shop_keeps_users_out_but_not_admins()
    {
        $closed = $this->createShop($this->company, ['ShopStat' => 0]);
        $alice = $this->createUser('alice', 'x', $this->cashier);
        $this->assign($alice, $closed, $this->cashier);

        $this->assertFalse($this->access->canAccessShop($alice, $closed));
        $this->assertTrue($this->access->canAccessShop($this->admin(), $closed));
    }

    public function test_inactive_role_keeps_the_user_out()
    {
        $retired = $this->createRole('Retired', false);
        $alice = $this->createUser('alice', 'x', $this->cashier);
        $this->assign($alice, $this->showroom, $retired);

        $this->assertFalse($this->access->canAccessShop($alice, $this->showroom));
    }

    public function test_inactive_user_is_kept_out_even_as_admin()
    {
        $alice = $this->createUser('alice', 'x', $this->cashier, ['UserStat' => 0]);
        $this->assign($alice, $this->showroom, $this->cashier);
        $boss = $this->createUser('boss', 'x', $this->cashier, ['UserType' => 1, 'UserStat' => 0]);

        $this->assertFalse($this->access->canAccessShop($alice, $this->showroom));
        $this->assertFalse($this->access->canAccessShop($boss, $this->showroom));
    }

    public function test_rejects_ids_that_are_not_positive_integers()
    {
        $this->assertFalse($this->access->canAccessShop('1 OR 1=1', $this->showroom));
        $this->assertFalse($this->access->canAccessShop($this->admin(), 0));
        $this->assertFalse($this->access->canAccessShop($this->admin(), 999));
    }

    // ---- getShopRoleId -------------------------------------------------------------------

    public function test_the_same_user_holds_a_different_role_in_each_shop()
    {
        $alice = $this->createUser('alice', 'x', $this->cashier);
        $this->assign($alice, $this->warehouse, $this->storeKeeper);
        $this->assign($alice, $this->showroom, $this->cashier);

        $this->assertSame($this->storeKeeper, $this->access->getShopRoleId($alice, $this->warehouse));
        $this->assertSame($this->cashier, $this->access->getShopRoleId($alice, $this->showroom));
    }

    public function test_no_role_for_admins_or_without_access()
    {
        $alice = $this->createUser('alice', 'x', $this->cashier);
        $this->assign($alice, $this->showroom, $this->cashier, false);

        $this->assertNull($this->access->getShopRoleId($this->admin(), $this->showroom));
        $this->assertNull($this->access->getShopRoleId($alice, $this->showroom));
        $this->assertNull($this->access->getShopRoleId($alice, $this->warehouse));
    }

    // ---- getSelectableShops / hasInactiveRoleAssignment ------------------------------------

    public function test_user_is_offered_only_the_shops_they_may_enter()
    {
        $retired = $this->createRole('Retired', false);
        $outlet = $this->createShop($this->company, ['ShopName' => 'Outlet']);
        $alice = $this->createUser('alice', 'x', $this->cashier);
        $this->assign($alice, $this->warehouse, $this->storeKeeper);
        $this->assign($alice, $this->showroom, $this->cashier, false);
        $this->assign($alice, $outlet, $retired);

        $shops = $this->access->getSelectableShops($alice);

        $this->assertSame(['Warehouse'], array_column($shops, 'ShopName'));
        $this->assertSame('Sleep Makers', $shops[0]['ComName']);
        $this->assertArrayHasKey('ComExpireDate', $shops[0]);
        $this->assertArrayHasKey('ComStat', $shops[0]);
    }

    public function test_admin_is_offered_every_shop()
    {
        $this->createShop($this->company, ['ShopName' => 'Closed', 'ShopStat' => 0]);
        $shops = $this->access->getSelectableShops($this->admin());
        $this->assertSame(['Warehouse', 'Valentino Italy', 'Closed'], array_column($shops, 'ShopName'));
    }

    public function test_inactive_user_is_offered_nothing()
    {
        $alice = $this->createUser('alice', 'x', $this->cashier, ['UserStat' => 0]);
        $this->assign($alice, $this->showroom, $this->cashier);
        $this->assertSame([], $this->access->getSelectableShops($alice));
    }

    public function test_tells_an_inactive_role_apart_from_no_assignment()
    {
        $retired = $this->createRole('Retired', false);
        $alice = $this->createUser('alice', 'x', $this->cashier);
        $bob = $this->createUser('bob', 'x', $this->cashier);
        $this->assign($alice, $this->showroom, $retired);

        $this->assertTrue($this->access->hasInactiveRoleAssignment($alice));
        $this->assertFalse($this->access->hasInactiveRoleAssignment($bob));
    }

    // ---- authenticate --------------------------------------------------------------------

    public function test_valid_credentials_with_access_sign_in()
    {
        $alice = $this->createUser('alice', 'correct horse', $this->cashier);
        $this->assign($alice, $this->showroom, $this->cashier);

        $result = $this->access->authenticate('alice', 'correct horse', $this->showroom);

        $this->assertTrue($result['ok']);
        $this->assertNull($result['error']);
        $this->assertSame($alice, $result['user']['USID']);
    }

    public function test_wrong_password_and_unknown_user_get_the_same_answer()
    {
        $alice = $this->createUser('alice', 'correct horse', $this->cashier);
        $this->assign($alice, $this->showroom, $this->cashier);

        $this->assertSame(ShopAccess::ERR_BAD_CREDENTIALS, $this->access->authenticate('alice', 'wrong', $this->showroom)['error']);
        $this->assertSame(ShopAccess::ERR_BAD_CREDENTIALS, $this->access->authenticate('nobody', 'x', $this->showroom)['error']);
        $this->assertSame(ShopAccess::ERR_BAD_CREDENTIALS, $this->access->authenticate('alice', '', $this->showroom)['error']);
        $this->assertSame(ShopAccess::ERR_BAD_CREDENTIALS, $this->access->authenticate(null, null, $this->showroom)['error']);
    }

    public function test_inactive_account_is_refused()
    {
        $alice = $this->createUser('alice', 'pw', $this->cashier, ['UserStat' => 0]);
        $this->assign($alice, $this->showroom, $this->cashier);
        $this->assertSame(ShopAccess::ERR_USER_INACTIVE, $this->access->authenticate('alice', 'pw', $this->showroom)['error']);
    }

    public function test_shop_without_access_is_refused()
    {
        $alice = $this->createUser('alice', 'pw', $this->cashier);
        $this->assign($alice, $this->showroom, $this->cashier, false);

        $this->assertSame(ShopAccess::ERR_NO_ACCESS, $this->access->authenticate('alice', 'pw', $this->warehouse)['error']);
        $this->assertSame(ShopAccess::ERR_NO_ACCESS, $this->access->authenticate('alice', 'pw', $this->showroom)['error']);
        $this->assertSame(ShopAccess::ERR_NO_ACCESS, $this->access->authenticate('alice', 'pw', 999)['error']);
        $this->assertSame(ShopAccess::ERR_NO_ACCESS, $this->access->authenticate('alice', 'pw', 'abc')['error']);
    }

    public function test_inactive_role_is_refused()
    {
        $retired = $this->createRole('Retired', false);
        $alice = $this->createUser('alice', 'pw', $this->cashier);
        $this->assign($alice, $this->showroom, $retired);
        $this->assertSame(ShopAccess::ERR_ROLE_INACTIVE, $this->access->authenticate('alice', 'pw', $this->showroom)['error']);
    }

    public function test_closed_company_refuses_users_but_not_admins()
    {
        $expired = $this->createCompany(['ComExpireDate' => date('Y-m-d', strtotime('-1 day'))]);
        $inactive = $this->createCompany(['ComStat' => 0]);
        $oldShop = $this->createShop($expired);
        $offShop = $this->createShop($inactive);
        $alice = $this->createUser('alice', 'pw', $this->cashier);
        $this->assign($alice, $oldShop, $this->cashier);
        $this->assign($alice, $offShop, $this->cashier);
        $this->createUser('boss', 'pw', $this->cashier, ['UserType' => 1]);

        $this->assertSame(ShopAccess::ERR_COMPANY_EXPIRED, $this->access->authenticate('alice', 'pw', $oldShop)['error']);
        $this->assertSame(ShopAccess::ERR_COMPANY_INACTIVE, $this->access->authenticate('alice', 'pw', $offShop)['error']);
        $this->assertTrue($this->access->authenticate('boss', 'pw', $oldShop)['ok']);
    }

    public function test_admin_signs_into_any_shop()
    {
        $this->createUser('boss', 'pw', $this->cashier, ['UserType' => 1]);
        $this->assertTrue($this->access->authenticate('boss', 'pw', $this->warehouse)['ok']);
    }

    // ---- messages and company state -------------------------------------------------------

    public function test_every_error_has_its_message()
    {
        $this->assertSame('Invalid username or password', ShopAccess::errorMessage(ShopAccess::ERR_BAD_CREDENTIALS));
        $this->assertSame('Your account is inactive. Please contact your system admin.', ShopAccess::errorMessage(ShopAccess::ERR_USER_INACTIVE));
        $this->assertSame('You do not have access to this shop.', ShopAccess::errorMessage(ShopAccess::ERR_NO_ACCESS));
        $this->assertSame('Your role in this shop is inactive. Please contact your system admin.', ShopAccess::errorMessage(ShopAccess::ERR_ROLE_INACTIVE));
        $this->assertSame('Company Expired. Please Contact Synnex IT Solutions.', ShopAccess::errorMessage(ShopAccess::ERR_COMPANY_EXPIRED));
        $this->assertSame('Company Inactive. Please Contact Synnex IT Solutions.', ShopAccess::errorMessage(ShopAccess::ERR_COMPANY_INACTIVE));
        $this->assertSame('Oops! Something went wrong', ShopAccess::errorMessage('unknown'));
    }

    // ---- isSuperAdmin ----------------------------------------------------------------------

    public function test_only_an_active_super_admin_is_a_super_admin()
    {
        $admin = $this->admin();
        $alice = $this->createUser('alice', 'x', $this->cashier);
        $retired = $this->createUser('retired-admin', 'x', $this->cashier, ['UserType' => 1, 'UserStat' => 0]);

        $this->assertTrue($this->access->isSuperAdmin($admin));
        $this->assertFalse($this->access->isSuperAdmin($alice));
        $this->assertFalse($this->access->isSuperAdmin($retired));
        $this->assertFalse($this->access->isSuperAdmin(999999));
        $this->assertFalse($this->access->isSuperAdmin(null));
        $this->assertFalse($this->access->isSuperAdmin('1 OR 1=1'));
    }

    // ---- hasFeatureRight -------------------------------------------------------------------

    public function test_feature_rights_come_from_the_role_held_in_that_shop()
    {
        $alice = $this->createUser('alice', 'x', $this->cashier);
        $this->assign($alice, $this->warehouse, $this->storeKeeper);
        $this->assign($alice, $this->showroom, $this->cashier);
        $this->grant($this->storeKeeper, 2, ['is_edit'], $this->warehouse);
        $this->grant($this->cashier, 2, ['is_view'], $this->showroom);

        $this->assertTrue($this->access->hasFeatureRight($alice, $this->warehouse, 2, ['is_create', 'is_edit']));
        $this->assertFalse($this->access->hasFeatureRight($alice, $this->showroom, 2, ['is_create', 'is_edit']));
        $this->assertFalse($this->access->hasFeatureRight($alice, $this->warehouse, 4, ['is_edit']));
        $this->assertFalse($this->access->hasFeatureRight($alice, $this->warehouse, 2, ['is_edit = 1 OR 1']));
    }

    public function test_the_same_role_is_ticked_separately_in_each_shop()
    {
        $bob = $this->createUser('bob', 'x', $this->storeKeeper);
        $this->assign($bob, $this->warehouse, $this->storeKeeper);
        $this->assign($bob, $this->showroom, $this->storeKeeper);
        $this->grant($this->storeKeeper, 2, ['is_edit'], $this->warehouse);

        $this->assertTrue($this->access->hasFeatureRight($bob, $this->warehouse, 2, ['is_edit']));
        $this->assertFalse($this->access->hasFeatureRight($bob, $this->showroom, 2, ['is_edit']));
    }

    public function test_a_super_admin_has_every_right_and_a_revoked_user_none()
    {
        $admin = $this->admin();
        $bob = $this->createUser('bob', 'x', $this->storeKeeper);
        $this->assign($bob, $this->warehouse, $this->storeKeeper, false);
        $this->grant($this->storeKeeper, 2, ['is_edit'], $this->warehouse);

        $this->assertTrue($this->access->hasFeatureRight($admin, $this->showroom, 4, ['is_edit']));
        $this->assertFalse($this->access->hasFeatureRight($bob, $this->warehouse, 2, ['is_edit']));
    }

    public function test_unavailable_reason_follows_company_state()
    {
        $open = ['ComStat' => 1, 'ComExpireDate' => date('Y-m-d')];
        $expired = ['ComStat' => 1, 'ComExpireDate' => date('Y-m-d', strtotime('-1 day'))];
        $inactive = ['ComStat' => 0, 'ComExpireDate' => date('Y-m-d', strtotime('+1 year'))];

        $this->assertNull(ShopAccess::unavailableReason($open, 0));
        $this->assertSame(ShopAccess::ERR_COMPANY_EXPIRED, ShopAccess::unavailableReason($expired, 0));
        $this->assertSame(ShopAccess::ERR_COMPANY_INACTIVE, ShopAccess::unavailableReason($inactive, 0));
        $this->assertNull(ShopAccess::unavailableReason($expired, 1));
    }
}
