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
}//WarehouseOrderTest
