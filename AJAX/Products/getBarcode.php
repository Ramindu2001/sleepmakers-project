<?php
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/product_class.php";

$barcode = $_GET['barcode'];

$sql = "SELECT * FROM products WHERE Barcode = '".$barcode."';";

$dbObj = new DBTransactions();
$prodData = $dbObj->getData($sql);

$has_barcode = empty($prodData) ? 0 : 1;

echo $has_barcode;
