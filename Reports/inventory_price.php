<?php 
// require_once '../Includes/includes.php';

include "../Includes/includes.php";
require_once '../Includes/authcheck.php';

$shopObj = new Shop();
$shop_id = $_SESSION['shop_id'];
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
    $feature_id=38;
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
        $shops= new Shop();
        $shop=$shops->getOneShop($shop_id);
        ?>
        <div class="container-fluid">
            <input type="hidden" name="" id="shop_name" value="<?=$shop[0]['ShopName']?>">
            <input type="hidden" name="" id="shop_address_one" value="<?=$shop[0]['AddressLineOne']?> ">
            <input type="hidden" name="" id="shop_address_two" value="<?=$shop[0]['AddressLineTwo']?>">
            <input type="hidden" name="" id="shop_city" value="<?=$shop[0]['City']?>">
            <input type="hidden" name="" id="shop_number" value="<?=$shop[0]['PhoneNumber']?> ">
            <input type="hidden" name="" id="title" value="Inventory Summary Report">
         <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Inventory Price Summary</h5>
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
                        <div class="dt-buttons">
                            <!-- <button class="buttons-pdf buttons-html5 btn btn-default w-10" tabindex="">

                            </button> -->

                        </div>
                    <table class="table table-hover" id="tbl_category">
                    <thead>
                        <tr> 
                            <th>No</th>
                            <th>Item</th>
                            <th>Current Quantity</th>
                            <th>Purchase Price</th>
                            <th>Selling Price</th>
                            <th>Total Purchase</th>
                            <th>Total Selling</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                         $total_purchase_sum = 0;
                         $total_selling_sum = 0;
                        $InvObj = new Report();
                        
                        $dbObj = new DBTransactions();

                        //get company stat
                        $sql = "SELECT * FROM shop
                        INNER JOIN company ON company.CMID = shop.Company_CMID
                        WHERE SHID = ".$shop_id.";";

                        $shopData = $dbObj->getData($sql);
                        $multi_category = floatval($shopData[0]['is_multicategory']);
                        $company_id = floatval($shopData[0]['CMID']);

                        if($shopObj->hasMinus($shop_id))
                        {
                            if($multi_category == 1)
                            {
                                $sql = "SELECT  products_PDID, Barcode, ItemName, CurrentQty FROM inventory 
                                INNER JOIN products ON products.PDID = inventory.products_PDID 
                                INNER JOIN shop ON shop.SHID = products.shop_SHID
                                WHERE shop.Company_CMID = ".$company_id." GROUP BY products.PDID,inventory.BatchID;";
                            }//has multi category
                            else
                            {
                                $sql = "SELECT  products_PDID, Barcode, ItemName, CurrentQty FROM inventory 
                                INNER JOIN products ON products.PDID = inventory.products_PDID 
                                WHERE inventory.shop_SHID = ".$shop_id." GROUP BY products.PDID,inventory.BatchID;";
                            }

                        }//has minus
                        else
                        {
                            if($multi_category == 1)
                            {
                                $sql = "SELECT  products_PDID, Barcode, ItemName, CurrentQty FROM inventory 
                                INNER JOIN products ON products.PDID = inventory.products_PDID 
                                INNER JOIN shop ON shop.SHID = products.shop_SHID
                                WHERE shop.Company_CMID = ".$company_id." AND CurrentQty>0 GROUP BY products.PDID,inventory.BatchID;";
                            }//has multi category
                            else
                            {
                                $sql = "SELECT  products_PDID, Barcode, ItemName, CurrentQty FROM inventory 
                                INNER JOIN products ON products.PDID = inventory.products_PDID 
                                WHERE inventory.shop_SHID = ".$shop_id." AND CurrentQty>0 GROUP BY products.PDID,inventory.BatchID;";
                            }
                        }//no minus

                        $row_count = 0;
                        $invData = $dbObj->getData($sql);

                        foreach($invData as $row)
                        {
                            // print_r($row);
                            $row_count += 1;
                            $product_id = $row['products_PDID'];
                            $barcode = $row['Barcode'];
                            $item_name = $row['ItemName'];

                            if($shopObj->hasMinus($shop_id))
                            {
                                $sql_1 = "SELECT * , sum(CurrentQty) as TotalQty, pricehistory.PurchasePrice AS PurchasePrice, pricehistory.SellingPrice AS SellingPrice  FROM inventory 
                                INNER JOIN pricehistory ON pricehistory.Inventory_INID = inventory.INID
                                INNER JOIN products ON products.PDID = inventory.products_PDID 
                                WHERE inventory.shop_SHID = ".$shop_id." AND inventory.products_PDID = ".$product_id.";";
                            }// has minus
                            else
                            {
                                $sql_1 = "SELECT *, sum(CurrentQty) as TotalQty, pricehistory.PurchasePrice AS PurchasePrice, pricehistory.SellingPrice AS SellingPrice  FROM inventory 
                                INNER JOIN pricehistory ON pricehistory.Inventory_INID = inventory.INID
                                INNER JOIN products ON products.PDID = inventory.products_PDID 
                                WHERE inventory.shop_SHID = ".$shop_id." AND CurrentQty > 0 AND inventory.products_PDID = ".$product_id.";";
                            }//no minus
                            
                            // $sql_1 = "SELECT SUM(CurrentQty) AS totalCurrent, sum(BillQty) as totalBill, sum(ReturnQty) as totalReturn, sum(TransferInQty) as totalTransferIn, sum(TransferOutQty) as totalTransferOut FROM inventory 
                            // WHERE products_PDID= ".$product_id." AND  shop_SHID = ".$shop_id.";";

                          
                            $detailData = $dbObj->getData($sql_1);

                            $total_current_qty = floatval($row["CurrentQty"]);
                            $unit_purchase_price = floatval($detailData[0]['PurchasePrice']);
                            $unit_selling_price = floatval($detailData[0]['SellingPrice']);
                            
                            $total_purchase_price = $total_current_qty * $unit_purchase_price;
                            $total_selling_price = $total_current_qty * $unit_selling_price;

                            // Add to total sum
                            $total_purchase_sum += $total_purchase_price;
                            $total_selling_sum += $total_selling_price;

                            // $total_transfer_in_qty = $detailData[0]['totalTransferIn'];
                            // $total_transfer_out_qty = $detailData[0]['totalTransferOut'];
                            ?>
                            <tr>
                                <td><?php echo $row_count;?></td>
                                <td><?php echo $barcode . "<br>" . $item_name;?></td>
                                <td><?php echo $row["CurrentQty"];?></td>
                                <td><?php echo $unit_purchase_price;?></td>
                                <td><?php echo $unit_selling_price;?></td>
                                <td><?php echo $total_purchase_price;?></td>
                                <td><?php echo $total_selling_price;?></td>
                            </tr>
                            <?php 

                        }//foreach 1

                        // $has_minus = $shopObj->hasMinus($shop_id);
                        // echo $has_minus;
                        // $invData = $InvObj->Inventory($has_minus);
                        ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="5"></td>
                        <td><strong>Total Selling Price:</strong></td>
                        <td><strong><?php echo $total_selling_sum; ?></strong> <input type="hidden" name="total_selling_sum" id="total_selling_sum" value="<?=$total_selling_sum?>"></td>
                    </tr>
                    <tr>
                        <td colspan="5"></td>
                        <td><strong>Total Purchase Price:</strong></td>
                        <td><strong><?php echo $total_purchase_sum; ?></strong> <input type="hidden" name="total_purchase_sum" id="total_purchase_sum" value="<?=$total_purchase_sum?>"></td>
                    </tr>
                </tfoot>
                </table>
                </div>
                </div>
                <!-- 
                <div id="checkListStockList_filter" class="dataTables_filter">
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
<script src="../Assets/jquery/inventory_price.js"></script>
<!-- <script src="../Assets/libs/jquery/dist/jquery.min.js"></script> -->
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="../Assets/js/dashboard.js"></script>
</body>
</html>
