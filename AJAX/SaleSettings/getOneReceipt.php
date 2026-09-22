<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$shop_receipt_id = $_GET['receipt_id'];

$sql = "SELECT * FROM shopreceipts
INNER JOIN shop ON shop.SHID = shopreceipts.shop_id WHERE SRID = ".$shop_receipt_id.";";

$dbObj = new DBTransactions();
$receiptData = $dbObj->getData($sql);

echo json_encode($receiptData, JSON_FORCE_OBJECT);