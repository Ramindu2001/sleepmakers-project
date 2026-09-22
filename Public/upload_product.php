<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';
?>

<!doctype html>
<html lang="en">

<head>
  <?php 
  include '../View/head.php';
  //// include '../View/loader.php';
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
                if(isset($_SESSION["erroProduct"]) && $_SESSION["erroProduct"]!="" && count($_SESSION["erroProduct"]))
                {
                    ?>
                    <div class="alert alert-danger">
                        <b>Errors! in below mentioned products</b> <br>
                    <?php 
                    foreach ($_SESSION["erroProduct"] as $row) 
                    {
                        echo $row["product"]." - ".$row["message"]."<br>";
                    }
                    ?>
                    </div>
                    <?php
                    unset($_SESSION["erroProduct"]);
                }
                ?>
                <?php 
                if(isset($_SESSION["successProduct"]) && $_SESSION["successProduct"]!="" && count($_SESSION["successProduct"]))
                {
                    ?>
                    <div class="alert alert-success">
                    <b>Success! below mentioned products are uploaded</b> <br>
                    <?php 
                    foreach ($_SESSION["successProduct"] as $row) 
                    {
                        echo $row["product"]." - ".$row["message"]."<br>";
                    }
                    ?>
                    </div>
                    <?php
                    unset($_SESSION["successProduct"]);
                }
                ?>
                
                <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Upload CSV Products</h5>
                <div class="card">
                    <div class="card-body">    
                        <a href="../Public/csv.php" class="btn btn-primary mb-2">Download Sample CSV</a>
                        <form action="../Controller/productController.php" method="post" enctype="multipart/form-data">
                            <label for="file" class="form-label">Upload CSV <span class="text-danger" style="font-size:10px;"> Max 500 products per upload.</span></label>
                            <input type="file" class="form-control required" name="file" id="file" accept=".csv" required><br>
                            <div class="row">
                                <div class="col-md-6">
                                    <!-- <div class="form-check form-switch py-2">
                                        <input class="form-check-input" type="checkbox" id="flexSwitchCheckChecked" value="1">
                                        <label class="form-check-label" for="flexSwitchCheckChecked">Allow Duplicates</label>
                                    </div> -->
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-end">
                                        <input type="submit" value="Submit" name="bulk_upload" class="btn btn-primary mt-2">
                                    </div>  
                                </div>
                            </div>                          
                        </form> 
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

    <script src="../Assets/jquery/product.js"></script>
    <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="../Assets/jquery/inventory_summary.js"></script>

</body>
</html>