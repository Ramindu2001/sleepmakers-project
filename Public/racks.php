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
    $feature_id=21;
    include '../Includes/viewPermission.php';
    ?>
    <!--  Sidebar End -->
    <!--  Main wrapper -->
    <div class="body-wrapper">
        <!--  Header Start -->
        <?php 
        include '../View/header.php';
        include "../View/modals/add-racks.php";
        ?>
        <!--  Header End -->

        <div class="container-fluid">
            <!-- messages -->
        <div class="container">
        <?php 
            if(isset($_SESSION['rack_update']))
            {
                if($_SESSION['rack_update'] == 0)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>No Section selected</strong>
                    </div>
                    <?php 
                }//no entry
                else if($_SESSION['rack_update'] == 1)
                {
                    ?>
                    <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Rack name empty.</strong>
                    </div>
                    <?php
                }//duplicate entry
                else if($_SESSION['rack_update'] == 2)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Rack Name already exists</strong>
                    </div>
                    <?php
                }//save success
                else if($_SESSION['rack_update'] == 3)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Rack saved </strong>successfully!
                    </div>
                    <?php
                }//update success
                else if($_SESSION['rack_update'] == 4)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Rack updated </strong>successfully!
                    </div>
                    <?php
                }//update success

                else if($_SESSION['rack_update'] == 5)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Rack <strong> deleted </strong>successfully!
                    </div>
                    <?php
                }//update success

                else if($_SESSION['rack_update'] == 6)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        This <strong> Rack </strong>can't delete from system.
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
                unset($_SESSION['rack_update']);
            }//session set
            ?>

        </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">
                        Racks
                        <?php 
                        if($userType==1 || $create==1)
                        {
                            ?>
                            <button class="btn btn-primary rounded-pill float-end" id="btn_open_racks"><small>Add Rack</small></button>
                            <?php
                        }
                        ?>
                    </h5>
                </div>

                <div class="card-body">
                    
                <div class="container-fluid">
                    <table class="table table-hover" id="tbl_racks">
                        <thead>
                            <tr>
                                <th>Rack No</th>
                                <th>Section</th>
                                <th>Rack Name</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                       <tbody>
                        <?php 
                            $rackObj = new Section();
                            $rackData = $rackObj->getAllRack($shop_id);
                            foreach($rackData as $row)
                            {
                                ?>
                                <tr data-id="<?php echo $row['RKID'];?>">
                                    <td><?php echo $row['RackNo'];?></td>
                                    <td><?php echo $row['SectionName'];?></td>
                                    <td><?php echo $row['RackName'];?></td>
                                    <td>
                                        <?php 
                                        if($userType==1 || $edit==1)
                                        {
                                            ?>
                                            <button type="button" id="btn_rack_<?php echo $row['RKID']?>" class="btn border border-primary text-primary btn_edit_rack"><i class="ti ti-edit"></i></button>
                                            <?php
                                        }
                                        if($userType==1 || $delete==1)
                                        {
                                            ?>
                                            <button type="button" id="btn_rack_<?php echo $row['RKID']?>" class="btn border border-danger text-danger btn_delete_rack"><i class="ti ti-x"></i></button>
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

    <script src="../Assets/jquery/section.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>

    <script>
        $(document).ready(function(){
            $("#tbl_racks").DataTable({
                paging: true,
                lengthChange: true,
                searching: true,
                // pageLength: 50,
            });
        });
    </script>

</body>
</html>