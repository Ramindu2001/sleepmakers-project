<?php
include "../Includes/includes.php";
require_once '../Includes/authcheck.php';

$shopObj = new Shop();
$shop_id = $_SESSION['shop_id'];

// Set timezone
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
<!-- Body Wrapper -->
<div class="h-100vh">
<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    <!-- Sidebar Start -->
    <?php 
    require_once '../View/sidebar.php';
    $feature_id=39;
    include '../Includes/viewPermission.php';
    if ($userType == 1 || $print == 1) {
        // Add permission check if necessary
    } else {
        ?>
        <script>
            setInterval(function(){
                $(".dt-buttons").addClass("d-none");  
            }, 100);
        </script>
        <?php
    }
    ?>
    <!-- Sidebar End -->

    <!-- Main wrapper -->
    <div class="body-wrapper">
        <?php 
        require_once '../View/header.php';
        ?>
        <div class="container-fluid">
           
            <div class="container-fluid">
                <?php 
                $shop = $shopObj->getOneShop($shop_id);
                ?>

                <input type="hidden" name="" id="shop_name" value="<?=$shop[0]['ShopName']?>">
                <input type="hidden" name="" id="shop_address_one" value="<?=$shop[0]['AddressLineOne']?> ">
                <input type="hidden" name="" id="shop_address_two" value="<?=$shop[0]['AddressLineTwo']?>">
                <input type="hidden" name="" id="shop_city" value="<?=$shop[0]['City']?>">
                <input type="hidden" name="" id="shop_number" value="<?=$shop[0]['PhoneNumber']?> ">
                <input type="hidden" name="" id="title" value="Invoice Sales Report">

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Invoice wise sales Report</h5>
                        <button class="btn btn-primary mb-3" onclick="printTable()">Print / PDF </button>
                    </div>
                    <div class="card-body">
                        <div class="container">
                            <form action="" method="get" class="form-inline">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="" class="form-label">Start Date</label>
                                        <input type="date" name="start_date" id="start_date" value="<?php echo $start_date;?>" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="" class="form-label">End Date</label>
                                        <input type="date" name="end_date" id="end_date" value="<?php echo $end_date;?>" class="form-control">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="claim" class="form-label">Claim Bill With Invoice</label>
                                        <input type="checkbox" name="claim" id="claim" class="form-check" value="1"
                                        <?php 
                                        if(isset($_GET["claim"]))
                                        {
                                            echo "checked";
                                        }
                                        ?>
                                        >
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-success mt-4" name="btn_invoice_sales" id="btn_invoice_sales">Find</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <table id="tbl_inventory_summary">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Invoice Date</th>
                                    <th>Invoice No</th>
                                    <th>Gross Amount</th>
                                    <th>Invoice Discount</th>
                                    <th>Net Amount</th>
                                    <th>Payment Method</th>
                                    <th>Invoice Status</th>
                                    <th>Customer</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $dbObj = new DBTransactions();

                                $add=" ";
                                if(isset($_GET["claim"]))
                                {
                                    $add.="OR invoiceheader.InvStat=6";
                                }
                                $sql = "SELECT * FROM invoiceheader 
                                INNER JOIN user ON user.USID = invoiceheader.user_USID 
                                INNER JOIN customers ON customers.CTID = invoiceheader.customers_CTID
                                WHERE EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."' AND invoiceheader.shop_SHID = ".$shop_id." AND invoiceheader.InvStat=1 $add;";
                                // echo $sql;
                                $row_count = 0;
                                $invData = $dbObj->getData($sql); 
                                foreach($invData as $row)
                                {
                                    $row_count += 1;
                                    $invoice_id = $row['IHID'];
                                    $InvStat = $row['InvStat'];
                                    $invoice_date = $row['InvEndTime'];
                                    $invoice_no = $row['BillNo'];
                                    $item_count = $row['InvItemCount'];
                                    $gross_amount = $row['GrossAmount'];
                                    $invoice_discount = $row['DiscountAmount'];
                                    $net_amount = $row['NetAmount'];
                                    $user_name = $row['CustName'];

                                    // Get transactions
                                    $sql_1 = "SELECT * FROM `transactions` 
                                    INNER JOIN paymethod ON paymethod.PMID = transactions.paymethod_PMID
                                    WHERE InvoiceHeader_IHID = ".$invoice_id.";";

                                    $transData = $dbObj->getData($sql_1);

                                    ?>
                                    <tr>
                                        <td><?php echo $row_count;?></td>
                                        <td><?php 
                                        if($InvStat==1)
                                        {
                                            ?>
                                            <span class="badge bg-success">Finalized</span>
                                            <?php
                                        }
                                        else if($InvStat==6)
                                        {
                                            ?>
                                            <span class="badge bg-danger" >Claim BillS </span>
                                            <?php
                                        }
                                        else
                                        {
                                            ?>
                                            <span class="badge bg-danger" >N/A</span>
                                            <?php
                                        }
                                        ?></td>
                                        <td><?php echo $invoice_date;?></td>
                                        <td><?php echo $invoice_no;?></td>
                                        <td><?php echo number_format($gross_amount,2,'.', '');?></td>
                                        <td><?php echo $invoice_discount;?></td>
                                        <td><?php echo number_format($net_amount,2,'.', '');?></td>
                                        <td>
                                            <?php 
                                            foreach($transData as $row_1)
                                            {
                                                $paymethod = $row_1['PaymethodName'];
                                                $trans_amount = $row_1['TransferAmount'];
                                                echo $paymethod . ": " . $trans_amount . "<br>";
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo $user_name;?></td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>

                            <tfoot>
                                <tr>
                                    <th colspan="5" style="text-align:right">Page Total:</th>
                                    <th colspan="2" id="page-total" style="text-align:right;"></th>
                                </tr>
                                <tr>
                                    <th colspan="5" style="text-align:right">Net Total:</th>
                                    <th colspan="2" id="full-total" style="text-align:right;"></th>
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
$(document).ready(function() {
    var shop_name = $("#shop_name").val();
    var shop_address_one = $('#shop_address_one').val();
    var shop_address_two = $('#shop_address_two').val();
    var shop_city = $('#shop_city').val();
    var shop_number = $('#shop_number').val();
    var reportname = $("#title").val();

});

function printTable() {
    var shop_name = document.getElementById("shop_name").value;
    var shop_address_one = document.getElementById("shop_address_one").value;
    var shop_address_two = document.getElementById("shop_address_two").value;
    var shop_city = document.getElementById("shop_city").value;
    var shop_number = document.getElementById("shop_number").value;
    var reportname = document.getElementById("title").value;

    var tableContent = document.getElementById("tbl_inventory_summary").outerHTML;

    var printWindow = window.open("", "", "width=900,height=700");
    printWindow.document.write("<html><head><title>" + reportname + "</title>");
    printWindow.document.write("<style>");
    printWindow.document.write("table {width: 100%; border-collapse: collapse;}");
    printWindow.document.write("th, td {border: 1px solid black; padding: 8px; text-align: left;}");
    printWindow.document.write("th {background-color: #f2f2f2;}");
    printWindow.document.write("body {font-family: Arial, sans-serif; text-align: center;}");
    printWindow.document.write("</style></head><body>");

    // Shop details
    printWindow.document.write("<h2>" + shop_name + "</h2>");
    printWindow.document.write("<p>" + shop_address_one + ", " + shop_address_two + "</p>");
    printWindow.document.write("<p>" + shop_city + "</p>");
    printWindow.document.write("<p>Contact: " + shop_number + "</p>");
    printWindow.document.write("<h3>" + reportname + "</h3>");

    // Table content
    printWindow.document.write(tableContent);

    printWindow.document.write("</body></html>");
    printWindow.document.close();
    printWindow.print();
}

</script>
</body>
</html>
