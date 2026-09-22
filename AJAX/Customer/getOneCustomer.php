<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$customer_id = $_GET['customer_id'];

$sql = "SELECT * FROM customers WHERE CTID = ".$customer_id.";";

$dbObj = new DBTransactions();
$custData = $dbObj->getData($sql);

echo json_encode($custData);