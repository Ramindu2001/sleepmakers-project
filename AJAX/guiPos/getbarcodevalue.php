<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$shop_id = $_SESSION['shop_id'];
$dbObj = new DBTransactions();
$sql = "SELECT * FROM shop
INNER JOIN company ON company.CMID = shop.Company_CMID
WHERE SHID = ".$shop_id.";";
$shopData = $dbObj->getData($sql);
$multi_category = $shopData[0]['is_multicategory'];
$com_id=$shopData[0]['CMID'];
// Always treat the barcode as a STRING so leading zeros are preserved.
$barcodevalue = isset($_GET['barcodevalue']) ? trim((string)$_GET['barcodevalue']) : "";

if($barcodevalue !== "")
{
    $dbObj = new DBTransactions();

    // Build the list of barcode variants to try, in priority order, so that a
    // product is found whether or not the scanner/database keeps the leading zero(s).
    //   1) Exact value as scanned.
    //   2) Value with a single leading zero prepended (scanner dropped the leading 0).
    //   3) Value with all leading zeros stripped (database stored it without the 0).
    $candidates = [];
    $candidates[] = $barcodevalue;
    $candidates[] = "0" . $barcodevalue;
    $stripped = ltrim($barcodevalue, "0");
    if($stripped === ""){ $stripped = "0"; }
    $candidates[] = $stripped;

    // De-duplicate while preserving priority order.
    $candidates = array_values(array_unique($candidates));

    $itemData = [];
    foreach($candidates as $candidate)
    {
        if($multi_category==1)
        {
            $sql = "SELECT * FROM products p
                    INNER JOIN shop s ON s.SHID=p.shop_SHID
                    WHERE p.Barcode = ? AND s.Company_CMID = ?";
            $params = [$candidate, $com_id];
        }
        else
        {
            $sql = "SELECT * FROM products WHERE Barcode = ? AND shop_SHID = ?";
            $params = [$candidate, $shop_id];
        }

        $itemData = $dbObj->getMultipleData($sql, $params);
        if(count($itemData) > 0)
        {
            // Stop at the first variant that matches a product.
            break;
        }
    }

    echo json_encode($itemData);
}
else
{
    echo json_encode([]);
}

