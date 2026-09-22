<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';
?>
<!doctype html>
<html lang="en">

<head>
  <?php
  include '../View/head.php';
  if(isset($_SESSION["loading"]))
  {
    include '../View/loader.php';
    unset($_SESSION["loading"]);
  }
  $shopObj = new Shop();
  $shopData = $shopObj->getOneShop($shop_id);         
  $company_logo = $shopData[0]['ComLogo'];
  $shop_id = $_SESSION['shop_id'];
  ?>
  <link rel="stylesheet" href="../Assets/css/dashboard.css">
  <style>
    .card 
    {
        margin-bottom: 30px;
        background: #ffffffe8;
    }
    .body-wrapper>.container-fluid
    {
        padding:0 ;
    }
  </style>
</head>

<body>

  <?php
  // print_r($_SESSION);

  if(isset($_SESSION["toast"]))
  {
    $message="Hi. ";
    $message2="Shop: ".$shopData[0]['ShopName'];
    include "../View/toast.php";
    unset($_SESSION["toast"]);
  }
  
  ?>
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
                    else if ($_SESSION['status']==3) 
                    {
                      ?>
                      <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Please start a new <strong>Cash Counter</strong>.
                      </div>
                      <?php
                    }
                    else if ($_SESSION['status']==4) 
                    {
                      ?>
                      <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Please add <strong>payment methods</strong> to the shop.
                      </div>
                      <?php
                    }//no paymethods
                    else if ($_SESSION['status']==5) 
                    {
                      ?>
                      <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Please add <strong>Sale Settings</strong> to the shop.
                      </div>
                      <?php
                    }//no sale settings
                    else if ($_SESSION['status']==6) 
                    {
                      ?>
                      <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Please add <strong>Receipt Settings</strong> to the shop.
                      </div>
                      <?php
                    }//no sale settings
                    else
                    {
                      ?>
                      <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Error - </strong> Something went wrong, Please try again!
                      </div>
                      <?php
                    }
                    unset($_SESSION['status']);
                  }//counter status

                  if(isset($_SESSION['user_error']))
                  {
                    if ($_SESSION['user_error']==4) 
                    {
                      ?>
                      <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Password Updated!</strong>Successfully
                      </div>
                      <?php
                    }
                    else if ($_SESSION['user_error']==5) 
                    {
                      ?>
                      <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Oops!Something Went Wrong,</strong>Please Try Again
                      </div>
                      <?php
                    }
                    else if ($_SESSION['user_error']==11)
                    {
                      ?>
                      <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Your session expired.</strong> Please try again.
                      </div>
                      <?php
                    }
                    else if ($_SESSION['user_error']==12)
                    {
                      ?>
                      <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Password not changed.</strong> Your current password is incorrect.
                      </div>
                      <?php
                    }
                    else if ($_SESSION['user_error']==13)
                    {
                      ?>
                      <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Password not changed.</strong> The new passwords are empty or do not match.
                      </div>
                      <?php
                    }
                    unset($_SESSION['user_error']);
                  }
                  ?>

    <!--------------------------------- background image ------------------------------------>
    <div style="width:100%; min-height:80vh; padding:50px; background-image:url('../Assets/Images/icons/background.jpg');background-position-x: center;background-size: cover;">
        <div class="row justify-content-center mt-5">
        <div class="card col-md-4" style="border-radius: 35px;">
            <div class="w-100 d-flex justify-content-center" style="    margin-top: -60px;">
                <img src="../Assets/Images/user_profile/<?=$UserProfile?>" class="w-30" style="border-radius:50%;" alt="Profile Picture">
            </div>
            
            <h3 class="text-primary text-center mt-2"><b>Welcome, <?=$user_name?></b></h3>
            <p class=" text-center" style="font-size: 15px;"><i class="ti ti-user"></i> <?=$user_name?></p>
            <p class=" text-center" style="font-size: 15px;"><i class="ti ti-phone"></i> <?=$UserContactNo?></p>
            <p class=" text-center" style="font-size: 15px;"><i class="ti ti-mail"></i></i> <?=$UserEmail?></p>
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
    
  <!-- <script src="../Assets/jquery/dashboard.js"></script> -->
  <!-- <script src="../Assets/libs/jquery/dist/jquery.min.js"></script> -->
  <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../Assets/js/sidebarmenu.js"></script>
  <script src="../Assets/js/app.min.js"></script>
  <!-- <script src="../Assets/libs /apexcharts/dist/apexcharts.min.js"></script> -->
  <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
  <!-- <script src="../Assets/js/dashboard.js"></script> -->
  <script src="../Assets/jquery/toast.js"></script>
</body>

</html>