<?php 
// require_once '../Includes/includes.php';

include "../Includes/includes.php";
require_once '../Includes/authcheck.php';

$shopObj = new Shop();
$shop_id = $_SESSION['shop_id'];

$dbObj = new DBTransactions();

 //get current date time
 date_default_timezone_set("Asia/Colombo");

 $start_date = "";
 $end_date = "";
 $customer_id = 0;
 if (isset($_GET['date'])) {
    $date = explode("_", $_GET['date']);
    $start_date = $date[0];
    $end_date = $date[1];
    $customer_id = $date[2];

} 
if(isset($_GET["start_date"]) && isset($_GET["end_date"]))
{
    $start_date = $_GET["start_date"];
    $end_date = $_GET["end_date"];
    // $customer_id = $date[2];

}
else {
    $start_date = date("Y-m-d");
    $end_date = date("Y-m-d");
    $customer_id = 0;

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
                <input type="hidden" name="" id="title" value="Customer Detail Report">
            
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Customer Detail Report</h5>
                    </div>
                    <div class="card-body">
                        <div class="container">
                            <form action=" " method="get" class="form-inline">
                                <div class="row">
                                    <div class="col-md-2">
                                        <label for="" class="form-label">Customer</label>
                                        <select name="cmb_customer" id="cmb_customer" class="form-select">
                                            <?php 
                                            $sql = "SELECT * FROM `customers` WHERE shop_SHID = ".$shop_id.";";
                                            $custData = $dbObj->getData($sql);
                                            foreach($custData as $row)
                                            {
                                                $is_select = "";
                                                if($customer_id == $row['CTID'])
                                                {
                                                    $is_select = "selected";
                                                }//has customer
                                                ?>      
                                                <option value="<?php echo $row['CTID'];?>" <?php echo $is_select;?>><?php echo $row['CustName'];?></option>
                                                <?php 
                                            }//foreach      
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="" class="form-label">Start Date</label>
                                        <input type="date" name="start_date" id="start_date" value="<?php echo $start_date;?>" class="form-control">
                                    </div>
                                    <div class="col-md-2">
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
                                        <button type="submit" class="btn btn-success mt-4" name="btn_customer_detail" id="btn_customer_detail">Find</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <table id="tbl_inventory_summary">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Date</th>
                                    <th>Invoice No</th>
                                    <th>Invoice Status</th>
                                    <th>Item Count</th>
                                    <th>Invoice Amount</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $add = "";
                                if (isset($_GET["claim"])) {
                                    $add = " OR invoiceheader.InvStat=6";
                                }

                                if($customer_id > 0)
                                {
                                    $sql = "SELECT * FROM `invoiceheader` 
                                    INNER JOIN user ON user.USID = invoiceheader.user_USID 
                                    WHERE customers_CTID = ".$customer_id." AND EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."' AND invoiceheader.InvStat=1 $add";
                                }//has customer
                                else
                                {
                                    $sql = "SELECT * FROM `invoiceheader` 
                                    INNER JOIN user ON user.USID = invoiceheader.user_USID
                                    WHERE EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."' AND invoiceheader.InvStat=1 $add ORDER BY IHID DESC LIMIT 50;";
                                }//no customer

                                $row_count = 0;
                                $salesData = $dbObj->getData($sql);
                                foreach($salesData as $row)
                                {
                                    $row_count += 1;
                                    $inv_date = $row['EffectiveDate'];
                                    $inv_no = $row['BillNo'];
                                    $item_count = $row['InvItemCount'];
                                    $net_amount = $row['NetAmount'];
                                    $user_name = $row['UserName'];
                                    $InvStat = $row['InvStat'];

                                    $net_amount = number_format((float)$net_amount, 2, ".", "");
                                    ?>
                                        <tr>
                                            <td><?php echo $row_count;?></td>
                                            <td><?php echo $inv_date;?></td>
                                            <td><?php echo $inv_no;?></td>
                                            <td><?php 
                                        if($InvStat==1)
                                        {
                                            ?>
                                            <span class="badge bg-success">Finalized</span>
                                            <?php
                                        }
                                        else if($InvStat==6)
                                        {
                                            ?>
                                            <span class="badge bg-danger" >Claim Bill</span>
                                            <?php
                                        }
                                        else
                                        {
                                            ?>
                                            <span class="badge bg-danger" >N/A</span>
                                            <?php
                                        }
                                        ?></td>
                                            <td><?php echo $item_count;?></td>
                                            <td><?php echo $net_amount;?></td>
                                            <td><?php echo $user_name;?></td>
                                        </tr>
                                    <?php
                                }//foreach 1
                                ?>
                            </tbody>

                            <!-- footer -->
                             <tfoot>
                                <tr>
                                    <th colspan="4" style="text-align:right">Total:</th>
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
                                            {text: shop_address_one + ",", bold: true, fontSize: 12},
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
                       .column(4)
                       .data()
                       .reduce((a, b) => intVal(a) + intVal(b), 0);

                    total = total.toFixed(2);
            
                    //Total over this page
                   pageTotal = api
                       .column(4, { page: 'current' })
                       .data()
                       .reduce((a, b) => intVal(a) + intVal(b), 0);
            
                    //Update footer
                    api.column(4).footer().innerHTML = pageTotal + ' (' + total + ')';
                }//footercall back

            }); 

        //================== Select 2 ====================//
        $("#cmb_customer").select2({
            cache: true,
            placeholder: 'Search for items',
            minimumInputLength: 1,
            width: '100%'
        });


        });//jquery
    </script>
</body>
</html>
