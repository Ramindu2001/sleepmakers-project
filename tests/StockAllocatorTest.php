<?php
final class StockAllocatorTest extends DatabaseTestCase
{
    public function test_takes_the_oldest_batches_first_and_reports_what_is_short()
    {
        $shop = $this->createShop($this->createCompany());
        $bed = $this->createProduct($shop, 'COO00001', 'Bed');
        $b1 = $this->addStock($bed, $shop, 5, 'B1', 900, 1400);
        $b2 = $this->addStock($bed, $shop, 5, 'B2', 950, 1450);
        $this->addStock($bed, $this->createShop($this->createCompany()), 50, 'X1', 1, 1);

        $a = (new StockAllocator())->allocate($bed, $shop, 12, []);
        $this->assertSame([[$b1, 'B1', 5], [$b2, 'B2', 5]], array_map(function ($p) { return [$p['inventory_id'], $p['batch_id'], $p['qty']]; }, $a['parts']));
        $this->assertSame([10, 0, 2], [$a['free'], $a['taken'], $a['short']]);
        $this->assertSame(['900.00', '1400.00', null, null, 0, null], [$a['parts'][0]['purchase'], $a['parts'][0]['selling'],
            $a['parts'][0]['mnf'], $a['parts'][0]['exp'], $a['parts'][0]['variation_id'], $a['parts'][0]['tdid']]);
    }

    public function test_what_is_already_taken_reduces_each_batch()
    {
        $shop = $this->createShop($this->createCompany());
        $bed = $this->createProduct($shop, 'COO00001', 'Bed');
        $b1 = $this->addStock($bed, $shop, 5, 'B1', 900, 1400);
        $b2 = $this->addStock($bed, $shop, 5, 'B2', 950, 1450);

        $a = (new StockAllocator())->allocate($bed, $shop, 3, [$b1 => ['tdid' => null, 'qty' => 5], $b2 => ['tdid' => 7, 'qty' => 1]]);
        $this->assertSame([[$b2, 3, 7]], array_map(function ($p) { return [$p['inventory_id'], $p['qty'], $p['tdid']]; }, $a['parts']));
        $this->assertSame([4, 6, 0], [$a['free'], $a['taken'], $a['short']]);
    }
}
