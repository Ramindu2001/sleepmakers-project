<?php 
// require_once '../Includes/includes.php';

include "../Includes/includes.php";
require_once '../Includes/authcheck.php';

$shopObj = new Shop();
$shop_id = $_SESSION['shop_id'];

 //get current date time
 date_default_timezone_set("Asia/Colombo");

 $start_date = "";
 $end_date = "";
 if(isset($_GET['date']))
 {
   $date = explode("_", $_GET['date']);
   $start_date = $date[0];
   $end_date = $date[1];
 }//date set
 else
 {
   $start_date = date("Y-m-d");
   $end_date = date("Y-m-d");
 }//date not set

?>

<!doctype html>
<html lang="en">

<head>
  <?php 
  require_once '../View/head.php';
  require_once '../View/loader.php';
  ?>

  <link rel="stylesheet" href="../Assets/css/datatables.min.css">
  <script src="../Assets/js/datatables.min.js"></script>

</head>
<body>
<!--  Body Wrapper -->
<div class="h-100vh">
<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    <!-- Sidebar Start -->
    <?php 
    require_once '../View/sidebar.php';
    $feature_id=38;
    include '../Includes/viewPermission.php';
    if($userType==1 || $print==1)
    {
        
    }
    else
    {
        ?>
        <script>
            setInterval(function(){
                $(".dt-buttons").addClass("d-none");  
            }, 100);
            
        </script>
        <?php
    }
    ?>
    <!--  Sidebar End -->
    <!--  Main wrapper -->
    <div class="body-wrapper">
        <?php 
        require_once '../View/header.php';
        ?>
        <div class="container-fluid">
           
            <div class="container-fluid">
                <?php 
                $shop = $shopObj->getOneShop($shop_id);
                ?>

                <input type="hidden" name="" id="shop_name" value="<?=$shop[0]['ShopName']?>">
                <input type="hidden" name="" id="shop_address_one" value="<?=$shop[0]['AddressLineOne']?> ">
                <input type="hidden" name="" id="shop_address_two" value="<?=$shop[0]['AddressLineTwo']?>">
                <input type="hidden" name="" id="shop_city" value="<?=$shop[0]['City']?>">
                <input type="hidden" name="" id="shop_number" value="<?=$shop[0]['PhoneNumber']?> ">
                <input type="hidden" name="" id="title" value="Stock Movement Report">
            
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Stock Movement Report</h5>
                    </div>
                    <div class="card-body">
                        <div class="container">
                            <form action="../Controller/ReportController.php" method="POST" class="form-inline">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="" class="form-label">Start Date</label>
                                        <input type="date" name="start_date" id="start_date" value="<?php echo $start_date;?>" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="" class="form-label">End Date</label>
                                        <input type="date" name="end_date" id="end_date" value="<?php echo $end_date;?>" class="form-control">
                                    </div>
                                    <div class="col-md-2">
                                        <button class="btn btn-success mt-4" name="btn_stock_movement" id="btn_stock_movement">Find</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <table id="tbl_inventory_summary">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Barcode</th>
                                    <th>Item Name</th>
                                    <th>Transaction Type</th>
                                    <th>Quantity In</th>
                                    <th>Quantity Out</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $dbObj = new DBTransactions();

                                $sql = "SELECT distinct products_PDID, Barcode, ItemName FROM inventory
                                    INNER JOIN products ON products.PDID = inventory.products_PDID
                                    WHERE inventory.shop_SHID = ".$shop_id." AND CurrentQty>0;";

                                $row_count = 0;
                                $invData = $dbObj->getData($sql);
                                foreach($invData as $row)
                                {
                                    $row_count += 1;
                                    $product_id = $row['products_PDID'];
                                    $barcode = $row['Barcode'];
                                    $item_name = $row['ItemName'];

                                    //inventory data
                                    $sql = "SELECT sum(CurrentQty) as current_qty, sum(BillQty) as bill_qty, sum(ReturnQty) as return_qty, sum(TransferInQty) as transfer_in, sum(TransferOutQty) as transfer_out, sum(Sup_Rtn) as sup_return_qty
                                    FROM pricehistory
                                    INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
                                    WHERE products_PDID = ".$product_id." AND inventory.shop_SHID = ".$shop_id.";";
                                    
                                    $invData = $dbObj->getData($sql);
                                    $current_qty = floatval($invData[0]['current_qty']);
                                    $balance_qty = $current_qty;
                                    
                                    //grn qty
                                    $sql_1 = "SELECT products.Barcode, products.ItemName, sum(grndetails.InitQty) as init_qty
                                    FROM `grndetails`
                                    INNER JOIN grnheader ON grnheader.GHID = grndetails.GRNHeader_GHID
                                    INNER JOIN products ON products.PDID = grndetails.products_PDID
                                    WHERE grnheader.shop_SHID = ".$shop_id." AND grndetails.products_PDID = ".$product_id." AND grnheader.EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."' AND grnheader.GRNStat = 2;";

                                    //----------------------------- GRN ----------------------------//
                                    $grnData = $dbObj->getData($sql_1);
                                    if(!empty($grnData))
                                    {
                                        $purchase_qty = floatval($grnData[0]['init_qty']);
                                        if($purchase_qty > 0)
                                        {
                                            ?>
                                            <tr>
                                                <td><?php echo $row_count;?></td>
                                                <td><?php echo $barcode;?></td>
                                                <td><?php echo $item_name;?></td>
                                                <td>Purchase</td>
                                                <td><?php echo $purchase_qty;?></td>
                                                <td>-</td>
                                                <td><?php echo $current_qty;?></td>
                                            </tr>
                                            <?php 
                                        }
                                    }//grn stock in

                                    //---------------------------  sale qty ------------------------//
                                    $sql_2 = "SELECT sum(SellQty) as sold_qty FROM invoicedetails
                                    INNER JOIN invoiceheader ON invoiceheader.IHID = invoicedetails.InvoiceHeader_IHID
                                    WHERE products_PDID = ".$product_id." AND invoiceheader.shop_SHID = ".$shop_id." AND invoiceheader.EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."' AND invoiceheader.InvStat = 1;";

                                    $saleData = $dbObj->getData($sql_2);

                                    if(!empty($saleData))
                                    {
                                        $sold_qty = floatval($saleData[0]['sold_qty']);
                                        if($sold_qty > 0)
                                        {
                                            $balance_qty = $current_qty - $sold_qty;
                                            ?>
                                            <tr>
                                                <td><?php echo $row_count;?></td>
                                                <td><?php echo $barcode;?></td>
                                                <td><?php echo $item_name;?></td>
                                                <td>Sale</td>
                                                <td>-</td>
                                                <td><?php echo $sold_qty;?></td>
                                                <td><?php echo $balance_qty;?></td>
                                            </tr>
                                            <?php 
                                        }//has qty
                                    }//has sold

                                    //-------------------------- Transfer Qty ------------------------//
                                    $sql_3 = "SELECT sum(transferdetails.TransferQty) as transfer_out_qty, sum(transferdetails.ReceivedQty) as transfer_in_qty FROM transferdetails
                                    INNER JOIN transferheader ON transferheader.THID = transferdetails.TransferHeader_THID
                                    WHERE products_PDID = ".$product_id." AND transferheader.TransferTo = ".$shop_id." AND transferheader.EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."' AND transferheader.TransferStat = 2;";

                                    $sql_4 = "SELECT sum(transferdetails.TransferQty) as transfer_out_qty, sum(transferdetails.ReceivedQty) as transfer_in_qty FROM transferdetails
                                    INNER JOIN transferheader ON transferheader.THID = transferdetails.TransferHeader_THID
                                    WHERE products_PDID = ".$product_id." AND transferheader.TransferFrom = ".$shop_id." AND transferheader.EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."' AND transferheader.TransferStat = 2;";
                                    
                                    $transinData = $dbObj->getData($sql_3);
                                    $transoutData = $dbObj->getData($sql_4);

                                    if(!empty($transinData) OR !empty($transoutData))
                                    {
                                        $transfer_in_qty = floatval($transinData[0]['transfer_in_qty']);
                                        $transfer_out_qty = floatval($transoutData[0]['transfer_out_qty']);

                                        if($transfer_in_qty > 0 OR $transfer_out_qty > 0)
                                        {
                                            $balance_qty = $balance_qty + $transfer_in_qty - $transfer_out_qty;
                                            ?>
                                            <tr>
                                                <td><?php echo $row_count;?></td>
                                                <td><?php echo $barcode;?></td>
                                                <td><?php echo $item_name;?></td>
                                                <td>Transfer</td>
                                                <td><?php echo $transfer_in_qty;?></td>
                                                <td><?php echo $transfer_out_qty;?></td>
                                                <td><?php echo $balance_qty;?></td>
                                            </tr>
                                            <?php 
                                        }//has qty
                                    }//has data

                                    //-------------------------- Adjustment ---------------------------//
                                    //adjust in
                                    $sql_5 = "SELECT SUM(adjustproddetails.AdjustProdQty) AS adjust_qty FROM adjustproddetails 
                                    INNER JOIN adjustheader ON adjustheader.AHID = adjustproddetails.AdjustHeader_AHID
                                    WHERE products_PDID = ".$product_id." AND adjustheader.shop_SHID = ".$shop_id." AND adjustheader.EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."' AND adjustheader.AdjustStat = 2 AND AdjustmentType_ITID = 1;";

                                    //adjust out
                                    $sql_6 = "SELECT SUM(adjustproddetails.AdjustProdQty) AS adjust_qty FROM adjustproddetails 
                                    INNER JOIN adjustheader ON adjustheader.AHID = adjustproddetails.AdjustHeader_AHID
                                    WHERE products_PDID = ".$product_id." AND adjustheader.shop_SHID = ".$shop_id." AND adjustheader.EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."' AND adjustheader.AdjustStat = 2 AND AdjustmentType_ITID = 2;";

                                    $adjustinData = $dbObj->getData($sql_5);
                                    $adjustoutData = $dbObj->getData($sql_6);

                                    if(!empty($adjustinData) OR !empty($adjustoutData))
                                    {
                                        $adjust_in_qty =  floatval($adjustinData[0]['adjust_qty']);
                                        $adjust_out_qty = floatval($adjustoutData[0]['adjust_qty']);

                                        if($adjust_in_qty > 0 OR $adjust_out_qty > 0)
                                        {
                                            $balance_qty = $balance_qty + $adjust_in_qty - $adjust_out_qty;
                                            ?>
                                            <tr>
                                                <td><?php echo $row_count;?></td>
                                                <td><?php echo $barcode;?></td>
                                                <td><?php echo $item_name;?></td>
                                                <td>Adjustment</td>
                                                <td><?php echo $adjust_in_qty;?></td>
                                                <td><?php echo $adjust_out_qty;?></td>
                                                <td><?php echo $balance_qty;?></td>
                                            </tr>
                                            <?php
                                        }//has qty
                                    }//adjust has

                                    //------------------------------- Return -----------------------------//
                                    $sql_7 = "SELECT SUM(ReturnQty) AS return_qty FROM returndetails
                                    INNER JOIN retrun_invoice_header ON retrun_invoice_header.RIHID = returndetails.ReturnHeader_RHID
                                    WHERE products_PDID = ".$product_id." 
                                    AND retrun_invoice_header.shopID = ".$shop_id."
                                    AND retrun_invoice_header.EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."';";

                                    $returnData = $dbObj->getData($sql_7);

                                    if(!empty($returnData))
                                    {
                                        $return_qty = floatval($returnData[0]['return_qty']);
                                        if($return_qty > 0)
                                        {
                                            $balance_qty = $balance_qty + $return_qty;
                                            ?>
                                            <tr>
                                                <td><?php echo $row_count;?></td>
                                                <td><?php echo $barcode;?></td>
                                                <td><?php echo $item_name;?></td>
                                                <td>Return</td>
                                                <td><?php echo $return_qty;?></td>
                                                <td>-</td>
                                                <td><?php echo $balance_qty;?></td>
                                            </tr>
                                            <?php
                                        }//has qty
                                    }//has return

                                    //-------------------------------- Supplier Return --------------------------------//
                                    $sql_8 = "SELECT sum(ReturnQty) as sup_return_qty FROM `supplierreturndetails`
                                    INNER JOIN supplierreturn ON supplierreturn.SRID = supplierreturndetails.supplierreturn_SRID
                                    WHERE ProductID = ".$product_id."
                                    AND supplierreturn.shop_SHID = ".$shop_id."
                                    AND supplierreturn.EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."';";

                                    $supData = $dbObj->getData($sql_8);
                                    if(!empty($supData))
                                    {
                                        $sup_return_qty = floatval($supData[0]['sup_return_qty']);
                                        if($sup_return_qty > 0)
                                        {
                                            $balance_qty = $balance_qty - $sup_return_qty;
                                            ?>
                                            <tr>
                                                <td><?php echo $row_count;?></td>
                                                <td><?php echo $barcode;?></td>
                                                <td><?php echo $item_name;?></td>
                                                <td>Supplier Return</td>
                                                <td>-</td>
                                                <td><?php echo $sup_return_qty;?></td>
                                                <td><?php echo $balance_qty;?></td>
                                            </tr>
                                            <?php
                                        }//has qty

                                    }//has supplier return

                                }//foreach 1
                                ?>
                            </tbody>

                            <!-- footer -->
                             <!-- <tfoot>
                                <tr>
                                    <th colspan="6" style="text-align:right">Total:</th>
                                    <th></th>
                                </tr>
                             </tfoot> -->
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php require_once '../View/footer.php';?>

    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>

    <script>
        $(document).ready(function(){
            var shop_name = $("#shop_name").val();
            var shop_address_one = $('#shop_address_one').val();
            var shop_address_two = $('#shop_address_two').val();
            var shop_city = $('#shop_city').val();
            var shop_number = $('#shop_number').val();
            var reportname=$("#title").val();

            $("#tbl_inventory_summary").DataTable({
               
                paging: true,
                lengthChange: true,
                searching: true,
                //pageLength: 10,

                layout:{
                    topStart:{
                        buttons:[
                            //====================== PDF
                            {
                                extend: 'pdf',
                                customize: function (doc){
                                    doc.content.splice(0,1,{
                                        text: [
                                            {text: shop_name + "\n", bold: true, fontSize: 16},
                                            {text: shop_address_one + ",\n", bold: true, fontSize: 12},
                                            {text: shop_address_two + ",\n", bold: true, fontSize: 12},
                                            {text: shop_number + ",\n", bold: true, fontSize: 12},
                                            {text: reportname + ",\n", bold: true, fontSize: 14},
                                        ]
                                    }) 
                                },
                                download: 'open'
                            },
                            //===================== print
                            {
                                extend: 'print',
                                title: '',
                                customize: function(win){
                                    var formattedCity = shop_city.charAt(0).toUpperCase() + shop_city.slice(1).toLowerCase();
                    
                                    $(win.document.body).css('font-size', '10pt').prepend(
                                    '<div style="text-align: center; margin-bottom: 20px;">' +
                                    '<p style="margin: 5px; font-size: 24px; font-weight: bold;">' + shop_name + '</p>' +
                                    '<p style="margin-bottom: 5px;">' + shop_address_one + ', ' + shop_address_two + '</p>' +
                                    '<p style="margin-bottom: 5px;">' + formattedCity + '</p>' +  // Reduced margin
                                    '<p style="margin-bottom: 5px;">' + shop_number + '</p>' +  // Reduced margin
                                    '<p style="margin: 5px; font-size: 18px; text-align:center;">'+ reportname +'</p>' + //report name
                                    '</div>'
                                    );
                                }
                            }, 
                            //===================== excel
                            {
                                extend: 'excel',
                                title: reportname,
                            },
                            //====================== csv
                            {
                                extend: 'csv',
                                title: reportname,
                            }
                        ]
                    } 
                },//layouts
                
                //get total
                // footerCallback: function (row, data, start, end, display) {
                //     let api = this.api();
            
                //     // Remove the formatting to get integer data for summation
                //     let intVal = function (i) {
                //         return typeof i === 'string'
                //             ? i.replace(/[\$,]/g, '') * 1
                //             : typeof i === 'number'
                //             ? i
                //             : 0;
                //     };
            
                //     Total over all pages
                //     total = api
                //         .column(6)
                //         .data()
                //         .reduce((a, b) => intVal(a) + intVal(b), 0);

                //     total = total.toFixed(2);
            
                //     // Total over this page
                //     pageTotal = api
                //         .column(6, { page: 'current' })
                //         .data()
                //         .reduce((a, b) => intVal(a) + intVal(b), 0);
            
                //     // Update footer
                //     api.column(6).footer().innerHTML = pageTotal + '(' + total + ')';
                // }
            }); 
        });//jquery
    </script>
</body>
</html>
