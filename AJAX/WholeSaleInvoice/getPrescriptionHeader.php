<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$dbObj = new DBTransactions();

$pres_header_id = $_GET['pres_header_id'];

$sql = "SELECT * FROM `prescriptionheader` WHERE PRHID = ".$pres_header_id.";";

$presData = $dbObj->getData($sql);

echo json_encode($presData);