<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../Includes/includes.php';
include '../Includes/authcheck.php';

$return_header_id = isset($_POST['return_header_id']) ? $_POST['return_header_id'] : 0;
$return_header_stat = isset($_POST['return_header_stat']) ? $_POST['return_header_stat'] : null;

if (!$return_header_id) {
    $_SESSION['return_update'] = 3;
    header("Location: supplier_return.php");
    exit("Error: no return header id.");
}
?>

<!doctype html>
<html lang="en">

<head>
    <?php
    include '../View/head.php';
    // include '../View/loader.php';
    ?>
</head>

<body>
    <div class="h-100vh">
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
            data-sidebar-position="fixed" data-header-position="fixed">
            
            <?php include '../View/sidebar.php'; 
            $feature_id=5;
            include '../Includes/editPermission.php';
            
            ?>

            <div class="body-wrapper">
                <?php
                include '../View/header.php';
                include "../View/modals/Submit-supplierreturn.php";
                ?>

                <div class="container-fluid">
                    <!-- messages -->
                    <div class="container">
                        <?php
                        if (isset($_SESSION['return_detail_update'])) {
                            $alertClass = $_SESSION['return_detail_update'] == 0 ? 'danger' : 'warning';
                            $alertMessage = $_SESSION['return_detail_update'] == 0 ? 'Please check <strong>Return Qty</strong> for each item.' : 'Please <strong>Add</strong> items to return.';
                            echo "<div class=\"alert alert-$alertClass alert-dismissible bg-$alertClass text-white border-0 fade show\" role=\"alert\">
                                    <button type=\"button\" class=\"btn-close btn-close-white\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>
                                    $alertMessage
                                  </div>";
                            unset($_SESSION['return_detail_update']);
                        }
                        ?>
                    </div>

                    <h5 class="card-title fw-semibold mb-2">Supplier Return Details</h5>

                    <div class="card mt-2 mb-2">
                        <div class="card-body">
                            <div>                               
                            <?php 
                                if($return_header_stat == '0')
                                {
                                    ?>
                                    <span class="badge bg-primary mt-2 mb-2">Supplier Return is <b>on Hold</b></span>
                                    <?php 
                                } //hold
                                else if($return_header_stat == '1')
                                {
                                    ?>
                                    <span class="badge bg-warning mt-2 mb-2">Supplier Return is <b>Pending</b></span>
                                    <?php 
                                } //pending
                                else if($return_header_stat == '2')
                                {
                                    ?>
                                    <span class="badge bg-success mt-2 mb-2">Supplier Return is <b>Verified</b></span>
                                    <?php 
                                } //verified
                                else if($return_header_stat == '3')
                                {
                                    ?>
                                    <span class="badge bg-danger mt-2 mb-2">Supplier Return was <b>Canceled</b></span>
                                    <?php 
                                } //cancled
                                else
                                {
                                    ?>
                                    <span class="badge bg-danger mt-2 mb-2">GRN is <b>Undefined</b></span>
                                    <?php 
                                } //undefined
                             ?>
                            </div>

                            <?php
                            $returnObj = new SupplierReturn();
                            $returnData = $returnObj->getOnesupplierreturn($return_header_id);
                            ?>
                            <div class="container-fluid">
                                <div class="table-responsive">

                                    <!-- hidden input -->
                                    <input type="hidden" name="hide_header_id" id="hide_header_id" value="<?php echo htmlspecialchars($return_header_id); ?>">
                                    <input type="hidden" name="hide_detail_id" id="hide_detail_id" value="0">

                                    <?php
                                    $shopObj = new Shop();

                                    if ($return_header_stat < 2) {
                                    ?>

                                        <table class="table table-hover" id="tbl_add_details">
                                            <tr>
                                                <th>Product</th>
                                                <?php if ($shopObj->hasVariation($shop_id)) { ?>
                                                    <th>Variations</th>
                                                <?php } ?>
                                                <th>Batch</th>
                                                <th>Ava.Qty</th>
                                                <th>Qty</th>
                                                <th>Purchase Price</th>
                                                <th></th>
                                                <th>Action</th>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <select name="cmb_product" id="cmb_product" class="form-select required">
                                                        <option value="">=== Select an Item ===</option>
                                                    </select>
                                                    <span class="text-danger" style="display: none;" id="product_warning">Please select product</span>
                                                    <input type="hidden" name="ids" id="ids" value="0">
                                                    <br>
                                                    <p id="product_detail"></p>
                                                </td>

                                                <?php if ($shopObj->hasVariation($shop_id)) { ?>
                                                    <td>
                                                        <select name="cmb_variation" id="cmb_variation" class="form-select">
                                                            <option value="0">No Variation</option>
                                                        </select>
                                                    </td>
                                                <?php } ?>

                                                <td>
                                                    <select name="cmb_Batch" id="cmb_Batch" class="form-select">
                                                        <option value="0">No Batch</option>
                                                    </select>
                                                    <input type="hidden" name="Batchid" id="Batchid" value="0">
                                                    <input type="hidden" name="inventory_id" id="inventory_id" value="0">
                                                    <br>
                                                    <p id="Batch_detail"></p>
                                                </td>
                                                
                                                <td>
                                                    <input type="number" step="0.001" name="Ava_qty" id="Ava_qty" class="form-control required" placeholder="AvaQty" readonly>
                                                    <span class="text-danger" id="qty_warning" style="display: none;">Not Valid Value</span>
                                                </td>

                                                <td>
                                                    <input type="number" step="0.001" name="prod_qty" id="prod_qty" class="form-control required" placeholder="Qty">
                                                    <span class="text-danger" id="qty_warning" style="display: none;">Not Valid Value</span>
                                                </td>

                                                <td>
                                                    <input type="number" step="0.01" name="purchase_price" id="purchase_price" class="form-control required" placeholder="Purchase Price">
                                                    <span class="text-danger" id="pur_warning" style="display: none;">Not Valid Value</span>
                                                </td>
                                                
                                                <td>
                                                    <input type="hidden" id="total_amount" name="total_amount" class="form-control" placeholder="Total Amount" readonly>
                                                </td>

                                                <td>
                                                    <button type="button" name="btn_add_srn_detail" id="btn_add_srn_detail" class="btn border border-success bg-success">
                                                        <i class="ti ti-plus"></i>
                                                    </button>

                                                    <button type="button" name="btn_edit_srn_detail" id="btn_edit_srn_detail" class="btn border border-warning bg-warning">
                                                        <i class="ti ti-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </table>
                                    <?php } ?>
                                </div>

                                <div class="table-responsive">
                                    <table id="tbl_srn_details" class="table table-hover">
                                        <tr>
                                            <th>Product</th>
                                            <?php if ($shopObj->hasVariation($shop_id)) { ?>
                                                <th>Variations</th>
                                            <?php } ?>
                                            <th>Batch</th>                                            
                                            <th>Qty</th>
                                            <th>Purchase Price</th>
                                            <th>Total Purchase</th>
                                            <?php if ($return_header_stat < 2) { ?>
                                                <th>Action</th>
                                            <?php } ?>
                                        </tr>
                                        <tbody id="tbody">
                                            <?php
                                            $sql = "SELECT SRDID, ProductID, products.ProdDescription, SD.UnitPurchasePrice, SD.VariationID, variations.VariationName, SD.Batch, SD.ReturnQty, SD.ReturnAmount
                                                    FROM supplierreturndetails SD
                                                    INNER JOIN supplierreturn SR ON SR.SRID = SD.supplierreturn_SRID
                                                    LEFT JOIN products ON products.PDID = SD.ProductID
                                                    LEFT JOIN variations ON variations.VRID = SD.VariationID
                                                    WHERE SR.ReturnNo = '" . htmlspecialchars($return_header_id) . "'
                                                    ORDER BY SRDID DESC";

                                            $dbObj = new DBTransactions();
                                            $dbData = $dbObj->getData($sql);

                                            $return_item_count = 0;
                                            $return_total_amount = 0;

                                            foreach ($dbData as $row) {
                                                $return_item_count += floatval($row['ReturnQty']);
                                                $return_total_amount += floatval($row['ReturnAmount']);
                                            ?>
                                                <tr data-id="<?php echo htmlspecialchars($row['SRDID']); ?>">
                                                   
                                                    <td><?php echo htmlspecialchars($row['ProdDescription']); ?></td>
                                                   
                                                    <?php if ($shopObj->hasVariation($shop_id)) { ?>
                                                        <td><?php echo htmlspecialchars($row['VariationName']); ?></td>
                                                    <?php } ?>

                                                    <td><?php echo htmlspecialchars($row['Batch']); ?></td>
                                                    <td style="text-align: center;"><?php echo htmlspecialchars($row['ReturnQty']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['UnitPurchasePrice']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['ReturnAmount']); ?></td>
                                                    <?php if ($return_header_stat < 2) { ?>
                                                        <td>
                                                            <button type="button" id="btn_srndetail_<?php echo htmlspecialchars($row['SRDID']); ?>" class="btn_srn_edit btn border border-primary">
                                                                <i class="ti ti-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn_srn_remove btn border border-danger">
                                                                <i class="ti ti-x"></i>
                                                            </button>
                                                        </td>
                                                    <?php } ?>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="row">
                                    <div class="col-md-6"></div>
                                    <div class="col-md-6">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>No. Of Items</th>
                                                    <td><h4 id="sub_item_count"  name="sub_item_count" style="text-align: right; margin: 0;"><b><?php echo htmlspecialchars(number_format($return_item_count, 2)); ?></b></h4></td>
                                                </tr>
                                                <tr>
                                                    <th>Total Amount</th>
                                                    <td><h4 id="sub_purchase_price" name="sub_purchase_price" style="text-align: right; margin: 0;"><b><?php echo htmlspecialchars(number_format($return_total_amount, 2)); ?></b></h4></td>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>

                                <div>
                                    <?php if ($return_header_stat < 2) { ?>
                                        <button id="btn_submit_srn" class="btn btn-primary rounded-pill">Submit</button>
                                    <?php } else { ?>
                                        <button id="btn_srn_print" class="btn btn-primary rounded-pill"><i class="ti ti-printer"></i> Print</button>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php include '../View/footer.php'; ?>

    <script src="../Assets/jquery/SupplierReturn.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>    

</body>

</html>
