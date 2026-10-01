<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';

$shop_id = $_SESSION['shop_id'];

//opened from the transfer list (POST) or after a redirect from the controller (GET ?id=)
$transfer_header_id = 0;
if(isset($_POST['transfer_header_id']))
{
    $transfer_header_id = (int)$_POST['transfer_header_id'];
}//id set
else if(isset($_GET['id']))
{
    $transfer_header_id = (int)$_GET['id'];
}//id in url

//the status always comes from the database, never from the posted form,
//so an old page can't offer actions for a transfer that has already moved on
$headerCheckObj = new Transfer();
$headerCheck = $transfer_header_id > 0 ? $headerCheckObj->getOneTransferHeader($transfer_header_id) : [];
if(empty($headerCheck) || ($headerCheck[0]['TransferFrom'] != $shop_id && $headerCheck[0]['TransferTo'] != $shop_id))
{
    $_SESSION['transfer_update'] = 3;
    header("Location: transfer-header.php");
    die("Error: no transfer header id.");
}//no id
$transfer_header_stat = (string)$headerCheck[0]['TransferStat'];
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
    include '../Includes/editPermission.php';

    //scanner upload (docs/superpowers/specs/2026-09-22-scanner-upload-design.md), while the
    //transfer is on hold or pending: the sending shop scans what it sends, the receiving shop
    //scans what arrived
    require_once '../Includes/scan_upload.php';
    $scanAccess = new ShopAccess();
    $scan_upload = null;
    if($transfer_header_stat <= 1 && $headerCheck[0]['TransferFrom'] == $shop_id
        && $scanAccess->hasFeatureRight($_SESSION['user_id'], $shop_id, TransferScan::FEATURE, TransferScan::SEND_RIGHTS))
    {
        $scan_upload = ['context' => 'transfer_send', 'doc_id' => $transfer_header_id, 'title' => $headerCheck[0]['TransferNo'],
            'apply_label' => 'Add to Transfer', 'button' => 'Scan / Upload'];
    }//sending shop
    elseif($transfer_header_stat <= 1 && $headerCheck[0]['TransferTo'] == $shop_id
        && $scanAccess->hasFeatureRight($_SESSION['user_id'], $shop_id, TransferScan::FEATURE, TransferScan::RECEIVE_RIGHTS))
    {
        $scan_upload = ['context' => 'transfer_receive', 'doc_id' => $transfer_header_id, 'title' => $headerCheck[0]['TransferNo'],
            'apply_label' => 'Apply received quantities', 'button' => 'Scan received items'];
    }//receiving shop
    ?>
    <!--  Sidebar End -->
    <!--  Main wrapper -->
    <div class="body-wrapper">
        <!--  Header Start -->
        <?php 
        include '../View/header.php';
        // include "../View/modals/submit-transferdetail.php";
        ?>
        <!--  Header End -->

        <div class="container-fluid">
            <!-- messages -->
        <div class="container">
        <?php  
        if(isset($_SESSION['transfer_detail_update']))
        {
            if($_SESSION['transfer_detail_update'] == 0)
            {
                ?>
                <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                    Please check <strong>Transfer Qty </strong> for each item.
                    <?php
                    if(!empty($_SESSION['transfer_short_items']))
                    {
                        echo "<br>Not enough stock in the sending shop for: <strong>".htmlspecialchars(implode(", ", $_SESSION['transfer_short_items']))."</strong>";
                    }//short items
                    ?>
                </div>
                <?php
            }//no data

            else if($_SESSION['transfer_detail_update'] == 1)
            {
                ?>
                <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                    Please <strong>Add </strong> items to transfer.
                </div>
                <?php
            }//no rows


            unset($_SESSION['transfer_detail_update']);
            unset($_SESSION['transfer_short_items']);
        }//has message session
        ?>

        </div>
            <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Transfer Note Details</h5>

            <div class="card mt-2 mb-2">
            <div class="card-body">
                <div>
                    <?php 
                    if($transfer_header_stat == '0')
                    {
                        ?>
                        <p class="bg-primary-subtle p-2 rounded-pill text-center">Transfer is on Hold</p>
                        <?php 
                    }//on hold
                    else if($transfer_header_stat == '1')
                    {
                        ?>
                        <p class="bg-warning-subtle p-2 rounded-pill text-center">Transfer is Pending</p>
                        <?php 
                    }//is pending

                    else if($transfer_header_stat == '2')
                    {
                        ?>
                        <p class="bg-success-subtle p-2 rounded-pill text-center">Transfer is Varified</p>
                        <?php 
                    }//is pending
                    ?>
                </div>

                <?php 
                    $tranObj = new Transfer();
                    $tranData = $tranObj->getOneTransferHeader($transfer_header_id);

                    $from_shop = $tranData[0]['TransferFrom'];
                    $to_shop = $tranData[0]['TransferTo'];

                    $shopObj = new Shop();
                    $fromShopData = $shopObj->getOneShop($from_shop);
                    $toShopData = $shopObj->getOneShop($to_shop);

                    $from_shop_name = $fromShopData[0]['ShopName'];
                    $from_shop_image = $fromShopData[0]['ShopLogo'];
                    $to_shop_name = $toShopData[0]['ShopName'];
                    $to_shop_image = $toShopData[0]['ShopLogo'];
                    $fromshopLogo="../Assets/Images/shop_images/".$from_shop_image;
                    if($from_shop_image==null)
                    {
                        $fromshopLogo="../Assets/Images/shop_images/no_image.jpg";
                    }
                    else if(file_exists($fromshopLogo))
                    {
                        $fromshopLogo="../Assets/Images/shop_images/".$from_shop_image;
                    }
                    else
                    {
                        $fromshopLogo="../Assets/Images/shop_images/no_image.jpg";
                    }
                    $toshopLogo="../Assets/Images/shop_images/".$to_shop_image;
                    if($to_shop_image==null)
                    {
                        $toshopLogo="../Assets/Images/shop_images/no_image.jpg";
                    }
                    else if(file_exists($toshopLogo))
                    {
                        $toshopLogo="../Assets/Images/shop_images/".$to_shop_image;
                    }
                    else
                    {
                        $toshopLogo="../Assets/Images/shop_images/no_image.jpg";
                    }
                ?>
                <div class="row">
                <div class="col-md-4">
                    <h5 class="text-center m-1">From <?php echo $from_shop_name;?></h5>
                    <img src="<?php echo $fromshopLogo;?>" alt="shop image" style="width: 40%; height:auto; margin-left:30%; margin-top:10%;">
                </div>
                <div class="col-md-4">
                    <img src="../Assets/Images/icons/right_arrow.png" alt="shop image" style="width: 20%; height:auto; margin-left:40%; margin-top:20%">
                </div>
                <div class="col-md-4">
                    <h5 class="text-center m-1">To <?php echo $to_shop_name;?></h5>
                    <img src="<?php echo $toshopLogo;?>" alt="shop image" style="width: 40%; height:auto; margin-left:30%; margin-top:10%;">
                </div>
                </div>
            </div>
            </div>

            <?php if($scan_upload !== null) { ?>
            <div class="d-flex justify-content-end mb-2">
                <button type="button" class="btn btn-outline-primary btn-scan-upload"><i class="ti ti-barcode"></i> <?= htmlspecialchars($scan_upload['button']) ?></button>
            </div>
            <?php } else if($transfer_header_stat <= 1) { ?>
            <!-- scanning is the only way items go on a transfer, so say so rather than leave an empty screen -->
            <div class="d-flex justify-content-end mb-2">
                <small class="text-muted">Items are added by scanning. Ask an administrator for the Transfer
                    Note scan right.</small>
            </div>
            <?php } ?>
            <!-- Items come in through Scan / Upload. This screen moves stock and shows no price.
                 The row below appears only while one line's quantity is being corrected; the line keeps
                 the batch, dates and prices the stock carries. -->
            <input type="hidden" id="hide_header_id" value="<?php echo $transfer_header_id;?>">
            <input type="hidden" id="hide_transfer_detail_id" value="0">
            <input type="hidden" id="hide_inventory_id" value="0">
            <input type="hidden" id="hide_transfer_qty" value="0">

            <?php
            if($transfer_header_stat <= 1 && ($userType == 1 || $edit == 1))
            {
                ?>
            <div class="card" id="card_edit_line" style="display:none;">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <tr>
                                <th style="min-width: 250px;">Product</th>
                                <th style="min-width: 100px;">Current Qty</th>
                                <?php
                                if($from_shop == $shop_id)
                                {
                                    ?>
                                <th style="min-width: 150px;">Transfer Qty</th>
                                <?php
                                }//sending shop

                                if($to_shop == $shop_id)
                                {
                                    ?>
                                <th style="min-width: 150px;">Receive Qty</th>
                                <?php
                                }//receiving shop
                                ?>
                                <th style="min-width: 150px;">Action</th>
                            </tr>
                            <tr>
                                <td>
                                    <p id="edit_line_product" class="mb-0 fw-semibold"></p>
                                    <input type="hidden" id="ids" value="0">
                                </td>
                                <td id="td_current_qty">0</td>
                                <?php
                                if($from_shop == $shop_id)
                                {
                                    ?>
                                <td>
                                    <input type="number" step="0.001" name="transfer_qty" id="transfer_qty"
                                        class="form-control" placeholder="Transfer Qty">
                                    <span style="display:none;" id="transfer_qty_warning" class="text-danger">Not a
                                        valid quantity</span>
                                </td>
                                <?php
                                }//sending shop

                                if($to_shop == $shop_id)
                                {
                                    ?>
                                <td>
                                    <input type="number" step="0.001" name="receive_qty" id="receive_qty"
                                        class="form-control" placeholder="Receive Qty">
                                    <span style="display:none;" id="receive_qty_warning" class="text-danger">Not a
                                        valid quantity</span>
                                </td>
                                <?php
                                }//receiving shop
                                ?>
                                <td>
                                    <button type="button" name="btn_edit_transfer_detail" id="btn_edit_transfer_detail"
                                        class="btn border border-warning bg-warning">
                                        <i class="ti ti-check"></i>
                                    </button>
                                    <button type="button" id="btn_cancel_edit_line"
                                        class="btn border border-secondary">
                                        <i class="ti ti-x"></i>
                                    </button>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <?php
            }//the transfer can still be changed
            ?>
            
            <div class="card">
                <div class="card-body">
                <div class="container-fluid">
                    
                    <table class="table table-hover" id="tbl_transfer_detail">
                        <tr>
                            <td style="display: none;">0</td>
                            <th>Product</th>
                            <?php 
                            if($shopObj->hasVariation($shop_id))
                            {
                                ?>
                                <th>Variation</th>
                                <?php 
                            }//has variation
                            ?>

                            <?php 
                                //transfer shop
                                if($from_shop == $shop_id)
                                {
                                    ?>
                                    <th>Transfer Qty</th>
                                    <?php 
                                }//transfer from shop

                                //receive shop
                                if($to_shop == $shop_id)
                                {
                                    ?>
                                    <th>Receive Qty</th>
                                    <?php 
                                }//receive shop
                            ?>
                            
                            <th>Avl Qty</th>
                            <?php 
                            if($transfer_header_stat < 2)
                            {
                                ?>
                                <th>Action</th>
                                <?php 
                            }
                            ?>
                        </tr>
                        <?php 
                            $sql = "SELECT * FROM transferdetails
                            INNER JOIN inventory ON inventory.INID = transferdetails.InventoryID
                            INNER JOIN products ON products.PDID = transferdetails.products_PDID
                            LEFT JOIN variations ON variations.VRID = transferdetails.VariationID
                            WHERE TransferHeader_THID = ".$transfer_header_id.";";
                            $dbObj = new DBTransactions();
                            $dbData = $dbObj->getData($sql);
                            $count = 0;
                            $transfer_row_count = 0;
                            $transfer_item_count = 0;
                            $receive_item_count = 0;
                            foreach($dbData as $row)
                            {
                                $count += 1;
                                $transfer_row_count += 1;
                                $transfer_item_count += floatval($row['TransferQty']);
                                $receive_item_count += floatval($row['ReceivedQty']);
                                ?>
                                <tr data-id="<?php echo $row['TDID'];?>" id="<?=$row["INID"]?>"
                                <?php  
                                if($row["CurrentQty"]<=0)
                                {
                                    ?>
                                    style="
                                    background: #ffc800;
                                    color: #ffffff;
                                    border-radius: 10px;
                                    font-weight: 700; "
                                    <?php
                                }
                                ?>
                                >
                                    <td style="display: none;"><?php echo $row['TDID'];?></td><!-- 0 -->
                                    <td>
                                        <?php echo $row['Barcode']?><br>
                                        <?php echo $row['ItemName'];?>
                                    </td><!-- 1 -->

                                    <?php 
                                        if($shopObj->hasVariation($shop_id))
                                        {
                                            ?>
                                            <td><?php echo $row['VariationName'];?></td><!-- 2 -->
                                            <?php 
                                        }//has variation
                                    ?>
                                    

                                    <?php 
                                        if($from_shop == $shop_id)
                                        {
                                            ?>
                                            <td><?php echo $row['TransferQty'] + 0;?></td><!-- 3 -->
                                            <?php 
                                        }//transfer from shop

                                        //receive shop
                                        if($to_shop == $shop_id)
                                        {
                                            ?>
                                            <td><?php echo $row['ReceivedQty'] + 0;?></td>
                                            <?php 
                                        }//transfer to shop
                                    ?>
                                    
                                    <td><?php echo $row['CurrentQty'] + 0;?></td><!-- 4 -->
                                    <?php 
                                    if($transfer_header_stat < 2)
                                    {
                                        ?>
                                        <td>
                                            <button type="button" class="btn_transfer_edit btn border border-primary mr-1"><i class="ti ti-edit"></i></button>
                                            <button type="button" class="btn_transfer_delete btn border border-danger"><i class="ti ti-x"></i></button>
                                        </td><!-- 13 -->
                                        <?php 
                                    }//on hold or pending
                                    ?>
                                </tr>
                                <?php 
                            }//foreach
                        ?>
                    </table>
                </div>

                <div class="row">
                                <div class="col-md-6"></div>
                                <div class="col-md-6">
                                    <table class="table" id="tbl_transfer_total">
                                        <thead>
                                            <tr>
                                                <th>Row Count</th>
                                                <th>
                                                    <h4 id="sub_row_count"><?php echo $transfer_row_count;?></h4>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th>Transfer Items</th>
                                                <th>
                                                    <h4 id="sub_transfer_count"><?php echo $transfer_item_count;?></h4>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th>Receive Items</th>
                                                <th>
                                                    <h4 id="sub_receive_count"><?php echo $receive_item_count;?></h4>
                                                </th>
                                            </tr>

                                        </thead>
                                    </table>
                                </div>
                            </div>

                <!-- submit GRN -->
                 <div>
                    <form action="../Controller/transferController.php" method="POST">
                        <input type="hidden" name="hide_transferheader_id" id="hide_transferheader_id" value="<?=$transfer_header_id?>">



                        <?php 
                        if($transfer_header_stat == '0')
                        {
                            $transObj = new Transfer();
                            $transData = $transObj->getOneTransferHeader($transfer_header_id);
                            $from_shop = $transData[0]['TransferFrom'];
                            $to_shop = $transData[0]['TransferTo'];
                            if($userType==1)
                            {
                                ?>
                                <!-- Add to pending -->
                                <button type="submit" name="btn_pending_transfer" class="btn bg-primary-subtle text-primary waves-effect" data-bs-dismiss="modal" id="btn_pending_grn">  
                                    Transfer Items
                                </button>
                                <!-- Add to store -->
                                <button type="submit" name="btn_verify_transfer" class="btn bg-success-subtle text-success waves-effect" data-bs-dismiss="modal" id="btn_verify_transfer">  
                                    Verify Transfer
                                </button>
                                <!-- Add to cancle -->
                                <button type="submit" name="btn_cancle_transfer" class="btn bg-danger-subtle text-danger waves-effect" data-bs-dismiss="modal" id="btn_cancle_transfer">  
                                    Cancle Transfer
                                </button>
                                <?php 

                            }
                            else
                            {
                                if($edit==1)
                                {
                                    ?>
                                    <!-- Add to pending -->
                                    <button type="submit" name="btn_pending_transfer" class="btn bg-primary-subtle text-primary waves-effect" data-bs-dismiss="modal" id="btn_pending_grn">  
                                        Transfer Items
                                    </button>
                                    <?php 
                                }
                                
                                if($from_shop == $shop_id && $verify==1)
                                {
                                    ?>
                                    <!-- Add to cancle -->
                                    <button type="submit" name="btn_cancle_transfer" class="btn bg-danger-subtle text-danger waves-effect" data-bs-dismiss="modal" id="btn_cancle_transfer">  
                                    Cancle Transfer
                                    </button>
                                    <?php 
                                }//transfer shop

                                if($to_shop == $shop_id && $verify==1)
                                {
                                    ?>
                                    <!-- Add to store -->
                                    <button type="submit" name="btn_verify_transfer" class="btn bg-success-subtle text-success waves-effect" data-bs-dismiss="modal" id="btn_verify_transfer">  
                                    Verify Transfer
                                    </button>
                                    <!-- Add to cancle -->
                                    <button type="submit" name="btn_cancle_transfer" class="btn bg-danger-subtle text-danger waves-effect" data-bs-dismiss="modal" id="btn_cancle_transfer">  
                                    Cancle Transfer
                                    </button>
                                    <?php 
                                }//receive shop
                            }
                            
                        }//on hold make submit
                        else if($transfer_header_stat == '1')
                        {
                            $transObj = new Transfer();
                            $transData = $transObj->getOneTransferHeader($transfer_header_id);
                            $from_shop = $transData[0]['TransferFrom'];
                            $to_shop = $transData[0]['TransferTo'];

                            if($userType==1)
                            {
                                ?>
                                <!-- Add to store -->
                                <button type="submit" name="btn_verify_transfer" class="btn bg-success-subtle text-success waves-effect" data-bs-dismiss="modal" id="btn_verify_transfer">  
                                Verify Transfer
                                </button>
                                <!-- Add to cancle -->
                                <button type="submit" name="btn_cancle_transfer" class="btn bg-danger-subtle text-danger waves-effect" data-bs-dismiss="modal" id="btn_cancle_transfer">  
                                Cancle Transfer
                                </button>                                
                                <?php
                            }
                            else
                            {
                                if($from_shop == $shop_id && $verify==1)
                                {
                                    ?>
                                    <!-- Add to cancle -->
                                    <button type="submit" name="btn_cancle_transfer" class="btn bg-danger-subtle text-danger waves-effect" data-bs-dismiss="modal" id="btn_cancle_transfer">  
                                    Cancle Transfer
                                    </button>
                                    <?php 
                                }//transfer shop
    
                                if($to_shop == $shop_id && $verify==1)
                                {
                                    ?>
                                    <!-- Add to store -->
                                    <button type="submit" name="btn_verify_transfer" class="btn bg-success-subtle text-success waves-effect" data-bs-dismiss="modal" id="btn_verify_transfer">  
                                    Verify Transfer
                                    </button>
                                    <!-- Add to cancle -->
                                    <button type="submit" name="btn_cancle_transfer" class="btn bg-danger-subtle text-danger waves-effect" data-bs-dismiss="modal" id="btn_cancle_transfer">  
                                    Cancle Transfer
                                    </button>
                                    <?php 
                                }//receive shop
                            }
                            
                        
                        }//on pending make verify or cancle
                        else
                        {
                            ?>
                            <button type="button" class="btn bg-danger-subtle text-danger  waves-effect" data-bs-dismiss="modal" id="close_submit_grndetail">
                                Close
                            </button>
                            <?php 
                        }//cancled or undefined
                        ?>
                    </form>
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

    <script src="../Assets/jquery/transfer_details.js?v=20261001"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <?php
    if($scan_upload !== null)
    {
        include '../View/modals/scan-upload.php';
        ?>
    <script src="../Assets/jquery/scan_upload.js?v=20261001"></script>
        <?php
    }//scanner upload
    ?>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="../Assets/js/dashboard.js"></script>

</body>
</html>