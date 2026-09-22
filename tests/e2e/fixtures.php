<?php
//The e2e records for the browser checks (tests/ui/*.mjs), from the command line:
//
//   C:/xampp/php/php.exe tests/e2e/fixtures.php up     creates them, prints ids and password as JSON
//   C:/xampp/php/php.exe tests/e2e/fixtures.php down   removes them
//
//The same records as the end-to-end checks (tests/e2e/lib.php); run it only against a
//development database.
$command = isset($argv[1]) ? $argv[1] : '';
$argv = [$argv[0]]; //lib.php reads an optional base url from the arguments
require __DIR__ . '/lib.php';

$pdo = (new E2EDb())->pdo();
$fx = new E2EFixtures($pdo);
$stock = new E2EStock($pdo, $fx);

if ($command === 'up') {
    $fx->up();
    $stock->up();
    echo json_encode([
        'password' => $fx->password,
        'shops' => $fx->shops,
        'users' => $fx->users,
        'products' => $stock->products,
        'grn' => $stock->grn,
        'transfer' => $stock->transfer,
    ]), "\n";
} elseif ($command === 'down') {
    $stock->down();
    $fx->down();
    echo "e2e records removed\n";
} else {
    fwrite(STDERR, "usage: php tests/e2e/fixtures.php up|down\n");
    exit(2);
}
