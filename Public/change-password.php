<!doctype html>
<html lang="en">

<head>
  <?php 
  include '../View/loginhead.php';
//   session_start();
  // print_r($_SESSION);
  include "../Includes/includes.php";
  if (!isset($_GET['token']) || $_GET['token']=="" || $_GET['token']==0 || $_GET['token']==null) 
  {
    $_SESSION['user_error']=0;
    header("Location:./forget-password.php");
  }
  ?>
</head>
<body>
  <!--  Body Wrapper -->
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    <div
      class="position-relative overflow-hidden radial-gradient min-vh-100 d-flex align-items-center justify-content-center">
      <div class="d-flex align-items-center justify-content-center w-100">
        <div class="row justify-content-center w-100">
          <div class="col-md-8 col-lg-4 col-xxl-3">
            <div class="card mb-0">
              <div class="card-body">
                <a href="index.php" class="text-nowrap logo-img text-center d-block py-3 w-100">
                  <img src="../Assets/Images/synnex_logo.png" width="300" alt="">
                </a>
                <!-- <p class="text-center">Elevate Your Business with Synnex Cloud POS</p> -->
                <?php  
                if (isset($_SESSION['user_error'])) 
                {
                  if ($_SESSION['user_error']==0) 
                  {
                    ?>
                    <p class="text-center" style="color:#ff0000;">Something went wrong, Please try again.</p>
                    <?php
                  }
                  elseif ($_SESSION['user_error']==1) 
                  {
                    ?>
                    <p class="text-center" style="color:#ff0000;">You have no Permssion to that page</p>
                    <?php
                  }
                  elseif ($_SESSION['user_error']==2) 
                  {
                    ?>
                    <p class="text-center" style="color:#ff0000;">No username/email found</p>
                    <?php
                  }
                  elseif ($_SESSION['user_error']==3) 
                  {
                    ?>
                    <p class="text-center" style="color:#ff0000;">Incorrect password</p>
                    <?php
                  }
                  else
                  {
                    ?>
                    <p class="text-center" style="color:#ff0000;">No username/email found</p>
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
                    <div class="d-flex align-items-center justify-content-between mb-4">
                    <a class="text-primary fw-bold" href="./login.php">Login</a>
                  </div>
                  <input type="submit" class="btn btn-primary w-100 py-8 fs-4 mb-4 rounded-2 btn-sign-in" value="Sign In" name="btn_password_change">
                </form>
              </div>
            </div>
          </div>
          <!-- footer start  -->
          <?php 
          $current_year=date('Y');
          ?>
          <div class="col-md-12 m-1">
                <p class="text-center" style="color:#2A3547; font-weight:600;"> Copyright &copy; <?=$current_year;?> By Synnex Cloud POS</p>
          </div>
          <!-- footer end  -->
        </div>
      </div>
    </div>
  </div>
  <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>