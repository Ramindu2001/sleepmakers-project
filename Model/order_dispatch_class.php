<?php
//One trip out of the warehouse to a customer
//(docs/superpowers/specs/2026-09-24-pos-warehouse-fulfilment-design.md).
//
//A dispatch is opened for what is ready, items are scanned into it (Model/scan_dispatch_class.php),
//and completing it is the moment the goods actually leave: stock comes off the warehouse's
//batches, the sale is costed against the shop's invoice, and every scanned sticker is marked as
//having gone to that customer. Until then nothing has moved and it can be cancelled freely.
//
//Part of an order may go now and the rest next week, so a line's quantity and its state are
//kept apart: DispatchedQty counts what has gone, LineStat describes the remainder.
class OrderDispatch extends Dbh
{
    const OPEN = 1;
    const SENT = 2;
    const CANCELLED = 3;

    private $orders;
    private $allocator;
    private $units;

    public function __construct()
    {
        $this->orders = new WarehouseOrder();
        $this->allocator = new StockAllocator();
        $this->units = new ProductUnits();
    }//construct

    // ---- opening and closing --------------------------------------------------------------------

    //Opens a dispatch holding every ready remainder, or the caller's subset
    //([['line_id' => .., 'qty' => ..], ...]) with each quantity capped at what is still owed.
    public function open($order_id, $shop_id, $user_id, array $wanted = null)
    {
        return $this->transaction(function() use ($order_id, $shop_id, $user_id, $wanted) {
            $order = $this->orders->lockOrder($order_id, $shop_id);
            $this->orders->requireIncoming($order, $shop_id);
            $this->orders->requireProcessRight($user_id, $shop_id);
            $this->orders->requireStillOpen($order);

            $existing = $this->openOn($order_id);
            if($existing !== null)
            {
                throw new CustomerOrderRefused(409, 'Dispatch ' . $existing['DispatchNo']
                    . ' is already open on this order. Finish it or cancel it first.');
            }//one at a time, so two people cannot pick the same order

            $caps = [];
            foreach(($wanted === null ? [] : $wanted) as $row)
            {
                if(!empty($row['line_id']))
                {
                    $caps[(int)$row['line_id']] = isset($row['qty']) ? (float)$row['qty'] : null;
                }
            }//what the dispatcher asked for

            $take = [];
            $products = [];
            foreach($this->orders->linesOf($order_id) as $line)
            {
                if((int)$line['LineStat'] !== WarehouseOrder::LINE_READY)
                {
                    continue;
                }//only what has been picked and packed
                $remaining = (float)$line['Qty'] - (float)$line['DispatchedQty'];
                if($remaining <= 0)
                {
                    continue;
                }
                if($wanted !== null && !array_key_exists((int)$line['COLID'], $caps))
                {
                    continue;
                }//left off this trip
                $qty = ($wanted !== null && $caps[(int)$line['COLID']] !== null)
                    ? min($remaining, (float)$caps[(int)$line['COLID']]) : $remaining;
                if($qty > 0)
                {
                    $take[(int)$line['COLID']] = $qty;
                    //a custom-made line has no product of its own; it is ticked off by hand
                    $products[(int)$line['COLID']] = $line['SupplierProductID'] === null ? 0 : (int)$line['SupplierProductID'];
                }
            }//each line

            if(empty($take))
            {
                throw new CustomerOrderRefused(422, 'Nothing on this order is ready to send.');
            }

            $no = $this->nextDispatchNo($shop_id);
            $now = date('Y-m-d H:i:s');
            $this->connect()->prepare("INSERT INTO orderdispatches (DispatchNo, customerorders_COID, shop_SHID,
                DispatchStat, DeliverTo, CreatedBy, CreatedAt) VALUES (?, ?, ?, ?, ?, ?, ?);")
                ->execute([$no, (int)$order_id, (int)$shop_id, self::OPEN, (int)$order['DeliverTo'],
                    (int)$user_id, $now]);
            $dispatch_id = (int)$this->connect()->lastInsertId();

            //One plan row per line says what THIS trip is to take. Without it a van that can
            //only hold two of three beds could never be sent.
            $plan = $this->connect()->prepare("INSERT INTO orderdispatchlines (orderdispatches_DSID,
                customerorderlines_COLID, products_PDID, Qty, PlannedQty, ScannedAt, ScannedBy)
                VALUES (?, ?, ?, 0, ?, ?, ?);");
            foreach($take as $line_id => $qty)
            {
                $plan->execute([$dispatch_id, (int)$line_id, (int)$products[$line_id], $qty, $now, (int)$user_id]);
            }//each line this trip carries

            return ['dispatch_id' => $dispatch_id, 'dispatch_no' => $no,
                'message' => 'Dispatch ' . $no . ' opened. Scan every item before sending it.'];
        });
    }//open

    //Nothing has left, so this only throws away the scans.
    public function cancel($dispatch_id, $shop_id, $user_id)
    {
        return $this->transaction(function() use ($dispatch_id, $shop_id, $user_id) {
            $dispatch = $this->lock($dispatch_id, $shop_id);
            $this->orders->requireProcessRight($user_id, $shop_id);
            if((int)$dispatch['DispatchStat'] !== self::OPEN)
            {
                throw new CustomerOrderRefused(409, 'Dispatch ' . $dispatch['DispatchNo'] . ' has already been sent.');
            }

            $this->connect()->prepare("DELETE FROM orderdispatchlines WHERE orderdispatches_DSID = ?;")
                ->execute([(int)$dispatch_id]);
            $this->connect()->prepare("UPDATE orderdispatches SET DispatchStat = ? WHERE DSID = ?;")
                ->execute([self::CANCELLED, (int)$dispatch_id]);
            $this->orders->refreshStatus($dispatch['customerorders_COID']);

            return ['message' => 'Dispatch ' . $dispatch['DispatchNo'] . ' was cancelled. Nothing left the warehouse.'];
        });
    }//cancel

    //The moment the goods leave. Everything below happens in one transaction, or none of it:
    //the stickers are claimed, the warehouse's batches are drawn down oldest first, the sale is
    //costed against the shop's invoice, and the order's lines move on.
    public function complete($dispatch_id, $shop_id, $user_id, array $decisions)
    {
        return $this->transaction(function() use ($dispatch_id, $shop_id, $user_id, $decisions) {
            $dispatch = $this->lock($dispatch_id, $shop_id);
            $this->orders->requireProcessRight($user_id, $shop_id);
            if((int)$dispatch['DispatchStat'] !== self::OPEN)
            {
                throw new CustomerOrderRefused(409, 'Dispatch ' . $dispatch['DispatchNo'] . ' has already been sent.');
            }
            if($dispatch['InvoiceHeader_IHID'] !== null && (int)$dispatch['InvStat'] !== 1)
            {
                throw new CustomerOrderRefused(409, 'Invoice ' . $dispatch['InvoiceNo']
                    . ' was cancelled. Nothing on this order should be sent.');
            }//the sale was returned in the meantime

            $short = [];
            foreach($this->linesOf($dispatch) as $line)
            {
                if((float)$line['Outstanding'] > 0)
                {
                    $short[] = $line['Description'] . ' (' . WarehouseOrder::qtyText($line['Outstanding']) . ' not scanned)';
                }
            }//everything on this trip must have been scanned
            if(!empty($short))
            {
                throw new CustomerOrderRefused(422, 'Scan every item first: ' . implode(', ', $short) . '.');
            }

            $balance = (float)$dispatch['CustBalance'];
            if($balance > 0 && empty($decisions['confirm_balance']))
            {
                throw new CustomerOrderRefused(409, 'The customer still owes Rs. '
                    . number_format($balance, 2) . ' on invoice ' . $dispatch['InvoiceNo'] . '. Send it anyway?');
            }//delivery on balance payment is normal here, but somebody says so out loud

            $scanned = $this->scannedLines($dispatch_id);
            if(empty($scanned))
            {
                throw new CustomerOrderRefused(422, 'Nothing has been scanned into this dispatch.');
            }

            $this->claimUnits($dispatch_id, $user_id);
            $this->takeStock($dispatch, $scanned, $shop_id);
            $this->advanceLines($scanned);

            $this->connect()->prepare("UPDATE orderdispatches SET DispatchStat = ?, SentBy = ?, SentAt = ? WHERE DSID = ?;")
                ->execute([self::SENT, (int)$user_id, date('Y-m-d H:i:s'), (int)$dispatch_id]);
            $this->orders->refreshStatus($dispatch['customerorders_COID']);

            return ['message' => 'Dispatch ' . $dispatch['DispatchNo'] . ' is on its way to ' . $dispatch['CustName'] . '.'];
        });
    }//complete

    //It reached the customer. Only then is the order finished - paying for it never was.
    public function delivered($dispatch_id, $shop_id, $user_id, $note)
    {
        return $this->transaction(function() use ($dispatch_id, $shop_id, $user_id, $note) {
            $dispatch = $this->lock($dispatch_id, $shop_id);
            $this->orders->requireProcessRight($user_id, $shop_id);
            if((int)$dispatch['DispatchStat'] !== self::SENT)
            {
                throw new CustomerOrderRefused(409, 'Dispatch ' . $dispatch['DispatchNo'] . ' has not been sent yet.');
            }

            foreach($this->scannedLines($dispatch_id) as $line_id => $qty)
            {
                $this->connect()->prepare("UPDATE customerorderlines
                    SET DeliveredQty = DeliveredQty + ?,
                        LineStat = CASE WHEN DeliveredQty + ? >= Qty THEN ? ELSE LineStat END
                    WHERE COLID = ?;")
                    ->execute([$qty, $qty, WarehouseOrder::LINE_DELIVERED, (int)$line_id]);
            }//each line this trip carried

            $this->connect()->prepare("UPDATE orderdispatches SET DeliveredBy = ?, DeliveredAt = ?,
                DeliveryNote = ? WHERE DSID = ?;")
                ->execute([(int)$user_id, date('Y-m-d H:i:s'),
                    trim((string)$note) === '' ? null : mb_substr(trim((string)$note), 0, 500), (int)$dispatch_id]);
            $this->orders->refreshStatus($dispatch['customerorders_COID']);

            return ['message' => 'Dispatch ' . $dispatch['DispatchNo'] . ' reached the customer.'];
        });
    }//delivered

    // ---- what completing actually does ------------------------------------------------------------

    //what was scanned into this dispatch: order line id => quantity
    private function scannedLines($dispatch_id)
    {
        //plan rows carry no quantity of their own, so only real scans count here
        $stmt = $this->connect()->prepare("SELECT customerorderlines_COLID, SUM(Qty) AS Qty
            FROM orderdispatchlines WHERE orderdispatches_DSID = ? AND PlannedQty IS NULL
            GROUP BY customerorderlines_COLID HAVING SUM(Qty) > 0;");
        $stmt->execute([(int)$dispatch_id]);
        $lines = [];
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row)
        {
            $lines[(int)$row['customerorderlines_COLID']] = (float)$row['Qty'];
        }
        return $lines;
    }//scanned lines

    //Claims every sticker on this dispatch. A unit already claimed by somebody else - whose
    //screen was built a moment earlier - loses here, whatever its preview said.
    private function claimUnits($dispatch_id, $user_id)
    {
        $stmt = $this->connect()->prepare("SELECT productunits_PUID, UnitBarcode FROM orderdispatchlines
            WHERE orderdispatches_DSID = ? AND productunits_PUID IS NOT NULL;");
        $stmt->execute([(int)$dispatch_id]);
        $units = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if(empty($units))
        {
            return;
        }

        $claim = $this->connect()->prepare("UPDATE productunits SET UnitStat = ?, orderdispatches_DSID = ?,
            DispatchedAt = NOW(), DispatchedBy = ? WHERE PUID = ? AND UnitStat IN (?, ?);");
        foreach($units as $unit)
        {
            $claim->execute([ProductUnits::DISPATCHED, (int)$dispatch_id, (int)$user_id, (int)$unit['productunits_PUID'],
                ProductUnits::PRINTED, ProductUnits::RECEIVED]);
            if($claim->rowCount() !== 1)
            {
                throw new CustomerOrderRefused(409, $unit['UnitBarcode']
                    . ' has just gone out on another dispatch. Check the list again.');
            }
        }//each sticker
    }//claim units

    //Draws the goods off the warehouse's own batches, oldest first, and costs them against the
    //shop's invoice - the sale is the shop's, the stock was the warehouse's.
    private function takeStock(array $dispatch, array $scanned, $shop_id)
    {
        $wanted = [];
        $names = [];
        foreach($this->orders->linesOf($dispatch['customerorders_COID']) as $line)
        {
            if(!isset($scanned[(int)$line['COLID']]) || $line['SupplierProductID'] === null)
            {
                continue;
            }//not on this trip, or custom-made and never in stock
            $product_id = (int)$line['SupplierProductID'];
            $wanted[$product_id] = (isset($wanted[$product_id]) ? $wanted[$product_id] : 0) + $scanned[(int)$line['COLID']];
            $names[$product_id] = $line['Description'];
        }//each product this trip takes

        $taken = $this->stockOnOpenTransfers($shop_id);
        $parts = [];
        foreach($wanted as $product_id => $qty)
        {
            $allocation = $this->allocator->allocate($product_id, $shop_id, $qty, $taken);
            if($allocation['short'] > 0)
            {
                throw new CustomerOrderRefused(409, 'There is not enough ' . $names[$product_id]
                    . ' in stock any more (' . WarehouseOrder::qtyText($allocation['short'])
                    . ' short). Nothing was sent.');
            }//somebody else took it between scanning and sending
            foreach($allocation['parts'] as $part)
            {
                $parts[] = ['product_id' => $product_id] + $part;
                $already = isset($taken[$part['inventory_id']]) ? $taken[$part['inventory_id']]['qty'] : 0;
                $taken[$part['inventory_id']] = ['tdid' => null, 'qty' => $already + $part['qty']];
            }
        }//each product

        $move = $this->connect()->prepare("UPDATE inventory SET CurrentQty = CurrentQty - ?, BillQty = BillQty + ?
            WHERE INID = ? AND CurrentQty >= ?;");
        $cost = $this->connect()->prepare("INSERT INTO inventory_consumption (invoice_headerID, status, inventory_INID,
            price, sold_price, `batch No`, product_PDID, quantity, shop_SHID) VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?);");
        $stamp = $this->connect()->prepare("UPDATE orderdispatchlines SET InventoryID = ?, Batch_ID = ?
            WHERE orderdispatches_DSID = ? AND products_PDID = ? AND InventoryID IS NULL LIMIT 1;");
        foreach($parts as $part)
        {
            $move->execute([$part['qty'], $part['qty'], $part['inventory_id'], $part['qty']]);
            if($move->rowCount() !== 1)
            {
                throw new CustomerOrderRefused(409, 'The stock moved while this was being sent. Nothing was sent.');
            }//another sale emptied the batch a moment ago
            $cost->execute([$dispatch['InvoiceHeader_IHID'], $part['inventory_id'], $part['purchase'],
                $part['selling'], (int)preg_replace('/\D/', '', (string)$part['batch_id']),
                $part['product_id'], $part['qty'], (int)$shop_id]);
            $stamp->execute([$part['inventory_id'], $part['batch_id'], (int)$dispatch['DSID'], $part['product_id']]);
        }//each batch this trip drew on
    }//take stock

    //stock this shop has already promised to other open transfers is not sold twice
    private function stockOnOpenTransfers($shop_id)
    {
        $stmt = $this->connect()->prepare("SELECT td.InventoryID, SUM(td.TransferQty) AS Qty FROM transferdetails td
            INNER JOIN transferheader th ON th.THID = td.TransferHeader_THID
            WHERE th.TransferFrom = ? AND th.TransferStat IN (0, 1) GROUP BY td.InventoryID;");
        $stmt->execute([(int)$shop_id]);
        $taken = [];
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row)
        {
            $taken[(int)$row['InventoryID']] = ['tdid' => null, 'qty' => (float)$row['Qty']];
        }
        return $taken;
    }//stock on open transfers

    //a line that has now sent everything it owed is Dispatched; a part-sent one keeps its state
    //for the remainder
    private function advanceLines(array $scanned)
    {
        $stmt = $this->connect()->prepare("UPDATE customerorderlines
            SET DispatchedQty = DispatchedQty + ?,
                LineStat = CASE WHEN DispatchedQty + ? >= Qty THEN ? ELSE ? END
            WHERE COLID = ?;");
        foreach($scanned as $line_id => $qty)
        {
            $stmt->execute([$qty, $qty, WarehouseOrder::LINE_DISPATCHED, WarehouseOrder::LINE_PENDING, (int)$line_id]);
        }
    }//advance lines

    // ---- reading ---------------------------------------------------------------------------------

    //The dispatch, its order, and one row per line it has to send with what is still needed on it.
    public function get($dispatch_id, $shop_id)
    {
        $dispatch = $this->find($dispatch_id, $shop_id);
        return ['dispatch' => $dispatch, 'lines' => $this->linesOf($dispatch)];
    }//get

    //What this dispatch still has to account for: the order line, what it owes, what has been
    //scanned into this dispatch so far, and what is therefore still needed.
    public function linesOf(array $dispatch)
    {
        //the plan rows written when the dispatch was opened ARE the list of what it must carry
        $stmt = $this->connect()->prepare("SELECT l.*, p.Barcode AS SupplierBarcode, p.ItemName AS SupplierItemName,
            plan.PlannedQty, COALESCE(scanned.Qty, 0) AS ScannedQty
            FROM orderdispatchlines plan
            INNER JOIN customerorderlines l ON l.COLID = plan.customerorderlines_COLID
            LEFT JOIN products p ON p.PDID = l.SupplierProductID
            LEFT JOIN (SELECT customerorderlines_COLID, SUM(Qty) AS Qty FROM orderdispatchlines
                       WHERE orderdispatches_DSID = ? AND PlannedQty IS NULL GROUP BY customerorderlines_COLID) scanned
                ON scanned.customerorderlines_COLID = l.COLID
            WHERE plan.orderdispatches_DSID = ? AND plan.PlannedQty IS NOT NULL
            ORDER BY l.SortOrder, l.COLID;");
        $stmt->execute([(int)$dispatch['DSID'], (int)$dispatch['DSID']]);

        $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($lines as &$line)
        {
            $line['Needed'] = max(0, (float)$line['PlannedQty']);
            $line['Outstanding'] = max(0, $line['Needed'] - (float)$line['ScannedQty']);
            $line['IsCustom'] = $line['SupplierProductID'] === null;
        }
        unset($line);
        return $lines;
    }//lines of

    //the dispatch still being scanned on this order, or null
    public function openOn($order_id)
    {
        $stmt = $this->connect()->prepare("SELECT * FROM orderdispatches
            WHERE customerorders_COID = ? AND DispatchStat = ? ORDER BY DSID DESC LIMIT 1;");
        $stmt->execute([(int)$order_id, self::OPEN]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }//open on

    // ---- plumbing ---------------------------------------------------------------------------------

    public function find($dispatch_id, $shop_id)
    {
        $stmt = $this->connect()->prepare("SELECT d.*, co.OrderNo, co.shop_SHID AS OrderShopID, co.SupplierShopID,
            co.CustName, co.InvoiceHeader_IHID, ih.InvoiceNo, ih.InvStat, ih.CustBalance
            FROM orderdispatches d
            INNER JOIN customerorders co ON co.COID = d.customerorders_COID
            LEFT JOIN invoiceheader ih ON ih.IHID = co.InvoiceHeader_IHID
            WHERE d.DSID = ?;");
        $stmt->execute([(int)$dispatch_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row === false || ((int)$row['shop_SHID'] !== (int)$shop_id && (int)$row['OrderShopID'] !== (int)$shop_id))
        {
            throw new CustomerOrderRefused(404, 'That dispatch is not in this shop.');
        }
        return $row;
    }//find

    protected function lock($dispatch_id, $shop_id)
    {
        $this->connect()->prepare("SELECT DSID FROM orderdispatches WHERE DSID = ? FOR UPDATE;")->execute([(int)$dispatch_id]);
        $dispatch = $this->find($dispatch_id, $shop_id);
        if((int)$dispatch['shop_SHID'] !== (int)$shop_id)
        {
            throw new CustomerOrderRefused(404, 'Only ' . $dispatch['OrderNo'] . "'s supplier can send it.");
        }
        $this->connect()->prepare("SELECT COID FROM customerorders WHERE COID = ? FOR UPDATE;")
            ->execute([(int)$dispatch['customerorders_COID']]);
        return $dispatch;
    }//lock

    protected function nextDispatchNo($shop_id)
    {
        $stmt = $this->connect()->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING(DispatchNo, 4) AS UNSIGNED)), 0) + 1
            FROM orderdispatches WHERE shop_SHID = ?;");
        $stmt->execute([(int)$shop_id]);
        return 'DS_' . str_pad((string)$stmt->fetchColumn(), 6, '0', STR_PAD_LEFT);
    }//next dispatch no

    protected function transaction(callable $work)
    {
        $pdo = $this->connect();
        if($pdo->inTransaction())
        {
            return $work();
        }
        $pdo->beginTransaction();
        try
        {
            $result = $work();
            $pdo->commit();
            return $result;
        }
        catch(Throwable $e)
        {
            if($pdo->inTransaction())
            {
                $pdo->rollBack();
            }
            throw $e;
        }
    }//transaction
}//OrderDispatch
