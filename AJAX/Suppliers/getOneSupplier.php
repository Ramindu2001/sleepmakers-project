<?php
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$supplier_id = $_GET['supplier_id'];

$sql = "SELECT * FROM `suppliers` WHERE SPID = ".$supplier_id.";";

$dbObj = new DBTransactions();
$supData = $dbObj->getData($sql);

echo json_encode($supData);
