<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';
$slrtObj=new Sales_return_class();
$userObj = new User();
?>

<!doctype html>
<html lang="en">

<head>
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <!-- jQuery (Required for Toastr) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
  <?php 
  include '../View/head.php';
  $shop_id = $_SESSION['shop_id'];
  $user_id=$_SESSION['user_id'];
  $user = $userObj->getOneUser($user_id);   
  $userType=$user[0]["UserType"];
  $shops=$slrtObj->selectShop($userType,$user_id); 
  $returnType=$slrtObj->selectAllReturnType(); 
  ?>
  <style>
    .card-header 
    {
        background: #fff;
        border-bottom: 1px solid #f5f5f5;
        padding: 10px;
    }
    .w-80
    {
        width: 80%;
    }
    .cart-table thead
    {
        background: black;
        color: #fff;
    }
    .cart-table thead th,
    .cart-table tbody td
    {
        border: 1px solid #e7e7e7;
    }
    .border-no
    {
        border: none !important;
    }
    .return-header
    {
        padding: 0 150px;
    }
    .invoices
    {
        display: none;
    }
    .toast-success
    {
        background-color: #13deb9 !important;
    }
    .toast-error
    {
        background-color: #ff0000 !important;
    }
    @media screen and (max-width:850px) {
        .return-header
        {
            padding: 0 ;
        }
        .select2
        {
            width: 70% !important;
        }
    }
    @media screen and (max-width:1050px) {
        .select2
        {
            width: 65% !important;
        }
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
        include "../View/modals/inventoryModal.php"; 
        include "../View/modals/productModal.php"; 
        ?>
        <!--  Header End -->

        <div class="container-fluid">
            <!-- messages -->
        <div class="container">

        </div>
            <div class="row">
                <div class="col-md-12 card p-2 mt-3">
                    <div class="card-header d-flex align-items-center gap-2">
                        <h5 class="card-title fw-semibold mb-3">Sales Return</h5>
                    </div>
                    <div class="card-body">
                        <!-- <form action="" id="form" class="row" method="post"> -->
                        <form id="form" class="row" method="post">
                            <div class="return-header row col-md-12">
                                <div class="col-md-6 mt-2">
                                    <label for="select-shop" class="form-label">Select Shop <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <a href="javascript:void(0)" class="input-group-text">
                                            <i class="ti ti-home"></i>
                                        </a>
                                        <input type="hidden" name="ori_shop" id="ori_shop" value="<?=$shop_id?>">
                                        <select name="shop_id" id="select-shop" class="form-select" required>
                                            <?php 
                                            foreach($shops AS $row)
                                            {
                                                ?>
                                                <option value="<?=$row["SHID"]?>" <?php 
                                                if($row["SHID"]==$shop_id)
                                                {
                                                    echo "selected";
                                                }
                                                ?>><?=$row["ShopName"]?></option>
                                                <?php
                                            }
                                            ?>
                                        </select>
                                    </div>                             
                                </div>
                                <div class="col-md-6 mt-2">
                                    <label for="search-customers" class="form-label">Search Customer <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <a href="javascript:void(0)" class="input-group-text">
                                            <i class="ti ti-user"></i>
                                        </a>
                                        <select name="customer" id="search-customers" class="form-select" >
                                            <option value="">Common Customer</option>
                                        </select>
                                    </div> 
                                    <p id="" style="padding:10px 0 0; color: red; text-align: center; font-weight: bold;"><span id="error-msg-customer" style="display: none;"> <i class="ti ti-alert-triangle"></i> Customer is mandatory</span></p>
                                </div>
                                <div class="col-md-6 mt-2">
                                    <label for="start" class="form-label">Effective <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <a href="javascript:void(0)" class="input-group-text">
                                            <i class="ti ti-calendar"></i>
                                        </a>
                                        <?php $start=date("Y-m-d")?>
                                        <input type="date" name="start" id="start" class="form-control" value="<?=$start?>" readonly required>
                                    </div>
                                </div>
                                <div class="col-md-6 mt-2">
                                    <label for="return-type" class="form-label">Select Type <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <a href="javascript:void(0)" class="input-group-text">
                                            <i class="ti ti-cash"></i>
                                        </a>                                        
                                        <select name="return-type" id="return-type" class="form-select">
                                            <?php 
                                            foreach($returnType AS $row)
                                            {
                                                ?>
                                                <option value="<?=$row["SRTID"]?>"><?=$row["SRT_Name"]?></option>
                                                <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 mt-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input primary" type="checkbox" name="with-invoice" id="with-invoice" value="1">
                                        <label class="form-check-label" for="with-invoice">With Invoice</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mt-3">
                                    <div class="invoices" style="height: 80px;">
                                        <label for="search-invoices" class="form-label"> Select Invoice<span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <a href="javascript:void(0)" class="input-group-text">
                                                <i class="ti ti-file-invoice"></i>
                                            </a> 
                                            <select name="search-invoices" id="search-invoices" class="form-select">
                                                <option value=""></option>
                                            </select>    
                                        </div>
                                        <p id="error-msg-invoice" style="padding:10px 0 0; color: red; display: none; text-align: center; font-weight: bold;"><i class="ti ti-alert-triangle"></i> Invoice is mandatory</p>
                                    </div>                            
                                </div>
                            </div>
                            <hr class="mt-5 mb-3">
                            <div class="col-md-12 d-flex justify-content-center">
                                <div class="input-group w-80">
                                    <a href="javascript:void(0)" class="input-group-text">
                                        <i class="ti ti-barcode"></i>
                                    </a>
                                    <select name="itemSearch" id="search-items" class="form-select">
                                        <option value=""></option>
                                    </select>
                                </div> 
                            </div>
                            <div class="col-md-12 mt-3">
                                <div class="table-responsive">
                                    <table class="table cart-table">
                                        <thead>
                                            <tr>
                                                <th style="width:350px;">Item Name</th>
                                                <th style="width:300px;">Quantity</th>
                                                <th style="width:200px;">Unit Price</th>
                                                <th style="width:100px;">Discount Type</th>
                                                <th>Discount (Per Unit)</th>
                                                <th>Subtotal</th>
                                                <th class="text-center" style="width:50px;">
                                                    <i class="ti ti-trash"></i>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody id="cart">

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <hr class="mt-5 mb-3">
                            <div class="col-md-12 row">
                                <div class="col-md-6">
                                    <label for="return-type" class="form-label"> Reason <span class="text-danger">*</span></label>
                                    <textarea name="remarks" class="form-control" id="remarks" rows="3" style="height: 120px !important;" ></textarea>    
                                    <p id="error-msg" style="padding:10px 0 0; color: red; display: none; text-align: center; font-weight: bold;"><i class="ti ti-alert-triangle"></i> Reason is mandatory</p>  
                                </div>
                                <div class="col-md-6">
                                    <table class="table">
                                        <tbody>
                                            <tr class="border-no">
                                                <th class="text-end border-no">Total Quantity: <input type="hidden" name="totQty" class="d-none" id="totQty"></th>
                                                <td class="text-end border-no" id="totalQty">0.00 </td>
                                            </tr>
                                            <tr class="border-no">
                                                <th class="text-end border-no">Gross Total: <input type="hidden" name="grossTotal" class="d-none" id="grossTotal"></th>
                                                <td class="text-end border-no" id="totalOriginalRateTotal">0.00 </td>
                                            </tr>
                                            <tr class="border-no">
                                                <th class="text-end border-no">Total Discount: <input type="hidden" name="totalDiscount" class="d-none" id="totalDiscounti"></th>
                                                <td class="text-end border-no" id="totalDiscount">0.00 </td>
                                            </tr>
                                            <tr class="border-no">
                                                <th class="text-end border-no">Net Total: <input type="text" name="netamount" class="d-none" id="netamount"></th>
                                                <td class="text-end border-no" id="NetTotal">0.00 </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <hr class="mt-5 mb-3">
                            <div class="col-md-12 mt-2 d-flex justify-content-center">
                                <div class="w-20 p-3">
                                    <button type="button" class="btn waves-effect waves-light btn-success w-100" id="subBtn"><i class="ti ti-send fs-4" ></i> Submit</button>
                                    <!-- <button type="submit" class="btn waves-effect waves-light btn-success w-100"><i class="ti ti-send fs-4"></i> Submit</button> -->
                                </div>                                                               
                                <div class="w-20 p-3">
                                    <button type="button" class="btn waves-effect waves-light btn-danger w-100" id="close"><i class="ti ti-trash"></i> Close</button>
                                </div>                                                               
                            </div>
                        </form>
                    </div>
                </div>
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
    <script src="../Assets/jquery/salesReturn.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="../Assets/jquery/toast.js"></script>
    




</body>
</html>