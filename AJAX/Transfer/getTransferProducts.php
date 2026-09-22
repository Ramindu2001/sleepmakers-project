<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";

$shopObj = new Shop();

if($_GET['type'] == 'item_search')
{
    $txt_search = !empty($_GET['search']) ? $_GET['search']: '';
    $shop_id = !empty($_SESSION['shop_id']) ? $_SESSION['shop_id'] : '';

    $sql = "";
            $sql = "SELECT * FROM pricehistory 
            INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
            INNER JOIN products ON products.PDID = inventory.products_PDID
            LEFT JOIN variations ON variations.VRID = pricehistory.VariationID
            WHERE inventory.shop_SHID = ".$shop_id." AND concat(Barcode, ItemName) LIKE '%".$txt_search."%' AND CurrentQty > 0;";
    $dbObj = new DBTransactions();
    $itemData = $dbObj->getData($sql);

    $itemResult = array();

    if(!empty($itemData))
    {
        foreach($itemData as $row)
        {
            $product_id = $row['products_PDID'];
            $variation_id = floatval($row['VariationID']);
            $data = array();

            if($variation_id > 1)
            {
                $data['id'] = $row['PDID'];
                $data['text'] = $row['Barcode'] ." - ". $row['ItemName'] . " - " . $row['VariationName'];
            }
            else
            {
                $data['id'] = $row['PDID'];
                $data['text'] = $row['Barcode'] ." - ". $row['ItemName'];
            }
            array_push($itemResult, $data);
        }//foreach

    }//has items
    echo json_encode($itemResult);
}//has type