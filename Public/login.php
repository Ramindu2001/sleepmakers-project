<?php 
  session_start();
  include_once '../Includes/config.php';
  require_once '../Includes/remember_me.php';
  if(isset($_SESSION["user_id"]) && isset($_SESSION["user"]))
  {
    header("Location:../Public/dashboard.php");
  }
?>
<!doctype html>
<html lang="en">

<head>
  <?php 
  include '../View/loginhead.php';
  // print_r($_SESSION);
  ?>
  <style>
    .card {
    /* margin-bottom: 30px !important; */
        background: #ffffffe8 !important;
    }
    .radial-gradient:before
    {
      background: none !important;
    }
  </style>
</head>

<body>
  <!--  Body Wrapper -->
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    <div
      class="position-relative overflow-hidden radial-gradient min-vh-100 d-flex align-items-center justify-content-center" style=" background: url('../Assets/Images/icons/background.png'); background-size: cover; ">
      <div class="d-flex align-items-center justify-content-center w-100" style="margin-top :80px;">
        <div class="row justify-content-center w-100">
          <div class="col-md-8 col-lg-4 col-xxl-3">
            <div class="card mb-0">
              <div class="card-body">
                <a href="index.php" class="text-nowrap logo-img text-center d-block py-3 w-100">
                  <img src="../Assets/Images/synnex_logo.png" width="200" alt="">
                </a>
                <!-- <p class="text-center">Elevate Your Business with Synnex Cloud POS</p> -->
                <?php  
                if(isset($_SESSION['user_id']))
                {
                  header("Location:../Public/dashboard.php");
                }
                else if(($remembered_user_id = (new RememberMe())->userFromCookie()) !== null)
                {
                  //signed remember-me cookie, see Includes/remember_me.php
                  if(!headers_sent())
                  {
                    session_regenerate_id(true);
                  }
                  $_SESSION['user_id'] = $remembered_user_id;
                  header("Location:../Public/dashboard.php");

                }
                if (isset($_SESSION['expired'])) 
                {
                  ?>
                  <p class="text-center" style="color:#ff0000; font-weight:bold;">Company Expired. Please Contact Synnex IT Solutions.</p>
                  <?php
                  unset($_SESSION["expired"]);
                }
                if (isset($_SESSION['user_error'])) 
                {
                  if ($_SESSION['user_error']==1) 
                  {
                    ?>
                    <p class="text-center" style="color:#ff0000; font-weight:bold;">Access Denied, You have no Permssion to that page</p>
                    <?php
                  }
                  elseif ($_SESSION['user_error']==2) 
                  {     
                    ?>
                    <p class="text-center" style="color:#ff0000; font-weight:bold;">No username/email found</p>
                    <?php
                  }
                  elseif ($_SESSION['user_error']==3) 
                  {
                    ?>
                    <p class="text-center" style="color:#ff0000; font-weight:bold;">Incorrect password</p>
                    <?php
                  }
                  elseif ($_SESSION['user_error']==7) 
                  {
                    ?>
                    <p class="text-center" style="color:#ff0000; font-weight:bold;">Inactive userrole, Please contact your system Admin</p>
                    <?php
                  }
                  elseif ($_SESSION['user_error']==8) 
                  {
                    ?>
                    <p class="text-center" style="color:#ff0000; font-weight:bold;">Inactive user, Please contact your system Admin</p>
                    <?php
                  }
                  elseif ($_SESSION['user_error']==9) 
                  {
                    ?>
                    <p class="text-center" style="color:#ff0000; font-weight:bold;">No shops assigned to your account. Please contact your administrator.</p>
                    <?php
                  }
                  else
                  {
                    ?>
                    <p class="text-center" style="color:#ff0000; font-weight:bold;">Oops! Something went wrong</p>
                    <?php
                  }
                  unset($_SESSION['user_error']);
                }
                ?>
                <form action="../Controller/userController.php" method="POST">
                  <div class="mb-3">
                    <label for="user_name" class="form-label">Username/email</label>
                    <input type="text" name="user_name" class="form-control" id="user_name" maxlength="50" required>
                  </div>
                  <div class="mb-4">
                    <label for="user_pwd" class="form-label">Password</label>
                    <input type="password" class="form-control" id="user_pwd" name="user_pwd" required>
                  </div>
                  <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="form-check">
                      <input class="form-check-input primary" type="checkbox" id="show_password">
                      <label class="form-check-label text-dark" for="show_password">
                        Show Password
                      </label>
                    </div>
                    <a class="text-primary fw-bold" href="./forget-password.php">Forgot Password ?</a>
                  </div>
                  <input type="submit" class="btn btn-primary w-100 py-8 fs-4 mb-2 rounded-2 btn-sign-in" value="Sign In" name="btn_log_in">
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="form-check">
                      <input class="form-check-input primary" name="remember_me" type="checkbox" id="remember_me">
                      <label class="form-check-label text-dark" for="remember_me">
                        Remeber me
                      </label>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <!-- footer start  -->
          <?php 
          $current_year=date('Y');
          ?>
          <div class="col-md-12 m-1">
                <p class="text-center" style="color:#fff; font-weight:600;"> Copyright &copy; <?=$current_year;?> By Synnex Cloud POS</p>
          </div>
          <!-- footer end  -->
        </div>
      </div>
    </div>
  </div>
  <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    $(document).ready(function () 
    {
      $('#show_password').click(function () 
      {
          if ($('#user_pwd').prop("type")=="text") 
          {
            $('#user_pwd').prop("type","password")
          }
          else
          {
            $('#user_pwd').prop("type","text")
          }
      });
    });
  </script>
</body>
</html>