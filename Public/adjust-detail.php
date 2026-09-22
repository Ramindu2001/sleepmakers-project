<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';

$shop_id = $_SESSION['shop_id'];
$adjust_header_id = 0;
if(isset($_POST['adjust_header_id']))
{
    $adjust_header_id = $_POST['adjust_header_id'];
    $adjust_header_stat = $_POST['adjust_header_stat'];
}//id set
else
{
    $_SESSION['adjust_update'] == 0;
    header("Location: transfer-header.php");
    die("Error: no transfer header id.");
}//no id
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
        $feature_id=3;
        $checkview=$userObj->userAcces($userRole_id,$feature_id);
        $create=$checkview[0]["is_create"];
        $view=$checkview[0]["is_view"];
        $edit=$checkview[0]["is_edit"];
        $delete=$checkview[0]["is_delete"];
        $verify=$checkview[0]["is_verify"];
        $print=$checkview[0]["is_print"];
        if($verify==1 || $edit==1)
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
        // include "../View/modals/submit-adjustdetail.php";
        ?>
        <style>
        .table>:not(caption)>*>* {
            padding: 10px;
        }
        </style>
        <!--  Header End -->

        <div class="container-fluid">
            <!-- messages -->
        <div class="container">

        </div>
            <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Adjustment Details</h5>

            <div class="card mt-2 mb-2">
            <div class="card-body">
                <div>
                    <?php 
                    if($adjust_header_stat == '0')
                    {
                        ?>
                        <span class="badge bg-primary mb-3">Adjustment is on Hold State</span>
                        <?php 
                    }//on hold
                    else if($adjust_header_stat == '1')
                    {
                        ?>
                        <span class="badge bg-warning mb-3">Adjustment is on Pending State</span>
                        <?php 
                    }//is pending

                    else if($adjust_header_stat == '2')
                    {
                        ?>
                        <span class="badge bg-success mb-3">Adjustment is on Verified</span>
                        <?php 
                    }//is pending
                    ?>
                </div>
                    
                <?php
                    $adjustObj = new Adjustment();
                    $adjustData = $adjustObj->getOneAdjustHeader($adjust_header_id);

                    $adjust_type = $adjustData[0]['AdjustmentType_ITID'];
                    $type_name = "";
                    $adjust_type_note = "";
                    if($adjust_type == '1')
                    {   
                        $adjust_type_note = "";
                        ?>
                        <span class="badge" style="background: radial-gradient(white, palegreen); color:black;">
                            Adjust by <b>ADDING</b> items to inventory.
                        </span>
                        <?php 
                    }//name
                    else
                    {
                        $adjust_type_note = "disabled";
                        ?>
                        <span class="badge" style="background: radial-gradient(white, IndianRed); color:black;">
                            <h4>Adjust by <b>REMOVING</b> items from inventory.</h4>
                        </span>
                        <?php 
                    }//else
                ?>
            </div>

            <?php 
                if($adjust_type == '1')
                {
                    ?>
                    <!-- Adjustment IN -->
                        <div class="card-body">
                            <div class="table-responsive">
                                <input type="hidden" id="hide_adjust_type" value="<?php echo $adjust_type;?>">
                                <input type="hidden" id="hide_adjust_detail_id" value="0">
                                <input type="hidden" id="hide_header_id" value="<?php echo $adjust_header_id;?>">

                                <?php 
                                $shopObj = new Shop();
                    
                                if($adjust_header_stat < 2)
                                {
                                    ?>
                                    <table class="table">
                                        <tr>
                                            <th style="min-width: 250px;">Product</th>

                                            <?php 
                                            if($shopObj->hasVariation($shop_id))
                                            {
                                                ?>
                                                    <th style="min-width:200px;">Variations</th>
                                                <?php 
                                            }//has variation
                                            ?>

                                            <th style="min-width:250px;">Batch</th>
                                            <th style="min-width:150px;" class="text-center">Avl.Qty</th>
                                            <th style="min-width:150px;">Qty</th>
                                            <th style="min-width:150px;">Purchase Price</th>
                                            <th style="min-width:150px;">Selling Price</th>

                                            <?php 
                                            if($shopObj->hasExpiry($shop_id))
                                            {
                                                ?>
                                                    <th style="min-width:120px;">Mnf Date</th>
                                                    <th style="min-width:120px;">Exp Date</th>
                                                <?php 
                                            }//has expiry
                                            ?>

                                            <th style="min-width:150px;">Action</th>
                                        </tr>
                                        <tr>
                                            <td>
                                                <select name="cmb_product" id="cmb_product" class="form-select required">
                                                    <option value="">=== Select an Item ===</option>
                                                </select> 
                                                <input type="hidden" name="ids" id="ids" value="0">
                                                <br>
                                                <p id="product_detail"></p>
                                            </td>

                                            <?php 
                                            if($shopObj->hasVariation($shop_id))
                                            {
                                                ?>
                                                <td>
                                                    <select name="cmb_variation" id="cmb_variation" class="form-select" <?php echo $adjust_type_note;?>>
                                                        <option value="1">No Variation</option>
                                                    </select>
                                                    <br>
                                                    <p id="variation_name"></p>
                                                </td>
                                                <?php 
                                            }//has variation
                                            ?>
                                           
                                            <td>
                                                <select name="cmb_batch" id="cmb_batch" class="form-select" <?php echo $adjust_type_note;?>>
                                                    <option value="">No Batch</option>
                                                </select>
                                                <br>
                                                <p id="batch_name"></p>
                                            </td>
                                            <td>
                                                <span class="text-danger text-center w-100 d-block" id="avl-qty">0.00</span>
                                            </td>
                                            <td>
                                                <input type="number" step="0.001" name="prod_qty" id="prod_qty" class="form-control required" placeholder="Qty">
                                                <span class="text-danger" id="qty_warning" style="display: none;">Not Valid Value</span>
                                            </td>

                                            <td>
                                                <span id="purchase_price_span">0.00</span>
                                                <input type="number" step="0.01" name="purchase_price" id="purchase_price" class="form-control d-none required" placeholder="Purchase Price" <?php echo $adjust_type_note;?>>
                                                <span class="text-danger" id="pur_warning" style="display: none;">Not Valid Value</span>
                                            </td>
                                    
                                            <td>
                                                <span id="selling_price_span">0.00</span>
                                                <input type="number" step="0.01" name="selling_price" id="selling_price" class="form-control d-none required" placeholder="Selling Price" <?php echo $adjust_type_note;?>>
                                                <span class="text-danger" id="sel_warning" style="display: none;">Not Valid Value</span>
                                            </td>

                                            <?php 
                                            if($shopObj->hasExpiry($shop_id))
                                            {
                                                ?>
                                                <td>
                                                    <span id="mnf_date_span">00-00-0000</span>
                                                    <input type="date" name="mnf_date" id="mnf_date" class="form-control d-none required" placeholder="Manufacture Date" <?php echo $adjust_type_note;?>>
                                                    <span class="text-danger" id="mnf_warning" style="display: none;">Not Valid Date</span>
                                                </td>

                                                <td>
                                                    <span id="exp_date_span">00-00-0000</span>
                                                    <input type="date" name="exp_date" id="exp_date" class="form-control d-none required" placeholder="Expire Date">
                                                    <span class="text-danger" id="exp_warning" style="display: none;">Not Valid Date</span>
                                                </td>
                                                <?php 
                                            }//has expiry
                                            ?>
                                            <td>
                                                <button type="button" name="btn_add_adjust_detail" id="btn_add_adjust_detail" class="btn border border-success bg-success">
                                                <i class="ti ti-plus"></i>
                                                </button>
                                                <button type="button" name="btn_edit_adjust_detail" id="btn_edit_adjust_detail" class="btn border border-warning bg-warning">
                                                <i class="ti ti-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </table>

                                    <div id="div_diff">
                                        <table class="table" id="tbl_items">
                                            
                                        </table>
                                    </div>
                                    <?php 
                                }//transfer is on hold or pending
                                ?>
                                
                            </div>
                        </div>
                    <?php 
                }//adjustment IN
                else
                {
                    ?>
                    <!-- Adjustment OUT -->
                        <div class="card-body">
                            
                            <input type="hidden" id="hide_header_id" value="<?php echo $adjust_header_id;?>">
                            <input type="hidden" id="hide_price_history_id" value="0">
                            <input type="hidden" id="hide_adjustout_id" value="0">
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="" class="form-label">Select Items</label>
                                    <select name="cmb_inventory" id="cmb_inventory"></select>
                                </div>

                                <div class="col-md-4">
                                    <label for="" class="form-label">Batch</label>
                                    <select name="cmb_out_batch" id="cmb_out_batch" class="form-select"></select>
                                </div>

                                <!-- <div class="col-md-5" id="div_available_qty">
                                    <p id="p_available_qty"></p>
                                    <input type="hidden" id="hide_available_qty" value="0">
                                    
                                </div> -->

                                <div class="col-md-2">
                                    <label for="" class="form-label">Qty</label>
                                    <input type="number" step="0.001" name="adjust_out_qty" id="adjust_out_qty" class="form-control">
                                    <span class="text-danger" id="adjust_out_warning" style="display: none;">Not valid value</span>
                                </div>

                                <div class="col-md-1">
                                    <button type="button" name="btn_add_adjust_out" id="btn_add_adjust_out" class="btn border border-success text-success mt-3 ">
                                        <i class="ti ti-plus"></i>
                                    </button>

                                    <!-- <button type="button" id="btn_test">Test</button> -->
                                </div>

                            </div>
                        </div>
                    <?php 
                }//adjustment OUT
            ?>

                <div class="card-body">
                <div class="container-fluid">
                    
                    <table class="table table-hover" id="tbl_adjust_detail">
                        <tr>
                            <td style="display: none;">0</td>
                            <th>Product</th>
                            <th>Variation</th>
                            <th>Transfer Qty</th>
                            <th>Unit Purchase Price</th>
                            <th>Unit Selling Price</th>
                            <th>Total Amount</th>
                            <?php 
                            if($adjust_header_stat < 2)
                            {
                                ?>
                                <th>Action</th>
                                <?php 
                            }
                            ?>
                        </tr>
                        <?php 
                            $sql = "SELECT * FROM adjustproddetails
                            INNER JOIN products ON products.PDID = adjustproddetails.products_PDID
                            LEFT JOIN variations ON variations.VRID = adjustproddetails.VariationID
                            WHERE AdjustHeader_AHID = ".$adjust_header_id.";";
                            $dbObj = new DBTransactions();
                            $dbData = $dbObj->getData($sql);
                            $count = 0;
                            $adjust_row_count = 0;
                            $adjust_item_count = 0;
                            $adjust_total_amount = 0;
                            foreach($dbData as $row)
                            {
                                $count += 1;
                                $adjust_row_count += 1;
                                $adjust_item_count += floatval($row['AdjustProdQty']);
                                $adjust_total_amount += floatval($row['AdjustProdAmount']);
                                ?>
                                <tr data-id="<?php echo $row['APID'];?>">
                                    <td style="display: none;"><?php echo $row['APID'];?></td><!-- 0 -->
                                    <td>
                                        <?php echo $row['Barcode']?><br>
                                        <?php echo $row['ItemName'];?>
                                    </td><!-- 1 -->
                                    <td><?php echo $row['VariationName'];?></td><!-- 2 -->
                                    <td><?php echo $row['AdjustProdQty'] + 0;?></td><!-- 3 -->
                                    <td><?php echo $row['UnitPurchasePrice'] + 0;?></td><!-- 4 -->
                                    <td><?php echo $row['UnitSellingPrice'] + 0;?></td><!-- 5 -->
                                    <td><?php echo $row['AdjustProdAmount'] + 0;?></td><!-- 6 -->
                                    <td style="display: none;"><?php echo $row['PDID'];?></td><!-- 7 -->
                                    <td style="display: none;"><?php echo $row['VariationID'];?></td><!-- 8 -->
                                    <td style="display: none;"><?php echo $row['MnfDate'];?></td><!-- 9 -->
                                    <td style="display: none;"><?php echo $row['ExpDate'];?></td><!-- 10 -->
                                    <td style="display: none;"><?php echo $row['RackID'];?></td><!-- 11 -->
                                    <?php 
                                    if($adjust_header_stat < 2)
                                    {
                                        ?>
                                        <td>
                                            <button type="button" class="btn border border-danger btn_delete_adjust"><i class="ti ti-x"></i></button>
                                        </td><!-- 12 -->
                                        <?php 
                                    }//on hold or pending
                                    ?>
                                    
                                </tr>
                                <?php 
                            }//foreach
                        ?>
                    </table>
                </div>

                <!-- show total -->

                <div class="row">
                    <div class="col-md-6"></div>
                    <div class="col-md-6">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Row Count</th> 
                                    <td>
                                        <h4 id="sub_row_count"><b><?php echo $adjust_row_count;?></b></h4>
                                    </td>
                                </tr> 
                                <tr>
                                    <th>No of Items</th> 
                                    <td>
                                        <h4 id="sub_item_count"><b><?php echo $adjust_item_count;?></b></h4>
                                    </td>
                                </tr>  
                                <tr>
                                    <th>Total Price</th> 
                                    <td>
                                        <h4 id="sub_purchase_price"><b><?php echo $adjust_total_amount;?></b></h4>
                                    </td>
                                </tr>  

                            </thead>
                            
                        </table>
                    </div>
                </div>

                <!-- submit GRN -->         
                    <form action="../Controller/adjustController.php" method="POST">
                    <input type="hidden" name="hide_adjustheader_id" id="hide_adjustheader_id" value="<?=$adjust_header_id?>">
                    <?php 
                    if($adjust_header_stat == '0')
                    {
                        if($userType==1)
                        {
                            ?>
                            <!-- Add to pending -->
                            <button type="submit" name="btn_pending_adjust" class="btn bg-primary-subtle text-primary waves-effect" style="margin-left:10px;" data-bs-dismiss="modal" id="btn_pending_grn">  
                                Add to Pending
                            </button>
                            <!-- Add to store -->
                            <button type="submit" name="btn_verify_adjust" class="btn bg-success-subtle text-success waves-effect" style="margin-left:10px;" data-bs-dismiss="modal" id="btn_verify_transfer">  
                                Verify Adjustment
                            </button>
                            <!-- Add to Cancel -->
                            <button type="submit" name="btn_cancle_adjust" class="btn bg-danger-subtle text-danger waves-effect" style="margin-left:10px;" data-bs-dismiss="modal" id="btn_cancle_transfer">  
                                Cancel
                            </button>


                            <?php 
                        }
                        else
                        {
                            if($edit==1)
                            {
                                ?>
                                <!-- Add to pending -->
                                <button type="submit" name="btn_pending_adjust" class="btn bg-primary-subtle text-primary waves-effect" style="margin-left:10px;" data-bs-dismiss="modal" id="btn_pending_grn">  
                                    Add to Pending
                                </button>                                
                                <?php
                            }
                            if($verify==1)
                            {
                                ?>
                                <!-- Add to store -->
                                <button type="submit" name="btn_verify_adjust" class="btn bg-success-subtle text-success waves-effect" style="margin-left:10px;" data-bs-dismiss="modal" id="btn_verify_transfer">  
                                    Verify Adjustment
                                </button>
                                <!-- Add to cancle -->
                                <button type="submit" name="btn_cancle_adjust" class="btn bg-danger-subtle text-danger waves-effect" style="margin-left:10px;" data-bs-dismiss="modal" id="btn_cancle_transfer">  
                                    Cancle
                                </button>
                                <?php                                
                            }
                        }
                    }//on hold make submit
                    else if($adjust_header_stat == '1')
                    {
                        if($userType==1)
                        {
                            ?>
                            <!-- Add to store -->
                            <button type="submit" name="btn_verify_adjust" class="btn bg-success-subtle text-success waves-effect" style="margin-left:10px;" data-bs-dismiss="modal" id="btn_verify_transfer">  
                                Verify Adjustment
                            </button>
                            <!-- Add to cancle -->
                            <button type="submit" name="btn_cancle_adjust" class="btn bg-danger-subtle text-danger waves-effect" style="margin-left:10px;" data-bs-dismiss="modal" id="btn_cancle_transfer">  
                                Cancel
                            </button>
                            <?php
                        }
                        else
                        {
                            if($verify==1)
                            {
                                ?>
                                <!-- Add to store -->
                                <button type="submit" name="btn_verify_adjust" class="btn bg-success-subtle text-success waves-effect" style="margin-left:10px;" data-bs-dismiss="modal" id="btn_verify_transfer">  
                                    Verify Adjustment
                                </button>
                                <!-- Add to cancle -->
                                <button type="submit" name="btn_cancle_adjust" class="btn bg-danger-subtle text-danger waves-effect" style="margin-left:10px;" data-bs-dismiss="modal" id="btn_cancle_transfer">  
                                    Cancel
                                </button>
                                <?php                                
                            }
                        }
                    }//on pending make verify or cancle
                    else if($adjust_header_stat == '2')
                    {
                        if($userType==1)
                        {
                            ?>
                             <!-- Add to pending -->
                            <button type="button" name="btn_print_adjust" class="btn bg-warning-subtle text-warning waves-effect" style="margin-left:10px;" data-bs-dismiss="modal" id="btn_print_grn">  
                                Print
                            </button>
                            <?php
                        }
                        else
                        {
                            if($print==1)
                            {
                                ?>
                                <!-- Add to pending -->
                                <button type="button" name="btn_print_adjust" class="btn bg-warning-subtle text-warning waves-effect" style="margin-left:10px;" data-bs-dismiss="modal" id="btn_print_grn">  
                                   Print
                                </button>
                                <?php
                            }
                        }
                      ?>
        
                      <?php 
                    }//verified make print available
                    else
                    {
                      ?>
                      <button type="button" class="btn bg-danger-subtle text-danger  waves-effect" style="margin-left:10px;" data-bs-dismiss="modal" id="close_submit_grndetail">
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

    <script src="../Assets/jquery/adjustment.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>

</body>
</html>