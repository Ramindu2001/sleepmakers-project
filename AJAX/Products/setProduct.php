<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/common_class.php";
include "../../Model/product_class.php";
include "../../Model/inventory_class.php";
include "../../Model/pricehistory_class.php";

$prodObj = new Product();
$invObj = new Inventory();
$dbObj = new DBTransactions();
$priceObj = new PriceHistory();

$user_id = $_SESSION['user_id'];
$shop_id = $_SESSION['shop_id'];

$productData = $prodObj->getProductCount($shop_id);
$prod_count = intval($productData[0]['ProductCount']);
$prod_count += 1;

$commObj = new Common();
$product_no = $commObj->createCount("PD", $prod_count);
$prod_image_name = null;
if(!isset($_GET['barcode']) || $_GET['barcode']=="")
{
    $barcode=$product_no;
}
else 
{
   $barcode = $_GET['barcode']; 
}

$prod_name = $_GET['prod_name'];
$second_name = $_GET['second_name'];
$prod_description = $_GET['prod_description'];
$prod_carton_qty = $_GET['prod_carton_qty'];
$cmb_purchase_unit = $_GET['cmb_purchase_unit'];
$conversion_rate = $_GET['conversion_rate'];
$cmb_selling_unit = $_GET['cmb_selling_unit'];
$subcategory_id = $_GET['subcategory_id'];

$prod_purchase_price = $_GET['prod_purchase_price'];
$prod_selling_price = $_GET['prod_selling_price'];;

$prod_stat = 1;

//get current date
date_default_timezone_set("Asia/Colombo");
$added_date = date("Y-m-d");

$item_type = "P";

//create product
$prodObj->setProduct($product_no, $prod_image_name, $barcode, $prod_name, $prod_description, $second_name, $prod_purchase_price, $prod_selling_price, $prod_carton_qty, $prod_stat, $added_date, $added_date, $item_type, $user_id, $user_id, $subcategory_id, $shop_id, $cmb_purchase_unit, $conversion_rate, $cmb_selling_unit);

$current_qty = 0;
$bill_qty = 0;
$return_qty = 0;
$transfer_in_qty = 0;
$transfer_out_qty = 0;

$product_id = $prodObj->getProductCount();
$product_id = $product_id[0]["ProductCount"];

$rack_id = 1;

$count=$invObj->getInventorywithproductID($product_id);
$count=$count[0]["procount"];
$count=$count+1;

$batch_id=$invObj->getSequence($count);
$batch_id="B".$batch_id;

$default=1;

//add inventory
$invObj->setInventory2($current_qty, $bill_qty, $return_qty, $transfer_in_qty, $transfer_out_qty, $product_id, $shop_id, $rack_id, $batch_id,$default);

$mnf_date = date("Y-m-d");
$exp_date = date("Y-m-d");

$sql = "SELECT max(INID) AS MAXSID FROM inventory WHERE shop_SHID='$shop_id';";
$dbMax = $dbObj->getData($sql);
$max_id = floatval($dbMax[0]['MAXSID']);
$new_inventory_id = $max_id ;
$grn_detail_id = 0;

//add pricehistory
$priceObj->setPriceHistory($product_id, 0, $added_date, $prod_purchase_price, $prod_selling_price, $prod_selling_price, $mnf_date, $exp_date, $batch_id, $new_inventory_id, $grn_detail_id);

echo "product added successfully...";