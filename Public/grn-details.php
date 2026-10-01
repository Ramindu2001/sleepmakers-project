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

    //scanner upload (docs/superpowers/specs/2026-09-22-scanner-upload-design.md): open GRNs, GRN create/edit right
    require_once '../Includes/scan_upload.php';
    $grnScanAllowed = $grn_header_stat < 2 && (new ShopAccess())->hasFeatureRight($_SESSION['user_id'], $shop_id, GrnScan::FEATURE, GrnScan::RIGHTS);
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
                                <?php if($grnScanAllowed) { ?>
                                <button type="button" class="btn btn-primary float-end btn-scan-upload"><i
                                        class="ti ti-barcode"></i> Scan / Upload</button>
                                <?php } else if($grn_header_stat < 2) { ?>
                                <!-- scanning is the only way in, so say so rather than leave an empty screen -->
                                <small class="text-muted float-end">Stock is added by scanning. Ask an
                                    administrator for the Goods Received scan right.</small>
                                <?php } ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="container-fluid">
                                <div class="container table-responsive">
                                    <!-- hidden input -->
                                    <input type="hidden" name="hide_header_id" id="hide_header_id"
                                        value="<?php echo $grn_header_id;?>">
                                    <input type="hidden" name="hide_detail_id" id="hide_detail_id" value="0">

                                    <?php 
                            $shopObj = new Shop();

                            if($grn_header_stat <2)
                            {

                            ?>
                                    <!-- One line's quantity, shown only while that line is being corrected. This
                                         screen adds and counts stock: it shows no price, and correcting a line
                                         leaves the line's prices exactly as they were. -->
                                    <table class="table table-hover" id="tbl_edit_line" style="display:none;">
                                        <tr>
                                            <th style="min-width: 250px;">Product</th>
                                            <th style="min-width:150px;">Qty</th>
                                            <?php
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
                                                <p id="edit_line_product" class="mb-0 fw-semibold"></p>
                                                <input type="hidden" name="ids" id="ids" value="0">
                                            </td>

                                            <td>
                                                <input type="number" step="0.001" name="prod_qty" id="prod_qty"
                                                    class="form-control required" placeholder="Qty">
                                                <span class="text-danger" id="qty_warning" style="display: none;">Not
                                                    Valid Value</span>
                                            </td>

                                            <!-- shop has racks -->
                                            <?php
                                        if($shopObj->hasRacks($shop_id))
                                        {
                                            ?>
                                            <td>
                                                <!-- select section -->
                                                <select name="cmb_section" id="cmb_section" class="form-select"
                                                    style="min-width:100px;">
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
                                                <button type="button" name="btn_edit_grn_detail" id="btn_edit_grn_detail"
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
                                    //no price is selected: this screen counts stock and shows no money
                                    $sql = "SELECT GDID, PDID, Barcode, ItemName, VRID, VariationName, InitQty, MnfDate, ExpDate, SEID, SectionName, RKID, RackName, UNID, ShortName FROM grndetails
                                    LEFT JOIN products ON products.PDID = grndetails.products_PDID
                                    LEFT JOIN variations ON variations.VRID = grndetails.VariationID
                                    LEFT JOIN units ON units.UNID = products.PurchaseUnit
                                    LEFT JOIN rack ON rack.RKID = grndetails.Rack_RKID
                                    LEFT JOIN sections ON sections.SEID = rack.Sections_SEID
                                    WHERE GRNHeader_GHID = ".$grn_header_id." ORDER BY GDID DESC;";

                                    $dbObj = new DBTransactions();
                                    $dbData = $dbObj->getData($sql);    

                                    $grn_item_count = 0;
                                    $grn_row_count = 0;


                                    foreach($dbData as $row)
                                    {
                                        $grn_row_count += 1;
                                        $grn_item_count += floatval($row['InitQty']);
                                    ?>
                                            <tr data-id="<?php echo $row['GDID'];?>"
                                                data-qty="<?php echo $row['InitQty'];?>">

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

                                                <?php
                                        if($shopObj->hasExpiry($shop_id))
                                        {
                                            ?>
                                                <td><?php echo $row['MnfDate'];?></td><!-- 9 -->
                                                <td><?php echo $row['ExpDate'];?></td><!-- 10 -->
                                                <?php
                                        }//has expiry

                                        //has racks (the header shows this column for a shop with racks, so the
                                        //cell has to follow the same test or the columns no longer line up)
                                        if($shopObj->hasRacks($shop_id))
                                        {
                                            ?>
                                                <td>
                                                    <?php echo $row['SectionName'];?> <br>
                                                    <?php echo $row['RackName'];?>
                                                </td><!-- 11 -->
                                                <?php 
                                        }//has racks
                                        ?>
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

                                            </thead>
                                        </table>
                                    </div>
                                </div>
                                <!-- submit GRN -->
                                <form action="../Controller/grnController.php" method="POST" id="grn-form">

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
    <script src="../Assets/jquery/grn_detail.js?v=20260922"></script>



    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <?php
    if($grnScanAllowed)
    {
        $scan_upload = ['context' => 'grn', 'doc_id' => $grn_header_id, 'title' => $grn_no, 'apply_label' => 'Add to GRN'];
        include '../View/modals/scan-upload.php';
        ?>
    <script src="../Assets/jquery/scan_upload.js?v=20260922"></script>
        <?php
    }//scanner upload
    ?>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>

</body>

</html>