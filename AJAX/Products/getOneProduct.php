<?php
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/product_class.php";

$product_id = $_GET['product_id'];

$sql = "SELECT * FROM products
INNER JOIN subcategories ON subcategories.SCID = products.Subcategories_SCID
INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
WHERE PDID = ".$product_id.";";

$dbObj = new DBTransactions();
$prodData = $dbObj->getData($sql);

if(!empty($prodData))
{
    echo json_encode($prodData);
}//has data