<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$dbObj = new DBTransactions();

$customer_id = $_GET['customer_id'];

$sql = "SELECT * FROM `prescriptionheader` WHERE customer_CTID = ".$customer_id." AND invoice_IHID = 0;";

$dbData = $dbObj->getData($sql);

echo json_encode($dbData, JSON_FORCE_OBJECT);