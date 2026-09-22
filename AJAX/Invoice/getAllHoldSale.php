<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$user_id = $_SESSION['user_id'];
$shop_id = $_SESSION['shop_id'];

$txt_input = $_GET['txt_input'];

$sql = "SELECT * FROM sellheader
WHERE user_id=".$user_id." AND sellheader.shop_id=".$shop_id." AND tmp_bill_no LIKE '%".$txt_input."%';";

$dbObj = new DBTransactions();
$headerData = $dbObj->getData($sql);

echo json_encode($headerData);