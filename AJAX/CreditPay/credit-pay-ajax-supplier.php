<?php 
session_start();
include '../../Includes/config.php';
include '../../Model/credit_customer_class.php';
include '../../Model/user_class.php';
include '../../Model/DB_Class.php';
include '../../Includes/authcheck.php';
$credit_cus= new credit_customer();
if(isset($_POST["invoice_id"]))
{
    $invoice_id=$_POST["invoice_id"];
    $supplier_id=$_POST["supplierId"];
    $dbObj = new DBTransactions();
    $sqlinvoices = "SELECT *, SUM(cs.DebitAmount) AS totalDebit, SUM(cs.CreditAmount) AS totalCredit FROM suppliers s 
    INNER JOIN creditsupplier cs ON s.SPID=cs.Supplier_ID
    LEFT JOIN grnheader g ON g.GHID=cs.invoice_header_id
    WHERE cs.Supplier_ID='$supplier_id' AND cs.invoice_header_id='$invoice_id' AND cs.CreditStat=1 GROUP BY cs.invoice_header_id;";
    $due = $dbObj->getData($sqlinvoices);
    $due=$due[0]["totalDebit"] - $due[0]["totalCredit"];    
    echo $due;
}
?>