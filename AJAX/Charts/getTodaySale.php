<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$shop_id = $_SESSION['shop_id'];
$user_id = $_SESSION['user_id'];

$dbObj = new DBTransactions();
//get current date time
date_default_timezone_set("Asia/Colombo");
$effective_date = date("Y-m-d");

$sql = "SELECT IHID, BillNo, GrossAmount, NetAmount, InvEndTime FROM invoiceheader WHERE InvStat=1 AND  EffectiveDate = '".$effective_date."';";

$saleData = $dbObj->getData($sql);

$sale_time = array();
$time = array();

foreach($saleData as $row)
{
    $date_time = $row['InvEndTime'];
    $sale = $row['NetAmount'];

    $bill_time = substr($date_time, 11, 2);

    $sale_time[] = [
        'time' => $bill_time,
        'sale' => $sale,
    ];

    $time[] = $bill_time;
    //echo $bill_time . " - ";
}//foreach

$total = array();
$data = array();

foreach($sale_time as $row)
{
    $time = $row['time'];
    $sale = floatval($row['sale']);

    if(isset($total[$time]))
    {
        $total[$time] += $sale;
    }
    else
    {
        $total[$time] = $sale;
    }

}//foreach

foreach ($total as $time => $totalSale) {

    $data[] = array(
        'time' => $time . ":00",
        'sale' => $totalSale,
    );
    //echo "Time: $time, Total Sale: $totalSale\n";
}

echo json_encode($data);