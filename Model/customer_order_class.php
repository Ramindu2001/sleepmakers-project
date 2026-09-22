<?php
//Customer orders: a showroom asks the shop that supplies it (the warehouse) for what a
//customer bought but the showroom does not have - see
//docs/superpowers/specs/2026-09-22-customer-orders-design.md.
//
//GIVEN lines were handed over from the showroom's own stock and are never sent. WAREHOUSE lines
//are supplied: catalog products by a transfer created from the order, custom-made items marked
//sent by hand. Progress is read from those transfers, so the transfer Verify is unchanged.
//Billing stays in POS.
class CustomerOrders extends Dbh
{
    const REQUESTED = 1;
    const ACCEPTED = 2;
    const REJECTED = 3;
    const CANCELLED = 4;
    const HANDED_OVER = 5;

    const FEATURE_NAME = 'Customer Orders';
    const VIEW = ['is_view', 'is_create', 'is_edit', 'is_verify'];
    const PLACE = ['is_create'];
    const CHANGE = ['is_create', 'is_edit'];
    const PROCESS = ['is_verify'];
    const MAX_LINES = 100;

    const TRANSFER_STATUS = [0 => 'On hold', 1 => 'Pending', 2 => 'Verified', 3 => 'Cancelled'];

    const ORDER_SELECT = "SELECT co.*, s.ShopName AS ShopName, sup.ShopName AS SupplierName,
        cu.UserName AS CreatedByName, du.UserName AS DecidedByName, xu.UserName AS ClosedByName
        FROM customerorders co
        INNER JOIN shop s ON s.SHID = co.shop_SHID
        INNER JOIN shop sup ON sup.SHID = co.SupplierShopID
        LEFT JOIN user cu ON cu.USID = co.user_USID
        LEFT JOIN user du ON du.USID = co.DecidedBy
        LEFT JOIN user xu ON xu.USID = co.ClosedBy";

    private $access;
    private $allocator;
    private $featureId = null;

    public function __construct()
    {
        $this->access = new ShopAccess();
        $this->allocator = new StockAllocator();
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

    //where this shop can order from: the other active shops of its company
    public function supplierShops($shop_id)
    {
        $stmt = $this->connect()->prepare("SELECT s.SHID, s.ShopName FROM shop s INNER JOIN shop me ON me.Company_CMID = s.Company_CMID
            WHERE me.SHID = ? AND s.SHID <> me.SHID AND s.ShopStat = 1 ORDER BY s.SHID;");
        $stmt->execute([(int)$shop_id]);
        return array_map(function($row) {
            return ['SHID' => (int)$row['SHID'], 'ShopName' => $row['ShopName']];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }//supplier shops

    //a shop's active stock products matching the term, with the stock it holds
    public function searchProducts($shop_id, $term, $limit = 30)
    {
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim((string)$term)) . '%';
        $stmt = $this->connect()->prepare("SELECT p.PDID, p.Barcode, p.ItemName,
            COALESCE((SELECT SUM(i.CurrentQty) FROM inventory i WHERE i.products_PDID = p.PDID AND i.shop_SHID = p.shop_SHID AND i.CurrentQty > 0), 0) AS InStock
            FROM products p WHERE p.shop_SHID = ? AND p.ItemType = 'P' AND p.ProductStat = 1
            AND CONCAT(COALESCE(p.Barcode, ''), ' ', p.ItemName) LIKE ? ORDER BY p.ItemName LIMIT " . max(1, min(100, (int)$limit)) . ";");
        $stmt->execute([(int)$shop_id, $like]);
        return array_map(function($row) {
            return ['id' => (int)$row['PDID'], 'barcode' => (string)$row['Barcode'], 'name' => $row['ItemName'], 'in_stock' => self::number($row['InStock'])];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }//search products

    //---- placing and changing ------------------------------------------------------------------

    public function create($shop_id, $user_id, array $data)
    {
        if(!$this->can($user_id, $shop_id, self::PLACE))
        {
            throw new CustomerOrderRefused(403, 'You do not have the right to place customer orders in this shop.');
        }
        $clean = $this->validate($shop_id, $data);
        return $this->transaction(function() use ($shop_id, $user_id, $clean) {
            $pdo = $this->connect();
            for($attempt = 1; ; $attempt++)
            {
                $order_no = $this->nextOrderNo($shop_id);
                try
                {
                    $pdo->prepare("INSERT INTO customerorders (OrderNo, shop_SHID, SupplierShopID, CustName, CustPhone, CustAddress, NeededBy,
                        AdvancePaid, Notes, OrderStat, user_USID, CreatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?);")
                        ->execute([$order_no, (int)$shop_id, $clean['supplier_id'], $clean['cust_name'], $clean['cust_phone'], $clean['cust_address'],
                            $clean['needed_by'], $clean['advance'], $clean['notes'], (int)$user_id, date('Y-m-d H:i:s')]);
                    break;
                }
                catch(PDOException $e)
                {
                    if($attempt >= 3 || !isset($e->errorInfo[1]) || (int)$e->errorInfo[1] !== 1062)
                    {
                        throw $e;
                    }
                }//another order took the number at the same moment: take the next
            }
            $id = (int)$pdo->lastInsertId();
            $this->insertLines($id, $clean['lines']);
            return ['id' => $id, 'order_no' => $order_no];
        });
    }//create

    //while the supplier has not acted on it, the showroom may change everything
    public function update($id, $shop_id, $user_id, array $data)
    {
        $clean = $this->validate($shop_id, $data);
        $this->transaction(function() use ($id, $shop_id, $user_id, $clean) {
            $order = $this->lock($id, $shop_id);
            $this->requireSide($order, $shop_id, 'ours');
            $this->requireRight($user_id, $shop_id, self::CHANGE, 'change customer orders');
            if((int)$order['OrderStat'] !== self::REQUESTED)
            {
                throw new CustomerOrderRefused(409, 'The supplier has already acted on this order; it can no longer be edited.');
            }
            $this->connect()->prepare("UPDATE customerorders SET SupplierShopID = ?, CustName = ?, CustPhone = ?, CustAddress = ?, NeededBy = ?,
                AdvancePaid = ?, Notes = ? WHERE COID = ?;")
                ->execute([$clean['supplier_id'], $clean['cust_name'], $clean['cust_phone'], $clean['cust_address'], $clean['needed_by'],
                    $clean['advance'], $clean['notes'], (int)$id]);
            $this->connect()->prepare("DELETE FROM customerorderlines WHERE customerorders_COID = ?;")->execute([(int)$id]);
            $this->insertLines((int)$id, $clean['lines']);
        });
    }//update

    //---- reading -------------------------------------------------------------------------------

    public function get($id, $shop_id, $user_id)
    {
        $this->requireRight($user_id, $shop_id, self::VIEW, 'see customer orders');
        $order = $this->find($id, $shop_id, false);
        $progress = $this->progress($order);
        $status = self::statusOf($order, $progress);
        $ours = (int)$order['shop_SHID'] === (int)$shop_id;
        $incoming = (int)$order['SupplierShopID'] === (int)$shop_id;
        $open = in_array((int)$order['OrderStat'], [self::REQUESTED, self::ACCEPTED], true);
        $change = $ours && $this->can($user_id, $shop_id, self::CHANGE);
        $process = $incoming && $this->can($user_id, $shop_id, self::PROCESS);

        $stmt = $this->connect()->prepare("SELECT THID, TransferNo, TransferStat, EffectiveDate FROM transferheader WHERE CustomerOrderID = ? ORDER BY THID;");
        $stmt->execute([(int)$id]);
        $transfers = array_map(function($row) {
            return ['THID' => (int)$row['THID'], 'TransferNo' => $row['TransferNo'], 'EffectiveDate' => $row['EffectiveDate'],
                'status' => isset(self::TRANSFER_STATUS[(int)$row['TransferStat']]) ? self::TRANSFER_STATUS[(int)$row['TransferStat']] : 'Unknown'];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));

        return $order + [
            'lines' => $progress['lines'],
            'transfers' => $transfers,
            'status' => $status,
            'side' => $ours ? 'ours' : 'incoming',
            'can_edit' => $change && (int)$order['OrderStat'] === self::REQUESTED,
            'can_cancel' => $change && $open && !$progress['moving'],
            'can_handover' => $change && $status === 'Arrived',
            'can_accept' => $process && (int)$order['OrderStat'] === self::REQUESTED,
            'can_reject' => $process && $open && !$progress['moving'],
            'can_create_transfer' => $process && $open && $progress['catalog_left'],
            'can_mark_custom' => $process && $open && $progress['has_custom'],
        ];
    }//get

    //this shop's orders: the ones it placed ('ours') or the ones sent to it ('incoming')
    public function listFor($shop_id, $user_id, $side)
    {
        $this->requireRight($user_id, $shop_id, self::VIEW, 'see customer orders');
        $column = $side === 'incoming' ? 'co.SupplierShopID' : 'co.shop_SHID';
        $stmt = $this->connect()->prepare(self::ORDER_SELECT . " WHERE " . $column . " = ? ORDER BY co.COID DESC LIMIT 500;");
        $stmt->execute([(int)$shop_id]);
        $rows = [];
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $order)
        {
            $progress = $this->progress($order);
            $summary = [];
            foreach($progress['lines'] as $line)
            {
                if($line['LineSource'] === 'WAREHOUSE')
                {
                    $summary[] = $line['Description'] . ' × ' . self::qtyText($line['qty']);
                }
            }
            $rows[] = $order + ['status' => self::statusOf($order, $progress), 'summary' => implode(', ', $summary)];
        }
        return $rows;
    }//list for

    //orders waiting for this shop's answer (the menu badge)
    public function incomingCount($shop_id)
    {
        $stmt = $this->connect()->prepare("SELECT COUNT(*) FROM customerorders WHERE SupplierShopID = ? AND OrderStat = 1;");
        $stmt->execute([(int)$shop_id]);
        return (int)$stmt->fetchColumn();
    }//incoming count

    //---- the rules -----------------------------------------------------------------------------

    //the lines with what is on a transfer and what arrived, and what that means for the order
    private function progress(array $order)
    {
        $stmt = $this->connect()->prepare("SELECT * FROM customerorderlines WHERE customerorders_COID = ? ORDER BY SortOrder, COLID;");
        $stmt->execute([(int)$order['COID']]);
        $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->connect()->prepare("SELECT td.products_PDID, SUM(td.TransferQty) AS OnTransfer,
            SUM(CASE WHEN th.TransferStat = 2 THEN td.ReceivedQty ELSE 0 END) AS Arrived
            FROM transferheader th INNER JOIN transferdetails td ON td.TransferHeader_THID = th.THID
            WHERE th.CustomerOrderID = ? AND th.TransferStat <> 3 GROUP BY td.products_PDID;");
        $stmt->execute([(int)$order['COID']]);
        $pool = [];
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row)
        {
            $pool[(int)$row['products_PDID']] = ['on' => (float)$row['OnTransfer'], 'arrived' => (float)$row['Arrived']];
        }

        $moving = false;
        $allArrived = true;
        $warehouseLines = 0;
        $catalogLeft = false;
        $hasCustom = false;
        foreach($lines as &$line)
        {
            $line['products_PDID'] = $line['products_PDID'] === null ? null : (int)$line['products_PDID'];
            $line['qty'] = self::number($line['Qty']);
            $line['on_transfer'] = null;
            $line['arrived'] = null;
            if($line['LineSource'] !== 'WAREHOUSE')
            {
                continue;
            }//given from the showroom's stock: nothing to follow
            $warehouseLines++;
            if($line['products_PDID'] === null)
            {
                $hasCustom = true;
                $on = (float)$line['CustomSent'];
                $arrived = $on;
            }//custom-made: sent by hand
            else
            {
                $pdid = $line['products_PDID'];
                $p = isset($pool[$pdid]) ? $pool[$pdid] : ['on' => 0, 'arrived' => 0];
                $on = min((float)$line['Qty'], $p['on']);
                $arrived = min((float)$line['Qty'], $p['arrived']);
                $pool[$pdid] = ['on' => $p['on'] - $on, 'arrived' => $p['arrived'] - $arrived];
                $catalogLeft = $catalogLeft || $on < (float)$line['Qty'];
            }//a catalog product: from the order's transfers, filled in line order
            $line['on_transfer'] = self::number($on);
            $line['arrived'] = self::number($arrived);
            $moving = $moving || $on > 0;
            $allArrived = $allArrived && $arrived >= (float)$line['Qty'];
        }
        unset($line);

        return ['lines' => $lines, 'moving' => $moving, 'arrived' => $warehouseLines > 0 && $allArrived,
            'catalog_left' => $catalogLeft, 'has_custom' => $hasCustom];
    }//progress

    private static function statusOf(array $order, array $progress)
    {
        switch((int)$order['OrderStat'])
        {
            case self::REQUESTED: return 'Requested';
            case self::REJECTED: return 'Rejected';
            case self::CANCELLED: return 'Cancelled';
            case self::HANDED_OVER: return 'Handed over';
        }
        if($progress['arrived'])
        {
            return 'Arrived';
        }
        return $progress['moving'] ? 'In transit' : 'Accepted';
    }//status of

    private function validate($shop_id, array $data)
    {
        $text = function($key) use ($data) {
            return (isset($data[$key]) && is_scalar($data[$key])) ? trim((string)$data[$key]) : '';
        };

        $suppliers = array_column($this->supplierShops($shop_id), 'ShopName', 'SHID');
        $supplier_id = (int)$text('supplier_id');
        if(!isset($suppliers[$supplier_id]))
        {
            throw new CustomerOrderRefused(422, 'Choose the shop to order from.');
        }
        $clean = [
            'supplier_id' => $supplier_id,
            'cust_name' => $text('cust_name'),
            'cust_phone' => $text('cust_phone'),
            'cust_address' => $text('cust_address') === '' ? null : $text('cust_address'),
            'notes' => $text('notes') === '' ? null : $text('notes'),
            'needed_by' => null,
            'advance' => '0.00',
            'lines' => [],
        ];
        if($clean['cust_name'] === '' || $clean['cust_phone'] === '')
        {
            throw new CustomerOrderRefused(422, "Enter the customer's name and phone number.");
        }
        if(strlen($clean['cust_name']) > 120 || strlen($clean['cust_phone']) > 25 || strlen((string)$clean['cust_address']) > 255 || strlen((string)$clean['notes']) > 2000)
        {
            throw new CustomerOrderRefused(422, 'The customer details are too long.');
        }
        if($text('needed_by') !== '')
        {
            $date = DateTime::createFromFormat('!Y-m-d', $text('needed_by'));
            if(!$date || $date->format('Y-m-d') !== $text('needed_by'))
            {
                throw new CustomerOrderRefused(422, 'Enter a valid "needed by" date.');
            }
            $clean['needed_by'] = $text('needed_by');
        }
        if($text('advance') !== '')
        {
            if(!preg_match('/^\d+(\.\d{1,2})?$/', $text('advance')) || (float)$text('advance') > 99999999.99)
            {
                throw new CustomerOrderRefused(422, 'Enter a valid advance amount.');
            }
            $clean['advance'] = number_format((float)$text('advance'), 2, '.', '');
        }

        $lines = (isset($data['lines']) && is_array($data['lines'])) ? array_values($data['lines']) : [];
        if(count($lines) > self::MAX_LINES)
        {
            throw new CustomerOrderRefused(422, 'An order can have at most ' . self::MAX_LINES . ' lines.');
        }
        $warehouseLines = 0;
        foreach($lines as $i => $line)
        {
            $n = 'Line ' . ($i + 1) . ': ';
            $line = is_array($line) ? $line : [];
            $get = function($key) use ($line) {
                return (isset($line[$key]) && is_scalar($line[$key])) ? trim((string)$line[$key]) : '';
            };
            $source = $get('source') === 'GIVEN' ? 'GIVEN' : ($get('source') === 'WAREHOUSE' ? 'WAREHOUSE' : null);
            if($source === null)
            {
                throw new CustomerOrderRefused(422, $n . 'unknown kind of line.');
            }
            $qty = self::quantity($get('qty'), false);
            if($qty === null)
            {
                throw new CustomerOrderRefused(422, $n . 'enter a quantity above 0.');
            }
            $product_id = (int)$get('product_id');
            $description = $get('description');
            if($product_id > 0)
            {
                $owner = $source === 'WAREHOUSE' ? $supplier_id : (int)$shop_id;
                $stmt = $this->connect()->prepare("SELECT ItemName FROM products WHERE PDID = ? AND shop_SHID = ? AND ItemType = 'P' AND ProductStat = 1;");
                $stmt->execute([$product_id, $owner]);
                $name = $stmt->fetchColumn();
                if($name === false)
                {
                    throw new CustomerOrderRefused(422, $n . ($source === 'WAREHOUSE'
                        ? 'that product is not in the catalog of ' . $suppliers[$supplier_id] . '.' : 'that product is not one of this shop\'s products.'));
                }
                $description = $name;
            }//a product: its name is the description
            elseif($description === '')
            {
                throw new CustomerOrderRefused(422, $n . ($source === 'WAREHOUSE' ? 'describe the custom-made item.' : 'describe the item that was given.'));
            }
            if(strlen($description) > 255 || strlen($get('notes')) > 255 || strlen($get('invoice_no')) > 60)
            {
                throw new CustomerOrderRefused(422, $n . 'the text is too long.');
            }
            $warehouseLines += $source === 'WAREHOUSE' ? 1 : 0;
            $clean['lines'][] = [
                'source' => $source,
                'product_id' => $product_id > 0 ? $product_id : null,
                'description' => $description,
                'qty' => $qty,
                'notes' => $get('notes') === '' ? null : $get('notes'),
                'invoice_no' => ($source === 'GIVEN' && $get('invoice_no') !== '') ? $get('invoice_no') : null,
            ];
        }//each line
        if($warehouseLines === 0)
        {
            throw new CustomerOrderRefused(422, 'Add at least one item for ' . $suppliers[$supplier_id] . ' to supply.');
        }
        return $clean;
    }//validate

    private function insertLines($order_id, array $lines)
    {
        $stmt = $this->connect()->prepare("INSERT INTO customerorderlines (customerorders_COID, LineSource, products_PDID, Description, Qty, Notes,
            InvoiceNo, SortOrder) VALUES (?, ?, ?, ?, ?, ?, ?, ?);");
        foreach($lines as $i => $line)
        {
            $stmt->execute([(int)$order_id, $line['source'], $line['product_id'], $line['description'], $line['qty'], $line['notes'], $line['invoice_no'], $i + 1]);
        }
    }//insert lines

    //the order as this shop may see it, or 404. With $lock the order row (only that row - not
    //the joined shop and user rows) is locked for the rest of the transaction
    private function find($id, $shop_id, $lock)
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

    private function lock($id, $shop_id)
    {
        return $this->find($id, $shop_id, true);
    }//lock

    private function requireSide(array $order, $shop_id, $side)
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

    private function requireRight($user_id, $shop_id, array $rights, $what)
    {
        if(!$this->can($user_id, $shop_id, $rights))
        {
            throw new CustomerOrderRefused(403, 'You do not have the right to ' . $what . ' in this shop.');
        }
    }//require right

    private function nextOrderNo($shop_id)
    {
        $stmt = $this->connect()->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING(OrderNo, 4) AS UNSIGNED)), 0) + 1 FROM customerorders WHERE shop_SHID = ?;");
        $stmt->execute([(int)$shop_id]);
        return 'CO_' . str_pad((string)$stmt->fetchColumn(), 6, '0', STR_PAD_LEFT);
    }//next order no

    private function transaction(callable $work)
    {
        $pdo = $this->connect();
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

    //a quantity > 0 (or >= 0 when $zero) with up to 3 decimals, or null
    private static function quantity($value, $zero)
    {
        $value = trim((string)$value);
        if(!preg_match('/^\d+(\.\d{1,3})?$/', $value) || (float)$value > 99999 || (!$zero && (float)$value <= 0))
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
}//CustomerOrders
