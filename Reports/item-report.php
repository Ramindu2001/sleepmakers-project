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
    $feature_id=34;
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
         $shops = new Shop();
         $shop = $shops->getOneShop($shop_id);
        ?>
         <div class="container-fluid">
         <input type="hidden" name="" id="shop_name" value="<?=$shop[0]['ShopName']?>">
            <input type="hidden" name="" id="shop_address_one" value="<?=$shop[0]['AddressLineOne']?>">
            <input type="hidden" name="" id="shop_address_two" value="<?=$shop[0]['AddressLineTwo']?>">
            <input type="hidden" name="" id="shop_city" value="<?=$shop[0]['City']?>">  
            <input type="hidden" name="" id="shop_number" value="<?=$shop[0]['PhoneNumber']?>">
            <input type="hidden" name="" id="title" value="Item Report">
            <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Item Report</h5>
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
                    <table class="table table-hover" id="tbl_category">
                    <thead>  
                        <tr>
                            <th>SL</th>
                            <th>BarCode No</th>
                            <th>CategoryName</th>
                            <th>Item Name</th>
                            <th>Product Description</th>
                            <th>Current Qty</th>
                            <th>Unit Name</th>
                            <th>Product Purchase Price</th>
                            <th>Product Selling Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $product= new Report();
                        $shop_id = $shop[0]['SHID'];
                        $productData = $product->item($shop_id);
                        $i = 1;
                        $product_purchase =0;
                        $product_selling=0;
                        foreach ($productData as $row) 
                        {
                            $product_purchase +=$row['ProdPurchasePrice'];
                            $product_selling +=$row['ProdSellPrice'];
                            ?>
                            <tr>
                                <td> <?php echo $i;?></td>
                                <td> <?php echo $row['Barcode']?></td>
                                <td> <?php echo $row['SubCatName'] ?></td>
                                <td> <?php echo $row['ItemName'] ?></td>
                                <td> <?php echo $row['ProdDescription'] ?></td>
                                <td> <?php echo $row['CartonQty'] ?></td>
                                <td> <?php echo $row['UnitName'] ?></td>
                                <td> <?php echo $row['ProdPurchasePrice'] ?></td>
                                <td> <?php echo $row['ProdSellPrice'] ?></td>
                            </tr>
                            <?php 
                            $i++;
                        }
                        ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="7"></td>
                        <td><strong>Total Purchase Price:</strong></td>
                        <td><strong><?php echo $product_purchase; ?></strong> <input type="hidden" name="product_purchase" id="product_purchase" value="<?=$product_purchase?>"></td>
                    </tr>
                    <tr>
                        <td colspan="7"></td>
                        <td><strong>Total Selling Price:</strong></td>
                        <td><strong><?php echo $product_selling; ?></strong> <input type="hidden" name="product_selling" id="product_selling" value="<?=$product_selling?>"></td>
                    </tr>
                </tfoot>
                </table>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php require_once '../View/footer.php';?> 
    <script src="../Assets/jquery/item.js"></script>
    <!-- <script src="../Assets/libs/jquery/dist/jquery.min.js"></script> -->
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="../Assets/js/dashboard.js"></script>
</body>
</html>
