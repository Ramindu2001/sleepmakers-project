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

   // $sql = "SELECT * FROM products WHERE concat(Barcode, ItemName) LIKE '%".$txt_search."%' AND shop_SHID=".$shop_id.";";
    $sql = "SELECT * FROM `retrun_invoice_header` WHERE return_no LIKE'%$txt_search%' AND return_header_stat=0 AND shopID=".$shop_id.";";

    if($multi_category==1)
    {
        $com_id=$shopData[0]['CMID'];
        $sql = "SELECT * FROM `retrun_invoice_header` rih
                INNER JOIN shop s ON s.SHID = rih.shopID 
        WHERE rih.return_no LIKE'%$txt_search%' AND rih.return_header_stat=0 AND s.Company_CMID=".$com_id.";";
    }
    
    $dbObj = new DBTransactions();
    $itemData = $dbObj->getData($sql);

    if(!empty($itemData))
    {
        $itemResult = array();
        foreach($itemData as $row)
        {
            $data['id'] = $row['RIHID'];
            $data['text'] = $row['return_no'] ." - ". $row['return_amount'];

            array_push($itemResult, $data);
        }//foreach

    }//has items
    echo json_encode($itemResult);
}//has type