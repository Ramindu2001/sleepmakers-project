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
if (isset($_GET['date'])) {
    $date = explode("_", $_GET['date']);
    $start_date = $date[0];
    $end_date = $date[1];
} else {
    $start_date = date("Y-m-d");
    $end_date = date("Y-m-d");
}

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
                <input type="hidden" name="" id="title" value="Item Sales Report">
            
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Item Sales Report</h5>
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
                                        <button class="btn btn-success mt-4" name="btn_item_sales" id="btn_item_sales">Find</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <P class="text-danger" style="margin: 2px;">Item sales report does not include invoice discount.</P>

                        <table id="tbl_primary">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Item Name</th>
                                    <th>Qty</th>
                                    <th>Amount</th>
                                    <th>Avg Price</th>
                                    <th>Avg COGS</th>
                                    <th>Gross Margin</th>
                                    <th>Gross Margin %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $dbObj = new DBTransactions();

                                $sql = "SELECT products.PDID, products.Barcode, products.ItemName, sum(SellQty) as sell_qty, sum(SellAmount) as sell_amount FROM `invoicedetails`
                                INNER JOIN invoiceheader ON invoiceheader.IHID = invoicedetails.InvoiceHeader_IHID
                                INNER JOIN products ON products.PDID = invoicedetails.products_PDID
                                WHERE invoiceheader.shop_SHID = ".$shop_id." AND invoiceheader.EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."' GROUP BY products_PDID;";

                                $row_count = 0;
                                $sold_qty_total = 0;
                                $item_discount_total = 0;
                                $total_amount = 0;

                                $saleData = $dbObj->getData($sql);
                                foreach($saleData as $row)
                                {
                                    $row_count += 1;
                                    $barcode = $row['Barcode'];
                                    $item_name = $row['ItemName'];
                                    $sell_qty = floatval($row['sell_qty']);
                                    $sell_amount = floatval($row['sell_amount']);
                                    $sell_amount = number_format($sell_amount, 2, '.', '');
                                    $product_id = $row['PDID'];

                                    $avg_price = $sell_amount / $sell_qty;
                                    $avg_price = number_format($avg_price, 2, '.', '');

                                    $sql_1 = "SELECT AVG(PurchasePrice) AS avg_purchase FROM `pricehistory` 
                                    INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
                                    WHERE inventory.shop_SHID = ".$shop_id." AND ProductID = ".$product_id.";";

                                    $prodData = $dbObj->getData($sql_1);
                                    $avg_purchase = floatval($prodData[0]['avg_purchase']);

                                    $avg_cogs = $avg_purchase * $sell_qty;
                                    $avg_cogs = number_format($avg_cogs, 2, '.', '');

                                    $gross_margin = $avg_price - $avg_cogs;
                                    $gross_margin = number_format($gross_margin, 2, '.', '');

                                    $gross_margin_percent = ($gross_margin / $sell_amount) * 100;
                                    $gross_margin_percent = number_format($gross_margin_percent, 2, '.', '');

                                    ?>
                                    <tr>
                                        <td><?php echo $row_count;?></td>
                                        <td><?php echo $item_name;?></td>
                                        <td><?php echo $sell_qty;?></td>
                                        <td><?php echo $sell_amount;?></td>
                                        <td><?php echo $avg_price;?></td>
                                        <td><?php echo $avg_cogs;?></td>
                                        <td><?php echo $gross_margin;?></td>
                                        <td><?php echo $gross_margin_percent;?>%</td>
                                    </tr>
                                    <?php
                                    
                                }//foreach 1
                                ?>
                            </tbody>

                            <!-- footer -->
                           <!-- footer -->
                            <tfoot>
                            <tr>
                                <th colspan="5" style="text-align:right">Total:</th>
                                <th><?php echo number_format($sold_qty_total, 2); ?></th>
                                <th><?php echo number_format($item_discount_total, 2); ?></th>
                                <th><?php echo number_format($total_amount, 2); ?></th>
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

    <script>
        $(document).ready(function(){
            var shop_name = $("#shop_name").val();
            var shop_address_one = $('#shop_address_one').val();
            var shop_address_two = $('#shop_address_two').val();
            var shop_city = $('#shop_city').val();
            var shop_number = $('#shop_number').val();
            var reportname=$("#title").val();

            $("#tbl_primary").DataTable({
               
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
            
                    //Total over all pages
                    total = api
                        .column(5)
                        .data()
                        .reduce((a, b) => intVal(a) + intVal(b), 0);

                    total = total.toFixed(2);
            
                    // Total over this page
                    pageTotal = api
                        .column(5, { page: 'current' })
                        .data()
                        .reduce((a, b) => intVal(a) + intVal(b), 0);
            
                    // Update footer
                    api.column(5).footer().innerHTML = pageTotal + '(' + total + ')';

                    //total selling
                    totalSelling = api
                        .column(7)
                        .data()
                        .reduce((a, b) => intVal(a) + intVal(b), 0);

                    totalSelling = totalSelling.toFixed(2);

                    // Total over this page
                    pageTotalSelling = api
                        .column(7, { page: 'current' })
                        .data()
                        .reduce((a, b) => intVal(a) + intVal(b), 0);

                    //update footer
                    api.column(7).footer().innerHTML = pageTotalSelling + '(' + totalSelling + ')';
                }
            }); 
        });//jquery
    </script>
</body>
</html>
