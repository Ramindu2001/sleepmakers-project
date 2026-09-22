<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/GRN_class.php";
include "../../Model/common_class.php";

$shop_id = $_SESSION['shop_id'];

$grnObj = new GRN();
$grnCount = $grnObj->getGRNCount($shop_id);
$grn_count = intval($grnCount[0]['GRNCount']);
$grn_count += 1;

$commObj = new Common();
$grn_no = $commObj->createCount("GRN", $grn_count);

echo $grn_no;
