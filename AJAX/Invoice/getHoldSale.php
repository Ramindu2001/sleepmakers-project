<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$sale_header_id = $_GET['sale_header_id'];

$sql = "SELECT * FROM sellheader WHERE SHID = ".$sale_header_id.";";

$dbObj = new DBTransactions();
$headerData = $dbObj->getData($sql);

echo json_encode($headerData, JSON_FORCE_OBJECT);
