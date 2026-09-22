<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';

$grn_header_id = 0;
$grn_header_stat = 0;
if(isset($_POST['grn_header_id']))
{
    $grn_header_id = $_POST['grn_header_id'];
    $grn_header_stat = $_POST['grn_header_stat'];
}
else
{
    $_SESSION['grnheader_update'] = 3;
    header("Location: grn-header.php");
    die("Error: no grn header id.");
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
    <?php include '../View/sidebar.php'; ?>
    <div class="body-wrapper">
        <?php 
        include '../View/header.php';
        include "../View/modals/submit-grndetails.php";
        ?>
        <div class="container-fluid">
            <div class="container"></div>
            <div class="card">
                <div class="card-body">
                <h3 class="card-title fw-semibold mb-2" style="margin-top: 5px;" style="font-weight: 50px;" ><b>GRN REPORT</b></h3><br>

                <?php 
                    $dataObj = new DBTransactions();
                    $sql = "SELECT grnheader.GRNHeaderNo, grnheader.InvoiceNo, grnheader.EffectiveDate, grnheader.ItemCount, user.UserName, suppliers.SupplierName, shop.ShopName
                    FROM grnheader 
                    INNER JOIN user ON user.USID = grnheader.user_USID
                    INNER JOIN suppliers ON suppliers.SPID = grnheader.Suppliers_SPID
                    INNER JOIN shop ON shop.SHID = grnheader.shop_SHID
                    WHERE GHID = ".$grn_header_id.";";

                    $grnOne = $dataObj->getData($sql);
                    $grn_no = $grnOne[0]['GRNHeaderNo'];
                    $invoice_no = $grnOne[0]['InvoiceNo'];
                    $effective_date = $grnOne[0]['EffectiveDate'];
                    $item_count = $grnOne[0]['ItemCount'];
                    $shop_Name = $grnOne[0]['ShopName'];
                    $Supplier_Name = $grnOne[0]['SupplierName'];
                ?>
                <div class="row">
                    <div class="col-md-6">
                        <h6><b>GRN No: </b><?php echo $grn_no; ?><br>
                        <b>Effective Date:</b><?php echo $effective_date; ?><br>
                        <b>Supplier:</b><?php echo $Supplier_Name; ?></h6>
                    </div>
                    
                    <div class="col-md-6">
                        <h6><b>Invoice No:</b><?php echo $invoice_no; ?><br>
                        <b>Item Count:</b><?php echo $item_count; ?><br>
                        <b>Shop Name:</b><?php echo $shop_Name; ?></h6>
                    </div>
                </div>
                <hr>
                <h5 class="card-title fw-semibold mb-2 " style="margin-top: 0px;"></h5>
                <div class="card-body">
                    <div class="container-fluid">
                        <div class="container table-responsive" style="margin-left: -15px;"> <!-- Adjust the left margin here -->
                            <input type="hidden" name="hide_header_id" id="hide_header_id" value="<?php echo $grn_header_id; ?>">
                            <input type="hidden" name="hide_detail_id" id="hide_detail_id" value="0">


                
                            <div class="container table-responsive">
                                <table id="tbl_grn_details" class="table table-hover">
                                    <tr>
                                        <th style="min-width: 250px;">Product Name</th>
                                        <th style="min-width:150px;">Manufacture Date</th>
                                        <th style="min-width:150px;">Expired Date</th>
                                        <th style="min-width:150px;">Purchase Qty</th>
                                        <th style="min-width:150px;">Unit Purchase Price</th>
                                        <!-- <th style="min-width:150px;">Unit Label Price</th> -->
                                        <th style="min-width:150px;">Unit Sell Price</th>
                                        <th style="min-width:120px;">Total Purchase Price</th>
                                        <th style="min-width:120px;">Total Selling Price</th> 

                                    </tr>
                                    <?php 
                                    $sql = "SELECT ItemName, MnfDate, ExpDate, InitQty, UnitPurchasePrice, UnitLabelPrice, UnitSellPrice, TotalPurchasePrice, TotalSellPrice
                                            FROM grndetails
                                            INNER JOIN products ON products.PDID = grndetails.products_PDID
                                            LEFT JOIN variations ON variations.VRID = grndetails.VariationID
                                            INNER JOIN units ON units.UNID = products.PurchaseUnit
                                            WHERE GRNHeader_GHID = ".$grn_header_id." ORDER BY GDID DESC;";

                                    $dbObj = new DBTransactions();
                                    $dbData = $dbObj->getData($sql);

                                    $grn_purchase_price = 0;
                                    $grn_sell_price = 0;
                                    foreach($dbData as $row)
                                    {
                                        $grn_purchase_price += floatval($row['TotalPurchasePrice']);
                                        $grn_sell_price += floatval($row['TotalSellPrice']);
                                    
                                    ?>
                                    <tr>
                                        <td><?php echo $row['ItemName']; ?></td>
                                        <td><?php echo $row['MnfDate']; ?></td>
                                        <td><?php echo $row['ExpDate']; ?></td>
                                        <td><?php echo $row['InitQty']; ?></td>
                                        <td><?php echo $row['UnitPurchasePrice']; ?></td>
                                        <td><?php echo $row['UnitSellPrice']; ?></td>
                                        <td><?php echo $row['TotalPurchasePrice']; ?></td>
                                        <td><?php echo $row['TotalSellPrice']; ?></td>
                                    </tr>
                                    <?php 
                                    }
                                    ?>
                                    <tr>
                                        <td colspan="6" style="text-align: right;"><b>Total:</b></td>
                                        <td><b><?php echo number_format($grn_purchase_price, 2); ?></b></td>
                                        <td><b><?php echo number_format($grn_sell_price, 2); ?></b></td>
                                    </tr>
                                </table>
                            </div>
                            <br>
                            <button id="btn_grn_print" class="btn btn-primary rounded-pill"><i class="ti ti-printer"></i> Print</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../View/footer.php'; ?> 
<script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../Assets/js/sidebarmenu.js"></script>
<script src="../Assets/js/app.min.js"></script>
<script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
<script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
<script src="../Assets/js/dashboard.js"></script>

<script>
document.getElementById("btn_grn_print").addEventListener("click", function() {
    window.print();
});
</script>
</body>
</html>
