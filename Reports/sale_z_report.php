<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Z Report</title>
   
    <?php 
        include '../View/head.php';
        // include '../View/loader.php';
        //include '../View/datatables.php';

        //get current date time
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
    ?>

    <link rel="stylesheet" href="../Assets/css/datatables.min.css">
   <!-- <script src=./Assets/js/datatables.min.js"></script>-->

</head>
<body>
    <div class="h-100vh">
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
            data-sidebar-position="fixed" data-header-position="fixed">
            
            <!-- Sidebar Start -->
            <?php 
            include '../View/sidebar.php';
            $feature_id=62;
            include '../Includes/viewPermission.php';
            if($userType==1 || $print==1)
            {
                
            }
            else
            {
                ?>
                <script>
                    setInterval(function(){
                        $(".dt-buttons").addClass("d-none");  
                    }, 100);
                    
                </script>
                <?php
            }
            ?>
            <!--  Sidebar End -->
            <div class="body-wrapper">
                <?php 
                    include '../View/header.php';
                    include "../View/modals/main-category.php";
                    $shops= new Shop();
                    $shop=$shops->getOneShop($shop_id);

                    //get date
                    date_default_timezone_set("Asia/Colombo");
                    $effective_date = date("Y-m-d");
                ?>
                               
                <div class="container-fluid">
                    <input type="hidden" name="" id="shop_name" value="<?=$shop[0]['ShopName']?>">
                    <input type="hidden" name="" id="shop_number" value="<?=$shop[0]['CompanyLocation']?>">
                    <input type="hidden" name="" id="shop_address" value="<?=$shop[0]['CompanyLocation']?>"> 

                    <div class="mb-3">
                        <button id="btn-print" class="btn btn-primary">Print</button>
                        <button id="btn-export-excel" class="btn btn-success">Export to Excel</button>
                    </div>

                    <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Sale Z Report</h5>
                    <!-- Buttons for Print and Export -->
                    <div class="container-fluid">
                        <div class="card">
                            <div class="card-body">
                                <form action=" " class="form-inline" method="get" accept-charset="utf-8">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="" for="from_date">Start Date</label>
                                                <input type="date" name="start_date" class="form-control datepicker hasDatepicker" id="start_date" placeholder="Start Date" value="<?php echo $start_date;?>">
                                            </div> 
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="" for="to_date">End Date</label>
                                                <input type="date" name="end_date"class="form-control datepicker hasDatepicker" id="end_date" placeholder="End Date" value="<?php echo $end_date;?>">
                                            </div>
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
                                        <div class="col-md-2 mt-4">
                                            <button type="submit" name="btn_sale_z_report" id="btn-filter" class="btn btn-success">Find</button>
                                        </div>
                                    </div>
                                </form>
                                <!-- Sales Table -->
                                <table class="table table-hover" id="tbl_sales">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Description</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php 
                                        $dbObj = new DBTransactions();
                                        $add = "";

                                    if (isset($_GET["claim"])) {
                                        $add = "OR invoiceheader.InvStat=6";
                                    }

                                        //top
                                        $sql = "SELECT MIN(InvEndTime) as StartTime FROM invoiceheader WHERE EffectiveDate BETWEEN '$start_date' AND '$end_date' AND shop_SHID = '$shop_id' AND InvStat=1;";
                                        $dayStartData = $dbObj->getData($sql);
                                        $start_time = $dayStartData[0]['StartTime'];

                                        $sql = "SELECT max(InvEndTime) as EndTime FROM invoiceheader WHERE EffectiveDate BETWEEN '$start_date' AND '$end_date' AND invoiceheader.shop_SHID = $shop_id AND invoiceheader.InvStat=1 $add;";
                                        $dayEndData = $dbObj->getData($sql);
                                        $end_time = $dayEndData[0]['EndTime'];
                                        
                                        //================ Sale ================//
                                        $sql = "SELECT sum(GrossAmount) as GrossSale,sum(DiscountAmount) as InvDiscount,sum(NetAmount) as NetSale FROM invoiceheader WHERE EffectiveDate BETWEEN '$start_date' AND '$end_date' AND invoiceheader.shop_SHID = '$shop_id' AND invoiceheader.InvStat=1 $add;";
                                        $saleData = $dbObj->getData($sql);
                                        $gross_sale = $saleData[0]['GrossSale'];
                                        $inv_discounts = $saleData[0]['InvDiscount'];
                                        $net_sale = $saleData[0]['NetSale'];

                                        //EXPENSES
                                        $sql = "SELECT sum(ExpenseAmount) as TotalExpense FROM expenses WHERE EffectiveDate BETWEEN '$start_date' AND '$end_date' AND shop_SHID = $shop_id;";
                                        $saleData = $dbObj->getData($sql);
                                        $total_expense = empty($saleData[0]['TotalExpense']) ? 0 : $saleData[0]['TotalExpense'];

                                        //returns
                                        
                                        $sql = "SELECT SUM(return_amount) AS returnAmount FROM retrun_invoice_header WHERE EffectiveDate BETWEEN '$start_date' AND '$end_date' AND shopID = $shop_id;";
                                        $saleData = $dbObj->getData($sql);
                                        $return_amount = empty($saleData[0]['returnAmount']) ? 0 : $saleData[0]['returnAmount'];

                                        //services
                                        $sql = "SELECT SUM(SoldAmount) AS TotalService FROM invoiceheader 
                                        INNER JOIN invoicedetails ON invoicedetails.InvoiceHeader_IHID = invoiceheader.IHID
                                        INNER JOIN products ON products.PDID = invoicedetails.products_PDID
                                        WHERE invoiceheader.EffectiveDate BETWEEN '$start_date' AND '$end_date' AND invoiceheader.shop_SHID = $shop_id AND products.ItemType='S' AND invoiceheader.InvStat=1 $add";
                                        $saleData = $dbObj->getData($sql);
                                        $service_amount = empty($saleData[0]['TotalService']) ? 0 : $saleData[0]['TotalService'];

                                        //line discounts
                                        $sql = "SELECT sum(lineDiscount) as LineDiscounts FROM invoiceheader WHERE EffectiveDate BETWEEN '$start_date' AND '$end_date' AND shop_SHID = $shop_id AND invoiceheader.InvStat=1 $add";
                                        $saleData = $dbObj->getData($sql);
                                        $line_discounts = $saleData[0]['LineDiscounts'];

                                        //supplier credit
                                        $sql_1 = "SELECT sum(DebitAmount) as supplier_debit FROM `creditsupplier`
                                        INNER JOIN suppliers ON suppliers.SPID = creditsupplier.Supplier_ID
                                        WHERE creditsupplier.CreditStat = 1 AND creditsupplier.EffectiveDate BETWEEN '$start_date' AND '$end_date' AND suppliers.shop_SHID = $shop_id;";
                                        $supData = $dbObj->getData($sql_1);
                                        $total_supplier_debit = $supData[0]['supplier_debit'];

                                        //customer
                                        //CREDIT
                                        $sql = "SELECT sum(CreditAmount) as TotalCredit FROM creditcustomer 
                                        INNER JOIN customers ON customers.CTID = creditcustomer.Customers_CTID
                                        WHERE creditcustomer.CreditStat=1 AND creditcustomer.EffectiveDate BETWEEN '$start_date' AND '$end_date' AND customers.shop_SHID = $shop_id;";
                                        $saleData = $dbObj->getData($sql);
                                        $total_credit = $saleData[0]['TotalCredit'];

                                        //debit
                                        $sql = "SELECT sum(CreditAmount) as total_credit, sum(DebitAmount) as total_debit FROM creditcustomer 
                                        INNER JOIN customers ON customers.CTID = creditcustomer.Customers_CTID
                                        WHERE creditcustomer.CreditStat=1 AND EffectiveDate BETWEEN '$start_date' AND '$end_date' AND customers.shop_SHID = $shop_id;";
                                        $saleData = $dbObj->getData($sql);
                                        $total_credit = floatval($saleData[0]['total_credit']);
                                        $total_debit = floatval($saleData[0]['total_debit']);

                                        $credit_balance = $total_credit - $total_debit;

                                        //credit balance
                                        // $sql = "SELECT sum(Balance) as CreditBalance FROM creditcustomer 
                                        // INNER JOIN customers ON customers.CTID = creditcustomer.Customers_CTID
                                        // WHERE creditcustomer.CreditStat=1 AND EffectiveDate BETWEEN '$start_date' AND '$end_date' AND customers.shop_SHID = $shop_id;";
                                        // $saleData = $dbObj->getData($sql);
                                        // $credit_balance = $saleData[0]['CreditBalance'];

                                        ?>

                                        <tr>
                                            <td>1</td>
                                            <td>Start Date Time</td>
                                            <td><?=$start_time?></td>
                                        </tr>
                                        <tr>
                                            <td>2</td>
                                            <td>End Date Time</td>
                                            <td><?=$end_time?></td>
                                        </tr>
                                        <tr>
                                            <td></td>
                                            <td style="text-align: center;"><b>Sale</b></td>
                                            <td> </td>
                                        </tr>  
                                        <tr>
                                            <td>4</td>
                                            <td>Gross Sales</td>
                                            <td style="text-align: right;"><?php echo $gross_sale;?></td>
                                        </tr>
                                        <tr>
                                            <td>5</td>
                                            <td>Invoice Discounts</td>
                                            <td style="text-align: right;"><?php echo $inv_discounts;?></td>
                                        </tr>
                                        <tr>
                                            <td>6</td>
                                            <td>Net Sale</td>
                                            <td style="text-align: right;"><?php echo $net_sale;?></td>
                                        </tr>
                                        <tr>
                                            <td>7</td>
                                            <td>Total Expense</td>
                                            <td style="text-align: right;"><?php echo $total_expense;?></td>
                                        </tr>

                                        <tr>
                                            <td>8</td>
                                            <td>Return Amount</td>
                                            <td style="text-align: right;"><?php echo $return_amount;?></td>
                                        </tr>
                                        <tr>
                                            <td>9</td>
                                            <td>Service Charges</td>
                                            <td style="text-align: right;"><?php echo $service_amount;?></td>
                                        </tr>
                                        <tr>
                                            <td>10</td>
                                            <td>Line Discount</td>
                                            <td style="text-align: right;"><?php echo $line_discounts;?></td>
                                        </tr>

                                        <tr>
                                            <td>11</td>
                                            <td>Supplier Due payment</td>
                                            <td style="text-align: right;"><?php echo $total_supplier_debit;?></td>
                                        </tr>

                                        <tr>
                                            <td>12</td>
                                            <td>Customer Due Sales</td>

                                            <td style="text-align: right;"><?php echo $total_credit;?></td>
                                        </tr>

                                        <tr>
                                            <td>13</td>
                                            <td>Customer Repayments</td>

                                            <td style="text-align: right;"><?php echo $total_debit;?></td>
                                        </tr>

                                        <tr>
                                            <td>14</td>
                                            <td>Total Credit Receivables</td>
                                            <td style="text-align: right;"><?php echo $credit_balance;?></td>
                                        </tr>

                                        <tr>
                                            <td>15</td>
                                            <td><b>Total Received</b></td>
                                            <td style="text-align: right;"><b><?php echo $net_sale;?></b></td>
                                        </tr>

                                        <tr>
                                            <td></td>
                                            <td style="text-align: center;"><b>Transactions</b></td>
                                            <td> </td>
                                        </tr>    
                                        
                                        <?php 
                                        //TRANSACTIONS
                                        $sql_1 = "SELECT sum(TransferAmount) as paymethodSale, PaymethodName FROM transactions
                                        INNER JOIN invoiceheader ON invoiceheader.IHID = transactions.InvoiceHeader_IHID 
                                        INNER JOIN paymethod ON paymethod.PMID = transactions.paymethod_PMID
                                        WHERE EffectiveDate BETWEEN '$start_date' AND '$end_date' AND invoiceheader.shop_SHID = $shop_id AND invoiceheader.InvStat=1 GROUP BY paymethod_PMID;";
                                        
                                        $payData = $dbObj->getData($sql_1);

                                        $row_num = 17;
                                        foreach($payData as $row)
                                        {   
                                            $row_num += 1;
                                            ?>
                                            <tr>
                                                <td><?php echo $row_num;?></td>
                                                <td><?php echo $row['PaymethodName'];?></td>
                                                <td style="text-align: right;"><?php echo $row['paymethodSale'];?></td>
                                            </tr>
                                            <?php 
                                        }//foreach
                                        ?>

                                    </tbody>
                                                                    
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../View/footer.php';?>         

    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>

    <!-- JavaScript for Print and Export to Excel -->
    <script>
        // Print Button Functionality
        // document.getElementById('btn-print').addEventListener('click', function () {
        //     var content = document.getElementById('tbl_sales').outerHTML;
        //     var newWindow = window.open('', '_blank');
        //     newWindow.document.write('<html><head><title>Print</title></head><body>' + content + '</body></html>');
        //     newWindow.document.close();
        //     newWindow.print();
        // });

        // Print Button Functionality with Header and Footer
        document.getElementById('btn-print').addEventListener('click', function () {
            var content = document.getElementById('tbl_sales').outerHTML;
            
            var shop_name = $("#shop_name").val();
            var shop_address_one = $('#shop_address_one').val();
            var shop_address_two = $('#shop_address_two').val();
            var shop_city = $('#shop_city').val();
            var shop_number = $('#shop_number').val();
            var reportname=$("#title").val();
            
            // Header and Footer HTML
           
            var header = `
                <div style="text-align: center; font-size: 16px; font-weight: bold;">
                    <p>${shop_name}</p>
                </div>
            `;
            
            var footer = `
                <div style="text-align: center; font-size: 10px;">
                    <p>Date: ${new Date().toLocaleDateString()}</p>
                </div>
            `;
            
            // Open a new window to write the content
            var newWindow = window.open('', '_blank');
            
            // Write the full HTML content (header, table, footer)
            newWindow.document.write('<html><head><title>Print</title><style>' +
                'body { font-family: Arial, sans-serif; }' +
                '.table { width: 100%; border-collapse: collapse; }' +
                '.table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }' +
                '</style></head><body>' +
                header + // Insert header
                '<br>' + content + // Insert the table content
                footer + // Insert footer
                '</body></html>');
            
            // Close the document to trigger rendering
            newWindow.document.close();
            
            // Wait for the content to load and then print
            newWindow.onload = function () {
                newWindow.print();
                newWindow.close();
            };
        });


        // Export to Excel Button Functionality
        document.getElementById('btn-export-excel').addEventListener('click', function () {
            var table = document.getElementById('tbl_sales');
            var rows = table.querySelectorAll('tr');
            var csvContent = Array.from(rows).map(row => {
                return Array.from(row.querySelectorAll('th, td'))
                    .map(cell => cell.textContent.replace(/,/g, '')) // Remove commas for clean CSV
                    .join(',');
            }).join('\n');
            
            var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'Sales_Report.csv';
            link.click();
        });
    </script>
    
</body>
</html>
