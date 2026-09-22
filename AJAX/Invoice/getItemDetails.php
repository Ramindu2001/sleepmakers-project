<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$pricehistory_id = $_GET['pricehistory_id'];

$sql = "SELECT * FROM pricehistory
INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
INNER JOIN products ON products.PDID = pricehistory.ProductID
LEFT JOIN variations ON variations.VRID = pricehistory.VariationID
WHERE PHID = ".$pricehistory_id.";";

$dbObj = new DBTransactions();

$dbData = $dbObj->getData($sql);

$purchase_unit_id = empty($dbData[0]['PurchaseUnit']) ? 0 : $dbData[0]['PurchaseUnit'];
$selling_unit_id = empty($dbData[0]['SellingUnit']) ? 0 : $dbData[0]['SellingUnit'];
$unit_conversion_rate = $dbData[0]['UnitConversion'] + 0;

$purchase_unit = getUnitName($purchase_unit_id);
$selling_unit = getUnitName($selling_unit_id);

echo "<div class='row'>";
    //left column
    echo "<div class='col-md-4'>";
        if(empty($dbData[0]['ProdImage']))
        {
            echo "<img src='../Assets/Images/icons/product.png' style='width:90%; height:auto;'>";
        }//no image
        else
        {
            echo "<img src='../Assets/Images/prod_images/".$dbData[0]['ProdImage']."' style='width:100%; height:auto;'>";
        }//has image
    echo "</div>";

    //right column
    echo "<div class='col-md-8'>";
        echo "<p>";
        echo "Barcode: <b>". $dbData[0]['Barcode'] . "</b><br>";
        echo "Name: <b>". $dbData[0]['ItemName'] . "</b><br>";
        echo "Avl. Qty: <b>". $dbData[0]['CurrentQty'] . "</b><br>";
        echo "Price: <b>". $dbData[0]['SellingPrice'] . "</b><br>";
        echo "Unit: <b>". $purchase_unit . " = " . $selling_unit. " X " . $unit_conversion_rate. "</b><br>";
        echo "</p>";
    echo "</div>";
echo "</div>";

function getUnitName($unit_id)
{
    $sql_1 = "SELECT * FROM units WHERE UNID = ".$unit_id.";";

    $dbObj = new DBTransactions();

    $dbData = $dbObj->getData($sql_1);

    $unit = empty($dbData) ? "Pcs" : $dbData[0]['UnitName'];

    return $unit;
}//get unit name by id