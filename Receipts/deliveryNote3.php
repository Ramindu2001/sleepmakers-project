<?php
session_start();
include "../Includes/config.php";
include '../Model/DB_Class.php';
include '../Model/shop_class.php';

$shop_id = $_SESSION['shop_id'];

$invoice_id =$_GET['invoice_id'];

try {
    $dbObj = new DBTransactions();

    $sql = "SELECT 
                invoiceheader.*, 
                customers.CustName, 
                customers.CustContact, 
                customers.CustAddress 
            FROM invoiceheader 
            INNER JOIN customers ON customers.CTID = invoiceheader.customers_CTID 
            WHERE IHID = $invoice_id";
    $invData = $dbObj->getData($sql);

    $cust_name = $invData[0]['CustName'];
    $cust_contact = $invData[0]['CustContact'];
    $cust_address = $invData[0]['CustAddress'];
    $remark = $invData[0]['remarks'];
    $date = $invData[0]['EffectiveDate'];
    $shopObj = new Shop();
    $shopData = $shopObj->getOneShop($shop_id);
    $shop_name = $shopData[0]['ShopName'];
    $shop_address = $shopData[0]['AddressLineOne'];
    $receipt_logo = $shopData[0]['ReceiptLogo'];

    $sql = "SELECT 
                invoicedetails.SellQty, 
                products.Barcode 
            FROM invoicedetails 
            INNER JOIN products ON products.PDID = invoicedetails.products_PDID 
            WHERE InvoiceHeader_IHID = '$invoice_id'";
    $detailData = $dbObj->getData($sql);

    if (empty($detailData)) {
        die("Invoice details not found.");
    }

} catch (Exception $e) {
    die("An error occurred: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Note</title>
    <style>
        body {
            margin: 0;
            padding: 0;
        }

        .delivery-note {
            background-color: #fff;
            padding: 20px;
            margin: 0 auto;
        }

        .top-section {
            display: flex;
            justify-content: space-between; /* Changed to space-between to give more space */
            margin-bottom: 20px;
            align-items: center;
        }

        .date {
            display: flex;
            gap: 10px;
            font-size: 1.2em; 
        }
        .page
        {
            width: 148mm;
            height: 100mm;
            border-bottom: 1px solid black;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .right, .left {
                display: flex;
                flex-direction: column;
            }
        .dotted-line {
            flex: 1;
            border-bottom: 1px dotted #000;
        }

        p {
            margin: 20px 0;
            font-weight: bold;
        }

        .items .dotted-line {
            border-bottom: 1px dotted #000;
            margin: 20px 0;
        }

        .footer {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }

        .footer-section {
            width: 40%;
        }

        .footer-section .dotted-line {
            border-bottom: 1px dotted #000;
            margin-left: 10px;
        }
        .logo-container {
            width: 120px; 
            display: flex;
            justify-content: center;
        }

        .logo-container img {
            width: 100%; 
            height: auto;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="delivery-note">
            <div class="top-section">
                <div class="logo-container">
                    <img src="../Assets/Images/shop_images/SR_000006.jpg" alt="Shop Logo">
                </div>
                <div style="text-align:center; flex-grow: 1; font: size 10px;">
                    <h1>Delivery Note</h1>
                </div>
                <div class="date">
                    <span>Date:</span>
                    <span><?php echo $date; ?></span>
                </div>
            </div>

            <div class="header">  
            <div class="right"> 
                    <span>From:</span>
                    <span><b><?php echo ($shop_name); ?></b></span> 
                    <span><b><?php echo ($shop_address); ?></b></span>

                </div>

                <div class="left">
                    <span>To:</span>
                    <span><b><?php echo($cust_name); ?></b></span>
                </div>
            </div>

            <p>Boxes:</p>
            <div class="items">
                    <div class="dotted-line"></div>
                    <div class="dotted-line"></div>
                    <div class="dotted-line"></div>
            </div>

            <div class="footer">
                <div class="footer-section" style="width:20% !important;">
                    <span>Send by:</span>
                </div>
                <div class="footer-section">
                    <img src="../Assets/Images/stamp.png" alt="" style="width:65%;">
                </div>
                <div class="footer-section">
                    <span>Received by:</span>
                    <span class="dotted-line"></span>
                </div>
            </div>
        </div>
    </div>
    <div class="page">
        <div class="delivery-note">
            <div class="top-section">
                <div class="logo-container">
                    <img src="../Assets/Images/shop_images/SR_000006.jpg" alt="Shop Logo">
                </div>
                <div style="text-align:center; flex-grow: 1;">
                    <h1><b>Delivery Note</b></h1>
                </div>
                <div class="date">
                    <span>Date:</span>
                    <span><?php echo $date; ?></span>
                </div>
            </div>

            
            <div class="header">  
            <div class="right"> 
                    <span>From:</span>
                    <span><b><?php echo ($shop_name); ?></b></span> 
                    <span><b><?php echo ($shop_address); ?></b></span>

                </div>

                <div class="left">
                    <span>To:</span>
                    <span><b><?php echo($cust_name); ?></b></span>
                </div>
            </div>

            <p>Boxes:</p>
            <div class="items">
                    <div class="dotted-line"></div>
                    <div class="dotted-line"></div>
                    <div class="dotted-line"></div>
            </div>

            
            <div class="footer">
                <div class="footer-section" style="width:20% !important;">
                    <span>Send by:</span>
                </div>
                <div class="footer-section">
                    <img src="../Assets/Images/stamp.png" alt="" style="width:65%;">
                </div>
                <div class="footer-section">
                    <span>Received by:</span>
                    <span class="dotted-line"></span>
                </div>
            </div>
        </div>
    </div>
    <script>
        window.print();
        window.print();
        setTimeout(function () {
            <?php if (isset($_GET["print"])) { ?>
                window.close();
            <?php } else { ?>
                window.location = "../Public/wholesale-invoice.php";
            <?php } ?>
        }, 300);
    </script>
</body>
</html>

