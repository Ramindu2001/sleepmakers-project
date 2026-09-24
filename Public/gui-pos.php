<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';
$noRight=1;
$dbObj = new DBTransactions();
$shopObj=new Shop();
$shop_id = $_SESSION['shop_id'];
$guiObj= new guiPOS;
$counterObj = new Counter();
$user_id = $_SESSION['user_id'];
$shopData=$shopObj->getOneShop($shop_id);



$is_minus=$shopData[0]["is_minus"];
$is_under_cost=$shopData[0]["is_under_cost"];
$isFixedPrice=$shopData[0]["is_fixedprice"];
$is_fixedprice=$shopData[0]["is_fixedprice"];



$doc=$guiObj->select_docno($shop_id);
    if(count($doc)==0) 
    {
        $inser_doc=$guiObj->insert_doc_no($shop_id);
        $doc=$guiObj->select_docno($shop_id);
    }
    $ws_no=$doc[0]["org_no"] + 1;
    $ws_no=$guiObj->getSequence($ws_no);
    $salesetings=$guiObj->getSaleSettings($shop_id);
    if(count($salesetings)>0)
    {
        if(isset($salesetings[0]["billNoHeader"]))
        {
            $ws_no=$salesetings[0]["billNoHeader"]."-".$ws_no;
        }
        else
        {
            $ws_no="RINV-".$ws_no;
        }
    }
    else
    {
        $ws_no="RINV-".$ws_no;
    }
function CounterCheck($user_id, $shop_id)
{
    //get current date time
    date_default_timezone_set("Asia/Colombo");
    $current_date = date("Y-m-d");

    $sql = "SELECT * FROM cashcounter WHERE user_USID=".$user_id." AND shop_SHID=".$shop_id." AND CounterStat = 1 AND CounterDate = '".$current_date."';";

    $dbObj = new DBTransactions();
    $dbData = $dbObj->getData($sql);

    if(!empty($dbData))
    {
        return true;
    }//has counter
    else
    {
        return false;
    }//no counter
}//counter check
//shop data
$sql="SELECT * FROM shop WHERE SHID='$shop_id'";
$shopdata = $dbObj->getData($sql);
//company data
$company_id=$shopdata[0]["Company_CMID"];
$sql="SELECT * FROM company WHERE CMID='$company_id' ";
$companydata = $dbObj->getData($sql);

$sql="SELECT * FROM salesmans WHERE SLID=1";
$salesmandata = $dbObj->getData($sql);

$sql="SELECT * FROM customers WHERE CTID=1";
$customerdata = $dbObj->getData($sql);
require_once "../Includes/warehouse_fulfilment.php";
//the shop this one can order from, so POS knows whether to offer warehouse items at all
$supplierShop = (new WarehouseOrder())->supplierShop($shop_id);
$supplierShopId = $supplierShop === null ? 0 : (int)$supplierShop["SHID"];
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
  // include '../View/loader.php';
  ?>
<link rel="stylesheet" href="../Assets/css/gui-pos.css">
</head>

<body>
    <div class="toast toast-onload align-items-center text-bg-primary border-0 fade" role="alert" aria-live="assertive" aria-atomic="true" id="toast">
        <div class="toast-body hstack align-items-start gap-6">
            <i class="ti ti-alert-circle fs-6"></i>
            <div>
                <h5 class="text-white fs-3 mb-1">Welcome to Synnex Cloud POS</h5>
                <h6 class="text-white fs-2 mb-0">Happiness is the key to Success.</h6>
            </div>
            <button type="button" class="btn-close btn-close-white fs-2 m-0 ms-auto shadow-none" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
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
        include '../View/gui-header.php';
        include "../View/modals/inventoryModal.php"; 
        include "../View/modals/HoldListModal.php"; 
        include "../View/modals/ShortcutListModal.php"; 
        include "../View/modals/productModal.php"; 
        include '../View/modals/add-customer-wholesale.php';
        include '../View/modals/warehouse-order.php';
        include "../View/modals/add-products.php";
        include "../View/modals/daily-sale-modal.php";
        if($shopObj->hascounter($shop_id)==1)
        {
            //check cash counter
            if(!CounterCheck($user_id, $shop_id))
            {
                // include "../View/modals/gui-close-counter.php";
                $_SESSION['status']=3;
                ?>
                <!-- <input type="text" name="countID" id="countID" value=""> -->
                <script>
                    
                    window.location="../Public/home.php";
                </script>
                <?php
            }//no counter
        }

        ?>
        <!--  Header End -->

        <div class="container-fluid">
            
            <div class="row">
                <div class="col-md-7">
                    <div class="card">
                        <div class="card-body">
                            <!-- <form id="order_form" action="../Controller/guiPosController.php" method="post"> -->
                            <form id="order_form" action="" method="post" class="preDefault">
                                <div class="d-none">
                                    <input type="hidden" name="is_minus" class="d-none" id="is_minus" value="<?=$is_minus?>">
                                    <input type="hidden" name="is_under_cost" class="d-none" id="is_under_cost" value="<?=$is_under_cost?>">
                                    <input type="hidden" name="is_fixed_price" class="d-none" id="is_fixed_price" value="<?=$isFixedPrice?>">     
                                    <input type="hidden" name="is_fixedprice" class="d-none" id="is_fixedprice" value="<?=$is_fixedprice?>">
                                </div>
                                <?php 
                                include "../View/modals/termConditionModal.php";                                 
                                include "../View/modals/GUI-payment.php";     
                                //80mm receipt, or the A4 invoice when this shop is set to print A4
                                $receiptFormatObj = new ReceiptFormat();
                                $invoices = $receiptFormatObj->getTemplate($shop_id, ReceiptFormat::TYPE_RETAIL);
                                $invoice_format = ($receiptFormatObj->getShopFormat($shop_id) == ReceiptFormat::FORMAT_A4) ? "a4" : "80mm";
                                ?>
                                <input type="hidden" name="invoices" id="invoices" value="<?=$invoices?>">
                                <input type="hidden" name="invoice_format" id="invoice_format" value="<?=$invoice_format?>">
                                <div class="card-invoice-head">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p class="card-title">
                                                    <b>Invoice No: <span id="invoice-no"><?=$ws_no?></span></b>
                                                    <input type="hidden" name="HIID" id="HIID" value="">
                                                </p>
                                                <select name="return_no" id="return_no" class="form-select"></select>
                                            </div>
                                            <div class="col-md-6">
                                                <?php 
                                                if(isset($shopdata[0]["is_salesman"]) && $shopdata[0]["is_salesman"]==1)
                                                {
                                                    ?>
                                                    <div class="input-group">
                                                        <select name="search-salesmen" id="search-salesmen" class="form-select">
                                                            <option value="">Default Salesman</option>
                                                        </select>
                                                    </div> 
                                                    <?php
                                                }
                                                ?>                                            
                                                <span id="sales_man" class="mt-2 f12"><small>Salesman -</small><b> <?=$salesmandata[0]["SalesmanNo"]." - ".$salesmandata[0]["SalesmansName"]?></b><br></span>
                                                <input type="hidden" name="salesmanid" id="salesmanid" value="1">

                                            </div>
                                            <div class="col-md-6 mt-4">
                                                <?php 
                                                if(isset($shopdata[0]["is_customers"]) && $shopdata[0]["is_customers"]==1)
                                                {
                                                    ?>
                                                    <div class="input-group">
                                                        <a href="javascript:void(0)" class="input-group-text">
                                                            <i class="ti ti-user"></i>
                                                        </a>
                                                        <select name="customer" id="search-customers" class="form-select">
                                                            <option value="">Common Customer</option>
                                                        </select>
                                                        <a href="javascript:void(0)" id="add-customer" class="input-group-text">
                                                            <i class="ti ti-user-plus"></i>
                                                        </a>
                                                    </div> 
                                                <?php 
                                                }
                                                ?>
                                                <span id="p_customer" class="mt-2 f12"><small>Customer -</small><b> <?=$customerdata[0]["CustomerNo"]." - ".$customerdata[0]["CustName"]?></b><br></span>
                                                <input type="hidden" name="customerid" id="customerid" value="1">
                                                <button id="use_exccese" class="btn btn-primary">Use Excess</button>
                                            </div>
                                            <div class="col-md-6 mt-4">
                                                <div class="input-group">
                                                    <a href="javascript:void(0)" class="input-group-text">
                                                        <i class="ti ti-barcode"></i>
                                                    </a>
                                                    <input type="text" name="" id="barcode-search" class="barcode-search form-control" placeholder="Barcode">
                                                    <a href="javascript:void(0)" id="add-item" class="input-group-text">
                                                        <i class="ti ti-plus"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                </div>
                                <div class="card-invoice-details mt-3">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th style="width:150px;">Item Name</th>
                                                    <th style="width:100px;">Quantity</th>
                                                    <th style="width:100px;">Unit Price</th>
                                                    <th>Discount Type</th>
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
                                <?php if($supplierShopId > 0): ?>
                                <div class="mb-2 text-end">
                                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#customItemModal">
                                        <i class="ti ti-tools"></i> Custom-made item
                                    </button>
                                </div>
                                <?php endif; ?>
                                <!-- Warehouse order: what this bill asks the warehouse to deliver -->
                                <div id="wh_banner" class="alert alert-warning d-flex align-items-center justify-content-between py-2 mb-2" style="display:none;">
                                    <span>
                                        <b><span id="wh_banner_count">0</span></b> item(s) will be delivered by the warehouse &mdash;
                                        <span id="wh_banner_state">needs the customer's details</span>.
                                    </span>
                                    <button type="button" id="wh_open_details" class="btn btn-sm btn-outline-secondary">Delivery details</button>
                                </div>
                                <input type="hidden" name="wh_supplier_shop" id="wh_supplier_shop" value="<?=$supplierShopId?>">
                                <input type="hidden" name="wh_customer_id" id="wh_customer_id" value="">
                                <div class="card-invoice-footer">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check py-2">
                                                <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
                                                <label class="form-check-label" for="flexCheckDefault">
                                                    Send A Message To Customer 
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-end w-100 d-block mt-2">
                                            <a class="tc">
                                                <i class="ti ti-file-invoice"></i> T&C</a>  
                                            </div>
                                            
                                        </div>
                                        <div class="col-md-6 mt-2">
                                            <label for="invoice_discount_type" class="form-label">Invoice Discount Type</label>
                                            <select name="invoice_discount_type" id="invoice_discount_type" class="form-select" onchange="grandTotal()">
                                                <option value="1">Percentage</option>
                                                <option value="2">Flat</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mt-2">
                                            <label for="invoice_discount" class="form-label">Invoice Discount</label>
                                            <input type="text" name="invoice_discount" class="form-control" id="invoice_discount" onchange="grandTotal()" onkeyup="grandTotal()" onkeydown="grandTotal()" onkeypress="grandTotal()" placeholder="Invoice Discount">
                                        </div>
                                        <div class="col-md-12 row mt-2">
                                            <div class="col-md-2 mt-2">
                                                <p class="invoice-foot-items total-qty-invoice text-end">Quantity: <br><span><b> 0.00</b></span></p>
                                                <input type="hidden" name="totQty" id="totQty" class="form-control text-end" value="0.00" readonly>
                                            </div>
                                            <div class="col-md-2 mt-2">
                                                <p class="invoice-foot-items total-gross-invoice text-end">Gross Amount: <br><span><b>Rs. 0.00</b></span></p>
                                                <input type="hidden" name="grossTotal" id="grossTotal" class="form-control text-end" value="0.00" readonly>
                                            </div>
                                            <div class="col-md-2 mt-2">
                                                <p class="invoice-foot-items total-discount-invoice text-end">Total Discount: <br><span><b>Rs. 0.00</b></span></p>
                                                <input type="hidden" name="totalDiscount" id="totalDiscount" class="form-control text-end" value="0.00" readonly>
                                                <input type="hidden" name="totalDiscountLine" id="totalDiscountLine" class="form-control" value="0.00" readonly>
                                            </div>
                                            <div class="col-md-2 mt-2">
                                                <p class="invoice-foot-items text-end">Total Return : <br><span id="returnSpan"><b>Rs. 0.00</b></span></p>
                                                <input type="hidden" name="return_id" id="return_id" class="form-control" readonly>
                                                <input type="hidden" name="returnamount" id="returnamount" class="form-control" readonly>
                                            </div>
                                            <div class="col-md-2 mt-2">
                                                <p class="invoice-foot-items total-net-invoice text-end">Net Total: <br><span><b>Rs. 0.00</b></span></p>
                                                <input type="hidden" name="netamount" id="netamount" class="netamount">
                                            </div>
                                        </div>
                                        <div class="col-md-12 row mt-2">
                                            <div class="col-md-2 mt-2 d-flex justify-content-end">
                                                <a href="javascript:void(0)" class="w-100 btn btn-info preDefault" id="dailySaleBtn"><i class="ti ti-chart-bar"></i> Daily Sale</a>
                                            </div>
                                            <div class="col-md-2 mt-2 d-flex justify-content-end">
                                                <a href="javascript:void(0)" class="w-100 btn btn-warning preDefault" id="invoiceHold"><i class="ti ti-hand-stop"></i> HOLD</a>
                                            </div>
                                            <div class="col-md-3 mt-2 d-flex justify-content-end">
                                                <a href="javascript:void(0)" id="Multi-pay" class="w-100 btn btn-dark preDefault"><i class="ti ti-credit-card"></i> MULTIPLE</a>
                                            </div>
                                            <div class="col-md-2 mt-2 d-flex justify-content-end">
                                                <a href="javascript:void(0)" class="w-100 btn btn-primary preDefault" id="pay_cash"><i class="ti ti-premium-rights"></i> CASH</a>
                                                <!-- <button type="submit" name="cash" class="w-100 btn btn-primary preDefault">
                                                    <i class="ti ti-premium-rights"></i> CASH
                                                </button> -->
                                            </div>
                                            <div class="col-md-3 mt-2 d-flex justify-content-end">
                                                <a href="javascript:void(0)" id="clearCart" class="w-100 btn btn-danger preDefault"><i class="ti ti-xbox-x"></i> Clear Cart</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>                            
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="card card-product">
                        <div class="card-body">
                            <div class="product-cat">
                                <div class="row ">
                                        <?php 
                                        if($shopdata[0]["is_category"]==1)
                                        {
                                            ?>
                                            <div class="col-md-6 mt-2">
                                                <div class="input-group">
                                                    <select name="main_cat" id="main-cat" class="form-select main-cat">
                                                        <option value="">Main Category</option>
                                                    </select>
                                                    <a href="javascript:void(0)" id="refresh-main-cat" class="input-group-text">
                                                        <i class="ti ti-refresh fs-4"></i>
                                                    </a>
                                                </div>                                    
                                            </div>
                                            <div class="col-md-6 mt-2">
                                                <div class="input-group">
                                                    <select name="subcat" id="subcat" class="form-select">
                                                        <option value="">Sub Category</option>
                                                    </select>
                                                    <a href="javascript:void(0)" id="refresh-sub-cat" class="input-group-text">
                                                        <i class="ti ti-refresh fs-4"></i>
                                                    </a>
                                                </div>                                    
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    
                                    <div class="col-md-12 search-div">
                                        <div class="input-group">
                                            <input type="text" class="form-control preDefault" id="search-product" placeholder="Search Item ">
                                            <a href="javascript:void(0)" id="refresh-pro" class="input-group-text">
                                                <i class="ti ti-refresh fs-4"></i>
                                            </a>
                                        </div> 
                                    </div>
                                    <div class="col-md-12 mt-2">
                                        <p class="w-100 text-end">No of Results: <span id="procount"></span></p>
                                    </div>
                                    <hr>
                                </div>   
                            </div>
                            
                            <div class="product-list">
                                <div class="row" id="product-list">
                                    
                                </div>
                            </div>
                        </div>
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
    <!-- <?php include '../View/footer.php';?>  -->
    <!-- footer End  -->
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/jquery/guipos.js?v=20260924b"></script>
    <script src="../Assets/jquery/pos_warehouse_order.js?v=20260924b"></script>
    <script src="../Assets/jquery/daily-sales.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="../Assets/jquery/toast.js"></script>
    

</body>
</html>