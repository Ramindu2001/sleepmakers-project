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
  <style>
    .table>:not(caption)>*>* {
        padding: 10px 10px;
    }
    .ml-2 {
        margin-left: 10px;
    }
  </style>
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
        $feature_id=3;
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
        include '../View/modals/adjust_head_modal.php';
        ?>
        <!--  Header End -->

        <div class="container-fluid">
            <!-- messages -->
        <div class="container">
        <?php 
            if(isset($_SESSION['transfer_update']))
            {
                if($_SESSION['transfer_update'] == 0)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Please select an <strong>Effective Date.</strong> 
                    </div>
                    <?php 
                }//no date
                else if($_SESSION['transfer_update'] == 1)
                {
                    ?>
                        <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            New transfer created <strong>GRN Invoice No.</strong>
                        </div>
                        <?php
                }//no invoice
                else if($_SESSION['transfer_update'] == 2)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>GRN created </strong>successfully!
                    </div>
                    <?php
                }//save success
                else if($_SESSION['transfer_update'] == 3)
                {
                    ?>
                    <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Please select a <strong>GRN</strong> to add items
                    </div>
                    <?php
                }//update success
                else
                {
                    ?>
                    <div class="alert alert-danger">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Oops! </strong>something went wrong!
                    </div>
                    <?php 
                }//else
                unset($_SESSION['transfer_update']);
            }//session set
            ?>

        </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">
                        Adjustment Header
                        <?php
                        if($userType!=1)
                        {
                            if($create==1)
                            {
                                ?>
                                <button class="btn btn-primary rounded-pill float-end" id="btn_open_adjustment"><small>Add New Adjustment Note</small></button>
                                <?php
                            }
                        }
                        else
                        {
                            ?>
                            <button class="btn btn-primary rounded-pill float-end" id="btn_open_adjustment"><small>Add New Adjustment Note</small></button>
                            <?php
                        }
                        ?>
                    </h5>
                </div>
                <div class="card-body">
                    
                <div class="container-fluid table-responsive">
                    <table class="table table-hover" id="tbl_adjust_header">
                        <thead>
                        <tr>
                            <th>No</th>
                            <th>Date</th>
                            <th>Row Count</th>
                            <th>Adjustment Amount</th>
                            <th>Added By</th>
                            <th>Adjustment Type</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php 
                            $adjustObj = new Adjustment();
                            $adjustData = $adjustObj->getAllAdjustment($shop_id);
                            $count = 0;

                            foreach($adjustData as $row)
                            {   
                                $count += 1;
                                ?>
                                <tr>
                                    <td><?php echo $row['AdjustNo'];?></td>
                                    <td><?php echo $row['EffectiveDate'];?></td>
                                    <td><?php echo $row['AdjustCount'];?></td>
                                    <td><?php echo $row['AdjustAmount'];?></td>
                                    <td><?php echo $row['UserName'];?></td>
                                    <td>
                                        <?php 
                                        $adjust_type = $row['AdjustmentType_ITID'];
                                        if($adjust_type == '1')
                                        {
                                            ?>
                                            <p class="text-center" style="color:green;font-weight: 700;"><i class="ti ti-outbound" style=" display: inline-block; transform: rotate(180deg); "></i> IN</p>
                                            <?php 
                                        }//adjust in
                                        else
                                        {
                                            ?>
                                            <p class="text-center text-danger">OUT <i class="ti ti-outbound"></i></p>
                                            <?php 
                                        }//adjust out
                                        ?>
                                    </td>
                                    
                                    <td>
                                        <?php
                                        
                                        $adjust_stat = $row['AdjustStat'];
                                        if($adjust_stat == '0')
                                        {
                                            ?>
                                            <span class="badge bg-primary">Hold</span>
                                            <?php
                                        }//on hold
                                        else if($adjust_stat == '1')
                                        {
                                            ?>
                                            <span class="badge bg-warning">Pending</span>
                                            <?php
                                        }//pending
                                        else if($adjust_stat == '2')
                                        {
                                            ?>
                                            <span class="badge bg-success">Verified</span>
                                            <?php
                                        }//verified
                                        else if($adjust_stat == '3')
                                        {
                                            ?>
                                            <span class="badge bg-danger">Canceled</span>
                                            <?php
                                        }//cancled
                                        else
                                        {
                                            ?>
                                            <span class="badge bg-danger">Undefined</span>
                                            <?php 
                                        }//added
                                        ?>
                                    </td>
                                    <td>
                                        
                                        <?php 
                                        $adjust_stat = $row['AdjustStat'];
                                        if($adjust_stat == '0')
                                        {
                                            if($userType==1)
                                            {
                                                ?>
                                                <form action="adjust-detail.php" method="post">
                                                
                                                    <input type="hidden" name="adjust_header_id" value="<?php echo $row['AHID'];?>">
                                                    <input type="hidden" name="adjust_header_stat" value="<?php echo $adjust_stat;?>">
                                                    <button name="btn_goto_details" class="btn border border-success ml-2"><i class="ti ti-plus"></i></button>
                                                </form>
                                                <?php
                                            }
                                            else
                                            {
                                                if($edit==1 || $verify==1)
                                                {
                                                    ?>
                                                    <form action="adjust-detail.php" method="post">
                                                    
                                                        <input type="hidden" name="adjust_header_id" value="<?php echo $row['AHID'];?>">
                                                        <input type="hidden" name="adjust_header_stat" value="<?php echo $adjust_stat;?>">
                                                        <button name="btn_goto_details" class="btn border border-success ml-2"><i class="ti ti-plus"></i></button>
                                                    </form>
                                                    
                                                    <?php
                                                }
                                            }
                                            
                                        }//on hold
                                        else if($adjust_stat == '1')
                                        {
                                            if($userType==1)
                                            {
                                                ?>
                                                <form action="adjust-detail.php" method="post">
                                                
                                                    <input type="hidden" name="adjust_header_id" value="<?php echo $row['AHID'];?>">
                                                    <input type="hidden" name="adjust_header_stat" value="<?php echo $adjust_stat;?>">
                                                    <button name="btn_goto_details" class="btn border border-success ml-2"><i class="ti ti-plus"></i></button>
                                                </form>
                                                <?php
                                            }
                                            else
                                            {
                                                if($edit==1 || $verify==1)
                                                {
                                                    ?>
                                                    <form action="adjust-detail.php" method="post">
                                                    
                                                        <input type="hidden" name="adjust_header_id" value="<?php echo $row['AHID'];?>">
                                                        <input type="hidden" name="adjust_header_stat" value="<?php echo $adjust_stat;?>">
                                                        <button name="btn_goto_details" class="btn border border-success ml-2"><i class="ti ti-plus"></i></button>
                                                    </form>
                                                    
                                                    <?php
                                                }
                                            }
                                            
                                            
                                        }//on pending
                                        else if($adjust_stat == '2')
                                        {
                                            if($userType==1)
                                            {
                                                ?>                                
                                                <form action="../Reports/adjust_detail.php?header_id=<?php echo $row['AHID'];?>" method="post">
                                                    <input type="hidden" name="adjust_header_id" value="<?php echo $row['AHID'];?>">
                                                    <input type="hidden" name="adjust_header_stat" value="<?php echo $adjust_stat;?>">
                                                    <button type="submit" id="btn_adjust_<?php echo $row['AHID']?>" class="btn border border-primary ml-2"><i class="ti ti-printer"></i></button>
                                                </form>
                                             <?php
                                            }
                                            else
                                            {
                                                if($print==1)
                                                {
                                                    ?>                                
                                                    <form action="../Reports/adjust_detail.php?header_id=<?php echo $row['AHID'];?>" method="post">
                                                        <input type="hidden" name="adjust_header_id" value="<?php echo $row['AHID'];?>">
                                                        <input type="hidden" name="adjust_header_stat" value="<?php echo $adjust_stat;?>">
                                                        <button type="submit" id="btn_adjust_<?php echo $row['AHID']?>" class="btn border border-primary ml-2"><i class="ti ti-printer"></i></button>
                                                    </form>
                                                    
                                                    <?php
                                                }
                                            }
                                        }//varified


                                        else if($adjust_stat == '3')
                                        {
                                            if($userType==1)
                                            {
                                                ?>                                
                                                <form action="../Reports/adjust_detail.php?header_id=<?php echo $row['AHID'];?>" method="post">
                                                    <input type="hidden" name="adjust_header_id" value="<?php echo $row['AHID'];?>">
                                                    <input type="hidden" name="adjust_header_stat" value="<?php echo $adjust_stat;?>">
                                                    <button type="submit" id="btn_adjust_<?php echo $row['AHID']?>" class="btn border border-primary ml-2"><i class="ti ti-printer"></i></button>
                                                </form>
                                                
                                             <?php
                                            }
                                            else
                                            {
                                                if($print==1)
                                                {
                                                    ?>
                                                    <form action="../Reports/adjust_detail.php?header_id=<?php echo $row['AHID'];?>" method="post">
                                                        <input type="hidden" name="adjust_header_id" value="<?php echo $row['AHID'];?>">
                                                        <input type="hidden" name="adjust_header_stat" value="<?php echo $adjust_stat;?>">
                                                        <button type="submit" id="btn_adjust_<?php echo $row['AHID']?>" class="btn border border-primary ml-2"><i class="ti ti-printer"></i></button>
                                                    </form>
                                                    <?php

                                                }
                                            }
                                        }//cancled

                                        else
                                        {
                                            ?>
                                            <button type="button" id="btn_header_<?php echo $row['AHID']?>" class="btn border border-success"><i class="ti ti-eye"></i></button>
                                            <?php 
                                        }//added
                                        ?>
                                    </td>
                                </tr>
                                <?php 
                            }//foreach
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
    <script src="../Assets/jquery/adjustment.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>

    <script>
        $(document).ready(function(){
            $("#tbl_adjust_header").DataTable({
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