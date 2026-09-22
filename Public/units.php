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
    $feature_id=22;
    include '../Includes/viewPermission.php';
    ?>
    <!--  Sidebar End -->
    <!--  Main wrapper -->
    <div class="body-wrapper">
        <!--  Header Start -->
        <?php 
        include '../View/header.php';
        include "../View/modals/add-units.php";
        ?>
        <!--  Header End -->

        <div class="container-fluid">
            <!-- messages -->
        <div class="container">
        <?php 
            if(isset($_SESSION['unit_update']))
            {
                if($_SESSION['unit_update'] == 0)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Unit name empty.</strong>
                    </div>
                    <?php 
                }//no entry
                else if($_SESSION['unit_update'] == 1)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>short name empty.</strong>
                    </div>
                    <?php
                }//duplicate entry
                else if($_SESSION['unit_update'] == 2)
                {
                    ?>
                    <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Unit already exists.</strong>
                    </div>
                    <?php
                }//save success
                else if($_SESSION['unit_update'] == 3)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Unit saved </strong>successfully!
                    </div>
                    <?php
                }//save success
                else if($_SESSION['unit_update'] == 4)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Unit updated </strong>successfully!
                    </div>
                    <?php
                }//save success
                else if($_SESSION['unit_update'] == 5)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Cannot <strong>Delete unit </strong>from system.
                    </div>
                    <?php
                }//delete failed
                else if($_SESSION['unit_update'] == 6)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Unit <strong>Deleted </strong>successfully!
                    </div>
                    <?php
                }//delete success
                else
                {
                    ?>
                    
                    <div class="alert alert-danger">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Oops! </strong>something went wrong!
                    </div>

                    <?php 
                }//else
                unset($_SESSION['unit_update']);
            }//session set
            ?>

        </div>
            
            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title fw-semibold">
                        Units
                        <?php 
                        if($userType==1 || $create==1)
                        {
                            ?>
                            <button class="btn btn-primary rounded-pill float-end" id="btn_open_unit">Add Units</button>
                            <?php
                        }
                        ?>
                    </h5>
                </div>
                <div class="card-body">
                <div class="container-fluid">
                    <table class="table table-hover" id="tbl_unit">
                        <thead>
                        <tr>
                            <th>No</th>
                            <th>Unit Name</th>
                            <th>Short Name</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>

                        <?php 
                            $dbObj = new DBTransactions();
                            //get company stat
                            $sql = "SELECT * FROM shop
                            INNER JOIN company ON company.CMID = shop.Company_CMID
                            WHERE SHID = ".$shop_id.";";

                            $shopData = $dbObj->getData($sql);
                            $multi_category = floatval($shopData[0]['is_multicategory']);
                            $company_id = floatval($shopData[0]['CMID']);
                            $unitObj = new Unit();
                            $unitData = $unitObj->getAllUnits($shop_id,$multi_category,$company_id);
                            $count = 0;
                            foreach($unitData as $row)
                            {
                                $count += 1;
                                ?>
                                <tr data-id="<?php echo $row['UNID'];?>">
                                    <td><?php echo $count;?></td>
                                    <td><?php echo $row['UnitName'];?></td>
                                    <td><?php echo $row['ShortName'];?></td>
                                    <td>
                                        <?php 
                                        if($userType==1 || $edit==1)
                                        {
                                            ?>
                                            <button type="button" class="btn border border-primary text-primary btn_edit_unit"><i class="ti ti-edit"></i></button>
                                            <?php
                                        }
                                        if($userType==1 || $delete==1)
                                        {
                                            ?>
                                            <button type="button" class="btn border border-danger text-danger btn_delete_unit"><i class="ti ti-x"></i></button>
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
    <script src="../Assets/jquery/unit.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>

    <script>
         $(document).ready(function(){
            $("#tbl_unit").DataTable({
                paging: true,
                lengthChange: true,
                searching: true,
                // pageLength: 50,
            });
        });
    </script>
</body>
</html>