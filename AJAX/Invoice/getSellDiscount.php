<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$shop_id = $_SESSION['shop_id'];
$sell_header_id = $_GET['sell_header_id'];
// $tmp_bill_no = $_GET['tmp_bill_no'];

$sql = "SELECT * FROM `sellheader` WHERE SHID = ".$sell_header_id.";";

$dbObj = new DBTransactions();

$sellData = $dbObj->getData($sql);

if(!empty($sellData))
{
    echo json_encode($sellData, JSON_FORCE_OBJECT);
}//has data
else
{
    echo null;
}//else