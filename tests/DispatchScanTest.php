<?php
//Model/order_dispatch_class.php and Model/scan_dispatch_class.php: nothing leaves the warehouse
//until it has been scanned against the order it belongs to.
final class DispatchScanTest extends DatabaseTestCase
{
    private WarehouseOrder $orders;
    private OrderDispatch $dispatches;
    private $scan = null;
    private int $shop;
    private int $warehouse;
    private int $cashier;
    private int $picker;
    private int $bedHere;
    private int $bedThere;
    private int $sheetHere;
    private int $orderId = 0;
    private int $invoiceId = 0;
    private int $lineId = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orders = new WarehouseOrder();
        $this->dispatches = new OrderDispatch();
        //DispatchScan arrives with the scanning tests
        $company = $this->createCompany();
        $this->warehouse = $this->createShop($company, ['ShopName' => 'Warehouse']);
        $this->shop = $this->createShop($company, ['ShopName' => 'Valentino Italy']);
        $role = $this->createRole('Staff');
        $this->cashier = $this->createUser('cashier', 'x', $role);
        $this->picker = $this->createUser('picker', 'x', $role);
        $this->assign($this->cashier, $this->shop, $role);
        $this->assign($this->picker, $this->warehouse, $role);
        $feature = (int) $this->pdo->query("SELECT SFID FROM sysfeatures WHERE FeatureName = 'Customer Orders'")->fetchColumn();
        $this->grant($role, $feature, ['is_view', 'is_create', 'is_edit', 'is_verify'], $this->shop);
        $this->grant($role, $feature, ['is_view', 'is_create', 'is_edit', 'is_verify'], $this->warehouse);
        $this->bedThere = $this->createProduct($this->warehouse, 'COO00001', 'Cooler Bed');
        $this->bedHere = $this->createProduct($this->shop, 'COO00001', 'Cooler Bed');
        $this->sheetHere = $this->createProduct($this->shop, 'LIN00001', 'Bedsheet');
        $this->addStock($this->bedThere, $this->warehouse, 5, 'B1', 100, 150);
    }//setUp

    // ---- the fixture ---------------------------------------------------------------------------

    //An order for $qty beds plus a bedsheet already handed over, with the bed marked ready.
    //Sets $this->orderId, $this->invoiceId and $this->lineId.
    private function readyOrder($qty = 1)
    {
        $this->invoiceId = $this->insert('invoiceheader', ['InvoiceNo' => 'INV_' . substr(uniqid(), -6),
            'EffectiveDate' => date('Y-m-d'), 'InvItemCount' => 2, 'GrossAmount' => 1000, 'NetAmount' => 1000,
            'CustPayment' => 400, 'CustBalance' => 600, 'InvStat' => 1,
            'user_USID' => $this->cashier, 'shop_SHID' => $this->shop]);
        $this->orderId = $this->orders->createFromSale($this->shop, $this->cashier, [
            'invoice_id' => $this->invoiceId, 'supplier_shop_id' => $this->warehouse, 'customer_id' => null,
            'cust_name' => 'Nimal', 'cust_phone' => '0771234567', 'cust_address' => '12 Galle Rd',
            'deliver_to' => 1, 'delivery_address' => '12 Galle Rd', 'delivery_phone' => '0771234567',
            'delivery_note' => '', 'needed_by' => null, 'notes' => '', 'lines' => [
                ['source' => 'GIVEN', 'product_id' => $this->sheetHere, 'supplier_product_id' => null,
                    'description' => 'Bedsheet', 'qty' => 1, 'notes' => '', 'unit_price' => 100],
                ['source' => 'WAREHOUSE', 'product_id' => $this->bedHere, 'supplier_product_id' => $this->bedThere,
                    'description' => 'Cooler Bed', 'qty' => $qty, 'notes' => '', 'unit_price' => 800],
            ]])['order_id'];
        $this->lineId = $this->lineOf($this->orderId);
        $this->orders->markReady($this->orderId, $this->warehouse, $this->picker);
        return $this->orderId;
    }//ready order

    //the warehouse line of an order (the given one is never dispatchable)
    private function lineOf($order_id)
    {
        $stmt = $this->pdo->prepare("SELECT COLID FROM customerorderlines
            WHERE customerorders_COID = ? AND LineSource = 'WAREHOUSE' ORDER BY SortOrder LIMIT 1");
        $stmt->execute([$order_id]);
        return (int) $stmt->fetchColumn();
    }//line of

    //a ready order with an open dispatch on it
    private function openDispatch($needed = 1)
    {
        $this->readyOrder($needed);
        return $this->dispatches->open($this->orderId, $this->warehouse, $this->picker)['dispatch_id'];
    }//open dispatch

    //a second order of its own, so two dispatches can exist at once
    private function openDispatchOnAnotherOrder()
    {
        $keepOrder = $this->orderId;
        $keepInvoice = $this->invoiceId;
        $keepLine = $this->lineId;
        $dispatch = $this->openDispatch();
        $this->orderId = $keepOrder;
        $this->invoiceId = $keepInvoice;
        $this->lineId = $keepLine;
        return $dispatch;
    }//open dispatch on another order

    //one printed unit sticker for the warehouse's bed, as Print Barcode makes it
    private function printUnit()
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM barcodesettings WHERE shop_SHID = ?');
        $stmt->execute([$this->warehouse]);
        if ((int) $stmt->fetchColumn() === 0) {
            $this->insert('barcodesettings', ['shop_SHID' => $this->warehouse, 'UnitMode' => 1,
                'UnitPattern' => '{ITEM}{YY}{MM}{SEQ}', 'UnitSeqLength' => 4, 'UnitSeparator' => '']);
        }//unit barcodes on in the warehouse
        $batch = (new ProductUnits())->allocate($this->warehouse, $this->bedThere, date('Y-m-d'), 1, $this->picker);
        return $batch['codes'][0];
    }//print unit

    private function stockOf($product_id)
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(CurrentQty), 0) FROM inventory WHERE products_PDID = ?');
        $stmt->execute([$product_id]);
        return (float) $stmt->fetchColumn();
    }//stock of

    private function statusOf($id)
    {
        $stmt = $this->pdo->prepare('SELECT OrderStat FROM customerorders WHERE COID = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn();
    }//status of

    private function lineStatusOf($line_id)
    {
        $stmt = $this->pdo->prepare('SELECT LineStat FROM customerorderlines WHERE COLID = ?');
        $stmt->execute([$line_id]);
        return (int) $stmt->fetchColumn();
    }//line status of

    // ---- opening a dispatch ---------------------------------------------------------------------

    public function test_a_dispatch_opens_with_every_ready_line()
    {
        $this->readyOrder();

        $dispatch = $this->dispatches->open($this->orderId, $this->warehouse, $this->picker);

        $this->assertSame('DS_000001', $dispatch['dispatch_no']);
        $lines = $this->dispatches->get($dispatch['dispatch_id'], $this->warehouse)['lines'];
        $this->assertSame([1.0], array_map('floatval', array_column($lines, 'Needed')));
    }//a dispatch opens with every ready line

    public function test_a_line_given_at_the_shop_is_never_in_a_dispatch()
    {
        $dispatch = $this->openDispatch();

        $lines = $this->dispatches->get($dispatch, $this->warehouse)['lines'];

        $this->assertSame([$this->lineId], array_map('intval', array_column($lines, 'COLID')));
    }//given lines are never dispatched

    public function test_nothing_ready_means_nothing_to_dispatch()
    {
        $this->readyOrder();
        $this->pdo->prepare('UPDATE customerorderlines SET LineStat = ? WHERE COLID = ?')
            ->execute([WarehouseOrder::LINE_PENDING, $this->lineId]);

        $this->expectException(CustomerOrderRefused::class);
        $this->dispatches->open($this->orderId, $this->warehouse, $this->picker);
    }//nothing ready

    public function test_only_one_dispatch_is_open_on_an_order_at_a_time()
    {
        $this->openDispatch();

        $this->expectException(CustomerOrderRefused::class);
        $this->dispatches->open($this->orderId, $this->warehouse, $this->picker);
    }//one open dispatch at a time

    public function test_the_shop_cannot_open_a_dispatch_on_its_own_order()
    {
        $this->readyOrder();

        $this->expectException(CustomerOrderRefused::class);
        $this->dispatches->open($this->orderId, $this->shop, $this->cashier);
    }//only the warehouse dispatches

    public function test_an_open_dispatch_can_be_cancelled_and_moves_no_stock()
    {
        $dispatch = $this->openDispatch();
        $before = $this->stockOf($this->bedThere);

        $this->dispatches->cancel($dispatch, $this->warehouse, $this->picker);

        $this->assertSame($before, $this->stockOf($this->bedThere));
        $this->assertSame(WarehouseOrder::READY, $this->statusOf($this->orderId));
        $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM orderdispatchlines')->fetchColumn());
    }//cancelling moves no stock

    public function test_a_cancelled_dispatch_frees_the_order_for_another_one()
    {
        $dispatch = $this->openDispatch();
        $this->dispatches->cancel($dispatch, $this->warehouse, $this->picker);

        $second = $this->dispatches->open($this->orderId, $this->warehouse, $this->picker);

        $this->assertSame('DS_000002', $second['dispatch_no']);
    }//a cancelled dispatch frees the order
}//DispatchScanTest
