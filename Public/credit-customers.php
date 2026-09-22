<?php 
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

include "../Includes/includes.php";
include '../Includes/authcheck.php';

// Fetch unique customer names for the dropdown
$credit = new credit_customer();
$customerNames = $credit->getCustomerNames(); // Method to fetch unique customer names

// first look 
$shopObj = new Shop();
$shop_id = $_SESSION['shop_id'];
$shopData=$shopObj->getOneShop($shop_id);
// first look 
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
        span.select2.select2-container.select2-container--default.select2-container--below.select2-container--open {
            width: 100% !important;
        }
    </style>
</head>

<body>
    <?php include '../View/modals/SysFeatures.php'; ?>
    <!-- Body Wrapper -->
    <div class="h-100vh">
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
            data-sidebar-position="fixed" data-header-position="fixed">
            <!-- Sidebar Start -->
            <?php 
                include '../View/sidebar.php';
                $feature_id = 9;
                include '../Includes/viewPermission.php';
            ?>
            <!-- Sidebar End -->
            
            <!-- Main wrapper -->
            <div class="body-wrapper">
                <!-- Header Start -->
                <?php include '../View/header.php'; ?>
                

                <div class="container-fluid">
                    <h5 class="card-title fw-semibold mb-4">Customer Due Payment</h5>
                    
                    <?php 
                    if (isset($_SESSION["credit"]) && $_SESSION["credit"] != 1) { ?>
                        <div class="alert alert-success">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>Successfully</strong>Updated
                        </div>
                    <?php } 
                    unset($_SESSION["credit"]);
                    ?>
                    
                    <br>

                    <div class="card">
                        <div class="card-body">
                            <!-- Filter Form -->
                            <form id="filterForm" action="" method="get">
                                <div class="row">
                                      <!-- Credit Type Filter -->
                                    <div class="col-md-4">
                                        <label for="creditType" class="form-label">Credit Type</label>
                                        <select name="creditType" id="creditType" class="form-select">
                                            <option value="0">Select Type</option>
                                            <option value="1" <?= isset($_GET["creditType"]) && $_GET["creditType"] == 1 ? "selected" : "" ?>>Over Limit</option>
                                            <option value="2" <?= isset($_GET["creditType"]) && $_GET["creditType"] == 2 ? "selected" : "" ?>>Excess/Advance</option>
                                            <option value="3" <?= isset($_GET["creditType"]) && $_GET["creditType"] == 3 ? "selected" : "" ?>>Full Paid</option>
                                            <option value="4" <?= isset($_GET["creditType"]) && $_GET["creditType"] == 4 ? "selected" : "" ?>>On Limit</option>
                                        </select>
                                    </div>

                                    <!-- Customer Name Filter -->
                                    <div class="col-md-4">
                                        <label for="customerName" class="form-label">Customer Name</label><br>
                                        <select name="customerCTID" id="customerCTID" class="form-select">
                                            <option value="">Select Customer</option>
                                        </select>
                                    </div>

                                    <!-- Search Button -->
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary ">Search</button>
                                    </div>
                                </div>
                            </form>
                            <!-- first look  -->
                            <div class="d-none">
                                <input type="hidden" name="" id="shop_name" value="<?=$shopData[0]["ShopName"]?>">
                                <input type="hidden" name="" id="shop_address_one" value="<?=$shopData[0]["AddressLineOne"]?>">
                                <input type="hidden" name="" id="shop_address_two" value="<?=$shopData[0]["AddressLineTwo"]?>">
                                <input type="hidden" name="" id="shop_city" value="<?=$shopData[0]["City"]?>">
                                <input type="hidden" name="" id="shop_number" value="<?=$shopData[0]["ShopNo"]?>">
                            </div>
                            <!-- first look  -->
                            <!-- Table -->
                            <div class="table-responsive mt-3">
                                <table class="table search-table align-middle text-nowrap" id="tbl_active_store">
                                    <thead class="header-item">
                                        <tr>
                                            <th>SL</th>
                                            <th>Customer No</th>
                                            <th>Name</th>
                                            <th>Address</th>
                                            <th>Contact</th>
                                            <th>Type</th>
                                            <th>Credit Limit</th>
                                            <th>Total Payable Balance</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $creditType = isset($_GET['creditType']) ? trim($_GET['creditType']) : null;
                                        $customerCTID = isset($_GET['customerCTID']) ? trim($_GET['customerCTID']) : null;
                                        $ModuleName = $credit->select_credit_customer($customerCTID, $creditType);
                                        $i = 1;
                                        $total=0;
                                        $balance=0;

                                        if (empty($ModuleName)) {
                                            // first look
                                            // echo "<tr><td colspan='9'>No records found</td></tr>";
                                            // first look
                                        } else {
                                            foreach ($ModuleName as $Module) {
                                                $balance  = $Module["total_credit"] - $Module["total_debit"];
                                                $total += ($Module["total_credit"] - $Module["total_debit"]);

                                                $class = ($balance > $Module['MaxCreditAmount']) ? "class='text-danger'" : 
                                                        ($balance <= 0 ? "style='color:green;font-weight:700;'" : "");
                                                ?>
                                                <tr <?= $class ?>>
                                                    <td><?= $i ?></td>
                                                    <td><?= htmlspecialchars($Module['CustomerNo']) ?></td>
                                                    <td><?= htmlspecialchars($Module['CustName']) ?></td>
                                                    <td><?= htmlspecialchars($Module['CustAddress']) ?></td>
                                                    <td><?= htmlspecialchars($Module['CustContact']) ?></td>
                                                    <td><?= $balance > $Module['MaxCreditAmount'] ? "Over The Limit" : ($balance <= 0 ? "Excess/Advance" : "On Limit") ?></td>
                                                    <td><?= htmlspecialchars($Module['MaxCreditAmount']) ?></td>
                                                    <td align="right"><?= number_format($Module['BALANCE'], 2, '.', ',') ?></td>
                                                    <?php

                                                        ?>
                                                    <td>
                                                        <a href="credit-pay.php?cus_id=<?= $Module['CTID'] ?>" class="btn btn-primary" 
                                                        data-bs-toggle="tooltip" title="Repayment"> 
                                                            <i class="ti ti-cash-banknote"></i> 
                                                        </a>
                                                        <button class="btn btn-warning send-sms-btn" 
                                                                data-customer-id="<?= $Module['CTID'] ?>" 
                                                                data-customer-phone="<?= htmlspecialchars($Module['CustContact']) ?>" 
                                                                data-customer-name="<?= htmlspecialchars($Module['CustName']) ?>" 
                                                                data-customer-balance="<?= number_format($total, 2, '.', ',') ?>"
                                                                data-bs-toggle="tooltip" 
                                                                title="Send SMS Reminder">
                                                            <i class="ti ti-message"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php
                                                $i++;
                                            }
                                        }
                                        ?>
                                    </tbody>
                                    <tr><td style='text-align: center; font-weight: bold;' colspan="7">Total</td> 
                                    <td style='text-align: center; font-weight: bold;'><?= number_format((float)$total, 2, '.', ',') ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../View/footer.php'; ?> 

    <!-- Scripts -->
    <script src="../Assets/jquery/creditcustomer.js"></script>

    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>

    <!-- Custom JS -->
    <script>
        $(document).ready(function(){
            var shop_name = $("#shop_name").val();
            var shop_address_one = $('#shop_address_one').val();
            var shop_address_two = $('#shop_address_two').val();
            var shop_city = $('#shop_city').val();
            var shop_number = $('#shop_number').val();
            // first look 
            var reportname="Customer Credit Report";
            // first look 

            $("#tbl_active_store").DataTable({
               
                paging: true,
                lengthChange: true,
                searching: true,
                pageLength: 10,

                layout:{
                    topStart:{
                        buttons:[
                            //====================== PDF
                            {
                                extend: 'pdf',
                                customize: function (doc){
                                    doc.content.splice(0,1,{
                                        text: [
                                            {text: shop_name + "\n", bold: true, fontSize: 16},
                                            {text: shop_address_one + ",\n", bold: true, fontSize: 12},
                                            {text: shop_address_two + ",\n", bold: true, fontSize: 12},
                                            {text: shop_number + ",\n", bold: true, fontSize: 12},
                                            {text: reportname + ",\n", bold: true, fontSize: 14}
                                        ]
                                    }) 
                                },
                                download: 'open'
                            },
                            
                            //===================== excel
                            {
                                extend: 'excel',
                                title: reportname,
                            },
    
                        ]
                    } 
            }
        })
    });

        $(document).ready(function () {
            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    
        $(document).on('click', '.send-sms-btn', function () {
            const customerId = $(this).data('customer-id');
            const customerPhone = $(this).data('customer-phone');
            const customerName = $(this).data('customer-name');
            const customerBalance = $(this).data('customer-balance');

            if (confirm(`Send SMS reminder to ${customerName}?`)) {
                $.ajax({
                    url: '../Controller/sendreminderSMSController.php',
                    type: 'POST',
                    data: {
                        customerId: customerId,
                        customerPhone: customerPhone,
                        customerName: customerName,
                        customerBalance: customerBalance,
                    },
                    success: function (response) {
                        const res = JSON.parse(response);
                        if (res.status === 'success') {
                            alert(res.message);
                        } else {
                            alert(res.message);
                        }
                    },
                    error: function () {
                        alert('Error occurred while sending SMS.');
                    }
                });
            }
        });
    </script>
</body>
</html>
