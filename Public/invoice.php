<?php 

include '../Includes/includes.php';
include '../Includes/authcheck.php';

$user_id = $_SESSION['user_id'];
$shop_id = $_SESSION['shop_id'];
//check cash counter
$counter_id = CounterCheck($user_id, $shop_id);

if($counter_id == '0')
{
    $_SESSION['status']=3;
    header('Location: home.php');
    exit; //without this the page kept running after the redirect (and failed in the Today Sale panel)
}//no counter

// createNewSaleHeader($user_id, $shop_id);
$tmp_bill_no = CreateTmpNo($shop_id); 

$category_class= new Category();
$categories=$category_class->getCategoryByShop($shop_id);

$product_class=new Product(); 

//validate invoice settings
$dbObj = new DBTransactions();

//check paymethods
$sql = "SELECT * FROM shoppaymethod WHERE shop_SHID = ".$shop_id.";";
$payData = $dbObj->getData($sql);
if(empty($payData))
{
    $_SESSION['status']=4;
    header('Location: home.php');
}//no paymethod assigned

//check sale settings
$sql = "SELECT * FROM salesettings WHERE shop_id = ".$shop_id.";";
$settingData = $dbObj->getData($sql);
if(empty($settingData))
{
    $_SESSION['status']=5;
    header('Location: home.php');
}//no paymethod assigned

//check shop receipt
$sql = "SELECT * FROM shopreceipts WHERE shop_id=".$shop_id.";";
$receiptData = $dbObj->getData($sql);
if(empty($receiptData))
{
    $_SESSION['status']=6;
    header('Location: home.php');
}//no paymethod assigned

?>
<!doctype html>
<html lang="en">

<head>
  <?php
  include '../View/head.php';
  include '../View/modals/pos-GUI-modal.php';
  include '../View/modals/add-customer.php';
  include "../View/modals/payment.php";
  include "../View/modals/holdInvoice.php";
  include "../View/modals/add-service.php";
  include "../View/modals/receiptprint.php";
  include "../View/modals/todaySale.php";
  include "../View/modals/invoice-discount.php";
  include "../View/modals/invoice_expense.php";
  //// include '../View/loader.php';
  ?>

  <link rel="stylesheet" href="../Assets/css/invoice_style.css">
</head>

<body>
  <!--  Body Wrapper -->
    <div class="h-100vh">
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="mini-sidebar"
        data-sidebar-position="fixed" data-header-position="fixed">
            <!-- Sidebar Start -->
            <?php 
            // include '../View/sidebar.php';
            ?>
            <!--  Header Start -->
                <?php 
                // include '../View/header.php';
                ?>

                <!--  Header End -->
                <div class="container-fluid">

                <!--------------------------- hidden sell header id --------------------------->
                <input type="hidden" name="hide_sell_header_id" id="hide_sell_header_id" value="0">
                <input type="hidden" name="hide_tmp_no" id="hide_tmp_no" value="<?php echo $tmp_bill_no;?>">
                <input type="hidden" name="hide_counter_id" id="hide_counter_id" value="<?php echo $counter_id;?>">

                <!---------------------------------- top bar ---------------------------------->
                <div id="div_topbar">
                    <div>
                    <?php 
                        $sql = "SELECT * FROM shop WHERE SHID = ".$shop_id.";";
                        $shopData = $dbObj->getData($sql);

                        $shop_name = $shopData[0]['ShopName'];
                        $shop_logo =  $shopData[0]['ShopLogo'];

                        //echo "shop - " . $shop_id;
                        ?>
                        <a href="../Public/home.php">
                            <img src="../Assets/Images/shop_images/<?php echo $shop_logo;?>" alt="Home" id="img_shop">
                        </a>
                    </div>
                    <!-- buttons -->
                    <div>
                        <!-- Home -->
                        <a href="../Public/home.php" class="link_inv_nav">
                            <img src="../Assets/Images/invoice_icons/home_nav.png" alt="Home" class="img_inv_nav">
                        </a>
                        <!-- print -->
                        <button class="btn_inv_nav" id="btn_open_receiptprint">
                            <img src="../Assets/Images/invoice_icons/print_nav.png" alt="hold" class="img_inv_nav">
                        </button>
                        <!-- Sale -->
                        <button class="btn_inv_nav" id="btn_open_todaysale">
                            <img src="../Assets/Images/invoice_icons/sale_nav.png" alt="hold" class="img_inv_nav">
                        </button>
                        <?php 
                            if($shopObj->hasService($shop_id))
                            {
                                ?>
                                <!-- Service -->
                                <button class="btn_inv_nav" id="btn_open_service">
                                    <img src="../Assets/Images/invoice_icons/service_nav.png" alt="hold" class="img_inv_nav">
                                </button>
                                <?php 
                            }//has service

                            if($shopObj->hasExpenses($shop_id))
                            {
                                ?>
                                <!-- Expenses -->
                                <button class="btn_inv_nav" id="btn_open_expense">
                                    <img src="../Assets/Images/invoice_icons/expenses_nav.png" alt="hold" class="img_inv_nav">
                                </button>
                                <?php 
                            }//has expenses
                        ?>
                        
                        <!-- Return -->
                        <a href="../Public/sales-return.php" class="link_inv_nav">
                            <img src="../Assets/Images/invoice_icons/return_nav.png" alt="Home" class="img_inv_nav">
                        </a>
                        <!-- customers -->
                        <button class="btn_inv_nav" id="btn_open_customers">
                            <img src="../Assets/Images/invoice_icons/customers_nav.png" alt="hold" class="img_inv_nav">
                        </button>
                        
                    </div>
                    <!-- Hold -->
                    <div>
                        <form action="../Controller/invoiceController.php" method="post">
                            <input type="hidden" name="hide_header_id" id="hide_header_id" value="0">
                            <input type="hidden" name="hide_hold_tmp_no" id="hide_hold_tmp_no" value="<?php echo $tmp_bill_no;?>">
                            <button type="submit" name="btn_add_hold" class="btn_inv_nav">
                                <img src="../Assets/Images/invoice_icons/hold_nav.png" alt="hold" class="img_inv_nav">
                            </button>
                            <!-- open hold -->
                            <button type="button" class="btn_inv_nav" id="btn_open_hold">
                                <img src="../Assets/Images/invoice_icons/open_hold_nav.png" alt="hold" class="img_inv_nav">
                            </button>
                        </form>
                    </div>

                    <!-- Invoice Discount -->
                    <div>
                        <!-- Invoice Discount -->
                        <button class="btn_inv_nav" id="btn_open_discount">
                            <img src="../Assets/Images/invoice_icons/discount_nav.png" alt="discount" class="img_inv_nav">
                        </button>
                    </div>

                    <div class="p-2 rounded">
                        <h5 class="text-light bg-primary p-2 rounded" id="h5_bill_no">Bill No: <?php echo $tmp_bill_no;?></h5>
                    </div>

                    <div class="form-check form-switch mt-2">
                        <?php 
                        //get sale details
                        $sql = "SELECT * FROM salesettings WHERE shop_id = ".$shop_id.";";
                        $dbObj = new DBTransactions();
                        $settingData = $dbObj->getData($sql);
                        $item_add_option = $settingData[0]['billAddOption'];
                        $is_checked = "";
                        if(!empty($settingData))
                        {
                            $is_checked = $settingData[0]['billAddOption']== 0 ? 'checked' : '';
                        }//not empty has settings
                        else
                        {
                            header("Location: home.php");
                        }//no sale settings
                        ?>
                        <input type="hidden" id="item_add_option" value="<?php echo $settingData[0]['billAddOption'];?>">
                        <input type="hidden" id="item_add_duration" value="<?php echo $settingData[0]['qtyAddDuration'];?>">
                        <input class="form-check-input" type="checkbox" id="chk_auto_add" <?php echo $is_checked;?>>
                        <label class="form-check-label" for="chk_auto_add">Auto Add to Cart</label>
                    </div>

                    <div>
                        <a href="./logout.php" class="link_inv_nav">
                            <img src="../Assets/Images/invoice_icons/logout_nav.png" alt="Home" class="img_inv_nav">
                        </a>
                    </div>
                    
                </div>
                <!---------------------------------- end top bar ---------------------------------->

                <div class="bg-primary">
                    <?php 
                    if(isset($_SESSION['invoice_update']))
                    {
                        echo "Session - " . $_SESSION['invoice_update'];

                        unset($_SESSION['invoice_update']);
                    }//session set
                    ?>
                </div>

                <div class="row">
                    <!-- <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">GUI POS</h5> -->
                    <div class="col-md-6 ">
                        <div class="row">
                            <div class="col-md-2 p-2 ">
                                <div id="categories">
                                    <a href="javascript:void(0);" class="btn btn-primary w-100 mb-2 active cat" id="all" data-id="0">All</a>
                                    <?php
                                    foreach ($categories as $category)
                                    {
                                        ?>
                                        <a href="javascript:void(0);" class="btn btn-primary w-100 mb-2 cat" id="<?=$category["CTID"]?>" data-id="<?php echo $category["CTID"]?>" ><small><?=$category["CategoryName"]?></small></a>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="col-md-10 p-2" id="product-main">
                                <div class="mb-2" id="search-sec">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="row">
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="search" placeholder=" Search Products">
                                                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!---------- Search Item -------->
                                <div id="div_item_grid" class="row">
                                    <?php 
                                    $shopObj = new Shop();
                                    if($shopObj->hasMinus($shop_id))
                                    {
                                        $sql = "SELECT * FROM pricehistory
                                        INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
                                        INNER JOIN products ON products.PDID = inventory.products_PDID
                                        LEFT JOIN variations ON variations.VRID = pricehistory.VariationID
                                        WHERE inventory.shop_SHID = ".$shop_id." GROUP BY products.PDID LIMIT 50;";
                                    }//has minus stock
                                    else
                                    {
                                        $sql = "SELECT * FROM pricehistory
                                        INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
                                        INNER JOIN products ON products.PDID = inventory.products_PDID
                                        LEFT JOIN variations ON variations.VRID = pricehistory.VariationID
                                        WHERE inventory.shop_SHID = ".$shop_id." AND CurrentQty > 0 GROUP BY products.PDID LIMIT 50;";
                                    }//no minus
        
                                    $dbObj = new DBTransactions();
                                    $dbData = $dbObj->getData($sql);

                                    foreach($dbData as $row)
                                    {
                                        $image_path = "";   
                                        if(empty($row['ProdImage']))
                                        {
                                            $image_path = "../Assets/Images/icons/product.png";
                                        }//empty
                                        else
                                        {
                                            $image_path = "../Assets/Images/prod_images/" . $row['ProdImage'];
                                        }
                                        ?>
                                        <div class="col-md-3 p-3">
                                            <div class="card shadow border-primary div_item" data-id="<?php echo $row['PHID'];?>" data-item="<?php echo $row['PDID'];?>" id="div_grid_item">
                                                <!-- <p class="m-1 p-1 bg-primary text-light text-center rounded"><b><?php //echo $row['CurrentQty'] + 0;?></b></p> -->
                                                <img src="<?php echo $image_path;?>" alt="Product Image" class="img_grid_item">
                                                <p class="m-1 p-1 text-center" style="overflow:hidden;"> 
                                                    <small><?php echo $row['Barcode'];?> <br> <?php echo $row['ItemName'];?></small><br>
                                                    <b>Rs: <?php echo $row['SellingPrice'];?></b>
                                                </p>
                                            </div>                                            
                                        </div>
                                        <?php 
                                    }//foreach
                                    ?>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="col-md-6" id="border-left">
                        <div id="customer-search barcode-search">
                            <div class="row">
                                <div class="col-md-6 p-2">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="ti ti-barcode"></i></span>
                                        <input type="text" class="form-control" id="barcode_input" placeholder="Barcode">
                                    </div>
                                </div>
                                <div class="col-md-6 p-2">
                                    <div class="input-group">
                                        <a href="javascript:void(0);" class="input-group-text" id="add-customer"><i class="ti ti-user-plus" ></i></a>
                                        <input type="text" class="form-control" id="customer" placeholder="Search Customers">
                                        <input type="hidden" name="" id="customer_id" value="">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="cart">
                            <div class="table-responsive" style="min-height: 400px;" id="div_cart">
                                <table id="tbl_cart">
                                    <?php 
                                        // $dbObj = new DBTransactions();

                                        // $sql = "SELECT * FROM selldetail 
                                        // INNER JOIN pricehistory ON pricehistory.PHID = selldetail.pricehistory_id
                                        // INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
                                        // INNER JOIN products ON products.PDID = inventory.products_PDID
                                        // LEFT JOIN variations ON variations.VRID = pricehistory.VariationID
                                        // WHERE sellheader_id = ".$header_id.";";
                                        
                                        // $dbData = $dbObj->getData($sql);
                                        // foreach($dbData as $row)
                                        // {
                                            ?>
                                            <!-- <tr data-id='<?php //echo $row['SDID'];?>'>
                                                <td style="display:none"><?php //echo $row['SDID'];?></td>
                                                <td><?php //echo $row['Barcode'] ."<br>" . $row['ItemName'];?></td>
                                                <td><?php //echo $row['sellQty'] + 0;?></td>
                                                <td><?php //echo $row['SellingPrice'];?></td>
                                                <td><?php //echo $row['sellAmount'];?></td>
                                                <td><?php //echo $row['sellDiscount'];?></td>
                                                <td><?php //echo $row['soldAmount'];?></td>
                                                <td>
                                                    <button id="btn_sell_<?php //echo $row['SDID'];?>" class="tbl_cart_row btn border border-primary "><i class="ti ti-edit"></i></button>
                                                    <button id="btn_sell_delete_<?php //echo $row['SDID'];?>" class="btn border border-danger cart_row_delete"><i class="ti ti-x"></i></button>
                                                </td>
                                            </tr> -->
                                            <?php
                                       // }//foreach
                                    ?>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

    <div id="div_payment_footer">
        <div class="row">
            <div class="col-md-6">
                <p><small>Synnex IT Solution</small></p>
            </div>
            <div class="col-md-6">
                <div class="row">
                    <div class="col-md-6">
                        <p id="p_cart_detail"></p>
                    </div>
                    <div class="col-md-6">
                        <button id="btn_open_payment">Pay: </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <!-- footer Start  -->
        <?php include '../View/footer.php';?>
        <!-- footer End  -->
        
      </div>
    <!-- </div> -->
  </div>
</div>

<!-- ------------------------ Modals add items ------------------------ -->
<div class="modal" tabindex="-1" role="dialog" id="modal_add_item">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Item</h5>
        <button type="button" id="close_item_modal" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <input type="text" id="item_search_modal" class="form-control" placeholder="Search Items">
        <div id="div_modal_item" style="max-height: 500px; overflow:scroll; overflow-x:hidden;">
            <table id="tbl_more_search" class="table table-hover">
                
            </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!----------------------------- Qty Add Modal ------------------------->
<div class="modal" tabindex="-1" role="dialog" id="modal_add_qty">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Qty</h5>
        <button type="button" id="close_qty_modal" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="frm_add_item">
        <div class="modal-body">
            <input type="hidden" name="hide_pricehistory_id" id="hide_pricehistory_id" value="0">
            <input type="hidden" name="hide_item_id" id="hide_item_id" value="0">
            <input type="hidden" name="hide_header_id" id="hide_header_id" value="<?php echo $header_id;?>">
            <input type="hidden" name="hide_sell_price" id="hide_sell_price" value="0">

            <div id="div_item_info"></div>

            <!-- progress bar -->
            <div id="progress-bar-container">
                <div id="progress-bar"></div>
            </div>

            <div class="form-group mt-2">
                <label for="item_qty"> Qty</label>
                <div class="row">
                    <div class="col-md-2">
                        <button type="button" id="btn_minus_qty" class="btn btn-primary w-100"><i class="ti ti-minus"></i></button>
                    </div>
                    <div class="col-md-8">
                        <input type="number" step="0.001" name="item_qty" id="item_qty" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <button type="button" id="btn_add_qty" class="btn btn-primary w-100"><i class="ti ti-plus"></i></button>
                    </div>
                </div>
            </div>

            <?php 
                $shopObj = new Shop();

                $is_fixed_price = $shopObj->hasFixedPrice($shop_id) ? 'disabled' : "";
            ?>
            <div class="form-group mt-2">
                <label for="Price">Price</label>
                <input type="number" step="0.01" name="sell_amount" id="sell_amount" class="form-control" <?php echo $is_fixed_price;?>>
            </div>
                    
        </div>

        <div class="modal-footer">
            <button type="button" name="btn_add_item" id="btn_add_item" class="btn btn-primary">Add Item</button>
        </div>
      </form>

    </div>
  </div>
</div>

<!----------------------------- item edit Modal ------------------------->
<div class="modal" tabindex="-1" role="dialog" id="modal_edit_item">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Item</h5>
        <button type="button" id="close_edititem_modal" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
      </div>

        <div class="modal-body">
            <div class="row">
                <div class="col-md-4">
                    <img src="" alt="Item Image" id="img_cart_edit" style='width:100%; height:auto;'>
                </div>
                <div class="col-md-8">
                    <p id="p_cart_edit_details"></p>
                </div>
            </div>

            <div>
                <label for="">Sell Qty</label>
                <input type="number" step=0.001 id="edit_cart_qty" value="0" class="form-control">
                <span class="text-danger" id="item_qty_warning" style="display: none;">Invelid qty</span>

                <input type="hidden" id="hide_selldetail_id" value="0">
                <input type="hidden" id="hide_sellamount" value="0">
                <input type="hidden" id="item_unit_price" value="0">
                <input type="hidden" id="item_current_qty" value="0">
                <input type="hidden" id="hide_item_percent_discount" value="0">
                <input type="hidden" id="hide_item_wise_discount" value="0">
                <input type="hidden" id="item_discount" value="0">
                <input type="hidden" id="hide_header_id" value="<?php echo $header_id;?>">
                <!-- units -->
                <input type="hidden" id="item_conversion_rate" value="1">

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="rdb_item_discount" id="rdb_percent_discount" checked>
                            <label class="form-check-label" for="rdb_percent_discount">% Discount</label>
                        </div>
                        <input type="number" step="0.01" id="item_percent_discount" class="form-control">
                        <span class="text-danger" id="item_percent_warning" style="display: none;">Invalid percentage</span>
                    </div>

                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="rdb_item_discount" id="rdb_line_discount">
                            <label class="form-check-label" for="rdb_line_discount">Line Discount</label>
                        </div>
                        <input type="number" step="0.01" id="item_line_discount" class="form-control">
                        <span class="text-danger" id="item_line_warning" style="display: none;">Invalid amount</span>
                    </div>
                </div>
                <h5 id="discounted_price" class="text-light text-center bg-primary rounded-pill p-2 mt-2"></h5>
            </div>
                    
        </div>

        <div class="modal-footer">
            <button type="button" name="btn_edit_item" id="btn_edit_item" class="btn btn-primary">Edit Item</button>
            <!-- <button type="button" class="btn border-warning" data-dismiss="modal">Close</button> -->
        </div>

    </div>
  </div>
</div>
    
    <!-- <script src="../assets/libs/jquery/dist/jquery.min.js"></script> -->
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <!-- <script src="../assets/js/app.min.js"></script> -->
    <script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <!-- <script src="../Assets/js/pos.js"></script> -->
    <script src="../Assets/jquery/invoice.js"></script>
    <script src="../Assets/jquery/payment.js"></script>
    <script src="../Assets/jquery/invoiceChart.js"></script>

</body>
</html>

<?php 
function CounterCheck($user_id, $shop_id)
{
    //get current date time
    date_default_timezone_set("Asia/Colombo");
    $current_date = date("Y-m-d");

    $sql = "SELECT * FROM cashcounter WHERE user_USID=".$user_id." AND shop_SHID=".$shop_id." AND CounterStat = 1 AND CounterDate = '".$current_date."';";

    $dbObj = new DBTransactions();
    $dbData = $dbObj->getData($sql);

    $counter_id = empty($dbData[0]['CCID']) ? 0 : $dbData[0]['CCID'];

    if(!empty($dbData))
    {
        return $counter_id;
    }//has counter
    else
    {
        return $counter_id;
    }//no counter
}//counter check

function createNewSaleHeader($user_id, $shop_id)
{
    $invoiceObj = new Invoice();
    $bill_count = floatval($invoiceObj->getBillCount($shop_id));
    $bill_count += 1;

    $comObj = new Common();
    $bill_no = $comObj->createCount("INV", $bill_count);

    //SHID, BillNo, ItemCount, grossAmount, SellStat, user_id, shop_id, cashcounter_id
    $item_count = 0;
    $gross_amount = 0;
    $sell_stat = 1;

    $sql = "SELECT * FROM cashcounter WHERE user_USID=".$user_id." AND shop_SHID=".$shop_id." AND CounterStat = 1;";

    $dbObj = new DBTransactions();
    $dbData = $dbObj->getData($sql);

    $counter_id = $dbData[0]['CCID'];

    $invoiceObj->setSaleHeader($bill_no, $item_count, $gross_amount, $sell_stat, $user_id, $shop_id, $counter_id);

    //get next sell header id
    $sql_1 = "SELECT MAX(SHID) AS maxSaleHeader FROM sellheader;";
    $sellData = $dbObj->getData($sql_1);
    //next salesheader id
    $next_sale_header_id = floatval($sellData[0]['maxSaleHeader']);

    //get doc count
    $sql_2 = "SELECT COUNT(DNID) AS docCount FROM docno WHERE shop_id = ".$shop_id.";";
    $docData = $dbObj->getData($sql_2);
    $tmp_count = floatval($docData[0]['docCount']) + 1;

    $tmp_bill_no = $comObj->createCount("tmp", $tmp_count);

    $doc_stat = 0;

    $invoiceObj->setDoc($bill_no, $tmp_bill_no, $next_sale_header_id, $doc_stat, $shop_id);

}//create new sales header

function CreateTmpNo($shop_id)
{
    $sql = "SELECT * FROM docno WHERE shop_id = ".$shop_id.";";
    $tmp_bill_no = 0;

    $invObj = new Invoice();
    $dbObj = new DBTransactions();
    $comObj = new Common();
    $docData = $dbObj->getData($sql);

    if(!empty($docData))
    {
        //update doc no
        $doc_id = $docData[0]['DNID'];
        $tmp_no = intval($docData[0]['tmp_no']);
        $org_no = intval($docData[0]['org_no']);

        $new_tmp_no = $tmp_no + 1;

        $invObj->editDoc($new_tmp_no, $org_no, $doc_id);
    }//has shop doc no
    else
    {
        $tmp_no = 1;
        $org_no = 1;
        //create new doc no
        $invObj->setDoc($tmp_no, $org_no, $shop_id);
    }//no shop doc no

    $tmp_bill_no = $comObj->createCount("TMP", $new_tmp_no);

    return $tmp_bill_no;
}//create temp no

?>