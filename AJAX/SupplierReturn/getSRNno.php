<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/supplier_return_class.php";
include "../../Model/common_class.php";

$shop_id = $_SESSION['shop_id'];

$returnObj = new SupplierReturn();
$returnData = $returnObj->getReturnMax($shop_id);

$commObj = new Common();
$return_no = 0;

if (!empty($returnData)) 
{
    $max_value = floatval($returnData[0]['MaxReturn']);
    $max_value += 1;
    $return_no = $commObj->createCount("SR", $max_value);
} 
else {
    $return_no = $commObj->createCount("SR", 1);
}

echo $return_no;