<?php
final class CustomerOrdersTest extends DatabaseTestCase
{
    private CustomerOrders $orders;
    private int $warehouse;
    private int $showroom;
    private int $alice;     //store keeper in the warehouse: processes orders
    private int $bob;       //sales in the showroom: places orders
    private int $bed;
    private int $bed1;
    private int $bed2;
    private int $sheet;     //the showroom's own bedsheet

    protected function setUp(): void
    {
        parent::setUp();
        $this->orders = new CustomerOrders();
        $company = $this->createCompany();
        $this->warehouse = $this->createShop($company, ['ShopName' => 'Warehouse']);
        $this->showroom = $this->createShop($company, ['ShopName' => 'Valentino Italy']);
        $feature = $this->orders->featureId();
        $sales = $this->createRole('Sales');
        $this->grant($sales, $feature, ['is_view', 'is_create', 'is_edit']);
        $keeper = $this->createRole('Store Keeper');
        $this->grant($keeper, $feature, ['is_view', 'is_verify']);
        $this->bob = $this->createUser('bob', 'x', $sales);
        $this->assign($this->bob, $this->showroom, $sales);
        $this->alice = $this->createUser('alice', 'x', $keeper);
        $this->assign($this->alice, $this->warehouse, $keeper);
        $this->bed = $this->createProduct($this->warehouse, 'COO00001', 'Bed');
        $this->bed1 = $this->addStock($this->bed, $this->warehouse, 5, 'B1', 900, 1400);
        $this->bed2 = $this->addStock($this->bed, $this->warehouse, 5, 'B2', 950, 1450);
        $this->sheet = $this->createProduct($this->showroom, 'LIN00001', 'Bedsheet');
    }

    //a bedsheet already given from the showroom, and a bed the warehouse must supply
    private function data(array $overrides = [], ?array $lines = null)
    {
        return $overrides + [
            'supplier_id' => $this->warehouse, 'cust_name' => 'Nimal Perera', 'cust_phone' => '0771234567',
            'cust_address' => 'Colombo 5', 'needed_by' => date('Y-m-d', strtotime('+7 days')), 'advance' => '5000', 'notes' => 'Call first',
            'lines' => $lines ?? [
                ['source' => 'GIVEN', 'product_id' => $this->sheet, 'description' => '', 'qty' => '1', 'notes' => '', 'invoice_no' => 'INV-10'],
                ['source' => 'WAREHOUSE', 'product_id' => $this->bed, 'description' => '', 'qty' => '2', 'notes' => 'King size, firm', 'invoice_no' => ''],
            ],
        ];
    }

    private function refused(callable $call, $status, $message = null)
    {
        try {
            $call();
            $this->fail('expected ' . $status);
        } catch (CustomerOrderRefused $e) {
            $this->assertSame($status, $e->status, $e->getMessage());
            if ($message !== null) {
                $this->assertSame($message, $e->getMessage());
            }
        }
    }

    // ---- placing, editing, reading -----------------------------------------------------------

    public function test_a_showroom_places_an_order_with_given_and_warehouse_lines()
    {
        $placed = $this->orders->create($this->showroom, $this->bob, $this->data());
        $order = $this->orders->get($placed['id'], $this->showroom, $this->bob);

        $this->assertSame('CO_000001', $placed['order_no']);
        $this->assertSame(['CO_000001', 'Requested', 'ours', 'Nimal Perera', '5000.00', 'Warehouse'],
            [$order['OrderNo'], $order['status'], $order['side'], $order['CustName'], $order['AdvancePaid'], $order['SupplierName']]);
        $this->assertSame([['GIVEN', 'Bedsheet', 1, 'INV-10'], ['WAREHOUSE', 'Bed', 2, null]],
            array_map(function ($l) { return [$l['LineSource'], $l['Description'], $l['qty'], $l['InvoiceNo']]; }, $order['lines']));
        $this->assertSame('King size, firm', $order['lines'][1]['Notes']);
        $this->assertSame([true, true, false, false], [$order['can_edit'], $order['can_cancel'], $order['can_accept'], $order['can_handover']]);
    }

    public function test_order_numbers_count_per_shop()
    {
        $this->orders->create($this->showroom, $this->bob, $this->data());
        $this->assertSame('CO_000002', $this->orders->create($this->showroom, $this->bob, $this->data())['order_no']);
    }

    public function test_an_order_is_checked_before_it_is_saved()
    {
        $bob = $this->bob;
        $S = $this->showroom;
        $this->refused(function () use ($S, $bob) { $this->orders->create($S, $bob, $this->data(['cust_phone' => ' '])); }, 422, "Enter the customer's name and phone number.");
        $this->refused(function () use ($S, $bob) { $this->orders->create($S, $bob, $this->data(['supplier_id' => $S])); }, 422, 'Choose the shop to order from.');
        $this->refused(function () use ($S, $bob) { $this->orders->create($S, $bob, $this->data([], [
            ['source' => 'GIVEN', 'product_id' => $this->sheet, 'qty' => '1']])); }, 422, 'Add at least one item for Warehouse to supply.');
        $this->refused(function () use ($S, $bob) { $this->orders->create($S, $bob, $this->data([], [
            ['source' => 'WAREHOUSE', 'product_id' => $this->sheet, 'qty' => '1']])); }, 422, 'Line 1: that product is not in the catalog of Warehouse.');
        $this->refused(function () use ($S, $bob) { $this->orders->create($S, $bob, $this->data([], [
            ['source' => 'WAREHOUSE', 'product_id' => $this->bed, 'qty' => '0']])); }, 422, 'Line 1: enter a quantity above 0.');
        $this->refused(function () use ($S, $bob) { $this->orders->create($S, $bob, $this->data([], [
            ['source' => 'WAREHOUSE', 'product_id' => '', 'description' => ' ', 'qty' => '1']])); }, 422, 'Line 1: describe the custom-made item.');
        $this->refused(function () use ($S, $bob) { $this->orders->create($S, $bob, $this->data(['needed_by' => '2026-02-30'])); }, 422, 'Enter a valid "needed by" date.');

        $custom = $this->orders->create($S, $bob, $this->data([], [
            ['source' => 'WAREHOUSE', 'product_id' => '', 'description' => 'Headboard, walnut, 6ft', 'qty' => '1', 'notes' => 'Match the bed']]));
        $line = $this->orders->get($custom['id'], $S, $bob)['lines'][0];
        $this->assertSame([null, 'Headboard, walnut, 6ft', 'Match the bed'], [$line['products_PDID'], $line['Description'], $line['Notes']]);
    }

    public function test_only_a_role_with_the_right_places_orders()
    {
        $viewer = $this->createRole('Viewer');
        $this->grant($viewer, $this->orders->featureId(), ['is_view']);
        $clerk = $this->createUser('carol', 'x', $viewer);
        $this->assign($clerk, $this->showroom, $viewer);
        $this->refused(function () use ($clerk) { $this->orders->create($this->showroom, $clerk, $this->data()); }, 403);

        $admin = $this->createUser('admin', 'x', $viewer, ['UserType' => 1]);
        $this->assertSame('CO_000001', $this->orders->create($this->showroom, $admin, $this->data())['order_no']);
    }

    public function test_the_supplier_sees_it_as_incoming_and_other_shops_not_at_all()
    {
        $placed = $this->orders->create($this->showroom, $this->bob, $this->data());
        $incoming = $this->orders->get($placed['id'], $this->warehouse, $this->alice);
        $this->assertSame(['incoming', true, false, true], [$incoming['side'], $incoming['can_accept'], $incoming['can_edit'], $incoming['can_create_transfer']]);

        $other = $this->createShop($this->createCompany());
        $admin = $this->createUser('admin', 'x', 1, ['UserType' => 1]);
        $this->refused(function () use ($placed, $other, $admin) { $this->orders->get($placed['id'], $other, $admin); }, 404, 'This order is not in this shop.');

        $this->assertSame([$placed['order_no']], array_column($this->orders->listFor($this->showroom, $this->bob, 'ours'), 'OrderNo'));
        $this->assertSame([$placed['order_no']], array_column($this->orders->listFor($this->warehouse, $this->alice, 'incoming'), 'OrderNo'));
        $this->assertSame('Bed × 2', $this->orders->listFor($this->warehouse, $this->alice, 'incoming')[0]['summary']);
        $this->assertSame([], $this->orders->listFor($this->warehouse, $this->alice, 'ours'));
        $this->assertSame(1, $this->orders->incomingCount($this->warehouse));
    }

    public function test_an_order_is_edited_while_it_waits_for_the_supplier()
    {
        $placed = $this->orders->create($this->showroom, $this->bob, $this->data());
        $this->orders->update($placed['id'], $this->showroom, $this->bob, $this->data(['cust_phone' => '0710000000'], [
            ['source' => 'WAREHOUSE', 'product_id' => $this->bed, 'qty' => '3', 'notes' => 'Queen']]));
        $order = $this->orders->get($placed['id'], $this->showroom, $this->bob);
        $this->assertSame(['0710000000', 1, 3, 'Queen'], [$order['CustPhone'], count($order['lines']), $order['lines'][0]['qty'], $order['lines'][0]['Notes']]);
        $this->refused(function () use ($placed) { $this->orders->update($placed['id'], $this->warehouse, $this->alice, $this->data()); }, 422);
    }

    public function test_suppliers_are_the_other_active_shops_of_the_company_and_their_catalog_shows_stock()
    {
        $company = (int)$this->pdo->query("SELECT Company_CMID FROM shop WHERE SHID = {$this->showroom}")->fetchColumn();
        $this->createShop($company, ['ShopName' => 'Closed', 'ShopStat' => 0]);
        $this->assertSame([['SHID' => $this->warehouse, 'ShopName' => 'Warehouse']], $this->orders->supplierShops($this->showroom));
        $this->assertSame([['id' => $this->bed, 'barcode' => 'COO00001', 'name' => 'Bed', 'in_stock' => 10]], $this->orders->searchProducts($this->warehouse, 'bed'));
        $this->assertSame([], $this->orders->searchProducts($this->warehouse, '%'));
    }
}
