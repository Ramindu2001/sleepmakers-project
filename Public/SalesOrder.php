<?php 
    include '../Includes/includes.php';
    include '../Includes/authcheck.php';

    // Fetch unique customer names for the dropdown
    $objCus = new Customer();
    $customerNames = $objCus->getCustomerNames(); // Method to fetch unique customer names

    // Fetch sales order no
    $sql = "SELECT (IFNULL(MAX(id),0) + 1) AS SID FROM sales_orders";
    $dbObj = new DBTransactions(); 
    $Data = $dbObj->getData($sql);  
    
    $invoice_no = "SO" . str_pad($Data[0]['SID'], 6, "0", STR_PAD_LEFT);
    $effective_date = date("Y-m-d");

    // Fetch product data
    $sql = "SELECT PDID, ItemName FROM products";  // Adjust the table name if necessary
    $productData = $dbObj->getData($sql); // Fetch product data from database

    if (!is_array($productData)) {
        $productData = []; // Set an empty array to prevent foreach errors
    }

    $shop_id = $_SESSION['shop_id'];
  
?>

<!doctype html>
<html lang="en">

<head>
    <?php 
        include '../View/head.php';  
        
    ?>
    <style>
    .container.table-responsive {
        padding: 0 !important;
        margin: 0 !important;
        width: 100%;
    }

    @media (min-width: 1400px) {
        .container, .container-lg, .container-md, .container-sm, .container-xl, .container-xxl {
            max-width: 100%;
        }
    }
    </style>
</head>

<body>
    <div class="h-100vh">
        
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full" data-sidebar-position="fixed" data-header-position="fixed">
            <?php 
                include '../View/sidebar.php';
                if($userType==0)
                {
                    $userObj = new User();
                    $feature_id = 2;
                    $checkview = $userObj->userAcces($userRole_id, $feature_id);
                    $edit = $checkview[0]["is_edit"];
                    $verify = $checkview[0]["is_verify"];
                    if($edit == 1 || $verify == 1) {
                    } else {
                        ?>
                        <script>
                            window.location.href = "./home.php";
                        </script>
                        <?php
                    }
                }
                include "../View/modals/add_grn_product.php";
            ?>

            <div class="body-wrapper">

                <?php include '../View/header.php'; ?>
                
                <form id="salesOrderForm" request="POST">
  
                <div class="container-fluid">
                        
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="card-title fw-semibold mb-3" style="margin-top: 0px; color: #000;">Sales Order</h2>
                                </div>
                                
                                <div class="card-body">

                                    <div class="row">

                                        <h2 class="card-title fw-semibold mb-3" style="margin-top: 0px; color: #000;">Sales Order</h2>
                                        <div class="col-md-6">
                                            <label for="" class="form-label">Invoice No</label>
                                            <input type="text" name="invoice_no" id="invoiceno" value="<?php echo $invoice_no;?>" class="form-control" placeholder="Sales Order No" disabled>
                                            <input type="hidden" name="shop_no" id="shop_no" value="<?php echo $shop_id;?>" class="form-control">
                                        </div>

                                        <div class="col-md-6">
                                            <label for="customerName" class="form-label">Customer Name</label>
                                            <select name="customerCTID" id="customerCTID" class="form-select">
                                                <option value="">Select Customer</option>
                                                <?php foreach ($customerNames as $customer): ?>
                                                    <option value="<?= htmlspecialchars($customer['CTID']) ?>" 
                                                        <?= isset($_GET['customerCTID']) && $_GET['customerCTID'] === $customer['CTID'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($customer['CustName']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>                                

                                        <div class="col-md-6">
                                            <label for="" class="form-label">Effective Date</label>
                                            <input type="date" name="effective_date" value="<?php echo $effective_date;?>" class="form-control" readonly>                                  
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">
                                        <button class="btn btn-primary float-end" id="btn_open_grn_products">Add Products</button>
                                    </h5>
                                </div>
                                
                                <div class="card-body">
                                    <div class="container-fluid">
                                        <div class="container table-responsive">
                                            <table class="table table-hover" id="tbl_add_details">
                                                <tr>
                                                    <th style="min-width:600px;">Product</th>                                  
                                                    <th style="min-width:150px;">Selling Price</th>
                                                    <th style="min-width:150px;">Qty</th>
                                                    <th style="min-width:150px;">Action</th>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <select name="cmb_product" id="cmb_product" class="form-select required">
                                                            <option value="">=== Select an Item ===</option>
                                                            <?php foreach ($productData as $product): ?>
                                                                <option value="<?= $product['PDID'] ?>"><?= htmlspecialchars($product['ItemName']) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.01" name="selling_price" id="selling_price" class="form-control required" placeholder="Selling Price">
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.001" name="prod_qty" id="prod_qty" class="form-control required" placeholder="Qty">
                                                    </td>
                                                    <td>
                                                        <button type="button" name="btn_add_salOrder_detail" id="btn_add_salOrder_detail" class="btn border border-success bg-success">
                                                            <i class="ti ti-plus"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>

                                        <div class="container table-responsive">
                                            <table id="tbl_SO_details" class="table table-hover">
                                                <tr>
                                                    <th style="min-width:250px;">Product</th>                                  
                                                    <th style="min-width:100px;">Qty</th>                                           
                                                    <th style="min-width:100px;">Selling Price</th>                                            
                                                    <th style="min-width:100px;">Total</th>   
                                                </tr>
                                                <tbody id="tbody">
                                                    <!-- <?php 
                                                        foreach($grnData as $row) {
                                                    ?>
                                                    <tr>
                                                        <td><?php echo $row['Barcode'];?> <br> <?php echo $row['ItemName'];?></td>
                                                        <td><?php echo $row['InitQty']." ".$row['ShortName'];?></td>
                                                        <td><?php echo $row['UnitSellPrice'];?></td>
                                                        <td><?php echo $row['TotalSellPrice'];?></td>
                                                    </tr>
                                                    <?php } ?> -->
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6"></div>
                                            <div class="col-md-6">
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                            <th>Row Count</th>
                                                            <td><h4 id="sub_row_count" style="text-align: right;"></h4></td>
                                                        </tr>
                                                        <tr>
                                                            <th>No. Of Items</th>
                                                            <td><h4 id="sub_item_count" style="text-align: right;"></h4></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Total</th>
                                                            <td><h4 id="sub_purchase_price" style="text-align: right;"></h4></td>
                                                        </tr>
                                                    </thead>
                                                </table>
                                            </div>
                                        </div>

                                        <div>
                                        
                                            <!-- Button trigger modal -->            
                                            <button type="submit" name="btn_save_sales_order"
                                                        class="btn bg-primary-subtle text-primary waves-effect"
                                                        style="margin-left:10px;" data-bs-dismiss="modal" id="btn_save_sales_order">
                                                        Save
                                            </button>

                                            <button type="submit" name="btn_cancel_SO"
                                                        class="btn bg-danger-subtle text-danger waves-effect"
                                                        style="margin-left:10px;" data-bs-dismiss="modal" id="btn_cancel_SO">
                                                        Cancel
                                            </button>
                                    
                                        </div>
                                                
                                    </div>
                                    
                                </div>
                            </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../View/footer.php';?>
    <script src="../Assets/jquery/salesorder.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>

</body>
</html>
