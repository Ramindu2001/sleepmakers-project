<?php
final class AddUsersModelsTest extends DatabaseTestCase
{
    private AddUsersModels $model;
    private int $shop;
    private int $cashier;
    private int $alice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new AddUsersModels();
        $this->shop = $this->createShop($this->createCompany(), ['ShopName' => 'Warehouse']);
        $this->cashier = $this->createRole('Cashier');
        $this->alice = $this->createUser('alice', 'x', $this->cashier);
    }

    private function row($suid)
    {
        return $this->pdo->query('SELECT UserRoles_URID, is_active FROM shopusers WHERE SUID = ' . (int) $suid)->fetch(PDO::FETCH_ASSOC);
    }

    public function test_assigns_a_user_once_with_the_chosen_role()
    {
        $keeper = $this->createRole('Store Keeper');

        $this->assertSame('assigned', $this->model->assignUser($this->shop, $this->alice, $keeper));
        $this->assertSame('exists', $this->model->assignUser($this->shop, $this->alice, $this->cashier));

        $rows = $this->pdo->query('SELECT UserRoles_URID, is_active FROM shopusers')->fetchAll(PDO::FETCH_ASSOC);
        $this->assertSame([['UserRoles_URID' => $keeper, 'is_active' => 1]], $rows);
    }

    public function test_changes_the_role_and_toggles_access()
    {
        $keeper = $this->createRole('Store Keeper');
        $suid = $this->assign($this->alice, $this->shop, $this->cashier);

        $this->model->updateRole($suid, $keeper);
        $this->model->setActive($suid, 0);
        $this->assertSame(['UserRoles_URID' => $keeper, 'is_active' => 0], $this->row($suid));

        $this->model->setActive($suid, 1);
        $this->assertSame(1, $this->row($suid)['is_active']);
    }

    public function test_only_active_roles_are_offered_and_accepted()
    {
        $retired = $this->createRole('Retired', false);

        $this->assertTrue($this->model->isActiveRole($this->cashier));
        $this->assertFalse($this->model->isActiveRole($retired));
        $this->assertFalse($this->model->isActiveRole(999));
        $this->assertSame(['Cashier'], array_column($this->model->getActiveRoles(), 'UserRoleName'));
    }

    public function test_knows_which_shops_and_users_exist()
    {
        $this->assertTrue($this->model->shopExists($this->shop));
        $this->assertFalse($this->model->shopExists(999));
        $this->assertTrue($this->model->userExists($this->alice));
        $this->assertFalse($this->model->userExists(999));
    }

    public function test_lists_assignments_with_role_and_access()
    {
        $this->assign($this->alice, $this->shop, $this->cashier, false);

        $rows = $this->model->getAssignedUsers();

        $this->assertSame('Warehouse', $rows[0]['ShopName']);
        $this->assertSame('alice', $rows[0]['UserName']);
        $this->assertSame('Cashier', $rows[0]['UserRoleName']);
        $this->assertSame(0, $rows[0]['is_active']);
    }

    public function test_users_come_with_their_default_role()
    {
        $users = $this->model->getUsers();
        $this->assertSame($this->cashier, $users[0]['UserRoles_URID']);
    }
}
