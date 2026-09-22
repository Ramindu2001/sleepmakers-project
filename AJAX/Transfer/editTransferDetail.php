<?php
session_start();
include "../../Includes/config.php";
include "../../Model/transfer_class.php";
include "../../Model/DB_Class.php";

$shop_id = isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 0;
$transfer_detail_id = (int)$_GET['transfer_detail_id'];

$dbObj = new DBTransactions();
$tranObj = new Transfer();

//the line keeps the product, batch, variation and source inventory row it was added with;
//only quantities, prices and dates change here, and only while the transfer is on hold or pending
$lineData = $dbObj->getMultipleData("SELECT * FROM transferdetails WHERE TDID = ?;", [$transfer_detail_id]);
if(empty($lineData) || $tranObj->getEditableTransferForShop($lineData[0]['TransferHeader_THID'], $shop_id) === null)
{
    echo "This transfer can no longer be changed.";
    exit;
}//not editable
$line = $lineData[0];

$transfer_qty = floatval($_GET['transfer_qty']);
$receive_qty = (isset($_GET['receive_qty']) && is_numeric($_GET['receive_qty'])) ? floatval($_GET['receive_qty']) : $transfer_qty;
if($transfer_qty <= 0 || $receive_qty < 0 || $receive_qty > $transfer_qty)
{
    echo "Please check the quantities. Receive qty can't be more than the transfer qty.";
    exit;
}//qty not valid

$purchase_price = $_GET['purchase_price'];
$selling_price = $_GET['selling_price'];
//dates change only from a page that shows the date fields (a shop that tracks expiry);
//otherwise the line keeps its dates. An emptied field means no date, never today's date
$mnf_date = isset($_GET['mnf_date']) ? $tranObj->cleanDate($_GET['mnf_date']) : $line['MnfDate'];
$exp_date = isset($_GET['exp_date']) ? $tranObj->cleanDate($_GET['exp_date']) : $line['ExpDate'];
$total_amount = round($receive_qty * floatval($purchase_price), 2);

//TransferQty, UnitPurchasePrice, UnitSellingPrice, TransferTotalAmount, products_PDID, TransferStat, TransferHeader_THID
$tranObj->editTransferDetail($transfer_qty, $receive_qty, $purchase_price, $selling_price, $mnf_date, $exp_date, $total_amount, $line['InventoryID'], $line['products_PDID'], $line['VariationID'], $line['RackID'], $line['Batch_ID'], $transfer_detail_id);

echo 1;
