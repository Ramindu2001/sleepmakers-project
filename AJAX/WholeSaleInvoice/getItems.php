<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";
include '../../Model/wholesale_invoice_class.php';
$shopObj=new Shop();

$shop_id = $_SESSION['shop_id'];
$hasMinus=$shopObj->hasMinus($shop_id);
$hasbatchNo=$shopObj->hasbatchNo($shop_id);
$wholesale_invoice= new wholesale_invoice();
if($_GET['type'] == 'item_search')
{
    $txt_search = !empty($_GET['search']) ? $_GET['search']: '';
    $shop_id = $_SESSION['shop_id'];
    $dbObj = new DBTransactions();
    //get company stat
    $sql = "SELECT * FROM shop
    INNER JOIN company ON company.CMID = shop.Company_CMID
    WHERE SHID = ".$shop_id.";";
    $shopData=$dbObj->getData($sql);
    $common_stock = floatval($shopData[0]['is_commonStock']);
    $multi = floatval($shopData[0]['is_multicategory']);
    if($hasMinus==1)
    {
        $sql = "SELECT * FROM products p
        INNER JOIN inventory i ON i.products_PDID=p.PDID
        WHERE (p.Barcode LIKE '%$txt_search%' OR p.ItemName LIKE '%$txt_search%'  OR p.ProductNo LIKE '%$txt_search%')  AND i.shop_SHID='$shop_id' AND p.ProductStat = 1 GROUP BY p.PDID;";
        if($common_stock==1 || $multi==1)
        {
            $company=$shopData[0]['CMID'];
            $sql = "SELECT * FROM products p
            INNER JOIN inventory i ON i.products_PDID=p.PDID
            INNER JOIN shop s ON s.SHID = i.shop_SHID 
            WHERE (p.Barcode LIKE '%$txt_search%' OR p.ItemName LIKE '%$txt_search%'  OR p.ProductNo LIKE '%$txt_search%')  AND s.Company_CMID='$company' AND p.ProductStat = 1 GROUP BY p.PDID;";  
        }   
    }
    else
    {
        $sql = "SELECT * FROM products p
        INNER JOIN inventory i ON i.products_PDID=p.PDID
        WHERE (p.Barcode LIKE '%$txt_search%' OR p.ItemName LIKE '%$txt_search%'  OR p.ProductNo LIKE '%$txt_search%')  AND i.shop_SHID='$shop_id' AND i.CurrentQty > 0 AND p.ProductStat = 1 GROUP BY p.PDID;";
        if($common_stock==1 || $multi==1)
        {
            $company=$shopData[0]['CMID'];
            $sql = "SELECT * FROM products p
            INNER JOIN inventory i ON i.products_PDID=p.PDID
            INNER JOIN shop s ON s.SHID = i.shop_SHID 
            WHERE (p.Barcode LIKE '%$txt_search%' OR p.ItemName LIKE '%$txt_search%'  OR p.ProductNo LIKE '%$txt_search%')  AND s.Company_CMID='$company' AND i.CurrentQty > 0 AND p.ProductStat = 1 GROUP BY p.PDID;";    
        }
    }
    $dbObj = new DBTransactions();
    $itemData = $dbObj->getData($sql);

    if(!empty($itemData))
    {
        $itemResult = array();
        foreach($itemData as $row)
        {
            $data['id'] = $row['PDID'];
            $data['text'] = $row['Barcode'];
            $data['text'] .= " - ".$row['ItemName'];
            if($row['UnitConversion']==0.000)
            {
                $row['UnitConversion']=1.000;
            }
            if($hasbatchNo==0 && $common_stock==1)
            {
                $qty=$wholesale_invoice->getAvlQty($row['PDID'],$shop_id);
                $avlQty = $qty[0]['avlQty'] * $row['UnitConversion'];
                $data['text'] .= " - ".$avlQty;
            }
            array_push($itemResult, $data);
        }//foreach

    }//has items
    echo json_encode($itemResult);
}//has type