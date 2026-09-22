<?php 
include '../Includes/includes.php';
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

<!doctype html>
<html lang="en">

<head>
  <?php 
  include '../View/head.php';
  ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
  <style>
        *{
            box-sizing: border-box;
            font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
            font-weight: bolder;
            color: #000;
        }
        h1
        {
            color: #000;
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
            border-bottom: 1px black solid;
            border-top: 1px black solid;
            font-size: 12px;
            padding: 5px;
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
            font-size: 10px;
        }
        .td_row2
        {
            font-size: 10px;
            padding: 0 0 0 10px;
        }
        .td_row
        {
            border: 1px solid #fff;
        }
        .td_row2
        {
            border-bottom: 1px solid black ;
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
            text-align: center;
            font-size: 9px;
        }
        td 
        {
            padding: 5px;
        }
        #div_cart
        {
            border-bottom: 1px dashed white;
        }
        #tbl_bill_detail tr
        {
            border: none ;
        }
        @media print
        {
            .h-100vh, .loader
            {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div>
        <?php 
        //get shop details
        $sql = "SELECT * FROM retrun_invoice_header 
        INNER JOIN shop ON shop.SHID = retrun_invoice_header.shopID
        INNER JOIN salesreturntype ON salesreturntype.SRTID=retrun_invoice_header.return_type
        WHERE RIHID = '$invoice_id' OR return_no='$invoice_id';";
        $headData  = $dbObj->getData($sql);
        $receipt_logo_name = $headData[0]['ReceiptLogo'];
        $address_1 = $headData[0]['AddressLineOne'];
        $address_2 = $headData[0]['AddressLineTwo'];
        $contact = $headData[0]['PhoneNumber'];
        $bill_no = $headData[0]['InvoiceNo'];
        $return_no = $headData[0]['return_no'];
        $SRT_Name = $headData[0]['SRT_Name'];
        $return_header_stat = $headData[0]['return_header_stat'];

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
        </p><br>
        <h3 class="text-center"><b>Sales Return Note</b></h3>
        <?php 
        if($return_header_stat==1)
        {
            ?>
            <h3 class="text-center"> <Strong>Returned</Strong> </h3>
            <?php
        }
        ?>
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
                <td class="td_bill_detail">Return No</td>
                <td class="td_bill_detail td_data"><?php echo $return_no;?></td>
            </tr>
            <tr>
                <td class="td_bill_detail">Return Type:</td>
                <td class="td_bill_detail td_data"><?php echo $SRT_Name;?></td>
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
                <th style="text-align:right;">Total</th>
            </tr>
            <?php 
            $rhid=$headData[0]["RIHID"];
            $sql = "SELECT * FROM returndetails
            INNER JOIN products ON products.PDID = returndetails.products_PDID
            WHERE ReturnHeader_RHID = $rhid";

            $invData = $dbObj->getData($sql);
            $row_count = 0;
            foreach($invData as $row)
            {
                $row_count += 1;
                ?>
                <tr>
                    <td class="td_row" colspan="4"><?php echo "(". $row_count .") ". $row['ItemName']?></td>
                    
                </tr>
                <tr>
                    <td class="td_row2"><?php echo $row['return_unit_price'];?></td>
                    <td class="td_row2"><?=number_format((float)$row['ReturnQty'], 2, '.', '')?></td>
                    <td class="td_row2"><?php echo $row['return_discount'];?></td>
                    <td class="td_total td_row2"><?php echo $row['ReturnAmount'];?></td>
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
        $sql = "SELECT * FROM retrun_invoice_header WHERE RIHID = $rhid";
        $headerData = $dbObj->getData($sql);

        $item_count = $headerData[0]['return_count'];
        $gross_amount = $headerData[0]['return_gross_amount'];
        $inv_discount = $headerData[0]['return_discount'];
        $net_amount = $headerData[0]['return_amount'];

        ?>
        <tr>
            <td class="td_row">Item Count :</td>
            <td class="td_total td_row"><?=number_format((float)$item_count, 2, '.', '')?></td>
        </tr>
        <tr>
            <td class="td_row">Total Return :</td>
            <td class="td_total td_row"><?=number_format((float)$net_amount, 2, '.', '')?></td>
        </tr>
        </table>
    </div>
    <input type="hidden" name="" value="<?=$return_no?>" id="return">
    <canvas id="barcode">

    </canvas>
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
    </script>
    <!------------------------------------ Shop Conditions ----------------------------------->
    <p id="p_conditions">
        Thank you for shopping!
    </p>

    <!-------------------------- Footer -------------------------->
    <p id="p_footer">
        Powered by : Synnex IT Solution PVT(LTD)
    </p>

    <script>
        window.print();
        setInterval(function(){
            <?php 
                if(isset($_SESSION["sales_returni"]))
                {
                    ?>
                    window.location="../Receipts/wholesaleInvoice.php?invoice=<?=$invoice_id?>";
                    <?php
                }
                else if(isset($_GET["print"]))
                {
                    ?>
                    window.close();
                    <?php
                }
                else
                {
                    ?>
                    window.location="../Public/sales-return.php";
                    <?php
                }
                ?>
            
        }, 1000);
    </script>
</body>
</html>