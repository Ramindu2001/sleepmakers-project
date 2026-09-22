<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';

$shop_id = $_SESSION['shop_id'];

?>
<!doctype html>
<html lang="en">
<head>
  <?php 
  include '../View/head.php';
  // include '../View/loader.php';
  ?>
<link rel="stylesheet" href="../Assets/css/datatables.min.css">
<script src="../Assets/js/datatables.min.js"></script>
</head>
<body>
<!--  Body Wrapper -->
<div class="h-100vh">
<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    <!-- Sidebar Start -->
    <?php 
    include '../View/sidebar.php';
    if($userType==0)
    {
        $userObj=new User();
        $feature_id=1;
        $checkview=$userObj->userAcces($userRole_id,$feature_id);
        $view=$checkview[0]["is_view"];
        $edit=$checkview[0]["is_edit"];
        $view=$checkview[0]["is_view"];
        $delete=$checkview[0]["is_delete"];
        $verify=$checkview[0]["is_verify"];
        $print=$checkview[0]["is_print"];
        if($view==1)
        {

        }
        else
        {
            ?>
            <script>
                window.location.href = "./home.php";
            </script>
            <?php
        }
    }
    ?>
    <!--  Sidebar End -->
    <!--  Main wrapper -->
    <div class="body-wrapper">
        <!--  Header Start -->
        <?php 
        include '../View/header.php';
        include '../View/modals/batch-price.php';
        ?>
        <!--  Header End -->

        <div class="container-fluid">
            <!-- messages -->
        <div class="container">

        </div>
            <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Shop Store</h5>            
            <div class="card">
                <div class="card-body">

                <div class="container-fluid">
                    <!-- <div class="form-group">
                        <label for="searchInput" class="form-label">Search: </label>
                        <input type="text" id="searchInput" class="w-30 mt-10 form-control">
                    </div> -->

                    <?php 
                $shop = $shopObj->getOneShop($shop_id);
                if($shopObj->hasMinus($shop_id))
                {

                }
                else
                {
                    ?>
                    <form action="" method="get">
                        <div class="d-md-flex align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="noStock" name="noStock"
                                <?php 
                                if(isset($_GET["noStock"]) && $_GET["noStock"]==1)
                                {
                                    ?>
                                    checked
                                    <?php
                                }
                                ?>
                                >
                                <label class="form-check-label" for="noStock">
                                    Show No Stock Items
                                </label>
                            </div>
                            <div class="ms-auto mt-3 mt-md-0">
                                <button type="submit" class="btn btn-primary hstack gap-6">
                                <i class="ti ti-send fs-4"></i>
                                Filter
                                </button>
                            </div>
                        </div>
                    </form>
                    <?php
                }
                ?>

                <input type="hidden" name="" id="shop_name" value="<?=$shop[0]['ShopName']?>">
                <input type="hidden" name="" id="shop_address_one" value="<?=$shop[0]['AddressLineOne']?> ">
                <input type="hidden" name="" id="shop_address_two" value="<?=$shop[0]['AddressLineTwo']?>">
                <input type="hidden" name="" id="shop_city" value="<?=$shop[0]['City']?>">
                <input type="hidden" name="" id="shop_number" value="<?=$shop[0]['PhoneNumber']?> ">
                <input type="hidden" name="" id="title" value="Store Report">


                    <div class="table-responsive">
                      <table class="table table-hover" id="tbl_inventory">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Image</th>
                                <th>Barcode</th>
                                <th>Item Name</th>
                                <th>Current Qty</th>
                                <th>Sold Qty</th>
                                <th>Return Qty</th>
                                <th>Transfer In Qty</th>
                                <th>Transfer Out Qty</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        
                        <tbody>
                            <?php 

                                $shopObj = new Shop();
                                $dbObj = new DBTransactions();
                                //get company stat
                                $sql = "SELECT * FROM shop
                                INNER JOIN company ON company.CMID = shop.Company_CMID
                                WHERE SHID = ".$shop_id.";";
                                $common_stock = floatval($shopData[0]['is_commonStock']);
                                $company_id = floatval($shopData[0]['CMID']);

                                if($shopObj->hasMinus($shop_id))
                                {
                                    $sql_1 = "SELECT DISTINCT products_PDID FROM inventory WHERE shop_SHID = ".$shop_id.";";
                                }
                                else
                                {
                                    $sql_1 = "SELECT DISTINCT products_PDID FROM inventory WHERE shop_SHID = ".$shop_id." AND CurrentQty > 0;";
                                    if(isset($_GET["noStock"]) && $_GET["noStock"]==1)
                                    {
                                        $sql_1 = "SELECT DISTINCT products_PDID FROM inventory WHERE shop_SHID = ".$shop_id." AND CurrentQty >= 0;";
                                    }
                                }
                                
                                $dbData_1 = $dbObj->getData($sql_1);
                                $count = 0;

                                foreach($dbData_1 as $row)
                                {
                                    $count += 1;
                                    $product_id = $row['products_PDID'];

                                    $sql = "SELECT sum(CurrentQty) as totalCurrentQty, sum(BillQty) as totalBillQty, sum(ReturnQty) as totalReturnQty, sum(TransferInQty) as totalTransferIn, sum(TransferOutQty) as totalTransferOut, ProdImage, Barcode, ItemName FROM inventory
                                    INNER JOIN products ON products.PDID = inventory.products_PDID
                                    WHERE inventory.products_PDID = ".$row['products_PDID']." AND products.ItemType='P'  AND products.ProductStat='1' AND inventory.shop_SHID = ".$shop_id.";";
                                
                                    $dbData = $dbObj->getData($sql);
                                    
                                    ?>
                                    <tr data-id="<?php echo $product_id;?>">
                                        <td><?php echo $count;?></td>
                                        <td>
                                            <?php 
                                            $filepath="../Assets/Images/prod_images/".$dbData[0]['ProdImage'];
                                            if(!empty($dbData[0]['ProdImage']))
                                            {
                                                $filepath="../Assets/Images/prod_images/".$dbData[0]['ProdImage'];
                                            }
                                            else
                                            {
                                                $filepath="../Assets/Images/icons/product.png";
                                            }
                                            if(!file_exists($filepath))
                                            {
                                                $filepath="../Assets/Images/icons/product.png";
                                            }
                                            ?>
                                                <img src="<?=$filepath?>" alt="Product Image" style="width: 25px; height:auto;">
                                                
                                        </td>
                                        <td><?php echo $dbData[0]['Barcode'];?></td>
                                        <td><?php echo $dbData[0]['ItemName'];?></td>
                                        <td><?php echo $dbData[0]['totalCurrentQty'] + 0;?></td>
                                        <td><?php echo $dbData[0]['totalBillQty'] + 0;?></td>
                                        <td><?php echo $dbData[0]['totalReturnQty'] + 0;?></td>
                                        <td><?php echo $dbData[0]['totalTransferIn'] + 0;?></td>
                                        <td><?php echo $dbData[0]['totalTransferOut'] + 0;?></td> 
                                        <td>
                                            <button class="btn border border-primary text-primary btn_view_price"><i class="ti ti-eye"></i> Price</button>
                                        </td>
                                    </tr>
                                <?php 
                                }  //foreach
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
</div>
<!--  Body Wrapper End -->

    <!-- footer Start  -->
    <?php include '../View/footer.php';?> 
    <!-- footer End  -->

    <script src="../Assets/jquery/search.js"></script>
    <script src="../Assets/jquery/store.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>

    <script>
        $(document).ready(function(){
            var shop_name = $("#shop_name").val();
            var shop_address_one = $('#shop_address_one').val();
            var shop_address_two = $('#shop_address_two').val();
            var shop_city = $('#shop_city').val();
            var shop_number = $('#shop_number').val();
            var reportname=$("#title").val();

            $("#tbl_inventory").DataTable({
               
                paging: true,
                lengthChange: true,
                searching: true,
                //pageLength: 10,

                layout:{
                    topStart:{
                        buttons:[
                            //====================== PDF
                            {
                                extend: 'pdf',
                                customize: function (doc){
                                    doc.content.splice(0,1,{
                                        text: [
                                            {text: shop_name + "\n", bold: true, fontSize: 16},
                                            {text: shop_address_one + ",\n", bold: true, fontSize: 12},
                                            {text: shop_address_two + ",\n", bold: true, fontSize: 12},
                                            {text: shop_number + ",\n", bold: true, fontSize: 12},
                                            {text: reportname + ",\n", bold: true, fontSize: 14}
                                        ]
                                    }) 
                                },
                                download: 'open'
                            },
                            
                            //===================== excel
                            {
                                extend: 'excel',
                                title: reportname,
                            },
    
                        ]
                    } 
            }
        })
    });
    </script>
</body>
</html>