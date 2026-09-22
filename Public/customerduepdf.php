<?php
require '../vendor/dompdf/vendor/autoload.php';
require_once '../Includes/includes.php';
include "../vendor/dompdf/dompdf/src/Dompdf.php";

use dompdf\dompdf;

$id = $_GET['id']; // Get customer ID from the URL
$dbObj = new DBTransactions();
$sql2 = "SELECT * FROM shop WHERE SHID = ". $_SESSION["shop_id"] .";";
$shopData = $dbObj->getData($sql2);

$credit = new credit_customer(); // Replace with your actual class
$details = $credit->creditCustomerPDF($id,$_SESSION["shop_id"]);
$customers = $credit->getOneCustomer($id);
$CustName = $customers[0]["CustName"];
$totalCredit = 0;
$totalSettled = 0;
$amountDue = 0;
$domain = $_SERVER['SERVER_NAME'];
$logo = $shopData[0]["ReceiptLogo"];
$currentDate = date("Y-m-d");

$html = "<style>
    * { font-family: Arial, sans-serif; font-size: 12px !important;}
    img { margin-bottom: 20px; }
    .header { display: flex; justify-content: space-between; align-items: center; }
    .header h2 { margin: 0; }
    .header .date { font-size: 14px; text-align: right; }
    .content { text-align: center; font-family: Fantasy; font-size: 15px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
    th, td { text-align: center; padding: 5px; border:1px solid;}
    th { background-color: #f2f2f2; font-weight: bold; }
    tfoot{border-top:5px solid;}
</style>
<div class='header'>
    <img src='https://$domain/Assets/Images/shop_images/$logo' style='width:100px;'>
    <div class='date'>$currentDate</div>
    <div class='date'>$CustName</div>
</div>
<div class='content'>
    <h2>Customer Due Payment Details</h2>
</div>
<table>
    <thead>
        <tr>
            <th>SL</th>
            <th>Date</th>
            <th>Invoice No</th>
            <th>Description</th>
            <th>Payment Methods</th>
            <th>Debit (Rs.)</th>
            <th>Credit (Rs.)</th>
            <th>Balance</th>
        </tr>
    </thead>
    <tbody>";

$i = 1;
$count = count($details);
$overpayment = 0; // This will track the overpaid amount
$totalDebit=0;
$totalCredit=0;
if ($count > 0) {
    $balance=0;
    foreach ($details as $row) {
            $description =" ";
            $debitAmount=$row["total_debit"];
            $creditAmount=$row["total_credit"];
            $amount=0;
            
            if($row["paymentMode"]==1)
            {
                $description ="Credit Sale";

            }
            if($row["paymentMode"]==2)
            {
                $description ="Cheque Bounce";

            }
            if($row["paymentMode"]==3)
            {
                $description ="Credit Note";

            }
            if($row["paymentMode"]==4)
            {
                $description ="Settlement";

            }
            if($row["paymentMode"]==5)
            {
                $description ="Sales Return";

            }
            if($row["paymentMode"]==6)
            {
                $description ="Use Excess";

            }
            if($debitAmount !="0" && $debitAmount !="0.00")
            {
                $amount=$debitAmount;
                $balance=$balance+$amount;
            }
            else
            {
                
                $amount=$creditAmount;
                $balance=$balance-$amount;
            }
            $invoiceID=$row["IHID"];
            $CCID=$row["CCID"];

            $sql3 = "SELECT *
                     FROM cuscredittransactions cc 
                     LEFT JOIN paymethod p ON p.PMID = cc.paymethod_id
                     WHERE cc.CreditCustomer_CCID = '$CCID' ";
            $creditData = $dbObj->getData($sql3);
            
            $add = "";
            foreach ($creditData as $key) {

                if($debitAmount !="0" && $debitAmount !="0.00")
                {
                
                }
                else
                {
                    $add .= "<p>{$key["PaymethodName"]}</p>";

                }
            }

            $sql4 = "SELECT EffectiveDate FROM invoiceheader WHERE IHID = '$invoiceID'";
            $invoiceData = $dbObj->getData($sql4);
            $paymentMode=$row["paymentMode"];
            $html .= "<tr>
                <td>$i</td>
                <td>{$row["EffectiveDate"]}</td>
                <td>{$row["invoice"]}</td>
                <td>$description</td>
                <td>$add</td>";
                if($debitAmount !="0" && $debitAmount !="0.00")
                {
                    $html.="<td>" . number_format($debitAmount, 2) . "</td>
                            <td> </td>
                            <td>" . number_format($balance, 2) . "</td>
                        </tr>";
                }
                else
                {
                    $html.="<td> </td>
                            <td>" . number_format($creditAmount, 2) . "</td>
                            <td>" . number_format($balance, 2) . "</td>
                        </tr>";

                }
            
            $totalDebit=$totalDebit+$debitAmount;
            $totalCredit=$totalCredit+$creditAmount;
            // $amountDue += $pending;
            $i++;
    }
} else {
    $html .= "<tr>
        <td colspan='7' class='text-danger' style='color:red; font-weight:700; font: size 12px;'>No Records of Payment</td>
    </tr>";
}
$total=0;

if($totalDebit > $totalCredit)
{
    $total=$totalDebit;
    $bbf=$total-$totalCredit;
    $html.="<tr>
            <td colspan='5' style='text-align: right; font-weight: bold;font: size 12px;'>B/B/F</td>
            <td></td>
            <td>" . number_format($bbf, decimals: 2) . "</td>
            <td></td>
            </tr>";
}
else
{
    $total=$totalCredit;
    $bbf=$total-$totalDebit;
    $html.="<tr>
            <td colspan='5' style='text-align: right; font-weight: bold;font: size 12px;'>B/B/F</td>
            <td>" . number_format($bbf, decimals: 2) . "</td>
            <td></td>
            <td></td>
            </tr>";
}

$html .= "</tbody>
<tfoot>
<tr>
    <td style='text-align: center; font-weight: bold;'>Total</td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>";
        $html.="<td>" . number_format($total, decimals: 2) . "</td>
        <td>" . number_format($total, decimals: 2) . "</td>
        <td></td>
        </tr>";
        $html.="<tr>
        <td colspan='8' style='padding:15px;'></td>
        </tr>";
if($totalDebit > $totalCredit)
{
    $html.="<tr>
            <td colspan='5' style='text-align: right; font-weight: bold;'>B/C/F</td>
            <td>" . number_format($bbf, decimals: 2) . "</td>
            <td></td>
            <td></td>
            </tr>";
}
else
{
    $html.="<tr>
            <td colspan='5' style='text-align: right; font-weight: bold;'>B/C/F</td>
            <td></td>
            <td>" . number_format($bbf, decimals: 2) . "</td>
            <td></td>
            </tr>";

}
$html.="<tr>
<td colspan='8' style='padding:15px;'></td>
</tr>";
$html.="
</tfoot>
</table>";
// echo $html;
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->set_option('isRemoteEnabled', true);
$dompdf->setPaper('A4', 'portrait');
$date = date("Y-m-d");
$dompdf->render();
$dompdf->stream($CustName . ' - Customer_Due_Details_' . $date . '.pdf', ['Attachment'=>1]);
?>