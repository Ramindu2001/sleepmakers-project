<?php
//End-to-end check of customer orders against a running copy of the application - by default
//the local XAMPP site:
//
//   C:/xampp/php/php.exe tests/e2e/customer_orders_e2e.php [base-url]
//
//The e2e Showroom orders a bed from the e2e Warehouse for a customer who already took a
//bedsheet; the warehouse accepts, creates the transfer (without the bedsheet) and verifies it;
//the showroom hands the order over. Like the other E2E scripts it creates its own e2e records
//and removes them at the end. Run it only against a development database.
require __DIR__ . '/lib.php';

$pdo = (new E2EDb())->pdo();
$fx = new E2EFixtures($pdo);
$stock = new E2EStock($pdo, $fx);
$fx->up();
$stock->up();
$pw = $fx->password;
$W = $fx->shops['W'];
$S = $fx->shops['S'];

//one customer order request, with the CSRF token of the page the browser was on
function order(E2EBrowser $browser, $action, array $fields, $token)
{
    return $browser->post('Controller/CustomerOrderController.php', $fields + ['action' => $action, 'csrf_token' => $token]);
}//order

try {
    echo "The showroom places an order\n";
    $bob = new E2EBrowser($base);
    signIn($bob, 'e2e_bob', $pw);
    shopLogin($bob, $S, 'e2e_bob', $pw);
    $bob->get('Public/home.php');
    $bobToken = $bob->csrf();         //one token per session (Includes/csrf.php)
    $bob->get('Public/customer-order.php?new=1');
    check('the new order form offers the warehouse and both kinds of line', $bob->has('e2e Warehouse')
        && $bob->has('Already given from our stock') && $bob->csrf() === $bobToken, $bob);
    checkClean('the new order form', $bob);

    order($bob, 'products', ['scope' => 'supplier', 'supplier_id' => $W, 'term' => 'e2e Bed'], $bobToken);
    $found = $bob->json('products');
    check('the product search shows the warehouse catalog with its stock', is_array($found) && isset($found[0])
        && $found[0]['name'] === 'e2e Bed' && $found[0]['in_stock'] === 10, $bob);

    $data = ['supplier_id' => $W, 'cust_name' => 'e2e Customer', 'cust_phone' => '0770000000', 'lines' => [
        ['source' => 'GIVEN', 'description' => 'e2e Bedsheet', 'qty' => '1', 'invoice_no' => 'INV-E2E-1'],
        ['source' => 'WAREHOUSE', 'product_id' => $stock->products['bed'], 'qty' => '2', 'notes' => 'King size'],
    ]];
    $bob->post('Controller/CustomerOrderController.php', ['action' => 'create', 'order' => json_encode($data)]);
    check('without the CSRF token nothing is placed (400)', $bob->status === 400, $bob);
    order($bob, 'create', ['order' => json_encode($data)], $bobToken);
    $orderId = (int)$bob->json('id');
    check('the order is placed', $bob->status === 200 && $orderId > 0 && $bob->json('order_no') === 'CO_000001', $bob);
    $bob->get('Public/customer-order.php?id=' . $orderId);
    check('the order page shows the given bedsheet as not to be sent', $bob->has('Already given - do not send')
        && $bob->has('e2e Bedsheet') && $bob->has('Requested'), $bob);
    checkClean('the order page', $bob);

    echo "The warehouse fulfils it\n";
    $alice = new E2EBrowser($base);
    signIn($alice, 'e2e_alice', $pw);
    shopLogin($alice, $W, 'e2e_alice', $pw);
    $alice->get('Public/home.php');
    $aliceToken = $alice->csrf();
    $alice->get('Public/customer-orders.php?tab=incoming');
    check('the warehouse sees the order as incoming, with the menu badge', $alice->has('CO_000001')
        && $alice->has('Customer Orders <span class="badge'), $alice);
    checkClean('the order list', $alice);
    order($alice, 'cancel', ['id' => $orderId], $aliceToken);
    check('the warehouse cannot cancel the showroom\'s order (404)', $alice->status === 404, $alice);
    order($alice, 'accept', ['id' => $orderId], $aliceToken);
    check('the warehouse accepts it', $alice->status === 200 && $alice->json('ok') === true, $alice);
    order($alice, 'create_transfer', ['id' => $orderId], $aliceToken);
    $transfer = (int)$alice->json('transfer_id');
    $lines = $pdo->query("SELECT products_PDID, TransferQty FROM transferdetails WHERE TransferHeader_THID = $transfer")->fetchAll(PDO::FETCH_ASSOC);
    check('the transfer carries only the bed', $alice->status === 200 && count($lines) === 1
        && (int)$lines[0]['products_PDID'] === $stock->products['bed'] && $lines[0]['TransferQty'] === '2.000', $alice);
    order($bob, 'cancel', ['id' => $orderId], $bobToken);
    check('once items are on the way the showroom cannot cancel (409)', $bob->status === 409, $bob);

    $alice->post('Controller/transferController.php', ['btn_verify_transfer' => '1', 'hide_transferheader_id' => $transfer]);
    check('the warehouse verifies the transfer', (int)$pdo->query("SELECT TransferStat FROM transferheader WHERE THID = $transfer")->fetchColumn() === 2, $alice);
    $bob->get('Public/customer-order.php?id=' . $orderId);
    check('after Verify the order shows Arrived', $bob->has('>Arrived<'), $bob);

    echo "The showroom hands it over\n";
    order($bob, 'handover', ['id' => $orderId, 'invoice_no' => 'INV-E2E-2'], $bobToken);
    check('the showroom hands it over', $bob->status === 200, $bob);
    $bob->get('Public/customer-order.php?id=' . $orderId);
    check('the order shows Handed over with its invoice number', $bob->has('Handed over') && $bob->has('INV-E2E-2'), $bob);

    echo "Refusals\n";
    $clerk = new E2EBrowser($base);
    signIn($clerk, 'e2e_alice', $pw);
    shopLogin($clerk, $S, 'e2e_alice', $pw);        //Cashier in the showroom: no Customer Orders right
    $clerkToken = $clerk->csrf();
    order($clerk, 'create', ['order' => json_encode($data)], $clerkToken);
    check('a role without the right cannot place orders (403)', $clerk->status === 403, $clerk);
    $clerk->get('Public/customer-orders.php');
    check('and is turned away from the pages', $clerk->isOn('Public/home.php'), $clerk);
    $anon = new E2EBrowser($base);
    $anon->post('Controller/CustomerOrderController.php', ['action' => 'accept', 'id' => $orderId, 'csrf_token' => $aliceToken]);
    check('someone not signed in is refused (403)', $anon->status === 403, $anon);

    //scenarios of later tasks are added above this line
} finally {
    $stock->down();
    $fx->down();
}

echo $failures === 0 ? "\nAll checks passed.\n" : "\n" . $failures . " check(s) FAILED.\n";
exit($failures === 0 ? 0 : 1);
