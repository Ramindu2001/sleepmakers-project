<?php  
function numberToWords($number) {
    $hyphen = '-';
    $conjunction = ' and ';
    $separator = ', ';
    $negative = 'negative ';
    $decimal = '  ';
    $dictionary = [
        0 => '',
        1 => 'one',
        2 => 'two',
        3 => 'three',
        4 => 'four',
        5 => 'five',
        6 => 'six',
        7 => 'seven',
        8 => 'eight',
        9 => 'nine',
        10 => 'ten',
        11 => 'eleven',
        12 => 'twelve',
        13 => 'thirteen',
        14 => 'fourteen',
        15 => 'fifteen',
        16 => 'sixteen',
        17 => 'seventeen',
        18 => 'eighteen',
        19 => 'nineteen',
        20 => 'twenty',
        30 => 'thirty',
        40 => 'forty',
        50 => 'fifty',
        60 => 'sixty',
        70 => 'seventy',
        80 => 'eighty',
        90 => 'ninety',
        100 => 'hundred',
        1000 => 'thousand',
    ];
    if (!is_numeric($number)) {
        return false;
    }

    if ($number < 0) {
        return $negative . numberToWords(abs($number));
    }

    $string = $fraction = null;

    if (strpos($number, '.') !== false) {
        list($number, $fraction) = explode('.', $number);
    }

    switch (true) {
        case $number < 21:
            $string = $dictionary[$number];
            break;
        case $number < 100:
            $tens = ((int) ($number / 10)) * 10;
            $units = $number % 10;
            $string = $dictionary[$tens];
            if ($units) {
                $string .= $hyphen . $dictionary[$units];
            }
            break;
            case $number < 1000:
                $hundreds = (int) ($number / 100); 
                $remainder = $number % 100;
                $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
                if ($remainder) {
                    $string .= $conjunction . numberToWords($remainder);
                }
                break;
        default:
            $baseUnit = pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
            $string = numberToWords($numBaseUnits) . ' ' . $dictionary[$baseUnit];
            if ($remainder) {
                $string .= $remainder < 100 ? $conjunction : $separator;
                $string .= numberToWords($remainder);
            }
            break;
    }

    if (null !== $fraction && is_numeric($fraction)) {
        $string .= $decimal;
        $words = [];
        foreach (str_split((string) $fraction) as $number) {
            $words[] = $dictionary[$number];
        }
        $string .= implode(' ', $words);
    }

    return $string;
}


include '../Includes/includes.php';

if(isset($_GET["invoice"]))
{
    $invoice = $_GET["invoice"];
}
else if(isset($_GET["invoice_id"]))
{
    $invoice = $_GET["invoice_id"];
}
?>
<script>
<?php 
if(isset($_GET["Iframe"]))
{
    ?>

<?php
}
elseif(isset($_GET["pdf"]))
{
    ob_start();
}
else
{
    ?>
window.print();
<?php
}
?>

setTimeout(function() {
    <?php 
            if(isset($_SESSION["deliveryNote"]))    
            {
                if($_SESSION["deliveryNote"]==1)
                {
                    unset($_SESSION["deliveryNote"]);
                    ?>
    window.location = "./deliveryNote.php?invoice_id=<?=$invoice?>";
    <?php
                }
                else
                {
                    unset($_SESSION["deliveryNote"]);
                    ?>
    window.location = "../Public/wholesale-invoice.php";
    <?php
                }
                unset($_SESSION["deliveryNote"]);
            }
            elseif(isset($_SESSION["sales_returni"]))
            {
                unset($_SESSION["sales_returni"]);
                ?>
    window.location = "../Public/sales-return.php";
    <?php
            }
            elseif(isset($_GET["invoiceList"]))
            {
                ?>
    window.location = "../Public/invoice-list.php";
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
    window.location = "../Public/wholesale-invoice.php";
    <?php
            }
                
            ?>
}, <?php if(isset($_GET["pdf"])){?>5000<?php } else { ?> 2000 <?php }
        ?>);
</script>
<?php  
$invoice = $_GET["invoice"];
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
    }

    html,
    body {
        width: 210mm;
        height: 297mm;
        padding: 0 20px;
    }

    .header {
        background-color: #e60000;
        color: white;
        padding: 10px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .header img {
        height: 80px;
    }

    .company-details {
        text-align: right;
    }

    .invoice-title {
        font-size: 24px;
        font-weight: bold;
    }

    .details-section 
    {


    }

    .details-section table {
        width: 100%;
        margin-bottom: 20px;
        border-collapse: collapse;
    }

    .details-section th,
    .details-section td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }

    .details-section th {
        background: #e11b34;
        color: white;
    }

    .details-section th:nth-child(1) {
        border-radius: 10px 0 0 0;
        border: 0 solid #ddd;
    }

    .details-section th:nth-child(5) {
        border-radius: 0 10px 0 0;
        border: 0 solid #ddd;
    }

    .summary-section {
        text-align: right;
    }

    .summary-section table {
        width: 100%;
    }

    .summary-section th,
    .summary-section td {
        padding: 8px;
        text-align: left;
    }

    .footer {
        margin: 20px;
        text-align: center;
        font-size: 14px;
    }

    .authorized {
        margin-top: 50px;
        text-align: center;
        font-weight: bold;
    }

    .icon {
        width: 20px;
        height: 20px;
        padding: 0 5px;
    }

    .w-30 {
        display: flex;
        justify-content: center;
    }

    .icon-text {
        font-size: 12px;
        color: white;
        height: fit-content;
        margin: 5px 0 0 0;

    }

    .w-50 {
        width: 50%;
    }
    </style>
</head>

<body>
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
    $shop_email = $headData[0]['emailAddress'];
?>
    <div id="invoice_detail">
        <?php 
    //Invoice data
    $sql_1 = "SELECT ih.*, c.CustName As CustomerName, c.CustContact AS CustContact, c.CustAddress AS CustAddress, u.UserName AS officer, sm.SalesmansName AS Salesmen,c.CTID FROM `invoiceheader` ih
        INNER JOIN customers c ON c.CTID=ih.customers_CTID
        INNER JOIN user u on u.USID=ih.user_USID
        INNER JOIN salesmans sm ON sm.SLID=ih.Salesmans_SLID
        WHERE ih.IHID= '$invoice';";

    $invoiceData = $dbObj->getData($sql_1);
    $cusID = $invoiceData[0]['CTID'];
    $cus_name = $invoiceData[0]['CustomerName'];
    $cus_contact = $invoiceData[0]['CustContact'];
    $CustAddress = $invoiceData[0]['CustAddress'];
    $officer = $invoiceData[0]['officer'];
    $Salesman = $invoiceData[0]['Salesmen'];
    $BillNo = $invoiceData[0]['BillNo'];
    $date = $invoiceData[0]['EffectiveDate'];
    $GrossAmount = $invoiceData[0]['GrossAmount'];
    $NetAmount = $invoiceData[0]['NetAmount'];
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
    $cust_balance = $invoiceData[0]['CustBalance'];

    //print date time
    date_default_timezone_set("Asia/Colombo");
    $print_date = date("Y-m-d");
    $print_time = date("H:i:s");
    ?>
    </div>
    </div>
    <div class="header-flex ">
        <div class="header-main d-flex">
            <div class="d-flex" style="display:flex; justify-content:start;">
                <div class="w-80"
                    style="background:#262932;width:56%;height: 110px;border-radius: 0 0 132px 0; padding:10px;">
                    <img src="../Assets/Images/shop_images/ncs.jpg" alt="" style="width:100px;">
                    <h2
                    style="padding-top:0; font-size:18px; color:White; font-weight:400PX; font-family: 'Quicksand', serif; text-transform:uppercase;"><b>
                    New Season Center | Luta Bicycle Co.</h2></b>
                </div>
            </div>
            <div class="d-flex" style="display:flex; justify-content:end;">
                <div class="w-80"
                    style="position:absolute;top: 0px;background:#e11b34;width:75%;height:65px;border-radius: 0 0 0 132px; display:flex; justify-content:center; padding:10px 5PX 0 0;">
                    <div class="w-30" style="width:30%;">
                        <img src="../Assets/Images/icons/phone-white.png" alt="" class="icon">
                        <p class="icon-text"><?php echo $contact?></p>
                    </div>
                    <div class="w-30" style="width:30%;">
                        <img src="../Assets/Images/icons/email-white.png" alt="" class="icon">
                        <p class="icon-text"><?php echo $shop_email?></p>
                    </div>
                    <div class="w-30" style="width:30%;">
                        <img src="../Assets/Images/icons/maps-and-flags-white.png" alt="" class="icon">
                        <p class="icon-text"><?php echo $address_2?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div style="display:flex; justify-content:center;">
        <div class="w-50">
            <p style="color:#e11b34;">Bill To:</p>
            <p>Customer Name: <?php echo $cus_name ?></p>
            <p>Contact No:<?php echo $cus_contact ?></p>
        </div>
        <div class="w-50">
            <div class="invoice-title">
                Invoice
            </div>
            <div style="display:flex; justify-content:center;">
                <div class="w-50">
                    <p><b>Invoice No: </b><span></span></p>
                    <p><b>Date: </b><span></span></p>
                </div>
                <div class="w-50">
                    <p><?php echo $BillNo?></p>
                    <p><?php echo $date?></p>
                </div>
            </div>
        </div>
    </div>
    </div>
    <div class="details-section">
        <table>
            <thead style="background:#e11b34;">
                <tr>
                    <th>#</th>
                    <th>Item Name</th>
                    <th style="text-align: right;">Quantity</th>
                    <th style="text-align: right;">Price/Unit</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sl=1;
                $sql_2 = "SELECT id.*, p.ItemName AS ItemName FROM `invoicedetails` id 
                INNER JOIN products p ON p.PDID=id.products_PDID
                WHERE id.InvoiceHeader_IHID= '$invoice';";
                $shopData = $dbObj->getData($sql_2);
                foreach ($shopData as $row) 
                {
                    $ldiscount=0;
                    if($row["SellDiscount"]!=null)
                    {
                        $ldiscount=$row["SellDiscount"];
                    }
                    else
                    {
                        $ldiscount=$row["PercentDiscount"]."%";
                    }
                    ?>
                <tr>
                    <td style="text-align: center;"><?=$sl?></td>
                    <td><?=$row["ItemName"]?></td>
                    <td style="text-align: center;"><?=$row["SellQty"]?></td>
                    <td style="text-align: center;"><?=$row["UnitPrice"]?></td>
                    <td style="text-align: right;"><?=$row["SoldAmount"]?></td>
                </tr>
                <?php
                    $sl++;
                }

                ?>
            </tbody>
        </table>
    </div>
    <div style="display:flex; justify-content:center; margin-top: -20px; ">
        <div class="w-50">
            <p style="color:#e11b34; font-size:12px;">Pay To:</p>
            <p style="font-size:12px;">Bank Name: HNB</p>
            <p style="font-size:12px;">Bank Account No: 007010307501</p>
            <p style="font-size:12px;">Bank IFSC code: 7010</p>
            <p style="font-size:12px;">Account Holder's Name: Jezran</p>
        </div>
        <div class="w-50">
            <div class="summary-section details-section">
                <table>
                    <tr>
                        <td>Sub Total:</td>
                        <td><?=number_format((float)$GrossAmount,2,'.',',');?></td>
                    </tr>
                    <tr>
                        <td style="background:#e11b34;color:white;">Total:</td>
                        <td style="background:#e11b34;color:white;"><?=number_format((float)$NetAmount, 2, '.', ',');?>
                        </td>
                    </tr>
                    <?php
                    $words=numberToWords($NetAmount);
                       $sql_2 = "SELECT * 
                       FROM `transactions` WHERE InvoiceHeader_IHID = $invoice;";
             
                        $receviedBill = $dbObj->getData($sql_2);
                        $totalReceived=0;
                        foreach ($receviedBill as $receviedBills) 
                        {
                            $totalReceived=$totalReceived+$receviedBills["TransferAmount"];
                        }
                        $balance=$totalReceived-$NetAmount;
                        ?>
                        <tr>
                            <td>Received:</td>
                            <td><?=number_format((float)$totalReceived, 2, '.', ',') ?></td>
                        </tr>                        
                        <?php
                        if($balance > 0)
                        {
                            ?>
                            <tr>
                                <td>Balance (Change):</td>
                                <td><?= number_format((float)$balance, 2, '.', ',') ?></td>
                            </tr>
                            
                            <?php
                        }
                    $outstanding=0;
                    $sql_2 = "SELECT c.*, SUM(cc.CreditAmount) AS total_credit, SUM(cc.DebitAmount) AS total_debit,cc.EffectiveDate,cc.DueDate, i.BillNo FROM customers c
                    INNER JOIN creditcustomer cc ON c.CTID=cc.Customers_CTID 
                    INNER JOIN invoiceheader i on i.IHID=cc.invoice_header_id
                    WHERE cc.shop_SHID='$shop_id' AND cc.Customers_CTID='$cusID' GROUP by cc.Customers_CTID,cc.invoice_header_id;";

                    $outstandingBills = $dbObj->getData($sql_2);
                    foreach ($outstandingBills as $outstandingBills) 
                    {
                        if(isset($outstandingBills["total_credit"]))
                        {
                            $outstanding=$outstandingBills["total_credit"]-$outstandingBills["total_debit"];
                        } 
                        if($outstanding > 0 && $outstanding!="0.00")
                        {
                        ?>
                    <tr>
                        <td><?=$outstandingBills["BillNo"]?></td>
                        <td><?=number_format((float)$outstanding, 2, '.', ',')?></td>
                    </tr>
                    <?php
                        }
                    }
                    $outstanding=0;
                    $sql_2 = "SELECT c.*, SUM(cc.CreditAmount) AS total_credit, SUM(cc.DebitAmount) AS total_debit,cc.EffectiveDate,cc.DueDate FROM customers c
                    INNER JOIN creditcustomer cc ON c.CTID=cc.Customers_CTID 
                    WHERE cc.shop_SHID='$shop_id' AND cc.Customers_CTID='$cusID' GROUP by cc.Customers_CTID;";

                    $outstandingBills = $dbObj->getData($sql_2);
                    if(isset($outstandingBills[0]["total_credit"]))
                    {
                        $outstanding=$outstandingBills[0]["total_credit"]-$outstandingBills[0]["total_debit"];
                    }
                    
                    if($outstanding > 0 && $outstanding!="0.00")
                    {
                        ?>
                    <tr>
                        <td>Current Balance</td>
                        <td><?=number_format((float)$outstanding, 2, '.', ',')?></td>
                    </tr>
                    <?php
                    }?>
                </table>
            </div>
        </div>
    </div>
    <div style="display:flex; justify-content:center;">
        <div class="w-50">
            <p style="color:#e11b34; font-size:12px;">Invoice Amount In Words:</p>
            <p style="font-size:12px; text-transform:capitalize;"><?=$words?> Only/-</p>
        </div>
        <div class="w-50"></div>
    </div>
    <div style="display:flex; justify-content:center;">
        <div class="w-50">
            <p style="color:#e11b34; font-size:12px;">Terms And Conditions</p>
            <p style="font-size:12px;">Thanks for doing business with us!</p>
        </div>
        <div class="w-50"></div>
    </div>
    <div style="display:flex; justify-content:center;">
        <div class="w-50">
            <p style="border-bottom: 1px solid;font-size:12px; width:50%;">&nbsp;&nbsp;&nbsp;&nbsp;</p>
            <p style="font-size:12px; width:50%; text-align:center;"><b>Authorized Signature</b></p>
        </div>
        <div class="w-50"></div>
    </div>
    <!--<p style="text-align:right; font-size: 10px;">Software Provider: Synnex IT Solutions</p>-->

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
</body>

</html>
<?php 
if( isset($_GET["pdf"]))
{
    $html = ob_get_clean();

    // Load the HTML content into DOMPDF
    $dompdf->loadHtml($html);
    
    // Set paper size and orientation
    $dompdf->setPaper('A4', 'portrait');
    
    // Render the PDF
    $dompdf->render();
    
    // Output the PDF to the browser for download
    $dompdf->stream("sample.pdf", ["Attachment" => 1]);
}
?>