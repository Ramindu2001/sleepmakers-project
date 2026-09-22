<?php 
session_start();
include '../../Includes/config.php';
include '../../Model/credit_customer_class.php';
include '../../Model/user_class.php';
include '../../Includes/authcheck.php';
$shop_SHID = $_SESSION['shop_id'];
$credit_cus= new credit_customer();
if(isset($_POST["invoice_id"]))
{
    $invoice_id=$_POST["invoice_id"];
    $cus_id=$_POST["cus_id"];
    $due=$credit_cus->credit_customer_details_Invoice($invoice_id,$cus_id,$shop_SHID);
    $due=$due[0]["total_credit"] - $due[0]["total_debit"];    
    echo number_format((float)$due, 2, '.', ',');
}
?>