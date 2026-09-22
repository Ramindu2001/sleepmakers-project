<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$shop_id = $_SESSION['shop_id'];
$dbObj = new DBTransactions();
$sql = "SELECT * FROM shop
INNER JOIN company ON company.CMID = shop.Company_CMID
WHERE SHID = ".$shop_id.";";
$shopData = $dbObj->getData($sql);
$multi_category = $shopData[0]['is_multicategory'];

if($_GET['type'] == 'item_search')
{
    $txt_search = !empty($_GET['search']) ? $_GET['search']: '';
    $shop_id = isset($_SESSION['shop_id']) ? $_SESSION['shop_id']: '0';
    $sql = "SELECT c.* FROM customers c
                INNER JOIN creditcustomer cc ON c.CTID=cc.Customers_CTID 
                WHERE cc.shop_SHID='$shop_id' AND cc.CreditStat=1 AND concat(c.CustName, c.CustContact) LIKE '%$txt_search%' 
                GROUP by cc.Customers_CTID;";

   // $sql = "SELECT * FROM products WHERE concat(Barcode, ItemName) LIKE '%".$txt_search."%' AND shop_SHID=".$shop_id.";";
    
    $dbObj = new DBTransactions();
    $itemData = $dbObj->getData($sql);

    if(!empty($itemData))
    {
        $itemResult = array();
        foreach($itemData as $row)
        {
            $data['id'] = $row['CTID'];
            $data['text'] = $row['CustName'] ." - ". $row['CustContact'];

            array_push($itemResult, $data);
        }//foreach

    }//has items
    else
    {
        $itemResult = array();
        $data['id'] = "Add";
        $data['text'] = "Add Customer";
        array_push($itemResult, $data);
    }
    echo json_encode($itemResult);
}//has type