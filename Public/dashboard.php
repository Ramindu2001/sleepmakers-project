<?php
include '../Includes/includes.php';
include '../Includes/authcheck-dashboard.php';
require_once '../Includes/csrf.php';

//======================= Shop screen ====================//
/*
 * Lists the shops this user may enter. Entering one always takes a username and password:
 * the card opens the shop login dialog, which posts to Controller/shopController.php
 * (btn_shop_login). See db/SHOP_ACCESS_MODULE.md.
 */
if(isset($_SESSION['shop_id']))
{
  header("Location:../Public/home.php");
  exit;
}//already in a shop
else if(($remembered_shop_id = (new RememberMe())->shopFromCookie($_SESSION['user_id'])) !== null)
{
  //signed remember-me shop cookie, see Includes/remember_me.php
  $_SESSION['shop_id']=$remembered_shop_id;
  header("Location:../Public/home.php");
  exit;
}//remembered shop

$shopAccess = new ShopAccess();
$shops = $shopAccess->getSelectableShops($user_id);
if(empty($shops))
{
  $_SESSION['user_error'] = 9;
  unset($_SESSION['user_id'], $_SESSION['user']);
  header("Location: login.php");
  exit;
}//no shop to enter

$userType = $user[0]['UserType'];

//a refused shop login comes back here to reopen its dialog with the reason
$shop_login_error = isset($_SESSION['shop_login_error']) ? $_SESSION['shop_login_error'] : null;
unset($_SESSION['shop_login_error']);

//a value for inline JavaScript, safe inside a <script> block
function dashboard_js($value)
{
  return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}//dashboard js
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

    .synnex-shop:not(:disabled):hover
    {
      background: #5d87ff ;
    }

    .synnex-shop:not(:disabled):hover .card-title
    {
      color: white !important;
    }
    .synnex-shop:disabled
    {
      background: grey !important;
      cursor: not-allowed;
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
    /* the shop login dialog looks like the sign in card (Public/login.php) */
    #shop_login_modal .modal-content
    {
      background: #ffffffe8;
    }
    .btn-sign-in
    {
      background-color: #0072bc !important;
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
            if(isset($_SESSION['shop_access_error']))
            {
              ?>
              <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                <?=htmlspecialchars($_SESSION['shop_access_error'])?>
              </div>
              <?php
              unset($_SESSION['shop_access_error']);
            }//access removed while in a shop
            ?>
            <h5 class="card-title fw-semibold mb-4"><?=htmlspecialchars($shops[0]['ComName'])?></h5>
            <p class="mb-0">Please Select Your Shop</p>

            <div class="m-3">
              <div class="container-fluid row">
                <?php
                foreach ($shops as $shop)
                {
                  $reason = ShopAccess::unavailableReason($shop, $userType);
                  ?>
                  <div class="col-md-3 p-1">
                    <button type="button" class="card synnex-shop" data-shop-id="<?=(int)$shop['SHID']?>" data-shop-name="<?=htmlspecialchars($shop['ShopName'])?>" <?=$reason !== null ? 'disabled' : ''?>>
                      <div class="w-100 p-2">
                        <div class="row">
                          <div class="col-md-12 " style="display:flex; justify-content:center;">
                            <img src="../Assets/Images/icons/shop.png" class="w-50" alt="">
                          </div>
                          <div class="col-md-12 m-2">
                            <h6 class="card-title mb-4 text-center" style="font-size:14px; color:<?=$reason !== null ? '#ff0000' : '#5d87ff'?>;">
                              <?=htmlspecialchars($shop['ShopName'])?>
                            </h6>
                          </div>
                          <?php
                          if($reason !== null)
                          {
                            ?>
                            <div class="col-md-12 m-2">
                              <h6 class="card-title mb-4 text-center" style="font-size:14px; color:#ff0000;">
                                <b><?=ShopAccess::errorMessage($reason)?></b>
                              </h6>
                            </div>
                            <?php
                          }//company closed to this user
                          ?>
                        </div>
                      </div>
                    </button>
                  </div>
                  <?php
                }//foreach shop
                ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Shop login: same look as the sign in card on Public/login.php -->
  <div class="modal fade" id="shop_login_modal" tabindex="-1" aria-labelledby="shop_login_title" aria-hidden="true" style="background: #00000075;">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body p-4">
          <button type="button" class="btn-close float-end" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="text-center py-3">
            <img src="../Assets/Images/synnex_logo.png" width="200" alt="">
          </div>
          <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
            <img src="../Assets/Images/icons/shop.png" width="32" alt="">
            <h5 class="mb-0 fw-semibold" id="shop_login_title">Sign in to <span id="shop_login_name"></span></h5>
          </div>
          <p class="text-center" id="shop_login_error" style="color:#ff0000; font-weight:bold; display:none;"></p>
          <form action="../Controller/shopController.php" method="POST">
            <input type="hidden" name="csrf_token" id="shop_login_csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
            <input type="hidden" name="shop_id" id="shop_login_shop_id" value="">
            <div class="mb-3">
              <label for="shop_user_name" class="form-label">Username</label>
              <input type="text" name="user_name" class="form-control" id="shop_user_name" maxlength="50" autocomplete="username" required>
            </div>
            <div class="mb-4">
              <label for="shop_user_pwd" class="form-label">Password</label>
              <input type="password" class="form-control" id="shop_user_pwd" name="user_pwd" autocomplete="current-password" required>
            </div>
            <div class="d-flex align-items-center justify-content-between mb-4">
              <div class="form-check">
                <input class="form-check-input primary" type="checkbox" id="shop_show_password">
                <label class="form-check-label text-dark" for="shop_show_password">
                  Show Password
                </label>
              </div>
            </div>
            <input type="submit" class="btn btn-primary w-100 py-8 fs-4 mb-2 rounded-2 btn-sign-in" value="Sign In" name="btn_shop_login">
            <button type="button" class="btn bg-danger-subtle text-danger w-100 rounded-2" data-bs-dismiss="modal">Cancel</button>
          </form>
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
      var modalEl = document.getElementById('shop_login_modal');
      var modal = new bootstrap.Modal(modalEl);
      var signedInUser = <?=dashboard_js($user_name)?>;

      function openShopLogin(shopId, shopName, username, error)
      {
        $('#shop_login_shop_id').val(shopId);
        $('#shop_login_name').text(shopName);
        $('#shop_user_name').val(username);
        $('#shop_user_pwd').val('').prop('type', 'password');
        $('#shop_show_password').prop('checked', false);
        if (error)
        {
          $('#shop_login_error').text(error).show();
        }
        else
        {
          $('#shop_login_error').hide().text('');
        }
        modal.show();
      }//open shop login

      $('.synnex-shop').on('click', function(){
        openShopLogin($(this).data('shop-id'), $(this).data('shop-name'), signedInUser, '');
      });

      modalEl.addEventListener('shown.bs.modal', function(){
        $($('#shop_user_name').val() ? '#shop_user_pwd' : '#shop_user_name').trigger('focus');
      });

      $('#shop_show_password').on('change', function(){
        $('#shop_user_pwd').prop('type', this.checked ? 'text' : 'password');
      });

      <?php
      if($shop_login_error !== null)
      {
        ?>
        //the last shop login was refused: reopen it with the reason
        var refused = <?=dashboard_js($shop_login_error)?>;
        var card = $('.synnex-shop[data-shop-id="' + refused.shop_id + '"]');
        openShopLogin(refused.shop_id, card.length ? card.data('shop-name') : '', refused.username, refused.message);
        <?php
      }//refused shop login
      ?>
    });
  </script>
</body>

</html>
