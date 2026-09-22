<?php
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";

$shop_id = $_SESSION['shop_id'];
$product_id = $_GET['product_id'];

$dbObj = new DBTransactions();
$shopObj = new Shop();
//shop data
$sql="SELECT * FROM shop WHERE SHID='$shop_id'";
$shopdata = $dbObj->getData($sql);

//company data
$company_id=$shopdata[0]["Company_CMID"];
$sql="SELECT * FROM company WHERE CMID='$company_id' ";
$companydata = $dbObj->getData($sql);
$is_commonStock = $companydata[0]["is_commonStock"];
$is_minus=$shopdata[0]["is_minus"];

if($shopdata[0]["is_minus"]==1)
{
    $sql = "SELECT * FROM `pricehistory`
    INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
    INNER JOIN products ON products.PDID = pricehistory.ProductID
    WHERE ProductID = ".$product_id." AND inventory.shop_SHID='$shop_id';";
}//allow minus
else
{
    $sql = "SELECT * FROM `pricehistory`
    INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
    INNER JOIN products ON products.PDID = pricehistory.ProductID
    WHERE ProductID = ".$product_id." AND inventory.CurrentQty > 0 AND inventory.shop_SHID='$shop_id';";
}//has inventory

$prodData = $dbObj->getData($sql);

echo json_encode($prodData);
