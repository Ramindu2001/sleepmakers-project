<?php 
include "../../Includes/config.php";
include "../../Model/product_class.php";

$product_id = $_GET['product_id'];

$varObj = new Product();
$varData = $varObj->getVariationByProduct($product_id);

if(!empty($varData))
{
    foreach($varData as $row)
    {
        echo "<option value='".$row['VRID']."'>".$row['VariationName']."</option>";
    }//foreach
}//has variation
else
{
    echo "<option value='1'>No Variation</option>";
}//no variation