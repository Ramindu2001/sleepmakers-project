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

if(isset($_GET['invoice']))
{
    $invoice_id = $_GET['invoice'];
}//assign invoice idQ
if(isset($_GET['invoice_id']))
{
    $invoice_id = $_GET['invoice_id'];
}//assign invoice id

$dbObj = new DBTransactions();

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
<!DOCTYPE html>
<html lang="en">
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
        /* ---------------- top -------------- */
        #div_top{
            margin-top: 10px;
            display: flex;
            flex-direction: row;
            justify-content: space-between;
        }
        #top_row{
            display: flex;
            flex-direction: row;
            justify-content: space-evenly;
        }
        #inv_header{
            font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
            margin: 60px 0 0 0;
        }
        .fist_polygon{
            height: 120px;
            width: 120px;
            aspect-ratio: 1;
            clip-path: polygon(0 0, 40px 0, 100% 100%, 80px 100%);
            background: #ffd11a;
            position: absolute;
            z-index: 10;
        }

        .cust_detail{
            height: 120px;
            width: 360px;
            aspect-ratio: 1;
            clip-path: polygon(0 0, 280px 0, 100% 100%, 80px 100%);
            background: #1a1400;
            z-index: 0;
            padding: 10px 0 0 120px;
        }

        .last_polygon{
            margin-top: 60px;
            height: 60px;
            width: 200px;
            background-color: #ffd11a;
            position: absolute;
            right: 8px;
            z-index: -10;
        }

        #p_customer{
            color: #ffffcc;
        }
        #second_row{
            width: 100%;
            height: 60px;
            background-color: #ffd11a;
            display: flex;
            flex-direction: row;
            justify-content: space-evenly;
        }
        #img_shop_logo{
            height: 50px;
            margin-top: 5px;
            margin-left: 50px;
            width: auto;
        }
        #p_shop_name{
            position: fixed;
            margin: 10px;
            font-size: 30px;
        }
        #p_invoice_details{
            margin-top: 8px;
        }
        #fifth_row{
            position: fixed;
            bottom: 0;
            height: 300px;
            width: 100%;
        }
        #div_sub_total{
            display: flex;
            flex-direction: row;
            width: 100%;
        }
        #div_total{
            display: flex;
            flex-direction: row;
            background-color: #d9d9d9;
            width: 100%;
            /* border: 5px #1a1400 solid; */
            z-index: -10;
        }
        .left_triangle{
            height: 150px;
            width: 100px;
            clip-path: polygon(0 0, 0 0,100% 100%,0 100%);
            background-color: #ffd11a;
            position: inherit;
            z-index: 10;
        }
        .pay_polygon{
            height: 150px;
            width: 400px;
            clip-path: polygon(0 0, 300px 0, 100% 100%, 100px 100%);
            background-color: #8c8c8c;
            position: inherit;
            margin-left: -70px;
            z-index: 10;
        }
        #p_payment{
            color: white;
            font-weight: 600;
            margin-left: 50px;
        }
        #tbl_bank_details{
            margin-left: 100px;
        }
        #p_invoice_total{
            font-size: 24px;
            font-weight: bold;
            margin: 5px;
        }
        #shop_detail{
            background-color: #ffd11a;
            padding: 2px 10px;
        }
        /* ------------------------------------ */

    </style>
</head>
<body onload="window.print();">

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
                $effective_date = $headData[0]['EffectiveDate'];
                $shop_name = $headData[0]['ShopName'];
                $shop_city = $headData[0]['City'];
                $shop_contact = $headData[0]['PhoneNumber'];
                $shop_email = $headData[0]['emailAddress'];
                $shop_logo = $headData[0]['ShopLogo'];
                $receipt_logo = $headData[0]['ReceiptLogo'];

                $customer_name = $headData[0]['CustName'];
                $customer_contact = $headData[0]['CustContact'];

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
            <div id="top_row">
                <div>
                    <h1 id="inv_header">INVOICE</h1>
                </div>
                <div>
                    <div class="fist_polygon"></div>
                    <div class="last_polygon"></div>
                    <div class="cust_detail">
                        <p id="p_customer">
                            <b>Invoice To:</b><br>
                            <?php echo $customer_name;?><br>
                            <?php echo $customer_contact;?>
                        </p>
                    </div>
                   
                </div>
            </div>
            <div id="second_row">
                <div>
                    <img src="../Assets/Images/shop_images/<?php echo $shop_logo;?>" alt="" id="img_shop_logo">
                    <!-- <p id="p_shop_name"><?php echo $shop_name;?></p> -->
                </div>
                <div>
                    <p id="p_invoice_details">Invoice No: <?php echo $bill_no . "<br>Date: " . $effective_date;?> </p>
                </div>
            </div>

            <div id="third_row">
                <div style="width: 100%; height: 40px;"></div>
                <div style="width: 100%; height: 30px; background-color:#1a1400; color:#ffffcc;">
                    <table style="width: 80%; margin-left:10%;">
                        <tr>
                            <th style="width: 8%;">No.</th>
                            <th>Item Description</th>
                            <th style="width: 15%; text-align:right;">Price</th>
                            <th style="width: 12%; text-align:center;">Qty.</th>
                            <th style="width: 15%; text-align:right;">Total</th>
                        </tr>
                    </table>
                </div>
            </div>

            <div id="fourth_row">
                <table style="width: 80%; margin-left:10%;">
                    <?php 
                    $sql = "SELECT * FROM invoicedetails
                    INNER JOIN products ON products.PDID = invoicedetails.products_PDID
                    WHERE InvoiceHeader_IHID = ".$invoice_id.";";

                    $invData = $dbObj->getData($sql);
                    $row_count = 0;
                    $inv_total = 0;
                    foreach($invData as $row)
                    {
                        $row_count +=1;
                        $item_name = $row['ItemName'];
                        $sold_qty = floatval($row['SellQty']);
                        $sold_amount = floatval($row['SoldAmount']);
                        $sold_amount = number_format($sold_amount, 2, '.', '');
                        $sold_unit_price = $sold_amount / $sold_qty;
                        $sold_unit_price = number_format($sold_unit_price, 2, '.', '');
                        $inv_total += floatval($row['SoldAmount']);
                        ?>
                        <tr>
                            <td style="width: 8%;"><?php echo $row_count;?></td>
                            <td><?php echo $item_name;?></td>
                            <td style="width: 15%; text-align:right;"><?php echo $sold_unit_price;?></td>
                            <td style="width: 12%; text-align:center;"><?php echo $sold_qty;?></td>
                            <td style="width: 15%; text-align:right;"><?php echo $sold_amount;?></td>
                        </tr>
                        <?php 
                    }//foreach
                    $inv_total = number_format($inv_total, 2, '.', '');
                    ?>
                </table>
            </div>
        
            <div id="fifth_row">
                <div id="div_sub_total">
                    <div style="width: 50%;">
                        <p style="margin-left: 40px;">
                            <span style="color: red;">Terms & conditions</span><br>
                            Exchange available within 7 days of puchase.<br>

                        </p>
                    </div>
                    <div style="width: 50%;">
                        <table style="width: 80%; font-size: 20px;">
                            <tr>
                                <td>Sub Total:</td>
                                <td><?php echo $inv_total;?></td>
                            </tr>
                            <tr>
                                <td>Tax:</td>
                                <td>0.00%</td>
                            </tr>
                        </table>   
                    </div>
                </div>
                <div id="div_total">
                    <div class="left_triangle"></div>
                    <div class="pay_polygon">
                        <p id="p_payment">Payment Info.</p>
                        <table id="tbl_bank_details">
                            <tr>
                                <td>Account No</td>
                                <td>1234 5678 9012</td>
                            </tr>
                            <tr>
                                <td>Account Name</td>
                                <td>Shop Name</td>
                            </tr>
                            <tr>
                                <td>Branch Name</td>
                                <td>Colombo 04</td>
                            </tr>
                        </table>
                    </div>
                    <div style="position: inherit; margin-left: 150px;">
                        <div>
                            <p id="p_invoice_total" style="text-align: center;">Total: <?php echo $inv_total;?></p>
                        </div>
                        <div>
                            <p style="text-align: center; margin-top: 60px;">
                                ----------------------------<br>
                                Authorized Person
                            </p>
                        </div>
                    </div>
                </div>
                <div id="shop_detail">
                    <p style="font-size: 18px; font-weight:bold;">
                        Phone: <?php echo $shop_contact;?> | <?php echo $shop_city;?> | <?php echo $shop_email?>
                        <br>Thank you for your business
                    </p>
                </div>
            </div>
        </div>

        <?php  
    }//for loop page breaker
    ?>
    

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