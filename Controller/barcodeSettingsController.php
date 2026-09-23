<?php
/**
 * Settings > Barcode Settings - form handler.
 * -----------------------------------------------------------------------------
 * Saves the automatic barcode rules for the current shop and manages the
 * sequence counters those rules run on.
 *
 * Every branch ends in a redirect (POST / Redirect / GET) so a refresh never
 * re-submits the form.
 */

include "../Includes/includes.php";
include "../Includes/barcode_helper.php";
include "../Includes/barcode_generator.php";

$shop_id = (int) $_SESSION['shop_id'];
$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

$dbObj = new DBTransactions();
$bcObj = new BarcodeSettings();

/**
 * Bounce back to the settings page with a status code.
 */
function bcsReturn($status)
{
    $_SESSION['barcode_settings_update'] = $status;
    header("Location: ../Public/barcode-settings.php");
    exit;
}//bcsReturn

//the page hides the buttons, this stops the endpoint being posted to directly
if (!bcUserCanConfigure($dbObj, $user_id)) {
    bcsReturn(0); //no permission
}//no right

//=============================================== save the generation rules ===
if (isset($_POST['btn_save_barcode_settings'])) {

    /*
     * Everything is normalised before it is stored, so a hand crafted POST
     * cannot put a 500 character pattern or a negative sequence step into the
     * table. The saved row is then always safe to use without re-checking.
     */
    $settings = bcgNormalizeSettings($_POST);

    //checkboxes are absent from the POST when they are off
    $settings['AutoGenerate'] = isset($_POST['AutoGenerate']) ? 1 : 0;
    $settings['StripInvalid'] = isset($_POST['StripInvalid']) ? 1 : 0;

    //label defaults are owned by the print dialog - keep whatever is stored
    $current = $bcObj->getSettings($shop_id);
    $settings['LabelDefaults'] = isset($current['LabelDefaults']) ? (string) $current['LabelDefaults'] : '';

    if (!$bcObj->saveSettings($shop_id, $settings, $user_id)) {
        bcsReturn(7); //could not be written
    }//failed

    bcsReturn(1); //saved
}//save settings

//=================================== a unique barcode on every unit ===
if (isset($_POST['btn_save_unit_settings'])) {

    require_once __DIR__ . '/../Model/unit_barcode_refused_class.php';
    require_once __DIR__ . '/../Model/product_unit_class.php';

    /*
     * The pattern must keep a serial, or two units of the same item made on the
     * same day would share a code. ProductUnits::settings() appends one anyway;
     * this refuses instead, so nobody saves a rule that is not what they typed.
     */
    $pattern = strtoupper(trim((string) (isset($_POST['UnitPattern']) ? $_POST['UnitPattern'] : '')));
    $pattern = ($pattern === '') ? ProductUnits::DEFAULTS['pattern'] : substr($pattern, 0, 160);

    if (strpos($pattern, '{ITEM}') === false || strpos($pattern, '{SEQ}') === false) {
        bcsReturn(8); //a unit code needs the item and a serial
    }//not a usable rule

    $saved = $bcObj->saveUnitSettings($shop_id, array(
        'UnitMode'      => isset($_POST['UnitMode']) ? 1 : 0,
        'UnitPattern'   => $pattern,
        'UnitSeqLength' => max(1, min(9, (int) (isset($_POST['UnitSeqLength']) ? $_POST['UnitSeqLength'] : 4))),
        'UnitSeparator' => substr((string) (isset($_POST['UnitSeparator']) ? $_POST['UnitSeparator'] : ''), 0, 4),
    ), $user_id);

    bcsReturn($saved ? 1 : 7);
}//save unit settings

//====================================================== sequence counters ====
else if (isset($_POST['btn_reset_sequence'])) {

    $scope_key = isset($_POST['scope_key']) ? trim((string) $_POST['scope_key']) : '';
    $next_value = isset($_POST['next_value']) ? (int) $_POST['next_value'] : 0;

    if ($scope_key === '') {
        bcsReturn(3); //nothing selected
    }//no key

    /*
     * The number is where the NEXT product starts, so the operator can continue
     * an existing series by typing the number after the last one they used.
     */
    $bcObj->setSequence($shop_id, $scope_key, $next_value);

    bcsReturn(2); //counter changed
}//reset a counter

else if (isset($_POST['btn_delete_sequence'])) {

    $scope_key = isset($_POST['scope_key']) ? trim((string) $_POST['scope_key']) : '';

    if ($scope_key === '') {
        bcsReturn(3); //nothing selected
    }//no key

    $bcObj->deleteSequence($shop_id, $scope_key);

    bcsReturn(4); //counter removed
}//delete a counter

//================================================== label print defaults =====
else if (isset($_POST['btn_reset_label_defaults'])) {

    $settings = bcgNormalizeSettings($bcObj->getSettings($shop_id));
    $settings['LabelDefaults'] = '';

    $bcObj->saveSettings($shop_id, $settings, $user_id);

    bcsReturn(5); //label defaults cleared
}//clear label defaults

//========================================================= category codes ====
else if (isset($_POST['btn_fill_category_codes'])) {

    /*
     * One click set up: give every category and sub category that has no code
     * yet the code the generator would have derived from its name anyway.
     *
     * Writing them down makes them visible and editable on the category pages,
     * and stops a later rename silently changing every new barcode.
     */
    $settings = bcgNormalizeSettings($bcObj->getSettings($shop_id));
    $catObj = new Category();

    $stat = $dbObj->getData(
        "SELECT company.CMID, company.is_multicategory
         FROM shop INNER JOIN company ON company.CMID = shop.Company_CMID
         WHERE shop.SHID = " . $shop_id . ";"
    );

    $company_id = empty($stat) ? 0 : (int) $stat[0]['CMID'];
    $multi = empty($stat) ? 0 : (int) $stat[0]['is_multicategory'];

    $filled = 0;

    foreach ($catObj->getCategoryByShop($shop_id, $multi, $company_id) as $row) {
        if (trim((string) $row['CategoryCode']) !== '') {
            continue;
        }//already has one

        $code = bcgDeriveCode($row['CategoryName'], $settings['CatCodeLength']);
        if ($code === '') {
            continue;
        }//nothing derivable

        $catObj->setCategoryCode($code, (int) $row['CTID']);
        $filled++;
    }//foreach category

    foreach ($catObj->getSubcategoryByShop($shop_id, $multi, $company_id) as $row) {
        if (trim((string) $row['SubCatCode']) !== '') {
            continue;
        }//already has one

        $code = bcgDeriveCode($row['SubCatName'], $settings['SubCodeLength']);
        if ($code === '') {
            continue;
        }//nothing derivable

        $catObj->setSubcategoryCode($code, (int) $row['SCID']);
        $filled++;
    }//foreach subcategory

    $_SESSION['barcode_codes_filled'] = $filled;
    bcsReturn(6); //codes filled in
}//fill category codes

//------------------------------------------------------------ nothing posted
bcsReturn(3);
