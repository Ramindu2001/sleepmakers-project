<?php
//End-to-end check of POS to warehouse fulfilment, against a running copy of the application -
//by default the local XAMPP site:
//
//   C:/xampp/php/php.exe tests/e2e/warehouse_fulfilment_e2e.php [base-url]
//
//The showroom sells a bedsheet off its own shelf, a bed only the warehouse has, and a
//custom-made headboard, taking part of the money. The warehouse then prepares the order, scans
//it out and delivers it. Like the other E2E scripts it creates its own e2e records and removes
//them at the end. Run it only against a development database.
require __DIR__ . '/lib.php';

$pdo = (new E2EDb())->pdo();
$fx = new E2EFixtures($pdo);
$stock = new E2EStock($pdo, $fx);
$stock->down();     //anything a crashed run left behind: the documents go before their users
$fx->up();
$stock->up();
$pw = $fx->password;
$W = $fx->shops['W'];
$S = $fx->shops['S'];

//these e2e shops bill without a cash counter, so the sale does not stop to ask for one
$pdo->prepare("UPDATE shop SET is_counter = 0 WHERE SHID IN (?, ?)")->execute([$W, $S]);

//the showroom has its own bedsheet to hand over; the bed stays a warehouse-only item
$pdo->prepare("INSERT INTO products (Barcode, ItemName, ProdPurchasePrice, ProdSellPrice, ProductStat, ItemType,
    user_USID, Subcategories_SCID, shop_SHID, PurchaseUnit, UnitConversion, SellingUnit, prodFlatDiscount)
    VALUES ('E2ESHT01', 'e2e Bedsheet', 200, 350, 1, 'P', ?, 1, ?, 1, 1, 1, 0)")
    ->execute([$fx->users['admin'], $S]);
$sheetHere = (int) $pdo->lastInsertId();
$pdo->prepare("INSERT INTO inventory (CurrentQty, BillQty, ReturnQty, TransferInQty, TransferOutQty, products_PDID,
    shop_SHID, RackID, BatchID) VALUES (10, 0, 0, 0, 0, ?, ?, 1, 'E2ESH1')")->execute([$sheetHere, $S]);
$sheetInv = (int) $pdo->lastInsertId();
$pdo->prepare("INSERT INTO pricehistory (ProductID, VariationID, EffectiveDate, PurchasePrice, SellingPrice, labelPrice,
    BatchID, Inventory_INID) VALUES (?, 0, CURDATE(), 200, 350, 350, 'E2ESH1', ?)")->execute([$sheetHere, $sheetInv]);

$bedThere = $stock->products['bed'];
//the showroom keeps its own pillows, and a lamp the warehouse has never held
$pillowThere = $stock->products['pillow'];
$pillowHere = $stock->products['pillow_shop'];
$lampHere = $stock->products['lamp_shop'];

//the cart fields a gui-pos cash sale posts, with the warehouse columns beside them
function sale(array $lines, array $header = [])
{
    $fields = ['cash' => 1, 'InvoiceNo' => '', 'customerid' => 1, 'salesmanid' => 1,
        'invoice_discount' => 0, 'invoice_discount_type' => 1, 'totalDiscountLine' => 0,
        'deliveryCharge' => 0, 'otherCharges' => 0, 'excessamount' => 0, 'returnamount' => 0,
        'return_id' => 0, 'is_delivery' => 0, 'deliveryPartner' => '', 'sales_source' => 1,
        'detail' => '', 'item_id' => [], 'Item_name' => [], 'productType' => [], 'qty' => [],
        'original_rate' => [], 'rate' => [], 'discountType' => [], 'Original_discounttype' => [],
        'discount' => [], 'original_discount' => [], 'totals' => [], 'original_total' => [],
        'wh_line' => [], 'wh_custom' => [], 'wh_own' => [], 'wh_supplier_product' => [],
        'wh_notes' => []];
    $gross = 0;
    foreach ($lines as $line) {
        $total = $line['qty'] * $line['price'];
        $gross += $total;
        $fields['item_id'][] = $line['product'];
        $fields['Item_name'][] = $line['name'];
        $fields['productType'][] = 1;
        $fields['qty'][] = $line['qty'];
        $fields['original_rate'][] = $line['price'];
        $fields['rate'][] = $line['price'];
        $fields['discountType'][] = 1;
        $fields['Original_discounttype'][] = 1;
        $fields['discount'][] = 0;
        $fields['original_discount'][] = 0;
        $fields['totals'][] = $total;
        $fields['original_total'][] = $total;
        $fields['wh_line'][] = empty($line['warehouse']) ? '' : '1';
        $fields['wh_custom'][] = empty($line['custom']) ? '' : '1';
        $fields['wh_own'][] = empty($line['own']) ? '' : '1';
        $fields['wh_supplier_product'][] = isset($line['supplier']) ? $line['supplier'] : '';
        $fields['wh_notes'][] = isset($line['notes']) ? $line['notes'] : '';
    }//each cart line
    $fields['grossTotal'] = $gross;
    $fields['netamount'] = $gross;
    return $fields + $header;
}//sale

function rows(PDO $pdo, $sql, array $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}//rows

function one(PDO $pdo, $sql, array $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}//one

function scanInto(E2EBrowser $browser, $action, $dispatch, $raw, $token)
{
    return $browser->post('Controller/ScanUploadController.php', ['context' => 'dispatch', 'doc_id' => $dispatch,
        'action' => $action, 'raw' => $raw, 'csrf_token' => $token]);
}//scanInto

function act(E2EBrowser $browser, array $fields, $token)
{
    return $browser->post('Controller/WarehouseOrderController.php', $fields + ['csrf_token' => $token]);
}//act

try {
    $b = new E2EBrowser($base);

    // ---- the counter -------------------------------------------------------------------------
    echo "Selling a bedsheet, a warehouse bed and a custom headboard\n";
    signIn($b, 'e2e_admin', $pw);
    shopLogin($b, $S, 'e2e_admin', $pw);

    //Review Focus 1: a sale that cannot leave a proper order must not save at all
    $invoicesBefore = (int) one($pdo, "SELECT COUNT(*) FROM invoiceheader WHERE shop_SHID = ?", [$S]);
    $b->post('Controller/guiPosController.php?cash=1', sale([
        ['product' => $bedThere, 'name' => 'e2e Bed', 'qty' => 1, 'price' => 1500,
            'warehouse' => 1, 'supplier' => $bedThere],
    ], ['wh_supplier_shop' => $W, 'wh_cust_name' => '', 'wh_cust_phone' => '', 'wh_deliver_to' => 1,
        'wh_address' => '9 Marine Drive']));
    check('a warehouse sale with no customer is refused', strpos($b->body, 'name') !== false
        && (int) one($pdo, "SELECT COUNT(*) FROM invoiceheader WHERE shop_SHID = ?", [$S]) === $invoicesBefore, $b);

    $b->post('Controller/guiPosController.php?cash=1', sale([
        ['product' => $sheetHere, 'name' => 'e2e Bedsheet', 'qty' => 2, 'price' => 350],
        ['product' => $bedThere, 'name' => 'e2e Bed', 'qty' => 1, 'price' => 1500,
            'warehouse' => 1, 'supplier' => $bedThere, 'notes' => 'Extra firm'],
        ['product' => 0, 'name' => 'Headboard, walnut, 6ft', 'qty' => 1, 'price' => 4000,
            'warehouse' => 1, 'custom' => 1, 'notes' => 'Buttoned'],
    ], ['wh_supplier_shop' => $W, 'wh_cust_name' => 'e2e Nimal', 'wh_cust_phone' => '0771234567',
        'wh_cust_address' => '9 Marine Drive', 'wh_deliver_to' => 1, 'wh_address' => '9 Marine Drive',
        'wh_phone' => '0771234567', 'wh_note' => 'Blue gate']));

    $order = rows($pdo, "SELECT * FROM customerorders WHERE CustName = 'e2e Nimal' ORDER BY COID DESC LIMIT 1");
    check('the sale left a warehouse order', count($order) === 1, $b);
    if (count($order) !== 1) {
        throw new RuntimeException('no order to follow');
    }
    $order = $order[0];
    $orderId = (int) $order['COID'];

    $lines = rows($pdo, "SELECT * FROM customerorderlines WHERE customerorders_COID = ? ORDER BY SortOrder", [$orderId]);
    check('it has three lines: one given, two for the warehouse', count($lines) === 3
        && (int) $lines[0]['LineStat'] === 1 && (int) $lines[1]['LineStat'] === 2 && (int) $lines[2]['LineStat'] === 2);
    check('the custom line carries its description and specs',
        $lines[2]['Description'] === 'Headboard, walnut, 6ft' && $lines[2]['Notes'] === 'Buttoned');
    check('the bed line keeps the shop copy and the warehouse product apart',
        (int) $lines[1]['SupplierProductID'] === $bedThere && (int) $lines[1]['products_PDID'] !== $bedThere);
    check('the shop now has its own copy of the bed',
        (int) one($pdo, "SELECT COUNT(*) FROM products WHERE PDID = ? AND shop_SHID = ?",
            [(int) $lines[1]['products_PDID'], $S]) === 1);
    check('the bedsheet came off the shop shelf',
        (float) one($pdo, "SELECT CurrentQty FROM inventory WHERE INID = ?", [$sheetInv]) === 8.0);
    check('the warehouse bed has not moved yet',
        (float) one($pdo, "SELECT SUM(CurrentQty) FROM inventory WHERE products_PDID = ?", [$bedThere]) === 10.0);
    check('the order is Pending', (int) $order['OrderStat'] === 1);

    //the customer paid a deposit and settles the rest on delivery, as furniture usually goes
    $pdo->prepare("UPDATE invoiceheader SET CustPayment = ROUND(NetAmount / 2, 2),
        CustBalance = NetAmount - ROUND(NetAmount / 2, 2) WHERE IHID = ?")
        ->execute([(int) $order['InvoiceHeader_IHID']]);

    $b->get('Public/customer-orders.php');
    check('the shop sees it on its own orders page', $b->has($order['OrderNo']), $b);
    //this POS leaves InvoiceNo empty: the number the customer quotes back is BillNo
    $billNo = (string) one($pdo, "SELECT BillNo FROM invoiceheader WHERE IHID = ?",
        [(int) $order['InvoiceHeader_IHID']]);
    check('and which bill it was paid on (' . $billNo . ')', $billNo !== '' && $b->has($billNo), $b);
    checkClean('the shop orders page', $b);

    // ---- the warehouse ------------------------------------------------------------------------
    echo "\nThe warehouse prepares it\n";
    $b->dropSession();
    signIn($b, 'e2e_admin', $pw);
    shopLogin($b, $W, 'e2e_admin', $pw);

    $b->get('Public/warehouse-orders.php');
    check('the warehouse queue shows it as Pending', $b->has($order['OrderNo']) && $b->has('Pending'), $b);
    check('and the balance is shouted', $b->has('due'), $b);
    checkClean('the warehouse queue', $b);

    $b->get('Public/customer-order.php?id=' . $orderId);
    check('the job sheet marks what the shop already gave', $b->has('Given at shop'), $b);
    check('and shows the custom specs', $b->has('Buttoned'), $b);
    checkClean('the job sheet', $b);
    $token = $b->csrf();

    act($b, ['action' => 'start_preparing', 'id' => $orderId], $token);
    check('it can be started', (int) one($pdo, "SELECT OrderStat FROM customerorders WHERE COID = ?", [$orderId]) === 2);

    act($b, ['action' => 'mark_ready', 'id' => $orderId], $token);
    check('and marked ready', (int) one($pdo, "SELECT OrderStat FROM customerorders WHERE COID = ?", [$orderId]) === 3);

    // ---- scanning it out ----------------------------------------------------------------------
    echo "\nScanning it out\n";
    act($b, ['action' => 'open_dispatch', 'id' => $orderId], $token);
    $dispatch = (int) one($pdo, "SELECT DSID FROM orderdispatches WHERE customerorders_COID = ? ORDER BY DSID DESC LIMIT 1", [$orderId]);
    check('a dispatch opens', $dispatch > 0, $b);

    $b->get('Public/customer-order.php?id=' . $orderId);
    check('the job sheet offers the scanner', $b->has('scan_upload_modal') && $b->has('Scan items'), $b);
    checkClean('the job sheet with an open dispatch', $b);
    $token = $b->csrf();

    scanInto($b, 'check', $dispatch, 'E2ESHT01', $token);
    check('scanning what the customer already took is refused',
        strpos($b->body, 'Given at the shop') !== false, $b);

    scanInto($b, 'check', $dispatch, 'NOT-A-REAL-CODE', $token);
    check('an unknown code is refused', strpos($b->body, 'Not a product we know') !== false, $b);

    scanInto($b, 'check', $dispatch, "E2EBED01\nE2EBED01", $token);
    check('scanning more than the order needs is refused', strpos($b->body, 'Only 1') !== false, $b);

    scanInto($b, 'apply', $dispatch, 'E2EBED01', $token);
    check('the bed goes into the dispatch',
        (float) one($pdo, "SELECT COALESCE(SUM(Qty), 0) FROM orderdispatchlines
            WHERE orderdispatches_DSID = ? AND PlannedQty IS NULL", [$dispatch]) === 1.0, $b);

    //the headboard has no barcode; it is ticked off by hand against its line
    $customLine = (int) one($pdo, "SELECT COLID FROM customerorderlines
        WHERE customerorders_COID = ? AND SupplierProductID IS NULL AND LineSource = 'WAREHOUSE'", [$orderId]);
    $pdo->prepare("INSERT INTO orderdispatchlines (orderdispatches_DSID, customerorderlines_COLID, products_PDID,
        Qty, ScannedAt, ScannedBy) VALUES (?, ?, 0, 1, NOW(), ?)")
        ->execute([$dispatch, $customLine, $fx->users['admin']]);

    $b->get('Public/customer-order.php?id=' . $orderId);
    $token = $b->csrf();
    act($b, ['action' => 'complete_dispatch', 'id' => $dispatch], $token);
    check('sending a part-paid order asks first', $b->has('still owes')
        && (int) one($pdo, "SELECT DispatchStat FROM orderdispatches WHERE DSID = ?", [$dispatch]) === 1, $b);

    act($b, ['action' => 'complete_dispatch', 'id' => $dispatch, 'confirm_balance' => 1], $token);
    check('confirming sends it', (int) one($pdo, "SELECT DispatchStat FROM orderdispatches WHERE DSID = ?", [$dispatch]) === 2, $b);
    check('the warehouse stock went down by one bed',
        (float) one($pdo, "SELECT SUM(CurrentQty) FROM inventory WHERE products_PDID = ?", [$bedThere]) === 9.0);
    check('the sale was costed against the shop invoice',
        (int) one($pdo, "SELECT COUNT(*) FROM inventory_consumption ic
            INNER JOIN customerorders co ON co.InvoiceHeader_IHID = ic.invoice_headerID
            WHERE co.COID = ? AND ic.shop_SHID = ?", [$orderId, $W]) === 1);
    check('the order is Dispatched', (int) one($pdo, "SELECT OrderStat FROM customerorders WHERE COID = ?", [$orderId]) === 4);

    // ---- delivered ----------------------------------------------------------------------------
    echo "\nDelivering it\n";
    $b->get('Public/customer-order.php?id=' . $orderId);
    $token = $b->csrf();
    act($b, ['action' => 'delivered', 'id' => $dispatch, 'note' => 'Left with the customer'], $token);
    check('the order is Completed only once the customer has it',
        (int) one($pdo, "SELECT OrderStat FROM customerorders WHERE COID = ?", [$orderId]) === 5, $b);
    check('every line reads delivered or given at the shop',
        (int) one($pdo, "SELECT COUNT(*) FROM customerorderlines
            WHERE customerorders_COID = ? AND LineStat NOT IN (1, 5)", [$orderId]) === 0);

    $b->get('Public/customer-order.php?id=' . $orderId);
    checkClean('the finished job sheet', $b);

    // ---- the checkbox in front of an item name ------------------------------------------------
    echo "\nTicking an item off the shop's own shelf\n";
    $b->dropSession();
    signIn($b, 'e2e_admin', $pw);
    shopLogin($b, $S, 'e2e_admin', $pw);

    $delivery = ['wh_supplier_shop' => $W, 'wh_cust_name' => 'e2e Kamala', 'wh_cust_phone' => '0777654321',
        'wh_cust_address' => '4 Temple Lane', 'wh_deliver_to' => 1, 'wh_address' => '4 Temple Lane',
        'wh_phone' => '0777654321', 'wh_note' => ''];
    $shelf = function ($product) use ($pdo) {
        return (float) one($pdo, "SELECT COALESCE(SUM(CurrentQty), 0) FROM inventory WHERE products_PDID = ?", [$product]);
    };

    $invoicesBefore = (int) one($pdo, "SELECT COUNT(*) FROM invoiceheader WHERE shop_SHID = ?", [$S]);
    $b->post('Controller/guiPosController.php?cash=1', sale([
        ['product' => $lampHere, 'name' => 'e2e Lamp', 'qty' => 1, 'price' => 200,
            'warehouse' => 1, 'own' => 1],
    ], $delivery));
    check('ticking something the warehouse never had is refused',
        strpos($b->body, 'does not keep that item') !== false
        && (int) one($pdo, "SELECT COUNT(*) FROM invoiceheader WHERE shop_SHID = ?", [$S]) === $invoicesBefore, $b);
    check('and the lamp is still on the shelf', $shelf($lampHere) === 3.0);

    //a cart that billed a pillow but named the bed: the posted product must count for nothing
    $b->post('Controller/guiPosController.php?cash=1', sale([
        ['product' => $pillowHere, 'name' => 'e2e Pillow', 'qty' => 2, 'price' => 750,
            'warehouse' => 1, 'own' => 1, 'supplier' => $bedThere, 'notes' => 'Soft'],
    ], $delivery));

    $ticked = rows($pdo, "SELECT * FROM customerorders WHERE CustName = 'e2e Kamala' ORDER BY COID DESC LIMIT 1");
    check('the ticked line left a warehouse order', count($ticked) === 1, $b);
    if (count($ticked) !== 1) {
        throw new RuntimeException('no ticked order to check');
    }
    $tickedLines = rows($pdo, "SELECT * FROM customerorderlines WHERE customerorders_COID = ?", [(int) $ticked[0]['COID']]);

    check('it is the warehouse pillow that gets picked, not the bed the browser named',
        count($tickedLines) === 1 && (int) $tickedLines[0]['SupplierProductID'] === $pillowThere, $b);
    check('the invoice still bills the shop pillow',
        (int) $tickedLines[0]['products_PDID'] === $pillowHere
        && (int) one($pdo, "SELECT COUNT(*) FROM invoicedetails WHERE InvoiceHeader_IHID = ? AND products_PDID = ?",
            [(int) $ticked[0]['InvoiceHeader_IHID'], $pillowHere]) === 1);
    check('the line is Pending for the warehouse, not Given',
        (int) $tickedLines[0]['LineStat'] === 2 && $tickedLines[0]['LineSource'] === 'WAREHOUSE');
    check('the shop shelf was not touched: the warehouse is sending these', $shelf($pillowHere) === 4.0);
    check('the warehouse pillows have not moved yet either', $shelf($pillowThere) === 6.0);
    check('the order is Pending', (int) $ticked[0]['OrderStat'] === 1);

    $b->get('Public/customer-orders.php');
    check('the shop sees the ticked order on its own orders page', $b->has($ticked[0]['OrderNo']), $b);
    checkClean('the shop orders page after a ticked line', $b);
} finally {
    //the orders and dispatches go before the users and shops they point at
    $pdo->exec("DELETE dl FROM orderdispatchlines dl INNER JOIN orderdispatches d ON d.DSID = dl.orderdispatches_DSID
        INNER JOIN customerorders co ON co.COID = d.customerorders_COID WHERE co.CustName LIKE 'e2e %'");
    $pdo->exec("DELETE d FROM orderdispatches d INNER JOIN customerorders co ON co.COID = d.customerorders_COID
        WHERE co.CustName LIKE 'e2e %'");
    $pdo->exec("DELETE l FROM customerorderlines l INNER JOIN customerorders co ON co.COID = l.customerorders_COID
        WHERE co.CustName LIKE 'e2e %'");
    $pdo->exec("DELETE FROM customerorders WHERE CustName LIKE 'e2e %'");
    $pdo->exec("DELETE ic FROM inventory_consumption ic INNER JOIN invoiceheader ih ON ih.IHID = ic.invoice_headerID
        WHERE ih.InvoiceNo LIKE 'E2E%' OR ih.BillNo LIKE 'E2E%'");
    $pdo->exec("DELETE id FROM invoicedetails id INNER JOIN products p ON p.PDID = id.products_PDID
        WHERE p.Barcode LIKE 'E2E%' OR p.ItemName LIKE 'e2e %' OR p.ItemName = 'Custom-made item'");
    $pdo->exec("DELETE FROM invoiceheader WHERE shop_SHID IN
        (SELECT SHID FROM shop WHERE ShopName LIKE 'e2e %')");
    $pdo->exec("DELETE FROM transactions WHERE InvoiceHeader_IHID NOT IN (SELECT IHID FROM invoiceheader)");
    $stock->down();
    $fx->down();
}

echo "\n" . ($failures === 0 ? "All checks passed.\n" : $failures . " check(s) FAILED.\n");
exit($failures === 0 ? 0 : 1);
