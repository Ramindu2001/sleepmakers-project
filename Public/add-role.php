<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';
require_once '../Includes/super_admin.php';
super_admin_page(); //system admin only, decided before any of the page is sent
?>
<!doctype html>
<html lang="en">

<head>
  <?php
  include '../View/head.php';
  // include '../View/loader.php';
  $user_module=new UserRole();
  $user_modules=$user_module->select_modules();
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
                  <?php 
                  if (isset($_SESSION['role_status'])) 
                  {
                    if ($_SESSION['role_status']==1) 
                    {
                      ?>
                      <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Error - </strong> Role already available, Please try again!
                      </div>
                      <?php
                    }
                    else if ($_SESSION['role_status']==2) 
                    {
                      ?>
                      <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Role Added -</strong> Successfully
                      </div>
                      <?php
                    }
                    else
                    {
                      ?>
                      <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Error - </strong> Something went wrong, Please try again!
                      </div>
                      <?php
                    }
                    unset($_SESSION['role_status']);
                  }
                  ?>
                  
        <!--  Row 1 -->
        <div class="row">
          <div class="col-lg-12">
            <div class="card w-100">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-4">Add New User Role</h5>
                    <div class="row">
                        <div class="col-md-12">
                            <form action="../Controller/userrolecontrol.php" method="POST" class="">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                <div class="mb-3">
                                    <lable class="form-label mb-3">Role Name <span class="text-danger">*</span></lable><br>
                                    <input type="text" class="form-control" placeholder="Eg: Manager" name="role_name" required>
                                </div>

                                <?php 
                                foreach ($user_modules as $row_modules) 
                                {
                                    ?>
                                    <h5 class="card-title "><?=$row_modules['ModuleName']?></h5>
                                    <input type="hidden" name="module_id[]" value="<?=$row_modules['SMID']?>" id=""> 
                                    <input type="checkbox" name="module_user[<?=$row_modules['SMID']?>][]" value="<?=$row_modules['SMID']?>" id="" class="form-check-input">
                                    <div class="table-responsive mb-4">
                                        <table id="tbl<?=$row_modules['SMID']?>">
                                            <thead>
                                                <tr>
                                                    <th>SI No</th>
                                                    <th>Menu Name</th>
                                                    <th><input type="checkbox" class="select-all form-check-input" data-column="create"> Create</th>
                                                    <th><input type="checkbox" class="select-all form-check-input" data-column="update"> Update</th>
                                                    <th><input type="checkbox" class="select-all form-check-input" data-column="view"> View</th>
                                                    <th><input type="checkbox" class="select-all form-check-input" data-column="delete"> Delete</th>
                                                    <th><input type="checkbox" class="select-all form-check-input" data-column="verify"> Verify</th>
                                                    <th><input type="checkbox" class="select-all form-check-input" data-column="print"> Print</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            $user_sysfeatures=$user_module->select_features($row_modules['SMID']);
                                            $i=1;
                                            foreach ($user_sysfeatures as $row_feature) 
                                            {?>
                                            <tr>
                                                <td><?=$i?></td>
                                                <td>
                                                  <?=$row_feature['FeatureName']?>
                                                  <input type="hidden" name="feature_id[<?=$row_modules['SMID']?>][]" value="<?=$row_feature['SFID']?>" id="">
                                                </td>
                                                <td>
                                                  <input type="checkbox" name="create[<?=$row_modules['SMID']?>][<?=$row_feature['SFID']?>][]" id="" class="form-check-input create">
                                                </td>
                                                <td>
                                                  <input type="checkbox" name="update[<?=$row_modules['SMID']?>][<?=$row_feature['SFID']?>][]" id="" class="form-check-input update" value="1">
                                                </td>
                                                <td>
                                                  <input type="checkbox" name="view[<?=$row_modules['SMID']?>][<?=$row_feature['SFID']?>][]" id="" class="form-check-input view" value="1">
                                                </td>
                                                <td>
                                                  <input type="checkbox" name="delete[<?=$row_modules['SMID']?>][<?=$row_feature['SFID']?>][]" id="" class="form-check-input delete" value="1">
                                                </td>
                                                  <td>
                                                  <input type="checkbox" name="verify[<?=$row_modules['SMID']?>][<?=$row_feature['SFID']?>][]" id="" class="form-check-input verify" value="1">
                                                </td>
                                                <td>
                                                  <input type="checkbox" name="print[<?=$row_modules['SMID']?>][<?=$row_feature['SFID']?>][]" id="" class="form-check-input print" value="1">
                                                </td>
                                            </tr>
                                                <?php           
                                                $i++;                             
                                            }
                                            ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php
                                }
                                ?>
                                <input type="submit" value="Submit" class="btn btn-primary" name="add-role">
                            </form>
                        </div>
                        <div class="col-md-12">
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
          $(document).ready(function() {
              // Select/Deselect all checkboxes in a column
              $('.select-all').click(function() {
                  var columnClass = $(this).data('column');
                  var table = $(this).parent().parent().parent().parent().attr("id");
                  console.log(table);
                  
                  $('#'+table +' input.' + columnClass).prop('checked', this.checked);
              });
          });
        </script>
      </div>
    </div>
  </div>
</div>
    
  <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../Assets/js/sidebarmenu.js"></script>
  <script src="../Assets/js/app.min.js"></script>
  <script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
  <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
  <script src="../Assets/js/dashboard.js"></script>
</body>

</html>