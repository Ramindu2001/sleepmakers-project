<?php 
include "../../Includes/config.php";
include "../../Model/product_class.php";

$var_id = $_GET['var_id'];
$variation_name = $_GET['variation_name'];

$varObj = new Product();
$varObj->editVariation($variation_name, $var_id);