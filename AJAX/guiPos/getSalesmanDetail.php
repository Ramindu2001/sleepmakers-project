<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$saleaman_id = $_GET['saleaman_id'];

$sql = "SELECT * FROM salesmans WHERE SLID = ".$saleaman_id.";";

$data = array();
$dbObj = new DBTransactions();
$dbData = $dbObj->getData($sql);

$SLID = $dbData[0]['SLID'];
$SalesmanNo = $dbData[0]['SalesmanNo'];
$SalesmansName = $dbData[0]['SalesmansName'];
$SalesmansContact = $dbData[0]['SalesmansContact'];

$arr = array(
    "SLID" => $SLID,
    "SalesmansName" => $SalesmansName,
    "SalesmanNo" => $SalesmanNo,
    "SalesmansContact" => $SalesmansContact,
);

array_push($data, $arr);

echo json_encode($data);