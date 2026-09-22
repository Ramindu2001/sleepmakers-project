<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$trasnfer_detail_id = $_GET['detail_id'];

$dbObj = new DBTransactions();

$sql = "SELECT * FROM transferdetails 
INNER JOIN products ON products.PDID = transferdetails.products_PDID
INNER JOIN inventory ON inventory.INID = transferdetails.InventoryID
LEFT JOIN variations ON variations.VRID = transferdetails.VariationID
WHERE TDID = ".$trasnfer_detail_id.";";

$transferData = $dbObj->getData($sql);

echo json_encode($transferData);