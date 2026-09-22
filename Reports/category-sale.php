<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';

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
            <input type="hidden" name="" id="title" value="Sales Summary">
            <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Sales Summary</h5>
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
                    <form action="../Controller/ReportController.php" class="form-inline" method="POST" accept-charset="utf-8">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label class="" for="from_date">Start Date</label>
                                    <input type="date" name="start_date" class="form-control datepicker hasDatepicker" id="start_date" placeholder="Start Date" value="<?php echo $start_date;?>">
                                </div> 
                            </div>
                            <div class="col-md-5">
                                  <div class="form-group">
                                    <label class="" for="to_date">End Date</label>
                                    <input type="date" name="end_date"class="form-control datepicker hasDatepicker" id="end_date" placeholder="End Date" value="<?php echo $end_date;?>">
                                </div>
                            </div>
                            <div class="col-md-2 mt-4">
                                <button type="submit" name="btn_category_date" id="btn-filter" class="btn btn-success">Find</button>
                            </div>
                        </div>
                    </form>

                    <table class="table table-hover" id="tbl_category">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Category</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $dbObj = new DBTransactions();

                        $sql = "SELECT * FROM shop
                        INNER JOIN company ON company.CMID = shop.Company_CMID
                        WHERE SHID = ".$shop_id.";";

                        $comData = $dbObj->getData($sql);
                        $company_id = $comData[0]['CMID'];

                        $sql_1 = "SELECT * FROM categories
                        INNER JOIN shop ON shop.SHID = categories.shop_SHID
                        WHERE shop.Company_CMID = ".$company_id.";";

                        $row_count = 0;
                        $catData = $dbObj->getData($sql_1);
                        foreach($catData as $row)
                        {   
                            $row_count += 1;
                            $category_id = $row['CTID'];
                            $category_name = $row['CategoryName'];

                            //echo "cat - " . $category_id . "<br>";

                            $sql_2 = "SELECT sum(NetAmount) as TotalSale FROM invoiceheader
                            INNER JOIN invoicedetails ON invoicedetails.InvoiceHeader_IHID = invoiceheader.IHID
                            INNER JOIN products ON products.PDID = invoicedetails.products_PDID
                            INNER JOIN subcategories ON subcategories.SCID = products.Subcategories_SCID
                            WHERE EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."' AND categories_CTID = ".$category_id." AND invoiceheader.shop_SHID = ".$shop_id.";";

                            $saleData = $dbObj->getData($sql_2);

                            //echo "sale - " . $saleData[0]['TotalSale'] . "<br>";
                            $category_sale =  $saleData[0]['TotalSale'];

                            ?>
                            <tr>
                                <td><?php echo $row_count;?></td>
                                <td><?php echo $category_name;?></td>
                                <td><?php echo $category_sale;?></td>
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
</div>
<?php include '../View/footer.php';?> 

    <script src="../Assets/jquery/sale_summary.js"></script>
    <!-- <script src="../Assets/libs/jquery/dist/jquery.min.js"></script> -->
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
</body>
</html>
