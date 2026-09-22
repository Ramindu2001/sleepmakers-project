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
            justify-content: space-between;
        }
        /*----  table ---- */
        table{
            border-collapse: collapse;
        }
        th{
            border: 1px black solid;
            font-size: 15px;
        }
        td{
            padding: 5px;
            border: 1px solid black;
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
        $sql = "SELECT * FROM adjustheader
        INNER JOIN shop ON shop.SHID = adjustheader.shop_SHID
        WHERE AHID = ".$header_id.";";

        $headData  = $dbObj->getData($sql);

        $shop_logo_name = $headData[0]['ShopLogo'];
        $adjust_no = $headData[0]['AdjustNo'];
        $shop_name = $headData[0]['ShopName'];
        $adjust_date = $headData[0]['EffectiveDate'];
        $adjust_type = $headData[0]['AdjustmentType_ITID'];

        //user data
        $sql_1 = "SELECT * FROM user WHERE USID = ".$user_id.";";

        $userData = $dbObj->getData($sql_1);
        $user_name = $userData[0]['UserName'];

        //print date time
        date_default_timezone_set("Asia/Colombo");
        $print_date = date("Y-m-d");
        $print_time = date("H:i:s");

        ?>
        <div id="div_top_row">
            <div>
                <img src="../Assets/Images/shop_images/<?php echo $shop_logo_name;?>" alt="shop logo" id="img_receipt">
            </div>
            <div>
                <h2 style="margin-right: 20px; margin-bottom: 2px;"><?php echo $shop_name;?></h2>
                <h5 style="margin-right: 20px; margin-top: 2px;">Adjustment Detail Report</h5>
            </div>
        </div>
        
        <p id="p_address">
            <?php echo $adjust_no." - ". $adjust_date;?>
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
                <th>Qty</th>
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
                <th>Total Amount</th>
            </tr>
            <?php 
            $sql = "SELECT * FROM adjustproddetails 
            INNER JOIN products ON products.PDID = adjustproddetails.products_PDID
            LEFT JOIN variations ON variations.VRID = adjustproddetails.VariationID
            WHERE AdjustHeader_AHID = ".$header_id.";";

            $grnData = $dbObj->getData($sql);
            $row_count = 0;
            $item_count = 0;
            $total_purchase_price = 0;
            $total_selling_price = 0;

            foreach($grnData as $row)
            {
                $total_purchase_price += floatval($row['AdjustProdAmount']);
                // $total_selling_price += floatval($row['TotalSellPrice']);
                $row_count += 1;
                $item_count += floatval($row['AdjustProdQty']);
                ?>
                <tr>
                    <td><?php echo $row_count;?></td>
                    <td><?php echo $row['Barcode'] ."<br>". $row['ItemName'];?></td>
                    <td><?php echo $row['AdjustProdQty'] + 0;?></td>
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
                    <td><?php echo $row['AdjustProdAmount'];?></td>
                </tr>
                <?php 
            }//foreach

            $total_purchase_price = number_format((float)$total_purchase_price, 2, '.', '');
            ?>
        </table>
    </div>

    <div id="div_summary">
        <table id="tbl_summary">
            <tr>
                <td>Row Count</td>
                <td style="text-align: right;"><?php echo $row_count;?></td>
            </tr>
            <tr>
                <td>Item Count</td>
                <td style="text-align: right;"><?php echo $item_count;?></td>
            </tr>
            <tr>
                <td>Total Purchase Price</td>
                <td style="text-align: right;"><?php echo $total_purchase_price;?></td>
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