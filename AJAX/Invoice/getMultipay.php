<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$sale_header_id = $_GET['sale_header_id'];

$sql = "SELECT * FROM multipay 
INNER JOIN paymethod ON paymethod.PMID = multipay.paymethod_id
WHERE sellheader_id = ".$sale_header_id.";";

$dbObj = new DBTransactions();
$payData = $dbObj->getData($sql);

echo json_encode($payData);