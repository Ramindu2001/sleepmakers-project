
<?php 
  $shopObj = new Shop();
  $shopData = $shopObj->getOneShop($_SESSION['shop_id']);         
  $companyData = $shopObj->getCompanyONE($shopData[0]['Company_CMID']);         
  $company_logo = $shopData[0]['ComLogo'];
  $shop_name = $shopData[0]['ShopName'];
?>

<style>
    @media (max-width: 1200px) 
    {
      .loader .text-center 
      {
        width: 50%;
        margin-top: -70px;
        text-align: center !important;
      }
      #headerCollapse3
      {
        display: none !important;
      }
      #headerCollapse2
      {
        display: none !important;
      }
  }
  </style>
<header class="app-header">
  <nav class="navbar navbar-expand-lg navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item d-block laptop">
        <a class="nav-link sidebartoggler nav-icon-hover" id="headerCollapse3" href="javascript:void(0)">
          <i class="ti ti-menu-2"></i>
        </a>
        <a class="nav-link sidebartoggler nav-icon-hover" style="display:none;" id="headerCollapse2" href="javascript:void(0)">
          <i class="ti ti-menu-2"></i>
        </a>
      </li>
      <li class="nav-item d-block d-xl-none mobile">
        <a class="nav-link sidebartoggler nav-icon-hover" id="headerCollapse" href="javascript:void(0)">
          <i class="ti ti-menu-2"></i>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link nav-icon-hover" href="javascript:void(0)" id="showHoldList" data-toggle="tooltip" data-placement="bottom" title="Hold Invoices">
            <i class="ti ti-clock-pause"></i>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link nav-icon-hover" href="javascript:void(0)" id="showShortcutList" data-toggle="tooltip" data-placement="bottom" title="Shortcut Keys">
          <i class="ti ti-info-circle"></i>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link nav-icon-hover" href="javascript:void(0)" id="customerDisplayBtn" onclick="openCustomerDisplay()" data-toggle="tooltip" data-placement="bottom" title="Customer Display">
          <i class="ti ti-device-tv"></i>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link nav-icon-hover" href="javascript:void(0)" id="toggleImagesBtn" onclick="toggleProductImages()" data-toggle="tooltip" data-placement="bottom" title="Toggle Product Images">
          <i class="ti ti-photo"></i>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link nav-icon-hover" href="javascript:void(0)" id="fullscreenBtn" onclick="toggleFullscreen()" data-toggle="tooltip" data-placement="bottom" title="Toggle Fullscreen">
          <i class="ti ti-maximize"></i>
        </a>
      </li>
    </ul>
    <div class="navbar-collapse justify-content-center px-0" id="navbarNav">
      <a href="../Public/home.php" target="_blank" rel="noopener noreferrer" class="w-20">
        <img src="../Assets/Images/synnex_logo.png" alt="" class="w-100">
      </a>      
    </div>
    <div class="navbar-collapse justify-content-end px-0" id="navbarNav">
      <ul class="navbar-nav flex-row ms-auto align-items-center justify-content-end">
        
        <li class="nav-item dropdown">
          <a class="nav-link nav-icon-hover" href="javascript:void(0)" id="drop2" data-bs-toggle="dropdown"
            aria-expanded="false">
            <?php 
            $complogo="../Assets/Images/Company_Logos/$company_logo";
            if(file_exists($complogo))
            {
              $complogoa="../Assets/Images/Company_Logos/".$company_logo;
            }
            else
            {
              $complogoa="../Assets/Images/Company_Logos/synnex.png";
            }
            ?>
            <img src="<?php echo $complogoa;?>" alt="CL" width="75">
          </a>
          <div class="dropdown-menu dropdown-menu-end dropdown-menu-animate-up" aria-labelledby="drop2">
            <p class="text-center" style="font-size:12px; margin:0;"><?=$companyData[0]['ComName']?>-<?=$shopData[0]['ShopName']?></p>
            <p class="text-center" style="font-size:12px; margin:0;"><?=$shopData[0]['ShopNo']?></p>
            <div class="message-body">
              <a href="javascript:void(0)" class="d-flex align-items-center gap-2 dropdown-item" style="padding:5px 16px !important;">
                <i class="ti ti-user " style="font-size:12px;"></i>
                <p class="mb-0" style="font-size:12px;">My Profile</p>
              </a>
              <?php 
              if($userType==1)
              {
                ?>
                <a href="../Public/switchshop.php" class="d-flex align-items-center gap-2 dropdown-item" style="padding:5px 16px !important;">
                  <i class="ti ti-arrows-exchange-2" style="font-size:12px;"></i>
                  <p class="mb-0" style="font-size:12px;">Switch Shop</p>
                </a>
                <?php
              }
              ?>
              <a href="javascript:void(0)" id="user-password-change" class="d-flex align-items-center gap-2 dropdown-item" style="padding:5px 16px !important;">
              <i class="ti ti-user " style="font-size:12px;"></i>
              <p class="mb-0" style="font-size:12px;">Password Change</p>
              </a>
              <a href="../Public/logout.php" class="btn btn-outline-primary mx-3 mt-2 d-block">Logout</a>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </nav>
      </header>
      <script>
        
      function authcheck() {
        $.ajax({
          url:"../Includes/newauthcheck.php",
          method:"post",
          success:function(response)
          {
            
            if(response==1)
            {

            }
            else if(response==0)
            {
              window.location.href="../Public/logout.php";
            }
            else if(response==-1)
            {
              window.location.href="../Public/switchshop.php";
            }
            else
            {
              window.location.href="../Public/logout.php";
            }
          }
        })
      }
      setInterval(function(){
        authcheck();
      }, 10000);
      </script>
      <script src="../Assets/jquery/header.js">
      </script>
      <?php 
      if(isset($noRight) && $noRight==1)
      {

      }
      else
      {
        include '../View/right-sidebar.php'; 
      }
      
      ?>
      <script>
        $("#add-prescription").click(function(){
            $("#prescription_modal").modal("toggle");
        });
      </script>
      <?php 
      ?>