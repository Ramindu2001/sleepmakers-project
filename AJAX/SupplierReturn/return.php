<?php 
include "../../Includes/config.php";
include "../../Model/supplier_return_class.php";

//get data
$inventory_id = $_POST['inventory_id'];
$return_qty = $_POST['return_qty'];
$variation_id = $_POST['variation_id'];
$purchase_price = $_POST['purchase_price'];
$batchId = $_POST['batchId'];
$total_amount = $_POST['total_amount'];
$srn_header_id = $_POST['header_id'];

//hold Stat
$srn_stat = 0;

$srnObj = new SupplierReturn();
$status=$srnObj->check_srn_status($srn_header_id);
$status=$status[0]["ReturnStat"];
if($status== 0 || $status== 1)
{   
    $query=$srnObj->setReturnDetail($return_qty,$purchase_price,$inventory_id,$variation_id,$status,$total_amount,$srn_header_id,$batchId);    
    if($query==1)
    {
        echo 1;
    }
    else
    {
        echo 0;
    }
}
else
{
    ?><script> alert("SRN Already Verified"); window.location.href ="../Public/supplier_return.php"; </script> <?php
}

