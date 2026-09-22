<?php
/**
 * Live preview for Settings > Barcode Settings.
 * -----------------------------------------------------------------------------
 * Renders the code the CURRENT (unsaved) form values would produce, against a
 * real sub category of the shop, and lists everything wrong with the
 * combination in plain words.
 *
 * NEVER consumes a sequence number - it peeks. The operator is going to move
 * these sliders a hundred times before pressing Save.
 *
 * Response: { ok, code, sequence, scope_key, encodable, svg, warnings[] }
 */

require_once __DIR__ . '/../../Includes/barcode_ajax.php';
require_once __DIR__ . '/../../Includes/barcode_helper.php';
require_once __DIR__ . '/../../Model/barcode_settings_class.php';
require_once __DIR__ . '/../../Includes/barcode_generator.php';

list($shop_id, $user_id) = bcRequireSession();

try {
    $dbObj = new DBTransactions();

    if (!bcUserCanConfigure($dbObj, $user_id)) {
        bcFail('You do not have permission to change the barcode settings.');
    }//no right

    //the posted form, not what is saved - this is a preview of a draft
    $settings = bcgNormalizeSettings($_POST);

    $bcObj = new BarcodeSettings();
    $subcat_id = isset($_POST['preview_subcat']) ? (int) $_POST['preview_subcat'] : 0;

    $category = $bcObj->getCategoryContext($subcat_id);
    $shop = $bcObj->getShopContext($shop_id);

    $context = bcgBuildContext($settings, $category, $shop, 'Sample Product');
    $template = bcgComposeTemplate($settings, $context);
    $scope_key = bcgScopeKey($settings, $context, $template);
    $next = $bcObj->peekSequence($shop_id, $scope_key, $settings['SeqStart']);

    //three consecutive codes make the step and the padding obvious at a glance
    $series = array();
    for ($i = 0; $i < 3; $i++) {
        $series[] = bcgApplySequence($settings, $template, $next + ($i * $settings['SeqStep']));
    }//foreach

    $code = $series[0];
    $encodable = bcgEncodable($code, $settings['Symbology']);

    $warnings = bcgValidateSettings($settings, $context);

    if (!$encodable) {
        $warnings[] = 'This code cannot be encoded as ' . $settings['Symbology']
            . '. It will be printed as CODE 128 instead.';
    }//not encodable

    if ($bcObj->barcodeExists($code)) {
        $warnings[] = 'A product already uses ' . $code
            . '. The generator will skip past it to the next free number.';
    }//already taken

    if (empty($category)) {
        $warnings[] = 'No sub category was picked for the preview, so {CAT} and {SUB} are empty here. '
            . 'They will be filled in from the product being saved.';
    }//no category

    bcRespond(array(
        'ok'        => true,
        'code'      => $code,
        'series'    => $series,
        'sequence'  => $next,
        'scope_key' => $scope_key,
        'symbology' => $settings['Symbology'],
        'encodable' => $encodable,
        'svg'       => bcRenderBarcodeSvg($code, $settings['Symbology']),
        'cat_code'  => $context['CAT'],
        'sub_code'  => $context['SUB'],
        'shop_code' => $context['SHOP'],
        'warnings'  => $warnings,
    ));
}//try
catch (Throwable $e) {
    bcFail('Unable to build the preview. Please check the settings and try again.');
}//catch
