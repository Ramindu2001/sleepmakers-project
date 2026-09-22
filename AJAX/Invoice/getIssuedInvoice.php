<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";

$shop_id = $_SESSION['shop_id'];
$invoice_no = $_GET['invoice_no'];

$shopObj = new Shop();
$dbObj = new DBTransactions();

$sql = "SELECT * FROM invoiceheader WHERE shop_SHID = ".$shop_id." AND BillNo LIKE '%".$invoice_no."%' AND InvStat=1;";

$dbData = $dbObj->getData($sql);
if(!empty($dbData))
{
    $data = array();
    $rcpt = array();

    echo "<tr>";
    echo "<th>Date Time</th>";
    echo "<th>Invoice No</th>";
    echo "<th>Amount</th>";
    echo "<th>Action</th>";
    echo "</tr>";

    foreach($dbData as $row)
    {
        $invoice_id = $row['IHID'];
        $invoice_end_time = $row['InvEndTime'];
        $bill_no = $row['BillNo'];
        $net_amount = $row['NetAmount'];

        echo "<tr>";
        echo "<td>". $invoice_end_time ."</td>";
        echo "<td>". $bill_no ."</td>";
        echo "<td>". $net_amount ."</td>";

        echo "<td>";
        //get receipts
        $sql_1 = "SELECT * FROM shopreceipts WHERE shop_id = ".$shop_id.";";
        $receiptData = $dbObj->getData($sql_1);
        foreach($receiptData as $row_1)
        {
            $receipt_name = $row_1['receiptName'];
            $receipt_file = $row_1['ReceiptPath'];
            
            echo "<a href='../Receipts/".$receipt_file."?invoice_id=".$invoice_id."' class='btn btn-primary p-1 mr-1'>".$receipt_name."</a>";
        }
        echo "</td>";
        echo "</tr>";
    }//foreach 
}//has receipt