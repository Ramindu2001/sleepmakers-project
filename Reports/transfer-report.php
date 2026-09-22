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
    $feature_id=52;
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
        $shops= new Shop();
        $shop=$shops->getOneShop($shop_id);
        ?>
        <div class="container-fluid">
            <input type="hidden" name="" id="shop_name" value="<?=$shop[0]['ShopName']?>">
            <input type="hidden" name="" id="shop_address_one" value="<?=$shop[0]['AddressLineOne']?>">
            <input type="hidden" name="" id="shop_address_two" value="<?=$shop[0]['AddressLineTwo']?>"> 
            <input type="hidden" name="" id="shop_city" value="<?=$shop[0]['City']?>">
            <input type="hidden" name="" id="shop_number" value="<?=$shop[0]['PhoneNumber']?>"> 
            <input type="hidden" name="" id="title" value="Transfer Note"> 
         <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Transfer Note</h5>
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
                    <form action=" " class="form-inline" method="get" accept-charset="utf-8">
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
                                    <input type="date" name="to_date"class="form-control datepicker hasDatepicker" id="to_date" placeholder="End Date" value="2024-06-11">
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
                            <th style="display:none;">ID</th>
                            <th>Transfer No:</th>
                            <th>Effective Date</th>
                            <th>Transfer TotalCount</th>
                            <th>Transfer Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $transferobj = new Report();
                        $transferdata = $transferobj->transfernote($shop_id);
                        $i=1; 
                        $total_amount =0;
                        foreach ($transferdata as $row) 
                        {
                            $total_amount += $row['TransferTotalAmount'];
                            ?>
                            <tr>
                                <td> <?php echo $i; ?> </td>
                                <td> <?php echo $row['TransferNo']; ?></td>
                                <td> <?php echo $row['EffectiveDate']; ?></td>
                                <td> <?php echo $row['TransferTotalCount']; ?></td>       
                                <td> <?php echo $row['TransferTotalAmount']; ?></td>                               
                            </tr>
                            <?php 
                            $i++;
                        }
                        ?>
                    </tbody>
                    <tfoot>
                    <td colspan="3"></td> 
                    <td class="text-end"><strong>Total TransferAmount: <?php echo number_format($total_amount, 2); ?></strong><input type="hidden" name="total_amount" id="total_amount" value="<?=$total_amount?>"></td>
                    </tfoot>
                    </tfoot>
                </table>
                </div>
                </div>
<!--             <div id="checkListStockList_filter" class="dataTables_filter">
                    <label>
                        Search:
                        <input type="search" id="searchInput" class="form-control input-sum" placeholder="Search" aria-controls="checkListStockList">
                    </label>
                </div> -->
            </div>
        </div>
    </div>
</div>
</div>
    <?php require_once '../View/footer.php';?> 
    <script src="../Assets/jquery/transfer_report.js"></script>
    <!-- <script src="../Assets/libs/jquery/dist/jquery.min.js"></script> -->
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="../Assets/js/dashboard.js"></script>
</body>
</html>
