<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/invoice_class.php";

$shop_id = $_SESSION['shop_id'];
$user_id = $_SESSION['user_id'];

$service_product_id = $_GET['service_product_id'];
$service_charge = $_GET['service_charge'];
$tmp_bill_no = $_GET['tmp_bill_no'];

$sql = "SELECT * FROM sellheader WHERE tmp_bill_no='".$tmp_bill_no."' AND shop_id=".$shop_id.";";
$dbObj = new DBTransactions();
$headerData = $dbObj->getData($sql);
$header_id = 0;
if(empty($headerData))
{
    createNewSaleHeader($tmp_bill_no, $user_id, $shop_id);

    $sql = "SELECT * FROM sellheader WHERE tmp_bill_no='".$tmp_bill_no."' AND shop_id=".$shop_id.";";
    $sellData = $dbObj->getData($sql);

    $header_id = $sellData[0]['SHID'];
}//create new header
else
{   
    $header_id = $headerData[0]['SHID'];
}//get header id

//SDID, sellQty, unitSellAmount, sellAmount, itemWiseDiscount, itemPercentDiscount, sellDiscount, soldAmount, sellheader_id, pricehistory_id

$sell_qty = 1;
$unit_sell_amount = $service_charge;
$sell_amount = $service_charge;
$item_percent_discount = 0;
$item_wise_discount = 0;
$sell_discount = 0;
$sold_amount = $service_charge;
$pricehistory_id = 0;

$sellObj = new Invoice();

$sellObj->setSaleDetail($sell_qty, $unit_sell_amount, $sell_amount, $item_wise_discount, $item_percent_discount, $sell_discount, $sold_amount, $header_id, $service_product_id, $pricehistory_id);

echo "sell detail saved successfully...";

//============================== Functions ==============================//
function createNewSaleHeader($tmp_bill_no, $user_id, $shop_id)
{
    $invoiceObj = new Invoice();

    //SHID, BillNo, ItemCount, grossAmount, SellStat, user_id, shop_id, cashcounter_id
    $item_count = 0;
    $gross_amount = 0;
    $sell_stat = 0;

    $sql = "SELECT * FROM cashcounter WHERE user_USID=".$user_id." AND shop_SHID=".$shop_id." AND CounterStat = 1;";

    $dbObj = new DBTransactions();
    $dbData = $dbObj->getData($sql);

    $counter_id = $dbData[0]['CCID'];

    $invoiceObj->setSaleHeader($tmp_bill_no, $item_count, $gross_amount, $sell_stat, $user_id, $shop_id, $counter_id);
}//create new sales header