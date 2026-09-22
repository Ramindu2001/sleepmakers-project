<?php 
include "../Includes/includes.php";
include '../Includes/authcheck.php';

$header_id = 0;
$shop_id = 0;
$user_id = $_SESSION['user_id'];

if(isset($_SESSION['shop_id']))
{
    $shop_id = $_SESSION['shop_id'];
}//assign shop id

if(isset($_GET['header_id']))
{
    $header_id = $_GET['header_id'];
}//assign invoice id

$dbObj = new DBTransactions();
$shopObj = new Shop();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synnex Cloud Reports</title>
    
    <style>
        *{
            box-sizing: border-box;
            font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
        }
        #img_receipt{
            height: 80px;
            width: auto;
            margin-left: 20px;
        }
        #p_address{
            text-align: center;
            font-size: 20px;
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
        /*------- top row -------*/
        #div_top_row{
            display: flex;
            justify-content: space-evenly;
        }
        /*----  table ---- */
        th{
            border: 1px black solid;
            font-size: 15px;
        }
        td{
            padding: 2px;
            border-bottom: 1px solid black;
        }
        #tbl_cart{
            width: 100%;
            border-collapse: collapse;
        }
     

        /*------ Totals ------- */
        #tbl_totals{
            width: 100%;
        }
        #div_summary{
            position: fixed;
            right: 10px;
            width: 50%;
        }
        #tbl_summary{
            width: 100%;
            justify-content: right;
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
            position: fixed;
            left: 10px;
            bottom: 10px;
        }
    </style>
</head>
<body onload="window.print()">

    <div>
        <?php 
        //get shop details
        $sql = "SELECT * FROM transferheader WHERE THID = ".$header_id.";";

        $headData  = $dbObj->getData($sql);
        $transfer_date = $headData[0]['EffectiveDate'];
        $transfer_stat = $headData[0]['TransferStat'];
        $transfer_stat_name = "";

        switch ($transfer_stat) {
            case '0':
                $transfer_stat_name = "Hold";
            break;
            case '1':
                $transfer_stat_name = "Pending";
            break;
            case '2':
                $transfer_stat_name = "Verified";
            break;
            case '3':
                $transfer_stat_name = "Cancled";
            break;
        }//switch

        $from_shop_id = $headData[0]['TransferFrom'];
        $to_shop_id = $headData[0]['TransferTo'];

        $fromData = $shopObj->getOneShop($from_shop_id);
        $toData = $shopObj->getOneShop($to_shop_id);

        $from_shop_name = $fromData[0]['ShopName'];
        $to_shop_name = $toData[0]['ShopName'];

        //user data
        $sql_1 = "SELECT * FROM user WHERE USID = ".$user_id.";";

        $userData = $dbObj->getData($sql_1);
        $user_name = $userData[0]['UserName'];

        //print date time
        date_default_timezone_set("Asia/Colombo");
        $print_date = date("Y-m-d");
        $print_time = date("H:i:s");

        ?>
        <div>
            <h2 style="text-align: center;">Transfer Detail Note</h2>
        </div>
        <div id="div_top_row">
            <div>
                <h3><?php echo $from_shop_name;?></h3>
            </div>
            <div>
                <h3>To</h3>
            </div>
            <div>
                <h3><?php echo $to_shop_name;?></h3>
            </div>
            <!-- <div>
                <h2 style="margin-right: 20px; margin-bottom: 2px;"><?php //echo $shop_name;?></h2>
                <h5 style="margin-right: 20px; margin-top: 2px;">GRN Detail Report</h5>
            </div> --> 
        </div>
        
        <p id="p_address">
            <?php echo "Transfer Status: " . $transfer_stat_name ." - ". $transfer_date;?>
        </p>

    </div>

    <hr>
    <p style="margin: 0px;">Printed by: <?php echo $user_name ." at ". $print_date ." - ". $print_time;?></p>
    <hr>

    <!---------------------------------------- Cart ---------------------------------->
    <div id="div_cart">
        <table id="tbl_cart">
            <tr>
                <th>No</th>
                <th>Item</th>
                <th>Transfer Qty</th>
                <th>Receive Qty</th>
                <th>Purchase Price</th>
                <th>Selling Price</th>
                <?php 
                if($shopObj->hasExpiry($shop_id))
                {
                    ?>
                    <th>Mnf Date</th>
                    <th>Exp Date</th>
                    <?php 
                }//has expire date
                ?>
                <th>Total Purchase</th>
            </tr>
            <?php 
            $sql = "SELECT * FROM transferdetails 
            INNER JOIN products ON products.PDID = transferdetails.products_PDID 
            WHERE TransferHeader_THID = ".$header_id.";"; 

            $transferData = $dbObj->getData($sql);
            $row_count = 0;
            $total_transfer = 0;
            $total_receive = 0;
            $total_purchase_price = 0;
            $total_selling_price = 0;

            foreach($transferData as $row)
            {
                $total_purchase_price += floatval($row['TransferTotalAmount']);
                $row_count += 1;
                $total_transfer += floatval($row['TransferQty']);
                $total_receive += floatval($row['ReceivedQty']);
                ?>
                <tr>
                    <td><?php echo $row_count;?></td>
                    <td><?php echo $row['Barcode'] ."<br>". $row['ItemName'];?></td>
                    <td><?php echo $row['TransferQty'] + 0;?></td>
                    <td><?php echo $row['ReceivedQty'] + 0;?></td>
                    <td><?php echo $row['UnitPurchasePrice'];?></td>
                    <td><?php echo $row['UnitSellingPrice'];?></td>
                    <?php 
                        if($shopObj->hasExpiry($shop_id))
                        {
                            ?>
                            <td><?php echo $row['MnfDate'];?></td>
                            <td><?php echo $row['ExpDate'];?></td>
                            <?php 
                        }//has expire date
                    ?>
                    <td><?php echo $row['TransferTotalAmount'];?></td>
                </tr>
                <?php 
            }//foreach
            ?>
        </table>
    </div>

    <div id="div_summary">
        <table id="tbl_summary">
            <tr>
                <td>Row Count</td>
                <td><?php echo $row_count;?></td>
            </tr>
            <tr>
                <td>Total Transfer</td>
                <td><?php echo $total_transfer;?></td>
            </tr>
            <tr>
                <td>Total Receive</td>
                <td><?php echo $total_receive;?></td>
            </tr>
            <tr>
                <td>Purchase Price</td>
                <td><?php echo $total_purchase_price;?></td>
            </tr>
        </table>
    </div>

    <!-------------------------- Footer -------------------------->
    <p id="p_footer">
        Powered by : Synnex IT Solution PVT(LTD)
    </p>
    <script>
        // $(document).ready(function() {
        //     var value = $("#return").val();
        //     if (value) 
        //     {
        //         JsBarcode("#barcode", value, {
        //             format: "CODE128",
        //             lineColor: "#000",
        //             width: 2,
        //             height: 50,
        //             displayValue: true
        //         });
        //     } else {
        //         alert("Please enter a value for the barcode.");
        //     };
        // });

        setTimeout(function(){
            history.back();
        }, 200);//go back
        
    </script>
</body>
</html>