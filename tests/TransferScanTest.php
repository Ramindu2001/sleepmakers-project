<?php
final class TransferScanTest extends DatabaseTestCase
{
    private TransferScan $scan;
    private int $warehouse;
    private int $showroom;
    private int $alice;
    private int $bob;
    private int $bed;
    private int $sheet;
    private int $bed1;
    private int $bed2;
    private int $sheet1;
    private int $transfer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scan = new TransferScan();
        $company = $this->createCompany();
        $this->warehouse = $this->createShop($company, ['ShopName' => 'Warehouse']);
        $this->showroom = $this->createShop($company, ['ShopName' => 'Valentino Italy']);
        $keeper = $this->createRole('Store Keeper');
        $this->grant($keeper, 4, ['is_edit']);
        $this->alice = $this->createUser('alice', 'x', $keeper);
        $this->assign($this->alice, $this->warehouse, $keeper);
        $this->bob = $this->createUser('bob', 'x', $keeper);
        $this->assign($this->bob, $this->showroom, $keeper);
        $this->bed = $this->createProduct($this->warehouse, 'COO00001', 'Bed');
        $this->sheet = $this->createProduct($this->warehouse, 'LIN00001', 'Bedsheet');
        $this->bed1 = $this->addStock($this->bed, $this->warehouse, 5, 'B1', 900, 1400);
        $this->bed2 = $this->addStock($this->bed, $this->warehouse, 5, 'B2', 950, 1450);
        $this->sheet1 = $this->addStock($this->sheet, $this->warehouse, 10, 'S1', 200, 350);
        $this->transfer = $this->createTransfer($this->warehouse, $this->showroom, $this->alice);
    }

    private function lines(array $preview)
    {
        $out = [];
        foreach ($preview['lines'] as $line) {
            $out[$line['key']] = $line;
        }
        return $out;
    }

    private function details()
    {
        return $this->pdo->query('SELECT InventoryID, products_PDID, TransferQty, ReceivedQty, UnitPurchasePrice, UnitSellingPrice,
            TransferTotalAmount, Batch_ID, VariationID, RackID, TransferStat FROM transferdetails ORDER BY TDID')->fetchAll(PDO::FETCH_ASSOC);
    }

    private function codes($code, $times)
    {
        return str_repeat($code . "\n", $times);
    }

    // ---- sending ---------------------------------------------------------------------------

    public function test_send_takes_the_oldest_stock_first_and_splits_across_batches()
    {
        $line = $this->lines($this->scan->sendPreview($this->transfer, $this->warehouse, $this->alice, $this->codes('COO00001', 7)))['COO00001'];
        $this->assertSame('ok', $line['status']);
        $this->assertSame(10, $line['available']);
        $this->assertSame([['inventory_id' => $this->bed1, 'batch_id' => 'B1', 'qty' => 5, 'merge' => false],
            ['inventory_id' => $this->bed2, 'batch_id' => 'B2', 'qty' => 2, 'merge' => false]], $line['batches']);
        $this->assertSame('From batch B1 × 5, B2 × 2', $line['message']);

        $result = $this->scan->sendApply($this->transfer, $this->warehouse, $this->alice, $this->codes('COO00001', 7), []);
        $this->assertSame(['doc_id' => $this->transfer, 'lines' => 1, 'qty' => 7], $result['result']);
        $this->assertSame([
            ['InventoryID' => $this->bed1, 'products_PDID' => $this->bed, 'TransferQty' => '5.000', 'ReceivedQty' => '5.000',
             'UnitPurchasePrice' => '900.00', 'UnitSellingPrice' => '1400.00', 'TransferTotalAmount' => '4500.00',
             'Batch_ID' => 'B1', 'VariationID' => 0, 'RackID' => 1, 'TransferStat' => 0],
            ['InventoryID' => $this->bed2, 'products_PDID' => $this->bed, 'TransferQty' => '2.000', 'ReceivedQty' => '2.000',
             'UnitPurchasePrice' => '950.00', 'UnitSellingPrice' => '1450.00', 'TransferTotalAmount' => '1900.00',
             'Batch_ID' => 'B2', 'VariationID' => 0, 'RackID' => 1, 'TransferStat' => 0],
        ], $this->details());
    }

    public function test_send_counts_what_the_transfer_already_takes_and_adds_to_its_line()
    {
        $this->insert('transferdetails', ['TransferQty' => 4, 'ReceivedQty' => 4, 'UnitPurchasePrice' => 900, 'UnitSellingPrice' => 1400,
            'TransferTotalAmount' => 3600, 'InventoryID' => $this->bed1, 'products_PDID' => $this->bed, 'VariationID' => 0, 'RackID' => 1,
            'TransferStat' => 0, 'TransferHeader_THID' => $this->transfer, 'Batch_ID' => 'B1']);

        $tooMany = $this->lines($this->scan->sendPreview($this->transfer, $this->warehouse, $this->alice, $this->codes('COO00001', 7)))['COO00001'];
        $this->assertSame('Only 6 in stock (4 already on this transfer)', $tooMany['message']);

        $merge = $this->lines($this->scan->sendPreview($this->transfer, $this->warehouse, $this->alice, $this->codes('COO00001', 3)))['COO00001'];
        $this->assertSame('From batch B1 × 1, B2 × 2 - adds to the lines already on this transfer', $merge['message']);

        $this->scan->sendApply($this->transfer, $this->warehouse, $this->alice, $this->codes('COO00001', 3), []);
        $rows = $this->details();
        $this->assertCount(2, $rows);
        $this->assertSame(['5.000', '5.000', '4500.00'], [$rows[0]['TransferQty'], $rows[0]['ReceivedQty'], $rows[0]['TransferTotalAmount']]);
        $this->assertSame(['2.000', $this->bed2], [$rows[1]['TransferQty'], $rows[1]['InventoryID']]);
    }

    public function test_send_cannot_take_more_than_is_in_stock()
    {
        $line = $this->lines($this->scan->sendPreview($this->transfer, $this->warehouse, $this->alice, $this->codes('COO00001', 11)))['COO00001'];
        $this->assertSame(['error', 'Only 10 in stock (0 already on this transfer)'], [$line['status'], $line['message']]);
        try {
            $this->scan->sendApply($this->transfer, $this->warehouse, $this->alice, $this->codes('COO00001', 11), []);
            $this->fail('expected a refusal');
        } catch (ScanRefused $e) {
            $this->assertSame(422, $e->status);
        }
        $this->assertSame([], $this->details());
    }

    public function test_send_refuses_unknown_codes_and_service_items()
    {
        $this->createProduct($this->warehouse, 'SRV00001', 'Delivery', ['ItemType' => 'S']);
        $lines = $this->lines($this->scan->sendPreview($this->transfer, $this->warehouse, $this->alice, "SRV00001\nNOPE\nLIN00001"));
        $this->assertSame('Service item - no stock', $lines['SRV00001']['message']);
        $this->assertSame('Not a product in this shop', $lines['NOPE']['message']);
        $this->assertSame('ok', $lines['LIN00001']['status']);
    }

    public function test_send_guards_against_the_same_batch_twice()
    {
        $this->scan->sendApply($this->transfer, $this->warehouse, $this->alice, $this->codes('LIN00001', 2), []);
        try {
            $this->scan->sendApply($this->transfer, $this->warehouse, $this->alice, $this->codes('LIN00001', 2), []);
            $this->fail('expected a refusal');
        } catch (ScanRefused $e) {
            $this->assertSame([409, 'duplicate'], [$e->status, $e->confirm]);
        }
        $this->scan->sendApply($this->transfer, $this->warehouse, $this->alice, $this->codes('LIN00001', 2), ['confirm_duplicate' => 1]);
        $this->assertCount(1, $this->details());
        $this->assertSame('4.000', $this->details()[0]['TransferQty']);
    }

    public function test_send_is_refused_to_the_receiving_shop_closed_transfers_and_users_without_rights()
    {
        $viewer = $this->createRole('Viewer');
        $this->grant($viewer, 4, ['is_view']);
        $carol = $this->createUser('carol', 'x', $viewer);
        $this->assign($carol, $this->warehouse, $viewer);
        $closed = $this->createTransfer($this->warehouse, $this->showroom, $this->alice, 2);

        foreach ([[$this->transfer, $this->showroom, $this->bob, 404], [$closed, $this->warehouse, $this->alice, 409],
                  [$this->transfer, $this->warehouse, $carol, 403]] as [$transfer, $shop, $user, $status]) {
            try {
                $this->scan->sendPreview($transfer, $shop, $user, 'COO00001');
                $this->fail('expected ' . $status);
            } catch (ScanRefused $e) {
                $this->assertSame($status, $e->status);
            }
        }
    }
}
