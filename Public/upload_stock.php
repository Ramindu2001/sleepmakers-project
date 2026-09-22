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
                if(isset($_SESSION['file_upload']))
                {
                    if($_SESSION['file_upload'] == 0)
                    {
                        ?>
                         <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            Invalid <strong>File format.</strong>
                        </div>
                        <?php 
                    }//not support file
                    else if($_SESSION['file_upload'] == 1)
                    {
                        ?>
                        <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                           <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                           File added to <strong>Query.</strong>
                       </div>
                       <?php 
                    }//file saved

                    else if($_SESSION['file_upload'] == 2)
                    {
                        ?>
                        <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                           <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                           Categories added to <strong>shop</strong> Successfully.
                       </div>
                       <?php 
                    }//file saved

                    else if($_SESSION['file_upload'] == 3)
                    {
                        ?>
                        <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                           <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                           Please add all <strong>Categories and Subcategories.</strong>
                       </div>
                       <?php 
                    }//categories

                    else if($_SESSION['file_upload'] == 4)
                    {
                        ?>
                        <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                           <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                           File <strong>Deleted</strong> Successfully.
                       </div>
                       <?php 
                    }//file delete

                    else if($_SESSION['file_upload'] == 5)
                    {
                        ?>
                        <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                           <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                           File <strong>Converted </strong>Successfully.
                       </div>
                       <?php 
                    }//file delete

                    else if($_SESSION['file_upload'] == 6)
                    {
                        ?>
                        <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                           <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                           File Uploaded Successfully.
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

                    unset($_SESSION["file_upload"]);
                }//has message
                
                ?>
                
                <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Upload CSV Products</h5>
                <div class="card">
                    <div class="card-body">

                        <!-- product upload form -->
                        <form action="../Controller/UploadController.php" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <ul style="color:red;">
                                    <li>Add csv file or excel file</li>
                                    <li>Remove all single quotation (') marks from every cell </li>
                                    <li>Upload the file and check the content from table</li>
                                    <li>Select Columns from dropdown list</li>
                                    <li>Click "Check Category" button to add unsaved categories</li>
                                    <li>Click "Submit " to Convert the file into excel format</li>
                                    <li>Click "Upload" to upload item in to system.</li>
                                </ul>

                                <div class="col-md-6 mt-3">
                                    <label for="">Upload Excel Files</label>
                                    <input type="file" name="product_file" accept=".xlsx" class="form-control">

                                    <button type="submit" name="btn_upload_product" class="btn btn-success mt-3">Upload Product Excel</button>
                                </div>
                            
                            </div>
                        </form>

                        <!--uploaded file details -->
                        <div class="mt-3" >
                            <?php 
                                // Path to the Excel file
                                $filePath = '../Assets/uploads/product_excel.csv';

                                $has_file = file_exists("../Assets/uploads/product_excel.csv") ? true : false;

                                $headers = array();

                                if($has_file)
                                {
                                    // Load the Excel file
                                    $spreadsheet = IOFactory::load($filePath);
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

                                    ?>
                                    <form action="../Controller/UploadController.php" method="post">

                                        <div class="container mt-3">
                                            <h5>Select Columns</h5>

                                            <div class="row">
                                                <div class="col-md-9">
                                                    <table>
                                                        <tr>
                                                            <td>Category</td>
                                                            <td>
                                                                <select name="cmb_category" id="cmb_category" class="form-select">
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
                                                            <td>Subcategory</td>
                                                            <td>
                                                                <select name="cmb_subcategory" id="cmb_subcategory"  class="form-select">
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
                                                            <td>Barcode</td>
                                                            <td>
                                                                <select name="cmb_barcode" id="cmb_barcode"  class="form-select">
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
                                                                <select name="cmb_itemname" id="cmb_itemname"  class="form-select">
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
                                                            <td>Product Description</td>
                                                            <td>
                                                                <select name="cmb_description" id="cmb_description"  class="form-select">
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
                                                            <td>Second Language</td>
                                                            <td>
                                                                <select name="cmb_secondlanguage" id="cmb_secondlanguage"  class="form-select">
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

                                                        <tr>
                                                            <td>Carton Qty</td>
                                                            <td>
                                                                <select name="cmb_cartonqty" id="cmb_cartonqty"  class="form-select">
                                                                    <option value="-1">Use Default (one)</option>
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
                                                            <td>Item Type (Service)</td>
                                                            <td>
                                                                <select name="cmb_itemtype" id="cmb_itemtype"  class="form-select">
                                                                    <option value="-1">Use All Product</option>
                                                                    <option value="-2">Use All Service</option>
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
                                                            <td>Purchase Unit</td>
                                                            <td>
                                                                <select name="cmb_purchaseunit" id="cmb_purchaseunit"  class="form-select">
                                                                    <option value="-1">Use Default Unit (one)</option>
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
                                                            <td>Conversion Rate</td>
                                                            <td>
                                                                <select name="cmb_conversionrate" id="cmb_conversionrate"  class="form-select">
                                                                    <option value="-1">Use Default Unit (one)</option>
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
                                                            <td>Selling Unit</td>
                                                            <td>
                                                                <select name="cmb_sellingunit" id="cmb_sellingunit"  class="form-select">
                                                                    <option value="-1">Use Default Unit (one)</option>
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

                                                    </table>
                                                </div>
                                               
                                            </div>
                                            <button type="submit" name="btn_check_category" class="btn btn-success">Check Categories</button>

                                            <button type="submit" name="btn_submit_columns" class="btn btn-primary">Convert Columns</button>

                                            <button type="submit" name="btn_delete_excel1" class="btn btn-danger">Delete Excel File</button>
                                        </div>
                                    </form>
                                    <?php 

                                }//has file
                            ?>
                        </div>
                    </div>

                </div>
                
                <!-- excel sheet -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5>Upload Excel Sheet</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                            // Path to the Excel file
                            $filePath = '../Assets/uploads/tmp_product_excel.xlsx';

                            $has_file = file_exists("../Assets/uploads/tmp_product_excel.xlsx") ? true : false; 

                            if($has_file)
                            {
                                // Load the Excel file
                                $spreadsheet = IOFactory::load($filePath);
                                $sheet = $spreadsheet->getActiveSheet();
                                $rows = $sheet->toArray();
                                ?>
                                <div style="max-height: 500px; overflow:scroll;">
                                <table>
                                    <tr>
                                        <th>Barcode</th>
                                        <th>Item Name</th>
                                        <th>Description</th>
                                        <th>Second Name</th>
                                        <th>Purchase Price</th>
                                        <th>Selling Price</th>
                                        <th>Carton Qty</th>
                                        <th>Item type</th>
                                        <th>Subcategory ID</th>
                                        <th>Shop ID</th>
                                        <th>Purchase Unit</th>
                                        <th>Conversion</th>
                                        <th>Selling Unit</th>
                                    </tr>
                                    <?php 
                                        for($i=0; $i<count($rows); $i++)
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
                                        }//for loop
                                    ?>
                                </table>
                                </div>
                                
                                <div class="container mt-3">
                                    <form action="../Controller/UploadController.php" method="post">
                                        <button type="submit" name="btn_upload_items" class="btn btn-primary">Upload Items</button>
                                        <button type="submit" name="btn_delete_items" class="btn btn-danger">Delete Items</button>
                                        <!-- <button type="submit" name="btn_test" class="btn btn-primary">Test</button> -->
                                    </form>
                                </div>
                                
                                <?php 
                            }//has tmp file
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