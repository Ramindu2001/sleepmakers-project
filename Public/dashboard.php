<?php 
include '../Includes/includes.php';
include '../Includes/authcheck-dashboard.php';

//======================= User Log in check ====================//
    /*
    * when the session is destroyed force to log in
    */
    $designation = 0;
    $user = "";
    $user_company = "";
    if(isset($_SESSION['shop_id']))
    {
      header("Location:../Public/home.php");
    }
    else if(($remembered_shop_id = (new RememberMe())->shopFromCookie($_SESSION['user_id'])) !== null)
    {
      //signed remember-me shop cookie, see Includes/remember_me.php
      $_SESSION['shop_id']=$remembered_shop_id;
      header("Location:../Public/home.php");

    }
    if(isset($_SESSION['user_id']))
    {
        $user_id = $_SESSION['user_id'];
        $userObj = new User();
        $user = $userObj->getOneUser($user_id);
    }//user logged in
    else
    {
        header("Location: login.php");
    }//force to log in

//================== Admin Log in ===================//
if($designation == 1)
{
    header("Location: company.php");
}

?>
<!doctype html>
<html lang="en">

<head>
  <?php 
  include '../View/head.php';
  ?>
  <style>
    @media (min-width: 1200px)
    {
      #main-wrapper[data-layout=vertical][data-header-position=fixed] .app-header {
          width: calc(100%);
      }
      #main-wrapper[data-layout=vertical][data-sidebartype=full] .body-wrapper {
      margin-left: 0;
      }
    }
    
    a#synnex_shop:hover 
    {
      background: #5d87ff ;
    }
    
    a#synnex_shop:hover .card-title
    {
      color: white !important;
    }
    #main-wrapper[data-layout=vertical][data-header-position=fixed] .body-wrapper>.container-fluid{
    padding-top: calc(182px + 15px);
    }
    .card {
    margin-bottom: 30px;
    background: #ffffffeb;
    }
    .app-header
    {
      background: none;
    }
    body
    {
      
    height: 100vh;
    background: url('../Assets/Images/icons/background 3.jpg');
    background-position: center;
    background-size: cover;
    }
  </style>
</head>

<body>
  <div class="toast toast-onload align-items-center text-bg-primary border-0 fade" role="alert" aria-live="assertive" aria-atomic="true" id="toast">
    <div class="toast-body hstack align-items-start gap-6">
      <i class="ti ti-alert-circle fs-6"></i>
      <div>
        <h5 class="text-white fs-3 mb-1">Welcome to Synnex Cloud POS</h5>
        <h6 class="text-white fs-2 mb-0">Happiness is the key to Success.</h6>
      </div>
      <button type="button" class="btn-close btn-close-white fs-2 m-0 ms-auto shadow-none" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
  <!--  Body Wrapper -->
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    <!--  Main wrapper -->
    <div class="body-wrapper">
      <!--  Header Start -->
      <?php 
      include '../View/header-dashbord.php';
      ?>
      <!--  Header End -->
      <div class="container-fluid">
        <div class="card">
          <div class="card-body">
            <?php 
            $comObj = new Company();
            $UserType=$user[0]["UserType"];
            $comData = $comObj->getCompanyByUser($user_id,$UserType);
            if(!empty($comData))
            {
              ?>
                <h5 class="card-title fw-semibold mb-4"><?=$comData[0]['ComName'];?></h5>
                <p class="mb-0">Please Select Your Shop 
                  <?php 
                  // if(isset($_COOKIE["remember_me_synnex"])) echo $_COOKIE["remember_me_synnex"]
                  ?>
                </p>

                <form action="../Controller/shopController.php" method="post">
                <div class="m-3">
                <div class="container-fluid row">
                  <?php 
                  if(count($comData)==1)
                  {
                    $date=date("Y-m-d");
                    
                    if($date>$comData[0]["ComExpireDate"])
                    {
                      if($user[0]["UserType"]==0)
                      {
                        $_SESSION["expired"]=1;
                        unset($_SESSION['user_id']);
                        ?>
                        <script>                      
                          window.location="../Public/login.php";
                        </script>
                        
                        <?php
                      }
                      else
                      {
                        $_SESSION['shop_id'] = $comData[0]['SHID'];
                        //goto main page
                        // header("Location: ../Public/home.php");
                        ?>
                        <script>                      
                          window.location="../Public/home.php";
                        </script>
                        <div id="reply"></div>
                        <?php
                      }
                       
                    }
                    else
                    {
                      $_SESSION['shop_id'] = $comData[0]['SHID'];
                      //goto main page
                      // header("Location: ../Public/home.php");
                      ?>
                      <script>                      
                        window.location="../Public/home.php";
                      </script>
                      <div id="reply"></div>
                      <?php
                    }
                    
                    
                  }
                  else
                  {
                    foreach ($comData as $row) 
                    {
                      $date=date("Y-m-d");
                      ?>
                      <style>                        
                      <?php 
                      if($date>$row["ComExpireDate"] || $row["ComStat"]==0)
                      {
                        if($user[0]["UserType"]==0)
                        {
                          ?>
                          a#synnex_shop 
                          {
                            background: grey !important;
                          }
                          a#synnex_shop:hover 
                          {
                            background: grey !important;
                          }
                          a#synnex_shop:hover .card-title
                          {
                            color: red !important;
                          }
                          <?php
                        }
                      }
                      else
                      {

                      }
                      ?>
                      </style>
                      <div class="col-md-3 p-1">
                        <input type="hidden" name="hide_shop_id" id="hide_shop_id" value="<?=$row['SHID']?>">
                        <button type="submit" name="btn_continue" class="card" id="synnex_shop"
                        <?php 
                        if($date>$row["ComExpireDate"] || $row["ComStat"]==0)
                        {
                          if($user[0]["UserType"]==0)
                          { 
                            echo "disabled";
                          }
                        }
                        else
                        {

                        }
                        ?>
                        >
                          <div class="w-100 p-2">
                            <div class="row">
                              <div class="col-md-2 d-flex justify-content-start">
                                <?php 
                                if($user[0]["UserType"]==0)
                                {
                                  ?>
                                  <input type="radio" name="cmb_shops" id="radio" value="<?=$row['SHID']?>">
                                  <?php
                                }
                                else
                                {
                                  ?>
                                  <input type="radio" name="cmb_shops" id="radio" value="<?=$row['shopID']?>">
                                  <?php
                                }
                                ?>
                                
                              </div>
                              <div class="col-md-10">
                              </div>
                              <div class="row">
                                  <div class="col-md-12 " style="display:flex; justify-content:center;">
                                      <img src="../Assets/Images/icons/shop.png" class="w-50" alt="">
                                  </div>
                                  <?php 
                                  if($date>$row["ComExpireDate"] || $row["ComStat"]==0)
                                  {
                                      if($user[0]["UserType"]==0)
                                      {
                                      ?>
                                      <div class="col-md-12 m-2">
                                        <h6 class="card-title mb-4 text-center" style="font-size:14px; color:#ff0000;">
                                          <?=$row['ShopName']?>
                                        </h6>
                                      </div>
                                      <?php
                                      }
                                      else
                                      {
                                        ?>
                                        <div class="col-md-12 m-2">
                                          <h6 class="card-title mb-4 text-center" style="font-size:14px; color:#5d87ff;">
                                            <?=$row['ShopName']?>
                                          </h6>
                                        </div>
                                        <?php

                                      }
                                  }
                                  else
                                  {
                                    ?>
                                    <div class="col-md-12 m-2">
                                      <h6 class="card-title mb-4 text-center" style="font-size:14px; color:#5d87ff;">
                                        <?=$row['ShopName']?>
                                      </h6>
                                    </div>
                                    <?php
                                  }
                                  ?>
                                  <?php 
                                  if($date>$row["ComExpireDate"] ||  $row["ComStat"]==0)
                                  {
                                    if($user[0]["UserType"]==0)
                                    {
                                      if( $row["ComStat"]==0)
                                      {
                                        ?>
                                        <div class="col-md-12 m-2">
                                          <h6 class="card-title mb-4 text-center" style="font-size:14px; color:#ff0000;">
                                            <b>Company Inactive. Please Contact Synnex IT Solutions.</b>
                                          </h6>
                                        </div>
                                        <?php
                                      }
                                      else
                                      {
                                        ?>
                                        <div class="col-md-12 m-2">
                                          <h6 class="card-title mb-4 text-center" style="font-size:14px; color:#ff0000;">
                                            <b>Company Expired. Please Contact Synnex IT Solutions.</b>
                                          </h6>
                                        </div>
                                        <?php
                                        
                                      }
                                        
                                    }
                                    
                                  }
                                  else
                                  {

                                  }
                                  ?>
                              </div>
                            </div>
                          </div>
                        </button>
                      </div>
                      <?php
                    }//foreach 2025-08-05
                  }
                  
                  ?>
                </div>
              </div>
              </form>
              <?php 
            }//has comapny
            else
            {
              $_SESSION['no_shop_assigned'] = 1;
              unset($_SESSION['user_id']);
              ?>
              <script>
                window.location = "../Public/login.php";
              </script>
              <?php
            }//no shop or company
            ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../Assets/js/sidebarmenu.js"></script>
  <script src="../Assets/js/app.min.js"></script>
  <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
  <script src="../Assets/jquery/toast.js"></script>
  <script>
    $(document).ready(function(){
      $('body').on('click','#synnex_shop', function(){
        var radio = $(this).find("#radio");
        var value = $(this).find("#radio").val();
        console.log(value);
        if (radio.is(':checked')) 
        {
          radio.removeAttr('checked');
        }
        else
        {
          radio.attr('checked','checked'); 
        }
      });

    });
  </script>
</body>

</html>