<?php
//Model/product_unit_class.php: one row per printed unit, its code, and the rules that make
//the same unit impossible to register twice.
final class ProductUnitsTest extends DatabaseTestCase
{
    private ProductUnits $units;
    private int $company;
    private int $warehouse;
    private int $showroom;
    private int $bed;
    private int $sheet;
    private int $pillow;
    private int $alice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->units = new ProductUnits();
        $this->company = $this->createCompany();
        $this->warehouse = $this->createShop($this->company, ['ShopName' => 'Warehouse']);
        $this->showroom = $this->createShop($this->company, ['ShopName' => 'Valentino Italy']);
        $this->alice = $this->createUser('alice', 'x', $this->createRole('Store Keeper'));
        $this->bed = $this->createProduct($this->warehouse, 'COO00001', 'Cooler Mattress');
        $this->sheet = $this->createProduct($this->warehouse, 'LIN00001', 'Bedsheet');
        $this->pillow = $this->createProduct($this->warehouse, 'PIL00001', 'Pillow');
    }

    private function print($product_id, $date, $qty, $shop_id = null)
    {
        return $this->units->allocate($shop_id ?? $this->warehouse, $product_id, $date, $qty, $this->alice);
    }

    //a unit row as it was written before the day's series was shared: only its code and serial matter
    private function legacy($code, $item, $product_id, $date, $seq, $shop_id = null, $state = ProductUnits::PRINTED)
    {
        return $this->insert('productunits', ['UnitBarcode' => $code, 'ItemBarcode' => $item,
            'products_PDID' => $product_id, 'shop_SHID' => $shop_id ?? $this->warehouse, 'ProducedDate' => $date,
            'SeqNo' => $seq, 'UnitStat' => $state, 'PrintRef' => 'UP_OLD', 'PrintedAt' => date('Y-m-d H:i:s'),
            'PrintedBy' => $this->alice]);
    }

    //the warehouse numbers by the day with a serial of this many digits
    private function serialDigits($digits)
    {
        $this->insert('barcodesettings', ['shop_SHID' => $this->warehouse, 'UnitMode' => 1,
            'UnitPattern' => '{ITEM}{YY}{MM}{DD}{SEQ}', 'UnitSeqLength' => $digits, 'UnitSeparator' => '']);
    }

    private function rowCount($sql)
    {
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    // ---- building the code -------------------------------------------------------------

    public function test_a_unit_code_is_the_item_the_day_and_a_serial()
    {
        $batch = $this->print($this->bed, '2025-09-12', 3);

        $this->assertSame(['COO000012509120001', 'COO000012509120002', 'COO000012509120003'], $batch['codes']);
    }

    public function test_a_shop_with_no_saved_rules_numbers_by_the_day()
    {
        $this->assertSame('{ITEM}{YY}{MM}{DD}{SEQ}', $this->units->settings($this->warehouse)['pattern']);
    }

    public function test_the_serial_carries_on_where_the_last_print_left_off()
    {
        $this->print($this->bed, '2025-09-12', 2);
        $batch = $this->print($this->bed, '2025-09-12', 2);

        $this->assertSame(['COO000012509120003', 'COO000012509120004'], $batch['codes']);
    }

    public function test_every_product_made_on_a_day_shares_one_running_serial()
    {
        $codes = array_merge(
            $this->print($this->bed, '2025-09-12', 2)['codes'],       //A, A
            $this->print($this->sheet, '2025-09-12', 1)['codes'],     //B
            $this->print($this->pillow, '2025-09-12', 1)['codes'],    //C
            $this->print($this->sheet, '2025-09-12', 1)['codes']      //B again
        );

        $this->assertSame(['COO000012509120001', 'COO000012509120002', 'LIN000012509120003',
            'PIL000012509120004', 'LIN000012509120005'], $codes);
    }

    public function test_one_job_over_several_products_shares_the_serial_too()
    {
        $batch = $this->units->allocateBatch($this->warehouse, [$this->bed => 2, $this->sheet => 2], '2025-09-12', $this->alice);
        $rows = $this->pdo->query('SELECT UnitBarcode FROM productunits ORDER BY PUID')->fetchAll(PDO::FETCH_COLUMN);

        $this->assertSame(['COO000012509120001', 'COO000012509120002', 'LIN000012509120003', 'LIN000012509120004'], $rows);
        $this->assertSame([$this->bed => 2, $this->sheet => 2], $batch['counts']);
    }

    public function test_a_new_day_starts_again_at_one()
    {
        $this->print($this->bed, '2025-09-12', 2);

        $this->assertSame(['LIN000012509130001'], $this->print($this->sheet, '2025-09-13', 1)['codes']);
        $this->assertSame(['COO000012509120003'], $this->print($this->bed, '2025-09-12', 1)['codes'], 'the earlier day carries on');
    }

    public function test_two_shops_of_one_company_share_one_series()
    {
        $showroomPiece = $this->createProduct($this->showroom, 'SHW00001', 'Showroom piece');
        $this->print($this->bed, '2025-09-12', 1);

        $batch = $this->print($showroomPiece, '2025-09-12', 1, $this->showroom);

        $this->assertSame(['SHW000012509120002'], $batch['codes']);
    }

    public function test_two_companies_never_share_a_series()
    {
        $elsewhere = $this->createShop($this->createCompany(), ['ShopName' => 'Elsewhere']);
        $theirs = $this->createProduct($elsewhere, 'ELS00001', 'Their bed');
        $this->print($this->bed, '2025-09-12', 3);

        $batch = $this->print($theirs, '2025-09-12', 1, $elsewhere);

        $this->assertSame(['ELS000012509120001'], $batch['codes']);
    }

    public function test_the_number_series_is_named_after_the_company_and_the_date_part()
    {
        $rules = $this->units->settings($this->warehouse);
        $key = 'unit:c' . $this->company . ':';

        $this->assertSame($key . '250912', $this->units->scopeKey($rules, $this->company, '2025-09-12'));
        $this->assertSame($key . '251001', $this->units->scopeKey($rules, $this->company, '2025-10-01'));

        $monthly = ['mode' => true, 'pattern' => '{ITEM}{YY}{MM}{SEQ}', 'seq_length' => 4, 'separator' => ''];
        $this->assertSame($key . '2509', $this->units->scopeKey($monthly, $this->company, '2025-09-12'));
        $this->assertSame($key . '2509', $this->units->scopeKey($monthly, $this->company, '2025-09-30'));

        $dashed = ['mode' => true, 'pattern' => '{ITEM}-{YY}-{MM}-{SEQ}', 'seq_length' => 4, 'separator' => '-'];
        $this->assertSame($key . '25-09', $this->units->scopeKey($dashed, $this->company, '2025-09-12'));

        $joined = ['mode' => true, 'pattern' => '{ITEM}{YY}{MM}{DD}{SEQ}', 'seq_length' => 4, 'separator' => '-'];
        $this->assertSame($key . '25-09-12', $this->units->scopeKey($joined, $this->company, '2025-09-12'));

        $undated = ['mode' => true, 'pattern' => '{ITEM}{SEQ}', 'seq_length' => 4, 'separator' => ''];
        $this->assertSame($key, $this->units->scopeKey($undated, $this->company, '2025-09-12'));
    }

    public function test_the_shop_can_change_the_pattern()
    {
        $this->insert('barcodesettings', ['shop_SHID' => $this->warehouse, 'UnitMode' => 1,
            'UnitPattern' => '{ITEM}{YY}{MM}{DD}{SEQ}', 'UnitSeqLength' => 3, 'UnitSeparator' => '-']);

        $this->assertSame(['COO00001-25-09-12-001'], $this->print($this->bed, '2025-09-12', 1)['codes']);
    }

    // ---- a series that already holds serials -------------------------------------------

    public function test_a_day_that_already_has_per_item_serials_carries_on_after_the_highest()
    {
        //how the numbering used to run: every item counted from one
        foreach ([1, 2, 3] as $n) {
            $this->legacy(sprintf('COO00001250912%04d', $n), 'COO00001', $this->bed, '2025-09-12', $n);
        }
        foreach ([1, 2] as $n) {
            $this->legacy(sprintf('LIN00001250912%04d', $n), 'LIN00001', $this->sheet, '2025-09-12', $n);
        }

        $this->assertSame(['COO000012509120004'], $this->print($this->bed, '2025-09-12', 1)['codes']);
        $this->assertSame(['LIN000012509120005'], $this->print($this->sheet, '2025-09-12', 1)['codes']);
    }

    public function test_a_voided_unit_still_holds_its_serial()
    {
        $this->legacy('COO000012509120007', 'COO00001', $this->bed, '2025-09-12', 7, null, ProductUnits::VOIDED);

        $this->assertSame(['LIN000012509120008'], $this->print($this->sheet, '2025-09-12', 1)['codes']);
    }

    public function test_units_of_another_company_do_not_move_the_start()
    {
        $elsewhere = $this->createShop($this->createCompany(), ['ShopName' => 'Elsewhere']);
        $theirs = $this->createProduct($elsewhere, 'ELS00001', 'Their bed');
        $this->legacy('ELS000012509120009', 'ELS00001', $theirs, '2025-09-12', 9, $elsewhere);

        $this->assertSame(['COO000012509120001'], $this->print($this->bed, '2025-09-12', 1)['codes']);
    }

    public function test_a_monthly_pattern_starts_after_the_highest_serial_of_the_whole_month()
    {
        $this->insert('barcodesettings', ['shop_SHID' => $this->warehouse, 'UnitMode' => 1,
            'UnitPattern' => '{ITEM}{YY}{MM}{SEQ}', 'UnitSeqLength' => 4, 'UnitSeparator' => '']);
        $this->legacy('COO0000125090004', 'COO00001', $this->bed, '2025-09-05', 4);

        //another day of the same month: the code does not say which day, so the series is the month's
        $this->assertSame(['LIN0000125090005'], $this->print($this->sheet, '2025-09-12', 1)['codes']);
        $this->assertSame(['LIN0000125100001'], $this->print($this->sheet, '2025-10-01', 1)['codes']);
    }

    // ---- a series that is full ---------------------------------------------------------

    public function test_numbering_stops_when_the_serial_has_no_digits_left_and_gives_nothing_away()
    {
        $this->serialDigits(1);
        $this->print($this->bed, '2025-09-12', 9);        //1 to 9 fills one digit

        try {
            $this->print($this->sheet, '2025-09-12', 1);
            $this->fail('the tenth unit of the day should have been refused');
        } catch (UnitBarcodeRefused $e) {
            $this->assertSame(422, $e->status);
            $this->assertStringContainsString('Serial digits', $e->getMessage());
        }

        $this->assertSame(9, $this->rowCount('SELECT COUNT(*) FROM productunits'));
        $next = $this->pdo->prepare('SELECT NextValue FROM barcodesequence WHERE shop_SHID = 0 AND ScopeKey = ?');
        $next->execute(['unit:c' . $this->company . ':250912']);
        $this->assertSame(10, (int) $next->fetchColumn(), 'the refused number went back');
    }

    public function test_a_job_that_runs_out_of_serials_numbers_nothing_at_all()
    {
        $this->serialDigits(1);

        try {
            $this->units->allocateBatch($this->warehouse, [$this->bed => 5, $this->sheet => 6], '2025-09-12', $this->alice);
            $this->fail('eleven units do not fit one digit');
        } catch (UnitBarcodeRefused $e) {
            $this->assertSame(422, $e->status);
        }

        $this->assertSame(0, $this->rowCount('SELECT COUNT(*) FROM productunits'));
        $this->assertSame(0, $this->rowCount('SELECT COUNT(*) FROM barcodesequence WHERE shop_SHID = 0'), 'no counter was left behind');
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

    // ---- the two halves of a code, and finding a product from a sticker ----------------

    public function test_a_code_splits_into_the_item_and_the_rest()
    {
        $this->assertSame(['MATCOO00005', '2610010006'], ProductUnits::splitCode('MATCOO000052610010006', 'MATCOO00005'));
        //an item barcode that is long and ends in digits splits by the item, never by a width
        $this->assertSame(['MATCOO26092300001', '2610060001'],
            ProductUnits::splitCode('MATCOO260923000012610060001', 'MATCOO26092300001'));
    }

    public function test_the_separator_that_joined_the_parts_is_dropped()
    {
        $this->assertSame(['COO00001', '25-09-12-001'], ProductUnits::splitCode('COO00001-25-09-12-001', 'COO00001'));
        $this->assertSame(['COO00001', '250912_0001'], ProductUnits::splitCode('COO00001_250912_0001', 'COO00001'));
    }

    public function test_a_code_that_does_not_start_with_its_item_is_not_split()
    {
        $this->assertNull(ProductUnits::splitCode('2509COO000010001', 'COO00001'));
        $this->assertNull(ProductUnits::splitCode('COO00001', 'COO00001'), 'nothing after the item');
        $this->assertNull(ProductUnits::splitCode('COO000012509120001', ''), 'no item to split on');
    }

    public function test_the_item_is_matched_without_regard_to_case_but_printed_as_the_code_has_it()
    {
        $this->assertSame(['coo00001', '2509120001'], ProductUnits::splitCode('coo000012509120001', 'COO00001'));
    }

    public function test_a_typed_unit_code_names_the_item_it_was_printed_for()
    {
        $code = $this->print($this->bed, '2025-09-12', 1)['codes'][0];

        $this->assertSame('COO00001', $this->units->itemBarcodeFor($code, $this->warehouse));
        $this->assertSame('COO00001', $this->units->itemBarcodeFor('  ' . strtolower($code) . ' ', $this->warehouse));
    }

    public function test_text_that_is_not_a_printed_unit_code_names_no_item()
    {
        $this->print($this->bed, '2025-09-12', 1);

        $this->assertSame('', $this->units->itemBarcodeFor('COO00001', $this->warehouse), 'a product barcode is not a unit code');
        $this->assertSame('', $this->units->itemBarcodeFor('COO000012509129999', $this->warehouse));
        $this->assertSame('', $this->units->itemBarcodeFor('   ', $this->warehouse));
        $this->assertSame('', $this->units->itemBarcodeFor("x' OR '1'='1", $this->warehouse));
    }
}
