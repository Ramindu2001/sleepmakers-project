<?php 
include "../../Includes/config.php";
include "../../Model/supplier_return_class.php";

$srn_detail_id = $_GET['srn_detail_id'];

$srnObj = new SupplierReturn();
$srnObj->deleteSRNDetail($srn_detail_id);