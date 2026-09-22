<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$cust_id = $_GET['cust_id'];

$sql = "SELECT * FROM customers WHERE CTID = ".$cust_id.";";

$data = array();
$dbObj = new DBTransactions();
$dbData = $dbObj->getData($sql);

$cust_name = $dbData[0]['CustName'];
$cust_contact = $dbData[0]['CustContact'];
$max_credit = $dbData[0]['MaxCreditAmount'];

$sql_1 = "SELECT * FROM creditcustomer WHERE Customers_CTID = ".$cust_id." AND CreditStat=1;";

$credit_total = 0;
$debit_total = 0;
$cust_credit = 0;

$creditData = $dbObj->getData($sql_1);
if(!empty($creditData))
{
    foreach($creditData as $row)
    {
        $credit_total += floatval($row['CreditAmount']);
        $debit_total += floatval($row['DebitAmount']);
    }//foreach
    
    $cust_credit = $credit_total - $debit_total;
}//has credit

$arr = array(
    "cust_name" => $cust_name,
    "cust_contact" => $cust_contact,
    "max_credit" => $max_credit,
    "cust_credit" => $cust_credit,
);

array_push($data, $arr);

echo json_encode($data);