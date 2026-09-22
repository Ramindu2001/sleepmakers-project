<?php
//Scanner upload for a transfer (docs/superpowers/specs/2026-09-22-scanner-upload-design.md,
//§7 and §8). The sending shop scans what it sends: the stock is taken from its batches,
//oldest first, into the lines manual entry (AJAX/Transfer/addTransferDetail.php) would add.
class TransferScan extends ScanDocument
{
    const FEATURE = 4;                              //Transfer Note
    const SEND_RIGHTS = ['is_edit'];
    const RECEIVE_RIGHTS = ['is_edit', 'is_verify'];

    public function sendPreview($transfer_id, $shop_id, $user_id, $raw, array $decisions = [])
    {
        return $this->buildSend($this->header($transfer_id, $shop_id, $user_id, 'send', false), $raw, $decisions);
    }//send preview

    public function sendApply($transfer_id, $shop_id, $user_id, $raw, array $decisions)
    {
        return $this->transaction(function() use ($transfer_id, $shop_id, $user_id, $raw, $decisions) {
            $header = $this->header($transfer_id, $shop_id, $user_id, 'send', true);
            $preview = $this->buildSend($header, $raw, $decisions);
            $this->assertCanApply($preview, $decisions);

            $pdo = $this->connect();
            $lines = 0;
            $qty = 0;
            foreach($preview['lines'] as $line)
            {
                if($line['left_out'] || $line['status'] === 'error' || $line['apply_qty'] <= 0)
                {
                    continue;
                }
                foreach($line['parts'] as $part)
                {
                    if($part['tdid'] !== null)
                    {
                        $stmt = $pdo->prepare("SELECT TransferQty, ReceivedQty, UnitPurchasePrice FROM transferdetails WHERE TDID = ? FOR UPDATE;");
                        $stmt->execute([$part['tdid']]);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        $received = (float)$row['ReceivedQty'] + $part['qty'];
                        $pdo->prepare("UPDATE transferdetails SET TransferQty = ?, ReceivedQty = ?, TransferTotalAmount = ? WHERE TDID = ?;")
                            ->execute([(float)$row['TransferQty'] + $part['qty'], $received, round($received * (float)$row['UnitPurchasePrice'], 2), $part['tdid']]);
                    }//adds to the batch's line
                    else
                    {
                        $pdo->prepare("INSERT INTO transferdetails (TransferQty, ReceivedQty, UnitPurchasePrice, UnitSellingPrice, MnfDate, ExpDate,
                            TransferTotalAmount, InventoryID, products_PDID, VariationID, RackID, TransferStat, TransferHeader_THID, Batch_ID)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, ?, ?);")
                            ->execute([$part['qty'], $part['qty'], $part['purchase'], $part['selling'], $part['mnf'], $part['exp'],
                                round($part['qty'] * (float)$part['purchase'], 2), $part['inventory_id'], $line['product_id'],
                                $part['variation_id'], $header['THID'], $part['batch_id']]);
                    }//new line for the batch
                }//each batch
                $lines++;
                $qty += $line['apply_qty'];
            }//each product

            $this->batches->record(ScanBatches::TRANSFER_OUT, $header['THID'], $shop_id, $user_id, $preview['applied'], $preview['scans']);
            return [
                'message' => 'Added ' . $lines . ' product(s), ' . self::qty($qty) . ' item(s) to ' . $header['TransferNo'] . '.',
                'result' => ['doc_id' => (int)$header['THID'], 'lines' => $lines, 'qty' => $qty],
            ];
        });
    }//send apply

    public function receivePreview($transfer_id, $shop_id, $user_id, $raw, array $decisions = [])
    {
        return $this->buildReceive($this->header($transfer_id, $shop_id, $user_id, 'receive', false), $raw, $decisions);
    }//receive preview

    //sets each line's received quantity from what the receiving shop scanned. Receiving sets
    //rather than adds, so the same upload twice changes nothing (no duplicate guard), but every
    //upload is recorded
    public function receiveApply($transfer_id, $shop_id, $user_id, $raw, array $decisions)
    {
        return $this->transaction(function() use ($transfer_id, $shop_id, $user_id, $raw, $decisions) {
            $header = $this->header($transfer_id, $shop_id, $user_id, 'receive', true);
            $preview = $this->buildReceive($header, $raw, $decisions);
            $this->assertCanApply($preview, $decisions);
            if($preview['short'] > 0 && empty($decisions['confirm_short']))
            {
                throw new ScanRefused(409, self::qty($preview['short']) . ' item(s) short. They stay in the sending shop. Apply the received quantities?',
                    $preview, 'short');
            }//shortage not confirmed

            $pdo = $this->connect();
            foreach($preview['groups'] as $group)
            {
                $left = $group['received'];
                foreach($group['tdids'] as $tdid)
                {
                    $stmt = $pdo->prepare("SELECT TransferQty, UnitPurchasePrice FROM transferdetails WHERE TDID = ? FOR UPDATE;");
                    $stmt->execute([$tdid]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $take = min((float)$row['TransferQty'], $left);
                    $left -= $take;
                    $pdo->prepare("UPDATE transferdetails SET ReceivedQty = ?, TransferTotalAmount = ? WHERE TDID = ?;")
                        ->execute([$take, round($take * (float)$row['UnitPurchasePrice'], 2), $tdid]);
                }//each line of the product, in order
            }//each product on the transfer

            $this->batches->record(ScanBatches::TRANSFER_IN, $header['THID'], $shop_id, $user_id, $preview['applied'], $preview['scans']);
            $received = array_sum(array_column($preview['groups'], 'received'));
            return [
                'message' => 'Received quantities set on ' . $header['TransferNo'] . ': ' . self::qty($received) . ' item(s) received'
                    . ($preview['short'] > 0 ? ', ' . self::qty($preview['short']) . ' short.' : '.'),
                'result' => ['doc_id' => (int)$header['THID'], 'lines' => count($preview['groups']), 'qty' => self::number($received)],
            ];
        });
    }//receive apply

    //the transfer, when this user may scan it from this side now (locked for update when $lock)
    private function header($transfer_id, $shop_id, $user_id, $side, $lock)
    {
        $stmt = $this->connect()->prepare("SELECT THID, TransferNo, TransferStat, TransferFrom, TransferTo FROM transferheader WHERE THID = ?"
            . ($lock ? " FOR UPDATE" : "") . ";");
        $stmt->execute([(int)$transfer_id]);
        $header = $stmt->fetch(PDO::FETCH_ASSOC);
        $column = $side === 'send' ? 'TransferFrom' : 'TransferTo';
        if($header === false || (int)$header[$column] !== (int)$shop_id)
        {
            throw new ScanRefused(404, $side === 'send' ? 'This transfer is not sent from this shop.' : 'This transfer is not coming to this shop.');
        }
        if(!$this->access->hasFeatureRight($user_id, $shop_id, self::FEATURE, $side === 'send' ? self::SEND_RIGHTS : self::RECEIVE_RIGHTS))
        {
            throw new ScanRefused(403, 'You do not have the right to change transfers in this shop.');
        }
        if(!in_array((int)$header['TransferStat'], [0, 1], true))
        {
            throw new ScanRefused(409, 'This transfer is already verified or cancelled.');
        }
        return $header;
    }//header

    private function buildSend(array $header, $raw, array $decisions)
    {
        $shop_id = (int)$header['TransferFrom'];
        $byCode = $this->productsByBarcode([$shop_id]);
        $parsed = ScanParser::parse($raw, self::knownBarcodes($byCode));

        //what this transfer already takes from each batch (inventory row), and its line there
        $stmt = $this->connect()->prepare("SELECT TDID, InventoryID, TransferQty FROM transferdetails WHERE TransferHeader_THID = ? ORDER BY TDID;");
        $stmt->execute([$header['THID']]);
        $taken = [];
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row)
        {
            $id = (int)$row['InventoryID'];
            $taken[$id] = [
                'tdid' => isset($taken[$id]) ? $taken[$id]['tdid'] : (int)$row['TDID'],
                'qty' => (isset($taken[$id]) ? $taken[$id]['qty'] : 0) + (float)$row['TransferQty'],
            ];
        }

        $lines = [];
        foreach($parsed['items'] as $barcode => $qty)
        {
            $barcode = (string)$barcode;
            [$product, $reason] = $this->pickProduct($byCode[strtoupper($barcode)], $shop_id);
            if($product === null)
            {
                $lines[] = $this->errorLine($barcode, $qty, $reason);
            }
            elseif($product['ItemType'] !== 'P')
            {
                $lines[] = $this->errorLine($barcode, $qty, 'Service item - no stock', $product);
            }
            else
            {
                $lines[] = $this->sendLine($product, $barcode, $qty, $shop_id, $taken);
            }
        }//each code
        foreach($parsed['unknown'] as $token => $qty)
        {
            $lines[] = $this->errorLine($token, $qty, 'Not a product in this shop');
        }

        return $this->finish([
            'context' => 'transfer_send',
            'doc_id' => (int)$header['THID'],
            'scans' => $parsed['scans'],
            'truncated' => $parsed['truncated'],
            'lines' => $lines,
            'options' => [],
        ], $decisions, ScanBatches::TRANSFER_OUT);
    }//build send

    private function buildReceive(array $header, $raw, array $decisions)
    {
        $stmt = $this->connect()->prepare("SELECT transferdetails.TDID, transferdetails.products_PDID, transferdetails.TransferQty,
            products.Barcode, products.ItemName FROM transferdetails
            INNER JOIN products ON products.PDID = transferdetails.products_PDID
            WHERE transferdetails.TransferHeader_THID = ? ORDER BY transferdetails.TDID;");
        $stmt->execute([$header['THID']]);

        //the transfer's products by barcode: what was sent, and the lines to fill in order. The
        //receiving shop may not have these products yet - they are created when it is verified
        $groups = [];
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row)
        {
            $code = trim((string)$row['Barcode']);
            $key = $code === '' ? '#' . $row['products_PDID'] : strtoupper($code);
            if(!isset($groups[$key]))
            {
                $groups[$key] = ['barcode' => $code === '' ? null : $code, 'key' => $code === '' ? $key : $code,
                    'product_id' => (int)$row['products_PDID'], 'name' => $row['ItemName'], 'sent' => 0, 'tdids' => []];
            }
            $groups[$key]['sent'] += (float)$row['TransferQty'];
            $groups[$key]['tdids'][] = (int)$row['TDID'];
        }
        $parsed = ScanParser::parse($raw, array_values(array_filter(array_column($groups, 'barcode'), 'is_string')));

        $lines = [];
        $short = 0;
        foreach($groups as &$group)
        {
            $scanned = ($group['barcode'] !== null && isset($parsed['items'][$group['barcode']])) ? $parsed['items'][$group['barcode']] : 0;
            $group['received'] = min($group['sent'], $scanned);
            $short += max(0, $group['sent'] - $scanned);
            $line = [
                'key' => $group['key'], 'barcode' => $group['barcode'], 'product_id' => $group['product_id'], 'name' => $group['name'],
                'qty' => $scanned, 'sent' => self::number($group['sent']), 'received' => self::number($group['received']),
                'apply_qty' => self::number($group['received']), 'status' => 'ok', 'message' => 'Match',
                'left_out' => false, 'can_leave_out' => false, 'editable' => false,
            ];
            if($scanned < $group['sent'])
            {
                $line['status'] = 'warn';
                $line['message'] = 'Short ' . self::qty($group['sent'] - $scanned);
            }
            elseif($scanned > $group['sent'])
            {
                $line['status'] = 'warn';
                $line['message'] = 'Extra ' . self::qty($scanned - $group['sent']) . ' - only ' . self::qty($group['sent']) . ' can be received';
            }
            $lines[] = $line;
        }
        unset($group);
        foreach($parsed['unknown'] as $token => $qty)
        {
            $lines[] = $this->errorLine($token, $qty, 'Not on this transfer');
        }

        $preview = $this->finish([
            'context' => 'transfer_receive',
            'doc_id' => (int)$header['THID'],
            'scans' => $parsed['scans'],
            'truncated' => $parsed['truncated'],
            'lines' => $lines,
            'options' => [],
        ], $decisions, null);
        $preview['short'] = self::number($short);
        $preview['groups'] = array_values(array_map(function($group) {
            return ['received' => $group['received'], 'tdids' => $group['tdids']];
        }, $groups));
        return $preview;
    }//build receive

    //the product's batches with stock, oldest first, less what this transfer already takes
    private function sendLine(array $product, $barcode, $qty, $shop_id, array $taken)
    {
        $stmt = $this->connect()->prepare("SELECT inventory.INID, inventory.CurrentQty, pricehistory.BatchID, pricehistory.PurchasePrice,
            pricehistory.SellingPrice, pricehistory.MnfDate, pricehistory.ExpDate, pricehistory.VariationID
            FROM inventory
            INNER JOIN pricehistory ON pricehistory.PHID = (SELECT MAX(ph.PHID) FROM pricehistory ph WHERE ph.Inventory_INID = inventory.INID)
            WHERE inventory.products_PDID = ? AND inventory.shop_SHID = ? AND inventory.CurrentQty > 0
            ORDER BY inventory.INID ASC;");
        $stmt->execute([$product['PDID'], $shop_id]);

        $free = 0;
        $onTransfer = 0;
        $remaining = $qty;
        $parts = [];
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row)
        {
            $id = (int)$row['INID'];
            $already = isset($taken[$id]) ? $taken[$id]['qty'] : 0;
            $onTransfer += $already;
            $left = (float)$row['CurrentQty'] - $already;
            if($left <= 0)
            {
                continue;
            }//this batch is already all on the transfer
            $free += $left;
            $use = min($left, $remaining);
            if($use > 0)
            {
                $parts[] = [
                    'inventory_id' => $id,
                    'batch_id' => (string)$row['BatchID'],
                    'qty' => self::number($use),
                    'tdid' => isset($taken[$id]) ? $taken[$id]['tdid'] : null,
                    'purchase' => $row['PurchasePrice'],
                    'selling' => $row['SellingPrice'],
                    'mnf' => self::validDate((string)$row['MnfDate']),
                    'exp' => self::validDate((string)$row['ExpDate']),
                    'variation_id' => empty($row['VariationID']) ? 0 : (int)$row['VariationID'],
                ];
                $remaining -= $use;
            }
        }//each batch, oldest first

        $line = [
            'key' => $barcode, 'barcode' => $barcode, 'product_id' => (int)$product['PDID'], 'name' => $product['ItemName'],
            'qty' => $qty, 'apply_qty' => $qty, 'status' => 'ok', 'left_out' => false, 'can_leave_out' => true, 'editable' => false,
            'available' => self::number($free),
            'batches' => array_map(function($part) {
                return ['inventory_id' => $part['inventory_id'], 'batch_id' => $part['batch_id'], 'qty' => $part['qty'], 'merge' => $part['tdid'] !== null];
            }, $parts),
            'parts' => $parts,
        ];
        if($remaining > 0)
        {
            $line['status'] = 'error';
            $line['apply_qty'] = 0;
            $line['message'] = 'Only ' . self::qty($free) . ' in stock (' . self::qty($onTransfer) . ' already on this transfer)';
        }//not enough stock
        else
        {
            $merges = count(array_filter($parts, function($part) { return $part['tdid'] !== null; }));
            $line['message'] = 'From batch ' . implode(', ', array_map(function($part) {
                return $part['batch_id'] . ' × ' . self::qty($part['qty']);
            }, $parts)) . ($merges > 0 ? ' - adds to the lines already on this transfer' : '');
        }
        return $line;
    }//send line
}//TransferScan
