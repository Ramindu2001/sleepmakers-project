<?php  
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/remember_me.php';
$designation = 0;
$shop_id = 0;
$user_name = "";

if(isset($_SESSION['user_id']))
{
    $user_id = $_SESSION['user_id'];
    $userObj = new User();
    $user = $userObj->getOneUser($user_id);
    $user_name = $user[0]['UserName'];
    $UserProfile = $user[0]['UserProfile'];
    $UserContactNo = $user[0]['ContactNo'];
    $UserEmail = $user[0]['UserEmail'];
}//user logged in
else if(($remembered_user_id = (new RememberMe())->userFromCookie()) !== null)
{
    //a signed remember-me cookie (Includes/remember_me.php) - a fresh session id for the login
    if(session_status() === PHP_SESSION_ACTIVE && !headers_sent())
    {
        session_regenerate_id(true);
    }
    $_SESSION['user_id'] = $remembered_user_id;
    $user_id = $_SESSION['user_id'];
    $userObj = new User();
    $user = $userObj->getOneUser($user_id);
    $user_name = $user[0]['UserName'];
    $UserProfile = $user[0]['UserProfile'];
    $UserContactNo = $user[0]['ContactNo'];
    $UserEmail = $user[0]['UserEmail'];

}
else
{
    header("Location: ../Public/login.php");
    exit; //without this the rest of the page still ran for a visitor who is not logged in
}//force to log in

if(isset($_SESSION['shop_id']))
{
    $shop_id = $_SESSION['shop_id'];
}//if shop set
else if(($remembered_shop_id = (new RememberMe())->shopFromCookie($_SESSION['user_id'])) !== null)
{
    $_SESSION['shop_id'] = $remembered_shop_id;
    $shop_id = $_SESSION['shop_id'];
}
else
{
    header("Location: ../Public/dashboard.php");
    exit;
}//else goto dashboard
?>