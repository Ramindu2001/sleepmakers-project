<?php
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";

// $shop_id = $_SESSION['shop_id'];
$section_id = $_GET['section_id'];

$sql = "SELECT * FROM `sections` WHERE SEID = ".$section_id.";";

$dbObj = new DBTransactions();
$sectionData = $dbObj->getData($sql);

echo json_encode($sectionData);