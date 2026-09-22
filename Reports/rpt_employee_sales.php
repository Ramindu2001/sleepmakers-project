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
} 
if(isset($_GET["start_date"]) && isset($_GET["end_date"]))
{
    $start_date = $_GET["start_date"];
    $end_date = $_GET["end_date"];
}
else {
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
                <input type="hidden" name="" id="title" value="Employee Sales Report">
            
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Employee Sales Report</h5>
                    </div>
                    <div class="card-body">
                        <div class="container">
                            <form action="" method="get" class="form-inline">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="" class="form-label">Start Date</label>
                                        <input type="date" name="start_date" id="start_date" value="<?php echo $start_date;?>" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="" class="form-label">End Date</label>
                                        <input type="date" name="end_date" id="end_date" value="<?php echo $end_date;?>" class="form-control">
                                    </div>

                                    <?php 
                                    if($userType==1)
                                    {
                                        ?>
                                        <div class="col-md-2">
                                            <label for="claim" class="form-label">Claim Bill With Invoices</label>
                                            <input type="checkbox" name="claim" id="claim" class="form-check" value="1"
                                            <?php 
                                            if(isset($_GET["claim"]))
                                            {
                                                echo "checked";
                                            }
                                            ?>
                                            >
                                        </div>
                                        <?php
                                    }
                                    ?>

                                    <div class="col-md-2">
                                        <button class="btn btn-success mt-4" name="btn_employee_sales" id="btn_employee_sales">Find</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <table id="tbl_inventory_summary">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>User Name</th>
                                    <th>Bill Count</th>
                                    <th>Total Qty Sold</th>
                                    <th>Total Sales Value</th>
                                    <th>Average Price</th>
                                    <th>Average Sale Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $dbObj = new DBTransactions();

                                $sql = "SELECT * FROM shopusers 
                                INNER JOIN user ON user.USID = shopusers.user_USID
                                WHERE shopusers.shop_SHID = ".$shop_id.";";

                                $row_count = 0;
                                $userData = $dbObj->getData($sql);
                                $add = "";
                                    if (isset($_GET["claim"])) {
                                        $add = " OR invoiceheader.InvStat=6";
                                    }
                                foreach($userData as $row)
                                {
                                    $row_count += 1;
                                    $shop_user_id = $row['USID'];
                                    $user_name = $row['UserName'];
                                    
                                    //get transactions
                                    $sql_1 = "SELECT 
                                    count(invoiceheader.BillNo) as bill_count, 
                                    sum(invoiceheader.InvItemCount) as sold_qty,
                                    sum(invoiceheader.NetAmount) as net_amount
                                    FROM invoiceheader
                                    WHERE 
                                    invoiceheader.user_USID = ".$shop_user_id." 
                                    AND invoiceheader.EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."' 
                                    AND invoiceheader.shop_SHID = ".$shop_id."
                                    AND invoiceheader.InvStat=1 $add;";
                                    
                                    $invData = $dbObj->getData($sql_1);

                                    //get days
                                    $start_time = strtotime($start_date);
                                    $end_time = strtotime($end_date);
                                    $date_diff = $end_time -  $start_time;
                                    $days = round($date_diff/(60*60*24)) + 1;

                                    if(!empty($invData))
                                    {
                                        $bill_count = $invData[0]['bill_count'];
                                        $sold_qty = floatval($invData[0]['sold_qty']);
                                        $sold_amount = floatval($invData[0]['net_amount']);
                                        if($sold_qty > 0)
                                        {
                                            $average_price = round($sold_amount / $sold_qty, 2);
                                            $average_sale_qty = round(($sold_qty / $days), 2);

                                            ?>
                                            <tr>
                                                <td><?php echo $row_count;?></td>
                                                <td><?php echo $user_name;?></td>
                                                <td><?php echo $bill_count;?></td>
                                                <td><?php echo $sold_qty;?></td>
                                                <td><?php echo $sold_amount;?></td>
                                                <td><?php echo $average_price;?></td>
                                                <td><?php echo $average_sale_qty;?></td>
                                            </tr>
                                            <?php
                                        }//has value
                                    }//not empty
                                }//foreach 1
                                ?>
                            </tbody>

                            <!-- footer -->
                             <!-- <tfoot>
                                <tr>
                                    <th colspan="4" style="text-align:right">Total:</th>
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
                //        return typeof i === 'string'
                //            ? i.replace(/[\$,]/g, '') * 1
                //            : typeof i === 'number'
                //            ? i
                //            : 0;
                //     };
            
                //     //Total over all pages
                //     total = api
                //        .column(6)
                //        .data()
                //        .reduce((a, b) => intVal(a) + intVal(b), 0);

                //     total = total.toFixed(2);
            
                //     //Total over this page
                //    pageTotal = api
                //        .column(6, { page: 'current' })
                //        .data()
                //        .reduce((a, b) => intVal(a) + intVal(b), 0);
            
                //     //Update footer
                //     api.column(6).footer().innerHTML = pageTotal + ' (' + total + ')';
                // }//footercall back

            }); 
        });//jquery
    </script>
</body>
</html>
