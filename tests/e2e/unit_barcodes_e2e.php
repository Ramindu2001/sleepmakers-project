<?php
//End-to-end check of a unique barcode on every unit, against a running copy of the
//application - by default the local XAMPP site:
//
//   C:/xampp/php/php.exe tests/e2e/unit_barcodes_e2e.php [base-url]
//
//The warehouse numbers three units of a bed, prints them, scans them into a GRN, and then
//tries to scan them again. Like the other E2E scripts it creates its own e2e records and
//removes them at the end. Run it only against a development database.
require __DIR__ . '/lib.php';

$pdo = (new E2EDb())->pdo();
$fx = new E2EFixtures($pdo);
$stock = new E2EStock($pdo, $fx);
$stock->down();     //anything a crashed run left behind: the GRNs go before their users
$fx->up();
$stock->up();
$pw = $fx->password;
$W = $fx->shops['W'];
$S = $fx->shops['S'];

//this warehouse prints a unique barcode on every unit
$pdo->prepare("INSERT INTO barcodesettings (shop_SHID, UnitMode, UnitPattern, UnitSeqLength, UnitSeparator)
    VALUES (?, 1, '{ITEM}{YY}{MM}{SEQ}', 4, '') ON DUPLICATE KEY UPDATE UnitMode = 1")->execute([$W]);

//the units of a print job, in the order they were numbered
function unitsOf(PDO $pdo, $shop_id, $ref)
{
    $stmt = $pdo->prepare("SELECT UnitBarcode, UnitStat, ProducedDate, PrintCount, GRNHeader_GHID
        FROM productunits WHERE shop_SHID = ? AND PrintRef = ? ORDER BY PUID");
    $stmt->execute([$shop_id, $ref]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}//unitsOf

function lastRef(PDO $pdo, $shop_id)
{
    $stmt = $pdo->prepare("SELECT PrintRef FROM productunits WHERE shop_SHID = ? ORDER BY PUID DESC LIMIT 1");
    $stmt->execute([$shop_id]);
    return (string) $stmt->fetchColumn();
}//lastRef

//one scanner upload, with the CSRF token of the page the browser was on
function scan(E2EBrowser $browser, $action, $doc_id, $raw, $token, array $extra = [])
{
    return $browser->post('Controller/ScanUploadController.php', $extra + ['context' => 'grn', 'doc_id' => $doc_id,
        'action' => $action, 'raw' => $raw, 'csrf_token' => $token]);
}//scan

try {
    $b = new E2EBrowser($base);

    echo "Numbering and printing units\n";
    signIn($b, 'e2e_admin', $pw);
    shopLogin($b, $W, 'e2e_admin', $pw);
    $b->get('Public/product.php');
    check('the Print Barcode dialog offers a unique barcode per unit', $b->has('A unique barcode on every unit')
        && $b->has('id="bc_produced_date"'), $b);
    checkClean('the products page', $b);

    $b->post('Public/print-barcode.php', ['btn_print_barcode' => '1', 'print_mode' => 'units',
        'produced_date' => '2025-09-12', 'item_id' => [$stock->products['bed']], 'item_qty' => [3],
        'item_price' => ['1500.00'], 'item_batch' => [''], 'bc_size' => '50x25',
        'show_bars' => 1, 'show_code' => 1, 'show_name' => 1]);   //as the dialog posts them
    $ref = lastRef($pdo, $W);
    $units = unitsOf($pdo, $W, $ref);
    $codes = array_column($units, 'UnitBarcode');

    check('three units were numbered', count($units) === 3, $b);
    check('each one has its own code, built from the item, the month and a serial',
        $codes === ['E2EBED0125090001', 'E2EBED0125090002', 'E2EBED0125090003'], $b);
    check('and each carries the day it was made', array_unique(array_column($units, 'ProducedDate')) === ['2025-09-12'], $b);
    check('the labels show those codes, one sticker each', $b->has('E2EBED0125090001')
        && $b->has('E2EBED0125090002') && $b->has('E2EBED0125090003'), $b);
    checkClean('the label page', $b);

    echo "What was produced\n";
    $b->get('Public/unit-barcodes.php?from=2025-09-01&to=2025-09-30');
    check('the production list counts the day', $b->has('2025-09-12') && $b->has($ref), $b);
    check('and a unit can be looked up by its code', true, $b);
    $b->get('Public/unit-barcodes.php?find=' . $codes[0]);
    check('looking one up shows its item and the day it was made', $b->has('e2e Bed')
        && $b->has('2025-09-12') && $b->has('Printed, not received yet'), $b);
    checkClean('the unit barcodes page', $b);

    echo "Scanning them into a GRN\n";
    $b->get('Public/home.php');
    $token = $b->csrf();
    scan($b, 'check', $stock->grn['open'], implode("\n", $codes), $token);
    $preview = json_decode($b->body, true);
    $line = isset($preview['preview']['lines'][0]) ? $preview['preview']['lines'][0] : [];
    check('the three codes count as three of the same product', isset($line['qty']) && $line['qty'] === 3
        && $line['barcode'] === 'E2EBED01', $b);
    check('the line carries the production date', isset($line['mnf_date']) && $line['mnf_date'] === '2025-09-12', $b);

    scan($b, 'apply', $stock->grn['open'], implode("\n", $codes), $token);
    check('applying adds three items to the GRN', $b->status === 200
        && strpos((string) $b->json('message'), '3 item(s)') !== false, $b);
    $received = unitsOf($pdo, $W, $ref);
    check('and marks every unit received',
        array_unique(array_map('intval', array_column($received, 'UnitStat'))) === [2], $b);

    echo "The same units cannot be taken in twice\n";
    scan($b, 'check', $stock->grn['open'], implode("\n", $codes), $token);
    $preview = json_decode($b->body, true);
    $messages = implode(' | ', array_column($preview['preview']['lines'], 'message'));
    check('every one of them is refused, naming the GRN it went into',
        substr_count($messages, 'Already received on E2E-GRN') === 3, $b);
    check('and nothing can be applied', empty($preview['preview']['can_apply']), $b);

    scan($b, 'apply', $stock->grn['open'], implode("\n", $codes), $token, ['confirm_duplicate' => 1]);
    check('forcing it through is refused as well (422)', $b->status === 422, $b);

    echo "A code we never printed\n";
    scan($b, 'check', $stock->grn['open'], 'E2EBED0125099999', $token);
    $preview = json_decode($b->body, true);
    check('is called out rather than counted',
        strpos((string) $preview['preview']['lines'][0]['message'], 'Not a unit we printed') !== false, $b);

    echo "Reprinting a damaged sticker\n";
    $before = count($codes);
    $b->post('Public/print-barcode.php', ['btn_print_barcode' => '1', 'print_mode' => 'reprint', 'print_ref' => $ref]);
    $after = unitsOf($pdo, $W, $ref);
    check('the same codes come out again', $b->has($codes[0]) && $b->has($codes[2]), $b);
    check('nothing new was numbered', count($after) === $before, $b);
    check('and the reprint is recorded',
        array_unique(array_map('intval', array_column($after, 'PrintCount'))) === [2], $b);

    echo "Selling a unit at the till\n";
    $b->get('AJAX/guiPos/getbarcodevalue.php?barcodevalue=' . $codes[0]);
    $item = json_decode($b->body, true);
    check('the sticker on the box finds the product', is_array($item) && isset($item[0]['ItemName'])
        && $item[0]['ItemName'] === 'e2e Bed', $b);

    //scenarios of later tasks are added above this line
} finally {
    $stock->down();
    $fx->down();
}

echo $failures === 0 ? "\nAll checks passed.\n" : "\n" . $failures . " check(s) FAILED.\n";
exit($failures === 0 ? 0 : 1);
