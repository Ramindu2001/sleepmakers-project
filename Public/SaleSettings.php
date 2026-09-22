<?php 
include '../Includes/includes.php';
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

<!doctype html>
<html lang="en">

<head>
  <?php 
  include '../View/head.php';
  // include '../View/loader.php';
  ?>
</head>

<body>
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
        include "../View/modals/sale_settings_modal.php";
        include "../View/modals/shopReceiptModal.php";
        ?>
        <!--  Header End -->

        <div class="container-fluid">
        <!-- messages -->
        <div class="container">
        <?php 
            if(isset($_SESSION['setting_update']))
            {
                if($_SESSION['setting_update'] == 0)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Setting saved successfully.</strong>
                    </div>
                    <?php 
                }  //no entry
                else if($_SESSION['setting_update'] == 1)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Settings updated</strong>successfully.
                    </div>
                    <?php
                }  //updated entry
                else if($_SESSION['setting_update'] == 2)
                {
                    ?>
                    <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Settings deleted</strong>successfully.
                    </div>
                    <?php
                }  //deleted
                else if($_SESSION['setting_update'] == 3)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>No receipt selected.</strong>
                    </div>
                    <?php
                }  //filer not added
                else if($_SESSION['setting_update'] == 4)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        File <strong>size greater than 5 MB.</strong>
                    </div>
                    <?php
                }  //filer not added
                else if($_SESSION['setting_update'] == 5)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Not supported file <strong>type.</strong>
                    </div>
                    <?php   
                }  //not support file type
                else if($_SESSION['setting_update'] == 6)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Receipt saved</strong>successfully.
                    </div>
                    <?php
                }  //updated entry
                else if($_SESSION['setting_update'] == 7)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Receipt details updated</strong>successfully.
                    </div>
                    <?php
                }  //updated entry
                else if($_SESSION['setting_update'] == 8)
                {
                    ?>
                    <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Receipt deleted</strong>successfully.
                    </div>
                    <?php
                }  //updated entry
                else if($_SESSION['setting_update'] == 9)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Receipt files can no longer be uploaded</strong> from the browser, for security. Please ask your system provider to install the receipt.
                    </div>
                    <?php
                }  //upload blocked (Controller/shopReceiptController.php)
                else
                {
                    ?>
                    <div class="alert alert-danger">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Oops! </strong>something went wrong!
                    </div>
                    <?php 
                } //else
                unset($_SESSION['setting_update']);
            } //session set
            ?>
        </div>
         
        <!------------------------------ Sale Settings ------------------------------>
            <div class="card">
                <div class="card-header">
                <h5 class="card-title fw-semibold" style="margin-top: 0px;">
                    Sale Settings
                    <button class="btn btn-primary border rounded-pill ml-1 float-end" id="btn_add_settings">Add Sale Settings</button>
                </h5>
                </div>
                <div class="card-body">
                
                <div class="container-fluid">
                    <table class="table table-hover" id="tbl_sale_settings">
                        <tr>
                            <td style="display: none;">0</td>
                            <th>Shop</th>
                            <th>Shop Name</th>
                            <th>Add option</th>
                            <th>Duration</th>
                            <th>Header text</th>
                            <th>Wholesale Invoice Prefix</th>
                            <th>Counter type</th>
                            <th>Stat</th>
                            <th>Action</th>
                        </tr>
                        <?php 
                            $sql = "SELECT * FROM salesettings
                            INNER JOIN shop ON shop.SHID = salesettings.shop_id;";
                            $dbObj = new DBTransactions();

                            $saleData = $dbObj->getData($sql);
                            
                            foreach($saleData as $row)
                            {
                                ?>
                                <tr data-id="<?php echo $row['SSID'];?>">
                                    <td style="display: none;"><?php echo $row['SSID'];?></td>
                                    <td>
                                        <img src="../Assets/Images/shop_images/<?php echo $row['ShopLogo'];?>" alt="Shop Image" style="height: 50px; width:auto;">
                                    </td>
                                    <td><?php echo $row['ShopName'];?></td>
                                    <td>
                                        <?php 
                                        $add_option = $row['billAddOption']==0 ? "Auto Add Items" : "Manually Add Items";
                                        echo $add_option;
                                        ?>
                                    </td>
                                    <td><?php echo $row['qtyAddDuration'] . " s";?></td>
                                    <td><?php echo $row['billNoHeader'];?></td>
                                    <td><?php echo $row['WbillNoHeader'];?></td>
                                    <td>
                                        <?php 
                                            $counter_type = "";
                                            switch ($row['countertype_id']) {
                                                case '0':
                                                    $counter_type = "Touch only";
                                                    break;

                                                case '1':
                                                    $counter_type = "Touch & Keyboard";
                                                    break;

                                                case '2':
                                                    $counter_type = "Mouse & Keyboard";
                                                    break;
                                                
                                            }//switch

                                            echo $counter_type;
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if($row['settingStat'] == 1)
                                        {
                                            ?>
                                            <p class=" badge bg-primary text-light rounded-pill">Active</p>
                                            <?php 
                                        }//if active
                                        else
                                        {
                                            ?>
                                            <p class=" badge bg-warning text-light rounded-pill">Inactive</p>
                                            <?php 
                                        }//else
                                        ?>
                                    </td>

                                    <td>
                                        <button type="button"  class="setting_row_edit btn border border-primary"><i class="ti ti-edit"></i></button>
                                        <button type="button"  class="setting_row_delete btn border border-danger"><i class="ti ti-x"></i></button>
                                    </td>
                                </tr>
                                <?php 
                            }//foreach
                        ?>
                    </table>
                </div>

                </div>
            </div>

        <!---------------------------- Shop Receipt assign --------------------------->
            <div class="card mt-3">
                <div class="card-header">
                    <h5>
                        GUI Shop Receipts
                        <button class="btn btn-primary rounded-pill me-2 float-end" id="btn_add_receipt">Add Receipt</button>
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-hover" id="tbl_shop_receipts">
                        <tr>
                            <th>Shop</th>
                            <th>Shop Name</th>
                            <th>Receipt Name</th>
                            <th>Receipt File</th>
                            <th>Recipt Type</th>
                            <th>Default</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        <?php  
                            $sql = "SELECT * FROM shopreceipts sr
                            INNER JOIN shop s ON s.SHID = sr.shop_id
                            ;";
                            $dbObj = new DBTransactions();

                            $receiptData = $dbObj->getData($sql);
                            if(count($receiptData)>0)
                            {
                                foreach($receiptData as $row)
                                {
                                    ?>
                                    <tr data-id="<?php echo $row['SRID'];?>">
                                        <td>
                                            <img src="../Assets/Images/shop_images/<?php echo $row['ShopLogo'];?>" alt="Shop logo" style="height: 50px; width:auto;">
                                        </td>
                                        <td><?php echo $row['ShopName'];?></td>
                                        <td><?php echo $row['receiptName'];?></td>
                                        <td><?php echo $row['ReceiptPath'];?></td>
                                        <td>
                                            <?php 
                                            if($row["RecieptType"]==1)
                                            {
                                                echo "Retail POS Recipt";
                                            }
                                            else
                                            {
                                                echo "Wholesale POS Recipt";
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                                $is_default = $row['is_default'];
                                                if($row['is_default'] == 1)
                                                {
                                                    ?>
                                                    <p class="bg-primary text-light rounded-pill p-2 text-center">Default</p>
                                                    <?php 
                                                }//is default
                                                else
                                                {
                                                    ?>
                                                    <p class="bg-warning text-light rounded-pill p-2 text-center">Not Default</p>
                                                    <?php 
                                                }//not default
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if($row['ReceiptStat'] == 1)
                                            {
                                                ?>
                                                <p class="bg-primary text-light rounded-pill p-2 text-center">Active</p>
                                                <?php 
                                            }//active
                                            else
                                            {
                                                ?>
                                                <p class="bg-warning text-light rounded-pill p-2 text-center">Inactive</p>
                                                <?php 
                                            }//inactive
                                            ?>
                                        </td>
                                        <td>
                                            <button type="button"  class="receipt_row_edit btn border border-primary"><i class="ti ti-edit"></i></button>
                                            <button type="button"  class="receipt_row_delete btn border border-danger"><i class="ti ti-x"></i></button>
                                        </td>
                                    </tr>
                                    <?php 
                                }//foreach
                            }
                            else
                            {
                                ?>
                                <tr>
                                    <td colspan="7" class="text-danger text-center"> No Results Found</td>
                                </tr>
                                <?php
                            }
                            
                            
                        ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<!--  Body Wrapper End -->

    <!-- footer Start  -->
    <?php include '../View/footer.php';?> 
    <!-- footer End -->

    <script src="../Assets/jquery/SaleSettings.js"></script>
    <script src="../Assets/jquery/ShopReceipt.js"></script>
    <!-- <script src="../Assets/libs/jquery/dist/jquery.min.js"></script> -->
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <!-- <script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script> -->
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="../Assets/js/dashboard.js"></script>

</body>
</html>