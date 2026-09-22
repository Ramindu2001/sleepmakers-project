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

    $sql = "SELECT * FROM invoiceheader 
            WHERE (BillNo LIKE '%$txt_search%' OR InvoiceNo LIKE '%$txt_search%' 
                OR ('$txt_search' LIKE '%gui%' AND Inv_Type = 2) 
                OR ('$txt_search' LIKE '%wholesale%' AND Inv_Type = 1))
            AND shop_SHID = '$shop_id' ORDER BY IHID DESC;";
    $dbObj = new DBTransactions();
    $itemData = $dbObj->getData($sql);

    if(!empty($itemData))
    {
        $itemResult = array();
        foreach($itemData as $row)
        {
            if($row["Inv_Type"]==1)
            {
                $type="Wholesale Invoice";
            }
            else
            {
                $type="GUI POS Invoice";
            }
            $data['id'] = $row['IHID'];
            $data['text'] = $row['BillNo'] ." - ". $type;

            array_push($itemResult, $data);
        }//foreach

    }//has items
    else
    {
        
    }
    echo json_encode($itemResult);
}//has type