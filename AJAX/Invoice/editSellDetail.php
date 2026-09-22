<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/invoice_class.php";

$dbObj = new DBTransactions();

$selldetail_id = $_GET['selldetail_id'];
$item_qty = floatval($_GET['item_qty']);
//$discount_amount = floatval($_GET['discount_amount']);
$discount_amount = 0;
$sell_amount = floatval($_GET['sell_amount']);

//get 
$sql = "SELECT * FROM `selldetail` WHERE SDID = ".$selldetail_id.";";
$sellData = $dbObj->getData($sql);
$sold_price = floatval($sellData[0]['soldAmount']);
$sold_qty = floatval($sellData[0]['sellQty']);
$UnitSellAmount = floatval($sellData[0]['unitSellAmount']);

$percent_discount = $_GET['percent_discount'];
$direct_discount = $_GET['direct_discount'];

$percent_discount = $percent_discount == "" ? "0": $_GET['percent_discount'];
$direct_discount = $direct_discount == "" ? "0": $_GET['direct_discount'];


//calculation
$unit_price = $sold_price / $sold_qty;
$new_sell_amount = $item_qty * $UnitSellAmount;

if($percent_discount != 0)
{
    $discount_amount = ($UnitSellAmount * ($percent_discount/100) * $item_qty);
}

if($direct_discount != 0)
{
    $discount_amount = ($direct_discount * $item_qty);
}

$sold_amount = $new_sell_amount - $discount_amount;


$invObj = new Invoice();

$invObj->editSaleDetail($item_qty, $new_sell_amount, $direct_discount, $percent_discount, $discount_amount, $sold_amount, $selldetail_id);

echo "updated successfully... ";