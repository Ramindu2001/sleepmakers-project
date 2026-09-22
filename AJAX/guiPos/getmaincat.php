<?php
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
$shop_id = $_SESSION['shop_id'];
$supplier_id = $_GET['supplier_id'];
$dbObj = new DBTransactions();
//shop data
$sql="SELECT * FROM shop WHERE SHID='$shop_id'";
$shopdata = $dbObj->getData($sql);

//company data
$company_id=$shopdata[0]["Company_CMID"];
$sql="SELECT * FROM company WHERE CMID='$company_id' ";
$companydata = $dbObj->getData($sql);
if($companydata[0]["is_multicategory"]==1)
{
    $sql = "SELECT * FROM categories c 
    INNER JOIN shop s ON s.SHID=c.shop_SHID
    WHERE s.Company_CMID = '$company_id';";
    $catData = $dbObj->getData($sql);
}
else
{
    $sql = "SELECT * FROM `categories` WHERE shop_SHID = '$shop_id';";
    $catData = $dbObj->getData($sql); 
}


echo json_encode($catData);
