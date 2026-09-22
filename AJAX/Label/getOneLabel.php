<?php
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$label_id = $_GET['label_id'];

$sql = "SELECT * FROM `label`
INNER JOIN shop ON shop.SHID = label.shop_id 
WHERE LBID = ".$label_id.";";

$dbObj = new DBTransactions();
$prodData = $dbObj->getData($sql);

if(!empty($prodData))
{
    echo json_encode($prodData);
}//has data
