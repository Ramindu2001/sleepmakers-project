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
        $feature_id=42;
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
            $shops= new Shop();
            $shop=$shops->getOneShop($shop_id);
            ?>
            <div class="container-fluid">
                <input type="hidden" name="" id="shop_name" value="<?=$shop[0]['ShopName']?>">
                <input type="hidden" name="" id="shop_number" value="<?=$shop[0]['CompanyLocation']?>">
                <input type="hidden" name="" id="shop_address" value="<?=$shop[0]['CompanyLocation']?>"> 
                <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Item Return Report</h5>
                <div class="container-fluid">
                    <div class="card">
                        <div class="card-body">
                            <form action="" method="get" class="mb-3">
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label class="" for="from_date">Start Date</label>
                                            <input type="date" name="from_date" class="form-control datepicker hasDatepicker" id="from_date" placeholder="Start Date" <?php if(isset($_GET["from_date"])){ ?> value="<?=$_GET["from_date"]?>"<?php }?>>
                                        </div> 
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label class="" for="to_date">End Date</label>
                                            <input type="date" name="to_date"class="form-control datepicker hasDatepicker" id="to_date" placeholder="End Date" <?php if(isset($_GET["to_date"])){ ?> value="<?=$_GET["to_date"]?>"<?php }?>>
                                        </div>
                                    </div>
                                    <div class="col-md-2 mt-4">
                                        <input type="submit" value="Find" name="submit" id="btn-filter" class="btn btn-primary">
                                    </div>
                                </div>
                            </form>
                            <div class="table-responsive">
                                <table class="table table-hover" id="tbl_category">
                                    <thead>  
                                        <tr>
                                            <th>Sl</th>
                                            <th>Return Type</th>
                                            <th>Stock</th>
                                            <th>Return Date</th>
                                            <th>Return No</th>
                                            <th>Returned By</th>
                                            <th>Returned Count</th>
                                            <th>Gross Amount</th>
                                            <th>Total Discount</th>
                                            <th>Total Amount</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $saleObj = new Report();
                                        if(isset($_GET["from_date"]) && $_GET["to_date"])
                                        {
                                            $from_date=$_GET["from_date"];
                                            $to_date=$_GET["to_date"];
                                            $saleData = $saleObj->returninvoicereport($shop_id,$type=1,$from_date,$to_date);
                                        }
                                        else
                                        {
                                            $saleData = $saleObj->returninvoicereport($shop_id,$type=1);
                                        }
                                        $i=1;
                                        $total_discount=0;
                                        $total_amount=0;
                                        $count=count($saleData);
                                        if($count>0)
                                        {
                                            foreach ($saleData as $row) 
                                            {
                                                $total_discount += $row['return_discount'];
                                                $total_amount += $row['return_gross_amount'];
                                                ?>
                                                <tr>
                                                    <td> <?php echo $i;?> </td>
                                                    <td> <?php 
                                                    if($row["return_type"]==1)
                                                    {
                                                        ?>
                                                        <span class="  text-center m-1">Cash Refund</span>
                                                        <?php
                                                    }
                                                    else
                                                    {
                                                        ?>
                                                        <span class="  text-center m-1">Exchange</span>
                                                        <?php
                                                    }
                                                    ?> 
                                                    </td>
                                                    <td> <?php 
                                                    if($row["usability"]==1)
                                                    {
                                                        ?>
                                                        <span class="text-center m-1">Adjust Stock</span>
                                                        <?php
                                                    }
                                                    else
                                                    {
                                                        ?>
                                                        <span class="text-center text-danger m-1">Damage Stock</span>
                                                        <?php
                                                    }
                                                    ?> 
                                                </td>
                                                    <td> <?php echo $row['EffectiveDate'] ?></td>
                                                    <td> <?php echo $row['return_no'] ?></td>
                                                    <td> <?php echo $row['returnPerson'] ?></td>
                                                    <td> <?php echo $row['return_count'] ?></td>
                                                    <td> <?php echo $row['return_amount']?></td>
                                                    <td> <?php echo $row['return_discount']?></td>
                                                    <td> <?php echo $row['return_gross_amount']?></td> 
                                                    <td> <a href="return_view_details.php?id=<?=$row["RIHID"]?>" class="">View Info</a></td> 
                                                </tr>
                                                <?php 
                                                $i++;
                                            }
                                        }
                                        else
                                        {
                                            ?>
                                            <tr>
                                                <td colspan="11"> <p class="text-danger text-center">No Results Found</p></td>
                                            </tr>
                                            <?php
                                        }                                        
                                        ?>
                                    </tbody>
                                    <tfoot>
                                    <tr>
                                        <td colspan="9"></td> 
                                        <td class="text-end"><strong>Total Discount: <?php echo number_format($total_discount, 2); ?></strong></td>
                                        <td class="text-end"><strong>Total Amount: <?php echo number_format($total_amount, 2); ?></strong></td>
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
</div>
<!--  Body Wrapper End -->
<?php require_once '../View/footer.php';?> 

    <script src="../Assets/jquery/sale_summary.js"></script>
    <!-- <script src="../Assets/libs/jquery/dist/jquery.min.js"></script> -->
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="../Assets/js/dashboard.js"></script>
<script>
</script>
</body>
</html>
