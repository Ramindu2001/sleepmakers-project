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

    //the van has room for one bed and not the pillow: the dispatcher says so when opening it
    public function test_a_dispatch_can_be_opened_for_part_of_what_is_ready()
    {
        $pillow = $this->createProduct($this->warehouse, 'PIL00001', 'Pillow');
        $this->addStock($pillow, $this->warehouse, 4, 'P1', 50, 90);
        $this->readyOrder(3);
        $extra = $this->orders->get($this->orderId, $this->warehouse, $this->picker);

        $dispatch = $this->dispatches->open($this->orderId, $this->warehouse, $this->picker,
            [['line_id' => $this->lineId, 'qty' => 1]])['dispatch_id'];

        $lines = $this->dispatches->get($dispatch, $this->warehouse)['lines'];
        $this->assertCount(1, $lines);
        $this->assertSame(1.0, (float) $lines[0]['Needed']);
    }//part of what is ready

    public function test_asking_for_more_than_is_owed_takes_only_what_is_owed()
    {
        $this->readyOrder(2);

        $dispatch = $this->dispatches->open($this->orderId, $this->warehouse, $this->picker,
            [['line_id' => $this->lineId, 'qty' => 99]])['dispatch_id'];

        $this->assertSame(2.0, (float) $this->dispatches->get($dispatch, $this->warehouse)['lines'][0]['Needed']);
    }//never more than is owed

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

    // ---- scanning ---------------------------------------------------------------------------------

    private function scan()
    {
        if ($this->scan === null) {
            $this->scan = new DispatchScan();
        }
        return $this->scan;
    }//scan

    public function test_scanning_the_bed_counts_one_against_its_line()
    {
        $dispatch = $this->openDispatch();

        $preview = $this->scan()->preview($dispatch, $this->warehouse, $this->picker, 'COO00001');

        $this->assertSame([1, 'ok'], [$preview['lines'][0]['qty'], $preview['lines'][0]['status']]);
        $this->assertTrue($preview['can_apply']);
    }//scanning the bed

    public function test_the_scan_screen_says_what_is_still_needed()
    {
        $dispatch = $this->openDispatch(2);

        $preview = $this->scan()->preview($dispatch, $this->warehouse, $this->picker, 'COO00001');

        $this->assertSame([2, 0, 2], [$preview['needed'][0]['needed'], $preview['needed'][0]['scanned'],
            $preview['needed'][0]['outstanding']]);
    }//what is still needed

    public function test_applying_records_what_was_scanned()
    {
        $dispatch = $this->openDispatch(2);

        $this->scan()->apply($dispatch, $this->warehouse, $this->picker, "COO00001\nCOO00001", []);

        $stmt = $this->pdo->prepare('SELECT SUM(Qty) FROM orderdispatchlines WHERE orderdispatches_DSID = ?');
        $stmt->execute([$dispatch]);
        $this->assertSame(2.0, (float) $stmt->fetchColumn());
    }//applying records the scans

    public function test_a_unit_sticker_is_recorded_against_the_customer()
    {
        $dispatch = $this->openDispatch();
        $code = $this->printUnit();

        $this->scan()->apply($dispatch, $this->warehouse, $this->picker, $code, []);

        //PlannedQty marks the row written when the dispatch was opened; the scans are the rest
        $stmt = $this->pdo->prepare('SELECT UnitBarcode, productunits_PUID FROM orderdispatchlines
            WHERE orderdispatches_DSID = ? AND PlannedQty IS NULL');
        $stmt->execute([$dispatch]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertSame($code, $row['UnitBarcode']);
        $this->assertNotNull($row['productunits_PUID']);
    }//a sticker is recorded

    public function test_a_code_we_do_not_know_is_refused()
    {
        $preview = $this->scan()->preview($this->openDispatch(), $this->warehouse, $this->picker, 'NOT-A-CODE');

        $this->assertSame('error', $preview['lines'][0]['status']);
        $this->assertStringContainsString('Not a product we know', $preview['lines'][0]['message']);
        $this->assertFalse($preview['can_apply']);
    }//an unknown code

    public function test_a_product_that_is_not_on_this_order_is_refused()
    {
        $this->createProduct($this->warehouse, 'PIL00001', 'Pillow');
        $preview = $this->scan()->preview($this->openDispatch(), $this->warehouse, $this->picker, 'PIL00001');

        $this->assertStringContainsString('Not on this order', $preview['lines'][0]['message']);
    }//not on this order

    public function test_an_item_already_given_at_the_shop_is_refused_by_name()
    {
        $this->createProduct($this->warehouse, 'LIN00001', 'Bedsheet');
        $preview = $this->scan()->preview($this->openDispatch(), $this->warehouse, $this->picker, 'LIN00001');

        $this->assertStringContainsString('Given at the shop', $preview['lines'][0]['message']);
    }//given at the shop

    public function test_the_same_sticker_twice_counts_once()
    {
        $dispatch = $this->openDispatch();
        $code = $this->printUnit();

        $preview = $this->scan()->preview($dispatch, $this->warehouse, $this->picker, $code . "\n" . $code);

        $this->assertSame(1, $preview['lines'][0]['apply_qty']);
        $this->assertStringContainsString('scanned twice', $preview['lines'][0]['message']);
        $this->assertTrue($preview['can_apply']);
    }//the same sticker twice

    public function test_more_than_the_line_needs_is_refused()
    {
        $dispatch = $this->openDispatch();

        $preview = $this->scan()->preview($dispatch, $this->warehouse, $this->picker, "COO00001\nCOO00001");

        $this->assertSame('error', $preview['lines'][0]['status']);
        $this->assertStringContainsString('Only 1 more needed', $preview['lines'][0]['message']);
    }//over-scanning

    public function test_what_is_already_in_the_dispatch_counts_towards_the_total()
    {
        $dispatch = $this->openDispatch(2);
        $this->scan()->apply($dispatch, $this->warehouse, $this->picker, 'COO00001', []);

        $ok = $this->scan()->preview($dispatch, $this->warehouse, $this->picker, 'COO00001');
        $tooMany = $this->scan()->preview($dispatch, $this->warehouse, $this->picker, "COO00001\nCOO00001");

        $this->assertTrue($ok['can_apply']);
        $this->assertSame('error', $tooMany['lines'][0]['status']);
    }//earlier scans count

    public function test_a_voided_sticker_is_refused()
    {
        $dispatch = $this->openDispatch();
        $code = $this->printUnit();
        $this->pdo->prepare('UPDATE productunits SET UnitStat = ? WHERE UnitBarcode = ?')
            ->execute([ProductUnits::VOIDED, $code]);

        $preview = $this->scan()->preview($dispatch, $this->warehouse, $this->picker, $code);

        $this->assertStringContainsString('Voided sticker', $preview['lines'][0]['message']);
    }//a voided sticker

    public function test_a_sticker_sitting_in_another_open_dispatch_is_refused()
    {
        $code = $this->printUnit();
        $theirs = $this->openDispatchOnAnotherOrder();
        $this->scan()->apply($theirs, $this->warehouse, $this->picker, $code, []);
        $mine = $this->openDispatch();

        $preview = $this->scan()->preview($mine, $this->warehouse, $this->picker, $code);

        $this->assertSame('error', $preview['lines'][0]['status']);
        $this->assertStringContainsString('Being dispatched on DS_', $preview['lines'][0]['message']);
    }//a sticker in another open dispatch

    //the sale was returned overnight, so nothing on it should go out
    public function test_a_dispatch_for_a_cancelled_invoice_is_refused()
    {
        $dispatch = $this->openDispatch();
        $this->pdo->prepare('UPDATE invoiceheader SET InvStat = 0 WHERE IHID = ?')->execute([$this->invoiceId]);

        $this->expectException(ScanRefused::class);
        $this->scan()->apply($dispatch, $this->warehouse, $this->picker, 'COO00001', []);
    }//a cancelled invoice

    public function test_someone_without_the_right_cannot_scan()
    {
        $dispatch = $this->openDispatch();
        $bare = $this->createRole('No rights');
        $outsider = $this->createUser('outsider', 'x', $bare);
        $this->assign($outsider, $this->warehouse, $bare);

        $this->expectException(ScanRefused::class);
        $this->scan()->preview($dispatch, $this->warehouse, $outsider, 'COO00001');
    }//no right, no scanning

    // ---- sending it -------------------------------------------------------------------------------

    public function test_completing_a_dispatch_takes_the_stock_and_marks_the_line()
    {
        $dispatch = $this->openDispatch();
        $this->scan()->apply($dispatch, $this->warehouse, $this->picker, 'COO00001', []);
        $before = $this->stockOf($this->bedThere);

        $this->dispatches->complete($dispatch, $this->warehouse, $this->picker, ['confirm_balance' => 1]);

        $this->assertSame($before - 1, $this->stockOf($this->bedThere));
        $this->assertSame(WarehouseOrder::LINE_DISPATCHED, $this->lineStatusOf($this->lineId));
        $this->assertSame(WarehouseOrder::DISPATCHED, $this->statusOf($this->orderId));
    }//completing takes the stock

    public function test_the_sale_is_costed_against_the_shops_invoice()
    {
        $dispatch = $this->openDispatch();
        $this->scan()->apply($dispatch, $this->warehouse, $this->picker, 'COO00001', []);

        $this->dispatches->complete($dispatch, $this->warehouse, $this->picker, ['confirm_balance' => 1]);

        $row = $this->pdo->query('SELECT invoice_headerID, shop_SHID, quantity, product_PDID
            FROM inventory_consumption')->fetch(PDO::FETCH_ASSOC);
        //the money belongs to the shop's invoice; the goods came off the warehouse's shelf
        $this->assertSame([$this->invoiceId, $this->warehouse, 1.0, $this->bedThere],
            [(int) $row['invoice_headerID'], (int) $row['shop_SHID'], (float) $row['quantity'],
                (int) $row['product_PDID']]);
    }//costed against the shop's invoice

    public function test_the_shops_own_stock_is_never_touched()
    {
        $this->addStock($this->bedHere, $this->shop, 4, 'S1', 100, 150);
        $dispatch = $this->openDispatch();
        $this->scan()->apply($dispatch, $this->warehouse, $this->picker, 'COO00001', []);

        $this->dispatches->complete($dispatch, $this->warehouse, $this->picker, ['confirm_balance' => 1]);

        $this->assertSame(4.0, $this->stockOf($this->bedHere));
    }//the shop's stock is untouched

    public function test_a_half_scanned_dispatch_cannot_be_completed()
    {
        $dispatch = $this->openDispatch(2);
        $this->scan()->apply($dispatch, $this->warehouse, $this->picker, 'COO00001', []);

        try {
            $this->dispatches->complete($dispatch, $this->warehouse, $this->picker, ['confirm_balance' => 1]);
            $this->fail('a half scanned dispatch was sent');
        } catch (CustomerOrderRefused $e) {
            $this->assertSame(422, $e->status);
        }
        $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM inventory_consumption')->fetchColumn());
    }//half scanned

    public function test_an_outstanding_balance_must_be_confirmed()
    {
        $dispatch = $this->openDispatch();
        $this->scan()->apply($dispatch, $this->warehouse, $this->picker, 'COO00001', []);

        try {
            $this->dispatches->complete($dispatch, $this->warehouse, $this->picker, []);
            $this->fail('expected the balance to be confirmed');
        } catch (CustomerOrderRefused $e) {
            $this->assertSame(409, $e->status);
            $this->assertStringContainsString('600', $e->getMessage());
        }

        $this->dispatches->complete($dispatch, $this->warehouse, $this->picker, ['confirm_balance' => 1]);
        $this->assertSame(WarehouseOrder::DISPATCHED, $this->statusOf($this->orderId));
    }//a balance must be confirmed

    public function test_a_paid_order_needs_no_confirmation()
    {
        $dispatch = $this->openDispatch();
        $this->pdo->prepare('UPDATE invoiceheader SET CustPayment = 1000, CustBalance = 0 WHERE IHID = ?')
            ->execute([$this->invoiceId]);
        $this->scan()->apply($dispatch, $this->warehouse, $this->picker, 'COO00001', []);

        $this->dispatches->complete($dispatch, $this->warehouse, $this->picker, []);

        $this->assertSame(WarehouseOrder::DISPATCHED, $this->statusOf($this->orderId));
    }//a paid order needs no confirmation

    public function test_stock_that_went_between_scanning_and_completing_refuses_the_whole_dispatch()
    {
        $dispatch = $this->openDispatch();
        $this->scan()->apply($dispatch, $this->warehouse, $this->picker, 'COO00001', []);
        $this->pdo->prepare('UPDATE inventory SET CurrentQty = 0 WHERE products_PDID = ?')->execute([$this->bedThere]);

        try {
            $this->dispatches->complete($dispatch, $this->warehouse, $this->picker, ['confirm_balance' => 1]);
            $this->fail('expected a refusal');
        } catch (CustomerOrderRefused $e) {
            $this->assertSame(409, $e->status);
        }
        $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM inventory_consumption')->fetchColumn());
        $this->assertSame(0.0, (float) $this->pdo->query('SELECT COALESCE(SUM(DispatchedQty), 0)
            FROM customerorderlines')->fetchColumn());
    }//stock that went in the meantime

    public function test_a_dispatched_sticker_is_marked_as_gone()
    {
        $dispatch = $this->openDispatch();
        $code = $this->printUnit();
        $this->scan()->apply($dispatch, $this->warehouse, $this->picker, $code, []);

        $this->dispatches->complete($dispatch, $this->warehouse, $this->picker, ['confirm_balance' => 1]);

        $stmt = $this->pdo->prepare('SELECT UnitStat, orderdispatches_DSID FROM productunits WHERE UnitBarcode = ?');
        $stmt->execute([$code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertSame([ProductUnits::DISPATCHED, $dispatch],
            [(int) $row['UnitStat'], (int) $row['orderdispatches_DSID']]);
    }//a sticker is marked as gone

    //two dispatchers race for the same sticker: the second one loses, whatever its preview said
    public function test_a_sticker_can_only_be_completed_onto_one_dispatch()
    {
        $code = $this->printUnit();
        $first = $this->openDispatch();
        $this->scan()->apply($first, $this->warehouse, $this->picker, $code, []);
        $second = $this->openDispatchOnAnotherOrder();
        //the second picker's screen was built before the first one pressed send
        $this->pdo->prepare("INSERT INTO orderdispatchlines (orderdispatches_DSID, customerorderlines_COLID,
            products_PDID, Qty, UnitBarcode, productunits_PUID, ScannedAt, ScannedBy)
            SELECT ?, (SELECT COLID FROM customerorderlines WHERE customerorders_COID =
                (SELECT customerorders_COID FROM orderdispatches WHERE DSID = ?) AND LineSource = 'WAREHOUSE' LIMIT 1),
                products_PDID, Qty, UnitBarcode, productunits_PUID, NOW(), ?
            FROM orderdispatchlines WHERE orderdispatches_DSID = ?")
            ->execute([$second, $second, $this->picker, $first]);

        $this->dispatches->complete($first, $this->warehouse, $this->picker, ['confirm_balance' => 1]);

        $this->expectException(CustomerOrderRefused::class);
        $this->dispatches->complete($second, $this->warehouse, $this->picker, ['confirm_balance' => 1]);
    }//one sticker, one dispatch

    public function test_a_second_dispatch_sends_what_is_left()
    {
        //the van only has room for two of the three, so the trip is opened for two
        $this->readyOrder(3);
        $dispatch = $this->dispatches->open($this->orderId, $this->warehouse, $this->picker,
            [['line_id' => $this->lineId, 'qty' => 2]])['dispatch_id'];
        $this->scan()->apply($dispatch, $this->warehouse, $this->picker, "COO00001\nCOO00001", []);
        $this->dispatches->complete($dispatch, $this->warehouse, $this->picker, ['confirm_balance' => 1]);

        $this->orders->markReady($this->orderId, $this->warehouse, $this->picker);
        $second = $this->dispatches->open($this->orderId, $this->warehouse, $this->picker)['dispatch_id'];

        $lines = $this->dispatches->get($second, $this->warehouse)['lines'];
        $this->assertSame(1.0, (float) $lines[0]['Needed']);
    }//a second dispatch sends the rest

    public function test_delivering_completes_the_order()
    {
        $dispatch = $this->openDispatch();
        $this->scan()->apply($dispatch, $this->warehouse, $this->picker, 'COO00001', []);
        $this->dispatches->complete($dispatch, $this->warehouse, $this->picker, ['confirm_balance' => 1]);

        $this->dispatches->delivered($dispatch, $this->warehouse, $this->picker, 'Left with the customer');

        $this->assertSame(WarehouseOrder::COMPLETED, $this->statusOf($this->orderId));
        $this->assertSame(WarehouseOrder::LINE_DELIVERED, $this->lineStatusOf($this->lineId));
    }//delivering completes the order

    public function test_only_a_sent_dispatch_can_be_delivered()
    {
        $dispatch = $this->openDispatch();

        $this->expectException(CustomerOrderRefused::class);
        $this->dispatches->delivered($dispatch, $this->warehouse, $this->picker, '');
    }//only a sent dispatch is delivered

    //a slow click, clicked twice: the customer must not end up "delivered" twice over
    public function test_delivering_the_same_dispatch_twice_changes_nothing()
    {
        $dispatch = $this->openDispatch();
        $this->scan()->apply($dispatch, $this->warehouse, $this->picker, 'COO00001', []);
        $this->dispatches->complete($dispatch, $this->warehouse, $this->picker, ['confirm_balance' => 1]);
        $this->dispatches->delivered($dispatch, $this->warehouse, $this->picker, 'Left with the customer');

        try {
            $this->dispatches->delivered($dispatch, $this->warehouse, $this->picker, 'again');
            $this->fail('the same dispatch was delivered twice');
        } catch (CustomerOrderRefused $e) {
            $this->assertSame(409, $e->status);
        }

        $stmt = $this->pdo->prepare('SELECT DeliveredQty FROM customerorderlines WHERE COLID = ?');
        $stmt->execute([$this->lineId]);
        $this->assertSame(1.0, (float) $stmt->fetchColumn());
    }//delivering twice

    //the picker scanned the bed, applied, then swept the same sticker again in the next batch
    public function test_a_sticker_already_in_this_dispatch_is_refused_not_counted_again()
    {
        $dispatch = $this->openDispatch(2);
        $code = $this->printUnit();
        $this->scan()->apply($dispatch, $this->warehouse, $this->picker, $code, []);

        $preview = $this->scan()->preview($dispatch, $this->warehouse, $this->picker, $code);

        $this->assertSame('error', $preview['lines'][0]['status']);
        $this->assertStringContainsString('Already scanned', $preview['lines'][0]['message']);
        $this->assertFalse($preview['can_apply']);
    }//a sticker already in this dispatch

    public function test_the_batch_is_recorded_on_the_scan_and_not_on_the_plan()
    {
        $dispatch = $this->openDispatch();
        $this->scan()->apply($dispatch, $this->warehouse, $this->picker, 'COO00001', []);

        $this->dispatches->complete($dispatch, $this->warehouse, $this->picker, ['confirm_balance' => 1]);

        $stmt = $this->pdo->prepare('SELECT PlannedQty, InventoryID, Batch_ID FROM orderdispatchlines
            WHERE orderdispatches_DSID = ? ORDER BY DDID');
        $stmt->execute([$dispatch]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $plan = $rows[0];
        $scan = $rows[1];
        $this->assertNull($plan['InventoryID'], 'the plan row must not carry a batch');
        $this->assertNotNull($scan['InventoryID'], 'the scanned row must carry the batch it came from');
        $this->assertSame('B1', $scan['Batch_ID']);
    }//the batch is recorded on the scan

    public function test_an_order_with_no_invoice_can_still_be_scanned()
    {
        $this->orderId = $this->orders->createFromSale($this->shop, $this->cashier, [
            'invoice_id' => null, 'supplier_shop_id' => $this->warehouse, 'customer_id' => null,
            'cust_name' => 'Walk in', 'cust_phone' => '0770000000', 'cust_address' => '',
            'deliver_to' => 2, 'delivery_address' => '', 'delivery_phone' => '', 'delivery_note' => '',
            'needed_by' => null, 'notes' => '', 'lines' => [
                ['source' => 'WAREHOUSE', 'product_id' => $this->bedHere, 'supplier_product_id' => $this->bedThere,
                    'description' => 'Cooler Bed', 'qty' => 1, 'notes' => '', 'unit_price' => 800],
            ]])['order_id'];
        $this->lineId = $this->lineOf($this->orderId);
        $this->orders->markReady($this->orderId, $this->warehouse, $this->picker);
        $dispatch = $this->dispatches->open($this->orderId, $this->warehouse, $this->picker)['dispatch_id'];

        $this->scan()->apply($dispatch, $this->warehouse, $this->picker, 'COO00001', []);

        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(Qty), 0) FROM orderdispatchlines
            WHERE orderdispatches_DSID = ? AND PlannedQty IS NULL');
        $stmt->execute([$dispatch]);
        $this->assertSame(1.0, (float) $stmt->fetchColumn());
    }//an order with no invoice

    public function test_a_sent_dispatch_cannot_be_cancelled()
    {
        $dispatch = $this->openDispatch();
        $this->scan()->apply($dispatch, $this->warehouse, $this->picker, 'COO00001', []);
        $this->dispatches->complete($dispatch, $this->warehouse, $this->picker, ['confirm_balance' => 1]);

        $this->expectException(CustomerOrderRefused::class);
        $this->dispatches->cancel($dispatch, $this->warehouse, $this->picker);
    }//a sent dispatch stays sent
}//DispatchScanTest
