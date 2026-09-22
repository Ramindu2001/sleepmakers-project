<?php
include "../../Includes/config.php";
include "../../Model/add_users_to_shops_class.php";

// Get data from AJAX request
$shop_SHID = $_POST['shop_SHID'];
$user_USID = $_POST['user_USID'];

$ShopObj = new AddUsersModels();
$ShopObj->setUserModels($shop_SHID, $user_USID);

?>