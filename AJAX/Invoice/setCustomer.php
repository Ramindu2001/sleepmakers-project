<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/Customer_Class.php";
include "../../Model/common_class.php";

$shop_id = $_SESSION['shop_id'];
$cust_name = $_GET['cust_name'];
$cust_contact = $_GET['cust_contact'];
$cust_address = $_GET['cust_address'];
$max_credit = $_GET['max_credit'];
$payment_term = $_GET['payment_term'];
$cust_stat = $_GET['cust_stat'];

$sql = "SELECT MAX(CTID) maxCustID FROM customers;";
$dbObj = new DBTransactions();
$custData = $dbObj->getData($sql);
$max_customer_id = floatval($custData[0]['maxCustID']) + 1;

$comObj = new Common();
$customer_no = $comObj->createCount("CU", $max_customer_id);

$custObj = new Customer();
$custObj->setCustomer($customer_no, $cust_name, $cust_address, $cust_contact, $max_credit, $payment_term, $cust_stat, $shop_id);

echo "Customer saved successfully...";