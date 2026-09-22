<?php 
require_once '../Includes/includes.php';
require_once '../Includes/authcheck.php';

$shop_id = $_SESSION['shop_id'];
$dbObj = new DBTransactions();

$supplier_id = 0;
if(isset($_GET['supplier_id']))
{
    $supplier_id = $_GET['supplier_id'];
}//sup set
else
{
    $sql = "SELECT * FROM suppliers WHERE shop_SHID = '.$shop_id.';";
    $supData = $dbObj->getData($sql);
    $supplier_id = $supData[0]['SPID'];
}//else



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
    $feature_id=35;
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
            <input type="hidden" name="" id="title" value="Supplier List"> 
        <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Supplier Purchase</h5>
        <div class="container">
            <form action="../Controller/ReportController.php" method="POST">
                <select name="cmb_suppliers" id="cmb_suppliers">
                </select>
                <button type="submit" name="btn_search_supplier" class="btn btn-primary">Search</button>
            </form>
        </div>
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
                    <table class="table table-hover" id="tbl_category">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Date</th>
                            <th>Invoice No</th>
                            <th>Item Count</th>
                            <th>Purchase Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 

                        $sql = "SELECT * FROM grnheader
                        INNER JOIN suppliers ON suppliers.SPID = grnheader.Suppliers_SPID
                        WHERE SPID = '.$supplier_id.';";

                        $supplierData = $dbObj->getData($sql);
                        $row_count = 0;
                        $total_amount =0;
                        foreach($supplierData as $row)
                        {
                            $row_count += 1;
                            $total_amount +=$row['TotalPurchasePrice'];
                            ?> 
                            <tr>
                                <td><?php echo $row_count;?></td>
                                <td><?php echo $row['EffectiveDate'];?></td>
                                <td><?php echo $row['InvoiceNo'];?></td>
                                <td><?php echo $row['ItemCount'];?></td>
                                <td><?php echo $row['TotalPurchasePrice'];?></td>
                            </tr>
                            <?php
                        }//foreach
                        ?>
                    </tbody>
                    <tfoot>
                    <td colspan="4"></td> 
                    <td class="text-end"><strong>Total Purchase Amount: <?php echo number_format($total_amount, 2); ?></strong> <input type="hidden" name="total_amount" id="total_amount" value="<?=$total_amount?>"></td>
                    </tfoot>
                </table>
                </div>
                </div>
                <!--<div id="checkListStockList_filter" class="dataTables_filter">
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
    <script src="../Assets/jquery/supplier_purchase.js"></script>
    <script src="../Assets/jquery/Supplier.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script> 
    <script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="../Assets/js/dashboard.js"></script>
</body>
</html>
