<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$sale_setting_id = $_GET['setting_id'];

$sql = "SELECT * FROM salesettings
INNER JOIN shop ON shop.SHID = salesettings.shop_id
WHERE SSID = ".$sale_setting_id.";";

$dbObj = new DBTransactions();
$settingData = $dbObj->getData($sql);

echo json_encode($settingData, JSON_FORCE_OBJECT);