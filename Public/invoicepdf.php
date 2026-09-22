<?php
require '../vendor/dompdf/vendor/autoload.php';
require_once '../Includes/includes.php';
include "../vendor/dompdf/dompdf/src/Dompdf.php";

use Dompdf\Dompdf;
$dbObj = new DBTransactions();
$shop_id=$_SESSION["shop_id"];
$sql = "SELECT * FROM shopreceipts WHERE ReceiptStat = 1 AND shop_id='$shop_id' AND RecieptType=2";
$dbData = $dbObj->getData($sql);
$invoices = empty($dbData) ? "wholesaleInvoice.php" : $dbData[0]['ReceiptPath'];
$invoice_id = isset($_GET['invoice_id']) ? $_GET['invoice_id'] : 0;


$sql = "SELECT * FROM invoiceheader 
        INNER JOIN shop ON shop.SHID = invoiceheader.shop_SHID
        WHERE IHID = " . $invoice_id . ";";
$headData = $dbObj->getData($sql);

if (empty($headData)) {
    die("Invoice not found.");
}

$shop_name = $headData[0]['ShopName'];
$receipt_logo_name = $headData[0]['ReceiptLogo'];
$address_1 = $headData[0]['AddressLineOne'];
$address_2 = $headData[0]['AddressLineTwo'];
$contact = $headData[0]['PhoneNumber'];
$bill_no = $headData[0]['BillNo'];
$url = "../Receipts/$?invoice=$invoice_id&pdf=1";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  // Follow redirects if any
$html = curl_exec($ch);
curl_close($ch);
$mpdf = new Dompdf();
$mpdf->loadHtml($html);
$mpdf->set_option('isRemoteEnabled', true);

// Set paper size and orientation
$mpdf->setPaper('A4', 'landscape');
$date = date("Y-m-d");

// Render the PDF
$mpdf->render();

// Output the PDF to the browser
$mpdf->stream(' - Customer_Due_Details ' . $date . '.pdf', ['Attachment' => 1]);

