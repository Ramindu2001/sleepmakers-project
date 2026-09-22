<?php 
require_once '../Includes/includes.php';
require_once '../Includes/authcheck.php';
?>

<!doctype html>
<html lang="en">

<head>
  <?php 
  require_once '../View/head.php';
  require_once '../View/loader.php';
  require_once '../View/datatables.php';

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
</head>
<body>
<!--  Body Wrapper -->
<div class="h-100vh">
<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    <!-- Sidebar Start -->
    <?php 
    require_once '../View/sidebar.php';
    $feature_id=45;
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
        require_once "../View/modals/main-category.php";
         $shops = new Shop();
         $shop = $shops->getOneShop($shop_id);
        ?>
         <div class="container-fluid">
            <input type="hidden" name="" id="shop_name" value="<?=$shop[0]['ShopName']?>">
            <input type="hidden" name="" id="shop_address_one" value="<?=$shop[0]['AddressLineOne']?>">
            <input type="hidden" name="" id="shop_address_two" value="<?=$shop[0]['AddressLineTwo']?>">
            <input type="hidden" name="" id="shop_city" value="<?=$shop[0]['City']?>">  
            <input type="hidden" name="" id="shop_number" value="<?=$shop[0]['PhoneNumber']?>">
            <input type="hidden" name="" id="title" value="Customer Sale">
            <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Customer Sale</h5>
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
                    <form action="./customer-sale" class="form-inline" method="get" accept-charset="utf-8">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label class="" for="from_date">Start Date</label>
                                    <input type="date" name="from_date" class="form-control datepicker hasDatepicker" id="from_date" placeholder="Start Date" value="2024-06-11">
                                </div> 
                            </div>
                            <div class="col-md-5">
                                  <div class="form-group">
                                    <label class="" for="to_date">End Date</label>
                                    <input type="date" name="to_date" class="form-control datepicker hasDatepicker" id="to_date" placeholder="End Date" value="2024-06-11">
                                </div>
                            </div>
                            <div class="col-md-2 mt-4">
                                <button type="button" id="btn-filter" class="btn btn-success">Find</button>
                            </div>
                        </div>
                    </form>
                    <table class="table table-hover" id="tbl_category">
                    <thead>  

                        <tr>
                            <th>No.</th>
                            <th>Customer Name</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // $customerObj = new Report();
                        // $customerData = $customerObj->customersale($shop_id);
                        $i = 1;

                        $dbObj = new DBTransactions();

                        $sql = "SELECT * FROM customers WHERE shop_SHID = ".$shop_id.";";
                        $custData = $dbObj->getData($sql);

                        $customer_id = 1;
                        foreach($custData as $row)
                        {
                            $customer_id = $row['CTID'];

                            $sql_1 = "SELECT SUM(NetAmount) AS TotalSale, CustName FROM invoiceheader 
                            INNER JOIN customers ON customers.CTID = invoiceheader.customers_CTID 
                            WHERE customers_CTID = ".$customer_id." AND EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."';";

                            $saleData = $dbObj->getData($sql_1);

                            $cust_sale = floatval($saleData[0]['TotalSale']);
                            $customer_name = $saleData[0]['CustName'];
                            if($cust_sale > 0)
                            {
                                ?>
                                <tr>
                                    <td><?php echo $i;?></td>
                                    <td><?php echo $customer_name;?></td>
                                    <td><?php echo $cust_sale;?></td>
                                </tr>
                                <?php
                            }
                            else
                            {
                                continue;
                            }//else continue

                            $i++;
                        }//foreach
                        ?>
                    </tbody>
                   </table>
                  </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../View/footer.php';?> 
<script src="../Assets/jquery/sale_summary.js"></script>
<!-- <script src="../Assets/libs/jquery/dist/jquery.min.js"></script> -->
<script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../Assets/js/sidebarmenu.js"></script>
<script src="../Assets/js/app.min.js"></script>
<script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
<script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
<script src="../Assets/js/dashboard.js"></script>
</body>
</html>
