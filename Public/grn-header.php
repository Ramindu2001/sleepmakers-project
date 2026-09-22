<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';
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
    if($userType==0)
    {
        $userObj=new User();
        $feature_id=2;
        $checkview=$userObj->userAcces($userRole_id,$feature_id);
        $create=$checkview[0]["is_create"];
        $view=$checkview[0]["is_view"];
        $edit=$checkview[0]["is_edit"];
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
        
        include "../View/modals/main-category.php";
        ?>
        <!--  Header End -->

        <div class="container-fluid">
            <!-- messages -->
        <div class="container">
        <?php 
            if(isset($_SESSION['grnheader_update']))
            {
                if($_SESSION['grnheader_update'] == 0)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Please select an <strong>Effective Date.</strong> 
                    </div>
                    <?php 
                }//no date
                else if($_SESSION['grnheader_update'] == 1)
                {
                    ?>
                        <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            Please enter <strong>GRN Invoice No.</strong>
                        </div>
                        <?php
                }//no invoice
                else if($_SESSION['grnheader_update'] == 2)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>GRN created </strong>successfully!
                    </div>
                    <?php
                }//save success
                else if($_SESSION['grnheader_update'] == 3)
                {
                    ?>
                    <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Please select a <strong>GRN</strong> to add items
                    </div>
                    <?php
                }//update success
                else if($_SESSION['grnheader_update'] == 4)
                {
                    ?>
                    <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        This <strong>GRN</strong> was already verified or cancelled - its stock was not added again.
                    </div>
                    <?php
                }//double submit / already verified
                else
                {
                    ?>
                    <div class="alert alert-danger">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Oops! </strong>something went wrong!
                    </div>
                    <?php 
                }//else
                unset($_SESSION['grnheader_update']);
            }//session set
            ?>

        </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">
                        Goods Receive Note List
                        <?php 
                        if($userType!=1)
                        {
                            if($create==1)
                            {
                                ?>
                                <button class="btn btn-primary float-end" id="btn_open_grn">Add New GRN</button>
                                <a href="../Public/grnBulkUpload.php" class="btn btn-primary float-end"><i class="ti ti-file"></i>Add File</a>
                                <?php
                            }
                        }
                        else
                        {
                            ?>
                            <button class="btn btn-primary float-end" id="btn_open_grn" style="margin-left: 10px;">Add New GRN</button>
                            <a href="../Public/grnBulkUpload.php" class="btn btn-primary float-end"><i class="ti ti-file"></i>Add File</a>
                            <?php
                        }
                        ?>
                        
                    </h5>
                </div>
                <div class="card-body">
                
                <div class="container-fluid table-responsive">
                    <table class="table table-hover" id="tbl_grn_header">
                        <thead>
                        <tr>
                            <!-- <td style="display: none;">0</td> -->
                            <th>No</th>
                            <th>Date</th>
                            <th>Invoice No</th>
                            <th>Items</th>
                            <th>Purchase Total</th>
                            <th>Selling Total</th>
                            <th>Reference</th>
                            <th>Supplier</th>
                            <th>Added By</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php 
                            $grnObj = new GRN();
                            $grnData = $grnObj->getAllGRNHeader($shop_id);
                            $count = 0;
                            foreach($grnData as $row)
                            {
                                $count += 1;
                                ?>
                                <tr>
                                    <!-- <td style="display: none;"><?php echo $row['GHID'];?></td> -->
                                    <td><?php echo $row['GRNHeaderNo'];?></td>
                                    <td><?php echo $row['EffectiveDate'];?></td>
                                    <td><?php echo $row['InvoiceNo'];?></td>
                                    <td text-align="center"><?php echo $row['ItemCount'];?></td>
                                    <td text-align="right"><?php echo $row['TotalPurchasePrice'];?></td>
                                    <td text-align="right"><?php echo $row['TotalSellPrice'];?></td>
                                    <td text-align="right"><?php echo $row['refference'];?></td>
                                    <td text-align="right"><?php echo $row['SupplierName'];?></td>
                                    <td><?php echo $row['UserName'];?></td>
                                    <td>
                                        <?php 
                                        $grn_stat = $row['GRNStat'];
                                        if($grn_stat == '0')
                                        {
                                            ?>
                                            <p class="text-center text-primary" style="font-weight: 700;"><i class="ti ti-player-pause"></i> Hold</p>
                                            <?php
                                        } //on hold
                                        else if($grn_stat == '1')
                                        {
                                            ?>
                                            <p class="text-center text-warning" style="font-weight: 700;"><i class="ti ti-refresh"></i> Pending</p>
                                            <?php
                                        } //pending
                                        else if($grn_stat == '2')
                                        {
                                            ?>
                                            <p class="text-center" style="color:green;font-weight: 700;"><i class="ti ti-checks"></i> Verified</p>
                                            <?php
                                        } //verified
                                        else if($grn_stat == '3')
                                        {
                                            ?>
                                            <p class="text-center text-danger" style="font-weight: 700;"><i class="ti ti-circle-x"></i> Cancelled</p>
                                            <?php
                                        } //cancled
                                        else
                                        {
                                            ?>
                                            <p class="text-center text-danger" style="font-weight: 700;"><i class="ti ti-alert-octagon"></i> Undefined</p>
                                            <?php 
                                        } //added
                                        ?>
                                    </td>
                                    <td>
                                    <?php 
                                        $grn_stat = $row['GRNStat'];
                                        if($grn_stat == '0' || $grn_stat == '1' || $grn_stat == '2' || $grn_stat == '3')
                                        {
                                            ?>
                                            <form action="grn-details.php" method="post" style="display:inline;">
                                                <input type="hidden" name="grn_header_id" value="<?php echo $row['GHID'];?>">
                                                <input type="hidden" name="grn_header_stat" value="<?php echo $grn_stat;?>">
                                                <?php 
                                                if($userType!=1)
                                                {
                                                    if($verify==1 || $edit==1)
                                                    {
                                                        ?>
                                                        <button name="btn_goto_details" class="btn border border-success"><i class="ti ti-plus"></i></button>
                                                        <?php
                                                    }
                                                }
                                                else
                                                {
                                                    ?>
                                                    <button name="btn_goto_details" class="btn border border-success"><i class="ti ti-plus"></i></button>
                                                    <?php
                                                }
                                                ?>
                                            </form>
                                            <form action="../Reports/grn_detail.php?header_id=<?php echo $row['GHID'];?>" method="post" style="display:inline;">
                                              <input type="hidden" name="grn_header_id" value="<?php echo $row['GHID'];?>">
                                              <input type="hidden" name="grn_header_stat" value="<?php echo $row['GHID'];?>">
                                                <?php 
                                                if($userType!=1)
                                                {
                                                    if($print==1)
                                                    {
                                                        ?>
                                                        <button type="submit" class="btn border border-primary"><i class="ti ti-printer"></i></button>
                                                        <?php
                                                    }
                                                }
                                                else
                                                {
                                                    ?>
                                                    <button type="submit" class="btn border border-primary"><i class="ti ti-printer"></i></button>
                                                    <?php
                                                }
                                                ?>
                                                
                                            </form>
                                            <?php
                                        }//editable statuses

                                        else 
                                        {
                                            ?>
                                            <button type="button" id="btn_header_<?php echo $row['GHID']?>" class="btn border border-success"><i class="ti ti-eye"></i></button>
                                            <?php 
                                        }//added
                                        ?>
                                    </td>
                                </tr>
                                <?php 
                            } //foreach
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
<!--  Body Wrapper End -->

    <!-- footer Start  -->
    <?php include '../View/footer.php';?> 
    <!-- footer End  -->

    <script src="../Assets/jquery/grn.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>

    <script>
        $(document).ready(function(){
            $("#tbl_grn_header").DataTable({
                paging: true,
                lengthChange: true,
                searching: true,
                // pageLength: 50,
                order:[0, 'desc'],
            });//data table
        });
    </script>

</body>
</html>