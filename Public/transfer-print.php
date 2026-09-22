<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';

$adjust_header_id = 0;
if(isset($_POST['transfer_header_id']))
{
    $adjust_header_id = $_POST['transfer_header_id'];
    $adjust_header_stat = $_POST['transfer_header_stat'];
}
else
{
    //$_SESSION['adjust_update'] == 0;
    //header("Location: transfer-header.php");
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

<body>
<!-- Body Wrapper -->
<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    <!-- Sidebar Start -->
    <?php include '../View/sidebar.php'; ?>
    <!-- Sidebar End -->

    <!-- Main wrapper -->
    <div class="body-wrapper">
        <!-- Header Start -->
        <?php 
        include '../View/header.php';
        include "../View/modals/submit-transferdetail.php";
        ?>
        <!-- Header End -->

        <div class="container-fluid">
            <h5 class="card-title fw-semibold mb-2" id="header"  style="margin-top: 0px;">Transfer Note Report</h5>

            <div class="card mt-2 mb-2">
                <div class="card-body">
                <h1 class="text-center" id="shop-name"><?=$shop[0]['ShopName']?></h1>
                <h1 class="text-center" id="shop-location"><?=$shop[0]['CompanyLocation']?></h1>
                    <div class="container-fluid">
                        <table class="table table-hover" id="tbl_transfer_detail">
                            <thead>
                                <tr>
                                    <th style="display: none;">0</th>
                                    <th>GRN No</th>
                                    <th>Transfer To</th>
                                    <th>Transfer From</th>
                                    <th>Effective Date</th>
                                    <th>Transfer Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $sql = "SELECT TransferNo, TransferTo, TransferFrom, TransferTotalAmount, EffectiveDate
                                        FROM transferheader
                                        INNER JOIN shop ON shop.SHID = transferheader.shop_SHID
                                        INNER JOIN user ON user.USID = transferheader.user_USID;";


                                $transferObj = new Transfer();
                                $transferData = $transferObj->getAllTransfer($shop_id);
                                $count = 0;
                                $transfer_total_amount = 0;


                                foreach($transferData as $row)
                                {
                                    $count += 1;
                                    $transfer_total_amount += floatval($row['TransferTotalAmount']);
                                    $shopObj = new Shop();
                                    $fromShop = $shopObj->getOneShop($row['TransferFrom']);
                                    $toShop = $shopObj->getOneShop($row['TransferTo']);

                                    $from_shop = $fromShop[0]['ShopName'];
                                    $to_shop = $toShop[0]['ShopName'];
                                ?>
                                    <tr>
                                        <td style="display: none;"><?php echo $row['TransferNo'];?></td><!-- 0 -->
                                        <td><?php echo $count;?></td><!-- 1 -->
                                        <td><?php echo $from_shop;?></td>
                                        <td><?php echo $to_shop;?></td>
                                        <td><?php echo $row['EffectiveDate'];?></td><!-- 2 -->
                                        <td><?php echo number_format((float)$row['TransferTotalAmount'], 2);?></td><!-- 3 -->
                                    </tr>
                                <?php 
                                }
                                ?>
                                <tr>
                                    <td colspan="4" style="text-align: right;"><b>Total:</b></td>
                                    <td><b><?php echo number_format($transfer_total_amount, 2); ?></b></td>

                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button id="btn_grn_print" class="btn btn-primary rounded-pill"><i class="ti ti-printer"></i> Print</button>
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
