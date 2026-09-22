<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";

$shop_id = $_SESSION['shop_id'];
$shopObj = new Shop();
$dbObj = new DBTransactions();

$txt_search = $_GET['txt_search'];

    if($shopObj->hasMinus($shop_id))
    {
        $sql_1 = "SELECT DISTINCT products_PDID FROM inventory 
        INNER JOIN products ON products.PDID = inventory.products_PDID
        WHERE concat(Barcode, ItemName) LIKE '%".$txt_search."%' AND inventory.shop_SHID = ".$shop_id." LIMIT 100;";
    }
    else
    {
        $sql_1 = "SELECT DISTINCT products_PDID FROM inventory 
        INNER JOIN products ON products.PDID = inventory.products_PDID
        WHERE concat(Barcode, ItemName) LIKE '%".$txt_search."%' AND inventory.shop_SHID = ".$shop_id." AND CurrentQty>0 LIMIT 100;";
    }
    
    $dbObj = new DBTransactions();
    $dbData_1 = $dbObj->getData($sql_1);

    $data = array();
    $output = array();

    foreach($dbData_1 as $row)
    {
        $sql = "SELECT sum(CurrentQty) as totalCurrentQty, sum(BillQty) as totalBillQty, sum(ReturnQty) as totalReturnQty, sum(TransferInQty) as totalTransferIn, sum(TransferOutQty) as totalTransferOut, ProdImage, Barcode, ItemName FROM inventory
        INNER JOIN products ON products.PDID = inventory.products_PDID
        WHERE inventory.products_PDID = ".$row['products_PDID']." AND inventory.shop_SHID = ".$shop_id."  LIMIT 100;";

        $searchData = $dbObj->getData($sql);

        $data = array(
            'Barcode' => $searchData[0]['Barcode'],
            'ItemName' => $searchData[0]['ItemName'],
            'ProdImage' => $searchData[0]['ProdImage'],
            'totalCurrentQty' => $searchData[0]['totalCurrentQty'],
            'totalBillQty' => $searchData[0]['totalBillQty'],
            'totalReturnQty' => $searchData[0]['totalReturnQty'],
            'totalTransferIn' => $searchData[0]['totalTransferIn'],
            'totalTransferOut' => $searchData[0]['totalTransferOut'],
        );

        array_push($output, $data);
    }

echo json_encode($output);