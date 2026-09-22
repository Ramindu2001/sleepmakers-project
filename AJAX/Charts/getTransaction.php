<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";

$shop_id = $_SESSION['shop_id'];
$user_id = $_SESSION['user_id'];
//get shop pay methods
$sql = "SELECT PMID, PaymethodName  FROM shoppaymethod 
INNER JOIN paymethod ON paymethod.PMID = shoppaymethod.paymethod_PMID
WHERE shop_SHID = ".$shop_id.";";

$dbObj = new DBTransactions();
$shopObj = new Shop();

$paymethodData = $dbObj->getData($sql);

$data = array();
$arr = array();
$arrCredit = array();

//if shop has credit option
if($shopObj->hasCredit($shop_id))
{
    $arrCredit = array(
        "PMID" => 4,
        "PaymethodName" => "Credit",
    );
    //push Credit into array
    array_push($paymethodData, $arrCredit);
}//has credit

 //effective date
 date_default_timezone_set("Asia/Colombo");
 $effective_date = date("Y-m-d");

foreach($paymethodData as $row)
{
    $paymethod_id = $row['PMID'];
    $paymethod_name = $row['PaymethodName'];

    $sql_1 = "SELECT SUM(TransferAmount) AS transferTotal FROM transactions
    INNER JOIN invoiceheader ON invoiceheader.IHID = transactions.InvoiceHeader_IHID AND  invoiceheader.InvStat=1
    WHERE invoiceheader.shop_SHID = ".$shop_id." AND invoiceheader.EffectiveDate = '".$effective_date."' AND paymethod_PMID = ".$paymethod_id.";";

    $transData = $dbObj->getData($sql_1);

    $transfer_amount = empty($transData[0]['transferTotal']) ? 0 : $transData[0]['transferTotal'];

    $rand_number = dechex(rand(0, 15));
    $color_code = "#00" . $rand_number . $rand_number . "FF";

    array_push($data, array("paymethod"=>$paymethod_name, "transfer_amount"=>$transfer_amount, "paymethod_color"=>$color_code));

}//foreach

echo json_encode($data);