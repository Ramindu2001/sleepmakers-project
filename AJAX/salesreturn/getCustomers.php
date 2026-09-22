<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$shop_id = $_GET['shop_id'];
$dbObj = new DBTransactions();
$sql = "SELECT * FROM shop
INNER JOIN company ON company.CMID = shop.Company_CMID
WHERE SHID = ".$shop_id.";";
$shopData = $dbObj->getData($sql);
$multi_category = $shopData[0]['is_multicategory'];

if($_GET['type'] == 'item_search')
{
    $txt_search = !empty($_GET['search']) ? $_GET['search']: '';

    $sql = "SELECT * FROM customers WHERE concat(CustName, CustContact) LIKE '%$txt_search%' AND shop_SHID='$shop_id';";

    if($multi_category==1)
    {
        $com_id=$shopData[0]['CMID'];
        $sql = "SELECT * FROM customers c
                INNER JOIN shop s ON s.SHID = c.shop_SHID
                WHERE concat(c.CustName, c.CustContact) LIKE '%$txt_search%' AND s.Company_CMID='$com_id';";
    }
    
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
        
        $sql = "SELECT * FROM customers WHERE CTID=1;";
        $dbObj = new DBTransactions();
        $itemData = $dbObj->getData($sql);
        $itemResult = array();
        foreach($itemData as $row)
        {
            $data['id'] = $row['CTID'];
            $data['text'] = $row['CustName'] ." - ". $row['CustContact'];

            array_push($itemResult, $data);
        }//foreach
    }
    echo json_encode($itemResult);
}//has type