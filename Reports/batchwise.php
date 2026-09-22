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
  include '../View/loader.php';
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
            <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Item Batch Report</h5>            
            <div class="card">
                <div class="card-body">

                <div class="container-fluid">
                    <!-- <div class="form-group">
                        <label for="searchInput" class="form-label">Search: </label>
                        <input type="text" id="searchInput" class="w-30 mt-10 form-control">
                    </div> -->
                    <?php 
                $shop = $shopObj->getOneShop($shop_id);
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
                                <th>Item Name</th>
                                <th>Product Code</th>
                                <th>Batch</th>
                                <th>Current Qty</th>
                                <th>Unit Selling Price</th>
                                <th>Unit Purchase Price</th>
                                <th>Total Selling Price</th>
                                <th>Total Purchase Price</th>
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
                                    if($common_stock==1)
                                    {
                                        $sql_1 = "SELECT DISTINCT products_PDID FROM inventory 
                                        INNER JOIN shop ON shop.SHID = inventory.shop_SHID                                        
                                        WHERE shop.Company_CMID = ".$company_id.";";
                                    }
                                }
                                else
                                {
                                    $sql_1 = "SELECT DISTINCT products_PDID FROM inventory WHERE shop_SHID = ".$shop_id." AND CurrentQty>0;";
                                    if($common_stock==1)
                                    {
                                        $sql_1 = "SELECT DISTINCT products_PDID FROM inventory 
                                        INNER JOIN shop ON shop.SHID = inventory.shop_SHID                                        
                                        WHERE shop.Company_CMID = ".$company_id.";";
                                    }
                                }
                                $sql_1 ="SELECT * FROM `inventory` i
                                            INNER JOIN pricehistory ph ON ph.Inventory_INID=i.INID
                                            INNER JOIN products p ON p.PDID=i.products_PDID
                                            WHERE i.shop_SHID='$shop_id' AND i.CurrentQty > 0 ORDER BY p.ItemName ASC;";
                                if($common_stock==1)
                                {
                                    $sql_1 ="SELECT * FROM `inventory` i
                                                INNER JOIN pricehistory ph ON ph.Inventory_INID=i.INID
                                                INNER JOIN products p ON p.PDID=i.products_PDID
                                                INNER JOIN shop s ON s.SHID=i.shop_SHID
                                                WHERE s.Company_CMID='$company_id' AND i.CurrentQty > 0 ORDER BY p.ItemName ASC;";

                                }
                                $dbData_1 = $dbObj->getData($sql_1);
                                $i = 1;
                                $total_selling=0;
                                $total_purchase=0;
                              


                                foreach($dbData_1 as $row)
                                {

                                
                                    $dbData = $dbObj->getData($sql);
                                    $totalSellingPrice = $row['SellingPrice'] * $row['CurrentQty'];
                                    $totalPurchasePrice = $row['PurchasePrice'] * $row['CurrentQty'];
                                    $total_selling += $totalSellingPrice;
                                    $total_purchase += $totalPurchasePrice; 
                               

                                    ?>
                                    <tr data-id="<?php echo $product_id;?>">
                                        <td><?php echo $i;?></td>
                                        <td><?php echo $row['ItemName'];?></td>
                                        <td><?php echo $row['ProductNo'];?></td>
                                        <td><?php echo $row['BatchID'];?></td>
                                        <td><?php echo $row['CurrentQty'] + 0;?></td>
                                        <td><?php echo $row['SellingPrice'] + 0;?></td>
                                        <td><?php echo $row['PurchasePrice'] + 0;?></td>
                                        <td><?php echo $totalSellingPrice + 0;?></td>
                                        <td><?php echo $totalPurchasePrice + 0;?></td>
                                    </tr>
                                <?php 
                                    $i++;
                                }  //foreach
                            ?>
                        </tbody>
                        <t>
                          <th colspan="7" style="text-align:right">Total Selling:</th>
                          <th><?php echo number_format($total_selling, 2); ?></th>
                          <tr><th colspan="7" style="text-align:right;">Total Purchase:</th>
                          <th><?php echo number_format($total_purchase,2);?></th>
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
                                            {text: reportname + ",\n", bold: true, fontSize: 14},
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