<?php
/**
 * Data for the "Print Barcode" dialog.
 * -----------------------------------------------------------------------------
 * Returns, for every selected product: the item name, the barcode value, the
 * batch prices that can be printed, and how many barcode modules the value
 * encodes to (used to warn when a long code is squeezed onto a small sticker).
 *
 * Also returns the shop's saved label defaults, so the dialog opens on the
 * settings the shop actually uses rather than on the factory defaults.
 *
 * Response: { ok, shop_name, defaults, items:[...] }
 */

require_once __DIR__ . '/../../Includes/barcode_ajax.php';
require_once __DIR__ . '/../../Includes/barcode_helper.php';
require_once __DIR__ . '/../../Model/barcode_settings_class.php';

list($shop_id, $user_id) = bcRequireSession();

$raw_ids = isset($_REQUEST['product_ids']) ? $_REQUEST['product_ids'] : '';
if (!is_array($raw_ids)) {
    $raw_ids = explode(',', (string) $raw_ids);
}//normalise to array

$product_ids = array();
foreach ($raw_ids as $id) {
    $id = (int) trim((string) $id);
    if ($id > 0) {
        $product_ids[$id] = $id;
    }//valid
}//foreach

if (empty($product_ids)) {
    bcFail('No product was selected.');
}//nothing selected

try {
    $dbObj = new DBTransactions();

    if (!bcUserCanPrint($dbObj, $user_id)) {
        bcFail('You do not have permission to print barcode labels.');
    }//no print right

    $stat = bcShopStat($dbObj, $shop_id);

    $products = bcGetPrintableProducts(
        $dbObj,
        $product_ids,
        $shop_id,
        $stat['company_id'],
        $stat['multi_category']
    );

    $items = array();
    foreach ($products as $product) {
        $prices = bcGetProductPrices($dbObj, $product['PDID'], $shop_id, $stat['multi_category']);

        $default_price = !empty($prices) ? $prices[0]['price'] : round($product['ProdSellPrice'], 2);
        $barcode = trim($product['Barcode']);

        $items[] = array(
            'id'        => $product['PDID'],
            'name'      => $product['ItemName'],
            'second'    => $product['SecondName'],
            'sku'       => $product['ProductNo'],
            'category'  => $product['CategoryName'],
            'subcat'    => $product['SubCatName'],
            'barcode'   => $barcode,
            'price'     => number_format($default_price, 2, '.', ''),
            'prices'    => $prices,
            'modules'   => bcBarcodeModuleCount($barcode),
            'printable' => ($barcode !== ''),
        );
    }//foreach product

    if (empty($items)) {
        bcFail('The selected products are not available for this shop.');
    }//nothing printable

    //the shop's saved label defaults, so the dialog opens where the shop left it
    $bcObj = new BarcodeSettings();
    $settings = $bcObj->getSettings($shop_id);
    $defaults = bcShopLabelDefaults(isset($settings['LabelDefaults']) ? $settings['LabelDefaults'] : '');

    bcRespond(array(
        'ok'        => true,
        'shop_name' => $stat['name'],
        'defaults'  => $defaults,
        'items'     => $items,
    ));
}//try
catch (Throwable $e) {
    bcFail('Unable to load the barcode data. Please try again.');
}//catch
