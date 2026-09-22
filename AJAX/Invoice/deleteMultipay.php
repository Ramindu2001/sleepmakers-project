<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/invoice_class.php";

$multipay_id = $_GET['multipay_id'];

$invObj = new Invoice();

$invObj->deleteMultipay($multipay_id);

echo "payment deleted successfully.";