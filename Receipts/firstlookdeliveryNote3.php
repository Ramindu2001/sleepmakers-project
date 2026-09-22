<?php
include '../Includes/includes.php';

if(isset($_GET['invoice_id']))
{
    $invoice = $_GET['invoice_id'];
    $invoice_id = $_GET['invoice_id'];
}
else
{
    header("Location: ../Public/wholesale-invoice.php");
}
$iframe = isset($_GET['Iframe']);
$pdf = isset($_GET['pdf']);
$invoiceList = isset($_GET['invoiceList']);
$print = isset($_GET['print']);

// Include necessary JavaScript libraries
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
<?php if ($iframe): ?>
// Iframe-specific script
<?php elseif ($pdf): ?>
// Generate PDF using jsPDF
const {
    jsPDF
} = window.jspdf;
const doc = new jsPDF();
const content = $('#content').text();
doc.text(content, 10, 10);
doc.save('Invoice.pdf');
<?php else: ?>
<?php endif; ?>

setTimeout(() => {
    <?php if (isset($_SESSION['deliveryNote']) && $_SESSION['deliveryNote'] == 1): ?>
    <?php unset($_SESSION['deliveryNote']); ?>
    window.location = "./deliveryNote.php?invoice_id=<?= $invoice ?>";
    <?php elseif (isset($_SESSION['sales_returni'])): ?>
    <?php unset($_SESSION['sales_returni']); ?>
    window.location = "../Public/sales-return.php";
    <?php elseif ($invoiceList): ?>
    window.location = "../Public/invoice-list.php";
    <?php elseif ($pdf || $print): ?>
    window.close();
    <?php else: ?>
    window.location = "../Public/wholesale-invoice.php";
    <?php endif; ?>
}, <?= $pdf ? 5000 : 2000 ?>);
</script>

<?php
include '../Includes/authcheck.php';
$shop_id = $_SESSION['shop_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

// Initialize database object
$dbObj = new DBTransactions();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100..900&display=swap" rel="stylesheet">
    <style>
    body {
        font-family: 'Roboto', Arial, sans-serif;
        width: 800px;
        margin: 0 auto;
        padding: 6px;
        font-size: 12px;
    }

    h3,
    h4,
    h5 {
        margin: 0;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    th,
    td {
        padding: 3px;
        text-align: left;
    }

    footer {
        margin-top: 20px;
        text-align: right;
        font-size: 12px;
        color: #555;
    }

    .header-container {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 20px;
    }

    .logo {
        width: 100px;
        height: auto;
    }

    .invoice-details {
        text-align: right;
    }

    .bill-container {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .bill-section {
        width: 48%;
        font-size: 12px;
    }

    .bill-section strong {
        display: inline-block;
        border-bottom: 1px solid #000;
        padding-bottom: 2px;
        margin-bottom: 5px;
        width: 50%;
    }

    p {
        font-weight: 300;
        margin: 0;
    }
    .header-container {
    display: flex;
    justify-content: space-between; /* Align items to both left and right */
    align-items: center;
    margin-bottom: 20px;
}

    </style>
</head>

<body>
    <?php
$shopQuery = "SELECT * FROM `shop` WHERE SHID='$shop_id';";
$shopDetails = $dbObj->getData($shopQuery);

$shop = $shopDetails[0] ?? [];
$receiptLogo = $shop['ReceiptLogo'] ?? '';
$shopName = $shop['ShopName'] ?? '';
$address1 = $shop['AddressLineOne'] ?? '';
$address2 = $shop['AddressLineTwo'] ?? '';
$contact = $shop['PhoneNumber'] ?? '';
$email = $shop['emailAddress'] ?? '';

$invoiceQuery = "SELECT ih.*, c.CustName AS CustomerName, c.CustContact, c.CustAddress, 
                 u.UserName AS officer, sm.SalesmansName AS Salesman 
                 FROM `invoiceheader` ih
                 INNER JOIN customers c ON c.CTID = ih.customers_CTID
                 INNER JOIN user u ON u.USID = ih.user_USID
                 INNER JOIN salesmans sm ON sm.SLID = ih.Salesmans_SLID
                 WHERE ih.IHID='$invoice';";

$invoiceDetails = $dbObj->getData($invoiceQuery);

$invoice = $invoiceDetails[0] ?? [];
$billNo = $invoice['BillNo'] ?? '';
$effectiveDate = date('d-m-Y', strtotime($invoice['EffectiveDate'] ?? ''));
$salesman = $invoice['Salesman'] ?? '';
$grossAmount = $invoice['GrossAmount'] ?? 0.00;
$netAmount = $invoice['NetAmount'] ?? 0.00;

date_default_timezone_set("Asia/Colombo");
$printDate = date("Y-m-d");
$printTime = date("H:i:s");
?>

    <div class="header-container">
        <div class="logo">
            <img src="../Assets/Images/shop_images/<?= $receiptLogo ?>" alt="Logo" class="logo">
        </div>
        <div class="invoice-details">
            <p>Invoice No: <?= $billNo ?></p>
            <p>Invoice Date: <?= $effectiveDate ?></p>
        </div>
    </div>

    <div class="bill-container">
        <div class="bill-section">
            <strong>Bill From:</strong><br>
            <?= $shopName ?><br>
            <?= $address1 . ' ' . $address2 ?><br>
            <p><?= $contact ?></p>
            <p>Email: <?= $email ?></p>
        </div>
        <div class="bill-section" style="text-align: right;">
            <strong>Bill To:</strong><br>
            <?= $invoice['CustomerName'] ?? '' ?><br>
            <?= $invoice['CustAddress'] ?? '' ?><br>
            <?= $invoice['CustContact'] ?? '' ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description Note</th>
                <th>Quantity</th>
                <th>Rate</th>
                <th>Discount</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php
        $detailsQuery = "SELECT id.*, p.ItemName, p.Barcode, id.item_des AS Description 
                         FROM `invoicedetails` id 
                         INNER JOIN products p ON p.PDID = id.products_PDID
                         WHERE id.InvoiceHeader_IHID='$invoice_id';";
        $details = $dbObj->getData($detailsQuery);

        foreach ($details as $row) {
            $discount = $row['PercentDiscount'] ? $row['PercentDiscount'] . '%' : '-';
            echo "<tr>
                    <td>{$row['ItemName']}</td>
                    <td>{$row['SellQty']}</td>
                    <td>{$row['UnitPrice']}</td>
                    <td>{$discount}</td>
                    <td>{$row['SoldAmount']}</td>
                  </tr>";
        }
        ?>
        </tbody>
    </table>
</body>
        <script>
             window.print();
            setTimeout(function(){
                <?php 
                if(isset($_GET["print"]))
                {
                    ?>
                    window.close();
                    <?php
                }   
                else
                {
                    ?>
                    window.location="../Public/wholesale-invoice.php";
                    <?php
                } 
                ?>
                
            },300)
        </script>
</html>