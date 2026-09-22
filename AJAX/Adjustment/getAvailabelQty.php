<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";

$shop_id = $_SESSION['shop_id'];
$adjust_header_id = $_GET['adjust_header_id'];
$price_history_id = $_GET['price_history_id'];

//iterate through details table
$sql = "SELECT * FROM adjustproddetails WHERE AdjustHeader_AHID = ".$adjust_header_id.";";

$shopObj = new Shop();
$dbObj = new DBTransactions();
$adjustData = $dbObj->getData($sql);

$duplicate = false;
$adjust_out_qty = 0;
$sql_1 = "SELECT * FROM pricehistory 
    INNER JOIN products ON products.PDID = pricehistory.ProductID
    LEFT JOIN variations ON variations.VRID = pricehistory.VariationID
    WHERE PHID = ".$price_history_id.";";

    $phData = $dbObj->getData($sql_1);
    $inventory_id = $phData[0]['Inventory_INID'];

foreach($adjustData as $row)
{
    $product_id = $row['products_PDID'];
    $purchase_price = $row['UnitPurchasePrice'];
    $selling_price = $row['UnitSellingPrice'];
    $mnf_date = $row['MnfDate'];
    $exp_date = $row['ExpDate'];
    $variation_id = $row['VariationID'];
    $adjust_out_qty = floatval($row['AdjustProdQty']);

    $sql_1 = "SELECT * FROM pricehistory 
    INNER JOIN products ON products.PDID = pricehistory.ProductID
    LEFT JOIN variations ON variations.VRID = pricehistory.VariationID
    WHERE PHID = ".$price_history_id.";";

    $phData = $dbObj->getData($sql_1);
    $inventory_id = $phData[0]['Inventory_INID'];
    $barcode = $phData[0]['Barcode'];
    $item_name = $phData[0]['ItemName'];

    $item_purchase_price = $phData[0]['PurchasePrice'];
    $item_selling_price = $phData[0]['SellingPrice'];

    $item_mnf_date = $phData[0]['MnfDate'];
    $item_exp_date = $phData[0]['ExpDate'];

    $item_variation = $phData[0]['VariationName'];

    if($product_id == $phData[0]['ProductID'])
    {
        if($purchase_price == $phData[0]['PurchasePrice'] OR $selling_price == $phData[0]['SellingPrice'])
        {
            //check shop has expiry
            if($shopObj->hasExpiry($shop_id))
            {
                if($mnf_date == $phData[0]['MnfDate'] OR $exp_date == $phData[0]['ExpDate'])
                {
                    $duplicate = true;
                }//expire date match
                else
                {   
                    $duplicate = false;
                }//no expiry match
            }//has expiry

            //check shop has variation
            if($shopObj->hasVariation($shop_id))
            {
                if($variation_id == $phData[0]['VariationID'])
                {
                    $duplicate = true;
                }//expire date match
                else
                {
                    $duplicate = false;
                }//no expiry match
            }//has variation

        }//price match
        else
        {
            $duplicate = false;
        }//no price match
    }//check product
    else
    {
        $duplicate = false;
    }//no product match
}//foreach
    $inventory_id = $phData[0]['Inventory_INID'];
    $barcode = $phData[0]['Barcode'];
    $item_name = $phData[0]['ItemName'];

    $item_purchase_price = $phData[0]['PurchasePrice'];
    $item_selling_price = $phData[0]['SellingPrice'];

    $item_mnf_date = $phData[0]['MnfDate'];
    $item_exp_date = $phData[0]['ExpDate'];

    $item_variation = $phData[0]['VariationName'];
    $sql_2 = "SELECT * FROM inventory WHERE INID = ".$inventory_id.";";
    $invData = $dbObj->getData($sql_2);
    $current_qty = floatval($invData[0]['CurrentQty']);
    

if($duplicate)
{
    $availabel_qty = $current_qty - $adjust_out_qty;
    
}//has another row in detail table
else
{
    $availabel_qty = $current_qty;
}
    echo "<div class='row'>";

    echo "<div class='col-6'>";
    echo "<p>";
    echo "Barcode: <b>" . $barcode . "</b><br>";
    echo "Item Name: <b>" . $item_name . "</b><br>";
    echo "Qty: <b>" . $availabel_qty . "</b><br>";
    echo "</p>";
    echo "</div>";

    echo "<div class='col-6'>";
    echo "<p>";
    echo "Purchase Price: <b>" . $item_purchase_price . "</b><br>";
    echo "Selling Price: <b>" . $item_selling_price . "</b><br>";

    //check shop has expiry
    if($shopObj->hasExpiry($shop_id))
    {
        echo "Mnf Date: <b>" . $item_mnf_date . "</b><br>";
        echo "Exp Date: <b>" . $item_exp_date . "</b><br>";
    }//has expire

    //check shop has variation
    if($shopObj->hasVariation($shop_id))
    {
        echo "Variation: <b>" . $item_variation . "</b><br>";
    }//has variation

    echo "</p>";
    echo "</div>";

    echo "</div>";

    //hidden input
    echo "<input type='hidden' id='hide_available_qty' value='". $availabel_qty ."'>";
