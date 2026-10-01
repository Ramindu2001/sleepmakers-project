<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$trasnfer_detail_id = $_GET['detail_id'];

$dbObj = new DBTransactions();

//only what the quantity row needs - no price leaves the server
$sql = "SELECT TDID, products.Barcode, products.ItemName, transferdetails.products_PDID, transferdetails.InventoryID, TransferQty, ReceivedQty, inventory.CurrentQty FROM transferdetails
INNER JOIN products ON products.PDID = transferdetails.products_PDID
INNER JOIN inventory ON inventory.INID = transferdetails.InventoryID
LEFT JOIN variations ON variations.VRID = transferdetails.VariationID
WHERE TDID = ".$trasnfer_detail_id.";";

$transferData = $dbObj->getData($sql);

echo json_encode($transferData);