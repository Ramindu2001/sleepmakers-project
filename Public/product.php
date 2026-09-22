<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';

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


// Pagination variables
$items_per_page = 10; // Items displayed per page
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page from URL, default to 1
$offset = ($current_page - 1) * $items_per_page; // Calculate offset

// Get total product count
$count_sql = ($multi_category == 1) ?
    "SELECT COUNT(*) AS total FROM products 
    INNER JOIN subcategories ON subcategories.SCID = products.Subcategories_SCID
    INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
    INNER JOIN shop ON shop.SHID = products.shop_SHID
    WHERE shop.Company_CMID = $company_id;" :
    "SELECT COUNT(*) AS total FROM products 
    INNER JOIN subcategories ON subcategories.SCID = products.Subcategories_SCID
    INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
    WHERE products.shop_SHID = $shop_id;";

$total_items = $dbObj->getData($count_sql)[0]['total'];
$total_pages = ceil($total_items / $items_per_page); // Calculate total pages

// Fetch products with pagination
$product_sql = ($multi_category == 1) ?
    "SELECT *,categories.CTID AS cat_ID FROM products 
    INNER JOIN subcategories ON subcategories.SCID = products.Subcategories_SCID
    INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
    INNER JOIN shop ON shop.SHID = products.shop_SHID
    WHERE shop.Company_CMID = $company_id ORDER BY products.PDID
    LIMIT $items_per_page OFFSET $offset;" :
    "SELECT *,categories.CTID AS cat_ID FROM products 
    INNER JOIN subcategories ON subcategories.SCID = products.Subcategories_SCID
    INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
    WHERE products.shop_SHID = $shop_id ORDER BY products.PDID
    LIMIT $items_per_page OFFSET $offset ;";

$prodData = $dbObj->getData($product_sql);

?>

<!doctype html>
<html lang="en">

<head>
    <?php 
    include '../View/head.php';
    // include '../View/loader.php';
    ?>
    <style>
        .table>:not(caption)>*>* {
            padding: 10px ;
        }
        .pagination {
    width: 100%;
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
    $feature_id=16;
    include '../Includes/viewPermission.php';
    ?>
    <!--  Sidebar End -->
    <!--  Main wrapper -->
    <div class="body-wrapper">
        <!--  Header Start -->
        <?php 
        include '../View/header.php';
        include "../View/modals/add-products.php";
        include "../View/modals/add-variations.php";
        include "../View/modals/product_barcode.php";
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
                        <strong>Not supported image</strong>file!
                    </div>
                    <?php
                }//not support image
                else
                { 
                    ?>
                    <div class="alert alert-danger">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Oops! </strong>something went wrong!
                    </div>
                    <?php 
                }//else
                unset($_SESSION['product_update']);
            }//session set
            ?>

        </div>

               <div class="card">
                    <div class="card-header">
                        <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">
                            Products
                            <?php 
                            if($userType==1 || $create==1)
                            {
                                ?>
                                <button type="button" class="btn btn-primary float-end" id="btn_add_products"><small>Add Products</small></button>
                                <a href="upload_product.php" class="btn btn-primary float-end me-1" id="import"><small>CSV File Upload</small></a>
                                <?php
                            }
                            ?>
                        </h5>
                    </div>
                    <div class="card-body">   
                            
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                            <!-- the search box takes the row on a phone and shares it on a desktop -->
                            <input type="text" id="product_search" class="form-control flex-grow-1 w-auto"
                                   style="min-width:180px; max-width:420px;" placeholder="Search">

                            <?php
                            /* The bulk print button works on the tick boxes in the
                               first column. The selection survives paging and
                               searching, so items can be collected from several
                               pages before printing. */
                            if($userType==1 || (isset($print) && $print==1))
                            {
                                ?>
                                <button type="button" class="btn btn-primary" id="btn_print_selected" disabled>
                                    <i class="ti ti-printer"></i>
                                    <small>Print Barcode (<span id="bc_selected_count">0</span>)</small>
                                </button>
                                <button type="button" class="btn btn-link text-danger p-0 ms-1" id="btn_clear_selection" style="display:none;">
                                    <small>Clear selection</small>
                                </button>
                                <?php
                            }
                            ?>

                            <a href="barcode-settings.php" class="btn btn-light border ms-md-auto">
                                <i class="ti ti-settings"></i> <small>Barcode Settings</small>
                            </a>
                        </div>
                    
                        <div class="container-fluid table-responsive">
                        <table class="table table-hover" id="tbl_products">
                            <thead>
                            <tr>
                                <th style="width:34px;">
                                    <?php if($userType==1 || (isset($print) && $print==1)) { ?>
                                        <input type="checkbox" class="form-check-input" id="bc_select_all" title="Select all on this page">
                                    <?php } ?>
                                </th>
                                <th>No</th>
                                <th>Category</th>
                                <th>Sub Category</th>
                                <th>Barcode</th>
                                <th>Item</th>
                                <th>Image</th>
                                <th>Purchase Price</th>
                                <th>Selling Price</th>
                                <th>Dis(%) </th>
                                <th>Dis. </th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>FP</th>
                                <th>Action</th>
                                <th>Barcode</th>
                            </tr>
                            </thead>
                                <tbody>
                                    <?php 
                                    $row_count = $offset;
                                    foreach($prodData as $row) {
                                        $row_count++;
                                        ?>                                        
                                            <tr data-id="<?php echo $row['PDID'];?>">
                                            <td>
                                                <?php if($userType==1 || (isset($print) && $print==1)) { ?>
                                                    <input type="checkbox" class="form-check-input bc-select" title="Select for barcode printing">
                                                <?php } ?>
                                            </td>
                                            <td><?php echo $row_count; ?></td>
                                            <td><?php echo $row['CategoryName']; ?></td>
                                            <td><?php echo $row['SubCatName']; ?></td>
                                            <td><?php echo $row['Barcode']; ?></td>
                                            <td>
                                                <b>
                                                <?php 
                                                    echo $row['ItemName'];
                                                    if($shopObj->hasSecondLanguage($shop_id))
                                                    {
                                                        echo "<br>" . $row['SecondName'];
                                                    }//has second name
                                                    ?>
                                                </b>
                                            </td><!-- 4 -->
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
                                            </td><!-- 6 -->
                                            <td><?php echo $row['ProdPurchasePrice'];?></td>
                                            <td><?php echo $row['ProdSellPrice'];?></td>

                                            <td><?php echo $row['prodDiscount']; ?></td>
                                            <td><?php echo $row['prodFlatDiscount']; ?></td>
                                            
                                            <td>
                                                <?php 
                                                    if($row['ItemType'] == "P")
                                                    {
                                                        ?>
                                                        <p class="text-success" style="font-weight: 700;"><i class="ti ti-box"></i> Product</p>
                                                        <?php 
                                                    }//is a product
                                                    else
                                                    {
                                                        ?>
                                                        <p class="text-warning" style="font-weight: 700;"><i class="ti ti-man"></i> Service</p>
                                                        <?php 
                                                    }//is a service
                                                ?>
                                            </td><!-- 7 -->
                                            <td>
                                                <?php 
                                                if($row["ProductStat"]==1)
                                                {
                                                    ?>
                                                    <span class="badge bg-primary">Active</span>
                                                    <?php
                                                }
                                                else
                                                {
                                                    ?>
                                                    <span class="badge bg-danger">Inactive</span>
                                                    <?php
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if($row["is_fixedPrice"]==1)
                                                {
                                                    ?>
                                                    <span class="badge bg-primary">Fixed Price</span>
                                                    <?php
                                                }
                                                else
                                                {
                                                    ?>
                                                    <span class="badge bg-danger">No Fixed Price</span>
                                                    <?php
                                                }
                                                ?>
                                            </td>

                                            <td>
                                                <?php 
                                                    if($userType==1 || $create==1)
                                                    {
                                                        if($shopObj->hasVariation($shop_id))
                                                        {
                                                            ?>
                                                            <button type="button" id="btn_product_variant_<?php echo $row['PDID']?>" class="btn border border-primary m-1"><i class="ti ti-list"></i></button>
                                                            <?php 
                                                        }//has variations
                                                        ?>
                                                        <button type="button" id="btn_product_<?php echo $row['PDID']?>" class="btn_edit_product btn border border-primary m-1"><i class="ti ti-edit"></i></button>
                                                        <?php
                                                    }
        
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if($userType==1 || $print==1)
                                                {
                                                    ?>
                                                    <button class="btn border-primary btn_open_barcode"><small>Print Barcode</small></button>
                                                    <?php
                                                }
                                                ?>
                                            </td>         
                                            
                                        </tr>
                                    <?php } ?>
                                </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <nav>
                        <ul class="pagination justify-content-center">
                            <?php if ($current_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $current_page - 1; ?>">Previous</a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($current_page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $current_page + 1; ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>

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

    <!-- bootstrap first: the barcode dialog relies on its modal plugin -->
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <?php
    /*
     * The file modification time is appended so a browser can never serve a
     * stale script after a deployment - the URL changes with the file.
     */
    $product_js_ver = file_exists(__DIR__ . '/../Assets/jquery/product.js')
        ? filemtime(__DIR__ . '/../Assets/jquery/product.js') : time();
    $label_js_ver = file_exists(__DIR__ . '/../Assets/jquery/barcode-label.js')
        ? filemtime(__DIR__ . '/../Assets/jquery/barcode-label.js') : time();
    ?>
    <script src="../Assets/jquery/product.js?v=<?php echo $product_js_ver; ?>"></script>
    <script src="../Assets/jquery/barcode-label.js?v=<?php echo $label_js_ver; ?>"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>

    <script>
        $(document).ready(function(){
        });
    </script>

</body>
</html>