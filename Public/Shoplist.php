<?php 
include "../Includes/includes.php";
include '../Includes/authcheck.php';
if($userObj->checkusertype($_SESSION["user_id"])==1)
{

}
else
{
    ?>
    <script>
        window.location.href = "../Public/home.php";
    </script>
    <?php
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php 
 include '../View/head.php';
 // include '../View/loader.php';

  ?>
  <style>
    .text-danger 
    {
        color: #ff0000 !important;
        font-weight: 700;
        font-size: 10px;
    }
  </style>
</head>

<body>

<?php     
    include '../View/modals/shopmodel.php';
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
                    <div class="container">
                        <?php 
                        if(isset($_SESSION['shop_update']))
                        {
                            if($_SESSION['shop_update'] == 0)
                            {
                                ?>
                                <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                    Please select a <strong>Company </strong>from dropdown.
                                </div>
                                <?php 
                            }//no entry
                            else if($_SESSION['shop_update'] == 1)
                            {
                                ?>
                                <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                    Please select a <strong>Stock Type </strong>from dropdown.
                                </div>
                                <?php 
                            }//no stock type
                            else if($_SESSION['shop_update'] == 2)
                            {
                                ?>
                                <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                    Please enter <strong>Shop Name </strong>.
                                </div>
                                <?php 
                            }//no stock type
                            else if($_SESSION['shop_update'] == 3)
                            {
                                ?>
                                <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                    Please select Images less than  5 MB of <strong>Image Size</strong>.
                                </div>
                                <?php 
                            }//no stock type
                            else if($_SESSION['shop_update'] == 4)
                            {
                                ?>
                                <div class="alert alert-warning alert-dismissible border-0 fade show" role="alert">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    The shop was saved, but <strong>A4 Invoice Print</strong> could not be saved: this database does not have the setting yet. This shop keeps printing the 80mm receipt until it is added.
                                </div>
                                <?php
                            }//A4 setting missing from the database

                            unset($_SESSION['shop_update']);
                        }//session set
                        ?>
                    </div>

                    <h5 class="card-title fw-semibold mb-4">Shops List</h5>
                    
                    <button type="button" class="btn btn-primary rounded-pill ml-1 mb-3" id="btn_Add_Shop_modal">Add New Shop</button>
                    <br>
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                      <table class="table search-table align-middle text-nowrap" id="tbl_shop">
                                        <tr>
                                            <th>Shop Logo</th>  
                                            <th>Receipt Logo</th>                                            
                                            <th>Features</th>                                                 
                                            <th>Features</th>                                                 
                                            <th>Stock Type</th>                                                 
                                            <th>Shop Status</th>                                                 
                                            <th>Action</th>                                                                                               
                                        </tr>    
                                            <?php
                                                $ShopObj = new Shop();
                                                $Shops = $ShopObj->getShops(); 

                                                foreach ($Shops as $row):  
                                            ?>  
                                                <tr>
                                                    <td colspan="6">
                                                        <h2>
                                                        <?php echo $row['ComName'] ." - ". $row['ShopNo'] . " - " . $row['ShopName'];?>
                                                        <?php 
                                                        if($row['SHID']==$shop_id)
                                                        {
                                                            ?>
                                                                <span class="ms-2 mt-2 badge bg-danger"> Current Shop</span><br>
                                                            <?php

                                                        }
                                                        ?>
                                                        </h2>
                                                    </td>
                                                </tr> 
                                                <tr data-id="<?php echo $row['SHID'];?>">
                                                    <td style="display:none;"><?php echo $row['SHID'];?></td><!-- 1 -->
                                                    <td>
                                                        <?php 
                                                        $shopLogo="../Assets/Images/shop_images/".$row['ShopLogo'];
                                                        if($row['ShopLogo']==null)
                                                        {
                                                            $shopLogo="../Assets/Images/shop_images/synnex_logo.png";
                                                        }
                                                        else if(file_exists($shopLogo))
                                                        {
                                                            $shopLogo="../Assets/Images/shop_images/".$row['ShopLogo'];
                                                        }
                                                        else
                                                        {
                                                            $shopLogo="../Assets/Images/shop_images/synnex_logo.png";
                                                        }
                                                        ?>
                                                        <img src="<?=$shopLogo?>" alt="Shop Logo" class="img-fluid rounded shadow" style="width:100px; heigh:auto;">
                                                    </td><!-- 1 -->
                                                    <td>
                                                    <?php 
                                                        $ReceiptLogo="../Assets/Images/shop_images/".$row['ReceiptLogo'];
                                                        if($row['ReceiptLogo']==null)
                                                        {
                                                            $ReceiptLogo="../Assets/Images/shop_images/synnex_logo.png";
                                                        }
                                                        else if(file_exists($ReceiptLogo))
                                                        {
                                                            $ReceiptLogo="../Assets/Images/shop_images/".$row['ReceiptLogo'];
                                                        }
                                                        else
                                                        {
                                                            $ReceiptLogo="../Assets/Images/shop_images/synnex_logo.png";
                                                        }
                                                        ?>
                                                        <img src="<?=$ReceiptLogo?>" alt="Receipt Logo" class="img-fluid rounded shadow" style="width:100px; heigh:auto;">
                                                    </td><!-- 2 -->
                                                    <!--shop detail 1  -->
                                                    <td>
                                                    <?php 
                                                        if($row['is_inventory'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Inventory</span><br>
                                                            <?php
                                                        }//has inventory
                                                        if($row['is_minus'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Allow Minus</span><br>
                                                            <?php
                                                        }//Allow minus
                                                        if($row['is_expire'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Expire</span><br>
                                                            <?php
                                                        }//has inventory
                                                        if($row['is_fixedprice'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Fixed Price</span><br>
                                                            <?php
                                                        }//has inventory
                                                        if($row['is_variation'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Has Variations</span><br>
                                                            <?php
                                                        }//has inventory
                                                        if($row['is_secondlan'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Second Language</span><br>
                                                            <?php
                                                        }//has inventory
                                                        if($row['is_labelprice'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Label Price</span><br>
                                                            <?php
                                                        }//has inventory
                                                        if($row['is_carton'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Carton Qty</span><br>
                                                            <?php
                                                        }//has inventory
                                                        if($row['is_warranty'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Warranty</span><br>
                                                            <?php
                                                        }//has warranty

                                                        if($row['is_credit'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Credit</span><br>
                                                            <?php
                                                        }//has warranty
                                                        if($row['invoice_print'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Invoice Print</span><br>
                                                            <?php
                                                        }//has warranty
                                                        if($row['is_under_cost'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Under Cost</span><br>
                                                            <?php
                                                        }//has under cost
                                                        if(isset($row['is_a4invoice']) && $row['is_a4invoice'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">A4 Invoice Print</span><br>
                                                            <?php
                                                        }//prints A4 invoices
                                                        $comObj = new Company();
                                                        $features=$comObj->getOneShopfeatures($row['SHID']);
                                                        foreach ($features as $feature) 
                                                        {
                                                            if($feature["is_active"]==1)
                                                            {
                                                               ?>
                                                                <span class="mt-2 badge bg-primary"><?=$feature["FeatureName"]?></span><br>
                                                                <?php 
                                                            }
                                                            
                                                        }
                                                    ?>
                                                    </td><!-- 3 -->
                                                    <!-- shop detail 2 -->
                                                    <td>
                                                    <?php 
                                                        if($row['is_category'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Categories</span><br>
                                                            <?php
                                                        }//has category
                                                        if($row['is_suppliers'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Suppliers</span><br>
                                                            <?php
                                                        }//has inventory
                                                        if($row['is_service'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Services</span><br>
                                                            <?php
                                                        }//has inventory
                                                        if($row['is_salesman'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Salesman</span><br>
                                                            <?php
                                                        }//has inventory
                                                        if($row['is_expenses'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Expenses</span><br>
                                                            <?php
                                                        }//has inventory
                                                        if($row['is_customers'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Customers</span><br>
                                                            <?php
                                                        }//has inventory
                                                        if($row['is_quotation'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Quotations</span><br>
                                                            <?php
                                                        }//has inventory
                                                        if($row['is_promotions'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Promotions</span><br>
                                                            <?php
                                                        }//has promotion
                                                        if($row['is_racks'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Racks</span><br>
                                                            <?php
                                                        }//has racks
                                                        if($row['is_prescription'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Prescription</span><br>
                                                            <?php
                                                        }//has prescription
                                                        if($row['is_counter'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Counter</span><br>
                                                            <?php
                                                        }//has prescription
                                                        if($row['is_excessAmount'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Allow Excess Amount</span><br>
                                                            <?php
                                                        }//has Excess Amount
                                                        if($row['is_BatchNo'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Batch No</span><br>
                                                            <?php
                                                        }//has Batch No
                                                    ?>
                                                    </td><!-- 4 -->
                                                    <!-- stock type -->
                                                    <td>
                                                    <?php 
                                                        switch ($row['StockTypes_STID'])
                                                        {
                                                        case '1':
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Average</span><br>
                                                            <?php
                                                        break;
                                                        case '2':
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">FIFO</span><br>
                                                            <?php
                                                        break;
                                                        case '3':
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">LIFO</span><br>
                                                            <?php
                                                        break;
                                                        case '4':
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Expire Date</span><br>
                                                            <?php
                                                        break;
                                                        case '5':
                                                            ?>
                                                            <span class="mt-2 badge bg-primary">Batch</span><br>
                                                            <?php
                                                        break;
                                                        }//switch

                                                    ?>
                                                    </td><!-- 5 -->

                                                    <!-- hidden data -->
                                                    <td style="display:none"><?php echo $row['Company_CMID'];?></td><!-- 6 -->
                                                    <td style="display:none"><?php echo $row['ShopNo'];?></td><!-- 7 -->

                                                    <td style="display:none"><?php echo $row['ShopName'];?></td><!-- 8 -->
                                                    
                                                    <td style="display:none"><?php echo $row['StockTypes_STID'];?></td><!-- 9 --> 
                                                
                                                    <td style="display:none"><?php echo $row['ShopStat'];?></td><!-- 10 -->
                                                    
                                                    <td style="display:none"><?php echo $row['is_inventory'];?></td><!-- 11 -->
                                                    <td style="display:none"><?php echo $row['is_minus'];?></td><!-- 12 -->
                                                    <td style="display:none"><?php echo $row['is_expire'];?></td><!-- 13 -->
                                                    <td style="display:none"><?php echo $row['is_fixedprice'];?></td><!-- 14 -->
                                                    <td style="display:none"><?php echo $row['is_variation'];?></td><!-- 15 -->
                                                    <td style="display:none"><?php echo $row['is_secondlan'];?></td><!-- 16 -->
                                                    <td style="display:none"><?php echo $row['is_labelprice'];?></td><!-- 17 -->
                                                    <td style="display:none"><?php echo $row['is_carton'];?></td><!-- 18 -->
                                                    <td style="display:none"><?php echo $row['is_warranty'];?></td><!-- 19 -->
                                                    <td style="display:none"><?php echo $row['is_category'];?></td><!-- 20 -->
                                                    <td style="display:none"><?php echo $row['is_suppliers'];?></td><!-- 21 -->
                                                    <td style="display:none"><?php echo $row['is_service'];?></td><!-- 22 -->
                                                    <td style="display:none"><?php echo $row['is_salesman'];?></td><!-- 23 -->
                                                    <td style="display:none"><?php echo $row['is_expenses'];?></td><!-- 24 -->
                                                    <td style="display:none"><?php echo $row['is_customers'];?></td><!-- 25 -->
                                                    <td style="display:none"><?php echo $row['is_quotation'];?></td><!-- 26 -->
                                                    <td style="display:none"><?php echo $row['is_promotions'];?></td><!-- 27 -->
                                                    <td style="display:none"><?php echo $row['invoice_print'];?></td><!-- 28 -->
                                                    <td style="display:none"><?php echo $row['is_under_cost'];?></td><!-- 29 -->
                                                    <td style="display:none"><?php 
                                                    $shopLogo="../Assets/Images/shop_images/".$row['ShopLogo'];
                                                    if(file_exists($shopLogo))
                                                    {
                                                        $shopLogo=$row['ShopLogo'];
                                                    }
                                                    else
                                                    {
                                                        $shopLogo="synnex_logo.png";
                                                    }
                                                    echo $shopLogo;
                                                    ?></td><!-- 28 -->
                                                    <td style="display:none"><?php 
                                                        $ReceiptLogo="../Assets/Images/shop_images/".$row['ReceiptLogo'];
                                                        if(file_exists($ReceiptLogo))
                                                        {
                                                            $ReceiptLogo=$row['ReceiptLogo'];
                                                        }
                                                        else
                                                        {
                                                            $ReceiptLogo="synnex_logo.png";
                                                        }
                                                    echo $ReceiptLogo;
                                                    ?></td><!-- 29 -->
                                                    <td style="display:none"><?php echo $row['is_racks'];?></td><!-- 30 -->
                                                    <td style="display:none"><?php echo $row['is_credit'];?></td><!-- 31 -->
                                                    <td style="display:none"><?php echo $row['AddressLineOne'];?></td><!-- 32 -->
                                                    <td style="display:none"><?php echo $row['AddressLineTwo'];?></td><!-- 33 -->
                                                    <td style="display:none"><?php echo $row['City'];?></td><!-- 34 -->
                                                    <td style="display:none"><?php echo $row['PhoneNumber'];?></td><!-- 35 -->
                                                    <td style="display:none"><?php echo $row['WholesaleShop'];?></td><!-- 36 -->
                                                    <td style="display:none"><?php echo $row['RetailShop'];?></td><!-- 37 -->
                                                    <td style="display:none"><?php echo $row['is_counter'];?></td><!-- 38 -->
                                                    <td style="display:none"><?php echo $row['is_excessAmount'];?></td><!-- 38 -->
                                                    <td style="display:none"><?php echo $row['is_BatchNo'];?></td><!-- 38 -->
                                                    <td>
                                                        <?php 
                                                        //shop stat
                                                        if($row['ShopStat'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-success">Active</span><br>
                                                            <?php 
                                                        }//active
                                                        else
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-danger">Inactive</span><br>
                                                            <?php 
                                                        }//inactive
                                                        
                                                        if($row['WholesaleShop'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-success">Wholesale</span><br>
                                                            <?php 
                                                        }

                                                        if($row['RetailShop'] == 1)
                                                        {
                                                            ?>
                                                            <span class="mt-2 badge bg-success">Retail</span><br>
                                                            <?php 
                                                        }

                                                        ?>
                                                    </td>

                                                    <td>
                                                        <button class="btn btn-primary btn_edit_shop"><i class="ti ti-edit"></i> Edit</button>
                                                    </td>
                                                </tr>                                         
                                            <?php endforeach; ?>
                                      </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- footer Start  -->
    <?php include '../View/footer.php';?> 
    <!-- footer End  -->    
    <script src="../Assets/jquery/Shop.js"></script>
    <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>                                    
</body>

</html>


