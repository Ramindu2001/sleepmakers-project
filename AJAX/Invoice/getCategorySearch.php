<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";

$shop_id = $_SESSION['shop_id'];
$category_id = $_GET['category_id'];

$shopObj = new Shop();

$sql = "";

if($category_id == '0')
{
    if($shopObj->hasMinus($shop_id))
    {
        $sql = "SELECT * FROM pricehistory
        INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
        INNER JOIN products ON products.PDID = pricehistory.ProductID
        INNER JOIN subcategories ON subcategories.SCID = products.Subcategories_SCID
        WHERE products.shop_SHID = ".$shop_id." AND ItemType = 'P' GROUP BY PDID LIMIT 50;";
    }//has minus
    else
    {
        $sql = "SELECT * FROM pricehistory
        INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
        INNER JOIN products ON products.PDID = pricehistory.ProductID
        INNER JOIN subcategories ON subcategories.SCID = products.Subcategories_SCID
        WHERE products.shop_SHID = ".$shop_id." AND CurrentQty > 0 AND ItemType = 'P' GROUP BY PDID LIMIT 50;";
    }//no minus
}//get all categories
else
{

    if($shopObj->hasMinus($shop_id))
    {
        $sql = "SELECT * FROM pricehistory
        INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
        INNER JOIN products ON products.PDID = pricehistory.ProductID
        INNER JOIN subcategories ON subcategories.SCID = products.Subcategories_SCID
        WHERE categories_CTID = ".$category_id." AND products.shop_SHID = ".$shop_id." AND ItemType = 'P'; GROUP BY PDID";
    }//has minus
    else
    {
        $sql = "SELECT * FROM pricehistory
        INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
        INNER JOIN products ON products.PDID = pricehistory.ProductID
        INNER JOIN subcategories ON subcategories.SCID = products.Subcategories_SCID
        WHERE categories_CTID = ".$category_id." AND CurrentQty > 0 AND products.shop_SHID = ".$shop_id." AND ItemType = 'P' GROUP BY PDID;";
    }//no minus

}//search by category


$dbObj = new DBTransactions();
$dbData = $dbObj->getData($sql);

echo json_encode($dbData, JSON_FORCE_OBJECT);