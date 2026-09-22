<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$shop_id = $_SESSION['shop_id'];
$product_id = $_GET['product_id'];

$sql = "SELECT * FROM pricehistory
INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
INNER JOIN products ON products.PDID = pricehistory.ProductID
LEFT JOIN variations ON variations.VRID = pricehistory.VariationID
WHERE PDID = ".$product_id." AND inventory.shop_SHID = ".$shop_id.";";

$dbObj = new DBTransactions();
$dbData = $dbObj->getData($sql);

foreach($dbData as $row)
{
    echo "<tr>";

    echo "<td style='display:none'>".$row['PHID']."</td>";
    echo "<td>";
    if(!empty($row['ProdImage']))
    {
        echo "<img style='width:120px; heigh: auto;' src='../Assets/Images/prod_images/".$row['ProdImage']."'>";
    }
    else
    {
        echo "<img style='width:120px; heigh: auto;' src='../Assets/Images/icons/product.png'>";
    }
    echo "</td>";

    echo "<td>";
    echo "Barcode: <b>" . $row['Barcode'] . "</b><br>";
    echo "Item: <b>" . $row['ItemName'] . "</b><br>";
    echo "</td>";

    echo "<td>";
    echo "Current Qty: <b>" . $row['CurrentQty'] . "</b><br>";
    echo "Variation: <b>" . $row['VariationName'] . "</b><br>";
    echo "</td>";

    echo "<td>";
    echo "Purchase Price: <b>" . $row['PurchasePrice'] . "</b><br>";
    echo "Selling Price: <b>" . $row['SellingPrice'] . "</b><br>";
    echo "</td>";

    echo "<td>";
    echo "Mnf Date: <b>" . $row['MnfDate'] . "</b><br>";
    echo "Exp Date: <b>" . $row['ExpDate'] . "</b><br>";
    echo "</td>";

    echo "</tr>";
}//foreach

    echo "<tr>";
    echo "<td colspan='5' class='text-light bg-primary rounded'>Click to select an Item</td>";
    echo "<tr>";