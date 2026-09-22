<?php
/**
 * Save the current Print Barcode dialog settings as the shop's default.
 * -----------------------------------------------------------------------------
 * Stored as JSON on barcodesettings.LabelDefaults. Every Print Barcode dialog
 * in the shop then opens on these values, with the operator's own browser
 * choices layered on top.
 *
 * The options are normalised through bcResolveOptions() before they are stored,
 * so a hand crafted POST cannot park a 500mm font size in the database.
 *
 * Response: { ok, message }
 */

require_once __DIR__ . '/../../Includes/barcode_ajax.php';
require_once __DIR__ . '/../../Includes/barcode_helper.php';
require_once __DIR__ . '/../../Model/barcode_settings_class.php';
require_once __DIR__ . '/../../Includes/barcode_generator.php';

list($shop_id, $user_id) = bcRequireSession();

try {
    $dbObj = new DBTransactions();

    if (!bcUserCanConfigure($dbObj, $user_id)) {
        bcFail('You do not have permission to change the shop defaults.');
    }//no right

    $raw = isset($_POST['options']) ? (string) $_POST['options'] : '';
    $posted = json_decode($raw, true);

    if (!is_array($posted)) {
        bcFail('Nothing to save.');
    }//not usable

    /*
     * Resolve against the size that was actually chosen, so the "0 = automatic"
     * font sizes stay 0 in the stored profile instead of being frozen to one
     * sticker's typography. bcResolveOptions() fills a 0 in from the size, so
     * the zeros are put back afterwards.
     */
    $size = bcResolveLabelSize(
        isset($posted['size']) ? $posted['size'] : bcDefaultLabelSize(),
        isset($posted['custom_w']) ? $posted['custom_w'] : 0,
        isset($posted['custom_h']) ? $posted['custom_h'] : 0
    );

    $options = bcResolveOptions($posted, $size);

    //keep "automatic" as automatic
    $auto_keys = array('padding', 'line_gap', 'shop_font', 'name_font', 'code_font',
                       'price_font', 'small_font', 'bar_height');
    foreach ($auto_keys as $key) {
        if (!isset($posted[$key]) || (float) $posted[$key] <= 0) {
            $options[$key] = 0;
        }//was left on automatic
    }//foreach

    //font_css is derived, never stored
    unset($options['font_css']);

    /*
     * When this default was set. The dialog compares it with the timestamp on
     * the operator's own remembered choices, so a newly saved shop default
     * actually reaches people who have already used the dialog once.
     */
    $options['saved_at'] = time();

    $bcObj = new BarcodeSettings();
    $settings = bcgNormalizeSettings($bcObj->getSettings($shop_id));
    $settings['LabelDefaults'] = json_encode($options);

    if (!$bcObj->saveSettings($shop_id, $settings, $user_id)) {
        bcFail('The shop default could not be written.');
    }//failed

    bcRespond(array('ok' => true, 'message' => 'Saved'));
}//try
catch (Throwable $e) {
    bcFail('Unable to save the shop default.');
}//catch
