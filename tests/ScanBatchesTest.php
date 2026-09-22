<?php
final class ScanBatchesTest extends DatabaseTestCase
{
    public function test_the_fingerprint_ignores_order_and_case_but_not_counts()
    {
        $this->assertSame(ScanBatches::fingerprint(['A1' => 2, 'B2' => 1]), ScanBatches::fingerprint(['b2' => 1, 'a1' => 2]));
        $this->assertNotSame(ScanBatches::fingerprint(['A1' => 2]), ScanBatches::fingerprint(['A1' => 3]));
    }

    public function test_a_recorded_batch_is_found_again_only_on_the_same_document()
    {
        $user = $this->createUser('ramindu', 'x', $this->createRole('Store Keeper'));
        $batches = new ScanBatches();

        $this->assertGreaterThan(0, $batches->record(ScanBatches::GRN, 12, 3, $user, ['A1' => 2], 2));

        $fingerprint = ScanBatches::fingerprint(['A1' => 2]);
        $duplicate = $batches->findDuplicate(ScanBatches::GRN, 12, $fingerprint);
        $this->assertSame('ramindu', $duplicate['UserName']);
        $this->assertNull($batches->findDuplicate(ScanBatches::GRN, 13, $fingerprint));
        $this->assertNull($batches->findDuplicate(ScanBatches::TRANSFER_OUT, 12, $fingerprint));

        $row = $this->pdo->query('SELECT DocType, DocID, shop_SHID, user_USID, ScanCount, LineCount, Summary FROM scanbatches')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(['DocType' => 'GRN', 'DocID' => 12, 'shop_SHID' => 3, 'user_USID' => $user, 'ScanCount' => 2, 'LineCount' => 1, 'Summary' => '{"A1":2}'], $row);
    }
}
