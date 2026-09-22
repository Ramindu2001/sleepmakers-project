<?php
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/product_class.php";

$adjust_header_id = $_GET['adjust_header_id'];

$sql = "SELECT * FROM `adjustproddetails` 
INNER JOIN products ON products.PDID = adjustproddetails.products_PDID
WHERE AdjustHeader_AHID = ".$adjust_header_id.";";

$dbObj = new DBTransactions();
$adjustData = $dbObj->getData($sql);

echo json_encode($adjustData);
