<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";

$shop_id = $_SESSION['shop_id'];
$txt_input = $_GET['txt_search'];

$shopObj = new Shop();

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
    $count = count($dbData);
    if($count == 1)
    {
        echo "1";
    }//has one item
    else
    {
        echo "2";
    }//has more items
}//has barcode
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
        $count_1 = count($dbData_1);
        if($count_1 == 1)
        {
            echo "3";
        }//has one item
        else
        {
            echo "4";
        }//has more items
    }//has by name
    else
    {
        echo "5";
    }//search no item found by name or barcode
}//no result on barcode