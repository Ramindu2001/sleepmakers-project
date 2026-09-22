<?php
//End-to-end check of the scanner upload (GRN, transfer send, transfer receive) against a
//running copy of the application - by default the local XAMPP site:
//
//   C:/xampp/php/php.exe tests/e2e/scan_upload_e2e.php [base-url]
//
//Like shop_login_e2e.php it creates its own e2e shops, users, products, stock and documents,
//drives the site over HTTP and removes everything at the end, even when a check fails. Run it
//only against a development database.
require __DIR__ . '/lib.php';

$pdo = (new E2EDb())->pdo();
$fx = new E2EFixtures($pdo);
$stock = new E2EStock($pdo, $fx);
$fx->up();
$stock->up();
$pw = $fx->password;
$W = $fx->shops['W'];
$S = $fx->shops['S'];
$endpoint = 'Controller/ScanUploadController.php';

//one scanner upload request, with the CSRF token of the page the browser is on
function scan(E2EBrowser $browser, $context, $doc, $action, $raw, array $more = [], $token = null)
{
    global $endpoint;
    return $browser->post($endpoint, $more + ['context' => $context, 'doc_id' => $doc, 'action' => $action, 'raw' => $raw,
        'csrf_token' => $token === null ? $browser->csrf() : $token]);
}//scan

try {
    $a = new E2EBrowser($base);

    echo "GRN\n";
    signIn($a, 'e2e_alice', $pw);
    shopLogin($a, $W, 'e2e_alice', $pw);
    $a->post('Public/grn-details.php', ['grn_header_id' => $stock->grn['open'], 'grn_header_stat' => 0]);
    $token = $a->csrf();
    check('the GRN page offers Scan / Upload', $a->has('btn-scan-upload') && $a->has('id="scan_upload_modal"') && $token !== '', $a);
    checkClean('the GRN page with the scanner dialog', $a);

    $raw = "E2EBED01\nE2EBED01\nE2ESHT01\n";
    scan($a, 'grn', $stock->grn['open'], 'check', $raw, [], $token);
    $preview = $a->json('preview');
    check('check previews one line per product with its count', $a->status === 200 && is_array($preview)
        && $preview['applied'] === ['E2EBED01' => 2, 'E2ESHT01' => 1] && $preview['can_apply'] === true, $a);

    $a->post($endpoint, ['context' => 'grn', 'doc_id' => $stock->grn['open'], 'action' => 'check', 'raw' => $raw]);
    check('a request without the CSRF token is refused (400)', $a->status === 400, $a);

    scan($a, 'grn', $stock->grn['open'], 'apply', "E2EBED01\nNOPE\n", [], $token);
    check('apply refuses a code that is not a product until it is left out (422)', $a->status === 422
        && $stock->grnLines($stock->grn['open']) === [], $a);

    scan($a, 'grn', $stock->grn['open'], 'apply', $raw, [], $token);
    $lines = $stock->grnLines($stock->grn['open']);
    check('apply adds the lines to the GRN', $a->status === 200 && $a->json('ok') === true && count($lines) === 2
        && $lines[0]['InitQty'] === '2.000' && $lines[1]['InitQty'] === '1.000', $a);

    scan($a, 'grn', $stock->grn['open'], 'apply', $raw, [], $token);
    check('the same batch again asks for a confirmation (409)', $a->status === 409 && $a->json('confirm') === 'duplicate'
        && $stock->grnLines($stock->grn['open'])[0]['InitQty'] === '2.000', $a);
    scan($a, 'grn', $stock->grn['open'], 'apply', $raw, ['confirm_duplicate' => 1], $token);
    check('confirmed, it is added to the same lines', $a->status === 200 && count($stock->grnLines($stock->grn['open'])) === 2
        && $stock->grnLines($stock->grn['open'])[0]['InitQty'] === '4.000', $a);

    scan($a, 'grn', $stock->grn['verified'], 'check', $raw, [], $token);
    check('a verified GRN is refused (409)', $a->status === 409, $a);
    scan($a, 'grn', $stock->grn['showroom'], 'check', $raw, [], $token);
    check('another shop\'s GRN is refused (404)', $a->status === 404, $a);

    $a->get('Public/switchshop.php');
    shopLogin($a, $S, 'e2e_alice', $pw);
    scan($a, 'grn', $stock->grn['showroom'], 'check', $raw, [], $token);
    check('a role without GRN rights is refused (403)', $a->status === 403 && $a->json('ok') === false, $a);

    $anon = new E2EBrowser($base);
    $anon->post($endpoint, ['context' => 'grn', 'doc_id' => $stock->grn['open'], 'action' => 'apply', 'raw' => $raw, 'csrf_token' => $token]);
    check('someone not signed in is refused (403)', $anon->status === 403, $anon);

    echo "Transfer - sending\n";
    signIn($a, 'e2e_alice', $pw);
    shopLogin($a, $W, 'e2e_alice', $pw);
    $a->get('Public/transfer-details.php?id=' . $stock->transfer);
    $token = $a->csrf();
    check('the sending shop\'s transfer page offers Scan / Upload', $a->has('btn-scan-upload') && $a->has('data-context="transfer_send"'), $a);
    checkClean('the transfer page with the scanner dialog', $a);
    scan($a, 'transfer_send', $stock->transfer, 'check', str_repeat("E2EBED01\n", 7), [], $token);
    $line = $a->status === 200 ? $a->json('preview')['lines'][0] : null;
    check('check picks the oldest batches first', $line !== null && $line['available'] === 10
        && array_column($line['batches'], 'qty') === [5, 2] && !isset($line['parts']), $a);
    scan($a, 'transfer_send', $stock->transfer, 'apply', str_repeat("E2EBED01\n", 7) . "E2ESHT01\nE2ESHT01\nE2ESHT01\n", [], $token);
    check('apply adds a line per batch', $a->status === 200 && array_column($stock->transferLines(), 'TransferQty') === ['5.000', '2.000', '3.000'], $a);
    scan($a, 'transfer_send', $stock->transfer, 'apply', str_repeat("E2EBED01\n", 4), [], $token);
    check('more than the stock left is refused (422)', $a->status === 422 && count($stock->transferLines()) === 3, $a);

    echo "Transfer - receiving\n";
    $bob = new E2EBrowser($base);
    signIn($bob, 'e2e_bob', $pw);
    shopLogin($bob, $S, 'e2e_bob', $pw);
    $bob->get('Public/transfer-details.php?id=' . $stock->transfer);
    $bobToken = $bob->csrf();
    check('the receiving shop\'s transfer page offers Scan received items', $bob->has('Scan received items')
        && $bob->has('data-context="transfer_receive"'), $bob);
    checkClean('the receiving transfer page', $bob);
    $raw = str_repeat("E2EBED01\n", 6) . str_repeat("E2ESHT01\n", 3);
    scan($bob, 'transfer_receive', $stock->transfer, 'check', $raw, [], $bobToken);
    $bed = $bob->status === 200 ? $bob->json('preview')['lines'][0] : null;
    check('check compares what arrived with what was sent', $bed !== null && [$bed['sent'], $bed['qty'], $bed['message']] === [7, 6, 'Short 1']
        && !isset($bob->json('preview')['groups']), $bob);
    scan($bob, 'transfer_receive', $stock->transfer, 'apply', $raw, [], $bobToken);
    check('a shortage asks for a confirmation (409)', $bob->status === 409 && $bob->json('confirm') === 'short', $bob);
    scan($bob, 'transfer_receive', $stock->transfer, 'apply', $raw, ['confirm_short' => 1], $bobToken);
    check('confirmed, the received quantities are set in line order', $bob->status === 200
        && array_column($stock->transferLines(), 'ReceivedQty') === ['5.000', '1.000', '3.000'], $bob);
    scan($a, 'transfer_receive', $stock->transfer, 'check', $raw, [], $token);
    check('the sending shop cannot receive (404)', $a->status === 404, $a);
    scan($bob, 'transfer_send', $stock->transfer, 'check', $raw, [], $bobToken);
    check('the receiving shop cannot send (404)', $bob->status === 404, $bob);

    //scenarios of later tasks are added above this line
} finally {
    $stock->down();
    $fx->down();
}

echo $failures === 0 ? "\nAll checks passed.\n" : "\n" . $failures . " check(s) FAILED.\n";
exit($failures === 0 ? 0 : 1);
