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
    //  window.print();
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
                    // window.location="./deliveryNote.php?invoice_id=<?=$invoice?>";
                    <?php
                }
                else
                {
                    unset($_SESSION["deliveryNote"]);
                    ?>
                    // window.location="../Public/wholesale-invoice.php";
                    <?php
                }
                unset($_SESSION["deliveryNote"]);
            }
            elseif(isset($_SESSION["sales_returni"]))
            {
                unset($_SESSION["sales_returni"]);
                ?>
                // window.location="../Public/sales-return.php";
                <?php
            }
            elseif(isset($_GET["invoiceList"]))
            {
                ?>
                // //window.location="../Public/invoice-list.php";
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
                // window.location="../Public/wholesale-invoice.php";
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
<!DOCTYPE html>
<html lang="en">
<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Cloud POS | Synnex Cloud POS</title>
    <link rel="shortcut icon" type="image/png" href="../Assets/Images/favicon.png" />
    <link rel="stylesheet" href="../Assets/css/styles.min.css" />
    <style>
            *{
                color: black !important;
            }
            td
            {
                font-size:0.6rem;
            }
            th
            {
                font-size: 0.6rem;
            }
        .w-100 {
        width: 100% !important;
        }
        .w-90 {
        width: 90% !important;
        }
        .w-80 {
        width: 80% !important;
        }
        .w-70 {
        width: 70% !important;
        }
        .w-60 {
        width: 60% !important;
        }
        .w-50 {
        width: 50% !important;
        }
        .w-40 {
        width: 40% !important;
        }
        .w-30 {
        width: 30% !important;
        }
        .w-20 {
        width: 20% !important;
        }
        .w-10 {
        width: 10% !important;
        }
        #item
        {
            border-collapse: collapse;
        }
        #item , #item th, #item td {
        border: 1px solid #000; /* Black border for table, header, and cells */
        }

        #item th, #item td {
        padding: 10px; /* Adds padding inside the cells */
        text-align: left; /* Aligns text to the left */
        }
    </style>
</head>
<body>
    <div class="d-flex justify-centent-center">
        <div class="w-30 p-2" id="logo">
            <img src="../Assets/Images/shop_images/<?php echo $receipt_logo_name;?>" class="w-100" alt="shop logo" id="img_receipt">
        </div>
        <div class="w-70">
            <div class="w-100">
                <p class="text-center" style="font-size:0.8rem !important;"><b><?=$shop_name?></b></p>
            </div>
            <div class="w-100">
                <p class="text-center" style="font-size:0.6rem !important;">
                    <?=$address_1." ".$address_2?> <br>
                    <?=$contact?>  
                </p>
                
            </div>
        </div>
    </div>
    <div class="w-100">
        <table class="w-100">
            <tr>
                <td>Invoice No: </td>
                <td><?=$BillNo?></td>
                <td class="text-end">Date: </td>
                <td class="text-end"><?=$date?></td>
            </tr>
            <tr>
                <td>Customer Name: </td>
                <td><?=$cus_name?></td>
                <td></td>
            </tr>
            <tr>
                <td>Contact: </td>
                <td><?=$cus_contact?></td>
                <td></td>
            </tr>
            <tr>
                <td>Address: </td>
                <td><?=$CustAddress?></td>
                <td></td>
            </tr>
        </table>
        <table id="item" style="width:100%; margin-top:10px;">
            <thead>
                <tr>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: center;">Description</th>
                    <th style="text-align: right;">Rate</th>
                    <th style="text-align: right;">Sub Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sl=1;
                $sql_2 = "SELECT id.*, p.ItemName AS ItemName, p.Barcode AS ItemCode, p.ProdDescription AS Description FROM `invoicedetails` id 
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
                    <tr>
                        <td style="text-align: center;"><?=substr($row["SellQty"],0,-4)?></td>
                        <td style="text-align: center;"><?=$row["Description"]?></td>
                        <td style="text-align: right;"><?=$row["UnitPrice"]?></td>
                        <td style="text-align: right;"><?=$row["SoldAmount"]?></td>
                    </tr>
                    <?php
                    $sl++;
                }
                ?>
            </tbody>            
        </table>
        <div class="w-100" style="display:flex; Justify-content:center;">
            <div style="width: 20%;"></div>
            <div style="width: 80%; display:flex; Justify-content:end; margin-top:10px;">
                <table id="item" style="width: 100%;">
                    <tfoot>
                        <tr>
                            <td colspan="5" style="text-align: right;"><b>Net Total</b></td>
                            <td style="text-align: right;"><?=number_format((float)$NetAmount, 2, '.', ',');?></td>
                        </tr>
                        <?php 
                        $sql_2 = "SELECT * FROM `transactions` t
                        INNER JOIN paymethod p ON p.PMID=t.paymethod_PMID
                        WHERE t.InvoiceHeader_IHID= '$invoice' AND TransactionStat=1;";

                        $transactionData = $dbObj->getData($sql_2);
                        $totalPaid=0;
                        foreach ($transactionData as $row) 
                        {
                            $totalPaid=$totalPaid+$row["TransferAmount"];
                        }
                        if (!empty($deliveryCharge) && $deliveryCharge != 0)
                        {
                            ?>
                            <tr>
                                <td colspan="5" style="text-align: right;"><b>Delivery Charge </b></td>
                                <td style="text-align: right;"><?=number_format((float)$deliveryCharge, 2, '.', ',');?></td>
                            </tr>
                            <?php
                        }
                        if (!empty($otherCharge) && $otherCharge != 0)
                        {
                            ?>
                            <tr>
                                <td colspan="5" style="text-align: right;"><b>Other Charge</b></td>
                                <td style="text-align: right;"><?=number_format((float)$otherCharge, 2, '.', ',');?></td>
                            </tr>
                            <?php
                        }
                        if (!empty($returnAmount) && $returnAmount != 0)
                        {
                            ?>
                            <tr>
                                <td colspan="5" style="text-align: right;"><b>Return Deductions</b></td>
                                <td style="text-align: right;"><?=number_format((float)$returnAmount, 2, '.', ',');?></td>
                            </tr>
                            <?php
                        }
                        if (!empty($excessAmount) && $excessAmount != 0)
                        {
                            ?>
                            <tr>
                                <td colspan="5" style="text-align: right;"><b>Excess Deductions</b></td>
                                <td style="text-align: right;"><?=number_format((float)$excessAmount, 2, '.', ',');?></td>
                            </tr>
                            <?php
                        }
                        ?>
                        <tr>
                            <td colspan="5" style="text-align: right;"><b>Total Paid:</b></td>
                            <td style="text-align: right;"><?=number_format((float)$totalPaid, 2, '.', ',');?></td>
                        </tr>
                        <?php
                        $sql_2 = "SELECT * FROM `transactions` t
                            INNER JOIN paymethod p ON p.PMID=t.paymethod_PMID
                            WHERE t.InvoiceHeader_IHID= '$invoice' AND TransactionStat=1;";

                        $transactionData = $dbObj->getData($sql_2);
                        $totalPaid=0;
                        foreach ($transactionData as $row) 
                        {
                            $totalPaid=$totalPaid+$row["TransferAmount"];
                            ?>
                            <tr>
                                <td colspan="5" style="text-align: right;"><b><?=$row["PaymethodName"]?></b></td>
                                <td style="text-align: right;"><?=number_format((float)$row["TransferAmount"], 2, '.', ',');?></td>
                            </tr>
                            <?php
                        }
                        $sql_2 = "SELECT SUM(CreditAmount) AS TotCreditAmount, SUM(DebitAmount) AS TotDebitAmount FROM `creditcustomer` 
                        WHERE Customers_CTID=''
                        ";

                        $transactionData = $dbObj->getData($sql_2);
                        $balance=$NetAmount-$totalPaid;
                        if($balance>0)
                        {
                            ?>
                            <tr>
                                <td colspan="5" style="text-align: right;"><b>Total Payable</b></td>
                                <td style="text-align: right;"><?=number_format((float)$balance, 2, '.', ',');?></td>
                            </tr>
                            
                            <?php
                        }
                        ?>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
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