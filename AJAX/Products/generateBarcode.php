<?php
/**
 * Next barcode for the Add / Edit Product dialog.
 * -----------------------------------------------------------------------------
 * Two modes, and the difference between them matters:
 *
 *   mode=preview  (default) - PEEKS at the counter. Shows the operator what the
 *                             code will be. Consumes nothing, so opening the
 *                             dialog and closing it again wastes no numbers.
 *
 *   mode=generate           - ALLOCATES for real and hands back a code that is
 *                             already reserved. Only ever called when the
 *                             operator presses "Generate".
 *
 * Leaving the field empty and saving does NOT come through here at all - the
 * controller allocates at save time, so the code and the product are created in
 * the same request.
 *
 * Response: { ok, code, sequence, mode, symbology, encodable, message }
 */

require_once __DIR__ . '/../../Includes/barcode_ajax.php';
require_once __DIR__ . '/../../Model/barcode_settings_class.php';
require_once __DIR__ . '/../../Includes/barcode_helper.php';
require_once __DIR__ . '/../../Includes/barcode_generator.php';

list($shop_id, $user_id) = bcRequireSession();

try {
    $dbObj = new DBTransactions();

    if (!bcUserCanGenerate($dbObj, $user_id)) {
        bcFail('You do not have permission to create products.');
    }//no right

    $subcat_id = isset($_REQUEST['subcat_id']) ? (int) $_REQUEST['subcat_id'] : 0;
    $item_name = isset($_REQUEST['item_name']) ? (string) $_REQUEST['item_name'] : '';
    $mode      = (isset($_REQUEST['mode']) && $_REQUEST['mode'] === 'generate') ? 'generate' : 'preview';

    $bcObj = new BarcodeSettings();
    $settings = bcgNormalizeSettings($bcObj->getSettings($shop_id));

    if ((int) $settings['AutoGenerate'] !== 1 && $mode === 'preview') {
        //auto generation is switched off - say so instead of showing a code the
        //operator will never actually get
        bcRespond(array(
            'ok'       => true,
            'code'     => '',
            'mode'     => $mode,
            'disabled' => true,
            'message'  => 'Automatic barcodes are switched off for this shop.',
        ));
    }//switched off

    if ($mode === 'generate') {
        $code = bcgGenerateBarcode($bcObj, $shop_id, $subcat_id, $settings, $item_name);

        if ($code === '') {
            bcFail('A barcode could not be generated. Check Settings > Barcode Settings.');
        }//gave up

        bcRespond(array(
            'ok'        => true,
            'code'      => $code,
            'mode'      => 'generate',
            'symbology' => $settings['Symbology'],
            'encodable' => bcgEncodable($code, $settings['Symbology']),
        ));
    }//allocate for real

    //------------------------------------------------------------- preview
    $preview = bcgPreviewBarcode($bcObj, $shop_id, $subcat_id, $settings, $item_name);

    bcRespond(array(
        'ok'        => true,
        'code'      => $preview['code'],
        'sequence'  => $preview['sequence'],
        'mode'      => 'preview',
        'symbology' => $preview['symbology'],
        'encodable' => $preview['encodable'],
        'taken'     => $bcObj->barcodeExists($preview['code']),
    ));
}//try
catch (Throwable $e) {
    bcFail('Unable to build a barcode right now. Please try again.');
}//catch
