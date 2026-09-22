<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';

$shop_id = $_SESSION['shop_id'];

?>
<!doctype html>
<html lang="en">

<head>
  <?php 
  include '../View/head.php';
  // include '../View/loader.php';
  include '../View/datatables.php';

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
    include '../View/sidebar.php';
    $feature_id=39;
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
        include '../View/header.php';
        include "../View/modals/main-category.php";
        $shops= new Shop();
        $shop=$shops->getOneShop($shop_id);

        //get date
        date_default_timezone_set("Asia/Colombo");
        $effective_date = date("Y-m-d");
        ?>
         <div class="container-fluid">
            <input type="hidden" name="" id="shop_name" value="<?=$shop[0]['ShopName']?>">
            <input type="hidden" name="" id="shop_address_one" value="<?=$shop[0]['AddressLineOne']?>">
            <input type="hidden" name="" id="shop_address_two" value="<?=$shop[0]['AddressLineTwo']?>">
            <input type="hidden" name="" id="shop_city" value="<?=$shop[0]['City']?>">
            <input type="hidden" name="" id="shop_number" value="<?=$shop[0]['PhoneNumber']?>"> 
            <input type="hidden" name="" id="title" value="Monthly Sales Trend">

            <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Monthly Sales Trend</h5>
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
                    <form action="../Controller/ReportController.php" class="form-inline" method="POST" accept-charset="utf-8">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label class="" for="from_date">Start Date</label>
                                    <input type="date" name="start_date" class="form-control" id="from_date" placeholder="Start Date" value="<?php echo $effective_date;?>">
                                </div> 
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label class="" for="to_date">End Date</label>
                                    <input type="date" name="end_date"class="form-control" id="to_date" placeholder="End Date" value="<?php echo $effective_date;?>">
                                </div>
                            </div>
                            <div class="col-md-2 mt-4">
                                <button type="submit" id="btn-filter" name="btn_monthly_sale" class="btn btn-success">Find</button>
                            </div>
                        </div>
                    </form>

                    <?php 
                        $dbObj = new DBTransactions();
                        $sql = "SELECT * FROM `invoiceheader` WHERE EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."' AND shop_SHID = ".$shop_id.";";

                        $invData = $dbObj->getData($sql);

                        $month_dates = [];
                        foreach($invData as $row)
                        {
                            $this_date = $row['EffectiveDate'];

                            // Create DateTime object
                            $date = new DateTime($this_date);

                            // Get first day of the month
                            $startOfMonth = $date->modify('first day of this month')->format('Y-m-d');

                            if(!in_array($startOfMonth, $month_dates))
                            {
                                //get first date
                                $month_dates[] = $startOfMonth;
                            }
                        }//foreach
                    ?>

                    <table class="table table-hover" id="tbl_sale_reports">
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th>Month</th>
                            <th>Item Count</th>
                            <th>Gross Sale</th>
                            <th>Net Sale</th>
                            <th>Bill Count</th>
                            <th>Average Sale</th>
                            <th>Top Item</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $gross_sale = 0;
                        $gross_return = 0;
                        $full_total = 0; // Initialize full total sale

                        foreach($month_dates as $row)
                        {
                            $start_of_month = $row;
                            
                            $date = new DateTime($start_of_month);
                            $end_of_month = $date->modify('last day of this month')->format('Y-m-d');

                            $year_num = substr($start_of_month, 0, 4);
                            //get month number
                            $month_num = substr($start_of_month, 5, 2);
                            $month_name = "";
                            switch ($month_num)
                            {
                                case '01': $month_name = "January";  
                                break;
                                case '02':$month_name = "February"; 
                                break;
                                case '03':$month_name = "March"; 
                                break;
                                case '04':$month_name = "April"; 
                                break;
                                case '05':$month_name = "May"; 
                                break;
                                case '06':$month_name = "June"; 
                                break;
                                case '07':$month_name = "July"; 
                                break;
                                case '08':$month_name = "August"; 
                                break;
                                case '09':$month_name = "September"; 
                                break;
                                case '10':$month_name = "Octomber"; 
                                break;
                                case '11':$month_name = "November"; 
                                break;
                                case '12':$month_name = "December"; 
                                break;
                                default:
                                # code...
                                break;
                            }//switch month

                            $sql_1 = "SELECT count(IHID) as BillCount, sum(InvItemCount) as SoldItemCount, sum(GrossAmount) as GrossTotal, sum(NetAmount) as NetTotal FROM `invoiceheader` WHERE EffectiveDate BETWEEN '".$start_of_month."' AND '".$end_of_month."'AND InvStat=1 AND shop_SHID = ".$shop_id.";";

                            $monthData = $dbObj->getData($sql_1);

                            $bill_count = $monthData[0]['BillCount'];
                            $item_count = $monthData[0]['SoldItemCount'];
                            $gross_sale = floatval($monthData[0]['GrossTotal']);
                            $net_sale = floatval($monthData[0]['NetTotal']);

                            //get return amount
                            $sql_2 = "SELECT sum(return_amount) as return_amount FROM `retrun_invoice_header` WHERE EffectiveDate BETWEEN '".$start_of_month."' AND '".$end_of_month."' AND shopID = ".$shop_id.";";
                            $returnData = $dbObj->getData($sql_2);
                            $return_amount = empty($returnData[0]['return_amount']) ? 0 : $returnData[0]['return_amount'];

                            //top selling
                            $sql_3 = "SELECT SellQty, products_PDID, Barcode, ItemName FROM invoicedetails 
                            INNER JOIN invoiceheader ON invoiceheader.IHID = invoicedetails.InvoiceHeader_IHID 
                            INNER JOIN products ON products.PDID = invoicedetails.products_PDID
                            WHERE 
                            invoiceheader.EffectiveDate BETWEEN '".$start_of_month."' AND '".$end_of_month."'AND invoiceheader.InvStat=1 AND invoiceheader.shop_SHID = ".$shop_id.";";

                            $topData = $dbObj->getData($sql_3);
                            $sale_count = 0;
                            $top_item_id = 0;
                            foreach($topData as $row_1)
                            {
                                $sale_qty = floatval($row_1['SellQty']);
                                if($sale_qty > $sale_count)
                                {
                                    $sale_count = $sale_qty;
                                    $top_item_id = $row_1['products_PDID'];
                                }
                            }//foreach_1

                            //get One product
                            $sql_4 = "SELECT * FROM products WHERE PDID = ".$top_item_id.";";
                            $prodData = $dbObj->getData($sql_4);
                            $barcode = $prodData[0]['Barcode'];
                            $item_name = $prodData[0]['ItemName'];

                            // $net_sale = $total_sale - floatval($return_amount);
                            $average_sale = $net_sale / $bill_count;
                            $average_sale = number_format((float)$average_sale, 2, '.', '');
                            // $full_total += floatval($net_sale);
                            ?>
                            <tr>
                                <td><?php echo $year_num;?></td>
                                <td><?php echo $month_name;?></td>
                                <td><?php echo $item_count;?></td>
                                <td><?php echo $gross_sale;?></td>
                                <td><?php echo $net_sale;?></td>
                                <td><?php echo $bill_count;?></td>
                                <td><?php echo $average_sale;?></td>
                                <td>
                                    <?php echo $barcode;?><br>
                                    <?php echo $item_name;?>
                                </td>
                            </tr>
                            <?php 
                        }//foreach

                        ?>
                    </tbody>
                   <!-- footer -->
                   <!-- <tfoot>
                    <tr>
                        <th colspan="2" style="text-align:right">Total:</th>
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
<?php include '../View/footer.php';?> 

    <!-- <script src="../Assets/jquery/monthly_summary.js"></script> -->
    <!-- <script src="../Assets/libs/jquery/dist/jquery.min.js"></script> -->
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>

    <script>
        $(document).ready(function(){
            var shop_name = $("#shop_name").val();
            var shop_address_one = $('#shop_address_one').val();
            var shop_address_two = $('#shop_address_two').val();
            var shop_city = $('#shop_city').val();
            var shop_number = $('#shop_number').val();
            var reportname=$("#title").val();

            $("#tbl_sale_reports").DataTable({
               
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
                //        return typeof i === 'string'
                //            ? i.replace(/[\$,]/g, '') * 1
                //            : typeof i === 'number'
                //            ? i
                //            : 0;
                //     };
            
                //     //Total over all pages
                //     total = api
                //        .column(2)
                //        .data()
                //        .reduce((a, b) => intVal(a) + intVal(b), 0);

                //     total = total.toFixed(2);
            
                //     //Total over this page
                //    pageTotal = api
                //        .column(2, { page: 'current' })
                //        .data()
                //        .reduce((a, b) => intVal(a) + intVal(b), 0);
            
                //     //Update footer
                //     api.column(2).footer().innerHTML = pageTotal + ' (' + total + ')';
                // }//footercall back

            }); 
        });//jquery
    </script>

</body>
</html>
