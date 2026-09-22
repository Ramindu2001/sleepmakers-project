<?php
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
$shop_id = $_SESSION['shop_id'];
$maincat = $_GET['maincat'];


$sql = "SELECT * FROM `subcategories` WHERE categories_CTID = '$maincat';";
$dbObj = new DBTransactions();
$subcatData = $dbObj->getData($sql);

echo json_encode($subcatData);
