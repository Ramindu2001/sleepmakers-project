<?php  
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include "../Includes/includes.php";
$shopObjAccess = new ShopAccess();
$designation = 0;
$shop_id = 0;
$user_name = "";
$login=1;
/*
Login 1 -> Active 
login 0 -> User Inactive
login -1 -> shop Inactive

*/
if(isset($_SESSION['user_id']))
{
    $user_id = $_SESSION['user_id'];
    $userObj = new User();
    $shopObj = new Shop();
    $user = $userObj->getOneUser($user_id);
    if($user[0]['UserType']!=1)
    {
        if($user[0]['UserStat']==1)
        {
            if(isset($_SESSION['shop_id']))
            {
                $shop=$shopObj->getOneShop($_SESSION['shop_id']);
                if($shop[0]["ShopStat"]==1)
                {
                    $company=$shopObj->getCompanyONE($shop[0]["Company_CMID"]);
                    if($company[0]["ComStat"]==1)
                    {
                        $today=date("Y-m-d");
                        if($today < $company[0]["ComExpireDate"])
                        {
                            if($company[0]["ComStat"]==1)
                            {
                                
                            }
                            else
                            {
                                $login=-1;
                            }
                        }
                        else
                        {
                            $login=-1;
                        }
                    }
                    else
                    {
                        $login=-1;
                    }
                }
                else
                {
                    $login=-1;
                }
            }
            else
            {
                $login=-1;
            }
        }
        else
        {
            $login=0;
        } 
    }
    else
    {
        if(isset($_SESSION['shop_id']))
        {
            $login=1;
        }
        else
        {
            $login=-1;
        }
    }
    
    

}
else
{
    $login=0;
}
if($login==1 && isset($_SESSION['shop_id']) && !$shopObjAccess->canAccessShop($_SESSION['user_id'], $_SESSION['shop_id']))
{
    //access to this shop revoked since it was entered: the page goes to switchshop.php
    $_SESSION['shop_access_error'] = "Your access to this shop has been removed.";
    $login=-1;
}//still allowed in this shop
echo $login;
?>