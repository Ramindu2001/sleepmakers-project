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
        include "../View/modals/add-sections.php";
        ?>
        <!--  Header End -->

        <div class="container-fluid">
            <!-- messages -->
        <div class="container">
        <?php 
            if(isset($_SESSION['section_update']))
            {
                if($_SESSION['section_update'] == 0)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Section name empty.</strong> Please enter section name.
                    </div>
                    <?php 
                }//no entry
                else if($_SESSION['section_update'] == 1)
                {
                    ?>
                    <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Section name already exists</strong> in this shop.
                    </div>
                    <?php
                }//duplicate entry
                else if($_SESSION['section_update'] == 2)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Section saved</strong>successfully!
                    </div>
                    <?php
                }//save success
                else if($_SESSION['section_update'] == 3)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Section updated</strong>successfully!
                    </div>
                    <?php
                }//update success

                else if($_SESSION['section_update'] == 4)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Section<strong> deleted</strong> successfully!
                    </div>
                    <?php
                }//delete success

                else if($_SESSION['section_update'] == 5)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Can't Delete this<strong> section</strong> from the system.
                    </div>
                    <?php
                }//delete failed

                else
                {
                    ?>
                    <div class="alert alert-danger">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Oops!</strong>something went wrong!
                    </div>
                    <?php 
                }//else
                unset($_SESSION['section_update']);
            }//session set
            ?>
        </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title fw-semibold " style="margin-top: 0px;">
                        Shop Sections
                        <?php 
                        if($userType==1 || $create==1)
                        {
                            ?>
                            <button class="btn btn-primary rounded-pill float-end" id="btn_open_section">Add Sections</button>    
                            <?php
                        }
                        ?>
                    </h5>
                </div>
                <div class="card-body">
                    
                <div class="container-fluid">
                    <table class="table table-hover" id="tbl_section">
                        <thead>
                            <tr>
                                <td style="display: none;">0</td>
                                <th>No</th>
                                <th>Section No</th>
                                <th>Section Name</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                            $secObj = new Section();
                            $secData = $secObj->getAllSections($shop_id);
                            $count = 0;
                            foreach($secData as $row)
                            {
                                $count += 1;
                                ?>
                                <tr data-id="<?php echo $row['SEID'];?>">
                                    <td style="display: none;"><?php echo $row['SEID'];?></td>
                                    <td><?php echo $count;?></td>
                                    <td><?php echo $row['SectionNo'];?></td>
                                    <td><?php echo $row['SectionName'];?></td>
                                    <td>
                                        <?php 
                                        if($userType==1 || $edit==1)
                                        {
                                            ?>
                                            <button type="button" id="btn_section_<?php echo $row['SEID']?>" class="btn border border-primary btn_edit_section"><i class="ti ti-edit"></i></button>
                                            <?php
                                        }
                                        if($userType==1 || $delete==1)
                                        {
                                            ?>
                                            <button type="button" id="btn_section_delete_<?php echo $row['SEID']?>" class="btn border border-danger btn_delete_section"><i class="ti ti-x"></i></button>
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
            $("#tbl_section").DataTable({
                paging: true,
                lengthChange: true,
                searching: true,
                // pageLength: 50,
            });
        });
    </script>
</body>
</html>