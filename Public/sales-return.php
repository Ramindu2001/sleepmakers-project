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
  ?>
  <style>
    .card-header 
    {
        background: #fff;
        border-bottom: 1px solid #f5f5f5;
        padding: 10px;
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
    $feature_id=10;
    include '../Includes/viewPermission.php';
    ?>
    <!--  Sidebar End -->
    <!--  Main wrapper -->
    <div class="body-wrapper">
        <!--  Header Start -->
        <?php 
        include '../View/header.php';
        include "../View/modals/main-category.php";
        ?>
        <!--  Header End -->
        <div class="container-fluid">
            <!-- messages -->
        <div class="container">
        <?php 
            if(isset($_SESSION['salesreturn_update']))
            {
                if($_SESSION['salesreturn_update'] == 0)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        No Results Found, <strong>Please Try Again.</strong> 
                    </div>
                    <?php 
                }//no date
                else if($_SESSION['salesreturn_update'] == 1)
                {
                    ?>
                        <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>Invoice Already Returned</strong>
                        </div>
                        <?php
                }//no invoice
                else if($_SESSION['salesreturn_update'] == 2)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Item/Invoice Returned </strong>Successfully!
                    </div>
                    <?php
                }//save success
                else if($_SESSION['salesreturn_update'] == 3)
                {
                    ?>
                    <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Please select a <strong>GRN</strong> to add items
                    </div>
                    <?php
                }//update success
                else if($_SESSION['salesreturn_update'] == 4)
                {
                    ?>
                        <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>No Product Found</strong>
                        </div>
                        <?php
                }//no invoice
                else if($_SESSION['salesreturn_update'] == 6)
                {
                    ?>
                        <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>No Sale Was Made For The Product</strong>
                        </div>
                        <?php
                }//no invoice
                else
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Oops! </strong>something went wrong!
                    </div>
                    <?php 
                }//else
                unset($_SESSION['salesreturn_update']);
            }//session set
            $shop_id = $_SESSION['shop_id'];
            ?>

        </div>
            <h5 class="card-title fw-semibold mb-3">Sales Return</h5>
            <div class="row">
                <div class="col-md-1"></div>
                <div class="col-md-5">
                    <div class="card p-2">
                        <div class="card-header">
                            <h5 class="card-title fw-semibold mb-3">Sales Return With Invoice</h5>
                        </div>
                        <div class="card-body">
                            <form action="../Controller/SalesReturnControl.php" method="post">
                                <?php 
                                if($userType==1)
                                {
                                    ?>
                                        <label for="invoiceNo" class="form-label">Invoice No <span class="text-danger">*</span></label>
                                        <input type="text" name="invoiceNo" maxlength="25" id="invoiceNo" value="" class="form-control mb-2" required>
                                    <input type="submit" name="submit" value="Search" class="btn btn-primary">
                                    <?php
                                }
                                else
                                {
                                    if($edit==1 || $verify==1 || $create==1)
                                    {
                                        ?>
                                        <label for="invoiceNo" class="form-label">Invoice No <span class="text-danger">*</span></label>
                                        <input type="text" name="invoiceNo" maxlength="25" id="invoiceNo" value="" class="form-control mb-2" required>
                                        <input type="submit" name="submit" value="Search" class="btn btn-primary">
                                        <?php
                                    }
                                }
                                ?>
                                
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="card p-2">
                        <div class="card-header">
                            <h5 class="card-title fw-semibold mb-3">Sales Return With Product Barcode</h5>
                        </div>
                        <div class="card-body">
                            <form action="../Controller/SalesReturnControl.php" method="post">
                                <?php 
                                if($userType==1)
                                {
                                    ?>
                                    <label for="barcode" class="form-label">Product Barcode <span class="text-danger">*</span></label>
                                    <input type="text" name="barcode" maxlength="25" id="barcode" value="" class="form-control mb-2" required>
                                    <input type="submit" name="submitbarcode" value="Search" class="btn btn-primary">
                                    <?php
                                }
                                else
                                {
                                    if($edit==1 || $verify==1 || $create==1)
                                    {
                                        ?>
                                        <label for="barcode" class="form-label">Product Barcode <span class="text-danger">*</span></label>
                                        <input type="text" name="barcode" maxlength="25" id="barcode" value="" class="form-control mb-2" required>
                                        <input type="submit" name="submitbarcode" value="Search" class="btn btn-primary">
                                        <?php
                                    }
                                }
                                ?>
                                
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-1"></div>
            </div>
        </div>
    </div>
</div>
</div>
<div id="print"></div>
<!--  Body Wrapper End -->
    <!-- footer Start  -->
    <?php include '../View/footer.php';?> 
    <!-- footer End  -->
    <script src="../Assets/jquery/sales-return.js"></script>
    <!-- <script src="../Assets/libs/jquery/dist/jquery.min.js"></script> -->
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <!-- <script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script> -->
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <!-- <script src="../Assets/js/dashboard.js"></script> -->
    




</body>
</html>