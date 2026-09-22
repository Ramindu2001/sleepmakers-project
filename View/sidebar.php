<script>
  $(document).ready(function() {
    $("[href]").each(function() {
      if (this.href == window.location.href) {
        $(this).addClass("active");
        $(this).parent().parent().addClass("in");
      }
    });
    $("#side-closes").click(function() {
      $(".left-sidebar").css("margin-left", "-270px")
    })
  });
</script>
<?php
$shopObj = new Shop();
$user_id = $_SESSION['user_id'];
$userObj = new User();
$user = $userObj->getOneUser($user_id);
$userType = $user[0]['UserType'];
$hasPrescription = $shopObj->hasPrescription($shop_id);
//features
$create = "is_create";
$edit = "is_edit";
$view = "is_view";
$delete = "is_delete";
$verify = "is_verify";
$print = "is_print";
if ($userType != 1) {
  //the role this user holds in the current shop (Model/shop_access_class.php); 0 matches no rights
  $userRole_id = (int) (new ShopAccess())->getShopRoleId($user_id, $shop_id);
  $modules = $userObj->getUserRoleModuleAccess($userRole_id);
  $userModules = [];
  foreach ($modules as $row) {
    $userModules[] = $row["SysModules_SMID"];
  }
}
?>
<aside class="left-sidebar">
  <!-- Sidebar scroll-->
  <div>
    <div class="brand-logo d-flex align-items-center justify-content-between">
      <a href="../Public/home.php" class="text-nowrap logo-img">
        <img src="../Assets/Images/synnex_logo.png" width="180" alt="" />
      </a>
      <button type="button" class="btn-close" id="side-closes" data-bs-dismiss="modal" aria-label="Close"
        style="display:none;"></button>
      <div class="close-btn d-xl-none d-block sidebartoggler cursor-pointer" id="sidebarCollapse">
        <i class="ti ti-x fs-8"></i>
      </div>
    </div>
    <!-- Sidebar navigation-->
    <nav class="sidebar-nav scroll-sidebar" data-simplebar="">
      <ul id="sidebarnav">
        <li class="nav-small-cap">
          <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
          <!-- <span class="hide-menu">Home</span> -->
        </li>
        <li class="sidebar-item">
          <a class="sidebar-link" href="../Public/home.php" aria-expanded="false">
            <span>
              <!-- <i class="ti ti-home"></i> -->
              <img src="../Assets/Images/icons/home.png" alt="" class="menu-icon">
              <!-- <i class="ti ti-layout-dashboard"></i> -->
            </span>
            <span class="hide-menu">Home</span>
          </a>
        </li>
        <li class="nav-small-cap">
          <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
          <!-- <span class="hide-menu">UI COMPONENTS</span> -->
        </li>
        <li class="sidebar-item">
          <a class="sidebar-link" href="../Public/analytics.php" aria-expanded="false">
            <span>
              <!-- <i class="ti ti-home"></i> -->
              <img src="../Assets/Images/icons/analytics.png" alt="" class="menu-icon">
              <!-- <i class="ti ti-layout-dashboard"></i> -->
            </span>
            <span class="hide-menu">Analytics</span>
          </a>
        </li>
        <?php
        if ($shopObj->hasRetailShop($shop_id)) { ?>
          <li class="sidebar-item">
            <a class="sidebar-link" href="../Public/gui-pos.php" aria-expanded="false">
              <span>
                <img src="../Assets/Images/icons/cashier.png" alt="" class="menu-icon">
              </span>
              <span class="hide-menu">POS</span>
            </a>
          </li>
        <?php } else { ?>
          <li class="sidebar-item">
            <a class="sidebar-link" href="../Public/wholesale-invoice.php" aria-expanded="false">
              <span>
                <img src="../Assets/Images/icons/cashier.png" alt="" class="menu-icon">
              </span>
              <span class="hide-menu">POS</span>
            </a>
          </li>
        <?php } ?>
        <li class="nav-small-cap">
          <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
          <!-- <span class="hide-menu">UI COMPONENTS</span> -->
        </li>
        <!----------------------------------- Inventory ----------------------------------->
        <?php
        if ($userType == 1) {
        ?>
          <!------------------------------------ Sales & Orders ------------------------------------>
          <li class="sidebar-item">
            <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
              <span class="d-flex">
                <img src="../Assets/Images/icons/orders.png" alt="" class="menu-icon">
              </span>
              <span class="hide-menu">Sales & Orders</span>
            </a>
            <ul aria-expanded="false" class="collapse first-level">
              <?php if ($shopObj->hasWholesaleShop($shop_id)) { ?>
                <li class="sidebar-item">
                  <a href="../Public/wholesale-invoice.php" class="sidebar-link sidebar-link2">
                    <div class="round-16 d-flex align-items-center justify-content-center">
                      <i class="ti ti-circle"></i>
                    </div>
                    <span class="hide-menu">Create Invoice</span>
                  </a>
                </li>
                <li class="sidebar-item">
                  <a href="../Public/SalesOrderheader.php" class="sidebar-link sidebar-link2">
                    <div class="round-16 d-flex align-items-center justify-content-center">
                      <i class="ti ti-circle"></i>
                    </div>
                    <span class="hide-menu">Create Sales Order</span>
                  </a>
                </li>
              <?php } ?>
              <?php
              $date = date("yyyy-mm-dd");
              $script = "?fdate=$date&tdate=$date";
              ?>
              <li class="sidebar-item">
                <a href="../Public/invoice-list.php<?= $script ?>" class="sidebar-link sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Invoice List</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Public/salesReturn.php" class="sidebar-link sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Sales Return</span>
                </a>
              </li>
            </ul>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
              <span class="d-flex">
                <img src="../Assets/Images/icons/carts.png" alt="" class="menu-icon">
              </span>
              <span class="hide-menu">Store</span>
            </a>
            <ul aria-expanded="false" class="collapse first-level">
              <li class="sidebar-item">
                <a href="../Public/store.php" class="sidebar-link sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Store</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Public/grn-header.php" class="sidebar-link">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">GRN Entry</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Public/adjust-header.php" class="sidebar-link">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Stock Adjust</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Public/transfer-header.php" class="sidebar-link">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Stock Transfer</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Public/price-change.php" class="sidebar-link">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Price Change</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Public/bulk-price-change.php" class="sidebar-link">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Bulk Price Change</span>
                </a>
              </li>
            </ul>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
              <span class="d-flex"><img src="../Assets/Images/icons/accounting.png" alt="" class="menu-icon"></span>
              <span class="hide-menu">Credit & Debit</span>
            </a>
            <ul aria-expanded="false" class="collapse first-level">
              <li class="sidebar-item">
                <a href="../Public/credit-customers.php" class="sidebar-link sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Customer Dues</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Public/customer-cheque-list.php" class="sidebar-link sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Customer Cheques</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Public/credit-supplier.php" class="sidebar-link">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Supplier Dues</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Public/credit-pay.php" class="sidebar-link">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Credit Settlement</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Public/supplier-cheque-list.php" class="sidebar-link">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Supplier Cheques</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Public/supplier_return.php" class="sidebar-link">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Supplier Return</span>
                </a>
              </li>
            </ul>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
              <span class="d-flex"><img src="../Assets/Images/icons/cogwheel.png" alt="" class="menu-icon"></span>
              <span class="hide-menu">Items</span>
            </a>
            <ul aria-expanded="false" class="collapse first-level">
              <li class="sidebar-item">
                <a href="../Public/product.php" class="sidebar-link sidebar-link5">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Products</span>
                </a>
              </li>
              <!-- automatic barcode rules + label print defaults -->
              <li class="sidebar-item">
                <a href="../Public/barcode-settings.php" class="sidebar-link sidebar-link5">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Barcode Settings</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Categories</span>
                </a>
                <ul aria-expanded="false" class="collapse first-level">
                  <li class="sidebar-item">

                    <a href="../Reports/unit-list.php" class="sidebar-link sidebar-link3">
                      <a href="../Public/category.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Main Categories</span>
                      </a>
                  </li>
                  <li class="sidebar-item">
                    <a href="../Public/subcategory.php" class="sidebar-link sidebar-link3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-star"></i>
                      </div>
                      <span class="hide-menu">Sub Categories</span>
                    </a>
                  </li>
                </ul>
              </li>
            </ul>
          </li>
          <!------------------------------------ Accounts ------------------------------------>
          <!----------------------------------------------------------------------------------- Add Expenses lator-->
          <li class="sidebar-item">
            <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
              <span class="d-flex">
                <img src="../Assets/Images/icons/accounting.png" alt="" class="menu-icon">
              </span>
              <span class="hide-menu">Expenses</span>
            </a>
            <ul aria-expanded="false" class="collapse first-level">
              <li class="sidebar-item">
                <a href="../Public/AddExpenseTypes.php" class="sidebar-link sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Expense Types</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Public/AddExpenseCategory.php" class="sidebar-link sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Expense Categories</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Public/AddExpences.php" class="sidebar-link sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Add Expense</span>
                </a>
              </li>
            </ul>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
              <span class="d-flex">
                <img src="../Assets/Images/icons/promotion.png" alt="" class="menu-icon">
              </span>
              <span class="hide-menu">Promotions</span>
            </a>
            <ul aria-expanded="false" class="collapse first-level">
              <li class="sidebar-item">
                <a href="../Public/SendSMSPromotions.php" class="sidebar-link sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Send Promotions</span>
                </a>
              </li>
            </ul>
          </li>
          <!------------------------------------ Reports ------------------------------------>
          <li class="sidebar-item">
            <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
              <span class="d-flex">
                <img src="../Assets/Images/icons/report.png" alt="" class="menu-icon">
              </span>
              <span class="hide-menu">Reports</span>
            </a>
            <ul aria-expanded="false" class="collapse first-level">
              <!--Master Reports-->
              <li class="sidebar-item">
                <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Master</span>
                </a>
                <ul aria-expanded="false" class="collapse first-level">
                  <li class="sidebar-item">
                    <a href="../Reports/master-report.php" class="sidebar-link sidebar-link3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-star"></i>
                      </div>
                      <span class="hide-menu">Master Report</span>
                    </a>
                  </li>
                  <li class="sidebar-item">
                    <a href="../Reports/unit-list.php" class="sidebar-link sidebar-link3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-star"></i>
                      </div>
                      <span class="hide-menu">Unit List</span>
                    </a>
                  </li>
                  <li class="sidebar-item">
                    <a href="../Reports/subcategory-report.php" class="sidebar-link sidebar-link3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-star"></i>
                      </div>
                      <span class="hide-menu">Sub Category List</span>
                    </a>
                  </li>
                  <li class="sidebar-item">
                    <a href="../Reports/category-report.php" class="sidebar-link sidebar-link3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-star"></i>
                      </div>
                      <span class="hide-menu">Category List</span>
                    </a>
                  </li>
                  <li class="sidebar-item">
                    <a href="../Reports/item-report.php" class="sidebar-link sidebar-link3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-star"></i>
                      </div>
                      <span class="hide-menu">Item List </span>
                    </a>
                  <li class="sidebar-item">
                    <a href="../Reports/supplier-list.php" class="sidebar-link sidebar-link3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-star"></i>
                      </div>
                      <span class="hide-menu">Supplier List</span>
                    </a>
                  </li>
              </li>
              <li class="sidebar-item">
                <a href="../Reports/Customer-list.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Customer List</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Reports/salesman-list.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Sales Man List</span>
                </a>
              </li>
            </ul>
          </li>
          <!--Inventory Reports-->
          <li class="sidebar-item">
            <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Store</span>
            </a>
            <ul aria-expanded="false" class="collapse first-level">
              <li class="sidebar-item">
                <a href="../Reports/Inventory_summery_report.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Store Summary</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Reports/inventory_price.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Store Price</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Reports/batchwise.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Batch Wise</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Reports/rpt_inventory.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Store Report</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Reports/rpt_stock_movement.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Stock Movement Report</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Reports/rpt_low_stock.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Low Stock Report</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Reports/rpt_stock_valuation.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Stock Valuation Report</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Reports/rpt_inventory_aging.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Store Aging</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Reports/rpt_stock_adjustment.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Stock Adjustment Report</span>
                </a>
              </li>
            </ul>
          </li>
          <!--Sales Reports-->
          <li class="sidebar-item">
            <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Sales</span>
            </a>
            <ul aria-expanded="false" class="collapse first-level">

              <li class="sidebar-item">
                <a href="../Reports/rpt_invoice_sales.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Invoice Sale</span>
                </a>
              </li>

              <li class="sidebar-item">
                <a href="../Reports/rpt_item_sales.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Item Wise Sales</span>
                </a>
              </li>

              <li class="sidebar-item">
                <a href="../Reports/rpt_category_sales.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Category Wise Sales</span>
                </a>
              </li>

              <li class="sidebar-item">
                <a href="../Reports/rpt_sales_trend.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Sales Trend</span>
                </a>
              </li>

              <li class="sidebar-item">
                <a href="../Reports/profitnloss.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Profit & Loss</span>
                </a>
              </li>

              <li class="sidebar-item">
                <a href="../Reports/rpt_paymethod_sales.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Paymethod Sale</span>
                </a>
              </li>

              <li class="sidebar-item">
                <a href="../Reports/sale_summary_report.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Sale Summary</span>
                </a>
              </li>

              <li class="sidebar-item">
                <a href="../Reports/rpt_customer_summary.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Customer Summary</span>
                </a>
              </li>

              <li class="sidebar-item">
                <a href="../Reports/rpt_customer_detail.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Customer Detail</span>
                </a>
              </li>

              <li class="sidebar-item">
                <a href="../Reports/rpt_salesman_summary.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Salesman Summary</span>
                </a>
              </li>

              <li class="sidebar-item">
                <a href="../Reports/rpt_salesman_detail.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Salesman Detail</span>
                </a>
              </li>

              <li class="sidebar-item">
                <a href="../Reports/sale_z_report.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Sale Z Report</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Reports/rpt_sales_trend.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Monthly Sales Trend</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Reports/rpt_employee_sales.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">User Sale</span>
                </a>
              </li>
            </ul>
          </li>
          <!--Sales Return-->
          <li class="sidebar-item">
            <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Returns</span>
            </a>
            <ul aria-expanded="false" class="collapse first-level">
              <li class="sidebar-item">
                <a href="../Reports/invoice_return_report.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Invoice Return</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Reports/item_return_report.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Item Return</span>
                </a>
              </li>
            </ul>
          </li>
          <!--Customer-->
          <li class="sidebar-item">
            <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Customer</span>
            </a>
            <ul aria-expanded="false" class="collapse first-level">
              <li class="sidebar-item">
                <a href="../Reports/customer-profiles.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Customer Profiles</span>
                </a>
              </li>
              <?php
              if ($hasPrescription == 1) {
              ?>
                <li class="sidebar-item">
                  <a href="../Public/prescription-list.php" class="sidebar-link sidebar-link8">
                    <div class="round-16 d-flex align-items-center justify-content-center">
                      <i class="ti ti-circle"></i>
                    </div>
                    <span class="hide-menu">Prescription List</span>
                  </a>
                </li>
              <?php
              }  ?>
              <li class="sidebar-item">
                <a href="../Reports/customer-credit.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Customer Credit Report</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Reports/customer-sale.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Customer wise sales report</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Reports/due-sale.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Customer due amount report</span>
                </a>
              </li>
            </ul>
          </li>
          <!--Product-->
          <li class="sidebar-item">
            <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Product</span>
            </a>
            <ul aria-expanded="false" class="collapse first-level">
              <li class="sidebar-item">
                <a href="../Reports/top-selling-product.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Top Selling Product</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Reports/product-variations.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Product Variations</span>
                </a>
              </li>
            </ul>
          </li>
          <!--supplier return-->
          <li class="sidebar-item">
            <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Supplier</span>
            </a>
            <ul aria-expanded="false" class="collapse first-level">
              <li class="sidebar-item">
                <a href="../Reports/suppplier-re-report.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Supplier Return</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Reports/supplier-purchase.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Supplier Purchase</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Reports/supplier-payment.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Supplier Payment</span>
                </a>
              </li>
            </ul>
          </li>
          <!--Category Reports-->
          <li class="sidebar-item">
            <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Categories</span>
            </a>
            <ul aria-expanded="false" class="collapse first-level">
              <li class="sidebar-item">
                <a href="../Reports/category-report.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Category selling</span>
                </a>
              </li>
            </ul>
          </li>
          <!-- Transfer Note Reports-->
          <li class="sidebar-item">
            <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Transfer Note</span>
            </a>
            <ul aria-expanded="false" class="collapse first-level">
              <li class="sidebar-item">
                <a href="../Reports/transfer-report.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Transfer Note Report</span>
                </a>
              </li>
            </ul>
          </li>
          <!--Expense Reports-->
          <li class="sidebar-item">
            <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Expense</span>
            </a>
            <ul aria-expanded="false" class="collapse first-level">
              <li class="sidebar-item">
                <a href="../Reports/ExpenseSummaryReport.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Expense Summary</span>
                </a>
              </li>
            </ul>
          </li>
          <li class="sidebar-item">
            <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Cheque Summary</span>
            </a>
            <ul aria-expanded="false" class="collapse first-level">
              <li class="sidebar-item">
                <a href="../Reports/Hand-held-cheque.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Hand Held Cheque</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Reports/customer-realized-cheque.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Customer Realized Cheque</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Reports/supplier-realized-cheque.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Supplier Realized Cheque</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Reports/customer-bounce-cheque.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Customer Bounce Cheque</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Reports/supplier-bounce-cheque.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Supplier Bounce Cheque</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Reports/Transfer-cheque.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Transfer Cheque</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Reports/cheque-notification.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Cheque Notification</span>
                </a>
              </li>
            </ul>
          </li>

          <!--Accounts Reports-->
          <li class="sidebar-item">
            <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Expenses</span>
            </a>
            <ul aria-expanded="false" class="collapse first-level">
              <li class="sidebar-item">
                <a href="../Reports/trialbalance.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Trial Balance</span>
                </a>
              </li>

              <li class="sidebar-item">
                <a href="../Reports/profitnloss.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Profit & Loss</span>
                </a>
              </li>

            </ul>
          </li>
      </ul>
      </li>
      <!----------------------------------- Settings ----------------------------------->
      <li class="sidebar-item">
        <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
          <span class="d-flex">
            <img src="../Assets/Images/icons/cogwheel.png" alt="" class="menu-icon">
          </span>
          <span class="hide-menu">Master Data</span>
        </a>
        <ul aria-expanded="false" class="collapse first-level">



          <li class="sidebar-item">
            <a href="../Public/Supplierlist.php" class="sidebar-link sidebar-link5">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Suppliers</span>
            </a>
          </li>

          <li class="sidebar-item">
            <a href="../Public/Customerlist.php" class="sidebar-link sidebar-link6">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Customer</span>
            </a>
          </li>

          <li class="sidebar-item">
            <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Sales Reps</span>
            </a>
            <ul aria-expanded="false" class="collapse first-level">
              <li class="sidebar-item">
                <a href="../Public/SaleRepGroupList.php" class="sidebar-link sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Sales Groups List</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Public/SalesReplist.php" class="sidebar-link sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Sales Rep List</span>
                </a>
              </li>
            </ul>
          </li>

          <li class="sidebar-item">
            <a href="../Public/Salesmanlist.php" class="sidebar-link sidebar-link8">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Salesman</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link9">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">User</span>
            </a>
            <ul aria-expanded="false" class="collapse first-level">
              <li class="sidebar-item">
                <a href="../Public/users.php" class="sidebar-link sidebar-link9">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Add Users</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Public/user-roles.php" class="sidebar-link sidebar-link9">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">User Role</span>
                </a>
              </li>
            </ul>
          </li>
          <!-- Sections and Racks -->
          <li class="sidebar-item">
            <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Sections & Racks</span>
            </a>
            <ul aria-expanded="false" class="collapse first-level">

              <li class="sidebar-item">
                <a href="../Public/sections.php" class="sidebar-link sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Add Sections</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Public/racks.php" class="sidebar-link sidebar-link3">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-star"></i>
                  </div>
                  <span class="hide-menu">Add Racks</span>
                </a>
              </li>
            </ul>
          </li>
          <!-- units -->
          <li class="sidebar-item">
            <a href="../Public/units.php" class="sidebar-link sidebar-link2">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Units</span>
            </a>
          </li>
        </ul>
      </li>
      <!---------------------------------- Administor ---------------------------------->
      <li class="sidebar-item">
        <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
          <span class="d-flex">
            <img src="../Assets/Images/icons/controller.png" alt="" class="menu-icon">
          </span>
          <span class="hide-menu">Control</span>
        </a>
        <ul aria-expanded="false" class="collapse first-level">
          <li class="sidebar-item">
            <a href="../Public/company.php" class="sidebar-link sidebar-link2">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Company</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a href="../Public/Shoplist.php" class="sidebar-link sidebar-link2">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Shops</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a href="../Public/SystemModulesList.php" class="sidebar-link sidebar-link2">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">System Modules</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a href="../Public/SystemFeaturesList.php" class="sidebar-link sidebar-link2">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">System Features</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a href="../Public/ShopFeaturesList.php" class="sidebar-link sidebar-link2">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Shop Features</span>
            </a>
          </li>
          <!-- assign users to the shops -->
          <li class="sidebar-item">
            <a href="../Public/AssignUsersToShops.php" class="sidebar-link sidebar-link2">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Assign Users</span>
            </a>
          </li>
          <!-- assign users to the shops -->
          <li class="sidebar-item">
            <a href="../Public/AssignPaymentMethods.php" class="sidebar-link sidebar-link2">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Assign Payment Methods</span>
            </a>
          </li>
          <!-- sale settings -->
          <li class="sidebar-item">
            <a href="../Public/SaleSettings.php" class="sidebar-link sidebar-link2">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Sale Settings</span>
            </a>
          </li>
          <!-- bulk upload -->
          <li class="sidebar-item">
            <a href="../Public/upload_stock.php" class="sidebar-link sidebar-link2">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Upload Product</span>
            </a>
          </li>
          <!-- bulk upload inventory -->
          <li class="sidebar-item">
            <a href="../Public/upload_inventory.php" class="sidebar-link sidebar-link2">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Upload Items</span>
            </a>
          </li>
          <!-- label print -->
          <li class="sidebar-item">
            <a href="../Public/label.php" class="sidebar-link sidebar-link2">
              <div class="round-16 d-flex align-items-center justify-content-center">
                <i class="ti ti-circle"></i>
              </div>
              <span class="hide-menu">Label Print</span>
            </a>
          </li>
        </ul>
      </li>
      <?php
        } else {
          if (in_array("2", $userModules)) {
      ?>
        <!------------------------------------ Orders ------------------------------------>
        <li class="sidebar-item">
          <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
            <span class="d-flex">
              <img src="../Assets/Images/icons/orders.png" alt="" class="menu-icon">
            </span>
            <span class="hide-menu">Sales & Orders</span>
          </a>
          <ul aria-expanded="false" class="collapse first-level">
            <?php
            $feature_id = 7;
            $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
              if ($shopObj->hasRetailShop($shop_id)) {
                if ($shopObj->hasRetailShop($shop_id)) {
            ?>
                  <li class="sidebar-item">
                    <a href="../Public/gui-pos.php" class="sidebar-link sidebar-link2">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-circle"></i>
                      </div>
                      <span class="hide-menu">POS Sale</span>
                    </a>
                  </li>

            <?php
                }
              } //show Retail shop
            } //feature
            ?>

            <?php
            $feature_id = 8;
            $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
              if ($shopObj->hasWholesaleShop($shop_id)) {
            ?>
                <li class="sidebar-item">
                  <a href="../Public/wholesale-invoice.php" class="sidebar-link sidebar-link2">
                    <div class="round-16 d-flex align-items-center justify-content-center">
                      <i class="ti ti-circle"></i>
                    </div>
                    <span class="hide-menu">Create Invoice</span>
                  </a>
                </li>
            <?php
              } //has retail
            } //feature
            ?>

            <?php //Sales Order
            $feature_id = 74;
            $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
              if ($shopObj->hasWholesaleShop($shop_id)) {
            ?>
                <li class="sidebar-item">
                  <a href="../Public/SalesOrderheader.php" class="sidebar-link sidebar-link2">
                    <div class="round-16 d-flex align-items-center justify-content-center">
                      <i class="ti ti-circle"></i>
                    </div>
                    <span class="hide-menu">Create Sales Order</span>
                  </a>
                </li>
            <?php
              } //has retail
            } //feature
            ?>

            <?php
            $feature_id = 56; //invoice list
            $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
              $date = date("yyyy-mm-dd");
              $script = "?fdate=$date&tdate=$date";
            ?>
              <li class="sidebar-item">
                <a href="../Public/invoice-list.php<?= $script ?>" class="sidebar-link sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Invoice List</span>
                </a>
              </li>
            <?php
            }
            ?>

            <?php
            $feature_id = 10;
            $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
            ?>
              <li class="sidebar-item">
                <a href="../Public/salesReturn.php" class="sidebar-link sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Returns</span>
                </a>
              </li>
            <?php
            }
            ?>
          </ul>
        </li>
      <?php
          }
          if (in_array("1", $userModules)) {
      ?>
        <li class="sidebar-item">
          <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
            <span class="d-flex">
              <img src="../Assets/Images/icons/carts.png" alt="" class="menu-icon">
            </span>
            <span class="hide-menu">Store</span>
          </a>
          <ul aria-expanded="false" class="collapse first-level">
            <?php
            $feature_id = 1;
            $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
            ?>
              <li class="sidebar-item">
                <a href="../Public/store.php" class="sidebar-link sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Store</span>
                </a>
              </li>
            <?php
            }
            ?>
            <?php
            $feature_id = 2;
            $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
            ?>
              <li class="sidebar-item">
                <a href="../Public/grn-header.php" class="sidebar-link">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">GRN Entry</span>
                </a>
              </li>
            <?php
            }
            ?>
            <?php
            $feature_id = 3;
            $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
            ?>
              <li class="sidebar-item">
                <a href="../Public/adjust-header.php" class="sidebar-link">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Stock Adjust</span>
                </a>
              </li>
            <?php
            }
            ?>
            <?php
            $feature_id = 4;
            $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
            ?>
              <li class="sidebar-item">
                <a href="../Public/transfer-header.php" class="sidebar-link">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Stock Transfer</span>
                </a>
              </li>
            <?php
            }
            ?>
            <?php
            $feature_id = 6;
            $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
            ?>
              <li class="sidebar-item">
                <a href="../Public/price-change.php" class="sidebar-link">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Price Change</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Public/bulk-price-change.php" class="sidebar-link">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Bulk Price Change</span>
                </a>
              </li>
            <?php
            }
            ?>
          </ul>
        </li>
      <?php
          }
          if (in_array("8", $userModules)) {
      ?>
        <li class="sidebar-item">
          <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
            <span class="d-flex"><img src="../Assets/Images/icons/accounting.png" alt="" class="menu-icon"></span>
            <span class="hide-menu">Credit & Debit</span>
          </a>
          <ul aria-expanded="false" class="collapse first-level">
            <?php
            $feature_id = 66;
            $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
            ?>
              <li class="sidebar-item">
                <a href="../Public/credit-supplier.php" class="sidebar-link">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Supplier Dues</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a href="../Public/credit-pay.php" class="sidebar-link">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Credit Settlement</span>
                </a>
              </li>

              <li class="sidebar-item">
                <a href="../Public/supplier-cheque-list.php" class="sidebar-link">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Supplier Cheques</span>
                </a>
              </li>
            <?php
            }
            ?>
            <?php
            $feature_id = 5;
            $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
            ?>
              <li class="sidebar-item">
                <a href="../Public/supplier_return.php" class="sidebar-link">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Supplier Return</span>
                </a>
              </li>
            <?php
            }
            ?>
          </ul>
        </li>
      <?php
          }
          if (in_array("7", $userModules)) {
      ?>
        <li class="sidebar-item">
          <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
            <span class="d-flex"><img src="../Assets/Images/icons/cogwheel.png" alt="" class="menu-icon"></span>
            <span class="hide-menu">Items</span>
          </a>
          <ul aria-expanded="false" class="collapse first-level">
            <?php
            $feature_id = 16;
            $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
            ?>
              <li class="sidebar-item">
                <a href="../Public/product.php" class="sidebar-link sidebar-link4">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Products</span>
                </a>
              </li>
              <!-- automatic barcode rules + label print defaults.
                   Same feature (16) as Products, so no new sysfeatures row and
                   no backfill of userroleaccess is needed on a live system. -->
              <li class="sidebar-item">
                <a href="../Public/barcode-settings.php" class="sidebar-link sidebar-link4">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Barcode Settings</span>
                </a>
              </li>
            <?php
            }
            ?>
            <?php
            $feature_id = 14;
            $feature2 = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            $feature_id = 15;
            $feature3 = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            if ($feature2[0]["is_view"] == 1 || $feature3[0]["is_view"] == 1) {
            ?>
              <li class="sidebar-item">
                <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Categories</span>
                </a>
                <ul aria-expanded="false" class="collapse first-level">
                  <?php
                  $feature_id = 14;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Public/category.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Main Categories</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                  <?php
                  $feature_id = 15;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Public/subcategory.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Sub Categories</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                </ul>
              </li>
            <?php
            }
            ?>
          </ul>
        </li>
      <?php
          }
          if (in_array("3", $userModules)) {
      ?>
        <!------------------------------------ Expenses ------------------------------------>
        <li class="sidebar-item">
          <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
            <span class="d-flex">
              <img src="../Assets/Images/icons/accounting.png" alt="" class="menu-icon">
            </span>
            <span class="hide-menu">Expenses</span>
          </a>
          <ul aria-expanded="false" class="collapse first-level">
            <?php
            $feature_id = 12;
            $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
            ?>
              <li class="sidebar-item">
                <a href="../Public/AddExpenseTypes.php" class="sidebar-link sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Expense Types</span>
                </a>
              </li>
            <?php
            }
            ?>
            <?php
            $feature_id = 11;
            $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
            ?>
              <li class="sidebar-item">
                <a href="../Public/AddExpenseCategory.php" class="sidebar-link sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Expense Categories</span>
                </a>
              </li>
            <?php
            }
            ?>
            <?php
            $feature_id = 13;
            $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
            ?>
              <li class="sidebar-item">
                <a href="../Public/AddExpences.php" class="sidebar-link sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Add Expense</span>
                </a>
              </li>
            <?php
            }
            ?>
          </ul>
        </li>

        <!----------------------------------Promotions-------------------------------------->

        <?php
          }
          if (in_array("6", $userModules)) {
            $feature_id = 71;
            $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
        ?>
          <li class="sidebar-item">
            <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
              <span class="d-flex">
                <img src="../Assets/Images/icons/promotion.png" alt="" class="menu-icon">
              </span>
              <span class="hide-menu">Promotions</span>
            </a>
            <ul aria-expanded="false" class="collapse first-level">
              <li class="sidebar-item">
                <a href="../Public/SendSMSPromotions.php" class="sidebar-link sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Send Promotions</span>
                </a>
              </li>
            </ul>
          </li>
        <?php
            }
        ?>
        <!-------------------------------------End Promotions------------------------------->

      <?php
          }
          if (in_array("4", $userModules)) {
      ?>
        <!------------------------------------ Reports ------------------------------------>
        <li class="sidebar-item">
          <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
            <span class="d-flex">
              <img src="../Assets/Images/icons/report.png" alt="" class="menu-icon">
            </span>
            <span class="hide-menu">Reports</span>
          </a>
          <ul aria-expanded="false" class="collapse first-level">
            <!--Master Reports-->
            <?php
            $feature_id = 31;
            $feature1 = $userObj->getRoleViewAccess($userRole_id, $feature_id);
            $feature_id = 32;
            $feature2 = $userObj->getRoleViewAccess($userRole_id, $feature_id);
            $feature_id = 33;
            $feature3 = $userObj->getRoleViewAccess($userRole_id, $feature_id);
            $feature_id = 34;
            $feature4 = $userObj->getRoleViewAccess($userRole_id, $feature_id);
            $feature_id = 35;
            $feature5 = $userObj->getRoleViewAccess($userRole_id, $feature_id);
            $feature_id = 36;
            $feature6 = $userObj->getRoleViewAccess($userRole_id, $feature_id);
            $feature_id = 37;
            $feature7 = $userObj->getRoleViewAccess($userRole_id, $feature_id);
            if ($feature1 == 1 || $feature2 == 1 || $feature3 == 1 || $feature4 == 1 || $feature5 == 1 || $feature6 == 1 || $feature7 == 1) {
            ?>
              <li class="sidebar-item">
                <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Master</span>
                </a>
                <ul aria-expanded="false" class="collapse first-level">
                  <?php
                  $feature_id = 31;
                  $feature = $userObj->getRoleViewAccess($userRole_id, $feature_id);
                  if ($feature == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/unit-list.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Unit List</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                  <?php
                  $feature_id = 32;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/subcategory-report.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Sub Category List</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                  <?php
                  $feature_id = 33;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/category-report.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Category List</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                  <?php
                  $feature_id = 34;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/item-report.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Item List </span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                  <?php
                  $feature_id = 35;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/supplier-list.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Supplier List</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                  <?php
                  $feature_id = 36;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/Customer-list.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Customer List</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                  <?php
                  $feature_id = 37;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/salesman-list.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Sales Man List</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                </ul>
              </li>
            <?php
            }
            ?>
            <!--Inventory Reports-->
            <li class="sidebar-item">
              <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
                <div class="round-16 d-flex align-items-center justify-content-center">
                  <i class="ti ti-circle"></i>
                </div>
                <span class="hide-menu">Store</span>
              </a>
              <ul aria-expanded="false" class="collapse first-level">
                <?php
                $feature_id = 38;
                $feature = $userObj->getRoleViewAccess($userRole_id, $feature_id);
                if ($feature == 1) {
                ?>
                  <li class="sidebar-item">
                    <a href="../Reports/Inventory_summery_report.php" class="sidebar-link sidebar-link3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-star"></i>
                      </div>
                      <span class="hide-menu">Store Summary</span>
                    </a>
                  </li>
                <?php
                } //inventory summary
                ?>
                <?php
                $feature_id = 65;
                $feature = $userObj->getRoleViewAccess($userRole_id, $feature_id);
                if ($feature == 1) {
                ?>
                  <li class="sidebar-item">
                    <a href="../Reports/inventory_price.php" class="sidebar-link sidebar-link3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-star"></i>
                      </div>
                      <span class="hide-menu">Store Price</span>
                    </a>
                  </li>
                <?php
                } //inventory summary
                ?>

                <?php
                $feature_id = 72;
                $feature = $userObj->getRoleViewAccess($userRole_id, $feature_id);
                if ($feature == 1) {
                ?>
                  <li class="sidebar-item">
                    <a href="../Reports/batchwise.php" class="sidebar-link sidebar-link3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-star"></i>
                      </div>
                      <span class="hide-menu">batch wise</span>
                    </a>
                  </li>
                <?php
                }
                ?>

                <?php
                $shop_id = $_SESSION['shop_id'];

                if ($shopObj->hasExpiry($shop_id)) {
                  $feature_id = 59;
                  $feature1 = $userObj->getRoleViewAccess($userRole_id, $feature_id);
                  if ($feature1 == 1) {
                ?>
                    <li class="sidebar-item">
                      <a href="../Reports/expired-items.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Expired Items</span>
                      </a>
                    </li>
                <?php
                  } //has expire report view 
                } //has expiry
                ?>
              </ul>
            </li>
            <?php
            $feature_id = 39;
            $feature1 = $userObj->getRoleViewAccess($userRole_id, $feature_id);
            $feature_id = 40;
            $feature2 = $userObj->getRoleViewAccess($userRole_id, $feature_id);
            $feature_id = 41;
            $feature3 = $userObj->getRoleViewAccess($userRole_id, $feature_id);
            $feature_id = 62;
            $feature4 = $userObj->getRoleViewAccess($userRole_id, $feature_id);
            if ($feature1 == 1 || $feature2 == 1 || $feature3 == 1 || $feature4 == 1) {
            ?>
              <!--Sales Reports-->
              <li class="sidebar-item">
                <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Sales</span>
                </a>
                <ul aria-expanded="false" class="collapse first-level">
                  <?php
                  $feature_id = 57;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/rpt_invoice_sales.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Invoice Sale</span>
                      </a>
                    </li>
                    <li class="sidebar-item">
                      <a href="../Reports/rpt_item_sales.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Item Wise Sales</span>
                      </a>
                    </li>
                    <li class="sidebar-item">
                      <a href="../Reports/profitnloss.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Profit & Loss</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                  <?php
                  $feature_id = 58;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/rpt_paymethod_sales.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Paymethod Sale</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                  <?php
                  $feature_id = 39;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/sale_summary_report.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Sale Summary</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                  <?php
                  $feature_id = 39;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/rpt_salesman_summary.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Salesman Summary</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                  <?php
                  $feature_id = 69;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/rpt_salesman_detail.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Salesman Details</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                  <!-- sale z report -->
                  <?php
                  $feature_id = 62;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/sale_z_report.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Sales Z Report</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                  <!-- monthly sale -->
                  <?php
                  $feature_id = 64;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/rpt_sales_trend.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Monthly Sales Trend</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                  <?php
                  $feature_id = 41;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/rpt_employee_sales.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">User Wise Sale</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                </ul>
              </li>
            <?php
            }
            ?>
            <?php
            $feature_id = 55;
            $feature1 = $userObj->getRoleViewAccess($userRole_id, $feature_id);
            $feature_id = 42;
            $feature2 = $userObj->getRoleViewAccess($userRole_id, $feature_id);
            if ($feature1 == 1 || $feature2 == 1) {
            ?>
              <!--Sales Return-->
              <li class="sidebar-item">
                <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Returns</span>
                </a>
                <ul aria-expanded="false" class="collapse first-level">
                  <?php
                  $feature_id = 55;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/invoice_return_report.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Invoice Return</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                  <?php
                  $feature_id = 42;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/item_return_report.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Item Return</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                </ul>
              </li>
            <?php
            }
            ?>
            <?php
            $feature_id = 43;
            $feature1 = $userObj->getRoleViewAccess($userRole_id, $feature_id);
            $feature_id = 44;
            $feature2 = $userObj->getRoleViewAccess($userRole_id, $feature_id);
            $feature_id = 45;
            $feature3 = $userObj->getRoleViewAccess($userRole_id, $feature_id);
            $feature_id = 46;
            $feature4 = $userObj->getRoleViewAccess($userRole_id, $feature_id);
            if ($feature1 == 1 || $feature2 == 1 || $feature3 == 1 || $feature4 == 1) {
            ?>
              <!--Customer-->
              <li class="sidebar-item">
                <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Customer</span>
                </a>
                <ul aria-expanded="false" class="collapse first-level">
                  <?php
                  $feature_id = 43;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/customer-profiles.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Customer Profiles</span>
                      </a>
                    </li>
                  <?php
                  }

                  if ($hasPrescription == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Public/prescription-list.php" class="sidebar-link sidebar-link8">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-circle"></i>
                        </div>
                        <span class="hide-menu">Prescription List</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>


                  <?php
                  $feature_id = 44;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/customer-credit.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Credit Customer</span>
                      </a>
                    </li>
                  <?php
                  }
                  $feature_id = 45;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/customer-sale.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Customer Sales</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                  <?php
                  $feature_id = 46;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/due-sale.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Due Sale</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                </ul>
              </li>
            <?php
            }
            ?>
            <?php

            $feature_id = 47;
            $feature1 = $userObj->getRoleViewAccess($userRole_id, $feature_id);
            $feature_id = 48;
            $feature2 = $userObj->getRoleViewAccess($userRole_id, $feature_id);
            if ($feature1 == 1 || $feature2 == 1) {
            ?>
              <!--Product-->
              <li class="sidebar-item">
                <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Product</span>
                </a>
                <ul aria-expanded="false" class="collapse first-level">
                  <?php
                  $feature_id = 47;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/top-selling-product.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Top Selling Product</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                  <?php
                  $feature_id = 48;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/product-variations.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Product Variations</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                </ul>
              </li>
            <?php
            }
            ?>
            <?php
            $feature_id = 49;
            $feature1 = $userObj->getRoleViewAccess($userRole_id, $feature_id);
            $feature_id = 50;
            $feature2 = $userObj->getRoleViewAccess($userRole_id, $feature_id);
            if ($feature1 == 1 || $feature2 == 1) {
            ?>
              <!--supplier return-->
              <li class="sidebar-item">
                <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Supplier</span>
                </a>
                <ul aria-expanded="false" class="collapse first-level">
                  <?php
                  $feature_id = 49;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/suppplier-re-report.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Supplier Return </span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                  <!--Supplier purchase  -->
                  <?php
                  $feature_id = 63;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/supplier-purchase.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Supplier Purchase</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                  <?php
                  $feature_id = 50;
                  $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
                  if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Reports/supplier-payment.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Supplier Payment</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                </ul>
              </li>
            <?php
            }
            ?>

            <!--Category Reports-->
            <?php
            $feature_id = 51;
            $feature = $userObj->getRoleViewAccess($userRole_id, $feature_id);
            if ($feature == 1) {
            ?>
              <li class="sidebar-item">
                <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Categories</span>
                </a>
                <ul aria-expanded="false" class="collapse first-level">
                  <li class="sidebar-item">
                    <a href="../Reports/category-sale.php" class="sidebar-link sidebar-link3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-star"></i>
                      </div>
                      <span class="hide-menu">Category selling</span>
                    </a>
                  </li>
                </ul>
              </li>
            <?php
            }
            ?>
            <!-- Transfer Note Reports-->
            <?php
            $feature_id = 52;
            $feature = $userObj->getRoleViewAccess($userRole_id, $feature_id);
            if ($feature == 1) {
            ?>
              <li class="sidebar-item">
                <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Transfer Note</span>
                </a>
                <ul aria-expanded="false" class="collapse first-level">
                  <li class="sidebar-item">
                    <a href="../Reports/transfer-report.php" class="sidebar-link sidebar-link3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-star"></i>
                      </div>
                      <span class="hide-menu">Transfer Note Report</span>
                    </a>
                  </li>
                </ul>
              </li>
            <?php
            }
            ?>
            <!--Expense Reports-->
            <?php
            $feature_id = 53;
            $feature = $userObj->getRoleViewAccess($userRole_id, $feature_id);
            if ($feature == 1) {
            ?>
              <li class="sidebar-item">
                <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Expense</span>
                </a>
                <ul aria-expanded="false" class="collapse first-level">
                  <li class="sidebar-item">
                    <a href="../Reports/ExpenseSummaryReport.php" class="sidebar-link sidebar-link3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-star"></i>
                      </div>
                      <span class="hide-menu">Expense Summary</span>
                    </a>
                  </li>
                </ul>
              </li>
              <li class="sidebar-item">
                <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Cheque Summary</span>
                </a>
                <ul aria-expanded="false" class="collapse first-level">
                  <li class="sidebar-item">
                    <a href="../Reports/Hand-held-cheque.php" class="sidebar-link sidebar-link3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-star"></i>
                      </div>
                      <span class="hide-menu">Hand Held Cheque</span>
                    </a>
                  </li>
                  <li class="sidebar-item">
                    <a href="../Reports/customer-realized-cheque.php" class="sidebar-link sidebar-link3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-star"></i>
                      </div>
                      <span class="hide-menu">Customer Realized Cheque</span>
                    </a>
                  </li>
                  <li class="sidebar-item">
                    <a href="../Reports/supplier-realized-cheque.php" class="sidebar-link sidebar-link3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-star"></i>
                      </div>
                      <span class="hide-menu">Supplier Realized Cheque</span>
                    </a>
                  </li>
                  <li class="sidebar-item">
                    <a href="../Reports/customer-bounce-cheque.php" class="sidebar-link sidebar-link3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-star"></i>
                      </div>
                      <span class="hide-menu">Customer Bounce Cheque</span>
                    </a>
                  </li>
                  <li class="sidebar-item">
                    <a href="../Reports/supplier-bounce-cheque.php" class="sidebar-link sidebar-link3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-star"></i>
                      </div>
                      <span class="hide-menu">Supplier Bounce Cheque</span>
                    </a>
                  </li>
                  <li class="sidebar-item">
                    <a href="../Reports/Transfer-cheque.php" class="sidebar-link sidebar-link3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-star"></i>
                      </div>
                      <span class="hide-menu">Transfer Cheque</span>
                    </a>
                  </li>
                </ul>
                <ul aria-expanded="false" class="collapse first-level">
                  <li class="sidebar-item">
                    <a href="../Reports/cheque-notification.php" class="sidebar-link sidebar-link3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-star"></i>
                      </div>
                      <span class="hide-menu">Cheque Notification</span>
                    </a>
                  </li>
                </ul>
              </li>


              <!--Accounts Reports-->

              <li class="sidebar-item">
                <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Expenses</span>
                </a>
                <ul aria-expanded="false" class="collapse first-level">
                  <li class="sidebar-item">
                    <a href="../Reports/trialbalance.php" class="sidebar-link sidebar-link3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-star"></i>
                      </div>
                      <span class="hide-menu">Trial Balance</span>
                    </a>
                  </li>

                  <li class="sidebar-item">
                    <a href="../Reports/profitnloss.php" class="sidebar-link sidebar-link3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-star"></i>
                      </div>
                      <span class="hide-menu">Profit & Loss</span>
                    </a>
                  </li>

                </ul>
              </li>

            <?php
            }
            ?>
          </ul>
        </li>
      <?php
          }

          if (in_array("5", $userModules)) {
      ?>
        <!----------------------------------- Settings ----------------------------------->
        <li class="sidebar-item">
          <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
            <span class="d-flex">
              <img src="../Assets/Images/icons/cogwheel.png" alt="" class="menu-icon">
            </span>
            <span class="hide-menu">Master Data</span>
          </a>
          <ul aria-expanded="false" class="collapse first-level">
            <?php
            $feature_id = 17;
            $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
            ?>
              <li class="sidebar-item">
                <a href="../Public/Supplierlist.php" class="sidebar-link sidebar-link4">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Suppliers</span>
                </a>
              </li>
            <?php
            }
            ?>
            <?php
            $feature_id = 18;
            $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
            ?>
              <li class="sidebar-item">
                <a href="../Public/Customerlist.php" class="sidebar-link sidebar-link4">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Customers</span>
                </a>
              </li>
            <?php
            }
            ?>
            <?php
            $feature_id = 19;
            $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
            ?>
              <li class="sidebar-item">
                <a href="../Public/Salesmanlist.php" class="sidebar-link sidebar-link4">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Salesman</span>
                </a>
              </li>
            <?php
            }
            ?>
            <?php
            $feature_id = 20;
            $feature2 = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            $feature_id = 54;
            $feature3 = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            if ($feature3[0]["is_view"] == 1 || $feature2[0]["is_view"] == 1) {
            ?>
              <li class="sidebar-item">
                <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link4">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">User</span>
                </a>
                <ul aria-expanded="false" class="collapse first-level">
                  <?php
                  if ($feature2[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Public/users.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">Add Users</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                  <?php
                  if ($feature3[0]["is_view"] == 1) {
                  ?>
                    <li class="sidebar-item">
                      <a href="../Public/user-roles.php" class="sidebar-link sidebar-link3">
                        <div class="round-16 d-flex align-items-center justify-content-center">
                          <i class="ti ti-star"></i>
                        </div>
                        <span class="hide-menu">User Role</span>
                      </a>
                    </li>
                  <?php
                  }
                  ?>
                </ul>
              </li>
            <?php
            }
            ?>
            <?php
            $feature_id = 21;
            $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
            ?>
              <!-- Sections and Racks -->
              <li class="sidebar-item">
                <a href="javascript:void(0)" class="sidebar-link has-arrow sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Sections & Racks</span>
                </a>
                <ul aria-expanded="false" class="collapse first-level">

                  <li class="sidebar-item">
                    <a href="../Public/sections.php" class="sidebar-link sidebar-link2">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-star"></i>
                      </div>
                      <span class="hide-menu">Add Sections</span>
                    </a>
                  </li>
                  <li class="sidebar-item">
                    <a href="../Public/racks.php" class="sidebar-link sidebar-link3">
                      <div class="round-16 d-flex align-items-center justify-content-center">
                        <i class="ti ti-star"></i>
                      </div>
                      <span class="hide-menu">Add Racks</span>
                    </a>
                  </li>
                </ul>
              </li>
            <?php
            }
            ?>
            <?php
            $feature_id = 22;
            $feature = $userObj->getUserRoleFeatureAccess($userRole_id, $feature_id);
            if (isset($feature[0]["is_view"]) && $feature[0]["is_view"] == 1) {
            ?>
              <!-- units -->
              <li class="sidebar-item">
                <a href="../Public/units.php" class="sidebar-link sidebar-link2">
                  <div class="round-16 d-flex align-items-center justify-content-center">
                    <i class="ti ti-circle"></i>
                  </div>
                  <span class="hide-menu">Units</span>
                </a>
              </li>
            <?php
            }
            ?>
          </ul>
        </li>
    <?php
          }
        }
    ?>
    </ul>
    </nav>
    <!-- End Sidebar navigation -->
  </div>
  <!-- End Sidebar scroll-->
</aside>