<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$sell_id = $_GET['sell_id'];

$sql = "SELECT * FROM selldetail 
    INNER JOIN pricehistory ON pricehistory.PHID = selldetail.pricehistory_id
    INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
    INNER JOIN products ON products.PDID = pricehistory.ProductID
    WHERE SDID = ".$sell_id.";";

$dbObj = new DBTransactions();
$dbData = $dbObj->getData($sql);

echo json_encode($dbData, JSON_FORCE_OBJECT);