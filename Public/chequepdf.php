<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
 require '../vendor/dompdf/vendor/autoload.php';
 require_once '../Includes/includes.php';
 include "../vendor/dompdf/dompdf/src/Dompdf.php";
 use Dompdf\Dompdf;

$dompdf = new Dompdf();

$customer_id = $_GET['cus_id'];
$shop_id = $_SESSION['shop_id'];


$dbObj = new DBTransactions();

$sql2 = "SELECT * FROM `shop` WHERE SHID = $shop_id;";
$shopData = $dbObj->getData($sql2);
$domain = $_SERVER['SERVER_NAME'];
$logo = $shopData[0]["ReceiptLogo"];
$currentDate = date("Y-m-d");


$sqlCheques = "
    SELECT * FROM `custcheq` cc
    INNER JOIN custchqdetail ccd ON cc.CCQID = ccd.CCQID
    INNER JOIN customers c ON c.CTID = cc.cust_CTID
    INNER JOIN invoiceheader i ON i.IHID = cc.invoiceID
    WHERE cc.shop_SHID = '$shop_id' AND cc.cust_CTID = '$customer_id'
    ORDER BY cc.chq_no DESC";
$chequeData = $dbObj->getData($sqlCheques);
if (empty($chequeData)) {
    $chequeData = [];
    ?>
    <script>
        window.history.back();
    </script>
    <?php
}
$CustName=$chequeData[0]["CustName"];

$html = "
<style>
    body {
        font-family: Arial, sans-serif;
    }
    .header {
        text-align: center;
        justify-content: space-between;
        margin-bottom: 20px;
    }
    .header img {
        width: 100px;
    }
    .header h2 {
        margin: 0;
        font-size: 20px;
    }
    table {
        width: 100%;
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
    .total-row {
        font-weight: bold;
    }
</style>
<div class='header'>
    <h2>$CustName Cheque Details</h2>
</div>
<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Submitted Date</th>
            <th>Cheque Realize Date</th>
            <th>Cheque No</th>
            <th>Bank Cheque No</th>
            <th>Bank</th>
            <th>Cheque Amount</th>
            <th>Cheque Type</th>
        </tr>
    </thead>
    <tbody>";

$i = 1;
$total = 0;
foreach ($chequeData as $row) {
    $effectiveDate=$row['effectiveDate'];
    $chqDate=$row['chqDate'];
    $chq_no=$row['chq_no'];
    $chqNo=$row['chqNo'];
    $bank=$row['bank'];
    $total += $row['chqAmount'];
    $type = $row['type'] == 1 ? 
        "<span style='color: red; font-weight: bold;'>Issued Cheque</span>" : 
        "<span style='color: green; font-weight: bold;'>Received Cheque</span>";
    $html .= "
        <tr>
            <td>$i</td>
            <td>$effectiveDate</td>
            <td>$chqDate</td>
            <td>$chq_no</td>
            <td>$chqNo</td>
            <td>$bank</td>
            <td>" . number_format($row['chqAmount'], 2, '.', ',') . "</td>
            <td>$type</td>
        </tr>";
    $i++;
}
$html .= "
    </tbody>
        <tfoot>
            <tr class='total-row'>
                <td colspan='6'>Total</td>
                <td colspan='2'>".number_format($total, 2, '.', ',') . "</td>
            </tr>
        </tfoot>
</table>";

// echo $html;

$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'portrait');

$dompdf->render();

$date = date('Y-m-d');
$dompdf->stream($CustName . '-Customer_Cheque_Details-'.$date.'.pdf', ['Attachment' => true]);
?>
