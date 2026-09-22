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
    $feature_id=4;
    include '../Includes/viewPermission.php';
    ?>
    <!--  Sidebar End -->
    <!--  Main wrapper -->
    <div class="body-wrapper">
        <!--  Header Start -->
        <?php 
        include '../View/header.php';
        include '../View/modals/transfer_head_modal.php';
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
                        Please select a <strong>Transfer</strong> to add items
                    </div>
                    <?php
                }//update success

                else if($_SESSION['transfer_update'] == 4)
                {
                    ?>
                    <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Please select a <strong>Shop</strong> to transfer goods.
                    </div>
                    <?php
                }//update success
                else if($_SESSION['transfer_update'] == 5)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Transfer verified.</strong> Stock has been moved to the receiving shop.
                    </div>
                    <?php
                }//verified
                else if($_SESSION['transfer_update'] == 6)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Transfer sent.</strong> It is now pending at the receiving shop.
                    </div>
                    <?php
                }//pending
                else if($_SESSION['transfer_update'] == 7)
                {
                    ?>
                    <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Transfer cancelled.</strong> No stock was moved.
                    </div>
                    <?php
                }//cancelled
                else if($_SESSION['transfer_update'] == 8)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        This transfer can no longer be changed. It has already been <strong>verified or cancelled</strong>, or it does not belong to this shop.
                    </div>
                    <?php
                }//not allowed
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
                        Transfer Note
                        <?php
                        if($userType!=1)
                        {
                            if($create==1)
                            {
                                ?>
                                <button class="btn btn-primary rounded-pill float-end" id="btn_open_transfer"><small>Add New Transfer</small></button>
                                <?php
                            }
                        }
                        else
                        {
                            ?>
                            <button class="btn btn-primary rounded-pill float-end" id="btn_open_transfer"><small>Add New Transfer</small></button>
                            <?php
                        }
                        ?>
                    </h5>
                </div>
                <div class="card-body">
                    
                

                <div class="container-fluid table-responsive">
                    <table class="table table-hover" id="tbl_transfer_header">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Date</th>
                                <th>Transfer From</th>
                                <th>Transfer To</th>
                                <th>Row Count</th>
                                <th>Transfer Amount</th>
                                <th>Type</th>
                                <th>Added By</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                            $sql = "SELECT * FROM transferheader 
                            INNER JOIN user ON user.USID = transferheader.user_USID
                            WHERE TransferFrom = ".$shop_id." OR (TransferTo=".$shop_id." AND TransferStat !=0) ORDER BY THID DESC;";

                            $dbObj = new DBTransactions();
                            $transferData = $dbObj->getData($sql);

                            // $transferObj = new Transfer();
                            // $transferData = $transferObj->getAllTransfer($shop_id);
                            $fromShop = null;
                            $toShop = null;

                            $count = 0;
                            foreach($transferData as $row)
                            {
                                $transfer_stat = $row['TransferStat'];
                                $count += 1;

                                $from_shop_id = $row['TransferFrom'];
                                $to_shop_id = $row['TransferTo'];

                                $shopObj = new Shop();
                                $fromShop = $shopObj->getOneShop($row['TransferFrom']);
                                $toShop = $shopObj->getOneShop($row['TransferTo']);

                                $from_shop = empty($fromShop[0]['ShopName']) ? 0 : $fromShop[0]['ShopName'];
                                $to_shop = empty($toShop[0]['ShopName']) ? 0 : $toShop[0]['ShopName'];
                                ?>
                                <tr>
                                    <td><?php echo $row['TransferNo'];?></td>
                                    <td><?php echo $row['EffectiveDate'];?></td>
                                    <td><?php echo $from_shop;?></td>
                                    <td><?php echo $to_shop;?></td>
                                    <td><?php echo $row['TransferTotalCount'];?></td>
                                    <td><?php echo $row['TransferTotalAmount'];?></td>

                                    <!-- transfer type -->
                                    <td>
                                        <?php 
                                        if($from_shop_id == $shop_id)
                                        {
                                            ?>
                                            <p class="text-primary" style="font-weight: 700;"><i class="ti ti-square-arrow-up"></i> Transfer</p>
                                            <?php 
                                        }//from shop

                                        if($to_shop_id == $shop_id)
                                        {
                                            ?>
                                            <p class="text-warning" style="font-weight: 700;"><i class="ti ti-square-arrow-down"></i> Received</p>
                                            <?php 
                                        }//to shop
                                        ?>
                                    </td>
                                    <td><?php echo $row['UserName'];?></td>
                                    <td>
                                        <?php 
                                        
                                        if($transfer_stat == '0')
                                        {
                                            ?>
                                            <p class="text-center text-primary" style="font-weight: 700;"><i class="ti ti-player-pause"></i> Hold</p>
                                            <?php
                                        }//on hold
                                        else if($transfer_stat == '1')
                                        {
                                            ?>
                                            <p class="text-center text-warning" style="font-weight: 700;"><i class="ti ti-refresh"></i> Pending</p>
                                            <?php
                                        }//pending
                                        else if($transfer_stat == '2')
                                        {
                                            ?>
                                            <p class="text-center" style="color:green;font-weight: 700;"><i class="ti ti-checks"></i> Verified</p>
                                            <?php
                                        }//verified
                                        else if($transfer_stat == '3')
                                        {
                                            ?>
                                            <p class="text-center text-danger" style="font-weight: 700;"><i class="ti ti-circle-x"></i> Cancelled</p>
                                            <?php
                                        }//canceled
                                        else
                                        {
                                            ?>
                                            <p class="text-center text-danger" style="font-weight: 700;"><i class="ti ti-alert-octagon"></i> Undefined</p>
                                            <?php 

                                        }//added
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $transfer_stat = $row['TransferStat'];
                                        if($transfer_stat == '0' || $transfer_stat == '1' || $transfer_stat == '2' || $transfer_stat == '3' )
                                        {
                                            if($userType==1)
                                            {
                                                ?>
                                                <form action="transfer-details.php" method="post" style="display:inline;">
                                                    <input type="hidden" name="transfer_header_id" value="<?php echo $row['THID'];?>">
                                                    <input type="hidden" name="transfer_header_stat" value="<?php echo $transfer_stat;?>">
                                                    <button name="btn_goto_details" class="btn border border-success"><i class="ti ti-plus"></i></button>
                                                </form>
                                                <?php
                                            }
                                            else
                                            {
                                                if($edit==1 || $verify==1)
                                                {
                                                    ?>
                                                    <form action="transfer-details.php" method="post" style="display:inline;">
                                                        <input type="hidden" name="transfer_header_id" value="<?php echo $row['THID'];?>">
                                                        <input type="hidden" name="transfer_header_stat" value="<?php echo $transfer_stat;?>">
                                                        <button name="btn_goto_details" class="btn border border-success"><i class="ti ti-plus"></i></button>
                                                    </form>
                                                    <?php
                                                }
                                            }
                                            if($userType==1)
                                            {
                                                ?>
                                                    <form action="../Reports/transfer_detail.php?header_id=<?php echo $row['THID'];?>" method="post" style="display:inline;">
                                                        <input type="hidden" name="transfer_header_id" value="<?php echo $row['THID'];?>">
                                                        <input type="hidden" name="transfer_header_stat" value="<?php echo $transfer_stat;?>">
                                                        <button type="submit" class="btn border border-primary"><i class="ti ti-printer"></i></button>
                                                    </form>
                                                <?php
                                            }
                                            else
                                            {
                                                if($print==1)
                                                {
                                                    ?>
                                                        <form action="../Reports/transfer_detail.php?header_id=<?php echo $row['THID'];?>" method="post" style="display:inline;">
                                                            <input type="hidden" name="transfer_header_id" value="<?php echo $row['THID'];?>">
                                                            <input type="hidden" name="transfer_header_stat" value="<?php echo $transfer_stat;?>">
                                                            <button type="submit" class="btn border border-primary"><i class="ti ti-printer"></i></button>
                                                        </form>
                                                    <?php
                                                }
                                            }
                                                
                                            
                                        }//conditional actions
                                        else
                                        {
                                            ?>
                                            <button type="button" id="btn_header_<?php echo $row['THID']?>" class="btn border border-success"><i class="ti ti-eye"></i></button>
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
    <script src="../Assets/jquery/transfer.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>

    <script>
        $(document).ready(function(){
            $("#tbl_transfer_header").DataTable({
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
