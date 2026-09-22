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
                window.history.back();
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

<!DOCTYPE html>
<html lang="en">
<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.css"> -->
    <title>Document</title>
    <style>
        *{
            font-family:sans-serif;
            font-size: 11px;
        }
        .container {
            width: 100%;
            height: 30vh; /* Adjust this as needed */
            display: flex;
            /* border: 1px solid #000; */
            justify-content: flex-start; /* Ensure rows start from the top */
            align-items: flex-start;
            flex-direction: column;
        }

        .container table {
            width: 100%;
            height: 100%;
            border-collapse: collapse;
        }
        .container tr:not(:last-child) {
            height: 30px;
        }

        .container tr:last-child {
            flex-grow: 1;
            border-bottom: 1px solid #000;
        }


        .container th{
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        .container td:first-child {
            border-left: 1px solid #000;
        }
        .container td {
            border-right: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        .container th {
            height: 40px;
        }

        .container tbody {
            height: 100%;
            overflow-y: auto; 
            justify-content: flex-start; /* Ensure rows start from the top */
            align-items: flex-start;
        }
        .container tbody td {
            height: auto;
        }

        #div_header{
            display: flex;
            flex-direction: row;
            margin-bottom: 100px;
        }
        #div_image{
            width: 16%;
        }
        #img_receipt_logo{
            max-width: 100%;
        }
        #company_name{
            margin-top: 5px;
            margin-bottom: 5px;
            margin: 5px 0px 5px 5px;
        }
        #company_detail{
            margin: 5px;
        }
        #invoice_detail{
            position: absolute;
            right: 10px;
        }
        #inv_detail{
            text-align: left;
            margin: 5px;
        }

        /*-------------- detail -------------- */
        #tbl_rent_detail{
            border-collapse: collapse;
            width: 100%;
        }
        #tbl_rent_detail th{
            border: 1px solid black;
            padding: 3px;
        }
        #tbl_rent_detail td{
            border: 1px solid black;
            padding: 3px;
        }

        #p_footer{
            bottom: 10px;
        }
        .w-100
        {
            width: 100%;
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
        $email = $headData[0]['emailAddress'];
    ?>
    <div class="row" id="div_header">
        <div id="invoice_detail">
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
                <h1 style="text-align:right; margin:5px;font-size:large">INVOICE</h1>
                
                <?php 
                if($invoiceData[0]['InvStat']==0)
                {
                    ?>
                    <h1 style="text-align:right; margin:5px;font-size:25px; text-transform:uppercase; font-weight:700;">Cancelled Invoice</h1>
                    <?php
                }
                ?>
                <?php 
                
                    if(isset($_GET["Iframe"]) || isset($_GET["pdf"]))
                    {

                    }
                    else
                    {
                        ?>
                        <h5 style="text-align:right; margin:5px;"><?php if($print_count>1){ echo "Duplicate Print ".$print_count;}?></h5>
                        <?php
                    }
                ?>
            
            <!-- <h5 style="text-align:right;" id="inv_detail">
                Date: <?php echo $date;?><br>
                Invoice No: <?php echo $BillNo;?>
            </h5> -->
        </div>
    </div>
    <div id="div_tenant">
        <table>
            <tr>
                <td><?=$address_1?>  <br>
                <?=$address_2?></td>
            </tr>
            <tr>
                <td><?=$contact?></td>
            </tr>
            <tr>
                <td>+94773940597</td>
            </tr>
            <tr>
                <td><?=$email?></td>
            </tr>
        </table>               
    </div>
        <div class="row" style="display:flex; Justify-content:center; margin-bottom:2px;">
            <div class="col-md-6" style="width:50%;">
                <h6 style="margin-bottom:0;">Invoice To:</h6>
                <table>
                    <tr>
                        <td style="border:1px solid black;  padding: 5px 81px 50px 5px;">
                            <?=$cus_name?><br>
                            <?=$cus_contact?><br>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6" style="width:50%; display:flex; Justify-content:end;    align-items: end;">
                <table id="tbl_rent_detail" style="width:auto; height:fit-content;">
                    <tr>
                        <td >
                            Date
                        </td>
                        <td>
                            <?=$EffectiveDate?>
                        </td>
                    </tr>
                    <tr>
                        <td >
                            Invoice No
                        </td>
                        <td>
                            <?php echo $BillNo;?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    <!-- rent detail -->
    <div>
        <div  class="container" >
        <table id="" style="width:100%;">
            <thead>
                <tr>
                    <th style="width: 80px;">Item Code</th>
                    <th style="width: 500px;">Description</th>
                    <th>Unit Price</th>
                    <th>Qty</th>
                    <th>Discount</th>
                    <th>Amount</th>
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
                        <td style="text-align: center;"><?=$row["ItemCode"]?></td>
                        <td><?=$row["Description"]?></td>
                        <td style="text-align: center;"><?=$row["UnitPrice"]?></td>
                        <td style="text-align: center;"><?=substr($row["SellQty"],0,-4)?></td>
                        <td style="text-align: center;"><?=$ldiscount?></td>
                        <td style="text-align: center;"><?=$row["SoldAmount"]?></td>
                    </tr>
                    <?php
                    $sl++;
                }
                ?>
    </div>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
            
        </table>
        </div>
        <div style="display:flex; Justify-content:center;">
            <div style="width: 50%;"></div>
            <div style="width: 50%; display:flex; Justify-content:end; margin-top:10px;">
                <table id="tbl_rent_detail" style="width: 50%;">
                    <tfoot>
                        <tr>
                            <td colspan="5" style="text-align: right;"><b>Subtotal</b></td>
                            <td style="text-align: right;"><?=number_format((float)$GrossAmount, 2, '.', ',');?></td>
                        </tr>
                        <tr>
                            <td colspan="5" style="text-align: right;"><b>Total Discount</b></td>
                            <td style="text-align: right;"><?=number_format((float)$DiscountAmount, 2, '.', ',');?></td>
                        </tr>
                        <tr>
                            <td colspan="5" style="text-align: right;"><b>Net Total</b></td>
                            <td style="text-align: right;"><?=number_format((float)$NetAmount, 2, '.', ',');?></td>
                        </tr>
                        <?php 
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

    <!-- footer -->
    <div>
        <p id="p_footer">
            <?=$remarks?>
        </p>
        <p id="p_footer" style="font-family: monospace; font-size: 11px;">Thank You For Shopping!</p>
        <p id="p_footer" style="font-family: monospace; font-size: 11px;">Powered By: Synnex IT Solution PVT (Ltd)</p>
    </div>


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