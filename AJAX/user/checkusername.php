<?php 
include "../../Includes/config.php";
include "../../Model/user_class.php";

$username = $_POST['variation_name'];

$userObj = new User();
$user=$userObj->getUsername($username);
if($user==1)
{
    //good 
    echo "";
}
else 
{
    // false
}
