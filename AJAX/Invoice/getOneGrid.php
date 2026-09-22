<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";

$shop_id = $_SESSION['shop_id'];
$pricehistory_id = $_GET['pricehistory_id'];
$item_id = $_GET['item_id'];

$shopObj = new Shop();
$dbObj = new DBTransactions();

//get stock type
$sql = "SELECT * FROM `shop` WHERE SHID = ".$shop_id.";";

$stockData = $dbObj->getData($sql);

$stock_type_id = $stockData[0]['StockTypes_STID'];
/*
* 1 - Average
* 2 - FIFO
* 3 - LIFO
* 4 - Expiredate
* 5 - Batch
*/

if($shopObj->hasMinus($shop_id))
{
    $sql_1 = "SELECT 
    min(PHID) as price_id, 
    products.Barcode, 
    products.ItemName, 
    products.ProdImage, 
    inventory.CurrentQty,
    SellingPrice,
    products.PurchaseUnit,
    products.SellingUnit,
    products.UnitConversion 
    FROM pricehistory
    INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
    INNER JOIN products ON products.PDID = pricehistory.ProductID
    WHERE pricehistory.ProductID = ".$item_id.";";
}//has minus
else
{
    switch ($stock_type_id) 
    {
        case '1':
            //average select the oldest batch
            $sql_1 = "SELECT 
            min(PHID) as price_id, 
            products.Barcode, 
            products.ItemName, 
            products.ProdImage, 
            inventory.CurrentQty,
            SellingPrice,
            products.PurchaseUnit,
            products.SellingUnit,
            products.UnitConversion 
            FROM pricehistory
            INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
            INNER JOIN products ON products.PDID = pricehistory.ProductID
            WHERE pricehistory.ProductID = ".$item_id." AND inventory.CurrentQty > 0;";
        break;

        case '2':
            //first in first out
            $sql_1 = "SELECT 
            min(PHID) as price_id, 
            products.Barcode, 
            products.ItemName, 
            products.ProdImage, 
            inventory.CurrentQty,
            SellingPrice,
            products.PurchaseUnit,
            products.SellingUnit,
            products.UnitConversion
            FROM pricehistory
            INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
            INNER JOIN products ON products.PDID = pricehistory.ProductID
            WHERE pricehistory.ProductID = ".$item_id." AND inventory.CurrentQty > 0;";
        break;

        case '3':
            //last in first out
            $sql_1 = "SELECT 
            MAX(PHID) as price_id, 
            products.Barcode, 
            products.ItemName, 
            products.ProdImage, 
            inventory.CurrentQty,
            SellingPrice,
            products.PurchaseUnit,
            products.SellingUnit,
            products.UnitConversion
            FROM pricehistory
            INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
            INNER JOIN products ON products.PDID = pricehistory.ProductID
            WHERE pricehistory.ProductID = ".$item_id." AND inventory.CurrentQty > 0;";
        break;

        case '4':
            //expiredate
            $sql_1 = "SELECT 
            PHID as price_id,
            MAX(ExpDate) as exp_date, 
            products.Barcode, 
            products.ItemName, 
            products.ProdImage, 
            inventory.CurrentQty,
            SellingPrice,
            products.PurchaseUnit,
            products.SellingUnit,
            products.UnitConversion
            FROM pricehistory
            INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
            INNER JOIN products ON products.PDID = pricehistory.ProductID
            WHERE pricehistory.ProductID = ".$item_id." AND inventory.CurrentQty > 0;";
        break;

        case '5':
            //batch
            $sql_1 = "SELECT 
            min(PHID) as price_id, 
            products.Barcode, 
            products.ItemName, 
            products.ProdImage, 
            inventory.CurrentQty,
            SellingPrice,
            products.PurchaseUnit,
            products.SellingUnit,
            products.UnitConversion
            FROM pricehistory
            INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
            INNER JOIN products ON products.PDID = pricehistory.ProductID
            WHERE pricehistory.ProductID = ".$item_id." AND inventory.CurrentQty > 0;";
        break;

        default:
             //first in first out
             $sql_1 = "SELECT 
             min(PHID) as price_id, 
             products.Barcode, 
             products.ItemName, 
             products.ProdImage, 
             inventory.CurrentQty,
             SellingPrice,
             products.PurchaseUnit,
             products.SellingUnit,
             products.UnitConversion
             FROM pricehistory
             INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
             INNER JOIN products ON products.PDID = pricehistory.ProductID
             WHERE pricehistory.ProductID = ".$item_id." AND inventory.CurrentQty > 0;";
        break;
    }//switch
}//no minus

$invData = $dbObj->getData($sql_1);
$price_id = $invData[0]['price_id'];
$barcode = $invData[0]['Barcode'];
$item_name = $invData[0]['ItemName'];
$current_qty = $invData[0]['CurrentQty'];
$item_image = $invData[0]['ProdImage'];
$selling_price = $invData[0]['SellingPrice'];

$purchase_unit_id = empty($invData[0]['PurchaseUnit']) ? 0 : $invData[0]['PurchaseUnit'];
$selling_unit_id = empty($invData[0]['SellingUnit']) ? 0 : $invData[0]['SellingUnit'];
$unit_conversion_rate = $invData[0]['UnitConversion'] + 0;

$purchase_unit = getUnitName($purchase_unit_id);
$selling_unit = getUnitName($selling_unit_id);

$item_desc = "";

$item_desc .="<div class='row'>";
$item_desc .="<div class='col-md-4'>";
if(empty($item_image))
{
    $item_desc .="<img src='../Assets/Images/icons/product.png' style='width:90%; height:auto;'>";
}//no image
else
{
    $item_desc .="<img src='../Assets/Images/prod_images/".$item_image."' style='width:100%; height:auto;'>";
}//has image

$item_desc .="</div>";
$item_desc .="<div class='col-md-8'>";
$item_desc .="<p>";
$item_desc .="Barcode: " . $barcode . "<br>";
$item_desc .="Name: " . $item_name . "<br>";
$item_desc .="Qty: " . $current_qty . "<br>";
$item_desc .="Price: " . $selling_price . "<br>";
$item_desc .="Unit: " . $purchase_unit . " = ".$selling_unit." X ".$unit_conversion_rate."<br>";
$item_desc .="</p>";
$item_desc .="</div>";
$item_desc .="</div>";

$data = array(
    'stock_type' => $stock_type_id,
    'price_id' => $price_id,
    'barcode' => $barcode,
    'item_name' => $item_name,
    'item_image' => $item_image,
    'current_qty' => $current_qty,
    'selling_price' => $selling_price,
    'purchase_unit' => $purchase_unit,
    'selling_unit' => $selling_unit,
    'unit_conversion_rate' => $unit_conversion_rate,
);

// array_push($data, array('stock_type'=>$stock_type_id, 'selling_price'=>$selling_price));

echo json_encode($data);

//=================== Function ==================//
function getUnitName($unit_id)
{
    $sql_1 = "SELECT * FROM units WHERE UNID = ".$unit_id.";";

    $dbObj = new DBTransactions();

    $dbData = $dbObj->getData($sql_1);

    $unit = empty($dbData) ? "Pcs" : $dbData[0]['UnitName'];

    return $unit;
}//get unit name by id