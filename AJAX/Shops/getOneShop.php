<?php
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$edit_shop_id = $_GET['edit_shop_id'];

$sql = "SELECT * FROM `shop` WHERE SHID = ".$edit_shop_id.";";

$dbObj = new DBTransactions();
$shopData = $dbObj->getData($sql);

if(!empty($shopData))
{
    echo json_encode($shopData, JSON_FORCE_OBJECT);
}//has data
