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
            <input type="hidden" name="" id="shop_number" value="<?=$shop[0]['CompanyLocation']?>">
            <input type="hidden" name="" id="shop_address" value="<?=$shop[0]['CompanyLocation']?>"> 
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
                            <th>Barcode</th>
                            <th>Item Name</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Added Date</th>
                            <th>Expired Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $dbObj = new DBTransactions();

                        $this_date = date("Y-m-d");

                        $sql = "SELECT * FROM pricehistory 
                        INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
                        INNER JOIN products ON products.PDID = pricehistory.ProductID
                        WHERE inventory.shop_SHID = ".$shop_id." AND ExpDate < '".$this_date."' AND CurrentQty > 0;";

                        $expData = $dbObj->getData($sql);

                        foreach($expData as $row)
                        {
                            ?>
                            <tr>
                                <td><?php echo $row['Barcode'];?></td>
                                <td><?php echo $row['ItemName'];?></td>
                                <td><?php echo $row['CurrentQty'];?></td>
                                <td><?php echo $row['SellingPrice'];?></td>
                                <td><?php echo $row['EffectiveDate'];?></td>
                                <td><?php echo $row['ExpDate'];?></td>
                            </tr>
                            <?php
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
