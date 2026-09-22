<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';

$grn_header_id = 0;
$grn_header_stat = 0;
$supplier_id = 0;

if(isset($_POST['grn_header_id']))
{
    $grn_header_id = $_POST['grn_header_id'];
    $grn_header_stat = $_POST['grn_header_stat'];
}//get header id
else
{
    $_SESSION['grnheader_update'] = 3;
    // header("Location: grn-header.php");
}//no header_id

?>
<!doctype html>
<html lang="en">

<head>
  <?php 
  include '../View/head.php';
  // include '../View/loader.php';
  ?>
  <style>
    #invoices
    {
        width: 100%;
        height: 300px;
        border: 1px solid #f5f5f5;
        border-radius: 10px;
        box-shadow: 0 25px 35px #0000002e;
        overflow-y: auto;
    }
  </style>
</head>

<body>
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
                // include "../View/modals/submit-grndetails.php";
                ?>
                <!--  Header End -->

                <div class="container-fluid">
                    <!-- messages -->
                    <div class="container">
                        <h3 class="card-title fw-semibold mb-2"> Delivery Note</h3>
                    </div>            
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12 row">
                                    <div class="col-md-4">
                                        <label for="cmb_customer" class="form-label">Customer Name/Phone No</label>
                                            <select name="cmb_customer" id="cmb_customer_delivery" class="form-select"></select>
                                        <input type="hidden" name="customer_id" id="customer_id" value="1">    
                                    </div>                                    
                                </div>
                                <div class="col-md-12 mt-3">
                                    <form action="../Public/delivery-note.php" method="POST">
                                        <div id="invoices">

                                        </div>
                                        <input type="submit" value="Print Delivery Note" class="btn btn-primary mt-3">
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<!--  Body Wrapper End -->
    <!-- footer Start  -->
    <?php include '../View/footer.php';?> 
    <!-- footer End  -->

    <!-- <script src="../Assets/jquery/grn.js"></script> -->
    <script src="../Assets/jquery/delivery_note.js"></script>

    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>

</body>
</html>