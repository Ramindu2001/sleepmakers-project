<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/invoice_class.php";
include "../../Model/shop_class.php";

$shop_id = $_SESSION['shop_id'];
$user_id = $_SESSION['user_id'];

$counter_id = floatval($_GET['counter_id']);
$pricehistory_id = $_GET['pricehistory_id'];
$sell_qty = floatval($_GET['sell_qty']);
$sell_price = floatval($_GET['sell_price']);
$tmp_bill_no = $_GET['tmp_bill_no'];
$manual_price = floatval($_GET['manual_price']);

$shopObj = new Shop();
$dbObj = new DBTransactions();
$invoiceObj = new Invoice();
$sellObj = new Invoice();

if($shopObj->hasFixedPrice($shop_id))
{
    $sell_price = floatval($_GET['sell_price']);
}
else
{
    $sell_price = $manual_price; 
}

$header_id = 0;
//get sell header id
$sql = "SELECT * FROM sellheader WHERE  tmp_bill_no='".$tmp_bill_no."' AND shop_id=".$shop_id.";";
$sellData = $dbObj->getData($sql);
if(empty($sellData))
{
    //create new header id
    // createNewSaleHeader($tmp_bill_no, $user_id, $shop_id);
    //SHID, BillNo, ItemCount, grossAmount, SellStat, user_id, shop_id, cashcounter_id
    $item_count = 0;
    $gross_amount = 0;
    $sell_stat = 0;

    //get max id
    $sql = "SELECT max(SHID) as max_id FROM sellheader;";
    $headerData = $dbObj->getData($sql);
    $header_id = floatval($headerData[0]['max_id']) + 1;

    $invoiceObj->setSaleHeader($tmp_bill_no, $item_count, $gross_amount, $sell_stat, $user_id, $shop_id, $counter_id);
    
}//no sell header
else
{   
    //get header id
    $header_id = $sellData[0]['SHID'];
}//have sell header
 
$sql = "SELECT * FROM pricehistory
INNER JOIN products ON products.PDID = pricehistory.ProductID
WHERE PHID = ".$pricehistory_id.";";

$prodData = $dbObj->getData($sql);

$unit_conversion_rate = floatval($prodData[0]['UnitConversion']);
$product_id = $prodData[0]['ProductID'];

//Added by Imila Madushan on 30/12/2024
$PercDisc = $prodData[0]['prodDiscount'];
$FlatDisc = $prodData[0]['prodFlatDiscount'];

$item_percent_discount = 0;
$item_wise_discount = 0;

if($PercDisc != 0)
{
    $item_percent_discount = ($sell_price * ($PercDisc / 100) * $sell_qty);
}


if($FlatDisc != 0)
{
    $item_wise_discount = ($FlatDisc * $sell_qty);
}



//SDID, sellQty, unitSellAmount, sellAmount, itemWiseDiscount, itemPercentDiscount, sellDiscount, soldAmount, sellheader_id, pricehistory_id


if($unit_conversion_rate != 0)
{
    $sell_amount = $sell_qty * ($sell_price / $unit_conversion_rate);
}
//unit sell price = sellprice with purchase unit / unit conversion rate

$sell_discount = 0;
$sold_amount = $sell_amount;

if($item_percent_discount > 0)
{
    $sell_discount = $item_percent_discount;
    $sold_amount = $sell_amount -  $sell_discount ;
}
else if($item_wise_discount > 0)
{
    $sell_discount = $item_wise_discount;
    $sold_amount = $sell_amount - $sell_discount ;
}

$sql_1 = "SELECT * FROM selldetail WHERE sellheader_id = ".$header_id.";";
$detailData = $dbObj->getData($sql_1);
$is_duplicate = false;
$new_sell_qty = 0;
$new_sell_amount = 0;
$new_sold_amount = 0;
$old_detail_id = 0;

foreach($detailData as $row)
{
    $old_price_id = $row['pricehistory_id'];
    $old_sell_qty = floatval($row['sellQty']);
    $old_sell_amount = floatval($row['sellAmount']);
    $old_sold_amount = floatval($row['soldAmount']);
    $old_detail_id = $row['SDID'];
    if($old_price_id == $pricehistory_id)
    {
        $is_duplicate = true;
        break;
    }//has duplicate item
    else
    {
        $is_duplicate = false;
    }//no duplicates
}//foreach

if($is_duplicate)
{
    //update 
    $new_sell_qty = $old_sell_qty + $sell_qty;
    $new_sell_amount = $old_sell_amount + $sell_amount;
    $new_sold_amount = $old_sold_amount + $sold_amount;

    $invoiceObj->editQtySaleDetail($new_sell_qty, $new_sell_amount, $new_sold_amount, $old_detail_id);
}
else
{
    //insert
    $invoiceObj->setSaleDetail($sell_qty, $sell_price, $sell_amount, $item_wise_discount, $item_percent_discount, $sell_discount, $sold_amount, $header_id, $product_id, $pricehistory_id);
}//else

//sending header id
echo $header_id;
