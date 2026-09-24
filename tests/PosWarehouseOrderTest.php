<?php
//Includes/pos_warehouse_order.php: the cart a cashier posts, read as an order for the warehouse.
//The interesting case is the checkbox in front of an item name - "deliver from warehouse" on
//something the shop itself sells. The shop keeps billing its own product; the warehouse has to
//be given its own copy to pick, and nothing the browser sends decides which product that is.
final class PosWarehouseOrderTest extends DatabaseTestCase
{
    private int $shop;
    private int $warehouse;
    private int $cashier;
    private int $bedHere;      //the shop's own copy of the bed
    private int $bedThere;     //the warehouse's bed
    private int $sofaThere;    //something else the warehouse keeps
    private int $sheetHere;    //the shop made this one up: the warehouse never had it

    protected function setUp(): void
    {
        parent::setUp();
        $company = $this->createCompany();
        $this->warehouse = $this->createShop($company, ['ShopName' => 'Warehouse']);
        $this->shop = $this->createShop($company, ['ShopName' => 'Valentino Italy']);
        $role = $this->createRole('Staff');
        $this->cashier = $this->createUser('cashier', 'x', $role);
        $this->assign($this->cashier, $this->shop, $role);
        $feature = (int) $this->pdo->query("SELECT SFID FROM sysfeatures WHERE FeatureName = 'Customer Orders'")->fetchColumn();
        $this->grant($role, $feature, ['is_view', 'is_create', 'is_edit', 'is_verify'], $this->shop);

        $this->bedThere = $this->createProduct($this->warehouse, 'COO00001', 'Cooler Bed');
        $this->bedHere = $this->createProduct($this->shop, 'COO00001', 'Cooler Bed');
        $this->sofaThere = $this->createProduct($this->warehouse, 'SOF00001', 'Leather Sofa');
        $this->sheetHere = $this->createProduct($this->shop, 'LIN00001', 'Bedsheet');
        $this->addStock($this->bedThere, $this->warehouse, 5, 'B1', 100, 150);
        $this->addStock($this->bedHere, $this->shop, 3, 'B1', 100, 150);
        $this->addStock($this->sheetHere, $this->shop, 9, 'B1', 20, 40);
        $_POST = [];
    }//setUp

    protected function tearDown(): void
    {
        $_POST = [];
        parent::tearDown();
    }//tearDown

    //A cart as the till posts it. Each line is [product id, name, qty, price, extra fields].
    private function cart(array $lines, array $details = [])
    {
        $_POST = $details + [
            'wh_supplier_shop' => '', 'wh_customer_id' => '', 'wh_cust_name' => 'Nimal',
            'wh_cust_phone' => '0771234567', 'wh_cust_address' => '12 Galle Rd',
            'wh_deliver_to' => '1', 'wh_address' => '12 Galle Rd', 'wh_phone' => '0771234567',
            'wh_note' => '', 'wh_needed_by' => '',
        ];
        foreach (['item_id', 'Item_name', 'qty', 'rate', 'totals', 'wh_line', 'wh_custom',
            'wh_own', 'wh_supplier_product', 'wh_notes'] as $field) {
            $_POST[$field] = [];
        }
        foreach ($lines as $line) {
            $_POST['item_id'][] = (string) $line[0];
            $_POST['Item_name'][] = $line[1];
            $_POST['qty'][] = (string) $line[2];
            $_POST['rate'][] = (string) $line[3];
            $_POST['totals'][] = (string) ($line[2] * $line[3]);
            $extra = isset($line[4]) ? $line[4] : [];
            foreach (['wh_line', 'wh_custom', 'wh_own', 'wh_supplier_product', 'wh_notes'] as $field) {
                $_POST[$field][] = isset($extra[$field]) ? (string) $extra[$field] : '';
            }
        }
    }//cart

    //the cashier ticked "deliver from warehouse" in front of the bed
    private function ticked(array $extra = [])
    {
        return ['wh_line' => '1', 'wh_own' => '1'] + $extra;
    }//ticked

    public function test_a_ticked_line_is_the_warehouses_job()
    {
        $this->cart([[$this->bedHere, 'Cooler Bed', 1, 800, $this->ticked()]]);

        $this->assertTrue(warehouseOrderWanted());
    }//the cart has something for the warehouse

    public function test_a_ticked_line_keeps_billing_the_shops_own_product()
    {
        $this->cart([[$this->bedHere, 'Cooler Bed', 1, 800, $this->ticked()]]);

        $sale = warehouseOrderBuild($this->shop, $this->cashier, true);

        //the invoice still bills the shop's bed, and no second copy of it is invented
        $this->assertSame((string) $this->bedHere, $_POST['item_id'][0]);
        $this->assertSame($this->bedHere, (int) $sale['lines'][0]['product_id']);
        $this->assertSame('WAREHOUSE', $sale['lines'][0]['source']);
    }//the shop keeps billing its own product

    public function test_a_ticked_line_names_the_warehouses_own_copy_to_pick()
    {
        $this->cart([[$this->bedHere, 'Cooler Bed', 1, 800, $this->ticked()]]);

        $sale = warehouseOrderBuild($this->shop, $this->cashier, true);

        $this->assertSame($this->bedThere, (int) $sale['lines'][0]['supplier_product_id']);
        $this->assertSame($this->warehouse, (int) $sale['supplier_shop_id']);
    }//the warehouse gets something it can pick

    public function test_the_warehouse_product_the_browser_sent_is_never_trusted()
    {
        //a cart that billed a bed but asked the warehouse for a sofa
        $this->cart([[$this->bedHere, 'Cooler Bed', 1, 800,
            $this->ticked(['wh_supplier_product' => $this->sofaThere])]]);

        $sale = warehouseOrderBuild($this->shop, $this->cashier, true);

        $this->assertSame($this->bedThere, (int) $sale['lines'][0]['supplier_product_id']);
    }//the posted product is ignored

    public function test_ticking_an_item_the_warehouse_never_had_refuses_the_sale()
    {
        $this->cart([[$this->sheetHere, 'Bedsheet', 1, 40, $this->ticked()]]);

        $problem = warehouseOrderCheck($this->shop, $this->cashier);

        $this->assertStringContainsString('does not keep that item', $problem);
        $this->assertStringContainsString('Bedsheet', $problem);
    }//the warehouse cannot send what it never had

    public function test_a_refused_tick_stops_the_cart_before_the_invoice()
    {
        $this->cart([[$this->sheetHere, 'Bedsheet', 1, 40, $this->ticked()]]);

        $problem = warehouseOrderPrepareCart($this->shop, $this->cashier);

        $this->assertStringContainsString('does not keep that item', $problem);
    }//preparing the cart says no rather than throwing

    public function test_a_cart_the_warehouse_can_fill_prepares_cleanly()
    {
        $this->cart([[$this->bedHere, 'Cooler Bed', 1, 800, $this->ticked()]]);

        $this->assertSame('', warehouseOrderPrepareCart($this->shop, $this->cashier));
        $this->assertSame('', warehouseOrderCheck($this->shop, $this->cashier));
    }//a good cart passes

    public function test_an_untouched_line_is_still_given_over_the_counter()
    {
        $this->cart([
            [$this->sheetHere, 'Bedsheet', 2, 40],
            [$this->bedHere, 'Cooler Bed', 1, 800, $this->ticked()],
        ]);

        $sale = warehouseOrderBuild($this->shop, $this->cashier, true);

        $this->assertSame(['GIVEN', 'WAREHOUSE'], array_column($sale['lines'], 'source'));
        //the bedsheet the customer carried out is recorded, so it is never sent again
        $this->assertSame($this->sheetHere, (int) $sale['lines'][0]['product_id']);
        $this->assertNull($sale['lines'][0]['supplier_product_id']);
    }//what was handed over stays handed over

    public function test_a_warehouse_tile_line_still_gets_the_shops_own_copy()
    {
        //nothing ticked here: the cashier clicked a tile only the warehouse has
        $this->cart([[$this->sofaThere, 'Leather Sofa', 1, 5000,
            ['wh_line' => '1', 'wh_supplier_product' => $this->sofaThere]]]);

        $sale = warehouseOrderBuild($this->shop, $this->cashier, true);

        $copy = (int) $_POST['item_id'][0];
        $this->assertNotSame($this->sofaThere, $copy);
        $stmt = $this->pdo->prepare('SELECT shop_SHID FROM products WHERE PDID = ?');
        $stmt->execute([$copy]);
        $this->assertSame($this->shop, (int) $stmt->fetchColumn());
        $this->assertSame($this->sofaThere, (int) $sale['lines'][0]['supplier_product_id']);
    }//the tile path is unchanged

    public function test_a_custom_line_is_unaffected_by_the_checkbox()
    {
        $this->cart([[0, 'Headboard, oak, 6ft', 1, 12000,
            ['wh_line' => '1', 'wh_custom' => '1', 'wh_notes' => 'Oak, 6ft']]]);

        $sale = warehouseOrderBuild($this->shop, $this->cashier, true);

        $this->assertNull($sale['lines'][0]['product_id']);
        $this->assertNull($sale['lines'][0]['supplier_product_id']);
        $this->assertSame('Oak, 6ft', $sale['lines'][0]['notes']);
    }//custom items are still described in words

    public function test_a_ticked_line_becomes_a_pending_order_line()
    {
        $this->cart([
            [$this->sheetHere, 'Bedsheet', 2, 40],
            [$this->bedHere, 'Cooler Bed', 1, 800, $this->ticked(['wh_notes' => 'Firm'])],
        ]);
        $invoice = $this->insert('invoiceheader', ['InvoiceNo' => 'INV_' . uniqid(),
            'EffectiveDate' => date('Y-m-d'), 'InvItemCount' => 2, 'GrossAmount' => 880,
            'NetAmount' => 880, 'CustPayment' => 880, 'CustBalance' => 0, 'InvStat' => 1,
            'user_USID' => $this->cashier, 'shop_SHID' => $this->shop]);
        $alert = [];

        $order = warehouseOrderPlace($this->shop, $this->cashier, $invoice, $alert);

        $this->assertNotNull($order);
        $lines = (new WarehouseOrder())->linesOf($order['order_id']);
        $this->assertSame([WarehouseOrder::LINE_GIVEN, WarehouseOrder::LINE_PENDING],
            array_map('intval', array_column($lines, 'LineStat')));
        $this->assertSame($this->bedThere, (int) $lines[1]['SupplierProductID']);
    }//the order the warehouse sees
}//PosWarehouseOrderTest
