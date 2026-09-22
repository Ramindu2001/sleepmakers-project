<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/product_class.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";

$shop_id = $_SESSION['shop_id'];
$product_id = $_GET['product_id'];
$batch_id = $_GET['batch_id'];

$shopObj = new Shop();
$dbObj = new DBTransactions();
$sql = "";
if($product_id > 0)
{
    $sql = "SELECT * FROM pricehistory
            INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
            WHERE ProductID = ".$product_id." AND pricehistory.BatchID='".$batch_id."' AND inventory.shop_SHID = ".$shop_id." AND CurrentQty > 0
            ORDER BY inventory.INID ASC;";
    $dbData = $dbObj->getData($sql);

    $prod_current_qty = floatval($dbData[0]['CurrentQty']);
    $variation_id = $dbData[0]['VariationID'];
    $variation_name = empty($dbData[0]['VariationName']) ? "No Variation" : $dbData[0]['VariationName'];
    $unit_purchase_price = $dbData[0]['PurchasePrice'];
    $unit_selling_price = $dbData[0]['SellingPrice'];
    $mnf_date = $dbData[0]['MnfDate'];
    $exp_date = $dbData[0]['ExpDate'];

    $arr = array();

    $arr ['variation_id'] = $variation_id;
    $arr ['variation_name'] = $variation_name;
    $arr ['current_qty'] = $prod_current_qty;
    $arr ['unit_price'] = $unit_purchase_price;
    $arr ['sell_price'] = $unit_selling_price;
    $arr ['mnf_date'] = $mnf_date;
    $arr ['exp_date'] = $exp_date;

    echo json_encode($arr);
}//have product
else
{
    $arr = array();
    $arr ['variation_id'] = 0;
    $arr ['variation_name'] = 0;
    $arr ['current_qty'] = 0;
    $arr ['unit_price'] = 0;
    $arr ['sell_price'] = 0;
    $arr ['mnf_date'] = 0;
    $arr ['exp_date'] = 0;

    echo json_encode($arr);
}//no product