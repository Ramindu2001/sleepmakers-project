<?php 
include "../Includes/includes.php";
include '../Includes/authcheck.php';

$invoice_id = 0;
$shop_id = 0;
$user_id = $_SESSION['user_id'];

if(isset($_SESSION['shop_id']))
{
    $shop_id = $_SESSION['shop_id'];
}//assign shop id

if(isset($_GET['invoice_id']))
{
    $invoice_id = $_GET['invoice_id'];
}//assign invoice id

$dbObj = new DBTransactions();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synnex Receipt Print</title>
        <script src="../JQuery_361.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>

    <style>
        *{
            box-sizing: border-box;
            font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
        }
        #img_receipt{
            width: 50%;
            margin-left: 25%;
        }
        #p_address{
            text-align: center;
            font-size: 12px;
            margin: 0px 5px;
            border: 1px solid black;
            border-radius: 5px;
        }
        #tbl_bill_detail{
            width: 100%;
        }
        .td_bill_detail{
            font-size: 12px;
        }
        .td_data{
            text-align: right;
        }


        /*----  Cart ---- */
        th{
            border: 1px black solid;
            font-size: 14px;
        }
        #tbl_cart{
            width: 100%;
            border-collapse: collapse;
        }
        .td_total{
            text-align: right;
            font-weight: bold;
        }
        .td_row{
            font-size: 13px;
            border: 1px black solid;
        }

        /*------ Totals ------- */
        #tbl_totals{
            width: 100%;
        }

        /*--------------- Conditions ------------- */
        #p_conditions{
            text-align: center;
            font-size: 11px;
        }

        /*---------------- Footer ------------- */
        #p_footer{
            margin-top: 2px;
            text-align: center;
            font-size: 9px;
        }
    </style>
</head>
<body onload="window.print()">

    <div>
        <?php 
        //get shop details
        $sql = "SELECT * FROM invoiceheader 
        INNER JOIN shop ON shop.SHID = invoiceheader.shop_SHID
        INNER JOIN user ON user.USID = invoiceheader.user_USID
        WHERE IHID = ".$invoice_id.";";

        $headData  = $dbObj->getData($sql);
        $receipt_logo_name = $headData[0]['ReceiptLogo'];
        $address_1 = $headData[0]['AddressLineOne'];
        $address_2 = $headData[0]['AddressLineTwo'];
        $contact = $headData[0]['PhoneNumber'];
        $bill_no = $headData[0]['BillNo'];
        $user_name = $headData[0]['UserName'];

        //user data
        $sql_1 = "SELECT * FROM user WHERE USID = ".$user_id.";";

        $userData = $dbObj->getData($sql_1);
        //$user_name = $userData[0]['UserName'];

        //print date time
        date_default_timezone_set("Asia/Colombo");
        $print_date = date("Y-m-d");
        $print_time = date("H:i:s");

        ?>
        <img src="../Assets/Images/shop_images/<?php echo $receipt_logo_name;?>" alt="shop logo" id="img_receipt">

        <p id="p_address">
            <?php echo $address_1 . ", " . $address_2 . ".";?><br>
            <?php echo "Tel - " . $contact;?>
        </p>
    </div>

    <!------------------------------------- Bill pay type ------------------------------>
    <?php 
    //get payment method
    $sql_2 = "SELECT * FROM transactions 
    INNER JOIN paymethod ON paymethod.PMID = transactions.paymethod_PMID
    WHERE InvoiceHeader_IHID = ".$invoice_id.";";
    $transData = $dbObj->getData($sql_2);

    $is_credit = 0;
    $is_multipay = 0;
    $row_count = 0;
    foreach($transData as $row)
    {
        $row_count += 1;
        if($row['paymethod_PMID'] == '4')
        {
            $is_credit = 1;
        }
    }//foreach

    //if credit invoice
    if($is_credit == 1)
    {
        ?>
        <p style="margin: 2px 0px; text-align:center; font-size: 16px;">Credit Invoice</p>
        <?php 
    }//credit invoice
    ?>

    <!------------------------------------- Bill Details ------------------------------->
    <div>
        <table id="tbl_bill_detail">

            <tr>
                <td class="td_bill_detail">Sale By: </td>
                <td class="td_bill_detail td_data"><?php echo $user_name?></td>
            </tr>
            <tr>
                <td class="td_bill_detail">Invoice #: </td>
                <td class="td_bill_detail td_data"><?php echo $bill_no;?></td>
            </tr>
            <tr>
                <td class="td_bill_detail">Date & Time: </td>
                <td class="td_bill_detail td_data"><?php echo $print_date ." at: ". $print_time;?></td>
            </tr>
        </table>
    </div>

    <!---------------------------------------- Cart ---------------------------------->
    <div id="div_cart">
        <table id="tbl_cart">
            <tr>
                <th>Description</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Amount</th>
            </tr>
            <?php 
            $sql = "SELECT * FROM invoicedetails
            INNER JOIN products ON products.PDID = invoicedetails.products_PDID
            WHERE InvoiceHeader_IHID = ".$invoice_id.";";

            $invData = $dbObj->getData($sql);
            $row_count = 0;
            foreach($invData as $row)
            {
                $conversion_rate = floatval($row['UnitConversion']);
                $unit_price = $row['UnitPrice'];
                $sell_qty = $row['SellQty'] + 0;

                $item_discount = floatval($row['SellDiscount']);
                
                $row_count += 1;
                ?>
                <tr>
                    <td class="td_row" style="text-align: center;">
                        <?php
                        echo $row['Barcode'] ." - ". $row['ItemName'];

                        if($item_discount > 0)
                        {
                            echo "<br> Discount: " . $item_discount;
                        }
                        ?>
                    </td>
                    <td class="td_row" style="text-align: center;"><?php echo $sell_qty;?></td>
                    <td class="td_row"><?php echo $unit_price;?></td>
                    <td class="td_total td_row"><?php echo $row['SoldAmount'];?></td>
                </tr>
                <?php 
            }//foreach
            ?>
        </table>
    </div>

    <!----------------------------------- Invoice Summary ---------------------------------->
    <div>
        <table id="tbl_totals">
        <?php 
        //invoice header details
        $sql = "SELECT * FROM invoiceheader WHERE IHID = ".$invoice_id.";";
        $headerData = $dbObj->getData($sql);

        $item_count = $headerData[0]['InvItemCount'];
        $gross_amount = $headerData[0]['GrossAmount'];
        $inv_discount = floatval($headerData[0]['DiscountAmount']);

        $net_amount = floatval($headerData[0]['NetAmount']);
        $net_amount = number_format((float)$net_amount, 2, ".", "");

        $cust_payment = $headerData[0]['CustPayment'];
        $cust_balance = $headerData[0]['CustBalance'];
        ?>
        <tr style="font-size: 14px;">
            <td style="text-align: center;">Total:</td>
            <td style="text-align: right;"><?php echo $gross_amount;?></td>
        </tr>
        <tr style="font-size: 14px;">
            <td style="text-align: center;">Discount:</td>
            <td style="text-align: right;"><?php echo $inv_discount;?></td>
        </tr>

        <tr style="font-size: 18px; font-weight:bold;">
            <td style="text-align: center;">Net Total:</td>
            <td style="text-align: right;"><?php echo $net_amount;?></td>
        </tr>
        <?php 

        //get payment method
        $sql_2 = "SELECT * FROM transactions 
        INNER JOIN paymethod ON paymethod.PMID = transactions.paymethod_PMID
        WHERE InvoiceHeader_IHID = ".$invoice_id.";";
        $transData = $dbObj->getData($sql_2);

        $transaction_total = 0;
        $is_credit = 0;
        $is_multipay = 0;
        foreach($transData as $row)
        {   
            if($row['paymethod_PMID'] == '4')
            {
                $is_credit = 1;
            }
            $transaction_total += floatval($row['TransferAmount']);
            ?>
            <tr style="font-size: 14px;">
                <td style="text-align: center;"><?php echo $row['PaymethodName'] . " : ";?></td>
                <td style="text-align: right;"><?php echo $row['TransferAmount'];?></td>
            </tr>
            <?php 
        }//foreach

        if($is_credit == 1)
        {   
            $cust_balance = 0;
        }//is credit
        ?>

        <tr style="font-size: 18px; font-weight:bold;">
            <td style="text-align: center;">Balance :</td>
            <td style="text-align: right;"><?php echo $cust_balance;?></td>
        </tr>

        <tr style="font-size: 11px;">
            <td style="text-align: center;">Number of Items:</td>
            <td style="text-align: right;"><?php echo $item_count;?></td>
        </tr>
        </table>
    </div>

    <!------------------------------------ Shop Conditions ----------------------------------->
    <p id="p_conditions" style="margin-bottom: 2px;">
        <b>Warranty & Exchange Policy</b><br>
        We offer 6 Months of warranty for every electronic product from the date of purchase. This warranty will be considered void if there is damage due 
        to improper use, lightning, over-voltage, negligence, accident, water/other liquid damages, physicaldamages and changes in original appearance.
        <br>
        Invoice must be handed along the product to claim your warranty or exchange.
        <br>
        Thank you, Come again...
    </p>

    <!-------------------------- Footer -------------------------->
    <p id="p_footer">
        Powered by : Synnex IT Solution PVT(LTD)
    </p>

    <input type="hidden" name="" value="<?=$bill_no?>" id="return">
    <canvas id="barcode"></canvas>
    <script>
        $(document).ready(function() {
            var value = $("#return").val();
            if (value) 
            {
                JsBarcode("#barcode", value, {
                    format: "CODE128",
                    lineColor: "#000",
                    width: 2,
                    height: 25,
                    displayValue: true
                });
            } else {
                alert("Please enter a value for the barcode.");
            };
        });
        setTimeout(function(){
            history.back();
        }, 200);//go back
    </script>
</body>
</html>