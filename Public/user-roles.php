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
  $ur=new UserRole();
  $userroles=$ur->select_all_userroles();

  ?>
</head> 
<body>
  <!--  Body Wrapper -->
    <div class="h-100vh">
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full" data-sidebar-position="fixed" data-header-position="fixed">
            <!-- Sidebar Start -->
            <?php 
            include '../View/sidebar.php';
            $feature_id=54;
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
                  <?php 
                  if (isset($_SESSION['status'])) 
                  {
                    if ($_SESSION['status']==1) 
                    {
                      ?>
                      <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Counter Started</strong> Successfully
                      </div>
                      <?php
                    }
                    else if ($_SESSION['status']==2) 
                    {
                      ?>
                      <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Counter Closed</strong> Successfully
                      </div>
                      <?php
                    }
                    else
                    {
                      ?>
                      <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Error -</strong> Something went wrong, Please try again!
                      </div>
                      <?php
                    }
                    unset($_SESSION['status']);
                  }
                  
                  if (isset($_SESSION['role_status'])) 
                  {
                    if ($_SESSION['role_status']==4) 
                    {
                      ?>
                      <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>User Role Activated!</strong> Successfully
                      </div>
                      <?php
                    }
                    else if ($_SESSION['role_status']==5) 
                    {
                      ?>
                      <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>User Role Deactivated!</strong> Successfully
                      </div>
                      <?php
                    }
                    else if ($_SESSION['role_status']==6)
                    {
                      ?>
                      <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Your session expired.</strong> Please try again.
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
                    <h5 class="card-title fw-semibold mb-4">User Roles</h5>
                    <div class="row">
                      <?php 
                      if($userType==1)
                      {
                        ?>
                        <div class="col-md-12 d-flex justify-content-end">
                            <a href="add-role.php" class="btn btn-primary">Add New User Role</a>
                        </div>
                        <?php
                      }
                      ?>
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th class="w-20">User Role</th>
                                            <th>User Features</th>
                                            <th>User Modules</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                      <?php 
                                      foreach ($userroles as $key) 
                                      {
                                        ?>
                                        <tr>
                                          <td>
                                            <?=$key['UserRoleName']?>
                                          </td>
                                          <td>
                                              <?php  
                                              $user_features=$ur->select_all_user_role_feature($key['URID']);
                                              foreach ($user_features as $features) 
                                              {
                                                ?>
                                                <span class="mb-1 badge text-bg-primary"><?=$features['careteAccess']?></span>
                                                <span class="mb-1 badge text-bg-primary"><?=$features['editAccess']?></span>
                                                <span class="mb-1 badge text-bg-primary"><?=$features['viewAccess']?></span>
                                                <span class="mb-1 badge text-bg-primary"><?=$features['deleteAccess']?></span>
                                                <span class="mb-1 badge text-bg-primary"><?=$features['verifyAccess']?></span>
                                                <span class="mb-1 badge text-bg-primary"><?=$features['printAccess']?></span>
                                                <?php
                                              }
                                              ?>
                                          </td>
                                          <td>
                                              <?php
                                              $user_module=$ur->select_all_user_role_module($key['URID']);
                                              foreach ($user_module as $module) 
                                              {
                                                ?>
                                                <span class="mb-1 badge text-bg-primary"><?=$module['ModuleName']?></span>
                                                <?php
                                              }
                                              
                                              ?>
                                          </td>
                                          <td>
                                            <?php  
                                            if ($key['ur_status']==1) 
                                            {
                                              ?>
                                              <span class="mb-1 badge text-bg-success">Active</span>                                              
                                              <?php
                                            }
                                            else
                                            {
                                              ?>
                                              <span class="mb-1 badge text-bg-danger">Inactive</span>                                              
                                              <?php
                                            }
                                            ?>
                                          </td>
                                          <td>
                                            <div class="d-flex align-items-center gap-2">
                                              <?php 
                                              if($userType==1 || $edit==1)
                                              {
                                                ?>
                                                <a href="edit-role.php?id=<?=$key['URID']?>" class="btn btn-sm p-0 border-0 bg-transparent text-primary"><i class="ti ti-edit"></i></a>
                                                <?php
                                              }
                                              
                                              if($userType==1 || $edit==1) // Assuming edit rights allows role status toggle
                                              {
                                                  if ($key['ur_status'] == 1) { ?>
                                                      <form action="../Controller/userrolecontrol.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to deactivate this role?');">
                                                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                                          <input type="hidden" name="delete_role_id" value="<?=$key['URID']?>">
                                                          <button type="submit" name="delete-role" class="btn btn-sm p-0 border-0 bg-transparent text-warning" title="Deactivate Role"><i class="ti ti-user-off"></i></button>
                                                      </form>
                                                  <?php } else { ?>
                                                      <form action="../Controller/userrolecontrol.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to activate this role?');">
                                                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                                          <input type="hidden" name="activate_role_id" value="<?=$key['URID']?>">
                                                          <button type="submit" name="activate-role" class="btn btn-sm p-0 border-0 bg-transparent text-success" title="Activate Role"><i class="ti ti-user-check"></i></button>
                                                      </form>
                                                  <?php }
                                              }
                                              ?>
                                            </div>
                                          </td>
                                        </tr>
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
        <!-- footer Start  -->
        <?php include '../View/footer.php';?>
        <!-- footer End  -->
        
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