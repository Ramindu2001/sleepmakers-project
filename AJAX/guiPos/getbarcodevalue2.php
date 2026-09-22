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
$is_commonStock = $shopData[0]['is_commonStock'];
$stockType = $shopData[0]['StockTypes_STID'];
$is_minus = $shopdata[0]["is_minus"] ?? false;
$com_id=$shopData[0]['CMID'];
$barcodevalue=$_GET['barcodevalue'];
if (!empty($barcodevalue))
{
    $sql="SELECT * FROM products p
    
    WHERE p.Barcode='$barcodevalue' AND p.shop_SHID='$shop_id'";
    if($multi_category==1)
    {
        $sql="SELECT p.* FROM products p
        INNER JOIN shop s ON s.SHID=p.shop_SHID
        WHERE p.Barcode='$barcodevalue' AND s.Company_CMID='$com_id'";
    }
    $dbObj = new DBTransactions();
    $itemData = $dbObj->getData($sql);
    $i=0;
    $data=[];
    foreach($itemData AS $row)
    {
        $product_id=$row["PDID"];
        $inventoryData=fetchInventoryData($product_id, $shop_id, $com_id, $is_commonStock, $stockType, false);
        if (!$inventoryData && $is_minus) {
            $inventoryData = fetchInventoryData($product_id, $com_id, $company_id, $is_commonStock, $stockType, true);
        }
        if(count($inventoryData) > 0)
        {
            $data[$i]=$row;
        }
        $i++;
    }
    
    echo json_encode($data);
}

function fetchInventoryData($product_id, $shop_id, $company_id, $is_commonStock, $stockType, $is_default) {
    global $dbObj;
    
    $condition = $is_default ? "AND i.is_default=1" : "AND i.CurrentQty > 0";
    $order = ($stockType == 3) ? "DESC" : "ASC"; // LIFO vs FIFO
    
    $sql = "SELECT i.INID, SUM(i.CurrentQty) AS TotalCurrentQty, ph.SellingPrice AS SellingPrice FROM `inventory` i
            INNER JOIN pricehistory ph ON ph.Inventory_INID=i.INID ";
    
    if ($is_commonStock == 1) {
        $sql .= "INNER JOIN shop s ON s.SHID=i.shop_SHID WHERE i.products_PDID='$product_id' AND s.Company_CMID='$company_id' $condition ";
    } else {
        $sql .= "WHERE i.products_PDID='$product_id' AND i.shop_SHID='$shop_id' $condition ";
    }
    
    $sql .= "GROUP BY ph.SellingPrice ORDER BY i.INID $order ";
    return $dbObj->getData($sql); // Return all records
}
