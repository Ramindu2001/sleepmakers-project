<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/invoice_class.php";

$shop_id = $_SESSION['shop_id'];
$user_id = $_SESSION['user_id'];

$paid_amount = $_GET['paid_amount'];
$paymethod_id = $_GET['paymethod_id'];
$pay_stat = 0; //initialize
$return_header_id = $_GET['return_header_id'];
$tmp_bill_no = $_GET['tmp_bill_no'];
$sell_header_id = $_GET['sell_header_id'];

$invObj = new Invoice();
$dbObj = new DBTransactions();

// $sql = "SELECT * FROM sellheader WHERE tmp_bill_no='".$tmp_bill_no."' AND shop_id=".$shop_id." AND user_id=".$user_id.";";
// $sellData = $dbObj->getData($sql);
// $sell_header_id = $sellData[0]['SHID'];

$sql = "SELECT sum(soldAmount) as sell_total FROM `selldetail` WHERE sellheader_id = ".$sell_header_id.";";
$sellData = $dbObj->getData($sql);
$sell_total = floatval($sellData[0]['sell_total']);

//get paid amount
$sql = "SELECT sum(paidAmount) as paid_amount FROM `multipay` WHERE sellheader_id = ".$sell_header_id.";";
$sellData = $dbObj->getData($sql);
$transfered_amount = floatval($sellData[0]['paid_amount']);

$pending_amount = $sell_total - $transfered_amount;

$pay_stat = 0;
if($pending_amount > 0)
{
    if($pending_amount > $paid_amount)
    {
        $pay_stat = 1;
        //add to multi pay
        $invObj->setMultipay($paid_amount, 0, $paymethod_id, $sell_header_id, $return_header_id);
    }//half pay
    else
    {
        $pay_stat = 1;
        //add to multi pay
        $invObj->setMultipay($pending_amount, 0, $paymethod_id, $sell_header_id, $return_header_id);
    }//full pay
}
else
{
    $pay_stat = 0;
}//else

$data = array('pay_stat'=> $pay_stat, 'pending_amount'=>$pending_amount);

//fetch data from multipay
// $sql = "SELECT * FROM multipay 
// INNER JOIN paymethod ON paymethod.PMID = multipay.paymethod_id
// WHERE sellheader_id = ".$sell_header_id.";";

// $dbObj = new DBTransactions();
// $multiData = $dbObj->getData($sql);

echo json_encode($data);