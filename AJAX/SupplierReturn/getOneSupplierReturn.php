<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$srn_detail_id = $_GET['srn_detail_id'];

$sql = "SELECT * FROM supplierreturndetails 
INNER JOIN products ON products.PDID = supplierreturndetails.ProductID
LEFT JOIN variations ON variations.VRID = supplierreturndetails.VariationID
WHERE SRDID = ".$srn_detail_id."";

$dbObj = new DBTransactions();
$srnData = $dbObj->getData($sql);

echo json_encode($srnData, JSON_FORCE_OBJECT);