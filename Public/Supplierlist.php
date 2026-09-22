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
<!--  Body Wrapper -->
<div class="h-100vh">
<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    <!-- Sidebar Start -->
    <?php 
    include '../View/sidebar.php';
    $feature_id=17;
    include '../Includes/viewPermission.php';
    ?>
    <!--  Sidebar End -->
    <!--  Main wrapper -->
    <div class="body-wrapper">
        <!--  Header Start -->
        <?php 
        include '../View/header.php';
        include "../View/modals/Suppliermodel.php";
        ?>
        <!--  Header End -->

        <div class="container-fluid">
            <!-- messages -->
        <div class="container">
        <?php 
            if(isset($_SESSION['supplier_update']))
            {
                if($_SESSION['supplier_update'] == 0)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Please enter <strong>Distributer Name.</strong>
                    </div>
                    <?php 
                }//no entry
                else if($_SESSION['supplier_update'] == 1)
                {
                    ?>
                        <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            Please enter <strong>Supplier Name</strong>.
                        </div>
                        <?php
                }//duplicate entry
                else if($_SESSION['supplier_update'] == 2)
                {
                    ?>
                    <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Please enter Supplier <strong>Contact</strong>
                    </div>
                    <?php
                }//save success
                else if($_SESSION['supplier_update'] == 3)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Supplier <strong>created </strong>successfully!
                    </div>
                    <?php
                }//update success
                else if($_SESSION['supplier_update'] == 4)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Supplier <strong>updated </strong>successfully!
                    </div>
                    <?php
                }//update success
                else if($_SESSION['supplier_update'] == 5)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Supplier <strong>Deleted </strong>successfully!
                    </div>
                    <?php
                }//update success
                else if($_SESSION['supplier_update'] == 6)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Supplier cannot <strong>Delete, </strong>there are items purchased from this supplier.
                    </div>
                    <?php
                }//update success
                else
                {
                    ?>
                    <div class="alert alert-danger">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Oops! </strong>something went wrong!
                    </div>
                    <?php 
                }//else
                unset($_SESSION['supplier_update']);
            }//session set
            ?>

        </div>
            
            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title" style="margin-top: 0px;">
                        Suppliers
                        <?php 
                        if($userType==1 || $create==1)
                        {
                            ?>
                            <button class="btn btn-primary rounded-pill float-end" id="btn_open_supplier"><small>Add Supplier</small></button>
                            <?php
                        }
                        ?>
                    </h5> 
                </div>
                <div class="card-body">
               
                <div class="container-fluid">
                    <table class="table table-hover" id="tbl_supplier">
                        <thead>
                            <tr>
                                <th>Supplier No</th>
                                <th>Distributer</th>
                                <th>Supplier Name</th>
                                <th>Contact</th>
                                <th>StatUS</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                            $supObj = new Supplier();
                            $supData = $supObj->getAllSuppliers($shop_id);
                            foreach($supData as $row)
                            {
                                ?>
                                <tr data-id="<?php echo $row['SPID'];?>">
                                    <td><?php echo $row['SupplierNo'];?></td>
                                    <td><?php echo $row['Distributer'];?></td>
                                    <td><?php echo $row['SupplierName'];?></td>
                                    <td><?php echo $row['Contact'];?></td>
                                    <td>
                                        <?php
                                        $sup_stat = $row['SupplierStat'];
                                        if($sup_stat == '1')
                                        {
                                            ?>
                                            <p class="text-success" style="font-weight: bold;"><i class="ti ti-check"></i>Active</p>
                                            <?php 
                                        }
                                        else
                                        {
                                            ?>
                                            <p class="text-warning" style="font-weight: bold;"><i class="ti ti-x"></i>Inactive</p>
                                            <?php
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if($userType==1 || $edit==1)
                                        {
                                            ?>
                                            <button type="button" class="btn border border-primary btn_edit_supplier"><i class="ti ti-edit"></i></button>
                                            <?php
                                        }
                                        if($userType==1 || $delete==1)
                                        {
                                            ?>
                                            <button type="button" class="btn border border-danger btn_delete_supplier"><i class="ti ti-x"></i></button>
                                            <?php
                                        }
                                        ?>
                                    </td>
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
<!--  Body Wrapper End -->

    <!-- footer Start  -->
    <?php include '../View/footer.php';?> 
    <!-- footer End  -->

    <script src="../Assets/jquery/Supplier.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>

    <script>
        $(document).ready(function(){
            $("#tbl_supplier").DataTable({
                paging: true,
                lengthChange: true,
                searching: true,
                // pageLength: 50,
            });
        });
    </script>
</body>
</html>