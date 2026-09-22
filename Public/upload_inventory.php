<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';
if($userObj->checkusertype($_SESSION["user_id"])==1)
{

}
else
{
    ?>
    <script>
        window.location.href = "../Public/home.php";
    </script>
    <?php
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
    header("Location: analytics.php");
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
                if(isset($_SESSION['inv_upload']))
                {
                    if($_SESSION['inv_upload'] == 0)
                    {
                        ?>
                         <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            Please select a <strong>File to upload. </strong>
                        </div>
                        <?php 
                    }//not support file
                    else if($_SESSION['inv_upload'] == 1)
                    {
                        ?>
                        <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                           <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                           File type not <strong>supported.</strong>
                       </div>
                       <?php 
                    }//file saved

                    else if($_SESSION['inv_upload'] == 2)
                    {
                        ?>
                        <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                           <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            File Added <strong>Successfully</strong>
                       </div>
                       <?php 
                    }//file saved

                    else if($_SESSION['inv_upload'] == 3)
                    {
                        ?>
                        <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                           <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                           Item didn't found in <strong>Product List</strong> please add item. 
                       </div>
                       <?php 
                    }//categories

                    else if($_SESSION['inv_upload'] == 4)
                    {
                        ?>
                        <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                           <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                           All items are <strong>added in list</strong>
                       </div>
                       <?php 
                    }//file delete

                    else if($_SESSION['inv_upload'] == 5)
                    {
                        ?>
                        <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                           <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                           File <strong>Deleted </strong>Successfully.
                       </div>
                       <?php 
                    }//file delete

                    else if($_SESSION['inv_upload'] == 6)
                    {
                        ?>
                        <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                           <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                           File <strong>Converted</strong> Successfully.
                       </div>
                       <?php 
                    }//file delete

                    else if($_SESSION['inv_upload'] == 7)
                    {
                        ?>
                        <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                           <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                           Inventory File <strong>Deleted</strong> Successfully.
                       </div>
                       <?php 
                    }//file delete

                    else if($_SESSION['inv_upload'] == 8)
                    {
                        ?>
                        <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                           <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                           Inventory File <strong>Added</strong> Successfully.
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

                    unset($_SESSION["inv_upload"]);
                }//has message
                
                ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Upload CSV Products</h5>
                    </div>
                    <div class="card-body">
                    <!-- product upload form -->
                        <form action="../Controller/UploadController.php" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mt-3">
                                    <label for="">Upload Excel Files</label>
                                    <input type="file" name="inventory_file" accept=".xlsx,.csv" class="form-control">
                                </div>

                                <div class="col-md-6 mt-3">
                                    <button type="submit" name="btn_upload_inventory" class="btn btn-success mt-4">Upload Inventory CSV</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-body">
                        <p>Inventory sheet data</p>
                        <?php 
                        // Path to the Excel file
                        $filePath_csv = '../Assets/uploads/inventory_excel.csv';
                        $filePath_xlsx = '../Assets/uploads/inventory_excel.xlsx';

                        $has_file_csv = file_exists("../Assets/uploads/inventory_excel.csv") ? true : false;
                        $has_file_xlsx = file_exists("../Assets/uploads/inventory_excel.xlsx") ? true : false;

                        $headers = array();

                        if($has_file_csv)
                        {
                            // Load the Excel file
                            $spreadsheet = IOFactory::load($filePath_csv);
                            $sheet = $spreadsheet->getActiveSheet();
                            $rows = $sheet->toArray();

                            ?>
                            <div style="max-height: 500px; overflow:scroll;">
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
                            <?php 

                        }//has file
                        elseif($has_file_xlsx)
                        {
                            // Load the Excel file
                            $spreadsheet = IOFactory::load($filePath_xlsx);
                            $sheet = $spreadsheet->getActiveSheet();
                            $rows = $sheet->toArray();

                            ?>
                            <div style="max-height: 500px; overflow:scroll;">
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
                            <?php 
                        }

                        if($has_file_csv OR $has_file_xlsx)
                        {
                            ?>
                            <form action="../Controller/UploadController.php" method="POST">
                                <div class="container mt-3">
                                    <h5>Select Columns</h5> 

                                    <div class="row">
                                        <div class="col-md-9">
                                            <table>
                                                <!-- barcode -->
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

                                                <!-- item name -->
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

                                                <!-- qty -->
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

                                                <!-- Variations -->
                                                <tr>
                                                    <td>Variations</td>
                                                    <td>
                                                        <select name="cmb_variation" id="cmb_variation" class="form-select">
                                                            <option value="-1">Use Default Variation</option>
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

                                                <!-- Purchase Price -->
                                                <tr>
                                                    <td>Purchase Price</td>
                                                    <td>
                                                        <select name="cmb_purchaseprice" id="cmb_purchaseprice"  class="form-select">
                                                            <option value="-1">Use Default (zero)</option>
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

                                                <!-- Selling Price -->
                                                <tr>
                                                    <td>Selling Price</td>
                                                    <td>
                                                        <select name="cmb_sellingprice" id="cmb_sellingprice"  class="form-select">
                                                            <option value="-1">Use Default (zero)</option>
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

                                                <!-- Label Price -->
                                                <tr>
                                                    <td>Label Price</td>
                                                    <td>
                                                        <select name="cmb_labelprice" id="cmb_labelprice"  class="form-select">
                                                            <option value="-1">Use Default (zero)</option>
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

                                                <!-- mnf date -->
                                                <tr>
                                                    <td>Manufacture Price</td>
                                                    <td>
                                                        <select name="cmb_mnfdate" id="cmb_mnfdate"  class="form-select">
                                                            <option value="-1">Use Default (today)</option>
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

                                                <!-- Exp date -->
                                                <tr>
                                                    <td>Expire Price</td>
                                                    <td>
                                                        <select name="cmb_expdate" id="cmb_expdate"  class="form-select">
                                                            <option value="-1">Use Default (today)</option>
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

                                                <!-- shop -->
                                                <tr>
                                                    <td>Shop</td>
                                                    <td>
                                                        <select name="cmb_shop" id="cmb_shop"  class="form-select">
                                                            <?php 
                                                                $dbObj = new DBTransactions();
                                                                $sql = "SELECT SHID, ComName, ShopName FROM shop 
                                                                        INNER JOIN company ON company.CMID = shop.Company_CMID
                                                                        WHERE ShopStat = 1;";

                                                                $shopData = $dbObj->getData($sql);
                                                                foreach($shopData as $row)
                                                                {
                                                                    ?>
                                                                    <option value="<?php echo $row['SHID'];?>"><?php echo $row['ComName'] ." - ". $row['ShopName'];?></option>
                                                                    <?php 
                                                                }//foreach
                                                            ?>
                                                        </select>
                                                    </td>
                                                </tr>

                                                <!-- category -->
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

                                                <!-- subcategory -->
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
                                    <button type="submit" name="btn_check_items" class="btn btn-primary">Check Items</button>
                                    <button type="submit" name="btn_convert_inventory" class="btn btn-success">Convert File</button>
                                    <button type="submit" name="btn_delete_excel2" class="btn btn-danger">Delete Excel File</button>
                                </div>
                            </form>
                            <?php 
                        }//has file 
                        ?>

                    </div>
                </div>
                

                <!------------------------- converted file ------------------------->
                <div class="card mt-3">
                    <div class="card-body">
                        <?php 
                            $filePath_xlsx = '../Assets/uploads/tmp_inventory_excel.xlsx';

                            $has_file_xlsx = file_exists("../Assets/uploads/tmp_inventory_excel.xlsx") ? true : false;

                            if($has_file_xlsx)
                            {
                                // Load the Excel file
                                $spreadsheet = IOFactory::load($filePath_xlsx);
                                $sheet = $spreadsheet->getActiveSheet();
                                $rows = $sheet->toArray();

                                ?>
                                <div style="max-height: 500px; overflow:scroll;">
                                    <table>
                                        <tr>
                                            <?php 
                                                foreach($rows[0] as $header)
                                                {
                                                    echo "<th>" . $header . "</th>";
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

                                <form action="../Controller/UploadController.php" method="POST">
                                    <button type="submit" name="btn_add_inventory" class="btn btn-primary mt-2">Upload Inventory</button>
                                    <button type="submit" name="btn_delete_invfile" class="btn btn-danger mt-2">Delete Inventory File</button>
                                </form> 
                                <?php 
                            }//has excel file
                        ?>
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

    <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="../Assets/jquery/inventory_summary.js"></script>

</body>
</html>