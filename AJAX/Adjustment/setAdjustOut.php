<?php 
include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/adjust_class.php";

$adjust_header_id = $_GET['adjust_header_id'];
$product_id = $_GET['product_id'];
$price_history_id = $_GET['price_history_id'];
$adjust_out_qty = floatval($_GET['adjust_out_qty']);
$out_batch_id = $_GET['out_batch_id'];

//get item data

$sql = "SELECT * FROM pricehistory
INNER JOIN inventory ON pricehistory.Inventory_INID = inventory.INID
WHERE ProductID = ".$product_id." AND pricehistory.BatchID = '".$out_batch_id."';";

$dbObj = new DBTransactions();

$dbData = $dbObj->getData($sql);

//APID, AdjustProdQty, UnitPurchasePrice, UnitSellingPrice, MnfDate, ExpDate, InventoryID, VariationID, RackID, AdjustStat, AdjustProdAmount, products_PDID, AdjustHeader_AHID
$purchase_price = floatval($dbData[0]['PurchasePrice']);
$selling_price = $dbData[0]['SellingPrice'];
$mnf_date = $dbData[0]['MnfDate'];
$exp_date = $dbData[0]['ExpDate'];
$inventory_id = $dbData[0]['Inventory_INID'];
$variation_id = $dbData[0]['VariationID'];
$adjustment_stat = 0;

$adjust_amount = $adjust_out_qty * $purchase_price;

$adjustObj = new Adjustment();
$adjustObj->setAdjustDetail($adjust_out_qty, $purchase_price, $selling_price, $mnf_date, $exp_date, $inventory_id, $variation_id, $adjustment_stat, $adjust_amount, $product_id, $adjust_header_id, $out_batch_id);

