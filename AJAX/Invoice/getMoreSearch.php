<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";

$shop_id = $_SESSION['shop_id'];
$txt_input = $_GET['txt_search'];

//shop object
$shopObj = new Shop();

if($shopObj->hasMinus($shop_id))
{
    $sql = "SELECT * FROM pricehistory
    INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
    INNER JOIN products ON products.PDID = pricehistory.ProductID
    LEFT JOIN variations ON variations.VRID = pricehistory.VariationID
    WHERE inventory.shop_SHID = ".$shop_id." AND products.Barcode = '".$txt_input."';";
}//has minus
else
{
    $sql = "SELECT * FROM pricehistory
    INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
    INNER JOIN products ON products.PDID = pricehistory.ProductID
    LEFT JOIN variations ON variations.VRID = pricehistory.VariationID
    WHERE inventory.shop_SHID = ".$shop_id." AND products.Barcode = '".$txt_input."' AND CurrentQty > 0;";
}//no minus

$dbObj = new DBTransactions();
$dbSearch = $dbObj->getData($sql);

if(!empty($dbSearch))
{
    foreach($dbSearch as $row)
    {
        $prod_image = $row['ProdImage'];
        $img_path = empty($row['ProdImage']) ? "../Assets/Images/icons/product.png" : "../Assets/Images/prod_images/".$row['ProdImage'];
        echo "<tr>";
        echo "<td style='display:none;'>".$row['PHID']."</td>";
        echo "<td style='display:none;'>".$row['SellingPrice']."</td>";
        echo "<td><img src='".$img_path."' style='height:80px; width:auto;' ></td>";

        echo "<td>".$row['Barcode']." <br>".$row['ItemName']."</td>";

        if($shopObj->hasVariation($shop_id))
        {
            echo "<td>Variation <br> <b>".$row['VariationName']."</b></td>";
        }//has variation
        
        echo "<td>Qty<br><b>".$row['CurrentQty']+0 ."</b></td>";

        echo "<td>Price <br> <b>".$row['SellingPrice']."</b></td>";

        if($shopObj->hasExpiry($shop_id))
        {
            echo "<td>Expire <br> <b>".$row['ExpDate']."</b></td>";
        }//has variation

        echo "</tr>";
    }//foreach
}//has more by barcode
else
{
    if($shopObj->hasMinus($shop_id))
    {
        $sql_1 = "SELECT * FROM pricehistory
        INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
        INNER JOIN products ON products.PDID = inventory.products_PDID
        LEFT JOIN variations ON variations.VRID = pricehistory.VariationID
        WHERE ItemName LIKE '%".$txt_input."%' AND inventory.shop_SHID = ".$shop_id.";";
    }//has minus
    else
    {
        $sql_1 = "SELECT * FROM pricehistory
        INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
        INNER JOIN products ON products.PDID = inventory.products_PDID
        LEFT JOIN variations ON variations.VRID = pricehistory.VariationID
        WHERE ItemName LIKE '%".$txt_input."%' AND inventory.shop_SHID = ".$shop_id." AND CurrentQty > 0;";
    }//no minus

    $dbData_1 = $dbObj->getData($sql_1);
    if(!empty($dbData_1))
    {
        foreach($dbData_1 as $row)
        {
            $prod_image = $row['ProdImage'];
            $img_path = empty($row['ProdImage']) ? "../Assets/Images/icons/product.png" : "../Assets/Images/prod_images/".$row['ProdImage'];
            echo "<tr>";
            echo "<td style='display:none;'>".$row['PHID']."</td>";
            echo "<td style='display:none;'>".$row['SellingPrice']."</td>";
            echo "<td><img src='".$img_path."' style='height:80px; width:auto;' ></td>";

            echo "<td>".$row['Barcode']." <br>".$row['ItemName']."</td>";

            if($shopObj->hasVariation($shop_id))
            {
                echo "<td>Variation <br> <b>".$row['VariationName']."</b></td>";
            }//has variation
            
            echo "<td>Qty<br><b>".$row['CurrentQty']+0 ."</b></td>";

            echo "<td>Price <br> <b>".$row['SellingPrice']."</b></td>";

            if($shopObj->hasExpiry($shop_id))
            {
                echo "<td>Expire <br> <b>".$row['ExpDate']."</b></td>";
            }//has variation

            echo "</tr>";
        }//foreach
    }//has items
}//search by name