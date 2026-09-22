<?php
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/product_class.php";

$dbObj = new DBTransactions();

$adjust_id = $_GET['adjust_id'];

$sql = "SELECT * FROM `adjustproddetails` WHERE APID = ".$adjust_id.";";

$prodData = $dbObj->getData($sql);

echo json_encode($prodData);
