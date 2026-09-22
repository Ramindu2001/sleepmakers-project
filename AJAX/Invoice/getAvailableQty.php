<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";

$shop_id = $_SESSION['shop_id'];
$selldetail_id = $_GET['selldetail_id'];

$dbObj = new DBTransactions();
$shopObj = new Shop();

$sql = "SELECT * FROM `selldetail` WHERE SDID = ".$selldetail_id.";";
$sellData = $dbObj->getData($sql);

$pricehistory_id = $sellData[0]['pricehistory_id'];
$product_id = $sellData[0]['product_id'];

$available_qty = 0;
$qty_stat = 0;
if($shopObj->hasMinus($shop_id))
{
    $qty_stat = 0;
    $available_qty = 0;
}//has minus
else
{
    $qty_stat = 1;
    //get available qty
    $sql = "SELECT sum(CurrentQty) as available_qty FROM `inventory` WHERE products_PDID = ".$product_id.";";

    $invData = $dbObj->getData($sql);
    $available_qty = $invData[0]['available_qty'];
}

$data = array("qty_stat"=>$qty_stat, "avl_qty"=>$available_qty);

echo json_encode($data);
// echo "working on this one. ";