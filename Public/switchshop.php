<?php 
session_start();
include_once "../Includes/config.php";
require_once "../Includes/remember_me.php";

if(isset($_SESSION["shop_id"]))
{

    RememberMe::forgetShop(); //the signed shop cookie and the old one it replaced
    unset($_SESSION["shop_id"]);
}

header("Location: ../Public/dashboard.php")

?>