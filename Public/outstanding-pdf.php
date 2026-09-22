<?php
require '../vendor/dompdf/vendor/autoload.php';
require_once '../Includes/includes.php';
include "../vendor/dompdf/dompdf/src/Dompdf.php";

use Dompdf\Dompdf;
$dbObj = new DBTransactions();
$customer_ID=$_GET["cus_id"];
$sql = "SELECT c.CustName, SUM(cc.CreditAmount) AS TOTALCredit, SUM(cc.DebitAmount) AS TOTALDebit FROM creditcustomer cc
                            INNER JOIN customers c ON c.CTID=cc.Customers_CTID
                            WHERE cc.Customers_CTID='$customer_ID' AND cc.CreditStat='1' GROUP BY cc.Customers_CTID;";

$invData = $dbObj->getData($sql);
$totalOutstanding = 0;
$totalOutstanding = 0;
$html = '
    <h2 style="text-align: center;">Customer Outstanding Report</h2>
    <table style=" border-collapse: collapse; width: 100%;">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th style="; padding: 8px;">Customer Name</th>
                <th style="b padding: 8px; text-align: right;">Outstanding Balance</th>
            </tr>
        </thead>
        <tbody>';

if (isset($invData) && is_array($invData) && count($invData) > 0) {
    foreach ($invData as $row) {

        $outstanding = $row['TOTALCredit'] - $row['TOTALDebit'];
        $totalOutstanding += $outstanding;

        $html .= '<tr>
                    <td style="padding: 8px; text-align:center">' . htmlspecialchars($row['CustName']) . '</td>
                    <td style="padding: 8px; text-align: right;">' . 
                        number_format((float)$outstanding, 2, '.', ',') . 
                    '</td>
                  </tr>';
    }
} else {
    $html .= '<tr>
                <td colspan="2" style="text-align: center; padding: 8px;">No outstanding data found.</td>
              </tr>';
}

$html .= '</tbody>
        <tfoot>
            <tr>
                <td style="text-align: right; font-weight: bold; padding: 8px;">Total Outstanding:</td>
                <td style="text-align: right; font-weight: bold;  padding: 8px;">' . 
                    number_format((float)$totalOutstanding, 2, '.', ',') . 
                '</td>
            </tr>
        </tfoot>
    </table>';

// Initialize DOMPDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->set_option('isRemoteEnabled', true); 

$dompdf->setPaper('A4', 'landscape');

$dompdf->render();

$date = date("Y-m-d");

$dompdf->stream('Customer_outstanding_' . $date . '.pdf', ['Attachment' => 1]);
