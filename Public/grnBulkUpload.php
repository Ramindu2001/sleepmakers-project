<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';

$shop_id = $_SESSION['shop_id'];

if($userObj->checkusertype($_SESSION["user_id"])==1)
{

}
else
{
   
}

// require 'vendor/autoload.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

//check user access
$user_id = $_SESSION['user_id'];
$userObj = new User();
$userData = $userObj->getOneUser($user_id);
$userType = $userData[0]['UserType'];

if($userType != 1)
{
    //header("Location: analytics.php");
}//not admin

?>

<!doctype html>
<html lang="en">

<head>
  <?php 
  include '../View/head.php';
  //// include '../View/loader.php';
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
            ?>
            <!--  Header End -->

            <div class="container-fluid">
                <?php 
                if(isset($_SESSION['grn_upload']))
                {
                    if($_SESSION['grn_upload'] == 0)
                    {
                        ?>
                         <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            Invalid <strong>File format.</strong>
                        </div>
                        <?php 
                    }//not support file
                    else if($_SESSION['grn_upload'] == 1)
                    {
                        ?>
                        <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                           <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                           File uploaded <strong>Successfully...</strong>
                       </div>
                       <?php 
                    }//file saved

                    else if($_SESSION['grn_upload'] == 2)
                    {
                        ?>
                        <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                           <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                           File removed <strong>Successfully.</strong> 
                       </div>
                       <?php 
                    }//file saved

                    else if($_SESSION['grn_upload'] == 3)
                    {
                        ?>
                        <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                           <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                           File data<strong> converted </strong>successfully.
                       </div>
                       <?php 
                    }//categories

                    else if($_SESSION['grn_upload'] == 4)
                    {
                        ?>
                        <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                           <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                           Table <strong>cleared</strong> Successfully.
                       </div>
                       <?php 
                    }//file delete

                    else if($_SESSION['grn_upload'] == 5)
                    {
                        ?>
                        <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                           <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                           File <strong>Converted </strong>Successfully.
                       </div>
                       <?php 
                    }//file delete

                    else if($_SESSION['grn_upload'] == 6)
                    {
                        ?>
                        <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                           <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                           Please create a new <strong>GRN </strong>
                       </div>
                       <?php 
                    }//file delete
                    else
                    {
                        ?>
                        <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                           <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                           Oops! Something went wrong!
                       </div>
                       <?php 
                    }

                    unset($_SESSION["grn_upload"]);
                }//has message
                ?>
                
                <div class="card">
                    <div class="card-header">
                        <h5>Upload Items to GRN using csv File</h5>
                    </div>

                    <div class="card-body">
                        <form action="../Controller/bulkUploadController.php" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="">Upload Excel Files</label>
                                    <input type="file" name="product_file" accept=".csv,.xlsx" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" name="btn_upload_file" class="btn btn-success mt-3">Upload Item CSV</button>
                                </div>
                            </div>
                        </form>

                        <!-- view file -->
                        <div class="mt-3">
                            <?php 
                            $shopObj = new Shop();
                            $dbObj = new DBTransactions();

                            // Path to the Excel file - check for both csv and xlsx
                            $filePath_csv = '../Assets/uploads/grn_upload_'.$shop_id.'.csv';
                            $filePath_xlsx = '../Assets/uploads/grn_upload_'.$shop_id.'.xlsx';

                            $has_file_csv = file_exists($filePath_csv) ? 1 : 0;
                            $has_file_xlsx = file_exists($filePath_xlsx) ? 1 : 0;
                            $has_file = $has_file_csv || $has_file_xlsx;
                            
                            $filePath = $has_file_xlsx ? $filePath_xlsx : $filePath_csv;

                            $headers = array();

                            if($has_file)
                            {
                                // Load the Excel file
                                $spreadsheet = IOFactory::load($filePath);
                                $sheet = $spreadsheet->getActiveSheet();
                                $rows = $sheet->toArray();

                                ?>
                                <div style="max-height: 500px; overflow:auto;">
                                    <table>
                                        <tr>
                                            <?php 
                                            foreach($rows[0] as $header)
                                            {
                                                echo "<th>" . $header . "</th>";
                                                $headers[] = $header;
                                            }//foreach  
                                            ?>
                                        </tr>
                                        <?php 
                                            for($i=1; $i<count($rows); $i++)
                                            {
                                                ?>
                                                <tr>
                                                    <?php 
                                                    foreach($rows[$i] as $cell)
                                                    {
                                                        echo "<td>".$cell."</td>";
                                                    }//foreach
                                                    ?>
                                                </tr>
                                                <?php 
                                            }//for
                                        ?>
                                    </table>
                                </div>
                                <div class="mt-2">
                                    <form action="../Controller/bulkUploadController.php" class="form-inline" method="POST">
                                        <button name="btn_delete_grn_file" class="btn btn-danger">Delete File</button>
                                    </form>
                                </div>

                                <!-- Assign file -->
                                <div class="mt-3">
                                    <form action="../Controller/bulkUploadController.php" method="POST">
                                        <h5>Select Columns</h5>

                                        <div class="row">
                                            <div class="col-md-9">
                                                <table>
                                                    <tr>
                                                        <td>Barcode</td>
                                                        <td>
                                                            <select name="cmb_barcode" id="cmb_barcode" class="form-select">
                                                                <?php 
                                                                $count = 0;
                                                                    foreach($headers as $row)
                                                                    {
                                                                        ?>
                                                                        <option value="<?php echo $count;?>"><?php echo $row;?></option>
                                                                        <?php 
                                                                        $count += 1;
                                                                    }//foreach
                                                                ?>
                                                            </select>
                                                        </td>
                                                    </tr>

                                                    <tr>
                                                        <td>Item Name</td>
                                                        <td>
                                                            <select name="cmb_itemname" id="cmb_itemname" class="form-select">
                                                                <?php 
                                                                $count = 0;
                                                                    foreach($headers as $row)
                                                                    {
                                                                        ?>
                                                                        <option value="<?php echo $count;?>"><?php echo $row;?></option>
                                                                        <?php 
                                                                        $count += 1;
                                                                    }//foreach
                                                                ?>
                                                            </select>
                                                        </td>
                                                    </tr>

                                                    <tr>
                                                        <td>Quantity</td>
                                                        <td>
                                                            <select name="cmb_qty" id="cmb_qty" class="form-select">
                                                                <?php 
                                                                $count = 0;
                                                                    foreach($headers as $row)
                                                                    {
                                                                        ?>
                                                                        <option value="<?php echo $count;?>"><?php echo $row;?></option>
                                                                        <?php 
                                                                        $count += 1;
                                                                    }//foreach
                                                                ?>
                                                            </select>
                                                        </td>
                                                    </tr>

                                                    <tr>
                                                        <td>Purchase Price</td>
                                                        <td>
                                                            <select name="cmb_purchaseprice" id="cmb_purchaseprice" class="form-select">
                                                                <option value="-1">Use Default (Zero)</option>
                                                                <?php 
                                                                $count = 0;
                                                                    foreach($headers as $row)
                                                                    {
                                                                        ?>
                                                                        <option value="<?php echo $count;?>"><?php echo $row;?></option>
                                                                        <?php 
                                                                        $count += 1;
                                                                    }//foreach
                                                                ?>
                                                            </select>
                                                        </td>
                                                    </tr>

                                                    <?php
                                                    if($shopObj->hasLabelPrice($shop_id))
                                                    {
                                                        ?>
                                                        <tr>
                                                            <td>Label Price</td>
                                                            <td>
                                                                <select name="cmb_labelprice" id="cmb_labelprice" class="form-select">
                                                                    <option value="-1">Use Default (One)</option>
                                                                    <?php 
                                                                    $count = 0;
                                                                        foreach($headers as $row)
                                                                        {
                                                                            ?>
                                                                            <option value="<?php echo $count;?>"><?php echo $row;?></option>
                                                                            <?php 
                                                                            $count += 1;
                                                                        }//foreach
                                                                    ?>
                                                                </select>
                                                            </td>
                                                        </tr>
                                                        <?php 
                                                    }//has label price
                                                    ?>
                                                    

                                                    <tr>
                                                        <td>Selling Price</td>
                                                        <td>
                                                            <select name="cmb_sellingprice" id="cmb_sellingprice" class="form-select">
                                                                <option value="-1">Use Default (One)</option>
                                                                <?php 
                                                                $count = 0;
                                                                    foreach($headers as $row)
                                                                    {
                                                                        ?>
                                                                        <option value="<?php echo $count;?>"><?php echo $row;?></option>
                                                                        <?php 
                                                                        $count += 1;
                                                                    }//foreach
                                                                ?>
                                                            </select>
                                                        </td>
                                                    </tr>

                                                    <?php 
                                                    if($shopObj->hasExpiry($shop_id))
                                                    {
                                                        ?>
                                                        <tr>
                                                            <td>Manufacture Date</td>
                                                            <td>
                                                                <select name="cmb_mnfdate" id="cmb_mnfdate" class="form-select">
                                                                    <option value="-1">Use Default Date</option>
                                                                    <?php 
                                                                    $count = 0;
                                                                        foreach($headers as $row)
                                                                        {
                                                                            ?>
                                                                            <option value="<?php echo $count;?>"><?php echo $row;?></option>
                                                                            <?php 
                                                                            $count += 1;
                                                                        }//foreach
                                                                    ?>
                                                                </select>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td>Expire Date</td>
                                                            <td>
                                                                <select name="cmb_expdate" id="cmb_expdate" class="form-select">
                                                                    <option value="-1">Use Default Date</option>
                                                                    <?php 
                                                                    $count = 0;
                                                                        foreach($headers as $row)
                                                                        {
                                                                            ?>
                                                                            <option value="<?php echo $count;?>"><?php echo $row;?></option>
                                                                            <?php 
                                                                            $count += 1;
                                                                        }//foreach
                                                                    ?>
                                                                </select>
                                                            </td>
                                                        </tr>
                                                        <?php 
                                                    }//has expiry

                                                    if($shopObj->hasRacks($shop_id))
                                                    {
                                                        ?>
                                                        <tr>
                                                            <td>Section</td>
                                                            <td>
                                                                <select name="cmb_section" id="cmb_section" class="form-select">
                                                                    <option value="-1">Use Default Section</option>
                                                                    <?php 
                                                                    $count = 0;
                                                                        foreach($headers as $row)
                                                                        {
                                                                            ?>
                                                                            <option value="<?php echo $count;?>"><?php echo $row;?></option>
                                                                            <?php 
                                                                            $count += 1;
                                                                        }//foreach
                                                                    ?>
                                                                </select>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td>Rack</td>
                                                            <td>
                                                                <select name="cmb_rack" id="cmb_rack" class="form-select">
                                                                    <option value="-1">Use Default Rack</option>
                                                                    <?php 
                                                                    $count = 0;
                                                                        foreach($headers as $row)
                                                                        {
                                                                            ?>
                                                                            <option value="<?php echo $count;?>"><?php echo $row;?></option>
                                                                            <?php 
                                                                            $count += 1;
                                                                        }//foreach
                                                                    ?>
                                                                </select>
                                                            </td>
                                                        </tr>
                                                        <?php 
                                                    }//has racks
                                                    ?>

                                                    <!-- Category for new items -->
                                                    <tr>
                                                        <td>Category</td>
                                                        <td>
                                                            <select name="cmb_category" id="cmb_category" class="form-select">
                                                                <option value="-1">-- Not in File --</option>
                                                                <?php 
                                                                $count = 0;
                                                                    foreach($headers as $row)
                                                                    {
                                                                        ?>
                                                                        <option value="<?php echo $count;?>"><?php echo $row;?></option>
                                                                        <?php 
                                                                        $count += 1;
                                                                    }//foreach
                                                                ?>
                                                            </select>
                                                        </td>
                                                    </tr>

                                                    <!-- SubCategory for new items -->
                                                    <tr>
                                                        <td>Sub Category</td>
                                                        <td>
                                                            <select name="cmb_subcategory" id="cmb_subcategory" class="form-select">
                                                                <option value="-1">-- Not in File --</option>
                                                                <?php 
                                                                $count = 0;
                                                                    foreach($headers as $row)
                                                                    {
                                                                        ?>
                                                                        <option value="<?php echo $count;?>"><?php echo $row;?></option>
                                                                        <?php 
                                                                        $count += 1;
                                                                    }//foreach
                                                                ?>
                                                            </select>
                                                        </td>
                                                    </tr>

                                                </table>

                                            </div>
                                        </div>
                                        
                                        <button class="btn btn-primary" name="btn_upload_grn">Upload GRN</button>
                                    </form>
                                </div>

                                <?php 
                            }//has file
                            ?>
                        </div>

                        <?php 
                        $sql = "SELECT * FROM `temp_grnupload` WHERE shop_id = ".$shop_id.";";
                        $uploadData = $dbObj->getData($sql);

                        if(!empty($uploadData))
                        {
                            ?>
                            <div class="mt-2">
                                <div style="max-height: 400px; overflow:auto;">
                                    <table class="table" id="tbl_upload">
                                        <tr>
                                            <th>No</th>
                                            <th>Barcode</th>
                                            <th>Item Name</th>
                                            <th>Qty</th>
                                            <th>Purchase Price</th>
                                            <?php 
                                            if($shopObj->hasLabelPrice($shop_id))
                                            {
                                                ?>
                                                <th>Label Price</th>
                                                <?php 
                                            }//has label price
                                            ?>
                                            <th>Selling Price</th>
                                            <?php 
                                            if($shopObj->hasExpiry($shop_id))
                                            {
                                                ?>
                                                <th>Mnf Date</th>
                                                <th>Exp Date</th>
                                                <?php 
                                            }//has expiry
                                            if($shopObj->hasRacks($shop_id))
                                            {
                                                ?>
                                                <th>Section</th>
                                                <th>Rack</th>
                                                <?php 
                                            }
                                            ?>
                                            <th>Stat</th>
                                            <th>Action</th>
                                        </tr>
                                        <?php 
                                        $row_count = 0;
                                        foreach($uploadData as $row)
                                        {
                                            $row_count += 1;
                                            ?>
                                            <tr data-id="<?php echo $row['upload_id'];?>">
                                                <td><?php echo $row_count;?></td>
                                                <td><?php echo $row['barcode'];?></td>
                                                <td><?php echo $row['itemname'];?></td>
                                                <td><?php echo $row['qty'] + 0;?></td>
                                                <td><?php echo $row['purchaseprice'];?></td>
                                                <?php 
                                                if($shopObj->hasLabelPrice($shop_id))
                                                {
                                                    ?>
                                                    <?php echo $row['labelprice'];?></td>
                                                    <?php 
                                                }//has label price
                                                ?>
                                                <td><?php echo $row['sellingprice'];?></td>

                                                <?php 
                                                if($shopObj->hasExpiry($shop_id))
                                                {   
                                                    ?>
                                                    <td><?php echo $row['mnfdate'];?></td>
                                                    <td><?php echo $row['expdate'];?></td>
                                                    <?php 
                                                }//has expiry

                                                //find section and rack
                                                $section_id = $row['section_id'];
                                                $sql = "SELECT * FROM `sections` WHERE SEID = ".$section_id.";";
                                                $secData = $dbObj->getData($sql);
                                                $section = $secData[0]['SectionName'];

                                                $rack_id = $row['section_id'];
                                                $sql = "SELECT * FROM `rack` WHERE RKID = ".$rack_id.";";
                                                $rackData = $dbObj->getData($sql);
                                                $rack = $rackData[0]['RackName'];

                                                if($shopObj->hasRacks($shop_id))
                                                {
                                                    ?>
                                                    <td><?php echo $section;?></td>
                                                    <td><?php echo $rack;?></td>
                                                    <?php 
                                                }//has racks

                                                $prod_stat = $row['prod_stat'];
                                                $availability = $row['prod_stat'] == 1 ? "Available" : "Not available";    
                                                
                                                if($prod_stat == 1)
                                                {
                                                    ?>
                                                    <td>
                                                        <p class="text-success m-1" style="font-weight: bold;">Available</p>
                                                    </td>
                                                    <?php  
                                                }//available
                                                else
                                                {
                                                    ?>
                                                    <td>
                                                        <p class="text-warning m-1" style="font-weight: bold;">Not Available</p>
                                                    </td>
                                                    <?php 
                                                }
                                                ?>
                                                <td>
                                                    <button class="btn border border-primary text-primary btn_edit_item"><i class="ti ti-edit"></i></button>
                                                    <button class="btn border border-danger text-danger btn_delete_item"><i class="ti ti-trash"></i></button>
                                                </td>
                                            </tr>
                                            <?php 
                                        }//foreach
                                        ?>
                                    </table>
                                </div>
                           
                                <!-- <button type="button" id="btn_load_table">Load Table</button> -->
                                
                                <form action="../Controller/bulkUploadController.php" method="POST">
                                    
                                        <p class="text-danger">Only the <b>available</b> items will be added to the GRN</p>

                                    <div class="row">
                                        <div class="col-md-6">
                                        <label for="" class="form-label">Select GRN</label>
                                        <select name="cmb_grn_header" id="cmb_grn_header" class="form-select">
                                            <?php 
                                            $sql = "SELECT * FROM `grnheader` WHERE GRNStat = 0 AND shop_SHID = ".$shop_id.";";
                                            $grnData = $dbObj->getData($sql);

                                            foreach($grnData as $row)
                                            {
                                                ?>
                                                <option value="<?php echo $row['GHID'];?>"><?php echo "Date:" . $row['EffectiveDate'] ." - Invoice:". $row['InvoiceNo'];?></option>
                                                <?php 
                                            }//foreach
                                            ?>
                                        </select>
                                        </div>
                                        <div class="col-md-6">
                                            <button name="btn_add_grn" class="btn btn-primary mt-4">Add GRN</button>
                                        </div>
                                    </div>

                                    <button name="btn_clear_table" class="btn btn-warning m-3">Clear Table</button>
                                   
                                </form>
                            </div>
                            <?php 
                        }//has data
                        ?>

                    </div> 
                </div><!-- card -->
                
               

            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<div class="modal" tabindex="-1" role="dialog" id="edit_item_modal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Item</h5>
        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close" id="btn_close_edit_modal">
          <!-- <span aria-hidden="true">&times;</span> -->
        </button>
      </div>

      <div class="modal-body">
        <p id="p_item">Modal body text goes here.</p>

        <input type="hidden" name="hide_upload_id" id="hide_upload_id" value="0">

        <label for="" class="form-label mt-2">Qty</label>
        <input type="number" step="0.001" name="item_qty" id="item_qty" class="form-control">

        <label for="" class="form-label mt-2">Purchase Price</label>
        <input type="number" step="0.001" name="item_purchase_price" id="item_purchase_price" class="form-control">

        <?php 
        if($shopObj->hasLabelPrice($shop_id))
        {
            ?>
            <label for="" class="form-label mt-2">Label Price</label>
            <input type="number" step="0.001" name="item_label_price" id="item_label_price" class="form-control">
            <?php 
        }//has label price
        ?>

        <label for="" class="form-label mt-2">Selling Price</label>
        <input type="number" step="0.001" name="item_selling_price" id="item_selling_price" class="form-control">

        <?php 
        if($shopObj->hasExpiry($shop_id))
        {
            ?>
            <label for="" class="form-label mt-2">Mnf Date</label>
            <input type="date" name="item_mnf_date" id="item_mnf_date" class="form-control">

            <label for="" class="form-label mt-2">Exp Date</label>
            <input type="date" name="item_exp_date" id="item_exp_date" class="form-control">
            <?php 
        }//has label price

        if($shopObj->hasRacks($shop_id))
        {
            ?>
            <label for="" class="form-label mt-2">Select Section</label>
            <select name="cmb_item_section" id="cmb_item_section">
                <?php 
                $sql = "SELECT * FROM `sections` WHERE shop_SHID = ".$shop_id.";";
                $secData = $dbObj->getData($sql);
                foreach($secData as $row)
                {
                    ?>
                    <option value="<?php echo $row['SEID'];?>"><?php echo $row['SectionName'];?></option>
                    <?php 
                }//foreach
                ?>
            </select>

            <label for="" class="form-label mt-2">Select rack</label>
            <select name="cmb_item_rack" id="cmb_item_rack">
                <?php
                $sql = "SELECT * FROM `rack` 
                INNER JOIN sections ON sections.SEID = rack.Sections_SEID
                WHERE sections.shop_SHID = ".$shop_id.";";
                $rackData = $dbObj->getData($sql);
                foreach($rackData as $row)
                {
                    ?>
                    <option value="<?php echo $row['RKID'];?>"><?php echo $row['RackName'];?></option>
                    <?php 
                }//foreach
                ?>
            </select>
            <?php 
        }//has racks
        ?>


      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="btn_update_item">Update</button>
        <!-- <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button> -->
      </div>

    </div>
  </div>
</div>

<!--  Body Wrapper End -->

    <!-- footer Start  -->
    <?php include '../View/footer.php';?> 
    <!-- footer End  -->

    <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../Assets/jquery/grnupload.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>

</body>
</html>