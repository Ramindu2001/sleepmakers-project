<?php
include "../../Includes/config.php";
include "../../Model/add_pay_methods_class.php";

// Get data from AJAX request
$shop_SHID = $_POST['shop_SHID'];
$paymethod_PMID = $_POST['paymethod_PMID'];

$PayObj = new AddPaymentModels();
$PayObj->setPaymentModels($shop_SHID, $paymethod_PMID);

?>