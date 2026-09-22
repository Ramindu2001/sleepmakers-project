<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$shop_id = $_SESSION['shop_id'];
$date_range = $_GET['date_range'];

$sql = "SELECT DISTINCT EffectiveDate FROM invoiceheader WHERE InvStat=1 AND shop_SHID=".$shop_id." ORDER BY EffectiveDate DESC LIMIT ".$date_range.";";

$dbObj = new DBTransactions();

$data = array(); 

$daysData = $dbObj->getData($sql);
foreach($daysData as $day)
{
    $sql_1 = "SELECT SUM(NetAmount) AS GrossSale, EffectiveDate FROM invoiceheader WHERE InvStat=1 AND EffectiveDate = '".$day['EffectiveDate']."' AND shop_SHID=".$shop_id.";";

    $saleData = $dbObj->getData($sql_1);

    foreach($saleData as $sale)
    {
        $data[] = $sale;
    }//foreach -2
}//foreach -1

$data = array_reverse($data);

echo json_encode($data);