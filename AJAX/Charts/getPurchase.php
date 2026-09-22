<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$shop_id = $_SESSION['shop_id'];
$user_id = $_SESSION['user_id'];

//get current date time
date_default_timezone_set("Asia/Colombo");
$effective_date = date("Y-m-d");

$new_date = date("Y-m-d", strtotime("-30 days"));

$sql = "SELECT EffectiveDate, TotalPurchasePrice FROM grnheader WHERE GRNStat = 2 AND shop_SHID = ".$shop_id." AND EffectiveDate BETWEEN '".$new_date."' AND '".$effective_date."';";

$dbObj = new DBTransactions();
$purchaseData = $dbObj->getData($sql);

echo json_encode($purchaseData);