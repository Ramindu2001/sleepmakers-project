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
//   // include '../View/loader.php';
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
    $feature_id=67;//add feature id
    include '../Includes/viewPermission.php';

    include '../View/modals/add-barcode.php';

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
                if(isset($_SESSION['label_update']))
                {
                    if($_SESSION['label_update'] == 0)
                    {
                        ?>
                        <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            Please add a<strong>Barcode File</strong>.
                        </div>
                        <?php
                    }//no file
                    else if($_SESSION['label_update'] == 1)
                    {
                        ?>
                        <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            Please add a<strong> Supporting file type (php)</strong>.
                        </div>
                        <?php
                    }//not support
                    else if($_SESSION['label_update'] == 2)
                    {
                        ?>
                        <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            Label added <strong> successfully.</strong>.
                        </div>
                        <?php
                    }//not support
                    else if($_SESSION['label_update'] == 3)
                    {
                        ?>
                        <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            Label updated <strong> successfully.</strong>.
                        </div>
                        <?php
                    }//not support
                    else if($_SESSION['label_update'] == 4)
                    {
                        ?>
                        <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>Label files can no longer be uploaded</strong> from the browser, for security. Use Products &gt; Print Barcode, or ask your system provider to install the label.
                        </div>
                        <?php
                    }//upload blocked (Controller/LabelController.php)
                    else
                    {
                        ?>
                        <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            Oops! something went<strong> wrong</strong>.
                        </div>
                        <?php
                    }//oops!

                    unset($_SESSION['label_update']);
                }//has session
                ?>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">
                        Shop Label
                        <button id="btn_open_label_modal" class="btn btn-primary float-end">Add Barcode Label</button>
                    </h5>
                </div>
                <div class="card-body">
                    <table id='tbl_labels'>
                        <tr>
                            <th>Shop</th>
                            <th>Shop Name</th>
                            <th>Label Name</th>
                            <th>Label File</th>
                            <th>Size</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        <?php 
                        $dbObj = new DBTransactions();
                        $sql = "SELECT * FROM `label`
                        INNER JOIN shop ON shop.SHID = label.shop_id;";

                        $labelData = $dbObj->getData($sql);

                        foreach($labelData as $row)
                        {
                            $label_size = $row['lblWidth'] + 0 ."X".$row['lblHeight'];
                            $status = "";
                            $lbl_stat = $row['lblStat'] == 1 ? $status="Active" : $status="Inactive";
                            ?>
                            <tr data-id="<?php echo $row['LBID'];?>">
                                <td>
                                    <img src="../Assets/Images/shop_images/<?php echo $row['ShopLogo'];?>" alt="shop_image" style="height: 50px; width:auto;">
                                </td>
                                <td><?php echo $row['ShopName'];?></td>
                                <td><?php echo $row['LabelName'];?></td>
                                <td><?php echo $row['LabelPath'];?></td>
                                <td><?php echo $label_size;?></td>
                                <td><?php echo $status;?></td>
                                <td>
                                    <button class="btn border-primary btn_edit_label"><i class="ti ti-edit"></i></button>
                                </td>
                            </tr>
                            <?php 
                        }//foreach
                        ?>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Label Design</h5>
                </div>

                <div class="card-body">
              
                
                <a href="../Barcodes/barcode_one.php" target="_blank" class="btn btn-primary mt-2">Go to PDF</a>
                

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

    <script src="../Assets/jquery/label.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="../Assets/js/dashboard.js"></script>
</body>
</html>