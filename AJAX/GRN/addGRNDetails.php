<?php 
include "../../Includes/config.php";
include "../../Model/GRN_class.php";

//get data (from whichever of POST / GET was actually sent - isset() is always true for both arrays)
if(!empty($_POST))
{
    $product_id = $_POST['product_id'];
    $variation_id = $_POST['variation_id'];
    $prod_qty = $_POST['prod_qty'];
    $purchase_price = $_POST['purchase_price'];
    $label_price = $_POST['label_price'];
    $selling_price = $_POST['selling_price'];
    $mnf_date = $_POST['mnf_date'];
    $exp_date = $_POST['exp_date'];
    $rack_id = $_POST['rack_id'];
    $grn_header_id = $_POST['grn_header_id'];
}
if(!empty($_GET))
{
    $product_id = $_GET['product_id'];
    $variation_id = $_GET['variation_id'];
    $prod_qty = $_GET['prod_qty'];
    $purchase_price = $_GET['purchase_price'];
    $label_price = $_GET['label_price'];
    $selling_price = $_GET['selling_price'];
    $mnf_date = $_GET['mnf_date'];
    $exp_date = $_GET['exp_date'];
    $rack_id = $_GET['rack_id'];
    $grn_header_id = $_GET['grn_header_id'];
    
}

$total_purchase = floatval($purchase_price) * floatval($prod_qty);
$total_selling = floatval($selling_price) * floatval($prod_qty);
//hold Stat
$grn_stat = 0;

$grnObj = new GRN();
$status=$grnObj->check_grn_status($grn_header_id);
$status=$status[0]["GRNStat"];
if($status== 0 || $status== 1)
{
    $query=$grnObj->setGRNDetails($prod_qty, $prod_qty, $purchase_price, $label_price, $selling_price, $total_purchase, $total_selling, $mnf_date, $exp_date, $grn_stat, $variation_id, $product_id, $grn_header_id, $rack_id);    
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
    ?><script> alert("GRN Already Verified"); window.location.href ="../Public/grn-header.php"; </script> <?php
}

