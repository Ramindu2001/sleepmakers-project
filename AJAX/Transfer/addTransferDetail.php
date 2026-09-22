<?php
session_start(); 
include "../../Includes/config.php";
include "../../Model/transfer_class.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";

$shop_id = $_SESSION['shop_id'];
$shopObj = new Shop();
$dbObj = new DBTransactions();

$product_id = (int)$_GET['product_id'];
$batch_id = $_GET['batch_id'];
$header_id = (int)$_GET['header_id'];

$tranObj = new Transfer();

//items are added by the sending shop only (from its own stock), while the transfer is on hold or pending
$headerRow = $tranObj->getEditableTransferForShop($header_id, $shop_id);
if($headerRow === null || $headerRow['TransferFrom'] != $shop_id)
{
    echo 2;
    exit;
}//not allowed

//the same batch row the page showed: the oldest row of this batch that still has stock
$sql = "SELECT inventory.INID, inventory.CurrentQty FROM pricehistory
INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
WHERE ProductID = ? AND pricehistory.BatchID = ? AND inventory.shop_SHID = ? AND inventory.CurrentQty > 0
ORDER BY inventory.INID ASC LIMIT 1;";

$batchData = $dbObj->getMultipleData($sql, [$product_id, $batch_id, $shop_id]);

$transfer_qty = floatval($_GET['transfer_qty']);
if(empty($batchData) || $transfer_qty <= 0 || $transfer_qty > floatval($batchData[0]['CurrentQty']))
{
    echo 3;
    exit;
}//not enough stock
$inventory_id = $batchData[0]['INID'];

$received_qty = $transfer_qty; //assume transfered qty has received
$purchase_price = $_GET['purchase_price'];
$selling_price = $_GET['selling_price'];
//the batch's dates as shown on the page (only shops that track expiry have the date fields); no date = none
$mnf_date = $tranObj->cleanDate(isset($_GET['mnf_date']) ? $_GET['mnf_date'] : '');
$exp_date = $tranObj->cleanDate(isset($_GET['exp_date']) ? $_GET['exp_date'] : '');
$total_amount = round($received_qty * floatval($purchase_price), 2);

$variation_id = empty($_GET['variation_id']) ? 0 : $_GET['variation_id'];
$rack_id = 1;
$transfer_stat = 0;

//TransferQty, UnitPurchasePrice, UnitSellingPrice, MnfDate, ExpDate, TransferTotalAmount, products_PDID, VariationID, RackID, TransferStat, TransferHeader_THID

if($tranObj->checktransferID($inventory_id,$header_id)==true)
{
    $message=1;
    $tranObj->setTransferDetail($transfer_qty, $received_qty, $purchase_price, $selling_price, $mnf_date, $exp_date, $total_amount, $inventory_id, $product_id, $variation_id, $rack_id, $transfer_stat, $header_id, $batch_id);
    $return=1;
}
else
{
    $message=0;
}
echo $message;
