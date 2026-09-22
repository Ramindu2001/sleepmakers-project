<?php 
include "../Includes/includes.php";
include '../Includes/authcheck.php';

$shop_id = $_SESSION['shop_id'];
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

    <?php 

    include '../View/modals/SysFeatures.php';
?>

    <!--  Body Wrapper -->
    <div class="h-100vh">
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
            data-sidebar-position="fixed" data-header-position="fixed">
            <!-- Sidebar Start -->
            <?php 
             include '../View/sidebar.php';
             $feature_id = 66;
             include '../Includes/viewPermission.php';
            ?>
            <!--  Sidebar End -->
            <!--  Main wrapper -->
            <div class="body-wrapper">
                <!--  Header Start -->
                <?php 
                    include '../View/header.php';
                ?>
                <!--  Header End -->

                <div class="container-fluid">
                    <h5 class="card-title fw-semibold mb-4">Credit Supplier List</h5>
                    <?php 
                    if(isset($_SESSION["credit"]))
                    {
                        if($_SESSION["credit"]==1)
                        {

                        }
                        else
                        {
                            ?>
                    <div class="alert alert-danger">
                        <button type="button" class="btn-close " data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong> Oops! </strong>something went wrong!
                    </div>
                    <?php

                        }
                        unset($_SESSION["credit"]);
                    }
                    ?>
                    <!-- <button type="button" class="btn btn-primary rounded-pill ml-1 mb-2" id="btn_Add_SysFeature_modal" data-bs-dismiss="modal">Add New Feature</button> -->
                    <br>

                    <div class="card">
                        <div class="card-body">

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table class="table search-table align-middle text-nowrap"
                                            id="tbl_active_store">
                                            <thead class="header-item">
                                                <tr>
                                                    <th>SL</th>
                                                    <th>Supplier No</th>
                                                    <th>Supplier Name</th>
                                                    <th>Contact</th>
                                                    <th>Total Credit</th>
                                                    <th>Total Settlement</th>
                                                    <th>Total Outstanding</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $dbObj = new DBTransactions();
                                                        $sql = "SELECT s.*, SUM(cs.CreditAmount) AS total_credit, SUM(cs.DebitAmount) AS total_debit, SUM(cs.CreditAmount) - SUM(cs.DebitAmount) AS BALANCE  FROM suppliers s 
                                                        INNER JOIN  creditsupplier cs ON s.SPID=cs.Supplier_ID
                                                        WHERE cs.shop_SHID='$shop_id' AND cs.CreditStat=1 
                                                        GROUP by cs.Supplier_ID ;";

                                                $suppData = $dbObj->getData($sql);
                                                $i=1;

                                                $row_style = "";
                                                $count=count($suppData);
                                                if($count > 0)
                                                {
                                                    foreach ($suppData as $row)
                                                    { 
                                                        $balance = $row["total_debit"] - $row["total_credit"];
                                                        ?>
                                                            <tr>
                                                                <td><?=$i?></td> 
                                                                <td><?=$row["SupplierNo"]?></td>
                                                                <td><?=$row["SupplierName"]?></td>
                                                                <td><?=$row["Contact"]?></td>
                                                                <td><?= number_format($row["total_debit"], 2, '.', ',') ?></td>
                                                                <td><?= number_format($row["total_credit"], 2, '.', ',') ?></td>
                                                                <td><?= number_format($balance, 2, '.', ',') ?></td>
                                                                <td>
                                                                <?php 
                                                                    if($userType==1 || $create==1 || $edit==1 || $verify==1)
                                                                    {
                                                                        ?>
                                                                        <a href="credit-supplier-pay.php?sup_id=<?=$row['SPID']?>"
                                                                            class="btn btn-primary" data-bs-toggle="tooltip"
                                                                            data-bs-placement="top" title="Repayment"
                                                                            data-bs-original-title="Repayment" aria-label="Repayment">
                                                                            <i class="ti ti-cash-banknote"></i>
                                                                        </a>
                                                                        <?php 
                                                                    }//permision
                                                                    ?>
                                                                </td>
                                                            </tr>                                                        
                                                        <?php 
                                                        $i++; 
                                                    }
                                                }
                                                else
                                                {
                                                    ?>
                                                    <td colspan="9"> <span class="text-danger text-center">No Results Found</span></td>
                                                    <?php
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
        </div>
    </div>
    <!-- footer Start  -->
    <?php include '../View/footer.php';?>
    <!-- footer End  -->
    <script>
    $(function() {
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
    </script>
    <script src="../Assets/jquery/SysModule.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script>
    $(document).ready(function() {
        $("#tbl_active_store").DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            // pageLength: 50,
        }); //data table
    });
    </script>
</body>

</html>