<?php 
include "../../Includes/config.php";
include "../../Model/supplier_return_class.php";

$prod_qty = $_POST['prod_qty'];
$purchase_price =$_POST['purchase_price'];
$variation_id = $_POST['variation_id'];
$batch_id = $_POST['BatchID'];
$Inventory_id = $_POST['InventoryID'];
$product_id = $_POST['product_id'];
$srn_detail_id = $_POST['srn_detail_id'];

$total_purchase = floatval($purchase_price) * floatval($prod_qty);

$srnObj = new SupplierReturn();
$srnObj->editReturnDetail($prod_qty,$purchase_price, $total_purchase, $variation_id, $product_id, $srn_detail_id,$batch_id);
