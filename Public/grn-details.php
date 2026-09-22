<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';

$grn_header_id = 0;
$grn_header_stat = 0;
$supplier_id = 0;

if(isset($_POST['grn_header_id']))
{
    $grn_header_id = $_POST['grn_header_id'];
    $grn_header_stat = $_POST['grn_header_stat'];
}//get header id
else
{
    //ask to pick a GRN, unless a message is already waiting (e.g. "GRN created" right after creating one)
    if(!isset($_SESSION['grnheader_update']))
    {
        $_SESSION['grnheader_update'] = 3;
    }
    header("Location: grn-header.php");
    die("Error: no grn header id.");
}//no header_id

?>
<!doctype html>
<html lang="en">

<head>
    <?php 
  include '../View/head.php';
  // include '../View/loader.php';
  ?>
    <style>
    .container.table-responsive {
        padding: 0 !important;
        margin: 0 !important;
        width: 100%;
    }

    @media (min-width: 1400px) {

        .container,
        .container-lg,
        .container-md,
        .container-sm,
        .container-xl,
        .container-xxl {
            max-width: 100%;
        }
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
        $feature_id=2;
        $checkview=$userObj->userAcces($userRole_id,$feature_id);
        $create=$checkview[0]["is_create"];
        $view=$checkview[0]["is_view"];
        $edit=$checkview[0]["is_edit"];
        $delete=$checkview[0]["is_delete"];
        $verify=$checkview[0]["is_verify"];
        $print=$checkview[0]["is_print"];
        if($edit==1 || $verify==1)
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

    include "../View/modals/add_grn_product.php";
    ?>
            <!--  Sidebar End -->
            <!--  Main wrapper -->
            <div class="body-wrapper">
                <!--  Header Start -->
                <?php 
        include '../View/header.php';
        // include "../View/modals/submit-grndetails.php";
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
                        <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show"
                            role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                            Please select an <strong>Effective Date.</strong>
                        </div>
                        <?php 
                }//no date
                else if($_SESSION['grnheader_update'] == 1)
                {
                    ?>
                        <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show"
                            role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                            Please enter <strong>GRN Invoice No.</strong>
                        </div>
                        <?php
                }//no invoice
                else if($_SESSION['grnheader_update'] == 2)
                {
                    ?>
                        <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show"
                            role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                            <strong>GRN created </strong>successfully!
                        </div>
                        <?php
                }//save success
                else if($_SESSION['grnheader_update'] == 3)
                {
                    ?>
                        <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show"
                            role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                            <strong>Category updated </strong>successfully!
                        </div>
                        <?php
                }//update success
                else
                {
                    ?>
                        <div class="alert alert-danger">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                            <strong>Oops! </strong>something went wrong!
                        </div>
                        <?php 
                }//else
                unset($_SESSION['grnheader_update']);
            }//session set
            ?>

                    </div>
                    <!-- GRN Header -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">GRN Header</h5>

                            <?php 
                    if($grn_header_stat == '0')
                    {
                        ?>
                            <span class="badge bg-primary mt-2 mb-2">GRN is <b>on Hold</b></span>
                            <?php 
                    } //hold
                    else if($grn_header_stat == '1')
                    {
                        ?>
                            <span class="badge bg-warning mt-2 mb-2">GRN is <b>Pending</b></span>
                            <?php 
                    } //pending
                    else if($grn_header_stat == '2')
                    {
                        ?>
                            <span class="badge bg-success mt-2 mb-2">GRN is <b>Verified</b></span>
                            <?php 
                    } //verified
                    else if($grn_header_stat == '3')
                    {
                        ?>
                            <span class="badge bg-danger mt-2 mb-2">GRN is <b>Canceled</b></span>
                            <?php 
                    } //cancled
                    else
                    {
                        ?>
                            <span class="badge bg-danger mt-2 mb-2">GRN is <b>Undefined</b></span>
                            <?php 
                    } //undefined
                ?>
                            <?php 
                    $dataObj = new DBTransactions();
                    
                    $sql = "SELECT * FROM grnheader 
                    INNER JOIN user ON user.USID = grnheader.user_USID
                    INNER JOIN suppliers ON suppliers.SPID = grnheader.Suppliers_SPID
                    WHERE GHID = ".$grn_header_id.";";

                    $grnOne = $dataObj->getData($sql);

                    $grn_no = $grnOne[0]['GRNHeaderNo'];
                    $user_name = $grnOne[0]['UserName'];
                    $invoice_no = $grnOne[0]['InvoiceNo'];
                    $effective_date = $grnOne[0]['EffectiveDate'];
                    $supplier_id = $grnOne[0]['Suppliers_SPID'];

                    $DiscType = $grnOne[0]['PurchDiscType'];
                    $SaleDiscount = $grnOne[0]['PurchDisc'];
                    $TotalDiscount = $grnOne[0]['TotalDisc'];

                ?>
                            <p>
                                GRN No: <?php echo $grn_no;?><br>
                                User: <?php echo $user_name;?>
                            </p>


                            <div class="row">
                                <!-- left column -->
                                <div class="col-md-6">
                                    <label for="" class="form-label">Effective Date</label>
                                    <input type="date" name="effective_date" value="<?php echo $effective_date;?>"
                                        class="form-control">

                                    
                                </div>
                                <!-- right column -->
                                <div class="col-md-6">
                                    <label for="" class="form-label">Invoice No</label>
                                    <input type="text" name="invoice_no" value="<?php echo $invoice_no;?>"
                                        class="form-control" placeholder="Supplier Invoice No" disabled>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">
                                GRN Detail
                                <button class="btn btn-primary float-end" id="btn_open_grn_products">Add
                                    Products</button>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="container-fluid">
                                <div class="container table-responsive">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <span class="text-danger">Note: - Prices must be entered in the selling unit
                                                price.</span>
                                        </div>
                                    </div>
                                    <!-- hidden input -->
                                    <input type="hidden" name="hide_header_id" id="hide_header_id"
                                        value="<?php echo $grn_header_id;?>">
                                    <input type="hidden" name="hide_detail_id" id="hide_detail_id" value="0">

                                    <?php 
                            $shopObj = new Shop();

                            if($grn_header_stat <2)
                            {

                            ?>
                                    <table class="table table-hover" id="tbl_add_details">
                                        <tr>
                                            <th style="min-width: 250px;">Product</th>
                                            <?php 
                                        if($shopObj->hasVariation($shop_id))
                                        {
                                            ?>
                                            <th style="min-width:150px;">Variations</th>
                                            <?php 
                                        }//has variation
                                    ?>
                                            <th style="min-width:150px;">Qty</th>
                                            <th style="min-width:150px;">Purchase Price</th>

                                            <?php 
                                        if($shopObj->hasLabelPrice($shop_id))
                                        {
                                            ?>
                                            <th style="min-width:150px;">Label Price</th>
                                            <?php 
                                        }//has label price
                                    ?>

                                            <th style="min-width:150px;">Selling Price</th>

                                            <?php 
                                        if($shopObj->hasExpiry($shop_id))
                                        {
                                            ?>
                                            <th style="min-width:120px;">Mnf Date</th>
                                            <th style="min-width:120px;">Exp Date</th>
                                            <?php 
                                        }//has expiry

                                        if($shopObj->hasRacks($shop_id))
                                        {
                                            ?>
                                            <th style="min-width:150px;">Section & Racks</th>
                                            <?php 
                                        }//has racks
                                    ?>
                                            <th style="min-width:150px;">Action</th>
                                        </tr>
                                        <tr>
                                            <td>
                                                <select name="cmb_product" id="cmb_product"
                                                    class="form-select required">
                                                    <option value="">=== Select an Item ===</option>
                                                </select>
                                                <span class="text-danger" style="display: none;"
                                                    id="product_warning">Please select product</span>
                                                <input type="hidden" name="ids" id="ids" value="0">
                                                <br>
                                                <p id="product_detail"></p>
                                            </td>

                                            <?php 
                                        if($shopObj->hasVariation($shop_id))
                                        {
                                            ?>
                                            <td>
                                                <select name="cmb_variation" id="cmb_variation" class="form-select">
                                                    <option value="0">No Variation</option>
                                                </select>
                                            </td>
                                            <?php 
                                        }//has variation
                                    ?>

                                            <td>
                                                <input type="number" step="0.001" name="prod_qty" id="prod_qty"
                                                    class="form-control required" placeholder="Qty">
                                                <span class="text-danger" id="qty_warning" style="display: none;">Not
                                                    Valid Value</span>
                                            </td>

                                            <td>
                                                <input type="number" step="0.01" name="purchase_price"
                                                    id="purchase_price" class="form-control required"
                                                    placeholder="Purchase Price">
                                                <span class="text-danger" id="pur_warning" style="display: none;">Not
                                                    Valid Value</span>
                                            </td>

                                            <?php 
                                    if($shopObj->hasLabelPrice($shop_id))
                                    {
                                        ?>
                                            <td>
                                                <input type="number" step="0.01" name="label_price" id="label_price"
                                                    class="form-control required" placeholder="Label Price">
                                                <span class="text-danger" id="lab_warning" style="display: none;">Not
                                                    Valid Value</span>
                                            </td>
                                            <?php 
                                    }//has label price
                                    ?>

                                            <td>
                                                <input type="number" step="0.01" name="selling_price" id="selling_price"
                                                    class="form-control required" placeholder="Selling Price">
                                                <span class="text-danger" id="sel_warning" style="display: none;">Not
                                                    Valid Value</span>
                                            </td>

                                            <?php 
                                        if($shopObj->hasExpiry($shop_id))
                                        {
                                            ?>
                                            <td>
                                                <input type="date" name="mnf_date" id="mnf_date"
                                                    class="form-control mb-3 required" placeholder="Manufacture Date">
                                                <span class="text-danger" id="mnf_warning" style="display: none;">Not
                                                    Valid Date</span>
                                            </td>

                                            <td>
                                                <input type="date" name="exp_date" id="exp_date"
                                                    class="form-control required" placeholder="Expire Date">
                                                <span class="text-danger" id="exp_warning" style="display: none;">Not
                                                    Valid Date</span>
                                            </td>
                                            <?php 
                                        }//has expiry
                                    ?>
                                            <!-- shop has racks -->
                                            <?php 
                                        if($shopObj->hasRacks($shop_id))
                                        {
                                            ?>
                                            <td>
                                                <!-- select section -->
                                                <select name="cmb_section" id="cmb_section" class="form-select"
                                                    style="min-width:100px;">
                                                    <!-- <option>Select Section</option> -->
                                                    <?php 
                                                    $sectionObj = new Section();
                                                    $sectionData = $sectionObj->getAllSections($shop_id);
                                                    foreach($sectionData as $row)
                                                    {
                                                        ?>
                                                    <option value="<?php echo $row['SEID'];?>">
                                                        <?php echo $row['SectionName'];?></option>
                                                    <?php 
                                                    }//foreach  
                                                    ?>
                                                </select>

                                                <!-- Select Racks -->
                                                <select name="cmb_racks" id="cmb_racks" class="form-select mt-1"
                                                    style="min-width:100px;">
                                                    <?php 
                                                    $rackData = $sectionObj->getAllRack($shop_id);
                                                    foreach($rackData as $row)
                                                    {
                                                        ?>
                                                    <option value="<?php echo $row['RKID'];?>">
                                                        <?php echo $row['RackName'];?></option>
                                                    <?php 
                                                    }//foreach
                                                    ?>
                                                </select>
                                            </td>
                                            <?php 
                                        }//has racks
                                    ?>

                                            <td>
                                                <button type="button" name="btn_add_grn_detail" id="btn_add_grn_detail"
                                                    class="btn border border-success bg-success">
                                                    <i class="ti ti-plus"></i>
                                                </button>

                                                <button type="button" name="btn_edit_grn_detail"
                                                    id="btn_edit_grn_detail"
                                                    class="btn border border-warning bg-warning">
                                                    <i class="ti ti-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </table>
                                    <?php 
                            }//grn stat is on hold or pending
                        ?>
                                </div>

                                <div class="container table-responsive">
                                    <table id="tbl_grn_details" class="table table-hover">
                                        <tr>
                                            <th style="min-width:250px;">Product</th>
                                            <?php 
                                        if($shopObj->hasVariation($shop_id))
                                        {
                                            ?>
                                            <th style="min-width:100px;">Variations</th>
                                            <?php 
                                        }//has variation
                                    ?>

                                            <th style="min-width:100px;">Qty</th>
                                            <th style="min-width:100px;">Purchase Price</th>

                                            <?php 
                                    if($shopObj->hasLabelPrice($shop_id))
                                    {   
                                        ?>
                                            <th style="min-width:100px;">Label Price</th>
                                            <?php 
                                    }//has label price
                                    ?>

                                            <th style="min-width:100px;">Selling Price</th>
                                            <th style="min-width:100px;">Total Purchase</th>
                                            <th style="min-width:100px;">Total Selling</th>

                                            <?php 
                                    if($shopObj->hasExpiry($shop_id))
                                    {
                                        ?>
                                            <th style="min-width:120px;">Mnf Date</th>
                                            <th style="min-width:120px;">Exp Date</th>
                                            <?php 
                                    }//has expire date
                                    ?>

                                            <?php 
                                    if($shopObj->hasRacks($shop_id))
                                    {
                                        ?>
                                            <th style="min-width:100px;">Section & Racks</th>
                                            <?php 
                                    }//has racks
                                    ?>
                                            <?php 
                                        if($grn_header_stat < 2)
                                        {
                                            ?>
                                            <th style="min-width:150px;">Action</th>
                                            <?php
                                        }?>
                                        </tr>
                                        <tbody id="tbody">
                                            <?php 
                                    $sql = "SELECT GDID, PDID, Barcode, ItemName, VRID, VariationName, InitQty, UnitPurchasePrice, UnitLabelPrice, UnitSellPrice, TotalPurchasePrice, TotalSellPrice, MnfDate, ExpDate, SEID, SectionName, RKID, RackName, UNID, ShortName FROM grndetails
                                    LEFT JOIN products ON products.PDID = grndetails.products_PDID
                                    LEFT JOIN variations ON variations.VRID = grndetails.VariationID
                                    LEFT JOIN units ON units.UNID = products.PurchaseUnit
                                    LEFT JOIN rack ON rack.RKID = grndetails.Rack_RKID
                                    LEFT JOIN sections ON sections.SEID = rack.Sections_SEID
                                    WHERE GRNHeader_GHID = ".$grn_header_id." ORDER BY GDID DESC;";

                                    $dbObj = new DBTransactions();
                                    $dbData = $dbObj->getData($sql);    

                                    $grn_purchase_price = 0;
                                    $grn_item_count = 0;
                                    $grn_row_count = 0;

                                  
                                    foreach($dbData as $row)
                                    {
                                        $grn_row_count += 1;
                                        $grn_item_count += floatval($row['InitQty']);
                                        $grn_purchase_price += floatval($row['TotalPurchasePrice']);
                                    ?>
                                            <tr data-id="<?php echo $row['GDID'];?>">
                                                <td style="display: none;"><?php echo $row['GDID'];?></td><!-- 0 -->
                                                <td style="display: none;"><?php echo $row['InitQty'];?></td><!-- 1 -->
                                                <td style="display: none;"><?php echo $row['TotalPurchasePrice'];?></td>
                                                <!-- 2 -->

                                                <td>
                                                    <?php echo $row['Barcode'];?> <br>
                                                    <?php echo $row['ItemName'];?>
                                                </td><!-- 1 -->

                                                <?php 
                                        if($shopObj->hasVariation($shop_id))
                                        {
                                            ?>
                                                <td><?php echo $row['VariationName'];?></td><!-- 2 -->
                                                <?php 
                                        }//has variations
                                        ?>

                                                <td><?php echo $row['InitQty'] + 0 . " " . $row['ShortName'];?></td>
                                                <!-- 3 -->
                                                <td><?php echo $row['UnitPurchasePrice'];?></td><!-- 4 -->

                                                <?php 
                                        if($shopObj->hasLabelPrice($shop_id))
                                        {
                                            ?>
                                                <td><?php echo $row['UnitLabelPrice'];?></td><!-- 5 -->
                                                <?php 
                                        }//has label price
                                        ?>

                                                <td><?php echo $row['UnitSellPrice'];?></td><!-- 6 -->
                                                <td><?php echo $row['TotalPurchasePrice'];?></td><!-- 7 -->
                                                <td><?php echo $row['TotalSellPrice'];?></td><!-- 8 -->

                                                <?php 
                                        if($shopObj->hasExpiry($shop_id))
                                        {
                                            ?>
                                                <td><?php echo $row['MnfDate'];?></td><!-- 9 -->
                                                <td><?php echo $row['ExpDate'];?></td><!-- 10 -->
                                                <?php 
                                        }//has expiry

                                        //has racks
                                        if($shopObj->hasExpiry($shop_id))
                                        {
                                            ?>
                                                <td>
                                                    <?php echo $row['SectionName'];?> <br>
                                                    <?php echo $row['RackName'];?>
                                                </td><!-- 11 -->
                                                <?php 
                                        }//has racks
                                        ?>
                                                <td style="display: none;"><?php echo $row['PDID'];?></td><!-- 12 -->
                                                <td style="display: none;"><?php echo $row['InitQty'];?></td><!-- 13 -->
                                                <td style="display: none;"><?php echo $row['SEID'];?></td><!-- 14 -->
                                                <td style="display: none;"><?php echo $row['RKID'];?></td><!-- 15 -->
                                                <td style="display: none;"><?php echo $row['VRID'];?></td><!-- 16 -->
                                                <?php  
                                        if($grn_header_stat < 2)
                                        {
                                            ?>
                                                <td>
                                                    <button type="button" id="btn_grndetail_<?php echo $row['GDID']?>"
                                                        class="btn_grn_edit btn border border-primary"><i
                                                            class="ti ti-edit"></i></button>
                                                    <button type="button"
                                                        id="btn_grndetail_delete_<?php echo $row['GDID']?>"
                                                        class="btn_grn_delete btn border border-danger"><i
                                                            class="ti ti-x"></i></button>
                                                </td>
                                                <?php 
                                        } //hold or pending stat
                                        ?>
                                            </tr>
                                            <?php 
                                    } //foreach
                                ?>
                                        </tbody>
                                    </table>

                                </div>
                                <div class="row">
                                    <div class="col-md-6"></div>
                                    <div class="col-md-6">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Row Count</th>
                                                    <td>
                                                        <h4 id="sub_row_count" style="text-align: right;">
                                                            <b><?php echo $grn_row_count;?></b></h4>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>No. Of Items</th>
                                                    <td>
                                                        <h4 id="sub_item_count" style="text-align: right;">
                                                            <b><?php echo $grn_item_count;?></b></h4>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <th>Total Purchase</th>  
                                                    <td>
                                                        <h4 id="sub_purchase_price" style="text-align: right;">
                                                            <b><?php echo $grn_purchase_price;?></b></h4>
                                                    </td>
                                                </tr>

                                                <tr>
                                                        <th>
                                                            <b class="det">Purch Disc. Type</b>
                                                        </th>
                                                        <td colspan="2">
                                                            <select name="SaleDiscountType" id="SaleDiscountType" class="form-select" >
                                                                <option value="1" <?php echo ($DiscType == 1) ? 'selected' : ''; ?>>Percentage</option>
                                                                <option value="2" <?php echo ($DiscType == 2) ? 'selected' : ''; ?>>Flat Amount</option>                                                                
                                                            </select>
                                                        </td>
                                                </tr>
                                                <tr>
                                                        <th>
                                                            <b class="det">Purch Disc.</b>
                                                        </th>

                                                        <td colspan="2">
                                                            <input type="text" name="saleDiscount" id="saleDiscount" class="form-control text-end"  value=<?php echo number_format((float)$SaleDiscount,2);?>>
                                                            
                                                        </td>
                                                </tr>

                                                <tr>
                                                        <th >
                                                            <b class="det">Total Disc.</b>
                                                        </th>
                                                        <td colspan="2">
                                                            <input type="text" name="totalDiscount" id="totalDiscount" class="form-control text-end" value=<?php echo number_format((float)$TotalDiscount,2);?> readonly>
                                                        </td>
                                                </tr>
                                              

                                                <tr>
                                                    <th>Grand Total</th>  
                                                    <td>
                                                        <h4 id="grand_total" style="text-align: right;">
                                                            <b><?php echo $grn_purchase_price;?></b>
                                                        </h4>
                                                    </td>
                                                </tr>
                                                                                                
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                                <!-- submit GRN -->
                                <form action="../Controller/grnController.php" method="POST" id="grn-form">

                                <input type="hidden" name="hiddenSaleDiscount" id="hiddenSaleDiscount">
                                <input type="hidden" name="hiddenTotalDiscount" id="hiddenTotalDiscount">
                                <input type="hidden" name="hiddenDiscountType" id="hiddenDiscountType">

                                <label for="" class="form-label">Select Supplier</label>
                                    <select name="cmb_edit_supplier" id="cmb_edit_supplier" class="form-select"
                                        >
                                        <?php 
                        $shop_id = $_SESSION['shop_id'];
                        $supObj = new Supplier();
                        $supData = $supObj->getAllActiveSuppliers($shop_id,1);
                        foreach($supData as $row)
                        {
                        ?>
                                        <option value="<?php echo $row['SPID'];?>" <?php if($row['SPID']==$supplier_id){echo "Selected";}?>>
                                            <?php echo $row['SupplierName'] ." ~ ". $row['Contact'];?></option>
                                        <?php 
                        }//foreach
                        ?>
                                    </select>
                                    <input type="hidden" name="hide_grnheader_id" id="hide_grnheader_id"
                                        value="<?=$grn_header_id?>">
                                        <div class="row">
                                <div class="col-md-10 mt-2">
                                    <div class="p-2">
                                        <div class="row mb-3" id="payment_methods">
                                            <div id="payment_method" class="row">
                                                <div class="col-md-4 mb-2">
                                                    <label for="" class="form-label">Payment Type</label>
                                                    <?php                                     
                                            if($grn_header_stat == '2')
                                            {   
                                            ?>
                                                    <select name="pay_id[]" id="pay_id" class="form-select" disabled>
                                                        <?php 
                                                $sql = "SELECT * FROM shoppaymethod
                                                INNER JOIN paymethod ON paymethod.PMID = shoppaymethod.paymethod_PMID
                                                WHERE shop_SHID = ".$shop_id." AND (paymethod.PMID!=6 AND paymethod.PMID!=4  AND paymethod.PMID!=9  AND paymethod.PMID!=10 AND paymethod.PMID!=12);";
                                                $dbObj = new DBTransactions();
                                                $dbPaymethods = $dbObj->getData($sql);
                                                $count = 0;
                                                foreach($dbPaymethods as $row)
                                                {
                                                    $is_checked = $count==0 ? 'checked' : '';
                                                    ?>
                                                        <option value="<?php echo $row['paymethod_PMID'];?>">
                                                            <?php echo $row['PaymethodName'];?></option>
                                                        <?php 
                                                    $count += 1;
                                                }//foreach
                                            ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <label for="paid-input" class="form-label">Paid</label>
                                                    <input type="text" class="form-control" name="paid[]"
                                                        id="paid-input" placeholder="0.00" disabled>
                                                </div>
                                                <!-- <div class="col-md-4 mb-2">
                                            <a href="javascript:void(0);" class="btn btn-primary mt-4" id="add-payment" onclick="addPayment()">Add Payment</a>
                                        </div> -->

                                                <?php                                     
                                            }
                                            else
                                            {
                                        ?>
                                                <select name="pay_id[]" id="pay_id" class="pay_id form-select">
                                                    <?php 
                                                $sql = "SELECT * FROM shoppaymethod
                                                INNER JOIN paymethod ON paymethod.PMID = shoppaymethod.paymethod_PMID
                                                WHERE shop_SHID = ".$shop_id." AND (paymethod.PMID!=6 AND paymethod.PMID!=4  AND paymethod.PMID!=9  AND paymethod.PMID!=10 AND paymethod.PMID!=12);";
                                                $dbObj = new DBTransactions();
                                                $dbPaymethods = $dbObj->getData($sql);
                                                $count = 0;
                                                foreach($dbPaymethods as $row)
                                                {
                                                    $is_checked = $count==0 ? 'checked' : '';
                                                    ?>
                                                    <option value="<?php echo $row['paymethod_PMID'];?>">
                                                        <?php echo $row['PaymethodName'];?></option>
                                                    <?php 
                                                    $count += 1;
                                                }//foreach
                                            ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label for="paid-input" class="form-label">Paid</label>
                                                <input type="text" class="form-control" name="paid[]" id="paid-input"
                                                    placeholder="0.00">
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <a href="javascript:void(0);" class="btn btn-primary mt-4"
                                                    id="add-payment" onclick="addPayment()">Add Payment</a>
                                            </div>
                                            <?php                                     
                                            }                                           
                                        ?>

                                        </div>
                                    </div>
                                </div>
                            </div>
                                    <div>
                                        <?php 
                                    if($grn_header_stat == '0')
                                    {
                                        if($userType!=1)
                                        {
                                            if($edit==1)
                                            {
                                                ?>
                                        <button type="submit" name="btn_pending_grn"
                                            class="btn bg-primary-subtle text-primary waves-effect"
                                            style="margin-left:10px;" data-bs-dismiss="modal" id="btn_pending_grn">
                                            Add to Pending
                                        </button>
                                        <?php
                                            }
                                            if($verify==1)
                                            {
                                                ?>
                                        <button type="submit" name="btn_verify_grn"
                                            class="btn bg-success-subtle text-success waves-effect"
                                            style="margin-left:10px;" data-bs-dismiss="modal" id="btn_verify_grn">
                                            Verify GRN
                                        </button>
                                        <!-- Add to cancle -->
                                        <button type="submit" name="btn_cancle_grn"
                                            class="btn bg-danger-subtle text-danger waves-effect"
                                            style="margin-left:10px;" data-bs-dismiss="modal" id="btn_cancle_grn">
                                            Cancel GRN
                                        </button>
                                        <?php
                                            }
                                        }
                                        else
                                        {
                                            ?>
                                        <button type="submit" name="btn_pending_grn"
                                            class="btn bg-primary-subtle text-primary waves-effect"
                                            style="margin-left:10px;" data-bs-dismiss="modal" id="btn_pending_grn">
                                            Add to Pending
                                        </button>
                                        <button type="submit" name="btn_verify_grn"
                                            class="btn bg-success-subtle text-success waves-effect"
                                            style="margin-left:10px;" data-bs-dismiss="modal" id="btn_verify_grn">
                                            Verify GRN
                                        </button>
                                        <!-- Add to cancle -->
                                        <button type="submit" name="btn_cancle_grn"
                                            class="btn bg-danger-subtle text-danger waves-effect"
                                            style="margin-left:10px;" data-bs-dismiss="modal" id="btn_cancle_grn">
                                            Cancel GRN
                                        </button>
                                        <?php
                                        }
                                        ?>
                                        <!-- Add to pending -->

                                        <?php 
                                    }//on hold make submit
                                    else if($grn_header_stat == '1')
                                    {
                                        if($userType!=1)
                                        {
                                            if($verify==1)
                                            {
                                                ?>
                                        <!-- Add to store -->
                                        <button type="submit" name="btn_verify_grn"
                                            class="btn bg-success-subtle text-success waves-effect"
                                            style="margin-left:10px;" data-bs-dismiss="modal" id="btn_verify_grn">
                                            Verify GRN
                                        </button>
                                        <!-- Add to cancle -->
                                        <button type="submit" name="btn_cancle_grn"
                                            class="btn bg-danger-subtle text-danger waves-effect"
                                            style="margin-left:10px;" data-bs-dismiss="modal" id="btn_cancle_grn">
                                            Cancel GRN
                                        </button>
                                        <?php
                                            }
                                        }
                                        else
                                        {
                                            ?>
                                        <!-- Add to store -->
                                        <button type="submit" name="btn_verify_grn"
                                            class="btn bg-success-subtle text-success waves-effect"
                                            style="margin-left:10px;" data-bs-dismiss="modal" id="btn_verify_grn">
                                            Verify GRN
                                        </button>
                                        <!-- Add to cancle -->
                                        <button type="submit" name="btn_cancle_grn"
                                            class="btn bg-danger-subtle text-danger waves-effect"
                                            style="margin-left:10px;" data-bs-dismiss="modal" id="btn_cancle_grn">
                                            Cancel GRN
                                        </button>
                                        <?php
                                        }
                                    ?>

                                        <?php 
                                    }//on pending make verify or cancle
                                    else if($grn_header_stat == '2')
                                    {
                                        if($userType!=1)
                                        {
                                            if($print==1)
                                            {
                                                ?>
                                        <a href="../Reports/grn_detail.php?header_id=<?=$grn_header_id?>"
                                            class="btn bg-warning-subtle text-warning waves-effect">Print</a>
                                        <?php
                                            }
                                        }
                                        else
                                        {
                                            ?>
                                        <a href="../Reports/grn_detail.php?header_id=<?=$grn_header_id?>"
                                            class="btn bg-warning-subtle text-warning waves-effect">Print</a>
                                        <?php
                                        }
                                    ?>


                                        <?php 
                                    }//verified make print available
                                    else
                                    {
                                    ?>
                                        <a href="../Public/grn-header.php"
                                            class="btn bg-danger-subtle text-danger  waves-effect">
                                            Close
                                        </a>
                                        <?php 
                                    }//cancled or undefined 
                                    ?>

                                    </div>
                            </div>
                        </div>
                    </div>
                    <!--Added by Imila on 2024-09-27-->
                </div>
            </form>
                <!--Added by Imila on 2024-09-27-->

            </div>
        </div>
    </div>
    </div>
    <div id="ajaxshow"> Something</div>
    <!--  Body Wrapper End -->
    <!-- footer Start  -->
    <?php include '../View/footer.php';?>
    <!-- footer End  -->

    <!-- <script src="../Assets/jquery/grn.js"></script> -->
    <script src="../Assets/jquery/grn_detail.js"></script>

    <script>
        function addChq()
        {
            var payment =`
                        <div id="chequeDiv" class="col-md-12 mb-2 row">
                                <div class="col-md-2">
                                    <label for="chqNo" class="form-label">Cheque No</label>
                                    <input type="text" name="chqNo[]" id="chqNo" class="form-control" placeholder="Cheque No">
                                </div>
                                <div class="col-md-2">
                                    <label for="chqdate" class="form-label">Cheque Date</label>
                                    <input type="date" name="chqdate[]" id="chqdate" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label for="chqbank" class="form-label">Bank</label>
                                    <input type="text" name="chqbank[]" id="chqbank" class="form-control" placeholder="Ex: BOC">
                                </div>
                                <div class="col-md-2">
                                    <label for="chqamount" class="form-label">Cheque Amount</label>
                                    <input type="text" name="chqAmount[]" id="chqamount" class="form-control" placeholder="Ex: 2000">
                                </div>
                                <div class="col-md-4">
                                <a href='javascript:void(0);' class='remove-payment btn btn-danger mt-4' id='remove-payment' onclick='remove_cheque(this)' ><i class='ti ti-trash'></i></a>
                                <a href='javascript:void(0);' class='btn btn-primary mt-4' onclick='addChq()' ><i class='ti ti-plus'></i></a>
                                </div>
                            </div>
                        `;
                            $("#payment_method").append(payment);
        }
        function addChqsettlement()
        {
            var payment =`
                        <div id="chequeDiv" class="col-md-12 mb-2 row">
                                <div class="col-md-4">
                                
                                </div>
                                <div class="col-md-4">
                                    <label for="transferCheque" class="form-label">Select Cheque</label>
                                    <select class="transferCheque form-select" name="transferCheque" id="transferCheque">
                                    <option value="" > Select Cheque</option>
                                    <?php 
                                    $sql = "SELECT * FROM custcheq
                                    INNER JOIN custchqdetail ON custchqdetail.CCQID = custcheq.CCQID
                                    INNER JOIN customers c ON c.CTID=custcheq.cust_CTID
                                    WHERE custcheq.shop_SHID = ".$shop_id." AND custcheq.chq_stat=1;";
                                    $dbObj = new DBTransactions();
                                    $dbPaymethods = $dbObj->getData($sql);
                                    $count = 0;
                                    foreach($dbPaymethods as $row)
                                    {
                                        ?>
                                        <option value="<?=$row["CCQID"]?>"><?=$row["bank"]?> - <?=$row["chqNo"]?> - <?=$row["CustName"]?> - <?=$row["chqAmount"]?></option>
                                        <?php
                                    }
                                    ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                <a href='javascript:void(0);' class='remove-payment btn btn-danger mt-4' id='remove-payment' onclick='remove_cheque(this)' ><i class='ti ti-trash'></i></a>
                                <a href='javascript:void(0);' class='btn btn-primary mt-4' onclick='addChqsettlement()' ><i class='ti ti-plus'></i></a>
                                </div>
                            </div>
                            `;
                            $("#payment_method").append(payment);
        }
    
        $(document).ready(function() {

        
        $('#grn-form').submit(function() {
            return validateForm();
        });
        $(document).on("change", ".transferCheque", function() {
            var selectedValue = $(this).val();
            var isDuplicate = false;
            if (selectedValue != "") {
                $('.transferCheque').not(this).each(function() {
                    if ($(this).val() === selectedValue) {
                        isDuplicate = true;
                        return false; // break out of the loop
                    }
                });

                if (isDuplicate) {
                    alert('Duplicate value selected!');
                    $(this).val("");
                } 
            }
        });
        $(document).on("change", ".pay_id", function() {
            var selectedValue = $(this).val();
            var isDuplicate = false;
            if (selectedValue != "") {
                $('.pay_id').not(this).each(function() {
                    if ($(this).val() === selectedValue) {
                        isDuplicate = true;
                        return false; // break out of the loop
                    }
                });

                if (isDuplicate) {
                    alert('Duplicate value selected!');
                    $(this).val("");
                } else {
                    if ($(this).val() == 5) {
                        var payment =`
                        <div id="chequeDiv" class="col-md-12 mb-2 row">
                                <div class="col-md-2">
                                    <label for="chqNo" class="form-label">Cheque No</label>
                                    <input type="text" name="chqNo[]" id="chqNo" class="form-control" placeholder="Cheque No">
                                </div>
                                <div class="col-md-2">
                                    <label for="chqdate" class="form-label">Cheque Date</label>
                                    <input type="date" name="chqdate[]" id="chqdate" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label for="chqbank" class="form-label">Bank</label>
                                    <input type="text" name="chqbank[]" id="chqbank" class="form-control" placeholder="Ex: BOC">
                                </div>
                                <div class="col-md-2">
                                    <label for="chqamount" class="form-label">Cheque Amount</label>
                                    <input type="text" name="chqAmount[]" id="chqamount" class="form-control" placeholder="Ex: 2000">
                                </div>
                                <div class="col-md-4">
                                <a href='javascript:void(0);' class='remove-payment btn btn-danger mt-4' id='remove-payment' onclick='remove_cheque(this)' ><i class='ti ti-trash'></i></a>
                                <a href='javascript:void(0);' class='btn btn-primary mt-4' onclick='addChq()' ><i class='ti ti-plus'></i></a>
                                </div>
                            </div>
                            `;
                            
                            $("#payment_method").append(payment);

                    } 
                    else if($(this).val() == 13)
                    {
                        var payment =`
                        <div id="chequeDiv" class="col-md-12 mb-2 row">
                                <div class="col-md-4">

                                </div>
                                <div class="col-md-4">
                                    <label for="transferCheque" class="form-label">Select Cheque</label>
                                    <select class="transferCheque form-select" name="transferCheque[]" id="transferCheque">
                                    <option value="" > Select Cheque</option>
                                    <?php 
                                    $sql = "SELECT * FROM custcheq
                                    INNER JOIN custchqdetail ON custchqdetail.CCQID = custcheq.CCQID
                                    INNER JOIN customers c ON c.CTID=custcheq.cust_CTID
                                    WHERE custcheq.shop_SHID = ".$shop_id." AND custcheq.chq_stat=1;";
                                    $dbObj = new DBTransactions();
                                    $dbPaymethods = $dbObj->getData($sql);
                                    $count = 0;
                                    foreach($dbPaymethods as $row)
                                    {
                                        ?>
                                        <option value="<?=$row["CCQID"]?>"><?=$row["bank"]?> - <?=$row["chqNo"]?> - <?=$row["CustName"]?> - <?=$row["chqAmount"]?></option>
                                        <?php
                                    }
                                    ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                <a href='javascript:void(0);' class='remove-payment btn btn-danger mt-4' id='remove-payment' onclick='remove_cheque(this)' ><i class='ti ti-trash'></i></a>
                                <a href='javascript:void(0);' class='btn btn-primary mt-4' onclick='addChqsettlement()' ><i class='ti ti-plus'></i></a>
                                </div>
                            </div>
                            `;
                            
                            $("#payment_method").append(payment);

                    }
                    else {

                    }
                }
            }
        })
           
    
     });
    //Added by Imila on 2024-09-27
    function remove_cheque(item) {
        var value = $(item).attr("id");
        $(item).parent().parent().remove();
        balance();

    }

    function addPayment() {
        var payment = "<div class='col-md-12 mb-2 row'>" +
            "<div class='col-md-4'>" +
            "<label for='' class='form-label'>Payment Type</label>" +
            "<select name='pay_id[]' id='pay_id' class='pay_id form-select'>" +
            "<option value=''>Select payment method</option>" +
            <?php 
                            $sql = "SELECT * FROM shoppaymethod
                            INNER JOIN paymethod ON paymethod.PMID = shoppaymethod.paymethod_PMID
                            WHERE shop_SHID = ".$shop_id.";";
                            $dbObj = new DBTransactions();
                            $dbPaymethods = $dbObj->getData($sql);
                            $count = 0;
                            foreach($dbPaymethods as $row)
                            {
                                $is_checked = $count==0 ? 'checked' : '';
                                ?> "<option value='<?php echo $row['paymethod_PMID'];?>'><?php echo $row['PaymethodName'];?></option>" +
            <?php 
                                $count += 1;
                            }//foreach
                        ?> "</select>" +
            "</div>" +
            "<div class='col-md-4'>" +
            "<label for='paid-input' class='form-label'>Paid</label>" +
            "<input type='text' class='form-control' name='paid[]' id='paid-input' placeholder='0.00'>" +
            "</div>" +
            "<div class='col-md-4'>" +
            "<a href='javascript:void(0);' class='remove-payment btn btn-danger mt-4' id='remove-payment' onclick='remove_payment(this)' ><i class='ti ti-trash'></i></a>" +
            "</div>" +
            "<div>";

        $("#payment_method").append(payment);
    }


    function remove_payment(item) {
        var value = $(item).attr("id");
        $(item).parent().parent().remove();

    }
    //Added by Imila on 2024-09-27
    function validateForm() {
        var netamount = parseFloat($("#sub_purchase_price").val());
        var paid = 0;
        $("body #paid-input").each(function() {
            if ($(this).val() == 0 && $(this).val() == "0" || $(this).val() == "") {
                paid = paid + 0;
                console.log("No value" + paid);
            } else {
                paid = paid + parseFloat($(this).val());
                console.log("value" + paid);
            }
        });
        if (paid == 0) {

        } else if (paid == "") {
            alert('Paid Amount Cannot be Empty');
            $("#paid-input").focus();
            return false;
        }
        // if(netamount > paid)
        // {
        //     if($("#customer_id").val()==1)
        //     {
        //         alert('Default Customer Cannot Have Credit');
        //         $("#add-customer").focus();
        //         return false;
        //     }
        // }

        return true;
    }

    
   
    </script>

    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>

</body>

</html>