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
                <input type="hidden" id="title" value="Trial Balance">

                <!-- <h5 class="card-title fw-semibold mb-2">Trial Balance</h5> -->
                <br>

                <!-- Print Button -->
                <button class="btn btn-primary mb-3" onclick="printTable()">Print / PDF </button>
                <button onclick="downloadExcel()" class="btn btn-primary mb-3">Download Excel</button>

                <!-- Header Information -->
                <div class="mb-3">
                    <h5><strong>Balance Sheet</strong></h5>
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
                                    <button type="submit" name="btn_balance_sheet" id="btn-filter"
                                            class="btn btn-success">Find</button>
                                </div>
                            </div>
                        </form>

                        <!-- Sales Table with Borders -->
                        <table class="table table-bordered table-hover" id="tbl_balance">
                            <thead>
                            <!-- <tr>
                                <th>Account</th>
                                <th style="text-align: right;">Debit(Rs.)</th>
                                <th style="text-align: right;">Credit(Rs.)</th>
                            </tr> -->
                            </thead>
                            <tbody>
                            <?php 
                            $dbObj = new DBTransactions();
                            

                            //----------------Assests Accounts----------------//

                            // Total Sales Query
                            $sql = "SELECT IFNULL(SUM(GrossAmount),0) AS GrossSale , IFNULL(SUM(NetAmount),0) AS NetSale FROM invoiceheader WHERE (EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."') AND shop_SHID = ".$shop_id." AND InvStat= 1;";
                            $gross_sale_data = $dbObj->getData($sql);

                            $gross_sale = (float) $gross_sale_data[0]['GrossSale'];
                            $net_sales = (float) $gross_sale_data[0]['NetSale'];

                            //without credit sales
                            $sql_wc = "SELECT SUM(INV.NetAmount) AS NetAmt FROM invoiceheader INV INNER JOIN transactions TR ON TR.InvoiceHeader_IHID = INV.IHID WHERE paymethod_PMID <> 4 AND (EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."') AND shop_SHID = ".$shop_id."  AND InvStat= 1;";
                            $sale_data = $dbObj->getData($sql_wc);                          
                            $net_saleswc = (float) $sale_data[0]['NetAmt'];


                            // Return Sales Query
                            $sql_1 = "SELECT SUM(return_gross_amount) AS TotalReturn FROM retrun_invoice_header WHERE (EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."') AND shopID = ".$shop_id.";";
                            $return_data = $dbObj->getData($sql_1);
                            $total_return = (float) $return_data[0]['TotalReturn'];

                            //Accounts Receivable
                            $sql_2 = "SELECT SUM(Balance) AS TotalCredit FROM creditcustomer WHERE (EffectiveDate  BETWEEN '".$start_date."' AND '".$end_date."') AND shop_SHID = ".$shop_id.";";
                            $AccRec_data = $dbObj->getData($sql_2);
                            $total_Acc_rec = (float) $AccRec_data[0]['TotalCredit'];

                            //Inventory
                            $sql_3 = "SELECT SUM(CurrentQty * PurchasePrice) AS InventoryValue FROM inventory INV INNER JOIN pricehistory PH ON INV.INID = PH.Inventory_INID AND INV.BatchID = PH.BatchID WHERE INV.is_default = 0 AND shop_SHID = ".$shop_id." AND INV.CurrentQty > 0 ;";
                            $inv_data = $dbObj->getData($sql_3);
                            $total_inv = (float) $inv_data[0]['InventoryValue'];

                            //-------------------------------------------------//

                            //----------------Liabillities Accounts----------------//
                            //Liabillities                            
                            $sql_4 = "SELECT SUM(Balance) AS TotalCredit FROM creditsupplier WHERE (EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."') AND shop_SHID = ".$shop_id.";";
                            $liab_data = $dbObj->getData($sql_4);
                            $Acc_pay = (float) $liab_data[0]['TotalCredit'];
                            //-------------------------------------------------//
                            
                             //----------------Revenue Accounts----------------//
                            // COGS Query
                            $sql_2 = "SELECT IFNULL((SUM(invoicedetails.SellQty * pricehistory.PurchasePrice)),0) AS COGS FROM invoicedetails 
                            INNER JOIN invoiceheader ON invoiceheader.IHID = invoicedetails.InvoiceHeader_IHID
                            INNER JOIN pricehistory ON pricehistory.ProductID = invoicedetails.products_PDID AND pricehistory.BatchID = invoicedetails.batch_no
                            WHERE (invoiceheader.EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."') AND shop_SHID = ".$shop_id." AND InvStat= 1;";
                            $sale_detail_data = $dbObj->getData($sql_2);
                            $total_purchase_price = (float) $sale_detail_data[0]['COGS'];

                            //----------------Expenses Accounts----------------//

                            // Expenses Query
                            $sql_5 = "SELECT sum(ExpenseAmount) as TotalExpenses FROM expenses WHERE (EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."') AND shop_SHID = ".$shop_id.";";
                            $expense_data = $dbObj->getData($sql_5);
                            $total_expense = (float) $expense_data[0]['TotalExpenses'];

                            //purchase expenses
                            $sql_6 = "SELECT SUM(TotalPurchasePrice) AS TotPurchase FROM grnheader WHERE (EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."') AND shop_SHID = ".$shop_id.";";
                            $purch_data = $dbObj->getData($sql_6);
                            $total_GRN = (float) $purch_data[0]['TotPurchase'];
                            
                            //----------------Contra Accounts----------------//
                            //Supplier returns
                            $sql_7 = "SELECT SUM(ReturnAmount) AS TotReturn FROM supplierreturn WHERE (EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."') AND shop_SHID = ".$shop_id.";";
                            $Supp_rtn_data = $dbObj->getData($sql_7);
                            $total_SRN = (float) $Supp_rtn_data[0]['TotReturn'];
                            //-----------------------------------------------//

                            // Calculations
                            $net_sales = $net_sales - $total_return;
                            $net_saleswc = $net_saleswc - $total_return;
                            
                            //Total Debit
                            $total_debit = $net_saleswc + $total_inv + $total_Acc_rec + $total_purchase_price + $total_expense + $total_GRN + $total_return;

                            //Total debit
                            $total_credit = $net_sales + $Acc_pay + $total_SRN;

                            ?>

                            <tr><td><b>Assets </b></td></tr>
                            <tr><td><b>Account </b></td><td><b>Amount </b></td></tr>
                            <tr>
                                <td>Cash</td> <!--Current cash balance in your bank or hand.-->
                                <td style="text-align: right;"><?= number_format($net_saleswc, 2); ?></td>
                            </tr>
                            <tr>
                                <td>Accounts Receivable</td> <!--Amounts owed by customers (outstanding invoices).-->
                                <td style="text-align: right;">(<?= number_format($total_Acc_rec, 2); ?>)</td>
                            </tr>
                            <tr>
                                <td>Inventory</td>
                                <td style="text-align: right;"><?= number_format($total_inv, 2); ?></td>
                            </tr>

                            <tr><td><b>Liabilities </b></td></tr>                           
                            <tr>
                                <td>Accounts Payable</td> <!--Amounts owed to suppliers (outstanding GRNs or credit purchases).-->
                                <td></td><td style="text-align: right;">(<?= number_format($Acc_pay, 2); ?>)</td>
                            </tr>
                            <tr>
                                <td><b>Revenue </b></td>
                            </tr>
                            <tr>
                                <td>Sales Revenue</td> <td></td>
                                <td style="text-align: right;">(<?= number_format($net_sales, 2); ?>)</td>
                            </tr>
                            <tr>
                                <td>Other Income</td><td></td>
                                <td style="text-align: right;">(<?= number_format(0, 2); ?>)</td>
                            </tr>
                            
                            <tr>
                                <td><b>Expenses </b></td>
                            </tr>

                            <tr>
                                <td>Cost of Goods Sold (COGS)</td>
                                <td style="text-align: right;"><?= number_format($total_purchase_price, 2); ?></td>
                            </tr>

                            <tr>
                                <td>Purchase Expenses</td>
                                <td style="text-align: right;"><?= number_format($total_GRN, 2); ?></td>
                            </tr>

                            <tr>
                                <td>Operating Expenses</td>
                                <td style="text-align: right;"><?= number_format($total_expense, 2); ?></td>
                            </tr>

                            <tr>
                                <td><b>Contra Accounts</b></td>
                            </tr>

                            <tr>
                                <td>Sales Returns and Allowances</td>
                                <td style="text-align: right;"><?= number_format($total_return, 2); ?></td>
                            </tr>

                            <tr>
                                <td>Purchase Returns</td> <td></td>
                                <td style="text-align: right;"><?= number_format($total_SRN, 2); ?></td>
                            </tr>

                            <tr>
                                <td><b>Totals</b></td>
                                <td style="text-align: right;"><?= number_format($total_debit, 2); ?></td>
                                <td style="text-align: right;"><?= number_format($total_credit, 2); ?></td>
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
    var printContent = document.getElementById("tbl_trial_balance");
    var header = "<h3>Trial Balance</h3><p><strong>Start Date:</strong> <?= $start_date; ?> | <strong>End Date:</strong> <?= $end_date; ?></p>";

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
    var table = document.getElementById("tbl_trial_balance");
    var wb = XLSX.utils.table_to_book(table, { sheet: "Trial Balance" });
    XLSX.write(wb, { bookType: "xlsx", type: "binary" });
    
    function s2ab(s) {
        var buf = new ArrayBuffer(s.length);
        var view = new Uint8Array(buf);
        for (var i = 0; i < s.length; i++) view[i] = s.charCodeAt(i) & 0xff;
        return buf;
    }
    
    var fileName = "Trial_Balance_report.xlsx";
    saveAs(new Blob([s2ab(XLSX.write(wb, { bookType: "xlsx", type: "binary" }))], { type: "application/octet-stream" }), fileName);
}
</script>


</body>
</html>
