<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";

$shop_id = $_SESSION['shop_id'];
$txt_input = $_GET['txt_input'];

$shopObj = new Shop();

if($shopObj->hasMinus($shop_id))
{
    $sql = "SELECT * FROM pricehistory
    INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
    INNER JOIN products ON products.PDID = pricehistory.ProductID
    LEFT JOIN variations ON variations.VRID = pricehistory.VariationID
    WHERE inventory.shop_SHID = ".$shop_id." AND concat(Barcode, ItemName) LIKE '%".$txt_input."%';";
}//has minus
else
{
    $sql = "SELECT * FROM pricehistory
    INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
    INNER JOIN products ON products.PDID = inventory.products_PDID
    LEFT JOIN variations ON variations.VRID = pricehistory.VariationID
    WHERE inventory.shop_SHID = ".$shop_id." AND CurrentQty > 0 AND concat(Barcode, ItemName) LIKE '%".$txt_input."%';";
}//no minus

$dbObj = new DBTransactions();
$dbData = $dbObj->getData($sql);

echo json_encode($dbData, JSON_FORCE_OBJECT);
