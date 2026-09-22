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
                                                    <th>Name</th>
                                                    <th>Contact</th>
                                                    <th>GRN Amount</th>
                                                    <th>GRN Credit</th>
                                                    <th>Total Settlement</th>
                                                    <th>Total Outstanding</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $dbObj = new DBTransactions();
                                                        $sql = "SELECT s.SPID, s.SupplierName, s.Contact, sum(cs.CreditAmount) as   TotalCredit, sum(cs.DebitAmount) as TotalDebit, sum(cs.Balance) as TotalBalance, sum(gh.TotalPurchasePrice) AS TotalPurchasePrice, SUM(gh.SuppPayment) AS SuppPayment, gh.GHID  FROM creditsupplier cs
                                                            LEFT JOIN grnheader gh ON gh.GHID=cs.invoice_header_id AND gh.GRNStat=2
                                                            LEFT JOIN suppliers s ON s.SPID=cs.Supplier_ID
                                                            WHERE gh.shop_SHID= '$shop_id' GROUP BY s.SPID;";

                                                $suppData = $dbObj->getData($sql);
                                                $i=1;

                                                $row_style = "";
                                                foreach ($suppData as $row)
                                                { 
                                                    $GHID=$row["GHID"];
                                                    $sql1="SELECT sum(TransferAmount) AS TotalPaid FROM `suppliertransactions` 
                                                            WHERE GRNHeader_GHID='$GHID' AND TransactionStat=1";
                                                    $totalpaid = $dbObj->getData($sql1);
                                                    $totalpaid = $totalpaid[0]["TotalPaid"];
                                                    $grnCredit=$row['TotalPurchasePrice']-$row['TotalDebit'];
                                                    $balance = floatval($grnCredit) - floatval(value: $totalpaid);
                                                    if($balance == 0)
                                                    {
                                                        $row_style = "";
                                                    }
                                                    else if($balance > 0)
                                                    {
                                                        $row_style = "style='color:red;font-weight: 700;'";
                                                    }
                                                    else
                                                    {
                                                        $row_style = "style='color:green;font-weight: 700;'";
                                                    }//else exess advance
                                                    ?>
                                                <tr <?php echo $row_style;?>>
                                                    <td><?php echo $i;?></td> 
                                                <!--Name-->
                                                <td><?php echo $row['SupplierName'];?></td> 
                                                <!--Contact-->
                                                <td><?php echo $row['Contact'];?></td> 
                                                <!--GRN Amount-->
                                                <td><?php echo number_format($row['TotalPurchasePrice'] ?? 0, 2, '.', '');?>
                                                    </td> 
                                                <!--GRN Credit-->
                                                <td><?php echo number_format($row['TotalPurchasePrice'] ?? 0, 2, '.', '');?>
                                                    </td> 
                                                <!--Total Settlement-->
                                                <td><?php echo number_format($totalpaid ?? 0, 2, '.', '');?>
                                                    </td> 
                                                <!--Total Outstanding-->
                                                <td><?php echo number_format($balance ?? 0, 2, '.', '');?>
                                                    </td> 

                                                <!--Status-->
                                                <td>
                                                        <?php 
                                                            
                                                            if($balance == 0)
                                                            {
                                                                ?>
                                                        <i class="ti ti-checks"></i>Paid
                                                        <?php 
                                                            }
                                                            else if($balance > 0)
                                                            {
                                                                ?>
                                                        <i class="ti ti-alert-octagon">Pending</i>
                                                        <?php     
                                                            }
                                                            else
                                                            {
                                                                ?>
                                                        <i class="ti ti-arrows-up">Advance</i>
                                                        <?php 
                                                            }
                                                            ?>
                                                    </td> 
                                                        <!--Action-->
                                                        <td>
                                                        <?php 
                                                            if($userType==1 || $create==1 || $edit==1 || $verify==1)
                                                            {
                                                                ?>
                                                        <a href="credit-supplier-pay.php?sup_id=<?=$row['SPID']?>"
                                                            class="btn border-primary" data-bs-toggle="tooltip"
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
                                                } ?>
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