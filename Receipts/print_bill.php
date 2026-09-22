<?php  
include '../Includes/includes.php';
$invoice = $_GET["invoice"];
?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
 <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
<?php 
if(isset($_GET["Iframe"]))
{
    ?>
    
    <?php
}
elseif(isset($_GET["pdf"]))
{
    ?>
    // Create a new jsPDF instance
    const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        // Get content from the HTML element
        var content = $('#content').text();

        // Add text to the PDF
        doc.text(content, 10, 10);

        // Save the generated PDF
        doc.save('Invoice.pdf');
    <?php
}
else
{
    ?>
     window.print();
    <?php
}
?>
       
        setTimeout(function(){
            <?php 
            if(isset($_SESSION["deliveryNote"]))    
            {
                if($_SESSION["deliveryNote"]==1)
                {
                    unset($_SESSION["deliveryNote"]);
                    ?>
                    window.location="./deliveryNote.php?invoice_id=<?=$invoice?>";
                    <?php
                }
                else
                {
                    unset($_SESSION["deliveryNote"]);
                    ?>
                    window.location="../Public/wholesale-invoice.php";
                    <?php
                }
                unset($_SESSION["deliveryNote"]);
            }
            elseif(isset($_SESSION["sales_returni"]))
            {
                unset($_SESSION["sales_returni"]);
                ?>
                window.location="../Public/sales-return.php";
                <?php
            }
            elseif(isset($_GET["invoiceList"]))
            {
                ?>
                //window.location="../Public/invoice-list.php";
window.history.back()
                <?php
            }
            elseif(isset($_GET["Iframe"]))
            {
                ?>
                <?php
            }
            elseif(isset($_GET["pdf"]))
            {
                ?>
                window.close();
                <?php
            }
            elseif(isset($_GET["print"]))
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
        },<?php if(isset($_GET["pdf"])){?>5000<?php } else { ?> 2000 <?php }
        ?>
        );
    </script>
<?php 
include '../Includes/authcheck.php';
$invoice_id = 0;
$shop_id = 0;
$user_id = $_SESSION['user_id'];
$invoice = $_GET["invoice"];

if(isset($_SESSION['shop_id']))
{
    $shop_id = $_SESSION['shop_id'];
}//assign shop id


$dbObj = new DBTransactions();
?>
</script>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100..900&display=swap" rel="stylesheet">
    <style>
    body {
        font-family: 'Roboto', Arial, sans-serif;
        width: 800px;
        margin: 0 auto;
        padding: 20px;
        /* border: 1px solid #ccc; */
    }
    *
    {
        
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

    .header {
        text-align: left;
        margin-bottom: 20px;
        padding-left: 15px;
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
    p 
    {
        font-weight: 300;
        margin: 0;
        font-size: 12px;
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
    </style>
</head>
<?php 
        //get shop details
        $sql = "SELECT * FROM `shop` WHERE SHID='$shop_id';";
        $headData  = $dbObj->getData($sql);
        $receipt_logo_name = $headData[0]['ReceiptLogo'];
        $ShopLogo = $headData[0]['ShopLogo'];
        $shop_name = $headData[0]['ShopName'];
        $address_1 = $headData[0]['AddressLineOne'];
        $address_2 = $headData[0]['AddressLineTwo'];
        $contact = $headData[0]['PhoneNumber'];
        $email = $headData[0]['emailAddress'];
    ?>
    <?php 
                //Invoice data
                $sql_1 = "SELECT ih.*, c.CustName As CustomerName, c.CustContact AS CustContact, c.CustAddress AS CustAddress, u.UserName AS officer, sm.SalesmansName AS Salesmen FROM `invoiceheader` ih
                    INNER JOIN customers c ON c.CTID=ih.customers_CTID
                    INNER JOIN user u on u.USID=ih.user_USID
                    INNER JOIN salesmans sm ON sm.SLID=ih.Salesmans_SLID
                    WHERE ih.IHID= '$invoice';";

                $invoiceData = $dbObj->getData($sql_1);
                $cus_name = $invoiceData[0]['CustomerName'];
                $cus_contact = $invoiceData[0]['CustContact'];
                $CustAddress = $invoiceData[0]['CustAddress'];
                $officer = $invoiceData[0]['officer'];
                $Salesman = $invoiceData[0]['Salesmen'];
                $BillNo = $invoiceData[0]['BillNo'];
                $date = $invoiceData[0]['EffectiveDate'];
                $GrossAmount = $invoiceData[0]['GrossAmount'];
                $remarks = $invoiceData[0]['remarks'];
                $lineDiscount = $invoiceData[0]['lineDiscount'];
                $FixedDiscount = $invoiceData[0]['FixedDiscount'];
                $DiscountAmount = $invoiceData[0]['DiscountAmount'];
                $deliveryCharge = $invoiceData[0]['deliveryCharge'];
                $otherCharge = $invoiceData[0]['otherCharge'];
                $NetAmount = $invoiceData[0]['NetAmount'];
                $print_count = $invoiceData[0]['print_count'];
                $excessAmount = $invoiceData[0]['excessAmount'];
                $returnAmount = $invoiceData[0]['returnAmount'];
                $EffectiveDate = $invoiceData[0]['EffectiveDate'];

                //print date time
                date_default_timezone_set("Asia/Colombo");
                $print_date = date("Y-m-d");
                $EffectiveDate = date("d-m-Y", strtotime($EffectiveDate));
                $print_time = date("H:i:s");
                ?>
<body>
    <div class="header-container">
        <div class="logo" style="width:20%;">
            <img src="../Assets//Images/shop_images/<?=$ShopLogo?>" alt="Logo" class="logo" style="width: 100%;">
        </div>
        <div class="invoice-details" style="width:80%;">
            <p>Invoice No: <?=$BillNo?></p>
            <p>Invoice Date: <?=$EffectiveDate?></p>
            <p>Printed Date: <?=$print_date?></p>
        </div>
    </div>
    <div class="bill-container">
        <div class="bill-section">
            <strong>Bill From:</strong><br>
            <?=$headData[0]['ShopName']?><br>
            <?=$headData[0]['AddressLineOne']?> <?=$headData[0]['AddressLineTwo']?><br>
            <p><?=$headData[0]['PhoneNumber']?></p>
            <h5>Email: <?=$headData[0]['emailAddress']?></h5>
        </div>
        <div class="bill-section" style="text-align:right;">
            <strong>Bill To:</strong><br>
            <?=$cus_name?><br>
            <?=$CustAddress?> -
            <?=$cus_contact?>
        </div>
    </div>
    <table>
        <thead>
            <tr style="border-bottom: 1px dashed #aaa;">
                <th>Description Note</th>
                <th>Quantity Unit</th>
                <th>Rate</th>
                <th>Discount</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
                <?php 
                $sl=1;
                $sql_2 = "SELECT id.*, p.ItemName AS ItemName, p.Barcode AS ItemCode, id.item_des AS Description FROM `invoicedetails` id 
                INNER JOIN products p ON p.PDID=id.products_PDID
                WHERE id.InvoiceHeader_IHID='$invoice';";
                $shopData = $dbObj->getData($sql_2);
                foreach ($shopData as $row) 
                {
                    $ldiscount=0;
                    if($row["SellDiscount"]!=null)
                    {
                        $ldiscount=substr($row["SellDiscount"],0,-3);
                    }
                    else
                    {
                        $ldiscount=substr($row["PercentDiscount"],0,-3)."%";
                    }
                    ?>
                    <tr style=" border-bottom: 1px dashed #aaa;">
                        <td style="text-align: center;"><?=$row["ItemName"]?></td>
                        <td><?=substr($row["SellQty"],0,-4)?></td>
                        <td style="text-align: center;"><?=$row["UnitPrice"]?></td>
                        <td style="text-align: center;"><?=$ldiscount?></td>
                        <td style="text-align: center;"><?=$row["SoldAmount"]?></td>
                    </tr>
                    <?php
                    $sl++;
                }
                $sql_2 = "SELECT * FROM `transactions` t
                    INNER JOIN paymethod p ON p.PMID=t.paymethod_PMID
                    WHERE t.InvoiceHeader_IHID= '$invoice' AND TransactionStat=1;";

                $transactionData = $dbObj->getData($sql_2);
                $totalPaid=0;
                foreach ($transactionData as $row) 
                {
                    $totalPaid=$totalPaid+$row["TransferAmount"];
                }
                $balance=$NetAmount-$totalPaid;
                ?>
        </tbody>
    </table>
    <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
        <table style="width: 300px; border-collapse:collapse;">
            <tr>
                <td><b>Subtotal:</b></td>
                <td style="text-align: right;"><?=number_format((float)$GrossAmount, 2, '.', ',');?></td>
            </tr>
            <tr>
                <td><b>Total:</b></td>
                <td style="text-align: right;"><?=number_format((float)$NetAmount, 2, '.', ',');?></td>
            </tr>
            <tr>
                <td><b>Paid:</b></td>
                <td style="text-align: right;"><?=number_format((float)$NetAmount, 2, '.', ',');?></td>
            </tr>
            <?php 
                        if($balance>=0)
                        {
                            ?>
                            <tr>
                            <td><b>Balance Due:</b></td>
                                <td style="text-align: right;"><?=number_format((float)$balance, 2, '.', ',');?></td>
                            </tr>
                            
                            <?php
                        }
            ?>
            <tr>
                <td><b>Method of payment:</b></td>
                <?php 
                foreach ($transactionData as $row) 
                {
                    ?>
                    <td style="text-align: right;"><?=$row["PaymethodName"]?> - <?=number_format((float)$row["TransferAmount"], 2, '.', ',');?></td>
                    <?php
                }
                
                ?>
            </tr>
            <!-- <tr>
                <td><b>Cheque No: :</b></td>
                <td style="text-align: right;">*******217</td>
            </tr>
            <tr>
                <td><b>Cheque Date:</b></td>
                <td style="text-align: right;">01/01/2025</td>
            </tr> -->
        </table>
    </div>
    <footer>Thank You for purchasing <br>
    Software Provider: Synnex IT Solutions (Pvt) Ltd

    </footer>
    <!-- <footer>Page No: 1/1</footer> -->
</body>
<?php 
if(isset($_GET["Iframe"]) || isset($_GET["pdf"]))
{

}
else
{
   $newprint_count=$print_count+1;
    $sql = "UPDATE `invoiceheader` SET `print_count`=print_count+1 WHERE IHID= '$invoice';";
    $update = $dbObj->executeTransaction($sql); 
}

?>
</html>