<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$shop_id = $_SESSION['shop_id'];
$user_id = $_SESSION['user_id'];

$date_range = $_GET['date_range'];

$sql = "SELECT SUM(SellQty) AS TotalSaleQty, ItemName, ProductNo FROM invoicedetails 
INNER JOIN invoiceheader ON invoiceheader.IHID = invoicedetails.InvoiceHeader_IHID AND  invoiceheader.InvStat=1 
INNER JOIN products ON products.PDID = invoicedetails.products_PDID
WHERE invoiceheader.shop_SHID = ".$shop_id." group by products_PDID ORDER BY TotalSaleQty DESC LIMIT 5;";

$dbObj = new DBTransactions();

$data = array();

$fastData = $dbObj->getData($sql);

$data = array_reverse($data);

echo json_encode($fastData);