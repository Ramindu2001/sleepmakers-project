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
  $shop_id = $_SESSION['shop_id'];
  $priceChange=new Pricechange();
  $fetch=$priceChange->select_pricechange($shop_id);
  $dbObj = new DBTransactions();
  ?>
  <style>
    .table>:not(caption)>*>* 
    {
        padding: 10px;
    }
    th 
    {
        font-size: 0.8rem;
    }    
    input[disabled]
    {
        cursor: not-allowed;
    }
    select[disabled]
    {
        cursor: not-allowed;
    }
    input[readonly]
    {
        background-color: rgb(235, 235, 235) !important;
        border-color: rgb(235, 235, 235) !important;
    }
    #prodItem:focus
    {
        border-color: red;
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
    $feature_id=6;
    include '../Includes/viewPermission.php';
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
            <!-- messages -->
        <div class="container">
        <?php 
            if(isset($_SESSION['price_update']))
            {
                if($_SESSION['price_update'] == 0)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Oops! </strong> Something went wrong.
                    </div>
                    <?php 
                }//no data
                else if($_SESSION['price_update'] == 1)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Price changeed! </strong>successfully!
                    </div>
                    <?php
                }//save success
                else
                {
                    ?>
                    <div class="alert alert-danger">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Oops! </strong>something went wrong!
                    </div>
                    <?php 
                }//else
                unset($_SESSION['price_update']);
            }//session set
            ?>

        </div>
            <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Price Change</h5>
            
            <div class="card mt-5">
                <div class="card-body">
                    <?php 
                    if($userType==1 || $create==1 || $edit==1)
                    {
                        if(!isset($_POST["cmb_category"]) && !isset($_POST["cmb_subcategory"]))
                        {
                            ?>
                            <form action="" method="POST" enctype="multipart/form-data">
                                <div class="row">
                                <?php 
                                $shopObj = new Shop();
                                $shop_id = $_SESSION['shop_id'];
                                $dbObj = new DBTransactions();
                                //get company stat
                                $sql = "SELECT * FROM shop
                                INNER JOIN company ON company.CMID = shop.Company_CMID
                                WHERE SHID = ".$shop_id.";";

                                $shopData = $dbObj->getData($sql);
                                $multi_category = floatval($shopData[0]['is_multicategory']);
                                $company_id = floatval($shopData[0]['CMID']);
                                if($shopObj->hasCategories($shop_id))
                                {
                                ?>
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="" class="form-label">Category <span class="text-danger text-alrt" style="display:none;">*</span></label>
                                            <select name="cmb_category" id="cmb_category" class="form-select dropdown-toggle required">
                                                <option value="0">=== Select Category ===</option>
                                                <?php 
                                                $shop_id = $_SESSION['shop_id'];
                                                $catObj = new Category();
                                                $catData = $catObj->getCategoryByShop($shop_id,$multi_category,$company_id);
                                                foreach($catData as $row)
                                                {
                                                    ?>
                                                    <option value="<?php echo $row['CTID'];?>">
                                                    <?php echo $row['CategoryName'];?>
                                                    </option>
                                                    <?php 
                                                }//foreach
                                                ?>
                                            </select>
                                            <span class="text-danger" id="alrt" style="display:none;">This Field is Required</span>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="" class="form-label">Sub Category <span class="text-danger text-alrt" style="display:none;">*</span></label>
                                            <select name="cmb_subcategory" id="cmb_subcategory" class="form-select dropdown-toggle required">
                                                <option value="">=== Select Sub Category ===</option>
                                            </select>
                                            <span class="text-danger" id="alrt" style="display:none;">This Field is Required</span>
                                        </div>
                                    </div>
                                </div>
                                    <div class="col-md-12 mt-2 d-flex justify-content-end">
                                        <input type="submit" value="Search Items" name="Search_item" class="btn btn-primary">
                                    </div>
                                    </div>
                                <?php 
                                }//has categories
                                ?>
                            </form>                        
                            <?php
                        }
                        else
                        {
                            $subcat=$_POST["cmb_subcategory"];
                            $sql="SELECT * FROM products p
                            INNER JOIN subcategories s ON p.Subcategories_SCID=s.SCID
                            WHERE p.Subcategories_SCID='$subcat'";
                            $products = $dbObj->getData($sql);
                            ?>
                            <form action="../Controller/pricechangeController.php" id="myForm" method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-3"></div>
                                    <div class="col-md-3"></div>
                                    <div class="col-md-5">
                                        <input type="text" placeholder="Enter Selling Price" name="price" id="" class="form-control" required>
                                    </div>
                                    <div class="col-md-1">
                                        <input type="submit" name="bulkPriceChange" value="Change Price" class="btn btn-primary">
                                    </div>
                                </div>
                                <table>
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" name="" id="prodAll" class="form-checkbox" onclick="checkALL()"></th>
                                            <th>Product Name</th>
                                            <th>Product Barcode</th>
                                            <th>Product Subcategory</th>
                                            <th>Product Description</th>
                                            <th>Product Selling Price</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if(count($products)>0)
                                        {
                                            foreach($products as $row)
                                            {
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <input type="checkbox" name="prodItem[]" value="<?=$row["PDID"]?>" id="prodItem" class="prod-checkbox form-checkbox" onclick="all()">
                                                            <input type="hidden" name="oldPrice">
                                                        </td>
                                                        <td>
                                                            <?=$row["ItemName"]?>
                                                        </td>
                                                        <td>
                                                            <?=$row["Barcode"]?>
                                                        </td>
                                                        <td>
                                                            <?=$row["SubCatName"]?>
                                                        </td>
                                                        <td>
                                                            <?=$row["ProdDescription"]?>
                                                        </td>
                                                        <td>
                                                            <?=$row["ProdSellPrice"]?>
                                                        </td>
                                                    </tr>
                                                <?php
                                            }
                                            
                                        }
                                        else
                                        {
                                            ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-danger">No Products Found</td>
                                            </tr>
                                            <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </form>
                            <?php
                        }
                    }
                    ?>
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

    <script src="../Assets/jquery/priceChange.js"></script>
    <script>
        $(document).ready(function () {
            // Intercept form submission
            $('#myForm').on('submit', function (e) {
                // Check if at least one checkbox is checked
                if ($('.prod-checkbox:checked').length === 0) {
                    // Prevent form submission
                    e.preventDefault();
                    // Alert the user
                    alert('Please select at least one product before submitting the form.');
                    $("#prodItem").focus();
                    
                }
            });
        });
        function checkALL()
        {
            if($("#prodAll").is(":checked"))
            {
                $("body #prodItem").each(function(){
                    $(this).prop("checked",true);
                });
            }
            else
            {
                $("body #prodItem").each(function(){
                    $(this).prop("checked",false);
                });
            }
            
        }
        function all()
        {
            // Count the total checkboxes
            let total = $('.prod-checkbox').length;
            // Count the checked checkboxes
            let checked = $('.prod-checkbox:checked').length;

            // Check if all are checked
            if (checked === total) {
                $("#prodAll").prop("checked",true);
                console.log("True");
            } else {
                $("#prodAll").prop("checked",false);
                console.log("False");                
            }
        }
    </script>
    <!-- <script src="../Assets/libs/jquery/dist/jquery.min.js"></script> -->
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    
</body>
</html>