<?php
//Model/product_unit_class.php: one row per printed unit, its code, and the rules that make
//the same unit impossible to register twice.
final class ProductUnitsTest extends DatabaseTestCase
{
    private ProductUnits $units;
    private int $warehouse;
    private int $showroom;
    private int $bed;
    private int $sheet;
    private int $alice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->units = new ProductUnits();
        $company = $this->createCompany();
        $this->warehouse = $this->createShop($company, ['ShopName' => 'Warehouse']);
        $this->showroom = $this->createShop($company, ['ShopName' => 'Valentino Italy']);
        $this->alice = $this->createUser('alice', 'x', $this->createRole('Store Keeper'));
        $this->bed = $this->createProduct($this->warehouse, 'COO00001', 'Cooler Mattress');
        $this->sheet = $this->createProduct($this->warehouse, 'LIN00001', 'Bedsheet');
    }

    private function print($product_id, $date, $qty, $shop_id = null)
    {
        return $this->units->allocate($shop_id ?? $this->warehouse, $product_id, $date, $qty, $this->alice);
    }

    // ---- building the code -------------------------------------------------------------

    public function test_a_unit_code_is_the_item_the_month_and_a_serial()
    {
        $batch = $this->print($this->bed, '2025-09-12', 3);

        $this->assertSame(['COO0000125090001', 'COO0000125090002', 'COO0000125090003'], $batch['codes']);
    }

    public function test_the_serial_carries_on_where_the_last_print_left_off()
    {
        $this->print($this->bed, '2025-09-12', 2);
        $batch = $this->print($this->bed, '2025-09-30', 2);

        $this->assertSame(['COO0000125090003', 'COO0000125090004'], $batch['codes']);
    }

    public function test_a_new_month_and_a_new_item_each_start_at_one()
    {
        $this->print($this->bed, '2025-09-12', 2);

        $this->assertSame(['COO0000125100001'], $this->print($this->bed, '2025-10-01', 1)['codes']);
        $this->assertSame(['LIN0000125090001'], $this->print($this->sheet, '2025-09-12', 1)['codes']);
    }

    public function test_the_number_series_is_named_after_everything_but_the_serial()
    {
        $rules = $this->units->settings($this->warehouse);

        $this->assertSame('unit:COO000012509', $this->units->scopeKey($rules, 'COO00001', '2025-09-12'));
        $this->assertSame('unit:COO000012510', $this->units->scopeKey($rules, 'COO00001', '2025-10-01'));
        $this->assertSame('unit:LIN000012509', $this->units->scopeKey($rules, 'LIN00001', '2025-09-12'));

        $dashed = ['mode' => true, 'pattern' => '{ITEM}-{YY}-{MM}-{SEQ}', 'seq_length' => 4, 'separator' => '-'];
        $this->assertSame('unit:COO00001-25-09', $this->units->scopeKey($dashed, 'COO00001', '2025-09-12'));
    }

    public function test_the_shop_can_change_the_pattern()
    {
        $this->insert('barcodesettings', ['shop_SHID' => $this->warehouse, 'UnitMode' => 1,
            'UnitPattern' => '{ITEM}{YY}{MM}{DD}{SEQ}', 'UnitSeqLength' => 3, 'UnitSeparator' => '-']);

        $this->assertSame(['COO00001-25-09-12-001'], $this->print($this->bed, '2025-09-12', 1)['codes']);
    }

    // ---- what is written ---------------------------------------------------------------

    public function test_every_unit_is_recorded_with_its_product_and_the_day_it_was_made()
    {
        $batch = $this->print($this->bed, '2025-09-12', 2);
        $rows = $this->units->forPrintRef($this->warehouse, $batch['print_ref']);

        $this->assertCount(2, $rows);
        $this->assertSame(['COO00001', $this->bed, '2025-09-12', 1, ProductUnits::PRINTED],
            [$rows[0]['ItemBarcode'], (int) $rows[0]['products_PDID'], $rows[0]['ProducedDate'],
                (int) $rows[0]['SeqNo'], (int) $rows[0]['UnitStat']]);
    }

    public function test_each_print_job_has_its_own_reference()
    {
        $first = $this->print($this->bed, '2025-09-12', 1);
        $second = $this->print($this->bed, '2025-09-12', 1);

        $this->assertSame('UP_000001', $first['print_ref']);
        $this->assertSame('UP_000002', $second['print_ref']);
    }

    public function test_the_same_code_can_never_be_stored_twice()
    {
        $batch = $this->print($this->bed, '2025-09-12', 1);

        $this->expectException(PDOException::class);
        $this->insert('productunits', ['UnitBarcode' => $batch['codes'][0], 'ItemBarcode' => 'COO00001',
            'products_PDID' => $this->bed, 'shop_SHID' => $this->warehouse, 'ProducedDate' => '2025-09-12',
            'SeqNo' => 99, 'PrintRef' => 'UP_000009', 'PrintedAt' => date('Y-m-d H:i:s'), 'PrintedBy' => $this->alice]);
    }

    public function test_a_product_with_no_barcode_cannot_have_units()
    {
        $blank = $this->createProduct($this->warehouse, '', 'No code');

        $this->expectException(UnitBarcodeRefused::class);
        $this->print($blank, '2025-09-12', 1);
    }

    public function test_nothing_is_written_when_the_quantity_is_not_a_sensible_number()
    {
        foreach ([0, -3, 100000] as $qty) {
            try {
                $this->print($this->bed, '2025-09-12', $qty);
                $this->fail('expected a refusal for quantity ' . $qty);
            } catch (UnitBarcodeRefused $e) {
                //expected
            }
        }
        $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM productunits')->fetchColumn());
    }

    // ---- resolving a scanned code --------------------------------------------------------

    public function test_a_unit_code_resolves_to_its_product_in_the_shop_that_scans_it()
    {
        $code = $this->print($this->bed, '2025-09-12', 1)['codes'][0];
        $showroomBed = $this->createProduct($this->showroom, 'COO00001', 'Cooler Mattress');

        $here = $this->units->resolve([$code], $this->warehouse);
        $there = $this->units->resolve([$code], $this->showroom);

        $this->assertSame([$this->bed, '2025-09-12'], [$here[$code]['product_id'], $here[$code]['ProducedDate']]);
        $this->assertSame($showroomBed, $there[$code]['product_id']);
    }

    public function test_resolving_ignores_case_and_unknown_codes()
    {
        $code = $this->print($this->bed, '2025-09-12', 1)['codes'][0];

        $found = $this->units->resolve([strtolower($code), 'NOT-A-UNIT'], $this->warehouse);

        $this->assertSame([strtolower($code)], array_keys($found));
        $this->assertSame($code, $found[strtolower($code)]['UnitBarcode']);
    }

    public function test_a_unit_whose_product_is_not_in_this_shop_has_no_product()
    {
        $code = $this->print($this->bed, '2025-09-12', 1)['codes'][0];

        $found = $this->units->resolve([$code], $this->showroom);

        $this->assertNull($found[$code]['product_id']);
    }

    // ---- receiving -----------------------------------------------------------------------

    public function test_receiving_a_unit_records_the_grn_it_went_into()
    {
        $code = $this->print($this->bed, '2025-09-12', 1)['codes'][0];
        $grn = $this->createGrn($this->warehouse, $this->alice);

        $this->units->markReceived([$code], $grn, 77, $this->alice);
        $row = $this->units->resolve([$code], $this->warehouse)[$code];

        $this->assertSame([ProductUnits::RECEIVED, $grn, 77],
            [(int) $row['UnitStat'], (int) $row['GRNHeader_GHID'], (int) $row['grndetails_GDID']]);
        $this->assertNotNull($row['ReceivedAt']);
    }

    public function test_a_unit_that_is_already_received_is_not_taken_a_second_time()
    {
        $code = $this->print($this->bed, '2025-09-12', 1)['codes'][0];
        $first = $this->createGrn($this->warehouse, $this->alice);
        $second = $this->createGrn($this->warehouse, $this->alice);

        $this->assertSame(1, $this->units->markReceived([$code], $first, 1, $this->alice));
        $this->assertSame(0, $this->units->markReceived([$code], $second, 2, $this->alice));
        $this->assertSame($first, (int) $this->units->resolve([$code], $this->warehouse)[$code]['GRNHeader_GHID']);
    }

    // ---- reprinting and reporting ---------------------------------------------------------

    public function test_a_reprint_gives_back_the_same_codes_and_allocates_nothing()
    {
        $batch = $this->print($this->bed, '2025-09-12', 2);

        $again = $this->units->reprint($this->warehouse, $batch['print_ref']);

        $this->assertSame($batch['codes'], array_column($again, 'UnitBarcode'));
        $this->assertSame([2, 2], array_map('intval', array_column($again, 'PrintCount')));
        $this->assertSame(2, (int) $this->pdo->query('SELECT COUNT(*) FROM productunits')->fetchColumn());
    }

    public function test_the_production_summary_counts_units_per_product_and_day()
    {
        $this->print($this->bed, '2025-09-12', 3);
        $this->print($this->bed, '2025-09-13', 2);
        $this->print($this->sheet, '2025-09-12', 1);
        $received = $this->print($this->bed, '2025-09-12', 1);
        $this->units->markReceived($received['codes'], $this->createGrn($this->warehouse, $this->alice), 1, $this->alice);

        $summary = $this->units->produced($this->warehouse, '2025-09-01', '2025-09-30');

        //by day, then by item name
        $this->assertSame([
            ['date' => '2025-09-12', 'product_id' => $this->sheet, 'name' => 'Bedsheet', 'printed' => 1, 'received' => 0],
            ['date' => '2025-09-12', 'product_id' => $this->bed, 'name' => 'Cooler Mattress', 'printed' => 4, 'received' => 1],
            ['date' => '2025-09-13', 'product_id' => $this->bed, 'name' => 'Cooler Mattress', 'printed' => 2, 'received' => 0],
        ], $summary);
    }

    public function test_how_many_were_made_on_one_day_is_a_single_number()
    {
        $this->print($this->bed, '2025-09-12', 3);
        $this->print($this->sheet, '2025-09-12', 4);
        $this->print($this->bed, '2025-09-13', 5);

        $this->assertSame(7, $this->units->countProduced($this->warehouse, '2025-09-12'));
    }
}
