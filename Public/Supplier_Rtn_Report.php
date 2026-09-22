<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';

$srn_header_id = 0;
$srn_header_stat = 0;
if(isset($_POST['srn_header_id']))
{
    $srn_header_id = $_POST['srn_header_id'];
    $grnsrn_header_stat_header_stat = $_POST['srn_header_stat'];
}
else
{
    $_SESSION['srnheader_update'] = 3;
    header("Location: supplier_return.php");
    die("Error: no srn header id.");
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
                <h3 class="card-title fw-semibold mb-2" style="margin-top: 5px;" style="font-weight: 50px;" ><b>SUPPLIER RETURN REPORT</b></h3><br>

                <?php 
                    $dataObj = new DBTransactions();
                                
                    $sql = "SELECT supplierreturn.ReturnNo, supplierreturn.EffectiveDate, user.UserName, suppliers.SupplierName, shop.ShopName
                    FROM supplierreturn 
                    INNER JOIN user ON user.USID = supplierreturn.user_USID
                    INNER JOIN suppliers ON suppliers.SPID = supplierreturn.Supplier_SPID
                    INNER JOIN shop ON shop.SHID = supplierreturn.shop_SHID
                    WHERE SRID = ".$srn_header_id."";

                    $srnOne = $dataObj->getData($sql);
                   
                    $srn_no = $srnOne[0]['ReturnNo'];                    
                    $effective_date = $srnOne[0]['EffectiveDate'];                    
                    $shop_Name = $srnOne[0]['ShopName'];
                    $Supplier_Name = $srnOne[0]['SupplierName'];
                ?>
                <div class="row">
                    <div class="col-md-6">
                        <h6><b>SRN No: </b><?php echo $srn_no; ?></h6><br>
                        <h6><b>Effective Date:</b><?php echo $effective_date; ?></h6><br>
                        <h6><b>Supplier:</b><?php echo $Supplier_Name; ?></h6><br>
                    </div>
                    <div class="col-md-6">                                                
                        <h6><b>Shop Name:</b><?php echo $shop_Name; ?></h6><br>
                    </div>
                </div>
                <hr>
                <h5 class="card-title fw-semibold mb-2 " style="margin-top: 0px;"></h5>
                <div class="card-body">
                    <div class="container-fluid">
                        <div class="container table-responsive" style="margin-left: -15px;"> <!-- Adjust the left margin here -->
                            <input type="hidden" name="hide_header_id" id="hide_header_id" value="<?php echo $srn_header_id; ?>">
                            <input type="hidden" name="hide_detail_id" id="hide_detail_id" value="0">

                
                            <div class="container table-responsive">
                                <table id="tbl_srn_details" class="table table-hover">
                                    <tr>
                                        <th style="min-width: 250px;">Product Name</th>                                     
                                        <th style="min-width:150px;">Return Qty</th>
                                        <th style="min-width:150px;">Unit Purchase Price</th>                                        
                                        <th style="min-width:120px;">Total Return Price</th>                                        

                                    </tr>
                                    <?php 
                                    $sql = "SELECT ItemName, ReturnQty, UnitPurchasePrice, ReturnAmount
                                            FROM supplierreturndetails
                                            INNER JOIN products ON products.PDID = 	supplierreturndetails.ProductID
                                            LEFT JOIN variations ON variations.VRID = supplierreturndetails.VariationID
                                            INNER JOIN units ON units.UNID = products.PurchaseUnit
                                            WHERE supplierreturndetails.supplierreturn_SRID = 1 ORDER BY supplierreturndetails.SRDID DESC;";

                                    $dbObj = new DBTransactions();
                                    $dbData = $dbObj->getData($sql);

                                    $srn_purchase_price = 0;
                                    
                                    foreach($dbData as $row)
                                    {
                                        $srn_purchase_price += floatval($row['ReturnAmount']);                                        
                                    
                                    ?>
                                    <tr>

                                        <td><?php echo $row['ItemName']; ?></td>                                        
                                        <td><?php echo $row['ReturnQty']; ?></td>
                                        <td><?php echo $row['UnitPurchasePrice']; ?></td>                                        
                                        <td><?php echo $row['ReturnAmount']; ?></td>                                        

                                    </tr>
                                    <?php 
                                    }
                                    ?>
                                    <tr>
                                        <td colspan="6" style="text-align: right;"><b>Total:</b></td>
                                        <td><b><?php echo number_format($srn_purchase_price, 2); ?></b></td>                                        
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
<script src="../Assets/js/dashboard.js"></script>

<script>
document.getElementById("btn_grn_print").addEventListener("click", function() {
    window.print();
});
</script>
</body>
</html>
