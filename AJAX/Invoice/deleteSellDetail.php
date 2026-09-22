<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/invoice_class.php";

$selldetail_id = $_GET['selldetail_id'];

$invObj = new Invoice();

$invObj->deleteSaleDetail($selldetail_id);
