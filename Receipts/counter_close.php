<?php 
include "../Includes/includes.php";
include '../Includes/authcheck.php';

$counter_id = 0;
// $user_id = $_SESSION['user_id'];

$shop_id = $_SESSION['shop_id'];

if(isset($_GET['counter_id']))
{
    $counter_id = $_GET['counter_id'];
}//assign invoice id
else
{
    header("Location: ../Public/home.php");
}

$dbObj = new DBTransactions();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synnex Counter Close Report</title>
    <script src="../JQuery_361.js"></script>
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
        $sql = "SELECT * FROM `cashcounter`
        INNER JOIN shop ON shop.SHID = cashcounter.shop_SHID
        INNER JOIN user ON user.USID = cashcounter.user_USID
        WHERE CCID = ".$counter_id.";";

        $headData  = $dbObj->getData($sql);
        $receipt_logo_name = $headData[0]['ReceiptLogo'];
        $address_1 = $headData[0]['AddressLineOne'];
        $address_2 = $headData[0]['AddressLineTwo'];
        $CounterDate = $headData[0]['CounterDate'];
        $contact = $headData[0]['PhoneNumber'];
        $counter_user_id =  $headData[0]['user_USID'];
        $user_name = $headData[0]['UserName'];

        $counter_start_time = $headData[0]['CounterStartTime'];
        $counter_end_time = $headData[0]['CounterEndTime'];

        $counter_start_balance = floatval($headData[0]['StartBalance']);
        $counter_start_balance = number_format($counter_start_balance, 2, ".", "");

        $counter_close_balance = floatval($headData[0]['EndBalance']);
        $counter_close_balance = number_format($counter_close_balance, 2, ".", "");

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

    <!-- cash counter summary -->
    <?php 
    $counterObj = new Counter();
    $counterData = $counterObj->getCounterTotalByUser($user_id);

    //getcounter total
    $sql = "";

    $counter_total = floatval($counterData[0]['CounterTotal']);
    ?>

    <p style="font-size: 24px; font-weight: bold; text-align:center;">Counter Summary Report</p>

    <!------------------------------------- Bill Details ------------------------------->
    <div>
        <table id="tbl_bill_detail">

            <tr>
                <td class="td_bill_detail">User: </td>
                <td class="td_bill_detail td_data"><?php echo $user_name?></td>
            </tr>
            <tr>
                <td class="td_bill_detail">Counter Start: </td>
                <td class="td_bill_detail td_data"><?php echo $counter_start_time;?></td>
            </tr>
            <tr>
                <td class="td_bill_detail">Counter Close: </td>
                <td class="td_bill_detail td_data"><?php echo $counter_end_time;?></td>
            </tr>
        </table>
    </div>

    <hr>

    <!---------------------------------------- Cart ---------------------------------->
    <div id="div_cart">
        <table id="tbl_cart">
            <tr style="text-align: center; font-weight: bold;">
                <td colspan="2">Cash Drawer</td>
            </tr>
            <tr>
                <td>Start Balance</td>
                <td class="td_data"><?php echo $counter_start_balance?></td>
            </tr>
            <tr>
                <td>Close Balance</td>
                <td class="td_data"><?php echo $counter_close_balance;?></td>
            </tr>

            <tr style="text-align: center; font-weight: bold;">
                <td colspan="2">System Transactions</td>
            </tr>

            <?php 
            $sql = "SELECT SUM(transactions.TransferAmount) AS transfer_amount, paymethod.PaymethodName FROM `invoiceheader`
            INNER JOIN transactions ON transactions.InvoiceHeader_IHID = invoiceheader.IHID
            INNER JOIN paymethod ON paymethod.PMID = transactions.paymethod_PMID
            WHERE CashCounter_CCID = ".$counter_id." GROUP BY transactions.paymethod_PMID;";

            $payData = $dbObj->getData($sql);

            foreach($payData as $row)
            {
                ?>
                <tr>
                    <td><?php echo $row['PaymethodName'];?></td>
                    <td class="td_data"><?php echo $row['transfer_amount'];?></td>
                </tr>
                <?php 
            }//foreach transaction
            ?>

            <!-- get expenses -->
            <?php 
            $sql = "SELECT sum(ExpenseAmount) as counter_expenses FROM `expenses` WHERE counter_id = ".$counter_id.";";

            $expData = $dbObj->getData($sql);

            $counter_expenses = floatval($expData[0]['counter_expenses']);

            if($counter_expenses > 0)
            {
                ?>
                <tr style="text-align: center; font-weight: bold;">
                    <td colspan="2">Counter Expenses</td>
                </tr>
                
                <tr>
                    <td>Expenses</td>
                    <td class="td_data"><?php echo $counter_expenses;?></td>
                </tr>

                <?php 
            }//has expenses

            //get counter cash total
            $sql = "SELECT SUM(transactions.TransferAmount) AS transfer_amount, paymethod.PaymethodName FROM `invoiceheader`
            INNER JOIN transactions ON transactions.InvoiceHeader_IHID = invoiceheader.IHID
            INNER JOIN paymethod ON paymethod.PMID = transactions.paymethod_PMID
            WHERE CashCounter_CCID =".$counter_id." AND paymethod.PMID = 1 AND invoiceheader.InvStat=1 AND transactions.TransactionStat=1";

            $cashData = $dbObj->getData($sql);
            $counter_cash = floatval($cashData[0]['transfer_amount']);
            $final_balance = $counter_cash - $counter_expenses + $counter_start_balance;

            $counter_expenses = number_format($counter_expenses, 2, ".", "");
            $counter_cash = number_format($counter_cash, 2, ".", "");
            $final_balance = number_format($final_balance, 2, ".", "");
            ?>
            <tr style="text-align: center; font-weight: bold;">
                <td colspan="2">Counter Balance</td>
            </tr>
            <tr>
                <td>Cash Total</td>
                <td class="td_data"><?php echo $counter_cash;?></td>
            </tr>
            <tr style="font-size: 22px; font-weight: bold;">
                <td>Final Balance</td>
                <td class="td_data"><?php echo $final_balance;?></td>
            </tr>
            <?php 
            if($final_balance!=$counter_close_balance)
            {
                $ban=$final_balance-$counter_close_balance;
                ?>
                <tr style="font-size: 22px; font-weight: bold;">
                    <td>Cash Varrient</td>
                    <td class="td_data"><?php echo number_format($ban, 2, ".", "");?></td>
                </tr>
                <?php
            }
            $sql="SELECT SUM(cuscreditTransactionAmount) AS cuscreditTransactionAmount FROM `cuscredittransactions` WHERE paymethod_id=1 AND createDate='$CounterDate'";

            $cuscredittransactionsData = $dbObj->getData($sql);
            $cuscreditTransactionAmount = $cuscredittransactionsData[0]["cuscreditTransactionAmount"];
            $count=count($cuscredittransactionsData);
            if($count > 0 && ($cuscreditTransactionAmount>0 || $cuscreditTransactionAmount>0.00))
            {
                ?>
                <tr>
                    <td>Customer Due Received <span style="font-size:10px;">(Cash)</span></td>
                    <td class="td_data"><?php echo number_format($cuscreditTransactionAmount, 2, ".", "");?></td>
                </tr>
                <?php
                
            }
            ?>
        </table>

    </div>

    <hr>

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
                    height: 50,
                    displayValue: true
                });
            } else {
                alert("Please enter a value for the barcode.");
            };
        });
        setTimeout(function(){
            history.back();
        }, 2000);//go back
    </script>
</body>
</html>