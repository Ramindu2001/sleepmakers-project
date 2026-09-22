<?php 
include "../../Includes/config.php";
include "../../Model/product_class.php";

$variation_name = $_GET['variation_name'];
$product_id = $_GET['product_id'];

$varObj = new Product();
$varObj->setVariation($variation_name, $product_id);
