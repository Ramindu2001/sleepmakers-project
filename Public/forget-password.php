<!doctype html>
<html lang="en">

<head>
  <?php 
  include '../View/loginhead.php';
  session_start();
  // print_r($_SESSION);
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
                <!-- Passwords are set by the system admin (Settings -> Users), never reset by email:
                     see db/SHOP_ACCESS_MODULE.md -->
                <h5 class="text-center fw-semibold mb-3">Forgot your password?</h5>
                <p class="text-center mb-4">
                  Usernames and passwords are issued by your system admin.<br>
                  Please contact your system admin to reset your password.
                </p>
                <a class="btn btn-primary w-100 py-8 fs-4 mb-4 rounded-2 btn-sign-in" href="./login.php">Back to Sign In</a>
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