<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/invoice_class.php";

// ItemCount, GrossAmount, PercentDiscount, FixedDiscount, DiscountAmount, NetAmount
$item_count = $_GET['row_count'];
$sub_total = $_GET['sub_total'];
$percent_discount = is_numeric($_GET['percent_discount']) ? $_GET['percent_discount']: 0;
$fixed_discount = is_numeric($_GET['fixed_discount']) ? $_GET['fixed_discount'] : 0;
$sell_discount = $_GET['sell_discount'];
$net_total = $_GET['net_total'];
$sell_header_id = $_GET['sell_header_id'];
$line_discount = 0;

$invObj = new Invoice();

$invObj->editSaleHeader($item_count, $sub_total, $percent_discount, $fixed_discount, $line_discount, $sell_discount, $net_total, $sell_header_id);

echo "data - " . $sell_discount . "<br>";