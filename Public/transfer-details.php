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

            else if($_SESSION['transfer_detail_update'] == 3)
            {
                ?>
                <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                    Please select a <strong>Paymethod</strong> for the entered amount, or clear the amount to verify without a payment.
                </div>
                <?php
            }//amount without paymethod

            else if($_SESSION['transfer_detail_update'] == 4)
            {
                ?>
                <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                    The payment <strong>Amount</strong> is not valid.
                </div>
                <?php
            }//amount not valid

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
            <?php } ?>
            <!------------------------------------- Add Transfer Items ------------------------------------->
            <div class="card">
                <div class="card-body">
                    <?php 
                    if($userType==1)
                    {
                        ?>
                        <div class="table-responsive">
                        <input type="hidden" id="hide_header_id" value="<?php echo $transfer_header_id;?>">
                        <input type="hidden" id="hide_transfer_detail_id" value="0">
                        <input type="hidden" id="hide_inventory_id" value="0">

                        <?php 
                        if($transfer_header_stat <= 1)
                        {
                            ?>
                            <table>
                                <tr>
                                    <th style="min-width: 250px;">Product</th>
                                    <th style="min-width: 120px;">Batch No</th>
                                    <?php
                                        if($shopObj->hasVariation($shop_id))
                                        {
                                            ?>
                                            <th style="min-width: 150px;">Variation</th>
                                            <?php 
                                        }//has variations
                                    ?>
                                    <th style="min-width: 100px;">Current Qty</th>

                                    <?php 
                                        //transfer shop
                                        if($from_shop == $shop_id)
                                        {
                                            ?>
                                            <th style="min-width: 150px;">Transfer Qty</th>
                                            <?php 
                                        }//this is from shop

                                        //receiver shop
                                        if($to_shop == $shop_id)
                                        {
                                            ?>
                                            <th style="min-width: 150px;">Receive Qty</th>
                                            <?php 
                                        }//this is for receive shop
                                    ?>

                                    <th style="min-width: 150px;">Unit Purchase Price</th>
                                    <th style="min-width: 150px;">Unit Selling Price</th>

                                    <?php 
                                        if($shopObj->hasExpiry($shop_id))
                                        {
                                            ?>
                                            <th style="min-width: 150px;">Mnf Date</th>
                                            <th style="min-width: 150px;">Exp Date</th>
                                            <?php 
                                        }//has expire
                                    ?>
                                    <th >Total Amount</th>
                                    <th>Action</th>
                                </tr>
                                <tr>
                                    <td>
                                        <select name="cmb_products" id="cmb_products">
                                           <option value="">= = = Select Item = = =</option>
                                        </select>
                                        <input type="hidden" name="ids" id="ids" value="0">
                                        <br>
                                        <p id="product_detail"></p>
                                    </td>

                                    <!-- Batches -->
                                    <td>
                                        <select name="cmb_batch" id="cmb_batch" class="form-select"></select>
                                    </td>

                                    <?php 
                                        if($shopObj->hasVariation($shop_id))
                                        {
                                            ?>
                                            <td id="td_variation_id" style="display: none;">0</td>
                                            <td id="td_variation_name">0</td>
                                            <?php 
                                        }//has variation    
                                    ?>

                                    <td id="td_current_qty">0</td>

                                    <?php 
                                        //transfer shop
                                        if($from_shop == $shop_id)
                                        {
                                            ?>
                                            <td>
                                                <input type="number" step="0.001" name="transfer_qty" id="transfer_qty" class="form-control">
                                                <span style="display:none;" id="transfer_qty_warning" class="text-danger">Not valid Qty</span>
                                            </td>
                                            <?php 
                                        }//this is transfer shop

                                        //receive shop
                                        if($to_shop == $shop_id)
                                        {
                                            ?>
                                            <td>
                                                <input type="number" step="0.001" name="receive_qty" id="receive_qty" class="form-control">
                                                <span style="display:none;" id="receive_qty_warning" class="text-danger">Not valid Qty</span>
                                                <input type="hidden" name="hide_transfer_qty" id="hide_transfer_qty" value="0">
                                            </td>
                                            <?php 
                                        }//this is receive shop
                                    ?>

                                    <td>
                                        <input type="number" step="0.01" name="transfer_unit_amount" id="transfer_unit_amount" class="form-control">
                                        <span style="display:none;" id="transfer_price_warning" class="text-danger">Not valid Price</span>
                                    </td>

                                    <td>
                                        <input type="number" step="0.01" name="transfer_unit_sell" id="transfer_unit_sell" class="form-control">
                                        <span style="display:none;" id="transfer_sell_warning" class="text-danger">Not valid Price</span>
                                    </td>

                                    <?php 
                                        if($shopObj->hasExpiry($shop_id))
                                        {
                                            ?>
                                            <td>
                                                <input type="date" name="mnf_date" id="mnf_date" class="form-control required" placeholder="Manufacture Date">
                                                <span class="text-danger" id="mnf_warning" style="display: none;">Not Valid Date</span>
                                            </td>

                                            <td>
                                                <input type="date" name="exp_date" id="exp_date" class="form-control required" placeholder="Expire Date">
                                                <span class="text-danger" id="exp_warning" style="display: none;">Not Valid Date</span>
                                            </td>
                                            <?php 
                                        }//has expiry 
                                    ?>

                                    <td id="td_total_amount">0</td>

                                    <td>
                                        <?php
                                        //items are added from the sending shop's own stock only
                                        if($from_shop == $shop_id)
                                        {
                                            ?>
                                            <button type="button" name="btn_add_transfer_detail" id="btn_add_transfer_detail" class="btn border border-success bg-success">
                                            <i class="ti ti-plus"></i>
                                            </button>
                                            <?php
                                        }//sending shop
                                        ?>

                                        <button type="button" name="btn_edit_transfer_detail" id="btn_edit_transfer_detail" class="btn border border-warning bg-warning">
                                        <i class="ti ti-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            </table> 
                        <?php 
                        }//transfer is on hold or pending
                        ?>
                        
                        </div>
                        <?php
                    }
                    else
                    {
                        if($edit==1)
                        {
                            ?>
                            <div class="table-responsive">
                            <input type="hidden" id="hide_header_id" value="<?php echo $transfer_header_id;?>">
                            <input type="hidden" id="hide_transfer_detail_id" value="0">
                            <input type="hidden" id="hide_inventory_id" value="0">
    
                            <?php 
                            if($transfer_header_stat <= 1 && $from_shop == $shop_id )
                            {
                                ?>
                                <table>
                                    <tr>
                                        <th style="min-width: 250px;">Product</th>
                                        <th style="min-width: 120px;">Batch No</th>
                                        <?php
                                            if($shopObj->hasVariation($shop_id))
                                            {
                                                ?>
                                                <th style="min-width: 150px;">Variation</th>
                                                <?php 
                                            }//has variations
                                        ?>
                                        <th style="min-width: 100px;">Current Qty</th>
    
                                        <?php 
                                            //transfer shop
                                            if($from_shop == $shop_id)
                                            {
                                                ?>
                                                <th style="min-width: 150px;">Transfer Qty</th>
                                                <?php 
                                            }//this is from shop
    
                                            //receiver shop
                                            if($to_shop == $shop_id)
                                            {
                                                ?>
                                                <th style="min-width: 150px;">Receive Qty</th>
                                                <?php 
                                            }//this is for receive shop
                                        ?>
    
                                        <th style="min-width: 150px;">Unit Purchase Price</th>
                                        <th style="min-width: 150px;">Unit Selling Price</th>
    
                                        <?php 
                                            if($shopObj->hasExpiry($shop_id))
                                            {
                                                ?>
                                                <th style="min-width: 150px;">Mnf Date</th>
                                                <th style="min-width: 150px;">Exp Date</th>
                                                <?php 
                                            }//has expire
                                        ?>
                                        <th >Total Amount</th>
                                        <th>Action</th>
                                    </tr>
                                    <tr>
                                        <td>
                                            <select name="cmb_products" id="cmb_products">
                                               <option value="">= = = Select Item = = =</option>
                                            </select>
                                            <input type="hidden" name="ids" id="ids" value="0">
                                            <br>
                                            <p id="product_detail"></p>
                                        </td>
    
                                        <!-- Batches -->
                                        <td>
                                            <select name="cmb_batch" id="cmb_batch" class="form-select"></select>
                                        </td>
    
                                        <?php 
                                            if($shopObj->hasVariation($shop_id))
                                            {
                                                ?>
                                                <td id="td_variation_id" style="display: none;">0</td>
                                                <td id="td_variation_name">0</td>
                                                <?php 
                                            }//has variation    
                                        ?>
    
                                        <td id="td_current_qty">0</td>
    
                                        <?php 
                                            //transfer shop
                                            if($from_shop == $shop_id)
                                            {
                                                ?>
                                                <td>
                                                    <input type="number" step="0.001" name="transfer_qty" id="transfer_qty" class="form-control">
                                                    <span style="display:none;" id="transfer_qty_warning" class="text-danger">Not valid Qty</span>
                                                </td>
                                                <?php 
                                            }//this is transfer shop
    
                                            //receive shop
                                            if($to_shop == $shop_id)
                                            {
                                                ?>
                                                <td>
                                                    <input type="number" step="0.001" name="receive_qty" id="receive_qty" class="form-control">
                                                    <span style="display:none;" id="receive_qty_warning" class="text-danger">Not valid Qty</span>
                                                    <input type="hidden" name="hide_transfer_qty" id="hide_transfer_qty" value="0">
                                                </td>
                                                <?php 
                                            }//this is receive shop
                                        ?>
    
                                        <td>
                                            <input type="number" step="0.01" name="transfer_unit_amount" id="transfer_unit_amount" class="form-control">
                                            <span style="display:none;" id="transfer_price_warning" class="text-danger">Not valid Price</span>
                                        </td>
    
                                        <td>
                                            <input type="number" step="0.01" name="transfer_unit_sell" id="transfer_unit_sell" class="form-control">
                                            <span style="display:none;" id="transfer_sell_warning" class="text-danger">Not valid Price</span>
                                        </td>
    
                                        <?php 
                                            if($shopObj->hasExpiry($shop_id))
                                            {
                                                ?>
                                                <td>
                                                    <input type="date" name="mnf_date" id="mnf_date" class="form-control required" placeholder="Manufacture Date">
                                                    <span class="text-danger" id="mnf_warning" style="display: none;">Not Valid Date</span>
                                                </td>
    
                                                <td>
                                                    <input type="date" name="exp_date" id="exp_date" class="form-control required" placeholder="Expire Date">
                                                    <span class="text-danger" id="exp_warning" style="display: none;">Not Valid Date</span>
                                                </td>
                                                <?php 
                                            }//has expiry 
                                        ?>
    
                                        <td id="td_total_amount">0</td>
    
                                        <td>
                                            <button type="button" name="btn_add_transfer_detail" id="btn_add_transfer_detail" class="btn border border-success bg-success">
                                            <i class="ti ti-plus"></i>
                                            </button>
    
                                            <button type="button" name="btn_edit_transfer_detail" id="btn_edit_transfer_detail" class="btn border border-warning bg-warning">
                                            <i class="ti ti-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </table> 
                            <?php 
                            }//transfer is on hold or pending
                            ?>
                            
                            </div>
                            <?php
                        }
                    }
                    ?>
                    
                </div>
            </div>
            
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
                            <th>Unit Purchase Price</th>
                            <th>Unit Selling Price</th>
                            <th>Total Amount</th>
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
                            $transfer_total_amount = 0;
                            foreach($dbData as $row)
                            {
                                $count += 1;
                                $transfer_row_count += 1;
                                $transfer_item_count += floatval($row['TransferQty']);
                                $receive_item_count += floatval($row['ReceivedQty']);
                                $transfer_total_amount += floatval($row['TransferTotalAmount']);
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
                                    <td><?php echo $row['UnitPurchasePrice'] + 0;?></td><!-- 5 -->
                                    <td><?php echo $row['UnitSellingPrice'] + 0;?></td><!-- 6 -->
                                    <td><?php echo $row['TransferTotalAmount'] + 0;?></td><!-- 7 -->
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
                                            <tr>
                                                <th>Transfer Amount</th>
                                                <th>
                                                    <h4 id="sub_purchase_price"><?php echo $transfer_total_amount;?></h4>
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
                        //a payment is recorded only when the transfer is verified, so the (optional)
                        //payment fields are shown to whoever sees the Verify button
                        $can_verify_transfer = ($transfer_header_stat == '0' || $transfer_header_stat == '1')
                            && ($userType == 1 || ($to_shop == $shop_id && $verify == 1));
                        if($can_verify_transfer)
                        {
                            ?>
                        <!-- transfer payment (optional) -->
                        <div class="row m-2">
                            <div class="col-md-4">
                                <label class="form-label">Select a Paymethod <small class="text-muted">(optional)</small></label>
                                <select name="cmb_paymethod" id="cmb_paymethod" class="form-select">
                                    <option value="">No payment</option>
                                    <?php
                                        $dbObj = new DBTransactions();
                                        $sql = "SELECT * FROM `shoppaymethod`
                                        INNER JOIN paymethod ON paymethod.PMID = shoppaymethod.paymethod_PMID
                                        WHERE shoppaymethod.shop_SHID = ".$shop_id.";";

                                        $payData = $dbObj->getData($sql);

                                        foreach($payData as $row)
                                        {
                                            ?>
                                            <option value="<?php echo $row['paymethod_PMID'];?>"><?php echo $row['PaymethodName'];?></option>
                                            <?php
                                        }//foreach
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Amount <small class="text-muted">(optional)</small></label>
                                <input type="number" id="transfer_amount" name="transfer_amount" class="form-control mb-2" placeholder="Enter Amount" min="0" step="0.01">
                            </div>
                            <div class="col-md-12">
                                <small class="text-muted">Leave the amount empty to verify the transfer without a payment.</small>
                            </div>
                        </div>
                            <?php
                        }//can verify
                        ?>

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

    <script src="../Assets/jquery/transfer_details.js?v=20260922"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <?php
    if($scan_upload !== null)
    {
        include '../View/modals/scan-upload.php';
        ?>
    <script src="../Assets/jquery/scan_upload.js?v=20260922"></script>
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