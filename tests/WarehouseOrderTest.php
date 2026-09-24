<?php
//Model/warehouse_order_class.php: the order a POS sale leaves for the warehouse - what the
//customer was given over the counter, what the warehouse still owes them, and what it is worth.
final class WarehouseOrderTest extends DatabaseTestCase
{
    private WarehouseOrder $orders;
    private int $shop;
    private int $warehouse;
    private int $cashier;
    private int $picker;
    private int $role;
    private int $bedHere;      //the shop's own copy of the bed
    private int $bedThere;     //the warehouse's bed
    private int $sheetHere;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orders = new WarehouseOrder();
        $company = $this->createCompany();
        $this->warehouse = $this->createShop($company, ['ShopName' => 'Warehouse']);
        $this->shop = $this->createShop($company, ['ShopName' => 'Valentino Italy']);
        $this->role = $this->createRole('Staff');
        $this->cashier = $this->createUser('cashier', 'x', $this->role);
        $this->picker = $this->createUser('picker', 'x', $this->role);
        $this->assign($this->cashier, $this->shop, $this->role);
        $this->assign($this->picker, $this->warehouse, $this->role);
        $feature = (int) $this->pdo->query("SELECT SFID FROM sysfeatures WHERE FeatureName = 'Customer Orders'")->fetchColumn();
        $this->grant($this->role, $feature, ['is_view', 'is_create', 'is_edit', 'is_verify'], $this->shop);
        $this->grant($this->role, $feature, ['is_view', 'is_create', 'is_edit', 'is_verify'], $this->warehouse);
        $this->bedThere = $this->createProduct($this->warehouse, 'COO00001', 'Cooler Bed');
        $this->bedHere = $this->createProduct($this->shop, 'COO00001', 'Cooler Bed');
        $this->sheetHere = $this->createProduct($this->shop, 'LIN00001', 'Bedsheet');
        $this->addStock($this->bedThere, $this->warehouse, 5, 'B1', 100, 150);
    }//setUp

    //an invoice as POS writes it: 1000 billed, 400 paid, 600 still owed
    private function invoice(array $overrides = [])
    {
        return $this->insert('invoiceheader', $overrides + ['InvoiceNo' => 'INV_' . uniqid(),
            'EffectiveDate' => date('Y-m-d'), 'InvItemCount' => 2, 'GrossAmount' => 1000,
            'NetAmount' => 1000, 'CustPayment' => 400, 'CustBalance' => 600, 'InvStat' => 1,
            'user_USID' => $this->cashier, 'shop_SHID' => $this->shop]);
    }//invoice

    //the order that sale leaves behind: a bedsheet handed over, a bed the warehouse must send
    private function order(array $lines = null, array $overrides = [])
    {
        $lines = $lines ?? [
            ['source' => 'GIVEN', 'product_id' => $this->sheetHere, 'supplier_product_id' => null,
                'description' => 'Bedsheet', 'qty' => 2, 'notes' => '', 'unit_price' => 100],
            ['source' => 'WAREHOUSE', 'product_id' => $this->bedHere, 'supplier_product_id' => $this->bedThere,
                'description' => 'Cooler Bed', 'qty' => 1, 'notes' => 'Firm', 'unit_price' => 800],
        ];
        return $this->orders->createFromSale($this->shop, $this->cashier, $overrides + [
            'invoice_id' => $this->invoice(), 'supplier_shop_id' => $this->warehouse, 'customer_id' => null,
            'cust_name' => 'Nimal', 'cust_phone' => '0771234567', 'cust_address' => '12 Galle Rd',
            'deliver_to' => 1, 'delivery_address' => '12 Galle Rd', 'delivery_phone' => '0771234567',
            'delivery_note' => '', 'needed_by' => null, 'notes' => '', 'lines' => $lines,
        ]);
    }//order

    public function test_the_order_shows_the_money_from_its_invoice()
    {
        $id = $this->order()['order_id'];

        $money = $this->orders->get($id, $this->warehouse, $this->picker)['money'];

        $this->assertSame([1000.0, 400.0, 600.0], [$money['net'], $money['paid'], $money['balance']]);
    }//money from the invoice

    public function test_a_later_payment_shows_without_touching_the_order()
    {
        $view = $this->orders->get($this->order()['order_id'], $this->warehouse, $this->picker);
        $this->pdo->prepare('UPDATE invoiceheader SET CustPayment = 1000, CustBalance = 0 WHERE IHID = ?')
            ->execute([$view['order']['InvoiceHeader_IHID']]);

        $money = $this->orders->get($view['order']['COID'], $this->warehouse, $this->picker)['money'];

        $this->assertSame(0.0, $money['balance']);
    }//a later payment shows

    public function test_both_shops_see_the_order_and_nobody_else_does()
    {
        $id = $this->order()['order_id'];
        $other = $this->createShop($this->createCompany(), ['ShopName' => 'Someone else']);

        $this->assertSame('CO_000001', $this->orders->get($id, $this->shop, $this->cashier)['order']['OrderNo']);
        $this->assertSame('CO_000001', $this->orders->get($id, $this->warehouse, $this->picker)['order']['OrderNo']);

        $this->expectException(CustomerOrderRefused::class);
        $this->orders->get($id, $other, $this->picker);
    }//both shops see it

    public function test_the_warehouse_queue_holds_orders_sent_to_it()
    {
        $this->order();

        $incoming = $this->orders->listFor($this->warehouse, $this->picker, 'incoming');
        $ours = $this->orders->listFor($this->shop, $this->cashier, 'ours');

        $this->assertCount(1, $incoming);
        $this->assertCount(1, $ours);
        $this->assertSame(1, $this->orders->pendingCount($this->warehouse));
    }//the warehouse queue

    // ---- what a sale leaves behind -----------------------------------------------------------

    public function test_a_sale_leaves_the_warehouse_lines_pending_and_the_given_ones_given()
    {
        $view = $this->orders->get($this->order()['order_id'], $this->warehouse, $this->picker);

        $this->assertSame([WarehouseOrder::LINE_GIVEN, WarehouseOrder::LINE_PENDING],
            array_map('intval', array_column($view['lines'], 'LineStat')));
        $this->assertSame(WarehouseOrder::PENDING, (int) $view['order']['OrderStat']);
    }//warehouse lines pending, given lines given

    public function test_the_order_is_numbered_per_shop()
    {
        $this->assertSame('CO_000001', $this->order()['order_no']);
        $this->assertSame('CO_000002', $this->order()['order_no']);
    }//numbered per shop

    public function test_a_line_keeps_the_shop_copy_and_the_warehouse_product_apart()
    {
        $lines = $this->orders->get($this->order()['order_id'], $this->warehouse, $this->picker)['lines'];

        $this->assertSame([$this->bedHere, $this->bedThere],
            [(int) $lines[1]['products_PDID'], (int) $lines[1]['SupplierProductID']]);
    }//both product ids kept apart

    public function test_the_line_remembers_what_the_customer_was_billed()
    {
        $lines = $this->orders->get($this->order()['order_id'], $this->warehouse, $this->picker)['lines'];

        $this->assertSame([800.0, 800.0], [(float) $lines[1]['UnitPrice'], (float) $lines[1]['LineTotal']]);
    }//the line remembers the price

    //the shop had 1 of 2 beds, so the bed is on the cart twice - once given, once ordered
    public function test_the_same_product_given_and_ordered_stays_two_lines()
    {
        $id = $this->order([
            ['source' => 'GIVEN', 'product_id' => $this->bedHere, 'supplier_product_id' => null,
                'description' => 'Cooler Bed', 'qty' => 1, 'notes' => '', 'unit_price' => 800],
            ['source' => 'WAREHOUSE', 'product_id' => $this->bedHere, 'supplier_product_id' => $this->bedThere,
                'description' => 'Cooler Bed', 'qty' => 1, 'notes' => '', 'unit_price' => 800],
        ])['order_id'];

        $lines = $this->orders->get($id, $this->warehouse, $this->picker)['lines'];

        $this->assertCount(2, $lines);
        $this->assertSame([1, 1], array_map(function ($line) { return (int) $line['Qty']; }, $lines));
        $this->assertSame([WarehouseOrder::LINE_GIVEN, WarehouseOrder::LINE_PENDING],
            array_map('intval', array_column($lines, 'LineStat')));
    }//given and ordered stay two lines

    public function test_a_custom_made_line_has_no_product()
    {
        $id = $this->order([
            ['source' => 'WAREHOUSE', 'product_id' => null, 'supplier_product_id' => null,
                'description' => 'Headboard, walnut, 6ft', 'qty' => 1, 'notes' => 'Buttoned', 'unit_price' => 45000],
        ])['order_id'];

        $line = $this->orders->get($id, $this->warehouse, $this->picker)['lines'][0];

        $this->assertNull($line['products_PDID']);
        $this->assertSame(['Headboard, walnut, 6ft', 'Buttoned'], [$line['Description'], $line['Notes']]);
    }//a custom made line

    public function test_a_sale_with_nothing_for_the_warehouse_makes_no_order()
    {
        $this->expectException(CustomerOrderRefused::class);
        $this->order([['source' => 'GIVEN', 'product_id' => $this->sheetHere, 'supplier_product_id' => null,
            'description' => 'Bedsheet', 'qty' => 1, 'notes' => '', 'unit_price' => 100]]);
    }//nothing for the warehouse

    public function test_the_customer_name_and_phone_are_required()
    {
        foreach (['cust_name', 'cust_phone'] as $field) {
            try {
                $this->order(null, [$field => '   ']);
                $this->fail('expected a refusal for ' . $field);
            } catch (CustomerOrderRefused $e) {
                $this->assertSame(422, $e->status);
            }
        }//each required field
    }//name and phone required

    public function test_a_delivery_address_is_required_unless_the_customer_collects()
    {
        try {
            $this->order(null, ['delivery_address' => '']);
            $this->fail('expected a refusal');
        } catch (CustomerOrderRefused $e) {
            $this->assertSame(422, $e->status);
        }

        $id = $this->order(null, ['delivery_address' => '', 'deliver_to' => WarehouseOrder::DELIVER_PICKUP])['order_id'];

        $this->assertSame(WarehouseOrder::DELIVER_PICKUP,
            (int) $this->orders->get($id, $this->shop, $this->cashier)['order']['DeliverTo']);
    }//address required unless collecting

    public function test_a_line_for_a_product_the_supplier_does_not_own_is_refused()
    {
        $this->expectException(CustomerOrderRefused::class);
        $this->order([['source' => 'WAREHOUSE', 'product_id' => $this->bedHere,
            'supplier_product_id' => $this->sheetHere, 'description' => 'Bedsheet', 'qty' => 1,
            'notes' => '', 'unit_price' => 100]]);
    }//a product the supplier does not own

    public function test_a_service_item_cannot_be_ordered_from_the_warehouse()
    {
        $service = $this->createProduct($this->warehouse, 'SRV00001', 'Delivery charge', ['ItemType' => 'S']);

        $this->expectException(CustomerOrderRefused::class);
        $this->order([['source' => 'WAREHOUSE', 'product_id' => $this->bedHere,
            'supplier_product_id' => $service, 'description' => 'Delivery charge', 'qty' => 1,
            'notes' => '', 'unit_price' => 100]]);
    }//a service item cannot be ordered

    public function test_a_shop_of_another_company_cannot_be_the_supplier()
    {
        $stranger = $this->createShop($this->createCompany(), ['ShopName' => 'Another company']);

        $this->expectException(CustomerOrderRefused::class);
        $this->order(null, ['supplier_shop_id' => $stranger]);
    }//supplier must be our own company

    public function test_a_quantity_that_is_not_a_sensible_number_is_refused()
    {
        foreach ([0, -1, 100000, 'two'] as $qty) {
            try {
                $this->order([['source' => 'WAREHOUSE', 'product_id' => $this->bedHere,
                    'supplier_product_id' => $this->bedThere, 'description' => 'Cooler Bed',
                    'qty' => $qty, 'notes' => '', 'unit_price' => 800]]);
                $this->fail('expected a refusal for quantity ' . $qty);
            } catch (CustomerOrderRefused $e) {
                $this->assertSame(422, $e->status);
            }
        }//each impossible quantity

        $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM customerorders')->fetchColumn());
    }//impossible quantities

    // ---- the warehouse works the order --------------------------------------------------------

    public function test_starting_and_readying_move_the_order_along()
    {
        $id = $this->order()['order_id'];

        $this->orders->startPreparing($id, $this->warehouse, $this->picker);
        $this->assertSame(WarehouseOrder::PREPARING, $this->statusOf($id));

        $this->orders->markReady($id, $this->warehouse, $this->picker);
        $this->assertSame(WarehouseOrder::READY, $this->statusOf($id));
    }//starting and readying

    public function test_a_half_ready_order_is_still_preparing()
    {
        $id = $this->twoWarehouseLines();
        $lines = $this->warehouseLines($id);

        $this->orders->markReady($id, $this->warehouse, $this->picker, (int) $lines[0]['COLID']);

        $this->assertSame(WarehouseOrder::PREPARING, $this->statusOf($id));
    }//a half ready order

    public function test_a_line_the_warehouse_cannot_supply_is_closed_with_its_reason()
    {
        $id = $this->order()['order_id'];
        $line = (int) $this->warehouseLines($id)[0]['COLID'];

        $this->orders->cannotSupply($id, $this->warehouse, $this->picker, $line, 'Discontinued by the mill');

        $view = $this->orders->get($id, $this->warehouse, $this->picker);
        $this->assertSame(WarehouseOrder::LINE_CANCELLED, (int) $view['lines'][1]['LineStat']);
        $this->assertSame('Discontinued by the mill', $view['lines'][1]['CancelReason']);
    }//cannot supply

    //the bedsheet was handed over, so the customer was served even though the bed fell through
    public function test_an_order_whose_last_warehouse_line_is_cancelled_completes()
    {
        $id = $this->order()['order_id'];
        $line = (int) $this->warehouseLines($id)[0]['COLID'];

        $this->orders->cannotSupply($id, $this->warehouse, $this->picker, $line, 'None left');

        $this->assertSame(WarehouseOrder::COMPLETED, $this->statusOf($id));
    }//cancelled but something was given

    public function test_an_order_with_nothing_given_and_nothing_supplied_is_cancelled()
    {
        $id = $this->order([['source' => 'WAREHOUSE', 'product_id' => $this->bedHere,
            'supplier_product_id' => $this->bedThere, 'description' => 'Cooler Bed', 'qty' => 1,
            'notes' => '', 'unit_price' => 800]])['order_id'];
        $line = (int) $this->warehouseLines($id)[0]['COLID'];

        $this->orders->cannotSupply($id, $this->warehouse, $this->picker, $line, 'None left');

        $this->assertSame(WarehouseOrder::CANCELLED, $this->statusOf($id));
    }//nothing given, nothing supplied

    public function test_a_reason_is_required_to_refuse_a_line()
    {
        $id = $this->order()['order_id'];
        $line = (int) $this->warehouseLines($id)[0]['COLID'];

        $this->expectException(CustomerOrderRefused::class);
        $this->orders->cannotSupply($id, $this->warehouse, $this->picker, $line, '   ');
    }//a reason is required

    public function test_a_given_line_can_never_be_readied_or_refused()
    {
        $id = $this->order()['order_id'];
        $given = (int) $this->orders->get($id, $this->warehouse, $this->picker)['lines'][0]['COLID'];

        try {
            $this->orders->markReady($id, $this->warehouse, $this->picker, $given);
            $this->fail('a line given at the shop was readied for picking');
        } catch (CustomerOrderRefused $e) {
            $this->assertSame(409, $e->status);
        }

        $this->expectException(CustomerOrderRefused::class);
        $this->orders->cannotSupply($id, $this->warehouse, $this->picker, $given, 'No');
    }//a given line is untouchable

    public function test_a_line_of_another_order_cannot_be_readied()
    {
        $mine = $this->order()['order_id'];
        $theirs = (int) $this->warehouseLines($this->order()['order_id'])[0]['COLID'];

        $this->expectException(CustomerOrderRefused::class);
        $this->orders->markReady($mine, $this->warehouse, $this->picker, $theirs);
    }//another order's line

    public function test_the_shop_cannot_prepare_and_the_warehouse_cannot_cancel_the_order()
    {
        $id = $this->order()['order_id'];

        try {
            $this->orders->startPreparing($id, $this->shop, $this->cashier);
            $this->fail('the shop prepared its own order');
        } catch (CustomerOrderRefused $e) {
            $this->assertSame(404, $e->status);
        }

        $this->expectException(CustomerOrderRefused::class);
        $this->orders->cancel($id, $this->warehouse, $this->picker);
    }//each side keeps to its own actions

    public function test_the_shop_can_cancel_an_order_nothing_has_left_on()
    {
        $id = $this->order()['order_id'];

        $this->orders->cancel($id, $this->shop, $this->cashier);

        $this->assertSame(WarehouseOrder::CANCELLED, $this->statusOf($id));
    }//the shop cancels

    public function test_someone_without_the_right_cannot_prepare()
    {
        $id = $this->order()['order_id'];
        $bare = $this->createRole('No rights');
        $outsider = $this->createUser('outsider', 'x', $bare);
        $this->assign($outsider, $this->warehouse, $bare);

        try {
            $this->orders->startPreparing($id, $this->warehouse, $outsider);
            $this->fail('expected a refusal');
        } catch (CustomerOrderRefused $e) {
            $this->assertSame(403, $e->status);
        }
    }//no right, no action

    //an order with two things for the warehouse and nothing given
    private function twoWarehouseLines()
    {
        return $this->order([
            ['source' => 'WAREHOUSE', 'product_id' => $this->bedHere, 'supplier_product_id' => $this->bedThere,
                'description' => 'Cooler Bed', 'qty' => 1, 'notes' => '', 'unit_price' => 800],
            ['source' => 'WAREHOUSE', 'product_id' => null, 'supplier_product_id' => null,
                'description' => 'Headboard', 'qty' => 1, 'notes' => '', 'unit_price' => 45000],
        ])['order_id'];
    }//two warehouse lines

    private function warehouseLines($order_id)
    {
        return array_values(array_filter($this->orders->linesOf($order_id), function ($line) {
            return $line['LineSource'] === 'WAREHOUSE';
        }));
    }//warehouse lines

    private function statusOf($id)
    {
        $stmt = $this->pdo->prepare('SELECT OrderStat FROM customerorders WHERE COID = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn();
    }//status of
}//WarehouseOrderTest
