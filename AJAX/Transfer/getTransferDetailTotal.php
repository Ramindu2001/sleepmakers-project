<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$transfer_header_id = $_GET['transfer_header_id'];

$dbObj = new DBTransactions();

$sql = "SELECT count(TDID) as rowCount, sum(TransferQty) as totalTransferQty, sum(ReceivedQty) as totalReceiveQty, sum(TransferTotalAmount) as totalTransfer FROM transferdetails WHERE TransferHeader_THID = ".$transfer_header_id.";";
$transData = $dbObj->getData($sql);

$row_count = empty($transData[0]['rowCount']) ? 0 : $transData[0]['rowCount'];
$transfer_qty = empty($transData[0]['totalTransferQty']) ? 0 : $transData[0]['totalTransferQty'];
$receive_qty = empty($transData[0]['totalReceiveQty']) ? 0 : $transData[0]['totalReceiveQty'];
$total_transfer = empty($transData[0]['totalTransfer']) ? 0 : $transData[0]['totalTransfer'];

$data = array(
    'row_count'=>$row_count,
    'transfer_qty'=>$transfer_qty,
    'receive_qty'=>$receive_qty,
    'transfer_total'=>$total_transfer,
);

echo json_encode($data);
