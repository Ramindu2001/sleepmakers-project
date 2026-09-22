<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$shop_id = $_SESSION['shop_id'];

if($_GET['type'] == 'item_search')
{
    $txt_search = !empty($_GET['search']) ? $_GET['search']: '';
    $shop_id = isset($_SESSION['shop_id']) ? $_SESSION['shop_id']: '0';

   // $sql = "SELECT * FROM products WHERE concat(Barcode, ItemName) LIKE '%".$txt_search."%' AND shop_SHID=".$shop_id.";";
    $sql = "SELECT * FROM salesmans WHERE concat(SalesmansName, SalesmansContact,SalesmanNo) LIKE '%".$txt_search."%' AND shop_SHID=".$shop_id.";";
    
    $dbObj = new DBTransactions();
    $itemData = $dbObj->getData($sql);

    if(!empty($itemData))
    {
        $itemResult = array();
        foreach($itemData as $row)
        {
            $data['id'] = $row['SLID'];
            $data['text'] = $row["SalesmanNo"]." - ".$row['SalesmansName'] ." - ". $row['SalesmansContact'];

            array_push($itemResult, $data);
        }//foreach

    }//has items
    echo json_encode($itemResult);
}//has type