<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';
?>

<!doctype html>
<html lang="en">

<head>
  <?php 
  include '../View/head.php';
  // include '../View/loader.php';
  include '../View/datatables.php';

  // Set timezone
  date_default_timezone_set("Asia/Colombo");

  // Initialize date variables
  $start_date = date("Y-m-d");
  $end_date = date("Y-m-d");

  if (isset($_GET['date'])) {
      $date = explode("_", $_GET['date']);
      $start_date = htmlspecialchars($date[0]);
      $end_date = htmlspecialchars($date[1]);
  }
  ?>
</head>

<body>
<div class="h-100vh">
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
         data-sidebar-position="fixed" data-header-position="fixed">

        <!-- Sidebar -->
        <?php 
        include '../View/sidebar.php';
        $feature_id = 57;
        include '../Includes/viewPermission.php';

        // Restrict actions based on permissions
        if ($userType != 1 && $print != 1) {
            echo '<script>setInterval(() => $(".dt-buttons").addClass("d-none"), 100);</script>';
        }
        ?>

        <!-- Main Wrapper -->
        <div class="body-wrapper">
            <?php 
            include '../View/header.php';
            include '../View/modals/main-category.php';

            $shops = new Shop();
            $shop = $shops->getOneShop($shop_id);
            ?>

            <!-- Container -->
            <div class="container-fluid">
                <input type="hidden" id="shop_name" value="<?= htmlspecialchars($shop[0]['ShopName']); ?>">
                <input type="hidden" id="shop_address_one" value="<?= htmlspecialchars($shop[0]['AddressLineOne']); ?>">
                <input type="hidden" id="shop_address_two" value="<?= htmlspecialchars($shop[0]['AddressLineTwo']); ?>">
                <input type="hidden" id="shop_city" value="<?= htmlspecialchars($shop[0]['City']); ?>">
                <input type="hidden" id="shop_number" value="<?= htmlspecialchars($shop[0]['PhoneNumber']); ?>">
                <input type="hidden" id="title" value="Sales Summary">

                <h5 class="card-title fw-semibold mb-2">Sales Summary</h5>

                <!-- Print Button -->
                <button class="btn btn-primary mb-3" onclick="printTable()">Print / PDF </button>
                <button onclick="downloadExcel()" class="btn btn-primary mb-3">Download Excel</button>

                <!-- Header Information -->
                <div class="mb-3">
                    <h5><strong>Profit and Loss</strong></h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Start Date:</strong> <?= $start_date; ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>End Date:</strong> <?= $end_date; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Sales Summary -->
                <div class="card">
                    <div class="card-body">
                        <!-- Form -->
                        <form action="../Controller/ReportController.php" method="POST" class="form-inline">
                            <div class="row">
                                <div class="col-md-5">
                                    <label for="start_date">Start Date</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control"
                                           value="<?= $start_date; ?>">
                                </div>
                                <div class="col-md-5">
                                    <label for="end_date">End Date</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control"
                                           value="<?= $end_date; ?>">
                                </div>
                                <div class="col-md-2 mt-4">
                                    <button type="submit" name="btn_profitnloss_date" id="btn-filter"
                                            class="btn btn-success">Find</button>
                                </div>
                            </div>
                        </form>

                        <!-- Sales Table with Borders -->
                        <table class="table table-bordered table-hover" id="tbl_profit_loss">
                            <thead>
                            <tr>
                                <th>Category</th>
                                <th style="text-align: right;">Amount</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php 
                            $dbObj = new DBTransactions();

                            // Gross Sale Query
                           $sql = "SELECT IFNULL(SUM(GrossAmount),0) AS GrossSale , IFNULL(SUM(NetAmount),0) AS NetSale FROM invoiceheader WHERE EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."' AND shop_SHID = ".$shop_id." AND InvStat= 1;";
                           $gross_sale_data = $dbObj->getData($sql);

                            $gross_sale = (float) $gross_sale_data[0]['GrossSale'];
                            $net_sales = (float) $gross_sale_data[0]['NetSale'];

                            // Return Sales Query
                            $sql_1 = "SELECT SUM(return_gross_amount) AS TotalReturn FROM retrun_invoice_header WHERE EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."' AND shopID = ".$shop_id.";";
                            $return_data = $dbObj->getData($sql_1);

                            $total_return = (float) $return_data[0]['TotalReturn'];

                            // COGS Query
                            $sql_2 = "SELECT IFNULL((SUM(invoicedetails.SellQty * pricehistory.PurchasePrice)),0) AS COGS FROM invoicedetails 
                            INNER JOIN invoiceheader ON invoiceheader.IHID = invoicedetails.InvoiceHeader_IHID
                            INNER JOIN pricehistory ON pricehistory.ProductID = invoicedetails.products_PDID AND pricehistory.BatchID = invoicedetails.batch_no
                            WHERE invoiceheader.EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."' AND shop_SHID = ".$shop_id." AND InvStat= 1;";
                            $sale_detail_data = $dbObj->getData($sql_2);
                            $total_purchase_price = (float) $sale_detail_data[0]['COGS'];

                            // Expenses Query
                            $sql_3 = "SELECT sum(ExpenseAmount) as TotalExpenses FROM expenses WHERE EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."' AND shop_SHID = ".$shop_id.";";
                            $expense_data = $dbObj->getData($sql_3);
                            $total_expense = (float) $expense_data[0]['TotalExpenses'];

                            // Calculations
                            $net_sales = $net_sales - $total_return;
                            $gross_profit = $net_sales - ($total_purchase_price);
                            $net_profit = $gross_profit - $total_expense;

                            ?>

                            <tr><td><b>Revenue (Sales)</b></td></tr>
                            <tr>
                                <td>Gross Sales</td>
                                <td style="text-align: right;"><?= number_format($gross_sale, 2); ?></td>
                            </tr>
                            <tr>
                                <td>Return Sales</td>
                                <td style="text-align: right;">(<?= number_format($total_return, 2); ?>)</td>
                            </tr>
                            <tr>
                                <td><b>Net Sales</b></td>
                                <td style="text-align: right;"><b><?= number_format($net_sales, 2); ?></b></td>
                            </tr>
                            <tr>
                                <td><b>Cost of Goods Sold (COGS)</b></td>
                                <td style="text-align: right;"><b>(<?= number_format($total_purchase_price, 2); ?>)</b></td>
                            </tr>
                            <tr>
                                <td><b>Gross Profit (Net sales - Cost of goods sold (COGS)) </b></td>
                                <td style="text-align: right;"><b><?= number_format($gross_profit, 2); ?></b></td>
                            </tr>
                            <tr>
                                <td><b>Expenses</b></td>
                            </tr>
                            <tr>
                                <td>General Expenses</td>
                                <td style="text-align: right;">(<?= number_format($total_expense, 2); ?>)</td>
                            </tr>
                            <tr>
                                <td><b>Total Expenses</b></td>
                                <td style="text-align: right;"><b>(<?= number_format($total_expense, 2); ?>)</b></td>
                            </tr>
                            <tr>
                                <td><b>Net Profit (Total revenue - Total expenses) </b></td>
                                <td style="text-align: right;"><b><?= number_format($net_profit, 2); ?></b></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../View/footer.php'; ?>

<!-- Scripts -->
<script src="../Assets/jquery/inventory_summary.js"></script>
<script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
<script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../Assets/js/sidebarmenu.js"></script>
<script src="../Assets/js/app.min.js"></script>
<script src="../Assets/libs/simplebar/dist/simplebar.js"></script>

<!-- Print Function -->


<!-- Include jsPDF and SheetJS libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
function printTable() {
    var printContent = document.getElementById("tbl_profit_loss");
    var header = "<h3>Profit and Loss</h3><p><strong>Start Date:</strong> <?= $start_date; ?> | <strong>End Date:</strong> <?= $end_date; ?></p>";

    var newWindow = window.open('', '', 'height=500, width=800');
    newWindow.document.write('<html><head><title>Print Table</title>');
    
    // Add custom print styles for table borders
    newWindow.document.write('<style>');
    newWindow.document.write('table {border-collapse: collapse; width: 100%;}');
    newWindow.document.write('th, td {border: 1px solid black; padding: 8px; text-align: right;}');
    newWindow.document.write('th {background-color: #f2f2f2; font-weight: bold;}');
    newWindow.document.write('body {font-family: Arial, sans-serif; margin: 20px;}');
    newWindow.document.write('</style>');

    newWindow.document.write('</head><body>');
    newWindow.document.write(header);
    newWindow.document.write(printContent.outerHTML);
    newWindow.document.write('</body></html>');
    
    newWindow.document.close();
    newWindow.print();
}


function downloadExcel() {
    var table = document.getElementById("tbl_profit_loss");
    var wb = XLSX.utils.table_to_book(table, { sheet: "Profit and Loss Report" });
    XLSX.write(wb, { bookType: "xlsx", type: "binary" });
    
    function s2ab(s) {
        var buf = new ArrayBuffer(s.length);
        var view = new Uint8Array(buf);
        for (var i = 0; i < s.length; i++) view[i] = s.charCodeAt(i) & 0xff;
        return buf;
    }
    
    var fileName = "profit_loss_report.xlsx";
    saveAs(new Blob([s2ab(XLSX.write(wb, { bookType: "xlsx", type: "binary" }))], { type: "application/octet-stream" }), fileName);
}
</script>


</body>
</html>
