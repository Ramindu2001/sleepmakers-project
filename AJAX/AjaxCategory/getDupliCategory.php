<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/category_class.php";

$shop_id = $_SESSION['shop_id'];
$cat_name = $_GET['cat_name'];

$catObj = new Category();
$catDuplicate = $catObj->getCategoryByName($shop_id, $cat_name);

$cat_count = $catDuplicate[0]['CategoryCount'];

echo $cat_count;