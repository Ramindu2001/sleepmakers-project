<?php 
include "../../Includes/config.php";
include "../../Model/GRN_class.php";

$prod_qty = $_GET['prod_qty'];
$purchase_price = $_GET['purchase_price'];
$label_price = $_GET['label_price'];
$selling_price = $_GET['selling_price'];
$mnf_date = $_GET['mnf_date'];
$exp_date = $_GET['exp_date'];
$variation_id = $_GET['variation_id'];
$product_id = $_GET['product_id'];
$rack_id = $_GET['rack_id'];
$grn_detail_id = $_GET['grn_detail_id'];

$total_purchase = floatval($purchase_price) * floatval($prod_qty);
$total_selling = floatval($selling_price) * floatval($prod_qty);

$grnObj = new GRN();
$grnObj->editGRNDetails($prod_qty, $prod_qty, $purchase_price, $label_price, $selling_price, $total_purchase, $total_selling, $mnf_date, $exp_date, $variation_id, $product_id, $rack_id, $grn_detail_id);
