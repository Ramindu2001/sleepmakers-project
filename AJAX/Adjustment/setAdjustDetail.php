<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/adjust_class.php";

$shop_id = $_SESSION['shop_id'];

//APID, AdjustProdQty, UnitPurchasePrice, UnitSellingPrice, MnfDate, ExpDate, InventoryID, VariationID, RackID, AdjustStat, AdjustProdAmount, products_PDID, AdjustHeader_AHID

$dbObj = new DBTransactions();

$adjust_header_id = $_GET['adjust_header_id'];
$adjust_qty = floatval($_GET['adjust_qty']);
$product_id = $_GET['product_id'];
$price_history_id = $_GET['price_history_id'];

$sql = "SELECT * FROM pricehistory 
        INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
        WHERE PHID = ".$price_history_id.";";

$itemData = $dbObj->getData($sql);

$batch_id = $itemData[0]['BatchID'];
$purchase_price = floatval($itemData[0]['PurchasePrice']);
$selling_price = $itemData[0]['SellingPrice'];
$mnf_date = $itemData[0]['MnfDate'];
$exp_date = $itemData[0]['ExpDate'];
$inventory_id = $itemData[0]['INID'];
$variation_id = $itemData[0]['VariationID'];
$adjust_stat = 0;
$adjust_amount = $adjust_qty * $purchase_price;

// $selling_price = $_GET['selling_price'];
// $mnf_date = $_GET['mnf_date'];
// $exp_date = $_GET['exp_date'];
// $inventory_id = $itemData[0]['INID'];
// $variation_id = $_GET['variation_id'];
// $adjust_stat = 0;
// $adjust_amount = $_GET['adjust_amount'];

$adjustObj = new Adjustment();
$adjustObj->setAdjustDetail($adjust_qty, $purchase_price, $selling_price, $mnf_date, $exp_date, $inventory_id, $variation_id, $adjust_stat, $adjust_amount, $product_id, $adjust_header_id, $batch_id);

echo "adjustment added succesfully...";