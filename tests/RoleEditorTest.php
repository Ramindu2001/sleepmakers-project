<?php
//Settings -> User Roles (Model/user_role.php). The editor always works in the shop that is
//open: ticking a feature for the showroom must leave the warehouse exactly as it was.
final class RoleEditorTest extends DatabaseTestCase
{
    private UserRole $roles;
    private int $warehouse;
    private int $showroom;
    private int $cashier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->roles = new UserRole();
        $company = $this->createCompany();
        $this->warehouse = $this->createShop($company, ['ShopName' => 'Warehouse']);
        $this->showroom = $this->createShop($company, ['ShopName' => 'Valentino Italy']);
        $this->cashier = $this->createRole('Cashier');
        $this->insert('sysmodules', ['SMID' => 1, 'ModuleName' => 'Inventory', 'sort_order' => 1]);
        $this->insert('sysfeatures', ['SFID' => 2, 'FeatureName' => 'Goods Received', 'SystemModules_SMID' => 1, 'sort_order' => 2]);
        $this->insert('sysfeatures', ['SFID' => 16, 'FeatureName' => 'Products', 'SystemModules_SMID' => 1, 'sort_order' => 16]);
    }

    //what the pages see for a role in one shop
    private function ticks($feature_id, $shop_id)
    {
        $rows = (new User())->userAcces($this->cashier, $feature_id, $shop_id);
        return [(int) $rows[0]['is_view'], (int) $rows[0]['is_edit'], (int) $rows[0]['is_print']];
    }

    public function test_ticks_saved_in_one_shop_are_not_ticked_in_the_other()
    {
        $this->roles->add_userrole(0, 1, 1, 0, 0, 1, $this->cashier, 16, $this->showroom);
        $this->roles->add_role_module($this->cashier, 1, $this->showroom);

        $this->assertSame([1, 1, 1], $this->ticks(16, $this->showroom));
        $this->assertSame([0, 0, 0], $this->ticks(16, $this->warehouse));
        $this->assertCount(1, (new User())->getUserRoleModuleAccess($this->cashier, $this->showroom));
        $this->assertSame([], (new User())->getUserRoleModuleAccess($this->cashier, $this->warehouse));
    }

    public function test_saving_a_role_in_one_shop_leaves_the_other_shop_untouched()
    {
        $this->roles->add_userrole(0, 0, 1, 0, 0, 0, $this->cashier, 16, $this->warehouse);
        $this->roles->add_role_module($this->cashier, 1, $this->warehouse);
        $this->roles->add_userrole(1, 1, 1, 0, 0, 0, $this->cashier, 16, $this->showroom);
        $this->roles->add_role_module($this->cashier, 1, $this->showroom);

        //what Controller/userrolecontrol.php does when the showroom's form is saved
        $this->roles->delete_user_feature($this->cashier, $this->showroom);
        $this->roles->delete_user_modules($this->cashier, $this->showroom);

        $this->assertSame([1, 0, 0], $this->ticks(16, $this->warehouse));
        $this->assertSame([0, 0, 0], $this->ticks(16, $this->showroom));
        $this->assertCount(1, (new User())->getUserRoleModuleAccess($this->cashier, $this->warehouse));
        $this->assertSame([], (new User())->getUserRoleModuleAccess($this->cashier, $this->showroom));
    }

    public function test_the_same_feature_saved_twice_keeps_one_row_with_the_last_ticks()
    {
        $this->roles->add_userrole(0, 0, 1, 0, 0, 0, $this->cashier, 16, $this->warehouse);
        $this->roles->add_userrole(1, 1, 1, 0, 0, 1, $this->cashier, 16, $this->warehouse);
        $this->roles->add_role_module($this->cashier, 1, $this->warehouse);
        $this->roles->add_role_module($this->cashier, 1, $this->warehouse);

        $this->assertSame([1, 1, 1], $this->ticks(16, $this->warehouse));
        $this->assertCount(1, (new User())->getUserRoleFeatureAccess($this->cashier, 16, $this->warehouse));
        $this->assertCount(1, (new User())->getUserRoleModuleAccess($this->cashier, $this->warehouse));
    }

    public function test_the_editor_form_shows_the_ticks_of_the_shop_it_is_opened_in()
    {
        $this->roles->add_userrole(0, 0, 1, 0, 0, 1, $this->cashier, 16, $this->warehouse);
        $this->roles->add_role_module($this->cashier, 1, $this->warehouse);

        $here = $this->roles->select_edit_rolefeatures(1, $this->cashier, $this->warehouse);
        $there = $this->roles->select_edit_rolefeatures(1, $this->cashier, $this->showroom);
        $products = array_values(array_filter($here, fn($row) => (int) $row['SFID'] === 16))[0];

        $this->assertSame([1, 1], [(int) $products['is_view'], (int) $products['is_print']]);
        $this->assertSame([0, 0], array_map('intval', array_column($there, 'is_view')));
        $this->assertSame(1, (int) $this->roles->select_edit_rolemodules($this->cashier, $this->warehouse)[0]['Access']);
        $this->assertSame(0, (int) $this->roles->select_edit_rolemodules($this->cashier, $this->showroom)[0]['Access']);
    }

    public function test_the_role_list_counts_only_what_is_ticked_here()
    {
        $this->roles->add_userrole(0, 0, 1, 0, 0, 0, $this->cashier, 16, $this->warehouse);
        $this->roles->add_userrole(0, 0, 0, 0, 0, 0, $this->cashier, 2, $this->warehouse);
        $this->roles->add_role_module($this->cashier, 1, $this->warehouse);

        //only the feature that is actually ticked is listed
        $this->assertSame([16], array_map('intval', array_column(
            $this->roles->select_all_user_role_feature($this->cashier, $this->warehouse), 'SFID')));
        $this->assertSame([], $this->roles->select_all_user_role_feature($this->cashier, $this->showroom));
        $this->assertCount(1, $this->roles->select_all_user_role_module($this->cashier, $this->warehouse));
        $this->assertSame([], $this->roles->select_all_user_role_module($this->cashier, $this->showroom));
    }
}
