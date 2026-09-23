<?php
//Scanning units into a GRN (docs/superpowers/specs/2026-09-23-unit-barcodes-design.md, §6).
//Every unit of a product carries its own barcode, so many different codes of the same product
//add up to that product's quantity - and no unit may ever be taken in twice.
final class GrnScanUnitsTest extends DatabaseTestCase
{
    private GrnScan $scan;
    private ProductUnits $units;
    private int $shop;
    private int $other;
    private int $alice;
    private int $keeper;
    private int $bed;
    private int $sheet;
    private int $grn;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scan = new GrnScan();
        $this->units = new ProductUnits();
        $company = $this->createCompany();
        $this->shop = $this->createShop($company, ['ShopName' => 'Warehouse']);
        $this->other = $this->createShop($company, ['ShopName' => 'Valentino Italy']);
        //this warehouse prints a unique barcode on every unit
        $this->insert('barcodesettings', ['shop_SHID' => $this->shop, 'UnitMode' => 1]);
        $this->keeper = $this->createRole('Store Keeper');
        $this->grant($this->keeper, 2, ['is_edit'], $this->shop);
        $this->alice = $this->createUser('alice', 'x', $this->keeper);
        $this->assign($this->alice, $this->shop, $this->keeper);
        $this->bed = $this->createProduct($this->shop, 'COO00001', 'Cooler Mattress',
            ['ProdPurchasePrice' => 1000, 'ProdSellPrice' => 1500]);
        $this->sheet = $this->createProduct($this->shop, 'LIN00001', 'Bedsheet',
            ['ProdPurchasePrice' => 200, 'ProdSellPrice' => 350]);
        $this->grn = $this->createGrn($this->shop, $this->alice);
    }

    //print $qty stickers for a product, made on $date; returns the codes
    private function printed($product_id, $date, $qty, $shop_id = null)
    {
        return $this->units->allocate($shop_id ?? $this->shop, $product_id, $date, $qty, $this->alice)['codes'];
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
        return $this->pdo->query('SELECT GDID, products_PDID, InitQty, MnfDate FROM grndetails ORDER BY GDID')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    private function unitRows()
    {
        return $this->pdo->query('SELECT UnitBarcode, UnitStat, GRNHeader_GHID, grndetails_GDID FROM productunits ORDER BY PUID')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    // ---- counting ---------------------------------------------------------------------------

    public function test_many_unit_codes_of_one_product_add_up_to_its_quantity()
    {
        $codes = $this->printed($this->bed, '2025-09-12', 100);

        $preview = $this->scan->preview($this->grn, $this->shop, $this->alice, implode("\n", $codes));
        $line = $this->lines($preview)['unit:COO00001:2025-09-12'];

        $this->assertSame(100, $line['qty']);
        $this->assertSame($this->bed, $line['product_id']);
        $this->assertSame('ok', $line['status']);
        $this->assertSame('COO00001', $line['barcode']);
        $this->assertStringContainsString('100 unit(s) made 2025-09-12', $line['message']);
        $this->assertSame(0, $preview['blocking']);
    }

    public function test_a_unit_scanned_twice_in_one_upload_is_counted_once()
    {
        $codes = $this->printed($this->bed, '2025-09-12', 2);

        $preview = $this->scan->preview($this->grn, $this->shop, $this->alice,
            $codes[0] . "\n" . $codes[1] . "\n" . $codes[0]);
        $line = $this->lines($preview)['unit:COO00001:2025-09-12'];

        $this->assertSame(2, $line['qty']);
        $this->assertStringContainsString('1 scanned twice', $line['message']);
        $this->assertSame('warn', $line['status']);
        $this->assertTrue($preview['can_apply']);
    }

    public function test_a_quantity_typed_after_a_unit_code_never_multiplies_it()
    {
        $code = $this->printed($this->bed, '2025-09-12', 1)[0];

        $line = $this->lines($this->scan->preview($this->grn, $this->shop, $this->alice, $code . ',10'))['unit:COO00001:2025-09-12'];

        $this->assertSame(1, $line['qty']);
    }

    public function test_units_made_on_two_days_become_two_lines_each_with_its_own_date()
    {
        $september = $this->printed($this->bed, '2025-09-12', 2);
        $october = $this->printed($this->bed, '2025-10-01', 3);

        $preview = $this->scan->preview($this->grn, $this->shop, $this->alice, implode("\n", array_merge($september, $october)));
        $lines = $this->lines($preview);

        $this->assertSame([2, '2025-09-12'], [$lines['unit:COO00001:2025-09-12']['qty'], $lines['unit:COO00001:2025-09-12']['mnf_date']]);
        $this->assertSame([3, '2025-10-01'], [$lines['unit:COO00001:2025-10-01']['qty'], $lines['unit:COO00001:2025-10-01']['mnf_date']]);
    }

    public function test_product_codes_and_unit_codes_can_arrive_in_one_upload()
    {
        $codes = $this->printed($this->bed, '2025-09-12', 2);

        $lines = $this->lines($this->scan->preview($this->grn, $this->shop, $this->alice,
            "LIN00001\n" . implode("\n", $codes)));

        $this->assertSame(1, $lines['LIN00001']['qty']);
        $this->assertSame(2, $lines['unit:COO00001:2025-09-12']['qty']);
    }

    // ---- what must be refused -----------------------------------------------------------------

    public function test_a_unit_already_taken_into_another_grn_is_refused_and_names_it()
    {
        $codes = $this->printed($this->bed, '2025-09-12', 2);
        $earlier = $this->createGrn($this->shop, $this->alice);
        $this->pdo->prepare('UPDATE grnheader SET GRNHeaderNo = ? WHERE GHID = ?')->execute(['GRN_000004', $earlier]);
        $this->units->markReceived([$codes[0]], $earlier, 1, $this->alice);

        $preview = $this->scan->preview($this->grn, $this->shop, $this->alice, implode("\n", $codes));
        $lines = $this->lines($preview);

        $this->assertSame('error', $lines[$codes[0]]['status']);
        $this->assertStringContainsString('GRN_000004', $lines[$codes[0]]['message']);
        $this->assertSame(1, $preview['blocking']);
        $this->assertSame(1, $lines['unit:COO00001:2025-09-12']['qty'], 'the other unit is still fine');
    }

    public function test_a_unit_code_we_never_printed_is_refused()
    {
        $preview = $this->scan->preview($this->grn, $this->shop, $this->alice, 'COO0000125099999');
        $line = $this->lines($preview)['COO0000125099999'];

        $this->assertSame('error', $line['status']);
        $this->assertStringContainsString('not a unit', strtolower($line['message']));
        $this->assertSame(1, $preview['blocking']);
    }

    public function test_a_unit_of_a_product_this_shop_does_not_carry_is_refused()
    {
        $theirs = $this->createProduct($this->other, 'VAL00001', 'Showroom only');
        $code = $this->printed($theirs, '2025-09-12', 1, $this->other)[0];

        $preview = $this->scan->preview($this->grn, $this->shop, $this->alice, $code);
        $line = $this->lines($preview)[$code];

        $this->assertSame('error', $line['status']);
        $this->assertSame(1, $preview['blocking']);
    }

    public function test_a_refused_unit_can_be_left_out_like_any_other_line()
    {
        $codes = $this->printed($this->bed, '2025-09-12', 2);
        $this->units->markReceived([$codes[0]], $this->createGrn($this->shop, $this->alice), 1, $this->alice);

        $preview = $this->scan->preview($this->grn, $this->shop, $this->alice, implode("\n", $codes),
            ['leave_out' => [$codes[0]]]);

        $this->assertSame(0, $preview['blocking']);
        $this->assertTrue($preview['can_apply']);
    }

    // ---- applying -------------------------------------------------------------------------------

    public function test_applying_adds_the_quantity_and_marks_every_unit_received()
    {
        $codes = $this->printed($this->bed, '2025-09-12', 3);

        $result = $this->scan->apply($this->grn, $this->shop, $this->alice, implode("\n", $codes), []);

        $details = $this->details();
        $this->assertSame([$this->bed, '3.000', '2025-09-12'],
            [(int) $details[0]['products_PDID'], $details[0]['InitQty'], $details[0]['MnfDate']]);
        foreach ($this->unitRows() as $unit) {
            $this->assertSame([ProductUnits::RECEIVED, $this->grn, (int) $details[0]['GDID']],
                array_map('intval', [$unit['UnitStat'], $unit['GRNHeader_GHID'], $unit['grndetails_GDID']]));
        }
        $this->assertStringContainsString('3 item(s)', $result['message']);
    }

    public function test_two_production_dates_are_applied_as_two_lines()
    {
        $codes = array_merge($this->printed($this->bed, '2025-09-12', 2), $this->printed($this->bed, '2025-10-01', 1));

        $this->scan->apply($this->grn, $this->shop, $this->alice, implode("\n", $codes), []);

        $this->assertSame(['2.000', '1.000'], array_column($this->details(), 'InitQty'));
        $this->assertSame(['2025-09-12', '2025-10-01'], array_column($this->details(), 'MnfDate'));
    }

    public function test_the_same_upload_applied_twice_adds_nothing_the_second_time()
    {
        $codes = $this->printed($this->bed, '2025-09-12', 2);
        $this->scan->apply($this->grn, $this->shop, $this->alice, implode("\n", $codes), []);

        try {
            $this->scan->apply($this->grn, $this->shop, $this->alice, implode("\n", $codes), ['confirm_duplicate' => 1]);
            $this->fail('expected the units to be refused');
        } catch (ScanRefused $e) {
            $this->assertSame(422, $e->status);
        }

        $this->assertSame(['2.000'], array_column($this->details(), 'InitQty'));
    }

    public function test_units_added_to_a_line_that_is_already_on_the_grn_join_it()
    {
        $first = $this->printed($this->bed, '2025-09-12', 1);
        $this->scan->apply($this->grn, $this->shop, $this->alice, implode("\n", $first), []);
        $second = $this->printed($this->bed, '2025-09-12', 2);

        $this->scan->apply($this->grn, $this->shop, $this->alice, implode("\n", $second), []);

        $this->assertSame(['3.000'], array_column($this->details(), 'InitQty'));
        $this->assertSame(1, count($this->details()));
    }
}
