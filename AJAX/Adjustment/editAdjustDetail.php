<?php 
include "../../Includes/config.php";
include "../../Model/adjust_class.php";

//fetch data
//APID, AdjustProdQty, UnitPurchasePrice, UnitSellingPrice, MnfDate, ExpDate, InventoryID, VariationID, RackID, AdjustStat, AdjustProdAmount, products_PDID, AdjustHeader_AHID

$adjust_qty = $_GET['adjust_qty'];
$purchase_price = $_GET['purchase_price'];
$selling_price = $_GET['selling_price'];
$mnf_date = $_GET['mnf_date'];
$exp_date = $_GET['exp_date'];
$inventory_id = $_GET['inventory_id'];
$variation_id = $_GET['variation_id'];
$rack_id = $_GET['rack_id'];
$adjust_stat = 0;
$adjust_amount = $_GET['adjust_amount'];
$product_id = $_GET['product_id'];
$adjust_header_id = $_GET['adjust_header_id'];
$adjust_detail_id = $_GET['adjust_detail_id'];

$adjustObj = new Adjustment();
$adjustObj->editAdjustDetail($adjust_qty, $purchase_price, $selling_price, $mnf_date, $exp_date, $inventory_id, $variation_id, $rack_id, $adjust_amount, $product_id, $adjust_detail_id);
