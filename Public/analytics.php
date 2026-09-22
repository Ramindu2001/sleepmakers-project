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
  $shopObj = new Shop();
  $shopData = $shopObj->getOneShop($_SESSION['shop_id']);         
  $company_logo = $shopData[0]['ComLogo'];
  $shop_id = $_SESSION['shop_id'];

  //necessary objects
  $dbObj = new DBTransactions();
  ?>
  <link rel="stylesheet" href="../Assets/css/dashboard.css">
</head>

<body>

  <?php
  if(isset($_SESSION["toast"]))
  {
    $message="Hi. ".$_SESSION['user'][0]['UserName'];
    $message2="Shop: ".$shopData[0]['ShopName'];
    include "../View/toast.php";
    unset($_SESSION["toast"]);
  }
  
  ?>
  <!--  Body Wrapper -->
    <div class="h-100vh">
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed">
            <!-- Sidebar Start -->
            <?php 
            include '../View/sidebar.php';
            ?>
            <!--  Sidebar End -->
            <!--  Main wrapper -->
            <div class="body-wrapper">
            <!--  Header Start -->
                <?php 
                include '../View/header.php';
                ?>
                <!--  Header End -->
                <div class="container-fluid">
                  <?php 
                  if (isset($_SESSION['status'])) 
                  {
                    if ($_SESSION['status']==1) 
                    {
                      ?>
                      <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Counter Started</strong> Successfully
                      </div>
                      <?php
                    }
                    else if ($_SESSION['status']==2) 
                    {
                      ?>
                      <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Counter Closed</strong> Successfully
                      </div>
                      <?php
                    }
                    else if ($_SESSION['status']==3) 
                    {
                      ?>
                      <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Please start a new <strong>Cash Counter</strong>.
                      </div>
                      <?php
                    }
                    else if ($_SESSION['status']==4) 
                    {
                      ?>
                      <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Please add <strong>payment methods</strong> to the shop.
                      </div>
                      <?php
                    }//no paymethods
                    else if ($_SESSION['status']==5) 
                    {
                      ?>
                      <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Please add <strong>Sale Settings</strong> to the shop.
                      </div>
                      <?php
                    }//no sale settings
                    else if ($_SESSION['status']==6) 
                    {
                      ?>
                      <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Please add <strong>Receipt Settings</strong> to the shop.
                      </div>
                      <?php
                    }//no sale settings
                    else
                    {
                      ?>
                      <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Error - </strong> Something went wrong, Please try again!
                      </div>
                      <?php
                    }
                    unset($_SESSION['status']);
                  }//counter status

                  if(isset($_SESSION['user_error']))
                  {
                    if ($_SESSION['user_error']==4) 
                    {
                      ?>
                      <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Password Updated!</strong>Successfully
                      </div>
                      <?php
                    }
                    else if ($_SESSION['user_error']==5) 
                    {
                      ?>
                      <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Oops!Something Went Wrong,</strong>Please Try Again
                      </div>
                      <?php
                    }
                    unset($_SESSION['user_error']);
                  }
                  ?>

    <!--------------------------------- Bar Charts ------------------------------------>
    <div class="row">
      <div class="card col-md-8">
          <div class="row">
              <div class="col-6">
                <select name="cmb_chart_type" id="cmb_chart_type" class="border border-primary form-select">
                  <option value="0">Gross Sales</option>
                  <option value="1">Number of Sales</option>
                </select>
              </div>
              <div class="col-6">
                <button class="btn_duration" id="btn_dur_day">7 Days</button>
                <button class="btn_duration" id="btn_dur_week">14 Days</button>
                <!-- <button class="btn_duration" id="btn_dur_month">30 Days</button> -->
              </div>
          </div>
          <!-- barchart -->
          <div id="bar_chart" class="p-2"></div>
      </div>

      <!----------------------------------- Donut Chart ----------------------------------->
      <div class="col-md-4" id="div_other_charts">
          <div class="card p-2">
            <h5 class="card-title mb-2 fw-semibold px-2">
              Today Revenue
              <button class="float-end" id="btn_refresh_donut">
                <img src="../Assets/Images/icons/refresh.png" alt="refresh" class="img_refresh">
              </button>
            </h5>
            <div class="row">
                <div class="col-6 p-2">
                  <?php 
                    //get current date time
                    date_default_timezone_set("Asia/Colombo");
                    $effective_date = date("Y-m-d");

                    $sql = "SELECT SUM(NetAmount) AS DayTotal, count(IHID) as BillCount , sum(InvItemCount) as TotalItems FROM invoiceheader WHERE InvStat=1 AND EffectiveDate='".$effective_date."' AND shop_SHID=".$shop_id.";";

                    $saleData = $dbObj->getData($sql);

                    //get return amount
                    $sql_1 = "SELECT sum(return_amount) as return_amount FROM `retrun_invoice_header` WHERE EffectiveDate = '".$effective_date."' AND shopID=".$shop_id.";";

                    $returnData = $dbObj->getData($sql_1);
                    $return_amount = empty($returnData[0]['return_amount']) ? 0 : $returnData[0]['return_amount'];

                    $gross_sale = empty($saleData[0]['DayTotal']) ? 0.00 : $saleData[0]['DayTotal'];
                    $bill_count =  empty($saleData[0]['BillCount']) ? 0 : $saleData[0]['BillCount'];
                    $item_count =  empty($saleData[0]['TotalItems']) ? 0 : $saleData[0]['TotalItems'];

                    $gross_sale = $gross_sale - $return_amount;
                  ?>
                  <h4 class="p-2"><?php echo "Rs: " . number_format((float)$gross_sale, 2, '.', '');?></h4>

                  <p class="p-3 mb-1">
                    Bill Count: <?php echo $bill_count;?> <br>
                    Total Items: <?php echo $item_count;?>
                  </p>
                </div>
                <div class="col-6 p-2">
                  <!-- donut chart -->
                  <div id="donut_chart"></div>
                </div>
            </div>
          </div>

          <!--------------------- Purchase Chart ------------------->
          <div class="card p-2">
            <h5 class="card-title mb-2 fw-semibold px-2">
                Last 30 Days Purchase
                <button class="float-end" id="btn_refresh_line">
                  <img src="../Assets/Images/icons/refresh.png" alt="refresh" class="img_refresh">
                </button>
            </h5>

            <div>
              <?php 
              //get current date time
              date_default_timezone_set("Asia/Colombo");
              $effective_date = date("Y-m-d");

              $new_date = date("Y-m-d", strtotime("-30 days"));

              $sql = "SELECT sum(TotalPurchasePrice) as TotalPurchase FROM grnheader WHERE GRNStat = 2 AND shop_SHID = ".$shop_id." AND EffectiveDate BETWEEN '".$new_date."' AND '".$effective_date."';";

              $grnData = $dbObj->getData($sql);
              $purchase_total = $grnData[0]['TotalPurchase'];
              ?>
              <h4 class="p-2 m-0"><?php echo "Rs: " . $purchase_total;?></h4>
              <!-- line chart -->
              <div id="line_chart"></div>
            </div>
          </div>
      </div>

    </div>

    <!------------------------------------- Other Details ----------------------------------->
    <div class="row mt-2">
      <!-- fast moving items -->
      <div class="col-md-4">
        <div class="card p-3" style="height: 450px;">
          <h5>Fast Moving Items</h5>
          <table>
            <tr>
              <th>Item Name</th>
              <th>Sold Qty</th>
            </tr>
            <?php 
            //get current date time
            date_default_timezone_set("Asia/Colombo");
            $effective_date = date("Y-m-d");

            $new_date = date("Y-m-d", strtotime("-7 days"));

            $sql = "SELECT SUM(SellQty) AS TotalSaleQty, ItemName FROM invoicedetails 
            INNER JOIN invoiceheader ON invoiceheader.IHID = invoicedetails.InvoiceHeader_IHID
            INNER JOIN products ON products.PDID = invoicedetails.products_PDID
            WHERE invoiceheader.shop_SHID =".$shop_id." AND EffectiveDate BETWEEN '".$new_date."' AND '".$effective_date."' AND invoiceheader.shop_SHID=".$shop_id." group by products_PDID ORDER BY TotalSaleQty DESC LIMIT 5;";
            
            $itemData = $dbObj->getData($sql);

            foreach($itemData as $row)
            {
              ?>
                <tr>
                  <td><?php  echo $row['ItemName'];?></td>
                  <td><?php  echo $row['TotalSaleQty'] + 0;?></td>
                </tr>
              <?php 
            }//foreach
            ?>
          </table>
         
        </div>
      </div>
      <!-- recent transactions -->
      <div class="col-md-8">
        <div class="card p-3" style="height: 450px;">
          <h5>Recent transactions</h5>
          <table>
            <tr>
              <th>Bill No</th>
              <th>Date</th>
              <th>Time</th>
              <th>Customer</th>
              <th>Amount</th>
            </tr>
          <?php 
            $sql = "SELECT * FROM invoiceheader 
            INNER JOIN customers ON customers.CTID = invoiceheader.customers_CTID 
            WHERE invoiceheader.shop_SHID =".$shop_id." ORDER BY InvEndTime DESC LIMIT 6;";
            
            $itemData = $dbObj->getData($sql);

            foreach($itemData as $row)
            {
              $bill_date = substr($row['InvEndTime'], 0, 10);
              $bill_time = substr($row['InvEndTime'], 10, 9);
              ?>
                <tr>
                  <td><b><?php  echo $row['BillNo'];?></b></td>
                  <td><?php  echo $bill_date;?></td>
                  <td><?php  echo $bill_time;?></td>
                  <td><?php  echo $row['CustName'];?></td>
                  <td><b><?php  echo $row['NetAmount'];?></b></td>
                </tr>
              <?php 
            }//foreach
          ?>
          </table>
        </div>
        
      </div>
    </div>

    <!-- Expire data -->
    <?php 
      if($shopObj->hasExpiry($shop_id))
      {
        ?>
          <div class="card mt-2">
            <div class="card-header">
              <h5>Sooner Expiring Items</h5>
            </div>
            <div class="card-body">
              <table>
                <tr>
                  <th>Barcode</th>
                  <th>Item Name</th>
                  <th>Qty</th>
                  <th>Price</th>
                  <th>Added Date</th>
                  <th>Exp date</th>
                  <th>Expire</th>
                </tr>
                <?php 
                  //get current date time
                  date_default_timezone_set("Asia/Colombo");
                  $current_date = date("Y-m-d");
                  $after_date = date("Y-m-d" , strtotime("+30 days"));
  
                  $sql = "SELECT * FROM pricehistory 
                  INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
                  INNER JOIN products ON products.PDID = pricehistory.ProductID
                  WHERE ExpDate BETWEEN '".$current_date."' AND '".$after_date."' AND inventory.shop_SHID = ".$shop_id." AND CurrentQty>0 ORDER BY ExpDate DESC;";

                  $expData = $dbObj->getData($sql);

                  $days_to_expire = 0;
                  foreach($expData as $row)
                  {
                    $now = time();
                    $exp_date = strtotime($row['ExpDate']);
                    $date_diff = $exp_date - $now;
                    $days_to_expire = round($date_diff / (60*60*24));
                    ?>
                    <tr>
                      <td><?php echo $row['Barcode'];?></td>
                      <td><?php echo $row['ItemName'];?></td>
                      <td><?php echo $row['CurrentQty'] + 0;?></td>
                      <td><?php echo $row['SellingPrice'];?></td>
                      <td><?php echo $row['EffectiveDate'];?></td>
                      <td><?php echo $row['ExpDate'];?></td>
                      <td>
                        <?php 
                          if($days_to_expire >0 AND $days_to_expire < 7)
                          {
                            ?>
                            <p style="font-weight: bold; color:#cc0000;"><?php echo $days_to_expire;?> Days</p>
                            <?php 
                          }//7 days
                          elseif($days_to_expire >=7 AND $days_to_expire < 14)
                          {
                            ?>
                            <p style="font-weight: bold; color:#cc5200;"><?php echo $days_to_expire;?> Days</p>
                            <?php 
                          }//14 days
                          elseif($days_to_expire >=14 AND $days_to_expire < 30)
                          {
                            ?>
                            <p style="font-weight: bold; color:#e6e600;"><?php echo $days_to_expire;?> Days</p>
                            <?php 
                          }//30 days
                        ?>
                      </td>
                    </tr>
                    <?php 
                  }//foreach
                ?>
              </table>
            </div>
          </div>
        <?php 
      }//has expire date
    ?>
        <!-- footer Start  -->
        <?php include '../View/footer.php';?>
        <!-- footer End  -->
        
      </div>
    </div>
  </div>
</div>
    
  <script src="../Assets/jquery/dashboard.js"></script>
  <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../Assets/js/sidebarmenu.js"></script>
  <script src="../Assets/js/app.min.js"></script>
  <script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
  <!-- <script src="../Assets/libs/simplebar/dist/simplebar.js"></script> -->
  <!-- <script src="../Assets/js/dashboard.js"></script> -->
  <script src="../Assets/jquery/toast.js"></script>
</body>

</html>