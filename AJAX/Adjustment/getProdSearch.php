<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$dbObj = new DBTransactions();
if($_GET['type'] == 'item_search')
{
    $txt_search = !empty($_GET['search']) ? $_GET['search']: '';
    $adjust_type = !empty($_GET['adjustType']) ? $_GET['adjustType'] : '';
    $shop_id = !empty($_SESSION['shop_id']) ? $_SESSION['shop_id'] : '';

    $dbObj = new DBTransactions();
    $itemResult = array();

    if($adjust_type == 1)
    {
        $sql = "";
        $sql = "SELECT * FROM products WHERE concat(Barcode, ItemName) LIKE '%".$txt_search."%' AND ItemType='p' AND shop_SHID=".$shop_id.";";
        
        $itemData = $dbObj->getData($sql);

        if(!empty($itemData))
        {
            foreach($itemData as $row)
            {
                $data['id'] = $row['PDID'];
                $data['text'] = $row['Barcode'] ." - ". $row['ItemName'];

                array_push($itemResult, $data);
            }//foreach
        }//has items

        echo json_encode($itemResult);
    }//adjustment IN
    else
    {
        $sql = "SELECT * FROM pricehistory
        INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
        INNER JOIN products ON products.PDID = pricehistory.ProductID
        WHERE inventory.shop_SHID = ".$shop_id." AND concat(Barcode, ItemName) LIKE '%".$txt_search."%' AND ItemType='p';";

        $itemData = $dbObj->getData($sql);

        if(!empty($itemData))
        {
            foreach($itemData as $row)
            {
                $data['id'] = $row['PDID'];
                $data['text'] = $row['Barcode'] ." - ". $row['ItemName'] ." - ". $row['SellingPrice'];

                array_push($itemResult, $data);
            }//foreach
        }//has items

        echo json_encode($itemResult);
    }//Adjustment OUT

}//has type
