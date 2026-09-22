<?php
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$subcat_id = $_GET['subcat_id'];

$sql = "SELECT * FROM subcategories WHERE SCID = ".$subcat_id.";";

$dbObj = new DBTransactions();
$catData = $dbObj->getData($sql);

echo json_encode($catData);
