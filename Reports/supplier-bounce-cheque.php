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
        $feature_id=55;
        include '../Includes/viewPermission.php';
        if($userType==1 || $print==1)
        {
            
        }
        else
        {
            ?>
            <script>
            setInterval(function() {
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
                    <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Supplier Realized Cheque Payments</h5>
                    <div class="container-fluid">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="tbl_category">
                                        <thead>

                                            <tr>
                                                <th>Sl</th>                                                
                                                <th>Invoice No</th>
                                                <th>Cheque No</th>
                                                <th>Bank</th>
                                                <th>Added By</th>
                                                <th>Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php 
                                        $report = new Report();
                                        $i=1;
                                        $status=3;
                                        $cheque=$report->suppliercheque($shop_id,$status);
                                        $count=count($cheque);
                                                if ($count > 0) {
                                            foreach ($cheque as $row) {
                                                ?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>
                                                    <td><?php echo $row['InvoiceNo']; ?></td>   
                                                    <td><?php echo $row['chq_no']; ?></td>
                                                    <td><?php echo $row['bank']; ?></td>
                                                    <td><?php echo $row['UserName']; ?></td>
                                                    <td><?=number_format($row["chqAmount"], 2)?></td>
                                                </tr>
                                                <?php 
                                                $i++;
                                            }
                                        } else {
                                            ?>
                                            <tr>
                                                <td colspan="12"> <p class="text-danger text-center">No Results Found</p></td>
                                            </tr>
                                            <?php
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