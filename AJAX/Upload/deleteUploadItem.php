<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$dbObj = new DBTransactions();

$upload_id = $_GET['upload_id'];

$query = "DELETE FROM `temp_grnupload` WHERE upload_id = ".$upload_id.";";

$dbObj->executeTransaction($query);

echo "row deleted successfully...";