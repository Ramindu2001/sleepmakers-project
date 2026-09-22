<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/supplier_credit_class.php";
if(isset($_GET["type"]))
{
    $supplier_transaction_id = $_GET['supplier_transaction_id'];

    $supObj = new SupplierCredit();
    $insert=$supObj->deleteSupplierCredit2($supplier_transaction_id);
    if($insert==true)
        echo "transaction Deleted Successfully !";
    else
        echo $insert;
    
}
else
{
    $supplier_transaction_id = $_GET['supplier_transaction_id'];

    $supObj = new SupplierCredit();
    
    $supObj->deleteSupplierCredit($supplier_transaction_id);
    
    echo "transaction deleted successfully !";
}
