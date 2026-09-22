<?php
require '../vendor/dompdf/vendor/autoload.php';
require_once '../Includes/includes.php';
include "../vendor/dompdf/dompdf/src/Dompdf.php";

use Dompdf\Dompdf;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid supplier ID.");
}
$supplier_id = intval($_GET['id']);

$dbObj = new DBTransactions();

$sql = "SELECT *, SUM(creditsupplier.DebitAmount) AS totalCredit,SUM(creditsupplier.CreditAmount) AS totalPaid,SUM(grnheader.SuppPayment) AS SuppPayment FROM `creditsupplier` 
    INNER JOIN grnheader ON grnheader.GHID = creditsupplier.invoice_header_id AND grnheader.GRNStat=2
    WHERE Supplier_ID = '$supplier_id' GROUP BY grnheader.GHID;";

$creditData = $dbObj->getData($sql);

$sql2 = "SELECT * FROM `shop` WHERE SHID = " . intval($_SESSION["shop_id"]) . ";";
$shopData = $dbObj->getData($sql2);

$supplier = new SupplierCredit();
$suppliers = $supplier->getSupplierNames($supplier_id);
$supplierName = $suppliers[0]["SupplierName"] ?? "Unknown Supplier";

$domain = $_SERVER['SERVER_NAME'];
$logo = $shopData[0]["ReceiptLogo"] ?? "default_logo.png";
$currentDate = date("Y-m-d");
$totalDue = 0;

$html = "
<style>
    body {
        font-family: Arial, sans-serif;
    }
    img {
        margin-bottom: 20px;
    }
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .header h2 {
        margin: 0;
    }
    .header .date {
        font-size: 14px;
        text-align: right;
    }
    .content {
    text-align: center;
    font-family:Fantasy; 
    font-size: 15px;
    }
           
    table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    font-size: 14px;
}

th, td {
    text-align: center;
    padding: 10px;
}
th {
    background-color: #f2f2f2;
    font-weight: bold;
}
</style>
<div class='header'>
    <img src='https://$domain/Assets/Images/shop_images/$logo' style='width:100px;'>
    <div class='date'>$currentDate</div>
    <div class='date'>$supplierName</div>
</div>
<div class='content'>
    <h2>Supplier Due Payment Details</h2>
</div>
<table>
    <thead>
        <tr>
            <th>SL</th>
            <th>Date</th>
            <th>Invoice No</th>
            <th>GRN Amount</th>
            <th>Credit Amount</th>
            <th>Settled Amount</th>
            <th>Total Due</th>
        </tr>
    </thead>
    <tbody>";

$i = 1;
foreach ($creditData as $row) {
    $GHID = $row["GHID"];
    
    $totalpaid = $row["totalPaid"] ?? 0;

    $creditAmount = floatval($row['totalCredit'] ?? 0);
    $balance = $creditAmount - floatval($totalpaid);
    $totalDue += $balance;

    $row_class = $balance > 0 ? "red" : ($balance == 0 ? "black" : "green");

    $html .= "
        <tr class='$row_class'>
            <td>{$i}</td>
            <td>{$row['EffectiveDate']}</td>
            <td>{$row['GRNHeaderNo']}</td>
            <td>" . number_format($row['TotalPurchasePrice'], 2) . "</td>
            <td>" . number_format($creditAmount, 2) . "</td>
            <td>" . number_format($totalpaid, 2) . "</td>
            <td>" . number_format($balance, 2) . "</td>
        </tr>";
    
    $i++;
}

$html .= "
    </tbody>
    <tfoot>
        <tr>
            <td colspan='6'><b>Total Due</b></td>
            <td><b>" . number_format($totalDue, 2) . "</b></td>
        </tr>
    </tfoot>
</table>";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->set_option('isRemoteEnabled', true);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("$supplierName - Supplier_Due_Details_$currentDate.pdf", ['Attachment' => 1]);
?>
