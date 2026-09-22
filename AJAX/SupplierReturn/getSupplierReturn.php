<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$srn_header_id = $_GET['srn_header_id'];

$sql = "SELECT *,supplierreturndetails.ReturnAmount AS return_detail_amount FROM supplierreturndetails 
INNER JOIN supplierreturn ON supplierreturn.SRID = supplierreturndetails.supplierreturn_SRID
INNER JOIN products ON products.PDID = supplierreturndetails.ProductID
LEFT JOIN variations ON variations.VRID = supplierreturndetails.VariationID
WHERE supplierreturn.ReturnNo = '".$srn_header_id."';";

$dbObj = new DBTransactions();
$srnData = $dbObj->getData($sql);

echo json_encode($srnData);