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
        $this->grant($storeKeeper, 16, ['is_view', 'is_print']);   //Products feature
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
}
