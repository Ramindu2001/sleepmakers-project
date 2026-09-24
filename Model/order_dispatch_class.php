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
                }
            }//each line

            if(empty($take))
            {
                throw new CustomerOrderRefused(422, 'Nothing on this order is ready to send.');
            }

            $no = $this->nextDispatchNo($shop_id);
            $this->connect()->prepare("INSERT INTO orderdispatches (DispatchNo, customerorders_COID, shop_SHID,
                DispatchStat, DeliverTo, CreatedBy, CreatedAt) VALUES (?, ?, ?, ?, ?, ?, ?);")
                ->execute([$no, (int)$order_id, (int)$shop_id, self::OPEN, (int)$order['DeliverTo'],
                    (int)$user_id, date('Y-m-d H:i:s')]);

            return ['dispatch_id' => (int)$this->connect()->lastInsertId(), 'dispatch_no' => $no,
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
        $stmt = $this->connect()->prepare("SELECT l.*, p.Barcode AS SupplierBarcode, p.ItemName AS SupplierItemName,
            COALESCE(scanned.Qty, 0) AS ScannedQty
            FROM customerorderlines l
            LEFT JOIN products p ON p.PDID = l.SupplierProductID
            LEFT JOIN (SELECT customerorderlines_COLID, SUM(Qty) AS Qty FROM orderdispatchlines
                       WHERE orderdispatches_DSID = ? GROUP BY customerorderlines_COLID) scanned
                ON scanned.customerorderlines_COLID = l.COLID
            WHERE l.customerorders_COID = ? AND l.LineSource = 'WAREHOUSE'
              AND (l.LineStat = ? OR COALESCE(scanned.Qty, 0) > 0)
            ORDER BY l.SortOrder, l.COLID;");
        $stmt->execute([(int)$dispatch['DSID'], (int)$dispatch['customerorders_COID'], WarehouseOrder::LINE_READY]);

        $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($lines as &$line)
        {
            $owed = (float)$line['Qty'] - (float)$line['DispatchedQty'];
            $line['Needed'] = max(0, $owed);
            $line['Outstanding'] = max(0, $owed - (float)$line['ScannedQty']);
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
