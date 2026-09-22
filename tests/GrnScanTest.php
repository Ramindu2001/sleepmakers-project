<?php
final class GrnScanTest extends DatabaseTestCase
{
    private GrnScan $scan;
    private int $shop;
    private int $keeper;
    private int $alice;
    private int $grn;
    private int $bed;
    private int $sheet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scan = new GrnScan();
        $company = $this->createCompany();
        $this->shop = $this->createShop($company, ['ShopName' => 'Warehouse']);
        $this->keeper = $this->createRole('Store Keeper');
        $this->grant($this->keeper, 2, ['is_edit']);
        $this->alice = $this->createUser('alice', 'x', $this->keeper);
        $this->assign($this->alice, $this->shop, $this->keeper);
        $this->bed = $this->createProduct($this->shop, 'COO00001', 'Bed', ['ProdPurchasePrice' => 1000, 'ProdSellPrice' => 1500]);
        $this->sheet = $this->createProduct($this->shop, 'LIN00001', 'Bedsheet', ['ProdPurchasePrice' => 200, 'ProdSellPrice' => 350]);
        $this->createProduct($this->shop, 'SRV00001', 'Delivery', ['ItemType' => 'S']);
        $this->createProduct($this->shop, 'OLD00001', 'Old bed', ['ProductStat' => 0]);
        $this->grn = $this->createGrn($this->shop, $this->alice);
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
        return $this->pdo->query('SELECT products_PDID, InitQty, CurrentQty, UnitPurchasePrice, UnitSellPrice, UnitLabelPrice,
            TotalPurchasePrice, TotalSellPrice, MnfDate, ExpDate, GRNStat, VariationID, Rack_RKID FROM grndetails ORDER BY GDID')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function test_preview_counts_each_product_and_prefills_its_prices()
    {
        $p = $this->scan->preview($this->grn, $this->shop, $this->alice, "COO00001\nCOO00001\nLIN00001\n");
        $lines = $this->lines($p);

        $this->assertSame(3, $p['scans']);
        $this->assertSame(2, $lines['COO00001']['qty']);
        $this->assertSame('ok', $lines['COO00001']['status']);
        $this->assertSame('1000.00', $lines['COO00001']['purchase_price']);
        $this->assertSame('1500.00', $lines['COO00001']['selling_price']);
        $this->assertTrue($lines['COO00001']['editable']);
        $this->assertSame(1, $lines['LIN00001']['qty']);
        $this->assertSame(['COO00001' => 2, 'LIN00001' => 1], $p['applied']);
        $this->assertSame(0, $p['blocking']);
        $this->assertTrue($p['can_apply']);
        $this->assertNull($p['duplicate']);
    }

    public function test_prices_come_from_the_latest_batch_when_there_is_one()
    {
        $this->addStock($this->bed, $this->shop, 3, 'B1', 900, 1400);
        $lines = $this->lines($this->scan->preview($this->grn, $this->shop, $this->alice, 'COO00001'));
        $this->assertSame('900.00', $lines['COO00001']['purchase_price']);
        $this->assertSame('1400.00', $lines['COO00001']['selling_price']);
    }

    public function test_problem_codes_block_until_they_are_left_out()
    {
        $raw = "SRV00001\nOLD00001\nNOPE\nCOO00001";
        $lines = $this->lines($p = $this->scan->preview($this->grn, $this->shop, $this->alice, $raw));

        $this->assertSame('Service item - no stock', $lines['SRV00001']['message']);
        $this->assertSame('Inactive product', $lines['OLD00001']['message']);
        $this->assertSame('Not a product in this shop', $lines['NOPE']['message']);
        $this->assertSame(3, $p['blocking']);
        $this->assertFalse($p['can_apply']);

        $p = $this->scan->preview($this->grn, $this->shop, $this->alice, $raw, ['leave_out' => ['SRV00001', 'OLD00001', 'NOPE']]);
        $this->assertSame(0, $p['blocking']);
        $this->assertTrue($p['can_apply']);
        $this->assertTrue($this->lines($p)['NOPE']['left_out']);
    }

    public function test_run_together_codes_are_split()
    {
        $lines = $this->lines($this->scan->preview($this->grn, $this->shop, $this->alice, 'COO00001COO00001LIN00001'));
        $this->assertSame(2, $lines['COO00001']['qty']);
        $this->assertSame(1, $lines['LIN00001']['qty']);
    }

    public function test_apply_adds_new_lines_like_manual_entry()
    {
        $result = $this->scan->apply($this->grn, $this->shop, $this->alice, "COO00001\nCOO00001", []);

        $this->assertSame(['doc_id' => $this->grn, 'lines' => 1, 'qty' => 2], $result['result']);
        $this->assertSame([[
            'products_PDID' => $this->bed, 'InitQty' => '2.000', 'CurrentQty' => '2.000', 'UnitPurchasePrice' => '1000.00',
            'UnitSellPrice' => '1500.00', 'UnitLabelPrice' => '0.00', 'TotalPurchasePrice' => '2000.00', 'TotalSellPrice' => '3000.00',
            'MnfDate' => date('Y-m-d'), 'ExpDate' => date('Y-m-d'), 'GRNStat' => 0, 'VariationID' => 1, 'Rack_RKID' => 1,
        ]], $this->details());
        $this->assertSame(1, (int)$this->pdo->query('SELECT COUNT(*) FROM scanbatches')->fetchColumn());
    }

    public function test_apply_adds_to_the_existing_line_and_keeps_its_prices()
    {
        $this->insert('grndetails', ['InitQty' => 5, 'CurrentQty' => 5, 'UnitPurchasePrice' => 800, 'UnitLabelPrice' => 0,
            'UnitSellPrice' => 1200, 'TotalPurchasePrice' => 4000, 'TotalSellPrice' => 6000, 'MnfDate' => date('Y-m-d'),
            'ExpDate' => date('Y-m-d'), 'GRNStat' => 0, 'VariationID' => 1, 'products_PDID' => $this->bed,
            'GRNHeader_GHID' => $this->grn, 'Rack_RKID' => 1]);

        $line = $this->lines($this->scan->preview($this->grn, $this->shop, $this->alice, "COO00001\nCOO00001\nCOO00001"))['COO00001'];
        $this->assertSame('Adds to the existing line: 5 → 8', $line['message']);
        $this->assertTrue($line['prices_locked']);
        $this->assertSame('800.00', $line['purchase_price']);

        $this->scan->apply($this->grn, $this->shop, $this->alice, "COO00001\nCOO00001\nCOO00001", ['prices' => [$this->bed => ['purchase' => '1', 'selling' => '1']]]);
        $rows = $this->details();
        $this->assertCount(1, $rows);
        $this->assertSame(['8.000', '8.000', '800.00', '6400.00', '9600.00'],
            [$rows[0]['InitQty'], $rows[0]['CurrentQty'], $rows[0]['UnitPurchasePrice'], $rows[0]['TotalPurchasePrice'], $rows[0]['TotalSellPrice']]);
    }

    public function test_edited_prices_are_used_and_invalid_ones_block()
    {
        $bad = $this->lines($this->scan->preview($this->grn, $this->shop, $this->alice, 'COO00001', ['prices' => [$this->bed => ['purchase' => '950', 'selling' => '-1']]]))['COO00001'];
        $this->assertSame('error', $bad['status']);
        $this->assertSame('Enter a valid selling price', $bad['message']);
        $this->assertTrue($bad['editable']);

        $this->scan->apply($this->grn, $this->shop, $this->alice, 'COO00001', ['prices' => [$this->bed => ['purchase' => '950', 'selling' => '1450.5']]]);
        $this->assertSame(['950.00', '1450.50'], [$this->details()[0]['UnitPurchasePrice'], $this->details()[0]['UnitSellPrice']]);
    }

    public function test_apply_refuses_while_a_line_blocks_and_writes_nothing()
    {
        try {
            $this->scan->apply($this->grn, $this->shop, $this->alice, "COO00001\nNOPE", []);
            $this->fail('expected a refusal');
        } catch (ScanRefused $e) {
            $this->assertSame(422, $e->status);
            $this->assertSame(1, $e->preview['blocking']);
        }
        $this->assertSame([], $this->details());
    }

    public function test_the_same_batch_twice_needs_a_confirmation()
    {
        $this->scan->apply($this->grn, $this->shop, $this->alice, "COO00001\nCOO00001", []);
        try {
            $this->scan->apply($this->grn, $this->shop, $this->alice, "COO00001\nCOO00001", []);
            $this->fail('expected a refusal');
        } catch (ScanRefused $e) {
            $this->assertSame(409, $e->status);
            $this->assertSame('duplicate', $e->confirm);
            $this->assertStringContainsString('already added by alice', $e->getMessage());
        }
        $this->scan->apply($this->grn, $this->shop, $this->alice, "COO00001\nCOO00001", ['confirm_duplicate' => true]);
        $this->assertSame('4.000', $this->details()[0]['InitQty']);
    }

    public function test_closed_grns_other_shops_grns_and_users_without_rights_are_refused()
    {
        $cases = [
            [$this->createGrn($this->shop, $this->alice, 2), $this->shop, $this->alice, 409],
            [$this->createGrn($this->createShop($this->createCompany()), $this->alice), $this->shop, $this->alice, 404],
        ];
        $viewer = $this->createRole('Viewer');
        $this->grant($viewer, 2, ['is_view']);
        $bob = $this->createUser('bob', 'x', $viewer);
        $this->assign($bob, $this->shop, $viewer);
        $cases[] = [$this->grn, $this->shop, $bob, 403];

        foreach ($cases as [$grn, $shop, $user, $status]) {
            try {
                $this->scan->preview($grn, $shop, $user, 'COO00001');
                $this->fail('expected ' . $status);
            } catch (ScanRefused $e) {
                $this->assertSame($status, $e->status);
            }
        }
    }

    public function test_a_shop_that_tracks_expiry_labels_and_racks_gets_those_fields()
    {
        $this->pdo->exec("UPDATE shop SET is_expire = 1, is_labelprice = 1, is_racks = 1 WHERE SHID = {$this->shop}");
        $section = $this->insert('sections', ['SectionNo' => 'S1', 'SectionName' => 'Main', 'shop_SHID' => $this->shop]);
        $rack = $this->insert('rack', ['RackNo' => 'R1', 'RackName' => 'Top', 'Sections_SEID' => $section]);

        $p = $this->scan->preview($this->grn, $this->shop, $this->alice, 'COO00001');
        $this->assertSame(['label_price' => true, 'expiry' => true, 'racks' => true, 'racks_list' => [['id' => $rack, 'name' => 'Main - Top']]], $p['options']);
        $this->assertSame($rack, $p['rack_id']);
        $this->assertSame('Enter the Mnf and Exp dates', $this->lines($p)['COO00001']['message']);
        $this->assertSame('1500.00', $this->lines($p)['COO00001']['label_price']);

        $mnf = date('Y-m-d', strtotime('-10 days'));
        $exp = date('Y-m-d', strtotime('+1 year'));
        $this->scan->apply($this->grn, $this->shop, $this->alice, 'COO00001', [
            'dates' => [$this->bed => ['mnf' => $mnf, 'exp' => $exp]],
            'prices' => [$this->bed => ['purchase' => '1000', 'selling' => '1500', 'label' => '1600']],
            'rack_id' => $rack,
        ]);
        $row = $this->details()[0];
        $this->assertSame([$mnf, $exp, '1600.00', $rack], [$row['MnfDate'], $row['ExpDate'], $row['UnitLabelPrice'], $row['Rack_RKID']]);
    }

    public function test_products_with_variations_stay_on_manual_entry_in_shops_that_use_them()
    {
        $this->pdo->exec("UPDATE shop SET is_variation = 1 WHERE SHID = {$this->shop}");
        $this->insert('variations', ['VariationName' => 'King', 'products_PDID' => $this->bed]);
        $lines = $this->lines($this->scan->preview($this->grn, $this->shop, $this->alice, "COO00001\nLIN00001"));
        $this->assertSame('Has variations - use Add Products', $lines['COO00001']['message']);
        $this->assertSame('ok', $lines['LIN00001']['status']);
    }

    public function test_multi_category_companies_prefer_the_shops_own_product()
    {
        $this->pdo->exec('UPDATE company SET is_multicategory = 1');
        $company = (int)$this->pdo->query("SELECT Company_CMID FROM shop WHERE SHID = {$this->shop}")->fetchColumn();
        $other = $this->createShop($company, ['ShopName' => 'Showroom']);
        $this->createProduct($other, 'COO00001', 'Bed (showroom copy)');
        $pillow = $this->createProduct($other, 'PIL00001', 'Pillow');

        $lines = $this->lines($this->scan->preview($this->grn, $this->shop, $this->alice, "COO00001\nPIL00001"));
        $this->assertSame($this->bed, $lines['COO00001']['product_id']);
        $this->assertSame($pillow, $lines['PIL00001']['product_id']);
    }
}
