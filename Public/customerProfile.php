<?php 
include "../Includes/includes.php";
include '../Includes/authcheck.php';
$dbObj = new DBTransactions();
if (!isset($_GET["cus_id"])) {
    header("Location:../Reports/customer-profiles.php");
} else {
    $customer_id = $_GET["cus_id"];
}

$sql = "SELECT * FROM shop
INNER JOIN company ON company.CMID = shop.Company_CMID
WHERE SHID = ".$shop_id.";";
$shopData = $dbObj->getData($sql);
$multi_category = $shopData[0]['is_multicategory'];
$sql = "SELECT * FROM customers WHERE CTID='$customer_id' AND shop_SHID='$shop_id'";
if($multi_category==1)
{
    $com_id=$shopData[0]['CMID'];
    $sql = "SELECT * FROM customers
            INNER JOIN shop ON shop.SHID = customers.shop_SHID
            WHERE shop.Company_CMID='$com_id' AND customers.CTID='$customer_id'";

}
$itemData = $dbObj->getData($sql);
if (count($itemData) == 0) {
    header("Location:../Reports/customer-profiles.php");
}
$outstanding=0;
$shopObj = new Shop();
$hasPrescription = $shopObj->hasPrescription($shop_id);
$prescription = $hasPrescription == 1 ? 1 : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php 
    include '../View/head.php';
    // include '../View/loader.php';
    ?>
    <style>
    .table>:not(caption)>*>* {
        padding: 10px;
    }

    input[readonly] {
        background-color: rgb(235, 235, 235);
        border-color: rgb(235, 235, 235);
    }
    </style>
</head>

<body>
    <!--  Body Wrapper -->
    <div class="h-100vh">
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
            data-sidebar-position="fixed" data-header-position="fixed">
            <!-- Sidebar Start -->
            <?php include '../View/sidebar.php'; ?>
            <!--  Sidebar End -->

            <div class="body-wrapper">
                <!--  Header Start -->
                <?php include '../View/header.php'; ?>
                <!--  Header End -->

                <div class="container-fluid">
                    <h5 class="card-title fw-semibold mb-4">Customer Profile</h5>

                    <!-- Alerts -->
                    <?php 
                    if (isset($_SESSION["credit_customer"])) {
                        $alertClass = $_SESSION["credit_customer"] == 1 ? "alert-success" : "alert-danger";
                        $alertMessage = $_SESSION["credit_customer"] == 1 ? "Payment Added Successfully!" : "Something went wrong, Please try again!";
                        echo "<div class='alert $alertClass alert-dismissible fade show' role='alert'>
                                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                                <strong>" . ($alertClass == "alert-success" ? "Success" : "Oops!") . "</strong> $alertMessage
                            </div>";
                        unset($_SESSION["credit_customer"]);
                    }
                    ?>
                    <!-- Customer Details -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h4>Customer Details</h4>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>SL</th>
                                            <th>Customer No</th>
                                            <th>Name</th>
                                            <th>Address</th>
                                            <th>Contact No</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $i = 1;
                                        foreach ($itemData as $row) {
                                            echo "<tr>
                                                <td>{$i}</td>
                                                <td>{$row['CustomerNo']}</td>
                                                <td>{$row['CustName']}</td>
                                                <td>{$row['CustAddress']}</td>
                                                <td>{$row['CustContact']}</td>
                                                <td>" . ($row['CustStat'] == 1 ? "Active" : "Inactive") . "</td>
                                            </tr>";
                                            $i++;
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Invoices -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h4>Customer Invoices</h4>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Invoice No</th>
                                            <th>Invoice Date</th>
                                            <th>Net Amount</th>
                                            <th>User</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $sql = "SELECT * FROM invoiceheader INNER JOIN user ON user.USID = invoiceheader.user_USID WHERE customers_CTID = '$customer_id' AND shop_SHID = '$shop_id'";
                                        $invData = $dbObj->getData($sql);
                                        $row_count = 1;
                                        foreach ($invData as $row) {
                                            $invoicePath = empty($rcptData) ? "wholesaleInvoice.php" : $rcptData[0]['ReceiptPath'];
                                            echo "<tr>
                                                <td>{$row_count}</td>
                                                <td>{$row['BillNo']}</td>
                                                <td>{$row['InvEndTime']}</td>
                                                <td>{$row['NetAmount']}</td>
                                                <td>{$row['UserName']}</td>
                                                <td><a href='../Receipts/{$invoicePath}?invoice={$row['IHID']}&invoiceList=1' class='btn btn-primary'>Wholesale Receipt</a></td>
                                            </tr>";
                                            $row_count++;
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Customer Prescriptions -->
                    <?php 
                    if($prescription==1)
                    {
                    ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h4>Customer Prescriptions</h4>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>SL</th>
                                                <th>Prescription No</th>
                                                <th>Date</th>
                                                <th>Subjective Refference</th>
                                                <th>Refraction By</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $sql = "SELECT * FROM prescriptionheader WHERE customer_CTID='$customer_id' AND shop_ID='$shop_id'";
                                            $itemData = $dbObj->getData($sql);
                                            $i=1;
                                            foreach ($itemData as $row) 
                                            {
                                                ?>
                                                <tr>
                                                    <td><?=$i?></td>
                                                    <td><?=$row["pr_no"]?></td>
                                                    <td><?=$row["date"]?></td>
                                                    <td><?=$row["pr_subjective_ref"]?></td>
                                                    <td><?=$row["pr_refraction"]?></td>
                                                    <td><a href="view-prescription.php?pres_id=<?=$row["PRHID"]?>">View Prescription</a></td>
                                                </tr>
                                                <?php
                                                $i++;
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                            </div> 
                        </div> 
                    </div> 
                    <?php
                    }
                    ?>                        

                    <!-- Customer Cheques -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h4>Customer Cheques</h4>
                            <div style="display: flex; justify-content: flex-end;">
                                <button class="btn btn-success" onclick="generatePDF()" id="generatePDF">Download
                                    PDF</button>
                            </div>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Submitted Date</th>
                                            <th>Cheque Realize Date</th>
                                            <th>Cheque No</th>
                                            <th>Bank Cheque No</th>
                                            <th>Bank</th>
                                            <th>Cheque Amount</th>
                                            <th style="text-align: right;">Cheque Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $shop_id = $_SESSION['shop_id'];
                                        $sql = "SELECT * FROM `custcheq` cc
                                                INNER JOIN custchqdetail ccd ON cc.CCQID=ccd.CCQID
                                                INNER JOIN customers c ON c.CTID=cc.cust_CTID
                                                INNER JOIN invoiceheader i ON i.IHID=cc.invoiceID
                                                WHERE cc.shop_SHID='$shop_id' AND cc.cust_CTID='$customer_id' ORDER BY cc.chq_no DESC ;";
                                        $dbObj = new DBTransactions();
                                        $invData = $dbObj->getData($sql);
                                        $i=1;
                                        $total=0;
                                        if(count($invData)>0)
                                        {
                                            foreach($invData as $row)
                                            {
                                                $total+=$row["chqAmount"];
                                                ?>
                                        <tr style="text-align: right;">
                                            <td><?=$i?></td>
                                            <td><?=$row["effectiveDate"]?></td>
                                            <td><?=$row["chqDate"]?></td>
                                            <td><?=$row["chq_no"]?></td>
                                            <td><?=$row["chqNo"]?></td>
                                            <td><?=$row["bank"]?></td>
                                            <td><?php if ($row["type"]==1){?>
                                                <span class="text-danger" style="font-weight:700;"><i
                                                        class="ti ti-outbound"
                                                        style=" display:inline-block; transform:rotate(311deg);"></i>
                                                    Issued Cheque</span><?php } else{?><span
                                                    style="font-weight:700; color:green;"> <i class="ti ti-outbound"
                                                        style=" display: inline-block; transform: rotate(133deg); "></i>
                                                    Received Cheque </span> <?php }?>
                                            </td>
                                            <td>
                                            <td><?=number_format($row["chqAmount"],2,'.')?></td>
                                        </tr>
                                        <?php 
                                                $i++;
                                            }//foreach
                                        }
                                        else
                                        {
                                            ?>
                                        <script>
                                        $(document).ready(function() {
                                            $("#generatePDF").remove();
                                        })
                                        </script>
                                        <tr>
                                            <td colspan="11" style="text-align: center;">
                                                <span class="text-danger">No results found</span>
                                            </td>
                                        </tr>
                                        <?php
                                        }
                                        
                                    ?>
                                    </tbody>
                                    <tfoot>
                                        <td colspan="8" class="text-end"><b>Total:</b></td>
                                        <td class="text-end"><?=number_format($total,2,'.')?></td>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- Customer Invoices -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h4>Customer Outstanding</h4>
                            <div style="display: flex; justify-content: flex-end;">
                                <button class="btn btn-success" onclick="pdf()" id="generatePDF">Download
                                    PDF</button>
                            </div>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Customer Name</th>
                                            <th>Outstanding Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 

                    $sql = "SELECT c.CustName, SUM(cc.CreditAmount) AS TOTALCredit, SUM(cc.DebitAmount) AS TOTALDebit FROM creditcustomer cc
                            INNER JOIN customers c ON c.CTID=cc.Customers_CTID
                            WHERE cc.Customers_CTID='$customer_id' AND cc.CreditStat='1' GROUP BY cc.Customers_CTID;";

                    $invData = $dbObj->getData($sql);
                    $totalOutstanding = 0;

                    if (!empty($invData)) {
                        foreach ($invData as $row) {
                            $outstanding = $row['TOTALCredit'] - $row['TOTALDebit'];
                            $totalOutstanding += $outstanding;

                            echo "<tr>
                                    <td>" . htmlspecialchars($row['CustName']) . "</td>
                                    <td>" . number_format((float)$outstanding, 2, '.', ',') . "</td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='2' style='text-align: center;'>No outstanding data found.</td></tr>";
                    }
                    ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td style="text-align: right; font-weight: bold;">Total Outstanding:</td>
                                            <td style="font-weight: bold;">
                                                <?php echo number_format((float)$totalOutstanding, 2, '.', ','); ?>
                                            </td>
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
    <?php include '../View/footer.php'; ?>
    <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script>
    function generatePDF() {
        window.location.href = 'chequepdf.php?cus_id=<?= $customer_id ?>';
    }
    function pdf()
    {
        window.location.href = 'outstanding-pdf.php?cus_id=<?= $customer_id ?>';
    }
    </script>
</body>
</html>