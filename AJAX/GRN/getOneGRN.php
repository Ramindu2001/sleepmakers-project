<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$grn_detail_id = $_GET['grn_detail_id'];

$sql = "SELECT * FROM grndetails 
INNER JOIN products ON products.PDID = grndetails.products_PDID
LEFT JOIN variations ON variations.VRID = grndetails.VariationID
WHERE GDID = ".$grn_detail_id.";";

$dbObj = new DBTransactions();
$grnData = $dbObj->getData($sql);

echo json_encode($grnData, JSON_FORCE_OBJECT);