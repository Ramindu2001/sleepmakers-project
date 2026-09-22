<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";

$shopObj = new Shop();

$shop_id = $_SESSION['shop_id'];
$txt_input = $_GET['txt_search'];

if($shopObj->hasMinus($shop_id))
{
    $sql = "SELECT * FROM pricehistory
    INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
    INNER JOIN products ON products.PDID = inventory.products_PDID
    LEFT JOIN variations ON variations.VRID = pricehistory.VariationID
    WHERE Barcode = '".$txt_input."' AND inventory.shop_SHID = ".$shop_id.";";
}//has minus
else
{
    $sql = "SELECT * FROM pricehistory
    INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
    INNER JOIN products ON products.PDID = inventory.products_PDID
    LEFT JOIN variations ON variations.VRID = pricehistory.VariationID
    WHERE Barcode = '".$txt_input."' AND inventory.shop_SHID = ".$shop_id." AND CurrentQty > 0;";
}//no minus

$dbObj = new DBTransactions();
$dbData = $dbObj->getData($sql);

if(!empty($dbData))
{
    $pricehistory_id = $dbData[0]['PHID'];
    $sell_price = $dbData[0]['SellingPrice'];
    
    $data = array();
    
    $data['pricehistory_id'] = $pricehistory_id;
    $data['sell_price'] = $sell_price;
    
    echo json_encode($data);
}//has items by barcode
else
{
    if($shopObj->hasMinus($shop_id))
    {
        $sql_1 = "SELECT * FROM pricehistory
        INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
        INNER JOIN products ON products.PDID = inventory.products_PDID
        LEFT JOIN variations ON variations.VRID = pricehistory.VariationID
        WHERE ItemName LIKE '%".$txt_input."%' AND inventory.shop_SHID = ".$shop_id.";";
    }//has minus
    else
    {
        $sql_1 = "SELECT * FROM pricehistory
        INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
        INNER JOIN products ON products.PDID = inventory.products_PDID
        LEFT JOIN variations ON variations.VRID = pricehistory.VariationID
        WHERE ItemName LIKE '%".$txt_input."%' AND inventory.shop_SHID = ".$shop_id." AND CurrentQty > 0;";
    }//no minus
    $dbData_1 = $dbObj->getData($sql_1);
    if(!empty($dbData_1))
    {
        if(!empty($dbData_1))
        {
            $pricehistory_id = $dbData_1[0]['PHID'];
            $sell_price = $dbData_1[0]['SellingPrice'];
            
            $data = array();
            
            $data['pricehistory_id'] = $pricehistory_id;
            $data['sell_price'] = $sell_price;
            
            echo json_encode($data);
        }//has items by barcode
    }//has item by name
}//search by item name
