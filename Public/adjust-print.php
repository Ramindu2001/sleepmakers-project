<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';

$adjust_header_id = 0;
if(isset($_POST['adjust_header_id']))
{
    $adjust_header_id = $_POST['adjust_header_id'];
    $adjust_header_stat = $_POST['adjust_header_stat'];
    
}
else
{
    $_SESSION['adjust_update'] = 0;
    header("Location: transfer-header.php");
    die("Error: no transfer header id.");
}


$shops= new Shop();
$shop=$shops->getOneShop($shop_id);
?>

<!doctype html>
<html lang="en">

<head>
  <?php 
  include '../View/head.php';
  // include '../View/loader.php';
  ?>
  <link rel="stylesheet" href="../Assets/css/print.css">
</head>
<!-- Body Wrapper -->
<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    <!-- Sidebar -->
    <?php include '../View/sidebar.php'; ?>
    <!-- Sidebar End -->

    <!-- Main wrapper -->
    <div class="body-wrapper">
        <!-- Header -->
        <?php 
        include '../View/header.php';
        include "../View/modals/submit-transferdetail.php";
        ?>

        <!-- Header End -->

        <div class="container-fluid">
            <h5 class="card-title fw-semibold mb-2"  id="header" style="margin-top: 0px;">Adjustment Details</h5>

            <div class="card mt-2 mb-2">
                <div class="card-body">
                    <h1 class="text-center" id="shop-name"><?=$shop[0]['ShopName']?></h1>
                    <h1 class="text-center" id="shop-location"><?=$shop[0]['CompanyLocation']?></h1>
                    <div class="container-fluid">
                        <table class="table table-hover" id="tbl_transfer_detail">
                            <thead>
                                <tr>
                                    <th style="display: none;">0</th>
                                    <th>Adjust No</th>
                                    <th>Shop Name</th>
                                    <th>Adjustment Type</th>
                                    <th>Adjustment Count</th>
                                    <th>Effective Date</th>
                                    <th>User Name</th>
                                    <th>Adjust Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $sql = "SELECT adjustheader.AdjustNo, shop.ShopName, AdjustmentTypeName, AdjustCount, adjustheader.EffectiveDate, AdjustAmount, user.UserName
                                        FROM adjustheader
                                        INNER JOIN adjustmenttype ON adjustmenttype.ITID = adjustheader.AdjustmentType_ITID
                                        INNER JOIN shop ON shop.SHID = adjustheader.shop_SHID
                                        INNER JOIN user ON user.USID = adjustheader.user_USID;";
                                        
                                $dbObj = new DBTransactions();
                                $dbData = $dbObj->getData($sql);
                                $count = 0;
                                $transfer_total_amount = 0;

                                foreach($dbData as $row)
                                {
                                    $count++;
                                    $transfer_total_amount += floatval($row['AdjustAmount']);
                                ?>
                                    <tr>
                                        <td style="display: none;"><?php echo $row['AdjustNo'];?></td><!-- 0 -->
                                        <td><?php echo $count;?></td><!-- 1 -->
                                        <td><?php echo $row['ShopName'];?></td><!-- 2 -->
                                        <td><?php echo $row['AdjustmentTypeName'];?></td><!-- 3 -->
                                        <td><?php echo $row['AdjustCount'];?></td><!-- 4 -->
                                        <td><?php echo $row['EffectiveDate'];?></td><!-- 5 -->
                                        <td><?php echo $row['UserName'];?></td><!-- 6 -->
                                        <td><?php echo number_format((float)$row['AdjustAmount'], 2);?></td><!-- 7 -->
                                    </tr>
                                <?php 
                                }
                                ?>
                                <tr>
                                    <td colspan="6" style="text-align: right;"><b>Total:</b></td>
                                    <td><b><?php echo number_format($transfer_total_amount, 2); ?></b></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!--<button id="btn_grn_print" class="btn btn-primary rounded-pill"><i class="ti ti-printer"></i> Print</button>-->
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Body Wrapper End -->

<!-- Footer -->
<?php include '../View/footer.php';?> 

<!-- Scripts -->
<script src="../Assets/jquery/adjustment.js"></script>
<script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../Assets/js/sidebarmenu.js"></script>
<script src="../Assets/js/app.min.js"></script>
<script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
<script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
<script src="../Assets/js/dashboard.js"></script>
<script>

    $(document).ready(function(){
    $('#btn_grn_print').click(function(){
        window.print();
    });
});

     
</script>
</body>
</html>
