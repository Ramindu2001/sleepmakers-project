<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$shop_id = $_SESSION['shop_id'];

if($_GET['type'] == 'item_search')
{
    $txt_search = !empty($_GET['search']) ? $_GET['search']: '';
    $shop_id = isset($_SESSION['shop_id']) ? $_SESSION['shop_id']: '0';

    $sql = "SELECT * FROM customers WHERE concat(CustName, CustContact) LIKE '%".$txt_search."%'AND shop_SHID=".$shop_id.";";
    
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
    echo json_encode($itemResult);
}//has type