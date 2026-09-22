<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$upload_id = $_GET['upload_id'];

$sql = "SELECT * FROM `temp_grnupload` WHERE upload_id = ".$upload_id.";";

$dbObj = new DBTransactions();
$dbData = $dbObj->getData($sql);

echo json_encode($dbData);