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
            <input type="hidden" name="" id="shop_number" value="<?=$shop[0]['CompanyLocation']?>">
            <input type="hidden" name="" id="shop_address" value="<?=$shop[0]['CompanyLocation']?>"> 

            <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">User Sale Report</h5>
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
                    <form action="" class="form-inline" method="get" accept-charset="utf-8">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label class="" for="from_date">Start Date</label>
                                    <input type="date" name="from_date" class="form-control datepicker hasDatepicker" id="from_date" <?php if(isset($_GET["from_date"])){?>value="<?=$_GET["from_date"]?>"<?php }?> placeholder="Start Date" >
                                </div> 
                            </div>
                            <div class="col-md-5">
                                  <div class="form-group">
                                    <label class="" for="to_date">End Date</label>
                                    <input type="date" name="to_date" class="form-control datepicker hasDatepicker" id="to_date" <?php if(isset($_GET["to_date"])){?>value="<?=$_GET["to_date"]?>"<?php }?> placeholder="End Date">
                                </div>
                            </div>


                            <div class="col-md-2 mt-4">
                                <button type="submit" id="btn-filter" class="btn btn-success">Find</button>
                            </div>

                        </div>
                    </form>
                    <table class="table table-hover" id="tbl_category">
                    <thead>  
                        <tr>
                            <th>Sl</th>
                            <th>Sales Person</th>
                            <th>Cashier</th>
                            <th>Gross Amount</th>
                            <th>Net Amount</th>
                            <th>Customer Payment</th>
                            <th>Customer Balance</th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <?php 
                        $user= new Report();
                        if(isset($_GET["from_date"]) && isset($_GET["to_date"]))
                        {
                            $start=$_GET["from_date"];
                            $end=$_GET["to_date"];
                            $userdata = $user->usersale($start,$end);
                        }
                        else
                        {
                            $userdata = $user->usersale();
                        }
                        
                        $i = 1;
                        foreach ($userdata as $row) 
                        {
                            ?>
                            <tr>
                                <td> <?php echo $i;?> </td>
                                <td> <?php echo $row['SalesPerson'] ?></td>
                                <td> <?php echo $row['cashier'] ?></td>
                                <td> <?php echo $row['GrossAmount'] ?></td>
                                <td> <?php echo $row['NetAmount'] ?></td>
                                <td> <?php echo $row['CustPayment'] ?></td>
                                <td> <?php echo $row['CustBalance'] ?></td>
                            </tr>
                            <?php 
                            $i++;
                        }
                        ?>
                    </tbody>

                </table>
                </div>
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
