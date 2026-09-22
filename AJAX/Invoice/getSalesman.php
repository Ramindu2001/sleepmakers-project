<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$sal_id = $_GET['sal_id'];

$sql = "SELECT * FROM salesmans WHERE SLID = ".$sal_id.";";

$dbObj = new DBTransactions();
$dbData = $dbObj->getData($sql);

echo json_encode($dbData);