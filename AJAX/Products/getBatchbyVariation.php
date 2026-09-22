<?php 
include "../../Includes/config.php";
include "../../Model/product_class.php";

$product_id = $_GET['product_id'];
$Variation_id = $_GET['Variation_id'];

$varObj = new Product();
$varData = $varObj->getBatchesByProductVariation($product_id, $Variation_id);


foreach($varData as $row)
{   
   echo "<option value='".$row['BatchID']."'>".$row['BatchID']." - ".$row['PurchasePrice']."</option>";
}
//foreach