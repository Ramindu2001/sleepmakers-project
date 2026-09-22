<?php
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$unit_id = $_GET['unit_id'];

$sql = "SELECT * FROM `units` WHERE UNID = ".$unit_id.";";

$dbObj = new DBTransactions();
$unitData = $dbObj->getData($sql);

echo json_encode($unitData);
