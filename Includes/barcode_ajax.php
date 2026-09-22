<?php
/**
 * Shared bootstrap for the barcode AJAX endpoints.
 * -----------------------------------------------------------------------------
 * Every endpoint in this module answers with JSON, and every one of them has to
 * survive the same hazard: Includes/config.php is saved with a UTF-8 BOM, and
 * any include that leaks a byte before the JSON turns a valid response into a
 * parse error in the browser.
 *
 * So: buffer everything, then throw the buffer away when the response is sent.
 */

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

ob_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../Model/DB_Class.php';

if (!function_exists('bcRespond')) {

    /**
     * Send a JSON payload and discard anything that leaked into the buffer.
     */
    function bcRespond($payload)
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }//drop stray output

        echo json_encode($payload);
        exit;
    }//bcRespond

    /**
     * Send a failure payload.
     */
    function bcFail($message)
    {
        bcRespond(array('ok' => false, 'message' => $message));
    }//bcFail

    /**
     * The logged in shop and user, or a failure response. Returns
     * array($shop_id, $user_id).
     */
    function bcRequireSession()
    {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['shop_id'])) {
            bcFail('Your session has expired. Please log in again.');
        }//not logged in

        return array((int) $_SESSION['shop_id'], (int) $_SESSION['user_id']);
    }//bcRequireSession

    /**
     * Company id and multi category flag for a shop.
     * Returns array('name'=>.., 'company_id'=>.., 'multi_category'=>..).
     */
    function bcShopStat($dbObj, $shop_id)
    {
        $sql = "SELECT shop.ShopName, company.CMID, company.is_multicategory
                FROM shop
                INNER JOIN company ON company.CMID = shop.Company_CMID
                WHERE shop.SHID = " . (int) $shop_id . ";";

        $rows = $dbObj->getData($sql);

        if (empty($rows)) {
            bcFail('Shop could not be identified. Please switch the shop and try again.');
        }//no shop

        return array(
            'name'           => (string) $rows[0]['ShopName'],
            'company_id'     => (int) $rows[0]['CMID'],
            'multi_category' => (int) $rows[0]['is_multicategory'],
        );
    }//bcShopStat

}//function guard
