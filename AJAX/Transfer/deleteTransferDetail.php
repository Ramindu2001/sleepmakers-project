<?php
session_start();
include "../../Includes/config.php";
include "../../Model/transfer_class.php";
include "../../Model/DB_Class.php";

$shop_id = isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 0;
$transfer_detail_id = (int)$_GET['transfer_detail_id'];

$dbObj = new DBTransactions();
$tranObj = new Transfer();

//lines can only be removed while the transfer is on hold or pending
$lineData = $dbObj->getMultipleData("SELECT TransferHeader_THID FROM transferdetails WHERE TDID = ?;", [$transfer_detail_id]);
if(empty($lineData) || $tranObj->getEditableTransferForShop($lineData[0]['TransferHeader_THID'], $shop_id) === null)
{
    echo "This transfer can no longer be changed.";
    exit;
}//not editable

$tranObj->deleteTransferDetail($transfer_detail_id);

echo 1;
