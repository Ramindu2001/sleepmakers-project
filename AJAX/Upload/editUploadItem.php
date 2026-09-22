<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$dbObj = new DBTransactions();

$upload_id = $_GET['upload_id'];
$qty = $_GET['qty'];
$purchase_price = $_GET['purchase_price'];
$label_price = $_GET['label_price'];
$selling_price = $_GET['selling_price'];
$mnf_date = $_GET['mnf_date'];
$exp_date = $_GET['exp_date'];
$section_id = $_GET['section_id'];
$rack_id = $_GET['rack_id'];

$query = "UPDATE `temp_grnupload` SET 
qty='".$qty."',
purchaseprice='".$purchase_price."',
labelprice='".$label_price."',
sellingprice='".$selling_price."',
mnfdate='".$mnf_date."',
expdate='".$exp_date."',
section_id='".$section_id."',
rack_id='".$rack_id."'
WHERE upload_id=".$upload_id.";";

$dbObj->executeTransaction($query);

echo "item updated successfully... ";