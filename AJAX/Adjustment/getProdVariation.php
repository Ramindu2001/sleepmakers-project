<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";

$shopObj = new Shop();

$shop_id = $_SESSION['shop_id'];
$product_id = $_GET['product_id'];

$sql = "SELECT * FROM inventory
INNER JOIN products ON products.PDID = inventory.products_PDID
INNER JOIN pricehistory ON pricehistory.Inventory_INID = inventory.INID
WHERE inventory.shop_SHID = ".$shop_id." AND CurrentQty > 0 AND ProductID = ".$product_id.";";

$dbObj = new DBTransactions();
$dbData = $dbObj->getData($sql);

echo json_encode($dbData);

// foreach($dbData as $row)
// {
//     echo "<option value='".$row['PHID']."'>";
    
//     $option = $row['Barcode'] . " - " . $row['ItemName'] ." - Qty:" . $row['CurrentQty'];
//     $variation = "";
//     $expire = "";

//     if($shopObj->hasVariation($shop_id))
//     {
//         $variation = " - Var:" . $row['VariationName'];
//     }//has variation
//     else
//     {
//         $variation = "";
//     }//no variation 
    
//     if($shopObj->hasExpiry($shop_id))
//     {
//         $expire = " - Exp:" . $row['ExpDate'];
//     }//has expire date
//     else
//     {
//         $expire = "";
//     }
    
//     echo $option . $variation . $expire;

//     echo "</option>";
// }//foreach