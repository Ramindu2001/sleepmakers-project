<?php 
session_start();
//include "../Includes/includes.php";
include "../Includes/config.php";
include '../Model/DB_Class.php';
include '../Model/user_class.php';
include '../Model/shop_class.php';

include '../Includes/authcheck.php';

$shop_id = $_SESSION['shop_id'];
$invoice_id = 60;
if(isset($_POST['invoice_id']))
{
    $invoice_id = $_POST['invoice_id'];
}
else
{
    header("Location: ../Public/delivery_note.php");
}
$array = [61, 62, 63, 64, 65, 66];
$x=1;

    ?>
    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synnex Receipts</title>
    <?php 
    include "../View/head.php";
    ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900&display=swap');
        *{
            box-sizing: border-box;
            font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif !important;
            color: black !important;
        }
        .body
        {
            width: 148mm;
            height: 100mm;
            border: 1px solid black;
            margin: 5px;
        }
        #tbl_date{
            border-collapse:collapse;
        }
        #tbl_date td{ 
            border: 1px solid black;
            padding: 1px;
        }
        .urgent
        {
            font-size: 1rem;
            font-weight: 700;
            font-family: "Merriweather", serif !important;
            border: 1px solid black;
            padding: 5px;
        }
        th {
            padding: 1px;
            border: 1px solid black;
        }
        #div_to{
            border: 1px black solid;
            padding: 5px;
        }
        #div_address{
            border: 1px black solid;
            padding: 5px;
        }
        #div_footer{
            width: 105mm;
            border: 1px solid black;
        }
        #div_footer_data{
            display: flex;
        }
        p 
        {
            margin: 0 !important;
        }
        td p 
        {
            font-size: 10px;
        }
    </style>
</head>
    <body>
<?php
$printCount=1;


    ?>
        <div class="body">
    <?php 
     $dbObj = new DBTransactions();
     $sql = "SELECT * FROM invoiceheader 
     INNER JOIN customers ON customers.CTID = invoiceheader.customers_CTID
     WHERE IHID = ".$_POST["invoice_id"][0].";";

     $invData = $dbObj->getData($sql);
     $cust_name = $invData[0]['CustName'];
     $cust_contact = $invData[0]['CustContact'];
     $cust_address = $invData[0]['CustAddress'];
     $remark = $invData[0]['remarks'];
     $date = $invData[0]['EffectiveDate'];

     $sql = "SELECT * FROM shop WHERE SHID = ".$shop_id.";";

     $shopData = $dbObj->getData($sql);

     //get COD
     $cod=0;
     foreach ($_POST["invoice_id"] AS $invoice_id)
    {
     $sql_1 = "SELECT * FROM transactions 
     INNER JOIN invoiceheader ON invoiceheader.IHID = transactions.InvoiceHeader_IHID
     WHERE InvoiceHeader_IHID = ".$invoice_id." AND paymethod_PMID = 10;";

     $transData = $dbObj->getData($sql_1);
     $cod = $cod + empty($transData[0]['TransferAmount']) ? 0 : $transData[0]['TransferAmount'];

    }
     $shopObj = new Shop();
     $shopData = $shopObj->getOneShop($shop_id);
     $receipt_logo = $shopData[0]['ReceiptLogo'];
    ?>
    <div class="row d-flex" style="height:90mm;">
        <div class="col-md-4" style="width:33.33%;">
            <img src="../Assets/Images/shop_images/SL_000002.png" alt="" class="w-100">
        </div>
        <div class="col-md-4" style="width:33.33%;">
            <h3 class="urgent">VERY URGENT!!!</h3>
        </div>
        <div class="col-md-4" style="width:33.33%;  height: 65px;">
            <h2><u> Delivery Note</u></h2>
            <table id="tbl_date">
                <tr>
                    <td><b>Date</b></td>
                    <td><?php echo $date;?></td>
                </tr>
                <tr>
                    <td><b>COD</b></td>
                    <td><?php echo "LKR: " . $cod;?></td>
                </tr>
            </table>
        </div>
        <div class="col-md-6 d-flex" style="width:100%;">
            <p style="padding-left:5px;">
              <?=$shopData[0]["AddressLineOne"]?><br>
              <?=$shopData[0]["AddressLineTwo"]?><br>
              <!--<?=$shopData[0]["City"]?><br>-->
              <?=$shopData[0]["PhoneNumber"]?><br>  
            </p>
        </div>
        <div class="col-md-6" style="width:50%;">
            <table id="tbl_date">
                <tr>
                    <th>Invoice No</th>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
                <?php 
                foreach ($_POST["invoice_id"] AS $invoice_id)
                {
                    $sql = "SELECT * FROM invoiceheader 
                    INNER JOIN customers ON customers.CTID = invoiceheader.customers_CTID
                    WHERE IHID = ".$invoice_id.";";
               
                    $invData = $dbObj->getData($sql);
                    $cust_name = $invData[0]['CustName'];
                    $cust_contact = $invData[0]['CustContact'];
                    $cust_address = $invData[0]['CustAddress'];
                    $remark = $invData[0]['remarks'];
                    $date = $invData[0]['EffectiveDate'];

                    $sql = "SELECT * FROM invoicedetails 
                    INNER JOIN products ON products.PDID = invoicedetails.products_PDID
                    WHERE InvoiceHeader_IHID = ".$invoice_id.";";

                    $detailData = $dbObj->getData($sql);
                    $count=count($detailData);
                        ?>
                        <tr>
                            <td><?php echo $invData[0]['BillNo'];?></td>
                            <td>
                                <?php 
                                    echo $invData[0]['remarks'] . "<br>";
                                ?>
                            </td>
                            <td ><?php echo $invData[0]['NetAmount'];?></td>
                        </tr>
                        <?php
                }
                ?>
                    
            </table>
        </div>
        <div class="col-md-6" style="width:50%; margin-top:-70px;">
            <div id="div_to">
                <p>TO: </p>
            </div>
            <div id="div_address">
                <p>
                    <?php echo $cust_name;?><br>
                    <?php echo $cust_contact;?><br>
                    <?php echo $cust_address;?>
                </p>
            </div>
        </div>
    </div>
    <div style="width:100%; padding:5px; border:1px solid black;">
        <div class="d-flex">
            <div class="div_footer_items" style="width:33%;">
                INV BY:
            </div>
            <div class="div_footer_items" style="width:33%;">
                QC BY:          
            </div>
            <div class="div_footer_items" style="width:33%;">
                PACKED BY:           
            </div>
        </div>
    </div>
    </div>
    <?php
?>
</body>

</html>
    <?php

?>

<script>
            window.print();
            setTimeout(function(){
                window.history.back();               
            },500)
        </script>