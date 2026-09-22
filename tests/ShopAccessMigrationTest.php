<?php
final class ShopAccessMigrationTest extends DatabaseTestCase
{
    protected $migrate = false; //these tests run the migration themselves

    private function column($name)
    {
        $stmt = $this->pdo->prepare("SELECT IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'shopusers' AND COLUMN_NAME = ?");
        $stmt->execute([$name]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function assignments()
    {
        return $this->pdo->query('SELECT SUID, shop_SHID, user_USID, UserRoles_URID, is_active FROM shopusers ORDER BY SUID')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function test_every_assignment_gets_its_users_current_role_and_stays_active()
    {
        $company = $this->createCompany();
        $shop = $this->createShop($company);
        $cashier = $this->createRole('Cashier');
        $manager = $this->createRole('Manager');
        $alice = $this->createUser('alice', 'x', $cashier);
        $bob = $this->createUser('bob', 'x', $manager);
        $this->insert('shopusers', ['shop_SHID' => $shop, 'user_USID' => $alice]);
        $this->insert('shopusers', ['shop_SHID' => $shop, 'user_USID' => $bob]);

        (new ShopAccessMigration($this->pdo))->run();

        $rows = $this->assignments();
        $this->assertSame($cashier, $rows[0]['UserRoles_URID']);
        $this->assertSame($manager, $rows[1]['UserRoles_URID']);
        $this->assertSame([1, 1], array_column($rows, 'is_active'));
    }

    public function test_assignments_of_deleted_users_are_revoked_with_no_role()
    {
        $shop = $this->createShop($this->createCompany());
        $this->insert('shopusers', ['shop_SHID' => $shop, 'user_USID' => 999]);

        $report = (new ShopAccessMigration($this->pdo))->run();

        $this->assertSame([['SUID' => 1, 'shop_SHID' => $shop, 'user_USID' => 999, 'UserRoles_URID' => 0, 'is_active' => 0]], $this->assignments());
        $this->assertStringContainsString('user 999, who no longer exists', implode("\n", $report));
    }

    public function test_duplicate_assignments_are_merged_into_the_oldest()
    {
        $shop = $this->createShop($this->createCompany());
        $role = $this->createRole('Cashier');
        $alice = $this->createUser('alice', 'x', $role);
        $first = $this->insert('shopusers', ['shop_SHID' => $shop, 'user_USID' => $alice]);
        $this->insert('shopusers', ['shop_SHID' => $shop, 'user_USID' => $alice]);

        (new ShopAccessMigration($this->pdo))->run();

        $this->assertSame([$first], array_column($this->assignments(), 'SUID'));
    }

    public function test_role_becomes_required_and_one_assignment_per_user_per_shop()
    {
        (new ShopAccessMigration($this->pdo))->run();

        $this->assertSame('NO', $this->column('UserRoles_URID')['IS_NULLABLE']);
        $this->assertSame('1', $this->column('is_active')['COLUMN_DEFAULT']);

        $shop = $this->createShop($this->createCompany());
        $this->insert('shopusers', ['shop_SHID' => $shop, 'user_USID' => 1, 'UserRoles_URID' => 1]);
        $this->expectException(PDOException::class);
        $this->insert('shopusers', ['shop_SHID' => $shop, 'user_USID' => 1, 'UserRoles_URID' => 2]);
    }

    public function test_running_twice_changes_nothing()
    {
        $shop = $this->createShop($this->createCompany());
        $role = $this->createRole('Cashier');
        $this->insert('shopusers', ['shop_SHID' => $shop, 'user_USID' => $this->createUser('alice', 'x', $role)]);
        (new ShopAccessMigration($this->pdo))->run();
        $before = $this->assignments();

        $report = (new ShopAccessMigration($this->pdo))->run();

        $this->assertSame($before, $this->assignments());
        foreach ($report as $line) {
            $this->assertStringStartsWith('[skip]', $line);
        }
    }
}
