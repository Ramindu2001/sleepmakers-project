<?php
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";

// $shop_id = $_SESSION['shop_id'];
$rack_id = $_GET['rack_id'];

$sql = "SELECT * FROM `rack` WHERE RKID = ".$rack_id.";";

$dbObj = new DBTransactions();
$rackData = $dbObj->getData($sql);

echo json_encode($rackData);