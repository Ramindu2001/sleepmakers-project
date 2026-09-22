<?php 
include "../Includes/includes.php";
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
                // unset($_SESSION["deliveryNote"]);
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
                // window.close();
                <?php
            }
            elseif(isset($_GET["print"]))
            {
                ?>
                // window.close();
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

if(isset($_SESSION['shop_id']))
{
    $shop_id = $_SESSION['shop_id'];
}//assign shop id

if(isset($_GET['invoice']))
{
    $invoice_id = $_GET['invoice'];
}//assign invoice id

$dbObj = new DBTransactions();

?>
<!DOCTYPE html>
<html lang="en" style="
    background-repeat: no-repeat;
    background-image: url('../Assets/Images/icons/door decor premium invoice.jpg');
    background-size: contain;
">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synnex Receipt Print</title>
    <script src="../JQuery_361.js"></script>
    <style>
        *{
            box-sizing: border-box;
            font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
        }
        /* div{
            border: 1px solid blue;
        } */
        
        /*td{*/
        /*    border:1px solid black;*/
        /*}*/
        /* ---------------- top -------------- */
        <?php 
        if(isset($_SESSION["claim"]))
        {
            ?>
            #div_payment
            {
                margin-right: 40px !important;
            }
            body
            {
                padding-top: 5px !important;
            }
                #div_top{
                margin-top: 105px !important;
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
            }
            #tbl_totals {
                position: absolute !important;
                right: 18px !important;
                bottom: 190px !important;
            }
            <?php
        }
        ?>
        
        #div_customer{
            margin-left: 40px;
        }
        #p_customer{
            margin-top: 0px;
        }

        #div_payment{
            margin-right: 10px; 
            width: 200px;
            font-size: 15px;
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
        #div_cart
        {
            margin-top: 35px;
        }
        th{
            border: 1px black solid;
            font-size: 15px;
        }
        #tbl_cart{
            margin-left: 25px;
            width: 95%;
            border-collapse: collapse;
        }
        .td_total{
            text-align: right;
            font-size: 13px;
            font-weight: bold;
            padding: 5px;
        }
        .td_row{
            font-size: 12px;
        }

        /*------ Totals ------- */
        #tbl_totals{
            position:fixed;
            right: 7px;
            bottom: 175px;
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
        body
        {
            width: 148mm;
            height: 100mm;
        }
    </style>
</head>
<body >
   
    <?php 
    $sql = "SELECT * FROM invoicedetails
    INNER JOIN products ON products.PDID = invoicedetails.products_PDID
    WHERE InvoiceHeader_IHID = ".$invoice_id.";";

    $invData = $dbObj->getData($sql);

    $row_count = count($invData);

    //number of items in one page
    $break_num = 10;

    $page_count = ceil($row_count / $break_num);

    for ($i=0; $i < $page_count ; $i++) 
    {   
        ?>
        <!---------------------------------- Customer & Invoice -------------------------------->
        <div>
            <?php 
                //get shop details
                $sql = "SELECT * FROM invoiceheader 
                INNER JOIN shop ON shop.SHID = invoiceheader.shop_SHID 
                INNER JOIN customers ON customers.CTID = invoiceheader.customers_CTID
                WHERE IHID = ".$invoice_id.";";

                $headData  = $dbObj->getData($sql);
                $bill_no = $headData[0]['BillNo'];
                $print_count = $headData[0]['print_count'];

                $customer_name = $headData[0]['CustName'];
                $customer_contact = $headData[0]['CustContact'];
                
                $issue_date_time = $headData[0]['InvEndTime'];
                $arr_date_time = explode(" ", $issue_date_time);

                //get transaction
                $sql_2 = "SELECT * FROM transactions 
                INNER JOIN paymethod ON paymethod.PMID = transactions.paymethod_PMID
                WHERE InvoiceHeader_IHID = ".$invoice_id.";";

                $transData = $dbObj->getData($sql_2);
                $pay_method = $transData[0]['PaymethodName'];

                //user data
                $sql_1 = "SELECT * FROM user WHERE USID = ".$user_id.";";

                $userData = $dbObj->getData($sql_1);
                $user_name = $userData[0]['UserName'];

                //print date time
                date_default_timezone_set("Asia/Colombo");
                $print_date = date("Y-m-d");
                $print_time = date("H:i:s");

            ?>
            <div id="div_top">
                <div id="div_customer">
                    <p id="p_customer">
                        Customer: <br>
                        <?php echo $customer_name;?> <br>
                        <?php echo $customer_contact;?>
                    </p>
                </div>
                <div id="div_payment">
                    <table style="width: 100%;">
                        <tr>
                            <td><?php echo $pay_method;?></td>
                            <td style="text-align: right;"><?php echo $bill_no;?></td>
                        </tr>
                        <tr>
                            <td><?php echo $print_date;?></td>
                            <td style="text-align: right;"><?php echo $print_time;?></td>
                        </tr>
                        <tr style="display:none;">
                            <td></td>
                            <td style="text-align: right;"><?php echo "Page: " . $i+1 ."/". $page_count;?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!---------------------------- Cart ------------------------->
        <div id="div_cart">
            <table id="tbl_cart">
                <?php 
                    $sql = "SELECT * FROM invoicedetails
                    INNER JOIN products ON products.PDID = invoicedetails.products_PDID
                    WHERE InvoiceHeader_IHID = ".$invoice_id.";";
        
                    $invData = $dbObj->getData($sql);
                    
                    $item_count = count($invData);
                    //starting with previous number
                    $start_num = $i * $break_num; 
                    
                    //end with row count or break number
                    $end_num = $start_num + $break_num;

                    $end_num = $end_num > $item_count ? $end_num = $item_count : $end_num = $start_num + $break_num;

                    $row_count = 0;
                    for ($j=$start_num; $j <$end_num ; $j++) 
                    { 
                        $row_count = $j + 1;
                        $item_name = $invData[$j]['ItemName'];
                        $sell_qty = $invData[$j]['SellQty'] + 0;
                        $unit_price = $invData[$j]['UnitPrice'];
                        $item_discount = empty($invData[$j]['SellDiscount']) ? 0.00 : $invData[$j]['SellDiscount'];
                        $sell_amount = $invData[$j]['SoldAmount'];
                        $ldiscount=0;
                        if($invData[$j]["disc_type"]==2)
                        {
                            $ldiscount=substr($invData[$j]["SellDiscount"],0,-3);
                        }
                        elseif($invData[$j]["disc_type"]==0)
                        {
                            if($invData[$j]["SellDiscount"]!=null)
                            {
                                $ldiscount=substr($invData[$j]["SellDiscount"],0,-3);
                            }
                            else
                            {
                                $ldiscount=substr($invData[$j]["PercentDiscount"],0,-3)."%";
                            }
                        }
                        else
                        {
                            $ldiscount=substr($invData[$j]["PercentDiscount"],0,-3)."%";
                        }

                        // echo "id = " . $invData[$j]['SellDiscount'] ." - ". $invData[$j]['ItemName'] . "<br>";
                        ?>
                        <tr>
                            <td class="td_row" style="width: 58px; text-align:center;"><?php echo $row_count;?></td>
                            <td class="td_row" style="width: 170px;"><?php echo $item_name;?></td>
                            <td class="td_row" style="width: 18px; text-align:center;"><?php echo $sell_qty;?></td>
                            <td class="td_row" style="width: 60px; text-align:right;"><?php echo $unit_price;?></td>
                            <td class="td_row" style="width: 55px; text-align:center;"><?php echo $ldiscount;?></td>
                            <td class="td_total td_row" style="width: 90px; text-align:right;"><span class="absolute" style=" position: absolute; right: 25px;"><?php echo $sell_amount;?></span></td>
                        </tr>
                        <?php 
                    }//for showing cart
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

                // $item_count = $headerData[0]['InvItemCount'];
                $gross_amount = $headerData[0]['GrossAmount'];
                $inv_discount = floatval($headerData[0]['DiscountAmount']);
                $net_amount = floatval($headerData[0]['NetAmount']);

                $inv_discount = number_format($inv_discount, 2, '.', '');
                $net_amount = number_format($net_amount, 2, '.', '');

                ?>
                <tr>
                    <td class="td_total"><?php  echo "Rs. " . $gross_amount;?></td>
                </tr>
                <tr>
                    <td class="td_total"><?php  echo "Rs. " . $inv_discount;?></td>
                </tr>
                <tr>
                    <td class="td_total"><?php  echo "Rs. " . $net_amount;?></td>
                </tr>
            </table>
        </div>

        <p style="page-break-after: always;"></p>
        <?php  
    }//for page breaker
    ?>

<?php 
if(isset($_GET["Iframe"]) || isset($_GET["pdf"]))
{

}
else
{
   $newprint_count=$print_count+1;
    $sql = "UPDATE `invoiceheader` SET `print_count`=print_count+1 WHERE IHID= '$invoice_id';";
    $update = $dbObj->executeTransaction($sql); 
}

?>
</body>
</html>