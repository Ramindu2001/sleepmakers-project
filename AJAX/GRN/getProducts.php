<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$shop_id = $_SESSION['shop_id'];
$dbObj = new DBTransactions();

//get company stat
$sql = "SELECT * FROM shop
INNER JOIN company ON company.CMID = shop.Company_CMID
WHERE SHID = ".$shop_id.";";

$shopData = $dbObj->getData($sql);
$multi_category = floatval($shopData[0]['is_multicategory']);
$company_id = floatval($shopData[0]['CMID']);

if($_GET['type'] == 'item_search')
{
    $txt_search = !empty($_GET['search']) ? $_GET['search']: '';
    $shop_id = isset($_SESSION['shop_id']) ? $_SESSION['shop_id']: '0';

    if($multi_category == 1)
    {
        $sql = "SELECT * FROM products 
        INNER JOIN shop ON shop.SHID = products.shop_SHID
        WHERE concat(Barcode, ItemName) LIKE '%".$txt_search."%' AND products.ItemType='P' AND shop.Company_CMID = ".$company_id.";";
    }//has multi category
    else
    {
        $sql = "SELECT * FROM products WHERE concat(Barcode, ItemName) LIKE '%".$txt_search."%' AND products.ItemType='P' AND shop_SHID=".$shop_id.";";
    }//no multi category
   
    $itemData = $dbObj->getData($sql);

    if(!empty($itemData))
    {
        $itemResult = array();
        foreach($itemData as $row)
        {
            $data['id'] = $row['PDID'];
            $data['text'] = $row['Barcode'] ." - ". $row['ItemName'];

            array_push($itemResult, $data);
        }//foreach

    }//has items
    echo json_encode($itemResult);
}//has type