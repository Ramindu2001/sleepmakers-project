<?php 
include "../../Includes/config.php";
include "../../Model/product_class.php";

$product_id = $_GET['product_id'];
$batch_id = $_GET['batch_id'];

$varObj = new Product();
$varData = $varObj->getSpecificByProductAndBatch($product_id,$batch_id);

echo json_encode($varData, JSON_FORCE_OBJECT);