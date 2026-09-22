<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';
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
    include '../View/sidebar.php';
    ?>
    <!--  Sidebar End -->
    <!--  Main wrapper -->
    <div class="body-wrapper">
        <!--  Header Start -->
        <?php 
        include '../View/header.php';
        include "../View/modals/add-products.php";
        include "../View/modals/add-variations.php";
        ?>
        <!--  Header End -->

        <div class="container-fluid">
            <!-- messages -->
        <div class="container">
        <?php 
            if(isset($_SESSION['product_update']))
            {
                if($_SESSION['product_update'] == 0)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Please select a <strong>Category and Subcategory.</strong>
                    </div>
                    <?php 
                }//no entry
                else if($_SESSION['product_update'] == 1)
                {
                    ?>
                    <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Please enter <strong>Barcode or Item Name.</strong>
                    </div>
                    <?php
                }//duplicate entry
                else if($_SESSION['product_update'] == 2)
                {
                    ?>
                    <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Selected <strong>File Type </strong>is not supported.
                    </div>
                    <?php
                }//save success
                else if($_SESSION['product_update'] == 3)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Item saved </strong>successfully!
                    </div>
                    <?php
                }//update success
                else if($_SESSION['product_update'] == 4)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Unable to save image.</strong>
                    </div>
                    <?php
                }//update success
                else if($_SESSION['product_update'] == 5)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Item updated</strong>successfully!
                    </div>
                    <?php
                }//update success
                else if($_SESSION['product_update'] == 6)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Not supported image </strong>file!
                    </div>
                    <?php
                } //not support image
                else
                {
                    ?>
                    <div class="alert alert-danger">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Oops!</strong>something went wrong!
                    </div>
                    <?php 
                } //else
                unset($_SESSION['product_update']);
            }   //session set
            ?>

        </div>
            <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Products</h5>
            
            <div class="card">
                <div class="card-body">
                <button class="btn btn-primary border border-success rounded-pill ml-1" id="btn_add_products">Add Products</button>
                
                <div class="container-fluid">
                    <table class="table table-hover" id="tbl_products">
                        <tr>
                            <td style="display: none;">0</td>
                            <th>Product No</th>
                            <th>Category</th>
                            <th>Sub Category</th>
                            <th>Barcode</th>
                            <th>Item Name</th>
                            <th>Image</th>
                            <th>Type</th>
                            <th>Action</th>
                        </tr>
                        <?php 
                        $prodObj = new Product();
                        $prodData = $prodObj->getProductByShop($shop_id);
                        foreach($prodData as $row)
                        {
                            ?>
                            <tr>
                                <td style="display: none;"><?php echo $row['PDID'];?></td> <!-- 0 -->
                                <td><?php echo $row['ProductNo'];?></td> <!-- 1 -->
                                <td><?php echo $row['CategoryName'];?></td> <!-- 2 -->
                                <td><?php echo $row['SubCatName'];?></td> <!-- 3 -->
                                <td><?php echo $row['Barcode'];?></td> <!-- 4 -->
                                <td><?php echo $row['ItemName'];?></td> <!-- 5 -->
                                <td>
                                    <?php 
                                    if(isset($row['ProdImage']))
                                    {
                                        ?>
                                        <img src="../Assets/Images/prod_images/<?php echo $row['ProdImage'];?>" alt="" srcset="" style="width: 30px; height:auto;">
                                        <?php 
                                    }//has image
                                    else
                                    {
                                        ?>
                                        <img src="../Assets/Images/icons/product.png" alt="Product Image" style="width: 30px; height:auto;">
                                        <?php 
                                    }//no image
                                    ?> 
                                </td> <!-- 6 -->
                                <td>
                                    <?php 
                                        if($row['ItemType'] == "p")
                                        {
                                            ?>
                                            <p class="p-1 bg-success text-white rounded-pill text-center">Product</p>
                                            <?php 
                                        }//is a product
                                        else
                                        {
                                            ?>
                                            <p class="p-1 bg-warning text-white rounded-pill text-center">Service</p>
                                            <?php 
                                        }//is a service
                                    ?>
                                </td><!-- 7 -->
                                <td>
                                    <button type="button" id="btn_product_variant_<?php echo $row['PDID']?>" class="btn border border-primary"><i class="ti ti-list"></i></button>
                                    <button type="button" id="btn_product_<?php echo $row['PDID']?>" class="btn border border-primary"><i class="ti ti-edit"></i></button>
                                    <button type="button" id="btn_product_delete_<?php echo $row['PDID']?>" class="btn border border-danger"><i class="ti ti-x"></i></button>
                                </td><!-- 8 -->
                                <!-- hidden values -->
                                <td style="display: none;"><?php echo $row['categories_CTID'];?></td><!-- 9 -->
                                <td style="display: none;"><?php echo $row['Subcategories_SCID'];?></td><!-- 10 -->
                                <td style="display: none;"><?php echo $row['SecondName'];?></td><!-- 11 -->
                                <td style="display: none;"><?php echo $row['ProdDescription'];?></td><!-- 12 -->
                                <td style="display: none;"><?php echo $row['ProdPurchasePrice'];?></td><!-- 13 -->
                                <td style="display: none;"><?php echo $row['ProdSellPrice'];?></td><!-- 14 -->
                                <td style="display: none;"><?php echo $row['CartonQty'];?></td><!-- 15 -->
                                <td style="display: none;"><?php echo $row['ItemType'];?></td><!-- 16 -->
                                <td style="display: none;"><?php echo $row['ProdImage'];?></td><!-- 17 -->
                            </tr>
                            <?php 
                        } //foreach  
                        ?>
                    </table>
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

    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/jquery/product.js"></script>
    <script src="../Assets/jquery/inventory_summary.js"></script>

</body>
</html>