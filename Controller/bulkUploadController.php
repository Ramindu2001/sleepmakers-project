<?php 
include "../Includes/includes.php";
$shop_id = $_SESSION['shop_id'];
$user_id = $_SESSION['user_id'];

require '../vendor/autoload.php';
require_once '../vendor/PhpXlsxGenerator.php'; 

use PhpOffice\PhpSpreadsheet\IOFactory;

if(isset($_POST['btn_upload_file']))
{
    //file upload directry
    $target_dir = "../Assets/uploads/";

    if(!empty($_FILES['product_file']['name']))
    {
        $product_file_name = basename($_FILES['product_file']['name']);

        $target_file_path = $target_dir . $product_file_name;
        $file_type = pathinfo($target_file_path, PATHINFO_EXTENSION);

        $target_file_path = $target_dir . "grn_upload_".$shop_id."." . $file_type;
        
        // Allow certain file formats 
        $allow_types = array('csv', 'xlsx');
        if(in_array($file_type, $allow_types))
        {
            if(move_uploaded_file($_FILES["product_file"]["tmp_name"], $target_file_path))
            {
                $_SESSION['grn_upload'] = 1;
                header("Location: ../Public/grnBulkUpload.php");
            }//file moved
        }//in array
        else
        {
            $_SESSION['grn_upload'] = 0;
            header("Location: ../Public/grnBulkUpload.php");
            die("Error: Not Support format");
        }//not support type
    }//has file
}//upload files

if(isset($_POST['btn_delete_grn_file']))
{
    $delete_path_csv = '../Assets/uploads/grn_upload_'.$shop_id.'.csv';
    $delete_path_xlsx = '../Assets/uploads/grn_upload_'.$shop_id.'.xlsx';

    if(file_exists($delete_path_csv))
    {
        unlink($delete_path_csv);
    }
    if(file_exists($delete_path_xlsx))
    {
        unlink($delete_path_xlsx);
    }

    $_SESSION['file_upload'] = 2;
    header("Location: ../Public/grnBulkUpload.php");
}//delete

if(isset($_POST['btn_upload_grn']))
{
    // Increase execution time for large uploads (6000+ items)
    set_time_limit(600);
    ini_set('memory_limit', '1024M');
    
    //effective date
    date_default_timezone_set("Asia/Colombo");
    $effective_date = date("Y-m-d");

    $col_barcode = $_POST['cmb_barcode'];
    $col_itemname = $_POST['cmb_itemname'];
    $col_qty = $_POST['cmb_qty'];
    $col_purchase_price = $_POST['cmb_purchaseprice'];
    $col_label_price = isset($_POST['cmb_labelprice']) ? $_POST['cmb_labelprice'] : -1 ;
    $col_selling_price = $_POST['cmb_sellingprice'];
    $col_mnf_date = isset($_POST['cmb_mnfdate']) ? $_POST['cmb_mnfdate'] : 0;
    $col_exp_date = isset($_POST['cmb_expdate']) ? $_POST['cmb_expdate'] : 0;
    $col_section = isset($_POST['cmb_section']) ? $_POST['cmb_section'] : 0;
    $col_rack = isset($_POST['cmb_rack']) ? $_POST['cmb_rack'] : 0;
    $col_category = isset($_POST['cmb_category']) ? $_POST['cmb_category'] : -1;
    $col_subcategory = isset($_POST['cmb_subcategory']) ? $_POST['cmb_subcategory'] : -1;

    $dbObj = new DBTransactions();
    $prodObj = new Product();
    $invObj = new Inventory();
    $priceObj = new PriceHistory();
    $commObj = new Common();
    $catObj = new Category();

    //create table and insert data
    $query = "CREATE TABLE IF NOT EXISTS temp_grnupload (
        upload_id int AUTO_INCREMENT,
        barcode varchar(45),
        itemname varchar(45),
        qty decimal(12,3),
        purchaseprice decimal(12,2),
        labelprice decimal(12,2),
        sellingprice decimal(12,2),
        mnfdate date,
        expdate date,
        section_id int,
        rack_id int,
        product_id int,
        prod_stat int,
        shop_id int,
        PRIMARY KEY(upload_id)
    );";

    $dbObj->executeTransaction($query);

    // Path to the Excel file - check for both csv and xlsx
    $filePath_csv = '../Assets/uploads/grn_upload_'.$shop_id.'.csv';
    $filePath_xlsx = '../Assets/uploads/grn_upload_'.$shop_id.'.xlsx';
    
    $filePath = '';
    if(file_exists($filePath_xlsx))
    {
        $filePath = $filePath_xlsx;
    }
    elseif(file_exists($filePath_csv))
    {
        $filePath = $filePath_csv;
    }
    
    // Load the Excel file
    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    // Pre-fetch all sections for this shop (cache)
    $sql_sections = "SELECT * FROM `sections` WHERE shop_SHID = ".$shop_id.";";
    $sectionsData = $dbObj->getData($sql_sections);
    $sections_cache = array();
    foreach($sectionsData as $sec)
    {
        $sections_cache[$sec['SectionName']] = $sec['SEID'];
    }
    
    // Pre-fetch all racks for this shop (cache)
    $sql_racks = "SELECT rack.*, sections.SectionName FROM `rack` 
    INNER JOIN sections ON sections.SEID = rack.Sections_SEID
    WHERE sections.shop_SHID = ".$shop_id.";";
    $racksData = $dbObj->getData($sql_racks);
    $racks_cache = array();
    foreach($racksData as $rck)
    {
        $key = $rck['Sections_SEID'] . '_' . $rck['RackName'];
        $racks_cache[$key] = $rck['RKID'];
    }
    
    // Pre-fetch all products for this shop (cache by barcode)
    $sql_products = "SELECT PDID, Barcode FROM products WHERE shop_SHID = ".$shop_id.";";
    $productsData = $dbObj->getData($sql_products);
    $products_cache = array();
    foreach($productsData as $prod)
    {
        $products_cache[$prod['Barcode']] = $prod['PDID'];
    }
    
    // Pre-fetch subcategories (cache)
    $sql_subcats = "SELECT subcategories.SCID, CategoryName, SubCatName FROM subcategories
    INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
    WHERE categories.shop_SHID = ".$shop_id.";";
    $subcatsData = $dbObj->getData($sql_subcats);
    $subcats_cache = array();
    foreach($subcatsData as $subcat)
    {
        $key = strtolower($subcat['CategoryName'] . '_' . $subcat['SubCatName']);
        $subcats_cache[$key] = $subcat['SCID'];
    }

    // Track processed barcodes to handle duplicates
    $processed_barcodes = array();
    
    // Build batch insert values
    $insert_values = array();
    
    // Process in chunks to avoid memory issues
    $chunk_size = 500;
    
    for($i=1; $i<count($rows); $i++)
    {
        $barcode = $rows[$i][$col_barcode];
        $item_name = $rows[$i][$col_itemname];
        
        // Skip duplicate barcodes - only take first occurrence
        if(in_array($barcode, $processed_barcodes))
        {
            continue;
        }
        $processed_barcodes[] = $barcode;

        // Escape apostrophes in item_name for SQL
        $item_name_sql = str_replace("'", "\\'", $item_name);

        $qty = is_numeric($rows[$i][$col_qty]) ? $rows[$i][$col_qty] : 0;
        $purchase_price = $col_purchase_price == -1 ? 0 : (is_numeric($rows[$i][$col_purchase_price]) ? $rows[$i][$col_purchase_price] : 0);
        $label_price = $col_label_price == -1 ? 0 : (is_numeric($rows[$i][$col_label_price]) ? $rows[$i][$col_label_price] : 0);
        $selling_price = $col_selling_price == -1 ? 0 : (is_numeric($rows[$i][$col_selling_price]) ? $rows[$i][$col_selling_price] : 0);

        $mnf_date = $col_mnf_date == 0 ? $effective_date : $rows[$i][$col_mnf_date];
        $exp_date = $col_exp_date == 0 ? $effective_date : $rows[$i][$col_exp_date];
        $section = $col_section == 0 ? 1 : $rows[$i][$col_section];
        $rack = $col_rack == 0 ? 1 : $rows[$i][$col_rack];

        // Use cache for section_id
        $section_id = isset($sections_cache[$section]) ? $sections_cache[$section] : 1;
        
        // Use cache for rack_id
        $rack_key = $section_id . '_' . $rack;
        $rack_id = isset($racks_cache[$rack_key]) ? $racks_cache[$rack_key] : 1;

        // Use cache for product_id
        $product_id = isset($products_cache[$barcode]) ? $products_cache[$barcode] : 0;
        
        // If product doesn't exist and category/subcategory are provided, create the product
        if($product_id == 0 && $col_category != -1 && $col_subcategory != -1)
        {
            $category_name = $rows[$i][$col_category];
            $subcategory_name = $rows[$i][$col_subcategory];
            
            // Check cache first for subcategory
            $subcat_key = strtolower($category_name . '_' . $subcategory_name);
            if(isset($subcats_cache[$subcat_key]))
            {
                $subcategory_id = $subcats_cache[$subcat_key];
            }
            else
            {
                // Get or create subcategory ID (only if not in cache)
                $subcategory_id = getOrCreateSubcategory($category_name, $subcategory_name, $shop_id, $dbObj, $catObj, $commObj);
                $subcats_cache[$subcat_key] = $subcategory_id;
            }
            
            if($subcategory_id > 0)
            {
                // Create the product
                $product_id = createNewProduct($barcode, $item_name, $purchase_price, $selling_price, $subcategory_id, $shop_id, $user_id, $dbObj, $prodObj, $invObj, $priceObj, $commObj);
                // Add to cache
                $products_cache[$barcode] = $product_id;
            }
        }
        
        $prod_stat = $product_id == 0 ? 0 : 1; 

        // Add to batch insert array
        $insert_values[] = "('".$barcode."', '".$item_name_sql."', ".$qty.", ".$purchase_price.", ".$label_price.", ".$selling_price.", '".$mnf_date."', '".$exp_date."', ".$section_id.", ".$rack_id.", ".$product_id.", ".$prod_stat.", ".$shop_id.")";
        
        // Process in chunks to avoid memory issues
        if(count($insert_values) >= $chunk_size || $i == count($rows) - 1)
        {
            // Execute batch insert
            $query = "INSERT INTO temp_grnupload(barcode, itemname, qty, purchaseprice, labelprice, sellingprice, mnfdate, expdate, section_id, rack_id, product_id, prod_stat, shop_id) VALUES " . implode(", ", $insert_values) . ";";
            $dbObj->executeTransaction($query);
            
            // Reset for next chunk
            $insert_values = array();
            
            // Force garbage collection
            if(function_exists('gc_collect_cycles'))
            {
                gc_collect_cycles();
            }
        }
    }//for

    // Delete uploaded file (csv or xlsx)
    $delete_path_csv = '../Assets/uploads/grn_upload_'.$shop_id.'.csv';
    $delete_path_xlsx = '../Assets/uploads/grn_upload_'.$shop_id.'.xlsx';

    if(file_exists($delete_path_csv))
    {
        unlink($delete_path_csv);
    }
    if(file_exists($delete_path_xlsx))
    {
        unlink($delete_path_xlsx);
    }

    $_SESSION['file_upload'] = 3;
    header("Location: ../Public/grnBulkUpload.php");

}//upload grn

if(isset($_POST['btn_clear_table']))
{
    $dbObj = new DBTransactions();

    $query = "DELETE FROM `temp_grnupload` WHERE shop_id = ".$shop_id.";";

    $dbObj->executeTransaction($query);

    $_SESSION['file_upload'] = 4;
    header("Location: ../Public/grnBulkUpload.php");
}

if(isset($_POST['btn_add_grn']))
{
    // Increase execution time for large GRN processing
    set_time_limit(600);
    ini_set('memory_limit', '1024M');
    
    $dbObj = new DBTransactions();
    $grnObj = new GRN();
    //get header details
    $grn_header_id = $_POST['cmb_grn_header'];
    if(!empty($grn_header_id))
    {
        //get grn header data
        $sql = "SELECT * FROM `grnheader` WHERE GHID = ".$grn_header_id.";";
        $headerData = $dbObj->getData($sql);

        $sql_1 = "SELECT * FROM `temp_grnupload` WHERE prod_stat = 1 AND shop_id = ".$shop_id.";";

        $uploadData = $dbObj->getData($sql_1);

        if(!empty($uploadData))
        {
            // Batch insert into grndetails table in chunks of 500
            $batch_values = array();
            $grn_chunk_size = 500;
            $row_index = 0;
            
            foreach($uploadData as $row)
            {
                $product_id = $row['product_id'];
                $init_qty = $row['qty'];
                $current_qty = floatval($row['qty']);
                $purchase_price = floatval($row['purchaseprice']);
                $label_price = floatval($row['labelprice']);
                $selling_price = floatval($row['sellingprice']);
                $total_purchase_price = $current_qty * $purchase_price;
                $total_selling_price = $current_qty * $selling_price;
                $mnf_date = $row['mnfdate'];
                $exp_date = $row['expdate'];
                $rack_id = $row['rack_id'];
                $grn_stat = 0;
                $variation_id = 1;

                $batch_values[] = "(".$init_qty.", ".$current_qty.", ".$purchase_price.", ".$label_price.", ".$selling_price.", ".$total_purchase_price.", ".$total_selling_price.", '".$mnf_date."', '".$exp_date."', ".$grn_stat.", ".$variation_id.", ".$product_id.", ".$grn_header_id.", ".$rack_id.")";
                
                $row_index++;
                
                // Execute in chunks
                if(count($batch_values) >= $grn_chunk_size || $row_index == count($uploadData))
                {
                    $grn_query = "INSERT INTO grndetails(InitQty, CurrentQty, UnitPurchasePrice, UnitLabelPrice, UnitSellPrice, TotalPurchasePrice, TotalSellPrice, MnfDate, ExpDate, GRNStat, VariationID, products_PDID, GRNHeader_GHID, Rack_RKID) VALUES " . implode(", ", $batch_values) . ";";
                    $dbObj->executeTransaction($grn_query);
                    $batch_values = array();
                }
            }

            // Clear table after successful batch insert
            $query = "DELETE FROM `temp_grnupload` WHERE shop_id = ".$shop_id.";";
            $dbObj->executeTransaction($query);
        }

        //head to grn header
        header("Location: ../Public/grn-header.php");
        echo "items added successfully...";
    }//has grn header
    else
    {
        $_SESSION['file_upload'] = 6;
        header("Location: ../Public/grnBulkUpload.php");
    }//no grn header
}//add to grn

//===================== Functions =======================//
function checkItem($barcode, $shop_id, $dbObj = null)
{
    $shopObj = new Shop();
    if($dbObj == null) $dbObj = new DBTransactions();
    
    //check multi category
    //get company stat
    $sql = "SELECT * FROM shop
    INNER JOIN company ON company.CMID = shop.Company_CMID
    WHERE SHID = ".$shop_id.";";

    $shopData = $dbObj->getData($sql);
    $multi_category = floatval($shopData[0]['is_multicategory']);
    $company_id = floatval($shopData[0]['CMID']);

    if($multi_category == 1)
    {
        $sql_1 = "SELECT * FROM products 
        INNER JOIN shop ON shop.SHID = products.shop_SHID
        WHERE Barcode = '".$barcode."' AND shop.Company_CMID = ".$company_id.";";
    }//has multi category
    else
    {
        $sql_1 = "SELECT * FROM products WHERE Barcode = '".$barcode."' AND products.shop_SHID = ".$shop_id.";";
    }//only shop

    $prodData = $dbObj->getData($sql_1);

    $product_id = !empty($prodData) ? $prodData[0]['PDID'] : 0;

    return $product_id;
}//check barcode

function getSectionID($section, $shop_id, $dbObj = null)
{
    if($dbObj == null) $dbObj = new DBTransactions();

    //get company stat
    $sql = "SELECT * FROM `sections` WHERE SectionName = '".$section."' AND shop_SHID = ".$shop_id.";";
    $secData = $dbObj->getData($sql);

    //get first section
    $section_id = empty($secData) ? 1 : $secData[0]['SEID'];

    return $section_id;
}//get section

function getRackID($section_id, $rack, $shop_id, $dbObj = null)
{
    if($dbObj == null) $dbObj = new DBTransactions();

    //get company stat
    $sql = "SELECT * FROM `rack` 
    INNER JOIN sections ON sections.SEID = rack.Sections_SEID
    WHERE Sections_SEID = ".$section_id." AND RackName = '".$rack."' AND sections.shop_SHID = ".$shop_id.";";
    $rackData = $dbObj->getData($sql);

    //get first section
    $rack_id = empty($rackData) ? 1 : $rackData[0]['RKID'];

    return $rack_id;
}//get rack id

function getOrCreateSubcategory($category_name, $subcategory_name, $shop_id, $dbObj = null, $catObj = null, $commObj = null)
{
    if($dbObj == null) $dbObj = new DBTransactions();
    if($catObj == null) $catObj = new Category();
    if($commObj == null) $commObj = new Common();
    
    $category_name_sql = str_replace("'", "\\'", $category_name);
    $subcategory_name_sql = str_replace("'", "\\'", $subcategory_name);
    
    // Check if subcategory exists
    $sql = "SELECT subcategories.SCID FROM subcategories
    INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
    WHERE CategoryName = '".$category_name_sql."' AND SubCatName = '".$subcategory_name_sql."' AND categories.shop_SHID = ".$shop_id.";";
    
    $subcatData = $dbObj->getData($sql);
    
    if(!empty($subcatData))
    {
        return $subcatData[0]['SCID'];
    }
    
    // Check if category exists, if not create it
    $sql_cat = "SELECT * FROM categories WHERE CategoryName = '".$category_name_sql."' AND shop_SHID = ".$shop_id.";";
    $catData = $dbObj->getData($sql_cat);
    
    if(empty($catData))
    {
        // Create category
        $catCountData = $catObj->getCategoryCount($shop_id);
        $category_count = intval($catCountData[0]['CategoryCount']) + 1;
        $category_no = $commObj->createCount("MC", $category_count);
        $catObj->setCategory($category_no, strtoupper($category_name), $shop_id);
        
        // Get the new category ID
        $catData = $dbObj->getData($sql_cat);
    }
    
    $category_id = $catData[0]['CTID'];
    
    // Create subcategory
    $subcatCount = $catObj->getSubcategoryCount($shop_id);
    $subcat_count = intval($subcatCount[0]['SubcatCount']) + 1;
    $subcat_no = $commObj->createCount("SC", $subcat_count);
    $catObj->setSubCategory($subcat_no, $subcategory_name, $category_id);
    
    // Get the new subcategory ID
    $subcatData = $dbObj->getData($sql);
    
    return !empty($subcatData) ? $subcatData[0]['SCID'] : 0;
}//get or create subcategory

function createNewProduct($barcode, $item_name, $purchase_price, $selling_price, $subcategory_id, $shop_id, $user_id, $dbObj = null, $prodObj = null, $invObj = null, $priceObj = null, $commObj = null)
{
    if($dbObj == null) $dbObj = new DBTransactions();
    if($prodObj == null) $prodObj = new Product();
    if($invObj == null) $invObj = new Inventory();
    if($priceObj == null) $priceObj = new PriceHistory();
    if($commObj == null) $commObj = new Common();
    
    // Escape item name
    $item_name_sql = str_replace("'", "\\'", $item_name);
    
    // Get product number
    $productData = $prodObj->getProductCount($shop_id);
    $prod_count = intval($productData[0]['ProductCount']) + 1;
    $product_no = $commObj->createCount("PD", $prod_count);
    
    // Set default values
    $product_image = null;
    $description = "";
    $second_name = "";
    $carton_qty = 1;
    $product_stat = 1;
    $item_type = "P";
    $purchase_unit = 1;
    $conversion_rate = 1;
    $selling_unit = 1;
    
    date_default_timezone_set("Asia/Colombo");
    $added_date = date("Y-m-d");
    $updated_date = date("Y-m-d");
    
    // Create the product
    $prodObj->setProduct($product_no, $product_image, $barcode, $item_name_sql, $description, $second_name, $purchase_price, $selling_price, $carton_qty, $product_stat, $added_date, $updated_date, $item_type, $user_id, $user_id, $subcategory_id, $shop_id, $purchase_unit, $conversion_rate, $selling_unit);
    
    // Get the new product ID
    $sql = "SELECT PDID FROM products WHERE Barcode = '".$barcode."' AND shop_SHID = ".$shop_id." ORDER BY PDID DESC LIMIT 1;";
    $newProdData = $dbObj->getData($sql);
    
    if(!empty($newProdData))
    {
        $product_id = $newProdData[0]['PDID'];
        
        // Create inventory entry
        $batch_id = "B001";
        $invObj->setInventory2(0, 0, 0, 0, 0, $product_id, $shop_id, 1, $batch_id, 1);
        
        // Get inventory ID
        $sql_inv = "SELECT max(INID) AS MAXSID FROM inventory WHERE shop_SHID = ".$shop_id.";";
        $dbMax = $dbObj->getData($sql_inv);
        $new_inventory_id = floatval($dbMax[0]['MAXSID']);
        
        // Create price history
        $effective_date = date("Y-m-d");
        $priceObj->setPriceHistory($product_id, 0, $effective_date, $purchase_price, $selling_price, $selling_price, $effective_date, $effective_date, $batch_id, $new_inventory_id, 0);
        
        return $product_id;
    }
    
    return 0;
}//create new product