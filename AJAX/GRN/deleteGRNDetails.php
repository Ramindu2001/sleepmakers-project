<?php 
include "../../Includes/config.php";
include "../../Model/GRN_class.php";

$grn_detail_id = $_GET['grn_detail_id'];

$grnObj = new GRN();
$grnObj->deleteGRNDetails($grn_detail_id);