<?php 
include "../../Includes/config.php";
include "../../Model/adjust_class.php";

$adjust_detail_id = $_GET['adjust_detail_id'];

$adjustObj = new Adjustment();
$adjustObj->deleteAdjustDetail($adjust_detail_id);

echo "adjust detail item deleted successfully.";