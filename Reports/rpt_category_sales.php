<?php 
include "../Includes/includes.php";
require_once '../Includes/authcheck.php';

$shopObj = new Shop();
$shop_id = $_SESSION['shop_id'];

// Get current date time
date_default_timezone_set("Asia/Colombo");

$start_date = "";
$end_date = "";
if (isset($_GET['date'])) {
   $date = explode("_", $_GET['date']);
   $start_date = $date[0];
   $end_date = $date[1];
} 
if(isset($_GET["start_date"]) && isset($_GET["end_date"]))
{
   $start_date = $_GET["start_date"];
   $end_date = $_GET["end_date"];
}
else {
   $start_date = date("Y-m-d");
   $end_date = date("Y-m-d");
}

// Calculate total line discount
$dbObj = new DBTransactions();
$sql = "SELECT IFNULL(SUM(lineDiscount), 0) AS lineDiscount FROM invoiceheader 
        WHERE EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."' 
        AND shop_SHID = ".$shop_id." AND InvStat = 1;";
$discount_data = $dbObj->getData($sql);
$TotLineDis = (float) $discount_data[0]['lineDiscount'];
?>

<!doctype html>
<html lang="en">

<head>
    <?php 
    require_once '../View/head.php';
    require_once '../View/loader.php';
    ?>
    <link rel="stylesheet" href="../Assets/css/datatables.min.css">
    <script src="../Assets/js/datatables.min.js"></script>
</head>
<body>
<div class="h-100vh">
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed">
        <?php 
        require_once '../View/sidebar.php';
        ?>
        <div class="body-wrapper">
            <?php require_once '../View/header.php'; ?>
            <div class="container-fluid">
                <div class="container-fluid">
                    <?php 
                    $shop = $shopObj->getOneShop($shop_id);
                    ?>
                    <input type="hidden" id="shop_name" value="<?=$shop[0]['ShopName']?>">
                    <input type="hidden" id="shop_address_one" value="<?=$shop[0]['AddressLineOne']?> ">
                    <input type="hidden" id="shop_address_two" value="<?=$shop[0]['AddressLineTwo']?>">
                    <input type="hidden" id="shop_city" value="<?=$shop[0]['City']?>">
                    <input type="hidden" id="shop_number" value="<?=$shop[0]['PhoneNumber']?> ">
                    <input type="hidden" id="title" value="Category Sales Report">
            
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Category Sales Report</h5>
                        </div>
                        <div class="card-body">
                            <div class="container">
                                <form action=" " method="get" class="form-inline">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="form-label">Start Date</label>
                                            <input type="date" name="start_date" id="start_date" value="<?php echo $start_date;?>" class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">End Date</label>
                                            <input type="date" name="end_date" id="end_date" value="<?php echo $end_date;?>" class="form-control">
                                        </div>
                                        <?php 
                                    if($userType==1)
                                    {
                                        ?>
                                        <div class="col-md-2">
                                            <label for="claim" class="form-label">Claim Bill With Invoices</label>
                                            <input type="checkbox" name="claim" id="claim" class="form-check" value="1"
                                            <?php 
                                            if(isset($_GET["claim"]))
                                            {
                                                echo "checked";
                                            }
                                            ?>
                                            >
                                        </div>
                                        <?php
                                    }
                                    ?>
                                        <div class="col-md-2">
                                            <button class="btn btn-success mt-4" name="btn_category_sales" id="btn_category_sales">Find</button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <table id="tbl_inventory_summary">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Category Name</th>
                                        <th>Total Qty Sold</th>
                                        <th>Total Sales Value</th>
                                        <th>COGS Value</th>
                                        <th>Average Unit Price</th>
                                        <th>Average Daily Sales</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $sql = "SELECT * FROM `categories` WHERE shop_SHID = ".$shop_id.";";
                                    $catData = $dbObj->getData($sql);
                                    $row_count = 0;

                                    foreach ($catData as $row) {
                                        $row_count++;
                                        $category_id = $row['CTID'];
                                        $category_name = $row['CategoryName'];

                                        $add = "";
                                    if (isset($_GET["claim"])) {
                                        $add = " OR invoiceheader.InvStat=6";
                                    }


                                        $sql_1 = "SELECT SUM(SellQty) AS sold_qty, SUM(SoldAmount) AS net_amount, 
                                                  IFNULL((SUM(invoicedetails.SellQty * pricehistory.PurchasePrice)), 0) AS COGS  
                                                  FROM invoicedetails
                                                  INNER JOIN invoiceheader ON invoiceheader.IHID = invoicedetails.InvoiceHeader_IHID
                                                  INNER JOIN products ON products.PDID = invoicedetails.products_PDID
                                                  INNER JOIN subcategories ON subcategories.SCID = products.Subcategories_SCID
                                                  INNER JOIN pricehistory ON pricehistory.ProductID = invoicedetails.products_PDID 
                                                  AND pricehistory.BatchID = invoicedetails.batch_no
                                                  WHERE invoiceheader.EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."' 
                                                  AND invoiceheader.shop_SHID = ".$shop_id." 
                                                  AND subcategories.categories_CTID = ".$category_id." AND invoiceheader.InvStat=1 $add;";
                                        
                                        $invData = $dbObj->getData($sql_1);
                                        if (!empty($invData)) {
                                            $sold_qty = floatval($invData[0]['sold_qty']);
                                            $sold_amount = floatval($invData[0]['net_amount']);
                                            $COGS = floatval($invData[0]['COGS']);
                                            
                                            $start_time = strtotime($start_date);
                                            $end_time = strtotime($end_date);
                                            $days = round(($end_time - $start_time) / (60 * 60 * 24)) + 1;

                                            if ($sold_qty > 0) {
                                                $average_price = round($sold_amount / $sold_qty, 2);
                                                $average_sale_qty = round(($sold_qty / $days), 2);
                                                ?>
                                                <tr>
                                                    <td><?php echo $row_count;?></td>
                                                    <td><?php echo $category_name;?></td>
                                                    <td><?php echo $sold_qty;?></td>
                                                    <td><?php echo $sold_amount;?></td>
                                                    <td><?php echo $COGS;?></td>
                                                    <td><?php echo $average_price;?></td>
                                                    <td><?php echo $average_sale_qty;?></td>
                                                </tr>
                                                <?php
                                            }
                                        }
                                    }
                                    ?>
                                </tbody>
                                <tfoot>
                                <tr>
                                    <th colspan="2" style="text-align:right">Total:</th>
                                    <th id="total_qty_sold"></th>
                                    <th id="total_sales_value"></th>
                                    <th id="total_cogs_value"></th>
                                    <th></th>
                                    <th id="total_avg_daily_sales"></th>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../View/footer.php';?>

<script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../Assets/js/sidebarmenu.js"></script>
<script src="../Assets/js/app.min.js"></script>

<script>
    $(document).ready(function () {
        var shop_name = $("#shop_name").val();
        var shop_address_one = $('#shop_address_one').val();
        var shop_address_two = $('#shop_address_two').val();
        var shop_city = $('#shop_city').val();
        var shop_number = $('#shop_number').val();
        var reportname = $("#title").val();

        $("#tbl_inventory_summary").DataTable({
            paging: true,
            searching: true,
            footerCallback: function (row, data, start, end, display) {
                let api = this.api();
                const intVal = (i) => (typeof i === "string" ? i.replace(/[\$,]/g, "") * 1 : typeof i === "number" ? i : 0);

                const totals = [2, 3, 4, 6].map(idx =>
                    api.column(idx, { page: "current" }).data().reduce((a, b) => intVal(a) + intVal(b), 0)
                );

                const adjustedSalesValue = totals[1] - <?= $TotLineDis ?>;

                $("#total_qty_sold").text(totals[0].toFixed(2));
                $("#total_sales_value").text(adjustedSalesValue.toFixed(2));
                $("#total_cogs_value").text(totals[2].toFixed(2));
                $("#total_avg_daily_sales").text(totals[3].toFixed(2));
            }
        });
    });
</script>
</body>
</html>
