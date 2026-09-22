<?php
require '../vendor/dompdf/vendor/autoload.php';
require_once '../Includes/includes.php';
include "../vendor/dompdf/dompdf/src/Dompdf.php";

use dompdf\dompdf;

$id = $_GET['id']; // Get customer ID from the URL
$dbObj = new DBTransactions();
$sql2 = "SELECT * FROM `shop` WHERE SHID = ". $_SESSION["shop_id"] .";";
$shopData = $dbObj->getData($sql2);

$credit = new credit_customer(); // Replace with your actual class
$details = $credit->credit_customer_details($id);
$customers = $credit->getOneCustomer($id);
$CustName = $customers[0]["CustName"];
$totalCredit = 0;
$totalSettled = 0;
$amountDue = 0;
$domain = $_SERVER['SERVER_NAME'];
$logo = $shopData[0]["ReceiptLogo"];
$currentDate = date("Y-m-d");

$html = "<style>
    body { font-family: Arial, sans-serif; }
    img { margin-bottom: 20px; }
    .header { display: flex; justify-content: space-between; align-items: center; }
    .header h2 { margin: 0; }
    .header .date { font-size: 14px; text-align: right; }
    .content { text-align: center; font-family: Fantasy; font-size: 15px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
    th, td { text-align: center; padding: 10px; }
    th { background-color: #f2f2f2; font-weight: bold; }
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
            <th>Invoice No</th>
            <th>Invoice Date</th>
            <th>Credit Amount</th>
            <th>Settled Amount</th>
            <th>Outstanding Amount</th>
            <th>Payment Methods</th>
        </tr>
    </thead>
    <tbody>";

$i = 1;
$count = count($details);
$overpayment = 0; // This will track the overpaid amount

if ($count > 0) {
    foreach ($details as $row) {
        $pending = $row["total_credit"] - $row["total_debit"];
        
        if ($pending > 0) { 
            $totalCredit += $row["total_credit"];
            $totalSettled += $row["total_debit"];
            $invoiceID = $row["invoice_header_id"];

            // Apply overpayment if available
            if ($overpayment > 0) {
                if ($overpayment >= $pending) {
                    // Apply full overpayment to this invoice
                    $pending = 0;
                    $overpayment -= $row["total_credit"];
                } else {
                    
                    $pending -= $overpayment;
                    $overpayment = 0;
                }
            }

            if ($pending == 0) {
                continue; 
            }

            $sql3 = "SELECT cc.pay_m_id, cc.DebitAmount, p.PaymethodName, cc.EffectiveDate 
                     FROM `creditcustomer` cc 
                     LEFT JOIN paymethod p ON p.PMID = cc.pay_m_id
                     WHERE cc.invoice_header_id = $invoiceID AND cc.DebitAmount > 0";
            $creditData = $dbObj->getData($sql3);
            
            $add = "";
            foreach ($creditData as $key) {
                $add .= "<p>{$key["PaymethodName"]} - \${$key["DebitAmount"]} - {$key["EffectiveDate"]}</p>";
            }

            $sql4 = "SELECT EffectiveDate FROM `invoiceheader` WHERE IHID = '$invoiceID'";
            $invoiceData = $dbObj->getData($sql4);
            
            $html .= "<tr>
                <td>$i</td>
                <td>{$row["invoice"]}</td>
                <td>{$invoiceData[0]["EffectiveDate"]}</td>
                <td>" . number_format($row["total_credit"], 2) . "</td>
                <td>" . number_format($row["total_debit"], 2) . "</td>
                <td>" . number_format($pending, 2) . "</td>
                <td>$add</td>
            </tr>";
            
            $amountDue += $pending;
            $i++;
        } else {
            // If overpayment exists, apply it to the next invoice
            $overpayment += $row["total_debit"] - $row["total_credit"];
        }
    }
} else {
    $html .= "<tr>
        <td colspan='7' class='text-danger' style='color:red; font-weight:700;'>No Records of Payment</td>
    </tr>";
}

$html .= "</tbody>
<tfoot>
<tr>
    <td colspan='7'><hr style='border: 2px solid black; margin: 5px 0;'></td>
</tr>
<tr>
    <td style='text-align: center; font-weight: bold;'>Total</td>
    <td>-</td>
    <td>-</td>
    <td style='text-align: center; font-weight: bold;'>" . number_format($totalCredit, 2) . "</td>
    <td style='text-align: center; font-weight: bold;'>" . number_format($totalSettled, 2) . "</td>
    <td style='text-align: center; font-weight: bold;'>" . number_format($amountDue, 2) . "</td>
    <td>-</td>
</tr>
</tfoot>
</table>";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->set_option('isRemoteEnabled', true);
$dompdf->setPaper('A4', 'landscape');
$date = date("Y-m-d");
$dompdf->render();
$dompdf->stream($CustName . ' - Customer_Due_Details_' . $date . '.pdf', ['Attachment' => 1]);
?>
