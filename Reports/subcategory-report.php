<?php 
require_once '../Includes/includes.php';
require_once '../Includes/authcheck.php';
?>

<!doctype html>
<html lang="en">


<head>
  <?php 
  require_once '../View/head.php';
  require_once '../View/loader.php';
  require_once '../View/datatables.php';
  ?>  
</head>
<body>
<!--  Body Wrapper -->
<div class="h-100vh">
<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    <!-- Sidebar Start -->
    <?php 
    require_once '../View/sidebar.php';
    $feature_id=32;
    include '../Includes/viewPermission.php';
    if($userType==1 || $print==1)
    {
        
    }
    else
    {
        ?>
        <script>
            setInterval(function(){
                $(".dt-buttons").addClass("d-none");  
            }, 100);
        </script>
        <?php
    }
    ?>
    <!--  Sidebar End -->
    <!--  Main wrapper -->
    <div class="body-wrapper">
        <?php 
        require_once '../View/header.php';
        require_once "../View/modals/main-category.php";
         $shops= new Shop();
         $shop=$shops->getOneShop($shop_id);
        ?>
         <div class="container-fluid">
         <input type="hidden" name="" id="shop_name" value="<?=$shop[0]['ShopName']?>">
            <input type="hidden" name="" id="shop_address_one" value="<?=$shop[0]['AddressLineOne']?>">
            <input type="hidden" name="" id="shop_address_two" value="<?=$shop[0]['AddressLineTwo']?>">
            <input type="hidden" name="" id="shop_city" value="<?=$shop[0]['City']?>">  
            <input type="hidden" name="" id="shop_number" value="<?=$shop[0]['PhoneNumber']?>">
            <input type="hidden" name="" id="title" value="Sub Category List">
         <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Sub Category List</h5>
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
                    <table class="table table-hover" id="tbl_category">
                    <thead>
                        <tr>
                            <th>Sl</th>
                            <th>Sub Category No</th>
                            <th>Sub Category Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $subcategoryObj = new Report();
                        $subcategoryData = $subcategoryObj->subcategories($shop_id);
                        $i=1;
                        foreach ($subcategoryData as $row) 
                        {
                            ?>
                            <tr>
                                <td> <?php echo $i;?> </td>
                                <td> <?php echo $row['SubCatNo'] ?></td>   
                                <td> <?php echo $row['SubCatName']?></td>
                            </tr>
                            <?php 
                            $i++;
                        }
                        ?>
                    </tbody>
                </table>
                </div>
                </div>
                
<!--    <div id="checkListStockList_filter" class="dataTables_filter">
                    <label>
                        Search:
                        <input type="search" id="searchInput" class="form-control input-sum" placeholder="Search" aria-controls="checkListStockList">
                    </label>
                </div> -->
            </div>
        </div>
    </div>
</div>
</div>
    <?php require_once '../View/footer.php';?>
    <script src="../Assets/jquery/sale_summary.js"></script>
    <!-- <script src="../Assets/libs/jquery/dist/jquery.min.js"></script> -->
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="../Assets/js/dashboard.js"></script>
</body>
</html>
