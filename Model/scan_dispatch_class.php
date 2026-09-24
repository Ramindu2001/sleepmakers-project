<?php
//Scanning items into a dispatch, the third document type beside GRN and transfer
//(docs/superpowers/specs/2026-09-24-pos-warehouse-fulfilment-design.md).
//
//The warehouse scans every item before it goes on the van. The screen answers one question per
//code: is this really something this customer bought, and has it not already gone somewhere
//else? A unit sticker is best - it names one physical item, so it can be refused by name if it
//was already sent. A plain product barcode counts one against its line.
//
//Nothing here moves stock. Completing the dispatch does that (Model/order_dispatch_class.php).
class DispatchScan extends ScanDocument
{
    private $dispatches;

    public function __construct()
    {
        parent::__construct();
        $this->dispatches = new OrderDispatch();
    }//construct

    public function preview($dispatch_id, $shop_id, $user_id, $raw, array $decisions = [])
    {
        $dispatch = $this->openDispatch($dispatch_id, $shop_id, $user_id);
        return $this->build($dispatch, $shop_id, $raw, $decisions);
    }//preview

    //Writes one row per scanned item. All of it, or none.
    public function apply($dispatch_id, $shop_id, $user_id, $raw, array $decisions)
    {
        return $this->transaction(function() use ($dispatch_id, $shop_id, $user_id, $raw, $decisions) {
            $dispatch = $this->openDispatch($dispatch_id, $shop_id, $user_id);
            if((int)$dispatch['InvStat'] !== 1)
            {
                throw new ScanRefused(409, 'Invoice ' . $dispatch['InvoiceNo'] . ' was cancelled. Nothing on this order should be sent.');
            }//the sale was returned overnight

            $preview = $this->build($dispatch, $shop_id, $raw, $decisions);
            $this->assertCanApply($preview, $decisions);

            $insert = $this->connect()->prepare("INSERT INTO orderdispatchlines (orderdispatches_DSID,
                customerorderlines_COLID, products_PDID, Qty, UnitBarcode, productunits_PUID, ScannedAt, ScannedBy)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?);");
            $now = date('Y-m-d H:i:s');
            foreach($preview['lines'] as $line)
            {
                if($line['left_out'] || $line['status'] === 'error' || $line['apply_qty'] <= 0)
                {
                    continue;
                }
                if(!empty($line['unit_codes']))
                {
                    foreach($line['unit_codes'] as $code => $unit_id)
                    {
                        $insert->execute([(int)$dispatch['DSID'], (int)$line['line_id'], (int)$line['product_id'],
                            1, $code, (int)$unit_id, $now, (int)$user_id]);
                    }//one row per sticker, so every unit is traceable to its customer
                    continue;
                }
                $insert->execute([(int)$dispatch['DSID'], (int)$line['line_id'], (int)$line['product_id'],
                    $line['apply_qty'], null, null, $now, (int)$user_id]);
            }//each line that may be written

            $applied = $this->build($dispatch, $shop_id, '', []);
            $applied['message'] = 'Added to ' . $dispatch['DispatchNo'] . '.';
            return $applied;
        });
    }//apply

    // ---- the preview -------------------------------------------------------------------------

    private function build(array $dispatch, $shop_id, $raw, array $decisions)
    {
        $needed = $this->dispatches->linesOf($dispatch);
        $byProduct = [];
        foreach($needed as $line)
        {
            if($line['SupplierProductID'] !== null)
            {
                $byProduct[(int)$line['SupplierProductID']] = $line;
            }
        }//the catalog lines this dispatch owes, by the warehouse's product

        $byCode = $this->productsByBarcode([(int)$shop_id]);
        $parsed = ScanParser::parse($raw, self::knownBarcodes($byCode), $this->units->suffixLength($shop_id));

        $groups = [];   //product id => ['qty' => n, 'units' => [code => PUID], 'repeats' => n]
        $errors = [];
        $this->readUnits($parsed, $shop_id, $dispatch, $groups, $errors);
        $this->readItems($parsed, $shop_id, $byCode, $groups, $errors);

        $given = $this->givenBarcodes($dispatch['customerorders_COID']);
        $lines = [];
        foreach($groups as $product_id => $group)
        {
            $lines[] = $this->line($product_id, $group, $byProduct, $given);
        }//one line per product scanned
        foreach($parsed['unknown'] as $code => $qty)
        {
            $errors[] = $this->errorLine($code, $qty, 'Not a product we know');
        }//never printed, never sold here

        $preview = [
            'doc_id' => (int)$dispatch['DSID'],
            'doc_no' => $dispatch['DispatchNo'],
            'order_no' => $dispatch['OrderNo'],
            'customer' => $dispatch['CustName'],
            'lines' => array_merge($lines, $errors),
            'needed' => $this->outstanding($needed),
            'scans' => $parsed['scans'],
            'truncated' => $parsed['truncated'],
        ];
        return $this->finish($preview, $decisions, null);
    }//build

    //what the dispatch still has to account for, for the screen
    private function outstanding(array $needed)
    {
        $rows = [];
        foreach($needed as $line)
        {
            $rows[] = [
                'line_id' => (int)$line['COLID'],
                'name' => $line['Description'],
                'barcode' => $line['SupplierBarcode'],
                'custom' => (bool)$line['IsCustom'],
                'needed' => self::number($line['Needed']),
                'scanned' => self::number($line['ScannedQty']),
                'outstanding' => self::number($line['Outstanding']),
                'notes' => $line['Notes'],
            ];
        }
        return $rows;
    }//outstanding

    //Every unit sticker that was read: which item it is, and whether it is still free to go.
    private function readUnits(array &$parsed, $shop_id, array $dispatch, array &$groups, array &$errors)
    {
        $codes = array_merge(array_keys($parsed['units']), array_keys($parsed['unknown']));
        if(empty($codes))
        {
            return;
        }
        $resolved = $this->units->resolve($codes, $shop_id);
        $busy = $this->unitsBeingDispatched(array_map(function($unit) { return (int)$unit['PUID']; }, $resolved),
            (int)$dispatch['DSID']);

        foreach($resolved as $code => $unit)
        {
            $repeats = isset($parsed['units'][$code]) ? (int)$parsed['units'][$code] : (int)$parsed['unknown'][$code];
            unset($parsed['units'][$code], $parsed['unknown'][(string)$code]);

            $stat = (int)$unit['UnitStat'];
            if($stat === ProductUnits::VOIDED)
            {
                $errors[] = $this->errorLine($code, 1, 'Voided sticker - it should not be on anything');
                continue;
            }
            if($stat === ProductUnits::DISPATCHED)
            {
                $errors[] = $this->errorLine($code, 1, 'Already dispatched on ' . $this->dispatchNoOf($unit));
                continue;
            }
            if(isset($busy[(int)$unit['PUID']]))
            {
                $errors[] = $this->errorLine($code, 1, 'Being dispatched on ' . $busy[(int)$unit['PUID']]);
                continue;
            }
            if(empty($unit['ShopProductID']))
            {
                $errors[] = $this->errorLine($code, 1, 'Not a product we know');
                continue;
            }

            $product_id = (int)$unit['ShopProductID'];
            if(!isset($groups[$product_id]))
            {
                $groups[$product_id] = ['qty' => 0, 'units' => [], 'repeats' => 0, 'name' => $unit['ItemName'],
                    'barcode' => $unit['ItemBarcode']];
            }
            if(isset($groups[$product_id]['units'][$code]))
            {
                $groups[$product_id]['repeats'] += $repeats;
                continue;
            }//the same sticker read twice is still one item
            $groups[$product_id]['units'][$code] = (int)$unit['PUID'];
            $groups[$product_id]['qty']++;
            $groups[$product_id]['repeats'] += max(0, $repeats - 1);
        }//each sticker we printed
    }//read units

    //Plain product barcodes: each one counts as one item of that product.
    private function readItems(array $parsed, $shop_id, array $byCode, array &$groups, array &$errors)
    {
        foreach($parsed['items'] as $code => $qty)
        {
            $candidates = isset($byCode[strtoupper((string)$code)]) ? $byCode[strtoupper((string)$code)] : [];
            list($product, $reason) = $this->pickProduct($candidates, $shop_id);
            if($product === null)
            {
                $errors[] = $this->errorLine($code, $qty, $reason === 'Not a product in this shop' ? 'Not a product we know' : $reason);
                continue;
            }
            $product_id = (int)$product['PDID'];
            if(!isset($groups[$product_id]))
            {
                $groups[$product_id] = ['qty' => 0, 'units' => [], 'repeats' => 0, 'name' => $product['ItemName'],
                    'barcode' => trim($product['Barcode'])];
            }
            $groups[$product_id]['qty'] += $qty;
        }//each product barcode
    }//read items

    //One preview line: what was scanned, and whether this customer's order can take it.
    private function line($product_id, array $group, array $byProduct, array $given)
    {
        $line = [
            'key' => 'p' . $product_id,
            'barcode' => $group['barcode'],
            'product_id' => (int)$product_id,
            'name' => $group['name'],
            'qty' => self::number($group['qty']),
            'apply_qty' => self::number($group['qty']),
            'status' => 'ok',
            'message' => '',
            'left_out' => false,
            'can_leave_out' => true,
            'editable' => false,
            'line_id' => null,
            'outstanding' => null,
            'unit_codes' => $group['units'],
        ];

        if(!isset($byProduct[(int)$product_id]))
        {
            $line['status'] = 'error';
            $line['apply_qty'] = 0;
            //the difference matters to the person holding the item: it may be this customer's,
            //but they already carried it out of the shop
            $line['message'] = isset($given[strtoupper((string)$group['barcode'])])
                ? 'Given at the shop - not to be sent'
                : 'Not on this order';
            return $line;
        }

        $order_line = $byProduct[(int)$product_id];
        $line['line_id'] = (int)$order_line['COLID'];
        $outstanding = (float)$order_line['Outstanding'];
        $line['outstanding'] = self::number(max(0, $outstanding - $group['qty']));
        if($group['qty'] > $outstanding)
        {
            $line['status'] = 'error';
            $line['apply_qty'] = 0;
            $line['message'] = $outstanding <= 0
                ? 'This dispatch already has all of them'
                : 'Only ' . self::qty($outstanding) . ' more needed';
            return $line;
        }

        if($group['repeats'] > 0)
        {
            $line['message'] = $group['repeats'] === 1 ? '1 code scanned twice' : $group['repeats'] . ' codes scanned twice';
        }//counted once, and said so
        return $line;
    }//line

    //The barcodes of what this customer already carried out of the shop. A shop keeps its own
    //copy of a product, so the two shops' rows share a barcode rather than an id.
    private function givenBarcodes($order_id)
    {
        $stmt = $this->connect()->prepare("SELECT p.Barcode FROM customerorderlines l
            INNER JOIN products p ON p.PDID = l.products_PDID
            WHERE l.customerorders_COID = ? AND l.LineStat = ?;");
        $stmt->execute([(int)$order_id, WarehouseOrder::LINE_GIVEN]);
        $given = [];
        foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $barcode)
        {
            $given[strtoupper(trim((string)$barcode))] = true;
        }
        return $given;
    }//given barcodes

    // ---- looking things up --------------------------------------------------------------------

    //the dispatch a unit already went out on, by name
    private function dispatchNoOf(array $unit)
    {
        $stmt = $this->connect()->prepare("SELECT DispatchNo FROM orderdispatches WHERE DSID = ?;");
        $stmt->execute([(int)$unit['orderdispatches_DSID']]);
        $no = $stmt->fetchColumn();
        return $no === false ? 'another dispatch' : $no;
    }//dispatch no of

    //units sitting in somebody else's open dispatch right now
    private function unitsBeingDispatched(array $unit_ids, $except_dispatch_id)
    {
        $unit_ids = array_values(array_filter(array_map('intval', $unit_ids)));
        if(empty($unit_ids))
        {
            return [];
        }
        $stmt = $this->connect()->prepare("SELECT dl.productunits_PUID, d.DispatchNo
            FROM orderdispatchlines dl INNER JOIN orderdispatches d ON d.DSID = dl.orderdispatches_DSID
            WHERE d.DispatchStat = ? AND d.DSID <> ? AND dl.productunits_PUID IN ("
            . implode(',', array_fill(0, count($unit_ids), '?')) . ");");
        $stmt->execute(array_merge([OrderDispatch::OPEN, (int)$except_dispatch_id], $unit_ids));
        $busy = [];
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row)
        {
            $busy[(int)$row['productunits_PUID']] = $row['DispatchNo'];
        }
        return $busy;
    }//units being dispatched

    //the dispatch, open and ours to scan into
    private function openDispatch($dispatch_id, $shop_id, $user_id)
    {
        $dispatch = $this->dispatches->find($dispatch_id, $shop_id);
        if((int)$dispatch['shop_SHID'] !== (int)$shop_id)
        {
            throw new ScanRefused(404, 'Only the warehouse sending this order can scan for it.');
        }
        if(!(new WarehouseOrder())->can($user_id, $shop_id, WarehouseOrder::PROCESS))
        {
            throw new ScanRefused(403, 'You do not have the right to prepare customer orders in this shop.');
        }
        if((int)$dispatch['DispatchStat'] !== OrderDispatch::OPEN)
        {
            throw new ScanRefused(409, 'Dispatch ' . $dispatch['DispatchNo'] . ' has already been sent.');
        }
        return $dispatch;
    }//open dispatch
}//DispatchScan
