<?php
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";

$shopObj = new Shop();
$dbObj = new DBTransactions();

$shop_id = $_SESSION['shop_id'];
$product_id = $_GET['product_id'];

//get company stat
$sql = "SELECT * FROM shop
INNER JOIN company ON company.CMID = shop.Company_CMID
WHERE SHID = ".$shop_id.";";

$shopData = $dbObj->getData($sql);
$multi_category = floatval($shopData[0]['is_multicategory']);
$company_id = floatval($shopData[0]['CMID']);

if($multi_category == 1)
{
    $sql = "SELECT * FROM `inventory` 
    INNER JOIN pricehistory ON pricehistory.Inventory_INID = inventory.INID
    WHERE products_PDID = ".$product_id.";";
}//hyas multi category
else
{
    $sql = "SELECT * FROM `inventory` 
    INNER JOIN pricehistory ON pricehistory.Inventory_INID = inventory.INID
    WHERE products_PDID = ".$product_id." AND shop_SHID = ".$shop_id.";";
}//no multi category

$prodData = $dbObj->getData($sql);

echo json_encode($prodData);

// if(!empty($prodData))
// { 
    
// }//has data