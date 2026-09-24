<?php
//The order a POS sale leaves for the warehouse
//(docs/superpowers/specs/2026-09-24-pos-warehouse-fulfilment-design.md).
//
//A cashier bills the whole order in one invoice: the bedsheet off the shelf, the bed only the
//warehouse has, and the headboard nobody has made yet. What the shop could give is recorded as
//GIVEN so the warehouse never sends it again; the rest becomes its job.
//
//The money is never copied here. Net, paid and balance are read from the invoice every time,
//so a payment taken tomorrow shows up on the warehouse's screen by itself.
class WarehouseOrder extends Dbh
{
    const PENDING = 1;
    const PREPARING = 2;
    const READY = 3;
    const DISPATCHED = 4;
    const COMPLETED = 5;
    const CANCELLED = 6;

    const LINE_GIVEN = 1;       //handed over the counter; never pickable
    const LINE_PENDING = 2;
    const LINE_READY = 3;
    const LINE_DISPATCHED = 4;
    const LINE_DELIVERED = 5;
    const LINE_CANCELLED = 6;

    const DELIVER_CUSTOMER = 1;
    const DELIVER_PICKUP = 2;

    const FEATURE_NAME = 'Customer Orders';
    const VIEW = ['is_view', 'is_create', 'is_edit', 'is_verify'];
    const CHANGE = ['is_create', 'is_edit'];
    const PROCESS = ['is_verify'];
    const MAX_LINES = 100;

    const ORDER_SELECT = "SELECT co.*, s.ShopName AS ShopName, sup.ShopName AS SupplierName,
        ih.InvoiceNo, ih.NetAmount, ih.CustPayment, ih.CustBalance, ih.InvStat
        FROM customerorders co
        INNER JOIN shop s ON s.SHID = co.shop_SHID
        INNER JOIN shop sup ON sup.SHID = co.SupplierShopID
        LEFT JOIN invoiceheader ih ON ih.IHID = co.InvoiceHeader_IHID";

    protected $access;
    private $featureId = null;

    public function __construct()
    {
        $this->access = new ShopAccess();
    }//construct

    //the role right's id, found by name (0 while the module is not installed)
    public function featureId()
    {
        if($this->featureId === null)
        {
            $stmt = $this->connect()->prepare("SELECT SFID FROM sysfeatures WHERE FeatureName = ? ORDER BY SFID LIMIT 1;");
            $stmt->execute([self::FEATURE_NAME]);
            $this->featureId = (int)$stmt->fetchColumn();
        }
        return $this->featureId;
    }//feature id

    //does the role this user holds in this shop grant one of these rights on Customer Orders?
    public function can($user_id, $shop_id, array $rights)
    {
        $feature = $this->featureId();
        return $feature > 0 && $this->access->hasFeatureRight($user_id, $shop_id, $feature, $rights);
    }//can

    // ---- creating ---------------------------------------------------------------------------

    //Everything a sale leaves behind, in one transaction. $sale: invoice_id, supplier_shop_id,
    //customer_id, cust_name, cust_phone, cust_address, deliver_to, delivery_address,
    //delivery_phone, delivery_note, needed_by, notes, lines[]. Each line: source (GIVEN or
    //WAREHOUSE), product_id (the shop's own copy), supplier_product_id (the warehouse's),
    //description, qty, notes, unit_price.
    public function createFromSale($shop_id, $user_id, array $sale)
    {
        return $this->transaction(function() use ($shop_id, $user_id, $sale) {
            $clean = $this->validate($shop_id, $sale);

            $order_no = $this->nextOrderNo($shop_id);
            $this->connect()->prepare("INSERT INTO customerorders (OrderNo, shop_SHID, SupplierShopID,
                InvoiceHeader_IHID, customers_CTID, CustName, CustPhone, CustAddress, DeliverTo,
                DeliveryAddress, DeliveryPhone, DeliveryNote, NeededBy, Notes, OrderStat, user_USID, CreatedAt)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);")
                ->execute([$order_no, (int)$shop_id, $clean['supplier_shop_id'], $clean['invoice_id'],
                    $clean['customer_id'], $clean['cust_name'], $clean['cust_phone'], $clean['cust_address'],
                    $clean['deliver_to'], $clean['delivery_address'], $clean['delivery_phone'],
                    $clean['delivery_note'], $clean['needed_by'], $clean['notes'], self::PENDING,
                    (int)$user_id, date('Y-m-d H:i:s')]);
            $order_id = (int)$this->connect()->lastInsertId();
            $this->insertLines($order_id, $clean['lines']);

            return ['order_id' => $order_id, 'order_no' => $order_no];
        });
    }//create from sale

    private function insertLines($order_id, array $lines)
    {
        $stmt = $this->connect()->prepare("INSERT INTO customerorderlines (customerorders_COID, LineSource,
            LineStat, products_PDID, SupplierProductID, Description, Qty, DispatchedQty, DeliveredQty,
            UnitPrice, LineTotal, Notes, SortOrder)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0, ?, ?, ?, ?);");
        foreach($lines as $i => $line)
        {
            $stmt->execute([(int)$order_id, $line['source'],
                $line['source'] === 'GIVEN' ? self::LINE_GIVEN : self::LINE_PENDING,
                $line['product_id'], $line['supplier_product_id'], $line['description'], $line['qty'],
                $line['unit_price'], round($line['qty'] * $line['unit_price'], 2), $line['notes'], $i + 1]);
        }//each line, in the order the cart had them
    }//insert lines

    //Checks the whole sale before a single row is written. Lines are never merged: the cart's
    //split is the customer's reality - one bed given off the shelf and one to come from the
    //warehouse are two lines, not a quantity of two.
    private function validate($shop_id, array $sale)
    {
        $name = trim((string)(isset($sale['cust_name']) ? $sale['cust_name'] : ''));
        $phone = trim((string)(isset($sale['cust_phone']) ? $sale['cust_phone'] : ''));
        if($name === '' || mb_strlen($name) > 120)
        {
            throw new CustomerOrderRefused(422, 'The customer\'s name is needed for a warehouse order.');
        }
        if($phone === '' || mb_strlen($phone) > 25)
        {
            throw new CustomerOrderRefused(422, 'The customer\'s phone number is needed for a warehouse order.');
        }

        $supplier = $this->supplierShopRow($shop_id, isset($sale['supplier_shop_id']) ? $sale['supplier_shop_id'] : 0);
        $deliver_to = (int)(isset($sale['deliver_to']) ? $sale['deliver_to'] : self::DELIVER_CUSTOMER);
        if(!in_array($deliver_to, [self::DELIVER_CUSTOMER, self::DELIVER_PICKUP], true))
        {
            throw new CustomerOrderRefused(422, 'Say where the order is going.');
        }
        $address = trim((string)(isset($sale['delivery_address']) ? $sale['delivery_address'] : ''));
        if($deliver_to === self::DELIVER_CUSTOMER && $address === '')
        {
            throw new CustomerOrderRefused(422, 'A delivery address is needed when the warehouse delivers.');
        }

        $lines = isset($sale['lines']) && is_array($sale['lines']) ? array_values($sale['lines']) : [];
        if(empty($lines) || count($lines) > self::MAX_LINES)
        {
            throw new CustomerOrderRefused(422, 'This sale has nothing for the warehouse.');
        }

        $clean = [];
        $wanted = 0;
        foreach($lines as $line)
        {
            $clean[] = $this->validateLine($line, $supplier);
            if($line['source'] === 'WAREHOUSE')
            {
                $wanted++;
            }
        }//each line
        if($wanted === 0)
        {
            throw new CustomerOrderRefused(422, 'This sale has nothing for the warehouse.');
        }

        return [
            'supplier_shop_id' => (int)$supplier['SHID'],
            'invoice_id' => empty($sale['invoice_id']) ? null : (int)$sale['invoice_id'],
            'customer_id' => empty($sale['customer_id']) ? null : (int)$sale['customer_id'],
            'cust_name' => $name,
            'cust_phone' => $phone,
            'cust_address' => $this->text($sale, 'cust_address', 255),
            'deliver_to' => $deliver_to,
            'delivery_address' => $address === '' ? null : mb_substr($address, 0, 255),
            'delivery_phone' => $this->text($sale, 'delivery_phone', 25),
            'delivery_note' => $this->text($sale, 'delivery_note', 65535),
            'needed_by' => $this->date(isset($sale['needed_by']) ? $sale['needed_by'] : null),
            'notes' => $this->text($sale, 'notes', 65535),
            'lines' => $clean,
        ];
    }//validate

    private function validateLine(array $line, array $supplier)
    {
        $source = isset($line['source']) ? (string)$line['source'] : '';
        if(!in_array($source, ['GIVEN', 'WAREHOUSE'], true))
        {
            throw new CustomerOrderRefused(422, 'Every line must say whether the shop gave it or the warehouse sends it.');
        }
        $qty = self::quantity(isset($line['qty']) ? $line['qty'] : null);
        if($qty === null)
        {
            throw new CustomerOrderRefused(422, 'A quantity must be a number above zero.');
        }
        $description = trim((string)(isset($line['description']) ? $line['description'] : ''));
        if($description === '')
        {
            throw new CustomerOrderRefused(422, 'Every line needs a description.');
        }
        if($source === 'WAREHOUSE' && !empty($line['supplier_product_id']))
        {
            $this->requireSupplierProduct($line['supplier_product_id'], $supplier, $description);
        }//a catalog line: it must be something that shop really sells

        return [
            'source' => $source,
            'product_id' => empty($line['product_id']) ? null : (int)$line['product_id'],
            'supplier_product_id' => empty($line['supplier_product_id']) ? null : (int)$line['supplier_product_id'],
            'description' => mb_substr($description, 0, 255),
            'qty' => $qty,
            'unit_price' => round((float)(isset($line['unit_price']) ? $line['unit_price'] : 0), 2),
            'notes' => isset($line['notes']) && trim((string)$line['notes']) !== ''
                ? mb_substr(trim((string)$line['notes']), 0, 255) : null,
        ];
    }//validate line

    //A line the warehouse is expected to pick must name a product that shop really keeps: its
    //own, still active, and a stock item rather than a service. A held bill recalled days later
    //comes back through here, so a product switched off in the meantime is caught at checkout
    //instead of becoming a job nobody can fill.
    private function requireSupplierProduct($product_id, array $supplier, $description)
    {
        $stmt = $this->connect()->prepare("SELECT PDID, ItemName, ItemType, ProductStat FROM products
            WHERE PDID = ? AND shop_SHID = ?;");
        $stmt->execute([(int)$product_id, (int)$supplier['SHID']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if($product === false)
        {
            throw new CustomerOrderRefused(422, $description . ' is not a product ' . $supplier['ShopName'] . ' keeps.');
        }
        if((int)$product['ProductStat'] !== 1)
        {
            throw new CustomerOrderRefused(422, $product['ItemName'] . ' is no longer sold at ' . $supplier['ShopName'] . '.');
        }
        if((string)$product['ItemType'] !== 'P')
        {
            throw new CustomerOrderRefused(422, $product['ItemName'] . ' is a service, so the warehouse cannot send it.');
        }
        return $product;
    }//require supplier product

    //the shop asked to supply this order: another active shop of the same company
    private function supplierShopRow($shop_id, $supplier_shop_id)
    {
        $stmt = $this->connect()->prepare("SELECT s.SHID, s.ShopName FROM shop s
            INNER JOIN shop me ON me.Company_CMID = s.Company_CMID
            WHERE me.SHID = ? AND s.SHID = ? AND s.SHID <> me.SHID AND s.ShopStat = 1;");
        $stmt->execute([(int)$shop_id, (int)$supplier_shop_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row === false)
        {
            throw new CustomerOrderRefused(422, 'That shop cannot supply this order.');
        }
        return $row;
    }//supplier shop row

    // ---- the warehouse works the order --------------------------------------------------------

    //the warehouse has picked the order up off the queue
    public function startPreparing($id, $shop_id, $user_id)
    {
        return $this->transaction(function() use ($id, $shop_id, $user_id) {
            $order = $this->lock($id, $shop_id);
            $this->requireSide($order, $shop_id, 'incoming');
            $this->requireRight($user_id, $shop_id, self::PROCESS, 'prepare customer orders');
            $this->requireOpen($order);

            $this->connect()->prepare("UPDATE customerorders SET DecidedBy = ?, DecidedAt = ? WHERE COID = ?;")
                ->execute([(int)$user_id, date('Y-m-d H:i:s'), (int)$id]);
            $this->refreshStatus($id);

            return ['message' => 'Order ' . $order['OrderNo'] . ' is being prepared.'];
        });
    }//start preparing

    //one line is picked and packed, or all of them ($line_id null)
    public function markReady($id, $shop_id, $user_id, $line_id = null)
    {
        return $this->transaction(function() use ($id, $shop_id, $user_id, $line_id) {
            $order = $this->lock($id, $shop_id);
            $this->requireSide($order, $shop_id, 'incoming');
            $this->requireRight($user_id, $shop_id, self::PROCESS, 'prepare customer orders');
            $this->requireOpen($order);

            $ready = 0;
            foreach($this->linesOf($id) as $line)
            {
                if($line_id !== null && (int)$line['COLID'] !== (int)$line_id)
                {
                    continue;
                }//one line only
                if($line_id !== null)
                {
                    $this->requirePickable($line);
                }//say why this one cannot be readied
                elseif(!$this->isPickable($line))
                {
                    continue;
                }//mark all: quietly skip what is not pickable

                $this->connect()->prepare("UPDATE customerorderlines SET LineStat = ? WHERE COLID = ?;")
                    ->execute([self::LINE_READY, (int)$line['COLID']]);
                $ready++;
            }//each line

            if($line_id !== null && $ready === 0)
            {
                throw new CustomerOrderRefused(404, 'That item is not on this order.');
            }
            if($ready === 0)
            {
                throw new CustomerOrderRefused(409, 'There is nothing left to prepare on this order.');
            }
            $this->refreshStatus($id);

            return ['message' => $ready . ' item(s) ready to go.'];
        });
    }//mark ready

    //the warehouse cannot fill this line at all; the shop refunds it through Sales Return
    public function cannotSupply($id, $shop_id, $user_id, $line_id, $reason)
    {
        return $this->transaction(function() use ($id, $shop_id, $user_id, $line_id, $reason) {
            $order = $this->lock($id, $shop_id);
            $this->requireSide($order, $shop_id, 'incoming');
            $this->requireRight($user_id, $shop_id, self::CHANGE, 'change customer orders');
            $this->requireOpen($order);

            $reason = trim((string)$reason);
            if($reason === '')
            {
                throw new CustomerOrderRefused(422, 'Say why this item cannot be supplied.');
            }
            $line = $this->lineOf($id, $line_id);
            $this->requirePickable($line);

            $this->connect()->prepare("UPDATE customerorderlines SET LineStat = ?, CancelReason = ? WHERE COLID = ?;")
                ->execute([self::LINE_CANCELLED, mb_substr($reason, 0, 255), (int)$line['COLID']]);
            $this->refreshStatus($id);

            return ['message' => $line['Description'] . ' was taken off the order.'];
        });
    }//cannot supply

    //the shop calls the whole thing off, while nothing has left the warehouse
    public function cancel($id, $shop_id, $user_id)
    {
        return $this->transaction(function() use ($id, $shop_id, $user_id) {
            $order = $this->lock($id, $shop_id);
            $this->requireSide($order, $shop_id, 'ours');
            $this->requireRight($user_id, $shop_id, self::CHANGE, 'change customer orders');
            $this->requireOpen($order);

            foreach($this->linesOf($id) as $line)
            {
                if((float)$line['DispatchedQty'] > 0)
                {
                    throw new CustomerOrderRefused(409, 'Part of this order has already left the warehouse.');
                }
            }//nothing may be on its way

            $this->connect()->prepare("UPDATE customerorderlines SET LineStat = ?, CancelReason = ?
                WHERE customerorders_COID = ? AND LineSource = 'WAREHOUSE' AND LineStat <> ?;")
                ->execute([self::LINE_CANCELLED, 'The order was cancelled', (int)$id, self::LINE_CANCELLED]);
            $this->connect()->prepare("UPDATE customerorders SET OrderStat = ?, ClosedBy = ?, ClosedAt = ? WHERE COID = ?;")
                ->execute([self::CANCELLED, (int)$user_id, date('Y-m-d H:i:s'), (int)$id]);

            return ['message' => 'Order ' . $order['OrderNo'] . ' was cancelled.'];
        });
    }//cancel

    //What the order reads as, worked out from its lines - never set by hand, and recomputed by
    //every action inside that action's own transaction. Public because OrderDispatch calls it
    //after moving stock.
    public function refreshStatus($id)
    {
        $stmt = $this->connect()->prepare("SELECT OrderStat, ClosedAt, DecidedAt FROM customerorders WHERE COID = ?;");
        $stmt->execute([(int)$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if($order === false)
        {
            return null;
        }
        if((int)$order['OrderStat'] === self::CANCELLED && $order['ClosedAt'] !== null)
        {
            return self::CANCELLED;
        }//called off by hand: it stays called off

        $open = 0;          //warehouse lines with something still to pick
        $ready = 0;         //of those, picked and packed
        $onTheRoad = 0;     //dispatched but not yet delivered
        $cancelled = 0;
        $warehouse = 0;
        $served = 0;        //given over the counter, or delivered
        foreach($this->linesOf($id) as $line)
        {
            if($line['LineSource'] !== 'WAREHOUSE')
            {
                $served++;
                continue;
            }//given at the shop: the customer already has it
            $warehouse++;
            if((int)$line['LineStat'] === self::LINE_CANCELLED)
            {
                $cancelled++;
                continue;
            }
            if((float)$line['DeliveredQty'] > 0)
            {
                $served++;
            }
            if((float)$line['DispatchedQty'] - (float)$line['DeliveredQty'] > 0)
            {
                $onTheRoad++;
            }
            if((float)$line['Qty'] - (float)$line['DispatchedQty'] > 0)
            {
                $open++;
                if((int)$line['LineStat'] === self::LINE_READY)
                {
                    $ready++;
                }
            }//still something to pick
        }//each line

        if($open === 0 && $onTheRoad === 0)
        {
            //everything is settled: served the customer, or nothing was ever supplied
            $status = ($cancelled === $warehouse && $served === 0) ? self::CANCELLED : self::COMPLETED;
        }
        elseif($open === 0)
        {
            $status = self::DISPATCHED;
        }
        elseif($ready === $open)
        {
            $status = self::READY;
        }
        elseif($ready > 0 || $onTheRoad > 0 || $order['DecidedAt'] !== null)
        {
            $status = self::PREPARING;
        }
        else
        {
            $status = self::PENDING;
        }

        $this->connect()->prepare("UPDATE customerorders SET OrderStat = ? WHERE COID = ?;")
            ->execute([$status, (int)$id]);
        return $status;
    }//refresh status

    //a line of this order, or 404
    protected function lineOf($order_id, $line_id)
    {
        $stmt = $this->connect()->prepare("SELECT * FROM customerorderlines WHERE COLID = ? AND customerorders_COID = ?;");
        $stmt->execute([(int)$line_id, (int)$order_id]);
        $line = $stmt->fetch(PDO::FETCH_ASSOC);
        if($line === false)
        {
            throw new CustomerOrderRefused(404, 'That item is not on this order.');
        }
        return $line;
    }//line of

    //can the warehouse still do something with this line?
    protected function isPickable(array $line)
    {
        return $line['LineSource'] === 'WAREHOUSE'
            && (int)$line['LineStat'] !== self::LINE_CANCELLED
            && (float)$line['Qty'] - (float)$line['DispatchedQty'] > 0;
    }//is pickable

    protected function requirePickable(array $line)
    {
        if($line['LineSource'] !== 'WAREHOUSE')
        {
            throw new CustomerOrderRefused(409, $line['Description'] . ' was given to the customer at the shop.');
        }
        if((int)$line['LineStat'] === self::LINE_CANCELLED)
        {
            throw new CustomerOrderRefused(409, $line['Description'] . ' was already taken off this order.');
        }
        if((float)$line['Qty'] - (float)$line['DispatchedQty'] <= 0)
        {
            throw new CustomerOrderRefused(409, $line['Description'] . ' has already left the warehouse.');
        }
    }//require pickable

    protected function requireOpen(array $order)
    {
        if(in_array((int)$order['OrderStat'], [self::COMPLETED, self::CANCELLED], true))
        {
            throw new CustomerOrderRefused(409, 'This order is already closed.');
        }
    }//require open

    // ---- reading ----------------------------------------------------------------------------

    //the order, its lines, its dispatches and what it is worth, as this shop may see it
    public function get($id, $shop_id, $user_id)
    {
        $order = $this->find($id, $shop_id, false);
        $this->requireRight($user_id, $shop_id, self::VIEW, 'see customer orders');

        return [
            'order' => $order,
            'lines' => $this->linesOf($order['COID']),
            'dispatches' => $this->dispatchesOf($order['COID']),
            'money' => self::moneyOf($order),
        ];
    }//get

    public function linesOf($order_id)
    {
        $stmt = $this->connect()->prepare("SELECT * FROM customerorderlines
            WHERE customerorders_COID = ? ORDER BY SortOrder, COLID;");
        $stmt->execute([(int)$order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }//lines of

    private function dispatchesOf($order_id)
    {
        $stmt = $this->connect()->prepare("SELECT d.*, COUNT(dl.DDID) AS ScannedCount,
            COALESCE(SUM(dl.Qty), 0) AS ScannedQty
            FROM orderdispatches d LEFT JOIN orderdispatchlines dl ON dl.orderdispatches_DSID = d.DSID
            WHERE d.customerorders_COID = ? GROUP BY d.DSID ORDER BY d.DSID;");
        $stmt->execute([(int)$order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }//dispatches of

    //never stored: read from the invoice so a later payment shows by itself
    public static function moneyOf(array $order)
    {
        return [
            'net' => (float)(isset($order['NetAmount']) ? $order['NetAmount'] : 0),
            'paid' => (float)(isset($order['CustPayment']) ? $order['CustPayment'] : 0),
            'balance' => (float)(isset($order['CustBalance']) ? $order['CustBalance'] : 0),
        ];
    }//money of

    //'ours' = the shop that took the order; 'incoming' = the shop that must supply it
    public function listFor($shop_id, $user_id, $side, $status = null)
    {
        $this->requireRight($user_id, $shop_id, self::VIEW, 'see customer orders');
        $where = $side === 'incoming' ? 'co.SupplierShopID = ?' : 'co.shop_SHID = ?';
        $params = [(int)$shop_id];
        if($status !== null && $status !== '')
        {
            $where .= ' AND co.OrderStat = ?';
            $params[] = (int)$status;
        }//one status only

        $stmt = $this->connect()->prepare(self::ORDER_SELECT . " WHERE " . $where . " ORDER BY co.COID DESC;");
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($orders as &$order)
        {
            $order['money'] = self::moneyOf($order);
            $order['progress'] = $this->progressOf($order['COID']);
        }
        unset($order);
        return $orders;
    }//list for

    //"2 of 3 pending" for the queue, without loading every line
    private function progressOf($order_id)
    {
        $stmt = $this->connect()->prepare("SELECT COUNT(*) AS Total,
            SUM(CASE WHEN LineStat IN (2, 3) THEN 1 ELSE 0 END) AS Waiting
            FROM customerorderlines WHERE customerorders_COID = ? AND LineSource = 'WAREHOUSE';");
        $stmt->execute([(int)$order_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return ['total' => (int)$row['Total'], 'waiting' => (int)$row['Waiting']];
    }//progress of

    //what the sidebar badge counts: orders this shop has not finished picking
    public function pendingCount($shop_id)
    {
        $stmt = $this->connect()->prepare("SELECT COUNT(*) FROM customerorders
            WHERE SupplierShopID = ? AND OrderStat IN (?, ?);");
        $stmt->execute([(int)$shop_id, self::PENDING, self::PREPARING]);
        return (int)$stmt->fetchColumn();
    }//pending count

    // ---- shared plumbing ---------------------------------------------------------------------

    //the order as this shop may see it, or 404. With $lock only the order row is locked - locking
    //the joined query would lock the shop and invoice rows too.
    protected function find($id, $shop_id, $lock)
    {
        if($lock)
        {
            $this->connect()->prepare("SELECT COID FROM customerorders WHERE COID = ? FOR UPDATE;")->execute([(int)$id]);
        }
        $stmt = $this->connect()->prepare(self::ORDER_SELECT . " WHERE co.COID = ?;");
        $stmt->execute([(int)$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if($order === false || ((int)$order['shop_SHID'] !== (int)$shop_id && (int)$order['SupplierShopID'] !== (int)$shop_id))
        {
            throw new CustomerOrderRefused(404, 'This order is not in this shop.');
        }
        return $order;
    }//find

    protected function lock($id, $shop_id)
    {
        return $this->find($id, $shop_id, true);
    }//lock

    protected function requireSide(array $order, $shop_id, $side)
    {
        if($side === 'ours' && (int)$order['shop_SHID'] !== (int)$shop_id)
        {
            throw new CustomerOrderRefused(404, 'This order was not placed by this shop.');
        }
        if($side === 'incoming' && (int)$order['SupplierShopID'] !== (int)$shop_id)
        {
            throw new CustomerOrderRefused(404, 'This order was not sent to this shop.');
        }
    }//require side

    protected function requireRight($user_id, $shop_id, array $rights, $what)
    {
        if(!$this->can($user_id, $shop_id, $rights))
        {
            throw new CustomerOrderRefused(403, 'You do not have the right to ' . $what . ' in this shop.');
        }
    }//require right

    protected function nextOrderNo($shop_id)
    {
        $stmt = $this->connect()->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING(OrderNo, 4) AS UNSIGNED)), 0) + 1
            FROM customerorders WHERE shop_SHID = ?;");
        $stmt->execute([(int)$shop_id]);
        return 'CO_' . str_pad((string)$stmt->fetchColumn(), 6, '0', STR_PAD_LEFT);
    }//next order no

    protected function transaction(callable $work)
    {
        $pdo = $this->connect();
        if($pdo->inTransaction())
        {
            return $work();
        }//already inside the caller's transaction, e.g. the POS checkout
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

    private function text(array $data, $key, $limit)
    {
        $value = trim((string)(isset($data[$key]) ? $data[$key] : ''));
        return $value === '' ? null : mb_substr($value, 0, $limit);
    }//text

    private function date($value)
    {
        $value = trim((string)$value);
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        return ($date && $date->format('Y-m-d') === $value) ? $value : null;
    }//date

    //a quantity above zero with up to 3 decimals, or null
    public static function quantity($value)
    {
        $value = trim((string)$value);
        if(!preg_match('/^\d+(\.\d{1,3})?$/', $value) || (float)$value <= 0 || (float)$value > 99999)
        {
            return null;
        }
        return (float)$value;
    }//quantity

    public static function number($number)
    {
        $number = round((float)$number, 3);
        return $number == (int)$number ? (int)$number : $number;
    }//number

    public static function qtyText($number)
    {
        return rtrim(rtrim(number_format((float)$number, 3, '.', ''), '0'), '.');
    }//qty text
}//WarehouseOrder
