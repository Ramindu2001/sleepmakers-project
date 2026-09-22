<?php 
session_start();
include_once "../Includes/config.php";
require_once "../Includes/remember_me.php";
unset($_SESSION);
session_destroy();
RememberMe::forget(); //the signed remember-me cookies and the old ones they replaced
setcookie("remember_meS", "", time() - 3600, "/");
header("Location:./login.php");
?>