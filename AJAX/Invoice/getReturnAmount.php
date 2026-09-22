<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$shop_id = $_SESSION['shop_id'];
$return_code = $_GET['return_code'];

$sql = "SELECT * FROM retrun_invoice_header WHERE return_no = '".$return_code."' AND shopID=".$shop_id." AND return_header_stat != 1;";

$dbObj = new DBTransactions();

$returnData = $dbObj->getData($sql);

$return_header_id = 0;
$return_amount = 0;
if(!empty($returnData))
{
    $return_header_id = $returnData[0]['RIHID'];
    $sql_1 = "SELECT * FROM multipay WHERE returnheader_id = ".$return_header_id.";";

    $multiData = $dbObj->getData($sql_1);
    if(!empty($multiData))
    {
        //return id is already in another invoice
        $return_stat = 1;
        $return_header_id = 0;
        $return_amount = 0;
    }//retun header is already used
    else
    {
        //return id is available
        $return_stat = 2;
        $return_header_id = $returnData[0]['RIHID'];
        $return_amount = $returnData[0]['return_gross_amount'];
    }
}//return  
else
{
    //cant find return header id
    $return_stat = 0;
}//no return item found

$arrReturn = array(
    "return_stat" => $return_stat,
    "return_header_id" => $return_header_id,
    "return_amount" => $return_amount,
);

echo json_encode($arrReturn);