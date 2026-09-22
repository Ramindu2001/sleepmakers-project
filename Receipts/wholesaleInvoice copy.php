<?php  
include '../Includes/includes.php';
$invoice = $_GET["invoice"];
?>
<script>
        window.print();
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
            else
            {
                ?>
                // window.location="../Public/wholesale-invoice.php";
                <?php
            }
                
            ?>
        },1000);
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
        }
        /* div{
            border: 1px solid blue;
        } */

        #div_header{
            display: flex;
            flex-direction: row;
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
    ?>
    <div class="row" id="div_header">
        <div id="div_image">
            <img class="w-100" src="../Assets/Images/shop_images/<?=$receipt_logo_name?>" alt="shop logo" id="img_receipt">
        </div>
        <div>
            <h1 id="company_name"><?=$shop_name?></h1>
            <h6 id="company_detail">
                <?=$address_1?>  <br>
                <?=$address_2?> <br>
                Contact: <?=$contact?> 
            </h6>
        </div>
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

                //print date time
                date_default_timezone_set("Asia/Colombo");
                $print_date = date("Y-m-d");
                $print_time = date("H:i:s");
                ?>
            <h2 style="text-align:right; margin:5px;">INVOICE</h2>
            <h5 style="text-align:right; margin:5px;"><?php if($print_count>1){ echo "Duplicate Print ".$print_count;}?></h5>
            <h5 style="text-align:right;" id="inv_detail">
                Date: <?php echo $date;?><br>
                Invoice No: <?php echo $BillNo;?>
            </h5>
        </div>
    </div>
    
    <hr>
    <div id="div_tenant">
        <table>
            <tr>
                <td colspan="2">Customer To</td>
            </tr>
            <tr>
                <td style="text-align: right;">Name:- </td>
                <td><?php echo $cus_name;?></td>
            <tr>
                <td style="text-align: right;">Contact:- </td>
                <td><?php echo $cus_contact;?></td>
            </tr>
            <tr>
                <td style="text-align: right;">Address:- </td>
                <td><?php echo $CustAddress;?></td>
            </tr>
        </table>        
    </div>

    <!-- rent detail -->
    <div>
        <table id="tbl_rent_detail">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Item Name</th>
                    <th>Unit Price</th>
                    <th>Qty</th>
                    <th>Discount</th>
                    <th>Amount</th>
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
                        <td style="text-align: center;"><?=$row["UnitPrice"]?></td>
                        <td style="text-align: center;"><?=$row["SellQty"]?></td>
                        <td style="text-align: center;"><?=$ldiscount?></td>
                        <td style="text-align: right;"><?=$row["SoldAmount"]?></td>
                    </tr>
                    <?php
                    $sl++;
                }

                ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align: right;"><b>Gross Total</b></td>
                    <td style="text-align: right;"><?=number_format((float)$GrossAmount, 2, '.', ',');?></td>
                </tr>
                <tr>
                    <td colspan="5" style="text-align: right;"><b>Total Discount</b></td>
                    <td style="text-align: right;"><?=number_format((float)$DiscountAmount, 2, '.', ',');?></td>
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
                    WHERE t.InvoiceHeader_IHID= '$invoice';";

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
                ?>
                <tr>
                    <td colspan="5" style="text-align: right;"><b>Net Total</b></td>
                    <td style="text-align: right;"><?=number_format((float)$NetAmount, 2, '.', ',');?></td>
                </tr>
                <?php 
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

    <!-- footer -->
    <div>
        <p id="p_footer">
            <?=$remarks?>
        </p>
        <p id="p_footer" style="font-family: monospace; font-size: 11px;">Thank You For Shopping!</p>
        <p id="p_footer" style="font-family: monospace; font-size: 11px;">Powered By: Synnex IT Solution PVT (Ltd)</p>
    </div>


    <?php 
    $newprint_count=$print_count+1;
    $sql = "UPDATE `invoiceheader` SET `print_count`='$newprint_count' WHERE IHID= '$invoice';";
    $update = $dbObj->executeTransaction($sql);
    ?>
</body>
</html>