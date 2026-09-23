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
  if (!isset($_GET['id'])||$_GET['id']==''||$_GET['id']==null||$_GET['id']==0) 
  {
    $_SESSION['status']=3;
    header("Location:./user-roles.php");
  }
  $user_module=new UserRole();
  $user_modules=$user_module->select_edit_rolemodules($_GET['id'],$shop_id);
  $edit_user=$user_module->edit_role($_GET['id']);
  if(count($edit_user)==0)
  {
    $_SESSION['status']=3;
    header("Location:./user-roles.php");
  }
  // print_r($edit_user)
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
            $feature_id=54;
            include '../Includes/editPermission.php';
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
                  <div id="alert">
                    
                  </div>
                    <?php 
                  if (isset($_SESSION['role_status'])) 
                  {
                    if ($_SESSION['role_status']==1) 
                    {
                      ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show"
                        role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"
                            aria-label="Close"></button>
                        <strong>Error - </strong> Role name cannot be empty, Please try again!
                    </div>
                    <?php
                    }
                    else if ($_SESSION['role_status']==2) 
                    {
                      ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show"
                        role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"
                            aria-label="Close"></button>
                        <strong>Error - </strong> Role already exists, Please try again!
                    </div>
                    <?php
                    }
                    else if ($_SESSION['role_status']==3) 
                    {
                      ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show"
                        role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"
                            aria-label="Close"></button>
                        <strong>Role Updated -</strong> Successfully
                    </div>
                    <?php
                    }
                    else
                    {
                      ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show"
                        role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"
                            aria-label="Close"></button>
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
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox"
                                                        id="status" <?php 
                                                        if ($edit_user[0]["ur_status"]==1) 
                                                        {
                                                          echo "checked value='0'";
                                                        }
                                                        else
                                                        {
                                                          echo "value='1'";
                                                        }
                                                        ?>>
                                                    <label class="form-check-label" for="status">Status</label>
                                                </div>
                                                <div class="mb-3">
                                                    <lable class="form-label mb-3">Role Name <span
                                                            class="text-danger">*</span></lable><br>
                                                    <input type="text" class="form-control" placeholder="Eg: Manager"
                                                        value="<?=$edit_user[0]['UserRoleName']?>" name="role_name"
                                                        required>
                                                    <input type="hidden" class="form-control" placeholder="Eg: Manager"
                                                        value="<?=$_GET['id']?>" name="role_id" id="role_id" required>

                                                </div>
                                                <?php 
                                foreach ($user_modules as $row_modules) 
                                {
                                    ?>
                                                <h5 class="card-title "><?=$row_modules['ModuleName']?></h5>
                                                <input type="hidden" name="module_id[]"
                                                    value="<?=$row_modules['SMID']?>" id="module_id<?=$row_modules['ModuleName']?>">
                                                <input type="checkbox" name="module_user[<?=$row_modules['SMID']?>][]"
                                                    value="<?=$row_modules['SMID']?>" id="module_user[<?=$row_modules['SMID']?>]" class="form-check-input"
                                                    <?php if($row_modules['Access']==1){echo "checked";}?>>
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
                                            $user_sysfeatures=$user_module->select_edit_rolefeatures($row_modules['SMID'],$_GET['id'],$shop_id);
                                            $i=1;
                                            foreach ($user_sysfeatures as $row_feature) 
                                            {
                                                ?>
                                                            <tr>
                                                                <td><?=$i?></td>
                                                                <td><?=$row_feature['FeatureName']?><input type="hidden"
                                                                        name="feature_id[<?=$row_modules['SMID']?>][]"
                                                                        value="<?=$row_feature['SFID']?>" id="feature_id[<?=$row_modules['SMID']?>]"></td>
                                                                <td><input type="checkbox"
                                                                        name="create[<?=$row_modules['SMID']?>][<?=$row_feature['SFID']?>][]"
                                                                        id="create<?=$row_modules['SMID']?>" class="create form-check-input"
                                                                        <?php if ($row_feature['is_create']==1) {echo "checked";}?>>
                                                                </td>
                                                                <td><input type="checkbox"
                                                                        name="update[<?=$row_modules['SMID']?>][<?=$row_feature['SFID']?>][]"
                                                                        id="update<?=$row_modules['SMID']?>" class="update form-check-input" value="1"
                                                                        <?php if ($row_feature['is_edit']==1) {echo "checked";}?>>
                                                                </td>
                                                                <td><input type="checkbox"
                                                                        name="view[<?=$row_modules['SMID']?>][<?=$row_feature['SFID']?>][]"
                                                                        id="view<?=$row_modules['SMID']?>" class="view form-check-input" value="1"
                                                                        <?php if ($row_feature['is_view']==1) {echo "checked";}?>>
                                                                </td>
                                                                <td><input type="checkbox"
                                                                        name="delete[<?=$row_modules['SMID']?>][<?=$row_feature['SFID']?>][]"
                                                                        id="delete<?=$row_modules['SMID']?>" class="delete form-check-input" value="1"
                                                                        <?php if ($row_feature['is_delete']==1) {echo "checked";}?>>
                                                                </td>
                                                                <td><input type="checkbox"
                                                                        name="verify[<?=$row_modules['SMID']?>][<?=$row_feature['SFID']?>][]"
                                                                        id="verify<?=$row_modules['SMID']?>" class="verify form-check-input" value="1"
                                                                        <?php if ($row_feature['is_verify']==1) {echo "checked";}?>>
                                                                </td>
                                                                <td><input type="checkbox"
                                                                        name="print[<?=$row_modules['SMID']?>][<?=$row_feature['SFID']?>][]"
                                                                        id="print<?=$row_modules['SMID']?>" class="print form-check-input" value="1"
                                                                        <?php if ($row_feature['is_print']==1) {echo "checked";}?>>
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
                                                <input type="submit" value="Submit" class="btn btn-primary"
                                                    name="edit-role">
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

                </div>
            </div>
        </div>
    </div>
    <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
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
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="../Assets/jquery/userrole.js"></script>
</body>

</html>