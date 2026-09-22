<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";

$shop_id = $_SESSION['shop_id'];
$product_id = $_GET['product_id'];
$shopObj = new Shop();
$dbObj = new DBTransactions();
$sql = "";

        $sql = "SELECT PHID, ProductID, pricehistory.BatchID, pricehistory.SellingPrice FROM pricehistory
        INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
        WHERE inventory.shop_SHID = ".$shop_id." AND ProductID = '".$product_id."' AND CurrentQty > 0;";
$batchData = $dbObj->getData($sql);

echo json_encode($batchData);