<?php 
include "../Includes/includes.php";
$shop_id = $_SESSION['shop_id'];
$user_id = $_SESSION['user_id'];

require '../vendor/autoload.php';
require_once '../vendor/PhpXlsxGenerator.php'; 

use PhpOffice\PhpSpreadsheet\IOFactory;

//========================== upload Products ==========================//
if(isset($_POST['btn_upload_product']))
{
    $want_second_name = isset($_POST['want_second_name']) ? 1 : 0;
    $want_product_price = isset($_POST['want_product_price']) ? 1 : 0;
    $want_service_product = isset($_POST['want_service_product']) ? 1 : 0;
    $want_service_product = isset($_POST['want_service_product']) ? 1 : 0;

    //file upload directry
    $target_dir = "../Assets/uploads/";

    if(!empty($_FILES['product_file']['name']))
    {
        $product_file_name = basename($_FILES['product_file']['name']);

        $target_file_path = $target_dir . $product_file_name;
        $file_type = pathinfo($target_file_path, PATHINFO_EXTENSION);

        $target_file_path = $target_dir . "product_excel." . $file_type;
        
        // Allow certain file formats 
        $allow_types = array('xlsx','csv');
        if(in_array($file_type, $allow_types))
        {
            if(move_uploaded_file($_FILES["product_file"]["tmp_name"], $target_file_path))
            {
                $_SESSION['file_update'] = 1;
                header("Location: ../Public/upload_stock.php");
            }//file moved
        }//in array
        else
        {
            $_SESSION['file_upload'] = 0;
            header("Location: ../Public/upload_stock.php");
            die("Error: Not Support format");
        }//not support type
    }//has file
}//upload product file

if(isset($_POST['btn_submit_columns']))
{
    $col_category = $_POST['cmb_category'];
    $col_subcategory = $_POST['cmb_subcategory'];
    $col_barcode = $_POST['cmb_barcode'];
    $col_itemname = $_POST['cmb_itemname'];
    $col_description = $_POST['cmb_description'];
    $col_secondlanguage = $_POST['cmb_secondlanguage'];
    $col_purchaseprice = $_POST['cmb_purchaseprice'];
    $col_sellingprice = $_POST['cmb_sellingprice'];
    $col_cartonqty = $_POST['cmb_cartonqty'];
    $col_itemtype = $_POST['cmb_itemtype'];
    $col_purchaseunit = $_POST['cmb_purchaseunit'];
    $col_conversionrate = $_POST['cmb_conversionrate'];
    $col_sellingunit = $_POST['cmb_sellingunit'];
    $col_shop = $_POST['cmb_shop'];

    // Path to the Excel file
    $filePath = '../Assets/uploads/product_excel.csv';

    // Load the Excel file
    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    $excelData[] = array("Barcode", "ItemName", "ProdDescription", "SecondName", "ProdPurchasePrice", "ProdSellPrice", "CartonQty", "ItemType", "Subcategories_SCID", "shop_SHID", "PurchaseUnit", "UnitConversion", "SellingUnit");

    for($i=1; $i<count($rows); $i++)
    {
        $category_name = $rows[$i][$col_category];
        $subcategory_name = $rows[$i][$col_subcategory];

        $subcategory_id = getSubcategoryID($category_name, $subcategory_name, $col_shop);

        if($subcategory_id == '0')
        {
            $_SESSION['file_upload'] = 3;
            header("Location: ../Public/upload_stock.php");

            break;
        }

        $barcode = $rows[$i][$col_barcode];
        $item_name = $rows[$i][$col_itemname];

        $replaced_item_name = str_replace("'", "`", $item_name);

        $description = $rows[$i][$col_description];
        $second_language = $rows[$i][$col_secondlanguage];

        $purchase_price = $col_purchaseprice == "-1" ? 0 : $rows[$i][$col_purchaseprice];
        $selling_price = $col_sellingprice == "-1" ? 0 : $rows[$i][$col_sellingprice];

        $carton_qty = $col_cartonqty == "-1" ? 1 : $rows[$i][$col_cartonqty];

        //item type product (P) or service (S)
        $item_type = "P";
        if($col_itemtype == '-2')
        {
            $item_type = "S";
        }
        elseif($col_itemtype == '-1')
        {
            $item_type = "P";
        }
        else
        {
            $item_type = $rows[$i][$col_itemtype];
        }

        $purchase_unit = $col_purchaseunit == "-1" ? 1 : $rows[$i][$col_purchaseunit];
        $conversion_rate = $col_conversionrate == "-1" ? 1 : $rows[$i][$col_conversionrate];
        $selling_unit = $col_sellingunit == "-1" ? 1 : $rows[$i][$col_sellingunit];

        //check barcode and Item name
        $item_check = checkBarcodeItem($barcode, $replaced_item_name, $col_shop);
    
        if($item_check)
        {
            continue;
        }//has items skip
        else
        {
            //add this row
            $lineData = array($barcode, $replaced_item_name, $description, $second_language, $purchase_price, $selling_price, $carton_qty, $item_type, $subcategory_id, $col_shop, $purchase_unit, $conversion_rate, $selling_unit);

            $excelData[] = $lineData; 
        }//no item like this
    }//forloop  

    //Export data to excel and save as xlsx file 
    $xlsx = CodexWorld\PhpXlsxGenerator::fromArray($excelData ); 
    // $file_save_path = "../assets/Files/tmp_save_excel.xlsx";
    $file_save_path = '../Assets/uploads/tmp_product_excel.xlsx';
    $xlsx->saveAs($file_save_path);

    //Delete product excel sheet
    $delete_path = '../Assets/uploads/product_excel.csv';

    unlink($delete_path);

    $_SESSION['file_upload'] = 5;
    header("Location: ../Public/upload_stock.php");

}//transfer bulk

if(isset($_POST['btn_check_category']))
{
    $col_shop = $_POST['cmb_shop'];
    $col_category = $_POST['cmb_category'];
    $col_subcategory = $_POST['cmb_subcategory'];

    //check categories
    // Path to the Excel file
    $filePath = '../Assets/uploads/product_excel.csv';

    // Load the Excel file
    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    $dbObj = new DBTransactions();

    for($i=1; $i<count($rows); $i++)
    {
        $category_name = strtoupper($rows[$i][$col_category]);

        $sql = "SELECT * FROM categories WHERE CategoryName = '".$category_name."' AND shop_SHID = ".$col_shop.";";

        $catData = $dbObj->getData($sql);

        if(empty($catData))
        {
        $catObj = new Category();
        $catData = $catObj->getCategoryCount($col_shop);
        $category_count = intval($catData[0]['CategoryCount']);
        $category_count += 1;
    
        $commObj = new Common();
        $category_no = $commObj->createCount("MC", $category_count);

        $catObj->setCategory($category_no, $category_name, $col_shop);

        // echo "set category - " . $category_name;
        }//empty
    }//for 

    $catObj = new Category();

    for($i=1; $i<count($rows); $i++)
    {
        $category_name = $rows[$i][$col_category];
        $subcategory_name = $rows[$i][$col_subcategory];

        $sql = "SELECT * FROM subcategories
        INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
        WHERE CategoryName = '".$category_name."' AND SubCatName = '".$subcategory_name."' AND categories.shop_SHID = '".$shop_id."';";
        $subcatData = $dbObj->getData($sql);

        // echo "category - " . $category_name . "<br>";
        // echo "subcate - " . $subcategory_name . "<br>";

        if(empty($subcatData))
        {
            $sql_1 = "SELECT * FROM categories WHERE CategoryName = '".$category_name."' AND shop_SHID = ".$shop_id.";";
            $catData = $dbObj->getData($sql_1);

            // echo "category - " . $category_name . "<br>";

            $category_id = $catData[0]['CTID'];

            //get count
            $subcatCount = $catObj->getSubcategoryCount($shop_id);
            $subcat_count = intval($subcatCount[0]['SubcatCount']);
            $subcat_count += 1;
 
            $commObj = new Common();
            $subcat_no = $commObj->createCount("SC", $subcat_count);

            $catObj->setSubCategory($subcat_no, $subcategory_name, $category_id);
        }//empty
    }//for

    $_SESSION['file_upload'] = 2;
    header("Location: ../Public/upload_stock.php");

}//check category and subcategory

if(isset($_POST['btn_delete_excel1']))
{
    $delete_path = '../Assets/uploads/product_excel.csv';

    unlink($delete_path);

    $_SESSION['file_upload'] = 4;
    header("Location: ../Public/upload_stock.php");
}//delete excel one

if(isset($_POST['btn_upload_items']))
{
    //create necessary object
    $prodObj = new Product();
    $invObj = new Inventory();
    $dbObj = new DBTransactions();
    $priceObj = new PriceHistory();

    // Path to the Excel file
    $filePath = '../Assets/uploads/tmp_product_excel.xlsx';

    // Load the Excel file
    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    $product_image = null;
    $product_stat = 1;

    //get current date time
    date_default_timezone_set("Asia/Colombo");
    $added_date = date("Y-m-d");
    $updated_date = date("Y-m-d");

    for($i=1; $i<count($rows); $i++)
    {
        //PDID, ProductNo, ProdImage, Barcode, ItemName, ProdDescription, SecondName, ProdPurchasePrice, ProdSellPrice, CartonQty, ProductStat, AddedDate, UpdatedDate, ItemType, user_USID, UpdateUserID, Subcategories_SCID, shop_SHID, PurchaseUnit, UnitConversion, SellingUnit

        $barcode = $rows[$i][0];
        $item_name = $rows[$i][1];
        $description = $rows[$i][2];
        $second_name = $rows[$i][3];
        $purchase_price = $rows[$i][4];
        $selling_price = $rows[$i][5];
        $carton_qty = $rows[$i][6];
        $item_type = $rows[$i][7];
        $subcategory_id = $rows[$i][8];
        $col_shop = $rows[$i][9];
        $purchase_unit = $rows[$i][10];
        $conversion_rate = $rows[$i][11];
        $selling_unit = $rows[$i][12];

        //get product no
        $productData = $prodObj->getProductCount($col_shop);
        $prod_count = intval($productData[0]['ProductCount']);
        $prod_count += 1;

        $commObj = new Common();
        $product_no = $commObj->createCount("PD", $prod_count);

        $prodObj->setProduct($product_no, $product_image, $barcode, $item_name, $description, $second_name, $purchase_price, $selling_price, $carton_qty, $product_stat, $added_date, $updated_date, $item_type, $user_id, $user_id, $subcategory_id, $col_shop, $purchase_unit, $conversion_rate, $selling_unit);

        $product_id=$prodObj->getProductCount($col_shop);
        $product_id=$product_id[0]["ProductCount"];
        $count=$invObj->getInventorywithproductID($product_id);
        $count=$count[0]["procount"];
        $count=$count+1;
        $batch_id=$invObj->getSequence($count);
        $batch_id="B".$batch_id;
        $CurrentQty=0;
        $BillQty=0;
        $ReturnQty=0;
        $TransfeInQty=0;
        $TransferOutQty=0;
        $RackID=1;
        $default=1;
        $mnf_date= date("Y-m-d");
        $exp_date= date("Y-m-d");
        $effective_date=date("Y-m-d");

        $invObj->setInventory2($CurrentQty, $BillQty, $ReturnQty, $TransfeInQty, $TransferOutQty, $product_id, $col_shop, $RackID, $batch_id,$default);
        $sql = "SELECT max(INID) AS MAXSID FROM inventory WHERE shop_SHID='$col_shop';";
        $dbMax = $dbObj->getData($sql);
        $max_id = floatval($dbMax[0]['MAXSID']);
        $new_inventory_id = $max_id ;
        $grn_detail_id = 0;

        $priceObj->setPriceHistory($product_id, 0, $effective_date, $purchase_price, $selling_price, $selling_price, $mnf_date, $exp_date, $batch_id, $new_inventory_id, $grn_detail_id);

    }//for loop

    //Delete product excel sheet
    $delete_path = '../Assets/uploads/tmp_product_excel.xlsx';

    unlink($delete_path);

    $_SESSION['file_upload'] = 6;
    header("Location: ../Public/upload_stock.php");
}//upload excel sheet

if(isset($_POST['btn_delete_items'])) 
{
    //Delete product excel sheet
    $delete_path = '../Assets/uploads/tmp_product_excel.xlsx';

    unlink($delete_path);

    $_SESSION['file_upload'] = 6;
    header("Location: ../Public/upload_stock.php");
}//delete upload excell sheet

//=========================== Upoload Stock ==========================//

if(isset($_POST['btn_upload_inventory']))
{
    //file upload directry
    $target_dir = "../Assets/uploads/";

    if(!empty($_FILES['inventory_file']['name']))
    {
        $inventory_file_name = basename($_FILES['inventory_file']['name']);

        $target_file_path = $target_dir . $inventory_file_name;
        $file_type = pathinfo($target_file_path, PATHINFO_EXTENSION);

        $target_file_path = $target_dir . "inventory_excel." . $file_type;
        
        // Allow certain file formats 
        $allow_types = array('xlsx','csv');
        if(in_array($file_type, $allow_types))
        {
            if(move_uploaded_file($_FILES["inventory_file"]["tmp_name"], $target_file_path))
            {
                $_SESSION['inv_upload'] = 2;
                header("Location: ../Public/upload_inventory.php");
            }//file moved
        }//in array
        else
        {
            $_SESSION['inv_upload'] = 1;
            header("Location: ../Public/upload_inventory.php");
            die("Error: Not Support format");
        }//not support type
    }//has file
    else
    {
        $_SESSION['inv_upload'] = 0;
        header("Location: ../Public/upload_inventory.php");
    }//no file

}//upload inventory

if(isset($_POST['btn_check_items']))
{
    $col_barcode = $_POST['cmb_barcode'];
    $col_itemname = $_POST['cmb_itemname'];
    $col_shop = $_POST['cmb_shop'];

      // Path to the Excel file
      $filePath_csv = '../Assets/uploads/inventory_excel.csv';
      $filePath_xlsx = '../Assets/uploads/inventory_excel.xlsx';
  
      $has_file_csv = file_exists("../Assets/uploads/inventory_excel.csv") ? true : false;
      $has_file_xlsx = file_exists("../Assets/uploads/inventory_excel.xlsx") ? true : false;

       //has excel file
    if($has_file_xlsx)
    {
        // Load the Excel file
        $spreadsheet = IOFactory::load($filePath_xlsx);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $has_item = 0;
        for($i=1; $i<count($rows); $i++)
        {
            $barcode = $rows[$i][$col_barcode];
            $item_name = $rows[$i][$col_itemname];

            $item_name = str_replace("`","'",$item_name);

            // echo "has item - " . checkItems($barcode, $item_name, $col_shop) . "<br>";

            if(checkItems($barcode, $item_name, $col_shop))
            {
                $has_item = 1;
            }
            else
            {
                $has_item = 0;
                break;
            }
        
        }//for

        if($has_item == 0)
        {
            $_SESSION['inv_upload'] = 3;
            header("Location: ../Public/upload_inventory.php");
        }
        else
        {
            $_SESSION['inv_upload'] = 4;
            header("Location: ../Public/upload_inventory.php");
        }
    }//has excel file
    elseif($has_file_csv)
    {
        // Load the Excel file
        $spreadsheet = IOFactory::load($filePath_csv);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $has_item = 0;
        for($i=1; $i<count($rows); $i++)
        {
            $barcode = $rows[$i][$col_barcode];
            $item_name = $rows[$i][$col_itemname];

            // echo "has item - " . checkItems($barcode, $item_name, $col_shop) . "<br>";

            if(checkItems($barcode, $item_name, $col_shop))
            {
                $has_item = 1;
            }
            else
            {
                $has_item = 0;
                break;
            }
        
        }//for

        if($has_item == 0)
        {
            $_SESSION['inv_upload'] = 3;
            header("Location: ../Public/upload_inventory.php");
        }
        else
        {
            $_SESSION['inv_upload'] = 4;
            header("Location: ../Public/upload_inventory.php");
        }
    }//has csv file
}//check items

if(isset($_POST['btn_convert_inventory']))
{
    $col_barcode = $_POST['cmb_barcode'];
    $col_itemname = $_POST['cmb_itemname'];
    $col_qty = $_POST['cmb_qty'];
    $col_variation = $_POST['cmb_variation'];
    $col_purchaseprice = $_POST['cmb_purchaseprice'];
    $col_sellingprice = $_POST['cmb_sellingprice'];
    $col_labelprice = $_POST['cmb_labelprice'];
    $col_mnfdate = $_POST['cmb_mnfdate'];
    $col_expdate = $_POST['cmb_expdate'];
    $col_shop = $_POST['cmb_shop'];
    $col_category = isset($_POST['cmb_category']) ? $_POST['cmb_category'] : '-1';
    $col_subcategory = isset($_POST['cmb_subcategory']) ? $_POST['cmb_subcategory'] : '-1';

    // Path to the Excel file
    $filePath_csv = '../Assets/uploads/inventory_excel.csv';
    $filePath_xlsx = '../Assets/uploads/inventory_excel.xlsx';

    $has_file_csv = file_exists("../Assets/uploads/inventory_excel.csv") ? true : false;
    $has_file_xlsx = file_exists("../Assets/uploads/inventory_excel.xlsx") ? true : false;

    if($has_file_csv || $has_file_xlsx)
    {
        // Load whichever file exists
        if($has_file_xlsx)
        {
            $spreadsheet = IOFactory::load($filePath_xlsx);
        }
        else
        {
            $spreadsheet = IOFactory::load($filePath_csv);
        }
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $exelData[] = array("Barcode", "ItemName", "product_id", "Qty", "Variation", "variation_id", "PurchasePrice", "SellingPrice", "LabelPrice", "MnfDate", "ExpDate", "shop_id");

        for($i=1; $i<count($rows); $i++)
        {
            $barcode = $rows[$i][$col_barcode];
            $item_name = $rows[$i][$col_itemname];

            $item_name_clean = str_replace("`","'",$item_name);
            $product_id = getProductID($barcode, $item_name_clean, $col_shop);

            // If product not found and category/subcategory provided, try by category
            if($product_id == 0 && $col_category != '-1' && $col_subcategory != '-1')
            {
                $category_name = $rows[$i][$col_category];
                $subcategory_name = $rows[$i][$col_subcategory];
                $product_id = getProductIDByCategoryItem($item_name_clean, $category_name, $subcategory_name, $col_shop);
            }

            $qty = is_numeric($rows[$i][$col_qty]) ? $rows[$i][$col_qty] : 0;

            $variation = $col_variation == '-1' ? 1 : $rows[$i][$col_variation];
            $variation_id = getVariationID($variation, $product_id);

            $purchase_price = $col_purchaseprice == '-1' ? 0 : $rows[$i][$col_purchaseprice];
            $selling_price = $col_sellingprice == '-1' ? 0 : $rows[$i][$col_sellingprice];
            $label_price = $col_labelprice == '-1' ? 0 : $rows[$i][$col_labelprice];

            //get current date
            date_default_timezone_set("Asia/Colombo");
            $effective_date = date("Y-m-d");

            $mnf_date = $col_mnfdate == '-1' ? $effective_date : $rows[$i][$col_mnfdate];
            $exp_date = $col_expdate == '-1' ? $effective_date : $rows[$i][$col_expdate];

            $col_shop_id = $col_shop;

            $lineData = array($barcode, $item_name, $product_id, $qty, $variation, $variation_id, $purchase_price, $selling_price, $label_price, $mnf_date, $exp_date, $col_shop_id);
    
            $exelData[] = $lineData; 
        }//for

        //Export data to excel and save as xlsx file 
        $xlsx = CodexWorld\PhpXlsxGenerator::fromArray( $exelData );
        $file_save_path = '../Assets/uploads/tmp_inventory_excel.xlsx';
        $xlsx->saveAs($file_save_path);

        //Delete uploaded excel sheet
        $delete_path_csv = '../Assets/uploads/inventory_excel.csv';
        $delete_path_xlsx = '../Assets/uploads/inventory_excel.xlsx';

        $has_file_csv = file_exists("../Assets/uploads/inventory_excel.csv") ? true : false;
        $has_file_xlsx = file_exists("../Assets/uploads/inventory_excel.xlsx") ? true : false;

        if($has_file_csv)
        {
            unlink($delete_path_csv);
        }
        if($has_file_xlsx)
        {
            unlink($delete_path_xlsx);
        }
    
        $_SESSION['file_upload'] = 6;
        header("Location: ../Public/upload_inventory.php");

    }//has file

}//convert inventory

if(isset($_POST['btn_delete_excel2']))
{
    //Delete product excel sheet
    $delete_path_csv = '../Assets/uploads/inventory_excel.csv';
    $delete_path_xlsx = '../Assets/uploads/inventory_excel.xlsx';

    $has_file_csv = file_exists("../Assets/uploads/inventory_excel.csv") ? true : false;
    $has_file_xlsx = file_exists("../Assets/uploads/inventory_excel.xlsx") ? true : false;

    if($has_file_csv)
    {
        unlink($delete_path_csv);
    }
    elseif($has_file_xlsx)
    {
        unlink($delete_path_xlsx);
    }
   
    $_SESSION['file_upload'] = 5;
    header("Location: ../Public/upload_inventory.php");
}//delete inventory file

if(isset($_POST['btn_add_inventory']))
{
    //create necessary object
    $prodObj = new Product();
    $invObj = new Inventory();
    $dbObj = new DBTransactions();
    $priceObj = new PriceHistory();

    // Path to the Excel file
    $filePath = '../Assets/uploads/tmp_inventory_excel.xlsx';

    // Load the Excel file
    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    for($i=1; $i<count($rows); $i++)
    {
        $barcode = $rows[$i][0];
        $item_name = $rows[$i][1];
        $product_id = $rows[$i][2];
        $qty = $rows[$i][3];
        $variation = $rows[$i][4];
        $variation_id = $rows[$i][5];
        $purchase_price = $rows[$i][6];
        $selling_price = $rows[$i][7];
        $label_price = $rows[$i][8];
        $mnf_date = date("Y-m-d", strtotime($rows[$i][9]));
        $exp_date = date("Y-m-d", strtotime($rows[$i][10]));
        $col_shop_id = $rows[$i][11];

        $batch_id = getBatchID($product_id, $col_shop_id);

        $BillQty = 0;
        $ReturnQty = 0;
        $TransferInQty = 0;
        $TransferOutQty = 0;
        $RackID = 1;
        $default = 1;

        //get current date time
        date_default_timezone_set("Asia/Colombo");
        $effective_date=date("Y-m-d");

        // echo "product_id - " .$product_id. "<br>";
        // echo "batch_id - " .$batch_id. "<br>";

        $invObj->setInventory2($qty, $BillQty, $ReturnQty, $TransferInQty, $TransferOutQty, $product_id, $col_shop_id, $RackID, $batch_id,$default);

        $sql = "SELECT max(INID) AS MAXSID FROM inventory WHERE shop_SHID='$col_shop_id';";
        $dbMax = $dbObj->getData($sql);
        $max_id = floatval($dbMax[0]['MAXSID']);
        $new_inventory_id = $max_id;
        $grn_detail_id = 0;

        $priceObj->setPriceHistory($product_id, $variation_id, $effective_date, $purchase_price, $selling_price, $label_price, $mnf_date, $exp_date, $batch_id, $new_inventory_id, $grn_detail_id);

        $delete_path_xlsx = '../Assets/uploads/tmp_inventory_excel.xlsx';

        $has_file_xlsx = file_exists("../Assets/uploads/tmp_inventory_excel.xlsx") ? true : false;

        if($has_file_xlsx)
        {
            unlink($delete_path_xlsx);
        }//has file

        $_SESSION['file_upload'] = 8;
        header("Location: ../Public/upload_inventory.php");
    }//for

}//upload inventory

if(isset($_POST['btn_delete_invfile']))
{
    $delete_path_xlsx = '../Assets/uploads/tmp_inventory_excel.xlsx';

    $has_file_xlsx = file_exists("../Assets/uploads/tmp_inventory_excel.xlsx") ? true : false;

    if($has_file_xlsx)
    {
        unlink($delete_path_xlsx);
    }//has file

    $_SESSION['file_upload'] = 7;
    header("Location: ../Public/upload_inventory.php");
}//delete inv tmp file

//============================== Functions  ===============================//
function checkCategories($shop_id)
{
    // Path to the Excel file
    $filePath = '../Assets/uploads/product_excel.csv';

    // Load the Excel file
    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    $dbObj = new DBTransactions();

    for($i=1; $i<count($rows); $i++)
    {
        $category_name = strtoupper($rows[$i][0]);

        $sql = "SELECT * FROM categories WHERE CategoryName = '".$category_name."' AND shop_SHID = ".$shop_id.";";

        $catData = $dbObj->getData($sql);

        if(empty($catData))
        {
            $catObj = new Category();
            $catData = $catObj->getCategoryCount($shop_id);
            $category_count = intval($catData[0]['CategoryCount']);
            $category_count += 1;
        
            $commObj = new Common();
            $category_no = $commObj->createCount("MC", $category_count);

            $catObj->setCategory($category_no, $category_name, $shop_id);
        }//empty
    }//for 
}//check categories

function checkSubCategories($shop_id)
{
    // Path to the Excel file
    $filePath = '../Assets/uploads/product_excel.csv';

    // Load the Excel file
    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    $dbObj = new DBTransactions();
    $catObj = new Category();

    for($i=1; $i<count($rows); $i++)
    {
        $category_name = $rows[$i][0];
        $subcategory_name = $rows[$i][1];

        $sql = "SELECT * FROM subcategories
        INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
        WHERE CategoryName = '".$category_name."' AND SubCatName = '".$subcategory_name."' AND categories.shop_SHID = '".$shop_id."';";

        $catData = $dbObj->getData($sql);

        if(empty($catData))
        {
            $sql_1 = "SELECT * FROM categories WHERE CategoryName = '".$category_name."' AND shop_SHID = ".$shop_id.";";
            $catData = $dbObj->getData($sql_1);
            $category_id = $catData[0]['CTID'];

            //get count
            $subcatCount = $catObj->getSubcategoryCount($shop_id);
            $subcat_count = intval($subcatCount[0]['SubcatCount']);
            $subcat_count += 1;

            $commObj = new Common();
            $subcat_no = $commObj->createCount("SC", $subcat_count);

            $catObj->setSubCategory($subcat_no, $subcategory_name, $category_id);
        }//empty
    }//for
}//check sub category

function getSubcategoryID($category_name, $subcategory_name, $col_shop)
{
    $dbObj = new DBTransactions();

    $sql = "SELECT * FROM subcategories
    INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
    WHERE CategoryName = '".$category_name."' AND SubCatName = '".$subcategory_name."' AND categories.shop_SHID = ".$col_shop.";";

    $subcatData = $dbObj->getData($sql);

    if(!empty($subcatData))
    {
        $subcategory_id = $subcatData[0]['SCID'];
    }//has subcategory
    else
    {
        $subcategory_id = 0;
    }

    return $subcategory_id;

}//get sub category id

function checkBarcodeItem($barcode, $item_name, $col_shop)
{
    $dbObj = new DBTransactions();

    //$sql = "SELECT * FROM products WHERE Barcode = '".$barcode."' AND ItemName = '".$item_name."' AND shop_SHID = ".$col_shop.";";
    //only check barcode
    $sql = "SELECT * FROM products WHERE Barcode = '".$barcode."' AND shop_SHID = ".$col_shop.";";

    $itemData = $dbObj->getData($sql);

    if(!empty($itemData))
    {
        return 1;
    }//has columns
    else
    {
        return 0;
    }//no items

}//check barcode item

function checkItems($barcode, $item_name, $col_shop)
{
    $dbObj = new DBTransactions();

    $item_name = str_replace("'", "\\'", $item_name);

    //get company stat
    $sql = "SELECT * FROM shop
    INNER JOIN company ON company.CMID = shop.Company_CMID
    WHERE SHID = ".$col_shop.";";

    $shopData = $dbObj->getData($sql);
    $multi_category = floatval($shopData[0]['is_multicategory']);
    $company_id = floatval($shopData[0]['CMID']);

    if($multi_category == 1)
    {
        $sql = "SELECT * FROM products 
        INNER JOIN shop ON shop.SHID = products.shop_SHID
        WHERE Barcode = '".$barcode."' AND ItemName = '".$item_name."' AND shop.Company_CMID = ".$company_id.";";
    }//has multi category
    else
    {
        $sql = "SELECT * FROM products WHERE Barcode = '".$barcode."' AND ItemName = '".$item_name."' AND shop_SHID=".$col_shop.";";
    }//no multi category

    $itemData = $dbObj->getData($sql);

    $has_item = empty($itemData) ? 0 : 1;

    return $has_item;

}//check items

function getProductID($barcode, $item_name, $col_shop)
{
    $dbObj = new DBTransactions();

    $item_name = str_replace("'", "\\'", $item_name);

    //get company stat
    $sql = "SELECT * FROM shop
    INNER JOIN company ON company.CMID = shop.Company_CMID
    WHERE SHID = ".$col_shop.";";

    $shopData = $dbObj->getData($sql);
    $multi_category = floatval($shopData[0]['is_multicategory']);
    $company_id = floatval($shopData[0]['CMID']);

    if($multi_category == 1)
    {
        $sql = "SELECT * FROM products 
        INNER JOIN shop ON shop.SHID = products.shop_SHID
        WHERE Barcode = '".$barcode."' AND ItemName = '".$item_name."' AND shop.Company_CMID = ".$company_id.";";
    }
    else
    {
        $sql = "SELECT * FROM products WHERE Barcode = '".$barcode."' AND ItemName = '".$item_name."' AND shop_SHID=".$col_shop.";";
    }

    $itemData = $dbObj->getData($sql);

    $product_id = 0;
    if(!empty($itemData))
    {
        $product_id = $itemData[0]['PDID'];
    }//has items
    else
    {
        $product_id = 0;
    }

    return $product_id;
}//get product id

function getVariationID($variation, $product_id)
{
    $dbObj = new DBTransactions();

    $sql = "SELECT * FROM variations WHERE VariationName = '".$variation."' AND products_PDID=".$product_id.";";

    $varData = $dbObj->getData($sql);

    $variation_id = empty($varData) ? 1 : $varData[0]['VRID'];

    return $variation_id;
}//get variation id

function getBatchID($product_id, $col_shop_id)
{
    //check inventory batch id
    $dbObj = new DBTransactions();
    $invObj = new Inventory();

    $sql = "SELECT * FROM inventory WHERE products_PDID = ".$product_id." AND shop_SHID = ".$col_shop_id.";";

    $batchData = $dbObj->getData($sql);

    $batch_count = count($batchData) + 1;
    $batch_id = $invObj->getSequence($batch_count);
    $batch_id = "B" . $batch_id;

    return $batch_id;

}//get batch id

function getProductIDByCategoryItem($item_name, $category_name, $subcategory_name, $col_shop)
{
    $dbObj = new DBTransactions();

    $item_name_sql = str_replace("'", "\\'", $item_name);
    $category_name_sql = str_replace("'", "\\'", $category_name);
    $subcategory_name_sql = str_replace("'", "\\'", $subcategory_name);

    $sql = "SELECT products.PDID FROM products
    INNER JOIN subcategories ON subcategories.SCID = products.Subcategories_SCID
    INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
    WHERE products.ItemName = '".$item_name_sql."'
    AND categories.CategoryName = '".$category_name_sql."'
    AND subcategories.SubCatName = '".$subcategory_name_sql."'
    AND products.shop_SHID = ".$col_shop.";";

    $itemData = $dbObj->getData($sql);

    $product_id = 0;
    if(!empty($itemData))
    {
        $product_id = $itemData[0]['PDID'];
    }

    return $product_id;
}//get product id by category and item