<?php
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/product_class.php";

$shop_id = $_SESSION['shop_id'];
$salesman_id = $_GET['salesman_id'];

$sql = "SELECT * FROM `salesmans` WHERE SLID = ".$salesman_id.";";

$dbObj = new DBTransactions();
$salesmanData = $dbObj->getData($sql);

echo json_encode($salesmanData);