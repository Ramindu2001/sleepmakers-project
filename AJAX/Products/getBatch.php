<?php 
include "../../Includes/config.php";
include "../../Model/product_class.php";

$product_id = $_GET['product_id'];

$varObj = new Product();
$varData = $varObj->getBatchesByProduct($product_id);


foreach($varData as $row)
{
   echo "<option value='".$row['BatchID']."'>".$row['BatchID']." - ".$row['PurchasePrice']."</option>";
}
//foreach