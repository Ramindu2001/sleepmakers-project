<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$shop_id = $_SESSION['shop_id'];
if(isset($_POST["return_id"]))
{
    $return_id=$_POST["return_id"];
    $sql = "SELECT * FROM `retrun_invoice_header` WHERE RIHID ='$return_id' AND return_header_stat=0 AND shopID=".$shop_id.";";
    $dbObj = new DBTransactions();
    $itemData = $dbObj->getData($sql);
    if(!empty($itemData))
    {
        $itemResult = array();
        foreach($itemData as $row)
        {
            $data['id'] = $row['RIHID'];
            $data['amount'] = $row['return_amount'];

            array_push($itemResult, $data);
        }//foreach

    }//has items
    echo json_encode($itemResult);
}