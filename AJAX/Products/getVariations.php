<?php 
include "../../Includes/config.php";
include "../../Model/product_class.php";

$product_id = $_GET['product_id'];

$varObj = new Product();
$varData = $varObj->getVariationByProduct($product_id);

echo "<tr>";
echo "<th style='display:none;'>Variation</th>";
echo "<th>Variation</th>";
echo "<th>Action</th>";
echo "</tr>";

foreach($varData as $row)
{
    echo "<tr>";
    echo "<td style='display:none;'>".$row['VRID']."</td>";
    echo "<td>".$row['VariationName']."</td>";
    echo "<td>";
    echo "<button type='button' id='edit_variation_".$row['VRID']."' class='btn border border-primary'><i class='ti ti-edit'></i></button>";
    echo "</td>";
    echo "</tr>";
}//foreach