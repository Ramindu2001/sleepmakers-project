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
  $ur=new UserRole();
  $userroles=$ur->select_all_active_userroles();
  $users_role=new User();
  ?>
  <style>
    .table>:not(caption)>*>* {
      padding: 10px;
  }
  </style>
</head>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.0/dist/jquery.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/habibmhamadi/multi-select-tag@2.0.1/dist/css/multi-select-tag.css">

<body>
  <!--  Body Wrapper -->
    <div class="h-100vh">
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed">
            <!-- Sidebar Start -->
            <?php 
            include '../View/sidebar.php';
            $feature_id=20;
            include '../Includes/viewPermission.php';
            $row_users_role=$users_role->select_all_users_with_role($userType);
            ?>
            <!--  Sidebar End -->
            <!--  Main wrapper -->
            <div class="body-wrapper">
            <!--  Header Start -->
                <?php 
                include '../View/header.php';
                include '../View/modals/add-user.php';
                include '../View/modals/edit-user.php';
                include '../View/modals/password-change.php';
                ?>
                <!--  Header End -->
                <div class="container-fluid">
                  <?php 
                  if (isset($_SESSION['user_error'])) 
                  {
                    if ($_SESSION['user_error']==1) 
                    {
                      ?>
                      <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>User Added</strong> Successfully
                      </div>
                      <?php
                    }
                    else if ($_SESSION['user_error']==2) 
                    {
                      ?>
                      <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Oops!</strong> Username/email already available
                      </div>
                      <?php
                    }
                    else if ($_SESSION['user_error']==3) 
                    {
                      ?>
                      <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>User Updated!</strong>Successfully
                      </div>
                      <?php
                    }
                    else if ($_SESSION['user_error']==4) 
                    {
                      ?>
                      <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Password Updated!</strong>Successfully
                      </div>
                      <?php
                    }
                    else if ($_SESSION['user_error']==6) 
                    {
                      ?>
                      <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>User Deactivated!</strong> Successfully
                      </div>
                      <?php
                    }
                    else if ($_SESSION['user_error']==7) 
                    {
                      ?>
                      <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>User Activated!</strong> Successfully
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
                    unset($_SESSION['user_error']);
                  }
                  ?>
                  
        <!--  Row 1 -->
        <div class="row">
          <div class="col-lg-12">
            <div class="card w-100">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-4">Users</h5>
                    <div class="row">
                        <div class="col-md-12 d-flex justify-content-end">
                          <?php 
                          if($userType==1 || $create==1)
                          {
                            ?>
                            <a href="javascript:void(0)" class="btn btn-primary" id="btn-add-user">Add New User</a>
                            <?php
                          }
                          ?>
                        </div>
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Profile Pic</th>
                                            <th class="w-10">Username</th>
                                            <th>User Email</th>
                                            <th>User Contact</th>
                                            <th>Daily Bill Limit</th>
                                            <th>User Role</th>

                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                      <?php 
                                      $i=1;
                                      foreach ($row_users_role as $key) 
                                      {
                                        ?>
                                        <tr>
                                            <td>
                                                <?=$i?>
                                            </td>
                                            <td>
                                                <img src="../Assets/Images/user_profile/<?=$key['UserProfile']?>" style="border-radius:50%; box-shadow: 6px 4px 12px;" alt="" class="w-20"> <?php 
                                                        if($key['USID']==$_SESSION['user_id'])
                                                        {
                                                            ?>
                                                                <span class="ms-4 badge bg-danger"> Current User</span><br>
                                                            <?php

                                                        }
                                                        ?>
                                            </td>
                                          <td>
                                            <input type="hidden" name="" value="<?=$key['USID']?>" id="uid">
                                            <input type="hidden" name="" value="<?=$key['UserName']?>" id="uname">
                                            <input type="hidden" name="" value="<?=$key['UserEmail']?>" id="uemail">
                                            <input type="hidden" name="" value="<?=$key['ContactNo']?>" id="ucontact">
                                            <input type="hidden" name="" value="<?=$key['paylimit']?>" id="paylimit">
                                            <input type="hidden" name="" value="<?=$key['UserRoles_URID']?>" id="urole_id">
                                            <input type="hidden" name="" value="<?=$key['UserRoleName']?>" id="urole">
                                            <input type="hidden" name="" value="<?=$key['UserProfile']?>" id="upropic">
                                            <input type="hidden" name="" value="<?=$key['UserStat']?>" id="ustat">
                                            <?=$key['UserName']?>
                                          </td>
                                          <td>
                                            <?=$key['UserEmail']?>                                              
                                          </td>
                                          <td>
                                            <?=$key['ContactNo']?>                                                 
                                          </td>
                                          <td>
                                            <?=number_format($key['paylimit'],2,'.')?>                                                 
                                          </td>
                                          <td>
                                            <?=$key['UserRoleName']?><br>                                     
                                          </td>

                                          <td>
                                            <?php  
                                            if ($key['UserStat']==1) 
                                            {
                                              ?>
                                              <span class="mb-1 badge text-bg-success">Active</span>                                              
                                              <?php
                                            }
                                            else
                                            {
                                              ?>
                                              <span class="mb-1 badge bg-danger">Inactive</span>                                              
                                              <?php
                                            }
                                            ?>
                                          </td>
                                          <td>
                                            <div class="row">
                                              <?php 
                                              if($edit==1 || $userType==1)
                                              {
                                                ?>
                                                <div class="col-md-3">
                                                    <a href="javascript:void(0)" class="" id="btn-user-edit"><i class="ti ti-edit"></i></a>
                                                </div>
                                                <div class="col-md-3">
                                                    <a href="javascript:void(0)" class="" id="btn-edit-password"><i class="ti ti-arrows-exchange-2"></i></a>
                                                </div>
                                                <div class="col-md-3">
                                                    <?php if ($key['UserStat'] == 1) { ?>
                                                        <form action="../Controller/userController.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to deactivate this user?');">
                                                            <input type="hidden" name="delete_user_id" value="<?=$key['USID']?>">
                                                            <button type="submit" name="delete-user" class="btn btn-sm p-0 border-0 bg-transparent text-warning" title="Deactivate User"><i class="ti ti-user-off"></i></button>
                                                        </form>
                                                    <?php } else { ?>
                                                        <form action="../Controller/userController.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to activate this user?');">
                                                            <input type="hidden" name="activate_user_id" value="<?=$key['USID']?>">
                                                            <button type="submit" name="activate-user" class="btn btn-sm p-0 border-0 bg-transparent text-success" title="Activate User"><i class="ti ti-user-check"></i></button>
                                                        </form>
                                                    <?php } ?>
                                                </div>
                                                <?php
                                              }
                                              ?>
                                                
                                            </div>
                                            
                                          </td>
                                        </tr>
                                        <?php
                                        $i++;
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
  <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
  <script src="../Assets/jquery/users.js"></script>
    <script src="./lib/select2/js/select2.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/habibmhamadi/multi-select-tag@2.0.1/dist/js/multi-select-tag.js"></script>
    <script>
        
      new MultiSelectTag('userRole')
    </script>
</body>

</html>