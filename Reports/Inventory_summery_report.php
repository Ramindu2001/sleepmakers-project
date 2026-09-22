<?php 
// require_once '../Includes/includes.php';

include "../Includes/includes.php";
require_once '../Includes/authcheck.php';

$shopObj = new Shop();
$shop_id = $_SESSION['shop_id'];
?>

<!doctype html>
<html lang="en">

<head>
  <?php 
  require_once '../View/head.php';
  require_once '../View/loader.php';
//   require_once '../View/datatables.php';
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
                <input type="hidden" name="" id="title" value="Inventory Summary Report">
            
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Inventory Summary</h5>
                    </div>
                    <div class="card-body">
                        <table id="tbl_inventory_summary">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Item</th>
                                    <th>Current Quantity</th>
                                    <th>Sold Qty</th>
                                    <th>Return Qty</th>
                                    <th>Transfer In Qty</th>
                                    <th>Transfer Out Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $dbObj = new DBTransactions();

                                if($shopObj->hasMinus($shop_id))
                                {
                                    $sql = "SELECT distinct products_PDID, Barcode, ItemName FROM inventory
                                    INNER JOIN products ON products.PDID = inventory.products_PDID
                                    WHERE inventory.shop_SHID = ".$shop_id.";";
                                }//has minus
                                else
                                {
                                    $sql = "SELECT distinct products_PDID, Barcode, ItemName FROM inventory
                                    INNER JOIN products ON products.PDID = inventory.products_PDID
                                    WHERE inventory.shop_SHID = ".$shop_id." AND CurrentQty>0;";
                                }//else

                                $row_count = 0;
                                $invData = $dbObj->getData($sql);
                                foreach($invData as $row)
                                {
                                    $row_count += 1;
                                    $product_id = $row['products_PDID'];
                                    $barcode = $row['Barcode'];
                                    $item_name = $row['ItemName'];

                                    if($shopObj->hasMinus($shop_id))
                                    {
                                        $sql_1 = "SELECT SUM(CurrentQty) AS totalCurrent, sum(BillQty) as totalBill, sum(ReturnQty) as totalReturn, sum(TransferInQty) as totalTransferIn, sum(TransferOutQty) as totalTransferOut FROM inventory 
                                        WHERE products_PDID= ".$product_id." AND  shop_SHID = ".$shop_id.";";

                                        $detailData = $dbObj->getData($sql_1);

                                        $total_current_qty = $detailData[0]['totalCurrent'] + 0;
                                        $total_bill_qty = $detailData[0]['totalBill'];
                                        $total_return_qty = $detailData[0]['totalReturn'];
                                        $total_transfer_in_qty = $detailData[0]['totalTransferIn'];
                                        $total_transfer_out_qty = $detailData[0]['totalTransferOut'];
                                        ?>
                                        <tr>
                                            <td><?php echo $row_count;?></td>
                                            <td><?php echo $barcode . "<br>" . $item_name;?></td>
                                            <td><?php echo $total_current_qty;?></td>
                                            <td><?php echo $total_bill_qty;?></td>
                                            <td><?php echo $total_return_qty;?></td>
                                            <td><?php echo $total_transfer_in_qty;?></td>
                                            <td><?php echo $total_transfer_out_qty;?></td>
                                        </tr>
                                        <?php 

                                    }//has minus
                                    else
                                    {
                                        $sql_1 = "SELECT SUM(CurrentQty) AS totalCurrent, sum(BillQty) as totalBill, sum(ReturnQty) as totalReturn, sum(TransferInQty) as totalTransferIn, sum(TransferOutQty) as totalTransferOut FROM inventory 
                                        WHERE products_PDID= ".$product_id." AND  shop_SHID = ".$shop_id." AND CurrentQty>0;";

                                        $detailData = $dbObj->getData($sql_1);

                                        $total_current_qty = $detailData[0]['totalCurrent'] + 0;
                                        $total_bill_qty = $detailData[0]['totalBill'];
                                        $total_return_qty = $detailData[0]['totalReturn'];
                                        $total_transfer_in_qty = $detailData[0]['totalTransferIn'];
                                        $total_transfer_out_qty = $detailData[0]['totalTransferOut'];
                                        ?>
                                        <tr>
                                            <td><?php echo $row_count;?></td>
                                            <td><?php echo $barcode . "<br>" . $item_name;?></td>
                                            <td><?php echo $total_current_qty;?></td>
                                            <td><?php echo $total_bill_qty;?></td>
                                            <td><?php echo $total_return_qty;?></td>
                                            <td><?php echo $total_transfer_in_qty;?></td>
                                            <td><?php echo $total_transfer_out_qty;?></td>
                                        </tr>
                                        <?php 
                                    }
                                }//foreach 1
                                ?>
                            </tbody>

                            <!-- footer -->
                             <tfoot>
                                <tr>
                                    <th colspan="6" style="text-align:right">Total:</th>
                                    <th></th>
                                </tr>
                             </tfoot>
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
    <!-- <script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="../Assets/js/dashboard.js"></script> -->

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

                layout:{
                    topStart:{
                        buttons:[
                            //====================== pdf
                            {
                                extend: 'pdf',
                                customize: function (doc){
                                    doc.content.splice(0,1,{
                                        text: [
                                            {text: shop_name+"\n", bold: true, fontSize: 16},
                                            {text: shop_address_one+",\n", bold: true, fontSize: 12},
                                            {text: shop_address_two+",\n", bold: true, fontSize: 12},
                                            {text: shop_number+",\n", bold: true, fontSize: 12},
                                            {text: reportname+",\n", bold: true, fontSize: 14},
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
                                    '<p style="margin: 5px; font-size: 18px;">'+ reportname +'</p>' + //report name
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
                footerCallback: function (row, data, start, end, display) {
                    let api = this.api();
            
                    // Remove the formatting to get integer data for summation
                    let intVal = function (i) {
                        return typeof i === 'string'
                            ? i.replace(/[\$,]/g, '') * 1
                            : typeof i === 'number'
                            ? i
                            : 0;
                    };
            
                    // Total over all pages
                    total = api
                        .column(2)
                        .data()
                        .reduce((a, b) => intVal(a) + intVal(b), 0);
            
                    // Total over this page
                    pageTotal = api
                        .column(2, { page: 'current' })
                        .data()
                        .reduce((a, b) => intVal(a) + intVal(b), 0);
            
                    // Update footer
                    api.column(6).footer().innerHTML = pageTotal + '(' + total + ' total)';
                }
            }); 
        });//jquery
    </script>
</body>
</html>
