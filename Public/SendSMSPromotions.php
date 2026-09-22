<?php 

include "../Includes/includes.php";
include '../Includes/authcheck.php';

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
    </style>
</head>

<body> 
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
                <!-- Header End -->

                <div class="container-fluid">
                    <h5 class="card-title fw-semibold mb-4">SMS Promotion</h5>
                    
                    <div class="card">
                        <div class="card-body">
                            <!-- SMS Form -->
                            <form action="../Controller/PromotionSMSController.php" method="POST">

                                <div class="mb-3">
                                    <label for="cmb_customer" class="form-label">Customer Name</label><br>
                                        <select name="cmb_customer" id="cmb_customer_SMS" class="form-select"></select>
                                    <input type="hidden" name="customer_id" id="customer_id" value="1">    
                                </div>

                                <div class="mb-3">
                                    <label for="customerPhone" class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" id="customerPhone" name="customerPhone" 
                                           placeholder="Enter phone number" required readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="smsContent" class="form-label">Message</label>
                                    <textarea class="form-control" name="smsContent" id="smsContent" 
                                              rows="10" placeholder="Type your SMS message here..." required></textarea>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" name="btnSMS" class="btn btn-primary">Send SMS</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <?php 
                    if (isset($_SESSION['message'])) { ?>
                        <div class="alert alert-info mt-3">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" name="btnSMS" aria-label="Close"></button>
                            <?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../View/footer.php'; ?> 

    <!-- Scripts -->

    <!-- <script src="../Assets/libs/jquery/dist/jquery.min.js"></script> -->
    <script src="../Assets/jquery/SMS_Promotion.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
</body>
</html>
