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
    ?>
</head>

<body>
    <!-- Body Wrapper -->
    <div class="h-100vh">
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
            data-sidebar-position="fixed" data-header-position="fixed">
            <!-- Sidebar Start -->
            <?php
            include '../View/sidebar.php';
            $feature_id=5;
            include '../Includes/viewPermission.php';
            ?>
            <!-- Sidebar End -->
            <!-- Main wrapper -->
            <div class="body-wrapper">
                <!-- Header Start -->
                <?php
                include '../View/header.php';
                include '../View/modals/supplier-return.php';
                ?>
                <!-- Header End -->

                <div class="container-fluid">
                    <!-- messages -->
                    <div class="container">
                        <?php
                        if (isset($_SESSION['return_update'])) {
                            switch ($_SESSION['return_update']) {
                                case 0:
                                    echo '<div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                    Please select an <strong>Effective Date.</strong> 
                                  </div>';
                                    break;
                                case 1:
                                    echo '<div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                    New return created <strong>Product Invoice No.</strong>
                                  </div>';
                                    break;
                                case 2:
                                    echo '<div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <strong>Supplier return created </strong>successfully!
                                  </div>';
                                    break;
                                case 3:
                                    echo '<div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                    Please select a <strong>GRN</strong> to add items
                                  </div>';
                                    break;
                                default:
                                    echo '<div class="alert alert-danger">
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <strong>Oops! </strong>something went wrong!
                                  </div>';
                                    break;
                            }
                            unset($_SESSION['return_update']);
                        }
                        ?>
                    </div>

                
                    <div class="card">
                        <div class="card-header">
                        <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">
                            Supplier return list
                            <?php 
                            if($userType==1)
                            {
                                ?>
                                <button class="btn btn-primary float-end" id="btn_open_return">Add New Return Note</button>
                                <?php
                            }
                            else
                            {
                                if($create==1)
                                {
                                    ?>
                                    <button class="btn btn-primary float-end" id="btn_open_return">Add New Return Note</button>
                                    <?php
                                }
                            }
                            ?>
                            
                        </h5>
                        </div>
                        <div class="card-body">                           
                            <div class="container-fluid table-responsive">
                                <table class="table table-hover" id="tbl_SupplierReturn">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Date</th>
                                            <th>Return Amount</th>
                                            <th>Added By</th>
                                            <th>Supplier</th>
                                            <th>Status</th>
                                            <th>Shop</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $returnObj = new SupplierReturn();
                                        $returnData = $returnObj->getAllReturns($shop_id);

                                        // Debugging: Log the returned data

                                        if (!empty($returnData)) {
                                            foreach ($returnData as $row) {
                                                ?>
                                                <tr>
                                                    <td><?php echo $row['ReturnNo']?></td>
                                                    <td><?php echo $row['EffectiveDate']?></td>
                                                    <td><?php echo $row['ReturnAmount']?></td>
                                                    <td><?php echo $row['UserName']?></td>
                                                    <td><?php echo $row['SupplierName']?></td>                                                   
                                                    <td>
                                                        <?php 
                                                        $grn_stat = $row['ReturnStat'];
                                                        if($grn_stat == '0')
                                                        {
                                                            ?>
                                                            <p class="text-center text-primary" style="font-weight: 700;"><i class="ti ti-player-pause"></i> Hold</p>
                                                            <?php
                                                        } //on hold
                                                        else if($grn_stat == '1')
                                                        {
                                                            ?>
                                                            <p class="text-center text-warning" style="font-weight: 700;"><i class="ti ti-refresh"></i> Pending</p>
                                                            <?php
                                                        } //pending
                                                        else if($grn_stat == '2')
                                                        {
                                                            ?>
                                                            <p class="text-center" style="color:green;font-weight: 700;"><i class="ti ti-checks"></i> Verified</p>
                                                            <?php
                                                        } //verified
                                                        else if($grn_stat == '3')
                                                        {
                                                            ?>
                                                            <p class="text-center text-danger" style="font-weight: 700;"><i class="ti ti-circle-x"></i> Cancelled</p>
                                                            <?php
                                                        } //cancled
                                                        else
                                                        {
                                                            ?>
                                                            <p class="text-center text-danger" style="font-weight: 700;"><i class="ti ti-alert-octagon"></i> Undefined</p>
                                                            <?php 
                                                        } //added
                                                        ?>
                                                    </td>
                                                    <td><?php echo $row['shopName']?></td>
                                                    <td>
                                                        <?php
                                                        if($userType==1)
                                                        {
                                                            ?>
                                                            <!-- Actions -->
                                                            <form action="supplier-return-detail.php" method="post" style="display:inline;">
                                                                <input type="hidden" name="return_header_id"
                                                                    value="<?php echo htmlspecialchars($row['ReturnNo']); ?>">
                                                                <input type="hidden" name="return_header_stat"
                                                                    value="<?php echo htmlspecialchars($row['ReturnStat']); ?>">
                                                                <button name="btn_goto_details" class="btn border border-success"><i
                                                                        class="ti ti-plus"></i></button>
                                                            </form>

                                                            <form action="Supplier_Rtn_Report.php" method="post" style="display:inline;">
                                                                <input type="hidden" name="srn_header_id" value="<?php echo $row['SRID'];?>">
                                                                <input type="hidden" name="srn_header_stat" value="<?php echo $row['SRID'];?>">
                                                                <button type="submit" class="btn border border-primary"><i class="ti ti-printer"></i></button>
                                                            </form>
                                                            <?php
                                                        }
                                                        else
                                                        {
                                                            if($edit==1 || $verify==4)
                                                            {
                                                                ?>
                                                                <!-- Actions -->
                                                                <form action="supplier-return-detail.php" method="post" style="display:inline;">
                                                                    <input type="hidden" name="return_header_id"
                                                                        value="<?php echo htmlspecialchars($row['ReturnNo']); ?>">
                                                                    <input type="hidden" name="return_header_stat"
                                                                        value="<?php echo htmlspecialchars($row['ReturnStat']); ?>">
                                                                    <button name="btn_goto_details" class="btn border border-success"><i
                                                                            class="ti ti-plus"></i></button>
                                                                </form>
                                                                <?php
                                                            }
                                                            if($print==1)
                                                            {
                                                                ?>
                                                                <form action="Supplier_Rtn_Report.php" method="post" style="display:inline;">
                                                                    <input type="hidden" name="srn_header_id" value="<?php echo $row['SRID'];?>">
                                                                    <input type="hidden" name="srn_header_stat" value="<?php echo $row['SRID'];?>">
                                                                    <button type="submit" class="btn border border-primary"><i class="ti ti-printer"></i></button>
                                                                </form>
                                                                <?php
                                                            }
                                                        }                                                        
                                                        ?>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                        }
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
    <!-- Body Wrapper End -->

    <!-- Footer Start -->
    <?php include '../View/footer.php'; ?>
    <!-- Footer End -->

    <script src="../Assets/jquery/SupplierReturn.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>

    <script>
        $(document).ready(function(){
            $("#tbl_SupplierReturn").DataTable({
                paging: true,
                lengthChange: true,
                searching: true,
                // pageLength: 50,
            });//data table
        });
    </script>                                  

</body>
</html>