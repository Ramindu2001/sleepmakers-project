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
            font-size: 15px;
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
            font-size: 15px;
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
        WHERE IHID = ".$invoice_id.";";

        $headData  = $dbObj->getData($sql);
        $receipt_logo_name = $headData[0]['ReceiptLogo'];
        $address_1 = $headData[0]['AddressLineOne'];
        $address_2 = $headData[0]['AddressLineTwo'];
        $contact = $headData[0]['PhoneNumber'];
        $bill_no = $headData[0]['BillNo'];

        //user data
        $sql_1 = "SELECT * FROM user WHERE USID = ".$user_id.";";

        $userData = $dbObj->getData($sql_1);
        $user_name = $userData[0]['UserName'];

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

    <!------------------------------------- Bill Details ------------------------------->
    <div>
        <table id="tbl_bill_detail">

            <tr>
                <td class="td_bill_detail">User: </td>
                <td class="td_bill_detail td_data"><?php echo $user_name?></td>
            </tr>
            <tr>
                <td class="td_bill_detail">Bill No</td>
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
                <th>Rate</th>
                <th>Qty</th>
                <th>Dis.</th>
                <th>Total</th>
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
                
                $row_count += 1;
                ?>
                <tr>
                    <td class="td_row" colspan="4"><?php echo "(". $row_count .") ". $row['ItemName']?></td>
                </tr>
                <tr>
                    <td class="td_row"><?php echo $unit_price;?></td>
                    <td class="td_row"><?php echo $sell_qty;?></td>
                    <td class="td_row"><?php echo $row['SellDiscount'];?></td>
                    <td class="td_total td_row"><?php echo $row['SoldAmount'];?></td>
                </tr>
                <?php 
            }//foreach
            ?>
        </table>
    </div>

    <hr>

    <!----------------------------------- Invoice Summary ---------------------------------->
    <div>
        <table id="tbl_totals">
        <?php 
        //invoice header details
        $sql = "SELECT * FROM invoiceheader WHERE IHID = ".$invoice_id.";";
        $headerData = $dbObj->getData($sql);

        $item_count = $headerData[0]['InvItemCount'];
        $gross_amount = $headerData[0]['GrossAmount'];
        $inv_discount = $headerData[0]['DiscountAmount'];
        $net_amount = $headerData[0]['NetAmount'];
        $cust_payment = $headerData[0]['CustPayment'];
        $cust_balance = $headerData[0]['CustBalance'];

        ?>
        <tr>
            <td>Item Count :</td>
            <td class="td_total"><?php echo $item_count;?></td>
        </tr>
        <tr>
            <td>Payment :</td>
            <td class="td_total"><?php echo $cust_payment;?></td>
        </tr>
        <?php 

        //get payment method
        $sql_2 = "SELECT * FROM transactions 
        INNER JOIN paymethod ON paymethod.PMID = transactions.paymethod_PMID
        WHERE InvoiceHeader_IHID = ".$invoice_id.";";
        $transData = $dbObj->getData($sql_2);

        foreach($transData as $row)
        {
            ?>
            <tr>
                <td><?php echo $row['PaymethodName'] . " : ";?></td>
                <td class="td_total"><?php echo $row['TransferAmount'];?></td>
            </tr>
            <?php 
        }//foreach
        ?>

        <tr>
            <td>Balance :</td>
            <td class="td_total"><?php echo $cust_balance;?></td>
        </tr>
        </table>
    </div>

    <!------------------------------------ Shop Conditions ----------------------------------->
    <p id="p_conditions" style="margin-bottom: 2px;">
        Thank you for shopping!
    </p>

    <!-------------------------- Footer -------------------------->
    <p id="p_footer">
        Powered by : Synnex IT Solution PVT(LTD)
    </p>

    <script>
        setTimeout(function(){
            history.back();
        }, 100);//go back
    </script>
</body>
</html>