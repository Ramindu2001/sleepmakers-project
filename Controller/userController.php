<?php
include "../Includes/includes.php";
require_once "../Includes/remember_me.php";
$userObj = new User();
$comObj = new Company();

//adding, editing, (de)activating users and resetting their passwords is for the system admin
//only, from our own pages - decided before anything below runs (Includes/super_admin.php)
require_once "../Includes/super_admin.php";
if(array_intersect(['add-user', 'edit-user', 'delete-user', 'activate-user', 'chng-pwd'], array_keys($_POST)))
{
    super_admin_request();
    if(!posted_csrf_valid())
    {
        $_SESSION['user_error'] = 11;
        header("Location: ../Public/users.php");
        exit;
    }//stale or forged form
}//user management

if(isset($_POST['add-user']))
{
    //fetch data
    if (!empty($_POST['username'])) 
    {
        if (!empty($_POST['userEmail'])) 
        {
            if (!empty($_POST['userContact'])) 
            {
                if (!empty($_POST['password'])) 
                {
                    if (!empty($_POST['userRole'])) 
                    {
                        $username=$_POST['username'];
                        $userEmail=$_POST['userEmail'];
                        $userContact=$_POST['userContact'];
                        $password=$_POST['password'];
                        $userRole=$_POST['userRole'];
                        if(isset($_POST["paylimit"]))
                        {
                            $paylimit=$_POST["paylimit"];
                        }
                        else
                        {
                            $paylimit=0;
                        }
                        $hash = password_hash($password,PASSWORD_DEFAULT);
                        $target_dir = "../Assets/Images/user_profile/";
                        if(isset($_POST["superadmin"]))
                        {
                            $type=1;
                        }
                        else
                        {
                            $type=0;
                        }
                        if (!empty($_FILES['propic']['name'])) 
                        {
                            $profile_image_name = "up_" . basename($_FILES['propic']['name']);

                            $target_file_path = $target_dir . $profile_image_name;
                            $file_type = pathinfo($target_file_path, PATHINFO_EXTENSION);
                    
                            // Allow certain file formats 
                            $allow_types = array('jpg','png','PNG','jpeg');
                            if(in_array($file_type, $allow_types))
                            {
                                if(move_uploaded_file($_FILES["propic"]["tmp_name"], $target_file_path))
                                {
                                    $propic=$profile_image_name;
                                }//move file
                                else
                                {
                                    $_SESSION['user_error']=5;//no username
                                    header("Location: ../Public/users.php");

                                }
                            }//check file type
                            else
                            {
                                $_SESSION['user_error']=5;//no username
                                header("Location: ../Public/users.php");
                            }//wrong file type
                            
                        }
                        else
                        {
                            $propic="avator.svg";
                        }
                        $add_users=$userObj->setUser($propic, $username, $userEmail, $userContact, $hash, $userRole,$type,$paylimit);
                        if ($add_users > 0) 
                        {
                            if(isset($_SESSION['shop_id']) && !empty($_SESSION['shop_id'])) {
                                $shopUserObj = new AddUsersModels();
                                //joins the shop they were created from, with their default role
                                $shopUserObj->assignUser($_SESSION['shop_id'], $add_users, $userRole);
                            }
                            $_SESSION['user_error']=1;   
                            header("Location: ../Public/users.php");
                        }
                        else
                        {
                            $_SESSION['user_error']=2;   
                            header("Location: ../Public/users.php");

                        }        

                    }
                    else
                    {
                        $_SESSION['user_error']=5;//no username
                        header("Location: ../Public/users.php");

                    }
                }
                else
                {
                    $_SESSION['user_error']=5;//no username
                    header("Location: ../Public/users.php");
                }
            }
            else
            {
                $_SESSION['user_error']=5;//no username
                header("Location: ../Public/users.php");
            }
            
        }
        else
        {
            $_SESSION['user_error']=5;//no username
            header("Location: ../Public/users.php");
        }
    }
    else
    {
        $_SESSION['user_error']=5;//no username
        header("Location: ../Public/users.php");
        
    }
    //uplaod profile image
    //file upload directry
    
}//save user
elseif (isset($_POST['edit-user'])) 
{
    if (!empty($_POST['username'])) 
    {
        if (!empty($_POST['userEmail'])) 
        {
            if (!empty($_POST['userContact'])) 
            {
                if (!empty($_POST['userRole'])) 
                {
                    $uid=$_POST['euserid'];
                    $username=$_POST['username'];
                    $userEmail=$_POST['userEmail'];
                    $userContact=$_POST['userContact'];
                    $userRole=$_POST['userRole'];
                    if(isset($_POST["status"]))
                    {
                        $status=1;
                    }
                    else
                    {
                        $status=0;
                    }
                    if(isset($_POST['epaylimit']))
                    {
                        $epaylimit=$_POST['epaylimit'];
                    }
                    else
                    {
                        $epaylimit=0;
                    }
                    $target_dir = "../Assets/Images/user_profile/";
                    if (!empty($_FILES['epropic']['name'])) 
                    {
                        $profile_image_name = "up_" . basename($_FILES['epropic']['name']);

                        $target_file_path = $target_dir . $profile_image_name;
                        $file_type = pathinfo($target_file_path, PATHINFO_EXTENSION);
                
                        // Allow certain file formats 
                        $allow_types = array('jpg','png','PNG','jpeg');
                        if(in_array($file_type, $allow_types))
                        {
                            if(move_uploaded_file($_FILES["epropic"]["tmp_name"], $target_file_path))
                            {
                                $eprofile=$profile_image_name;
                            }//move file
                            else
                            {
                                $_SESSION['user_error']=5;//no username
                                header("Location: ../Public/users.php");

                            }
                        }//check file type
                        else
                        {
                            $_SESSION['user_error']=5;//no username
                            header("Location: ../Public/users.php");
                        }//wrong file type
                        
                    }
                    else
                    {
                        $eprofile=$_POST['eprofile'];
                    }
                    echo $uid."<br>";
                    echo $username."<br>";
                    echo $userEmail."<br>";
                    echo $userContact."<br>";
                    echo $userRole."<br>";
                    echo $eprofile."<br>";
                    $user_updat=$userObj->edit_user($uid,$username,$userEmail,$userContact,$userRole,$eprofile,$epaylimit,$status);
                    print_r($user_updat);
                    if($user_updat==0)
                    {
                        $_SESSION['user_error']=2;//no username
                        header("Location: ../Public/users.php");
                    }
                    else
                    {
                        $_SESSION['user_error']=3;//no username
                        header("Location: ../Public/users.php");
                    }
                }
                else
                {
                    $_SESSION['user_error']=5;//no username
                    header("Location: ../Public/users.php");
            
                }
            }
            else
            {
                $_SESSION['user_error']=5;//no username
                header("Location: ../Public/users.php");
        
            }
        }
        else
        {
            $_SESSION['user_error']=5;//no username
            header("Location: ../Public/users.php");
    
        }
    }
    else
    {
        $_SESSION['user_error']=5;//no username
        header("Location: ../Public/users.php");

    }
}
else if(isset($_POST['delete-user']))
{
    if (!empty($_POST['delete_user_id']))
    {
        $delete_uid = $_POST['delete_user_id'];
        // Prevent deleting yourself
        if ($delete_uid == $_SESSION['user_id'])
        {
            $_SESSION['user_error'] = 5;
            header("Location: ../Public/users.php");
        }
        else
        {
            $result = $userObj->delete_user($delete_uid);
            if ($result == 1)
            {
                $_SESSION['user_error'] = 6;
                header("Location: ../Public/users.php");
            }
            else
            {
                $_SESSION['user_error'] = 5;
                header("Location: ../Public/users.php");
            }
        }
    }
    else
    {
        $_SESSION['user_error'] = 5;
        header("Location: ../Public/users.php");
    }
}
else if(isset($_POST['activate-user']))
{
    if (!empty($_POST['activate_user_id']))
    {
        $activate_uid = $_POST['activate_user_id'];
        
        $result = $userObj->activate_user($activate_uid);
        if ($result == 1)
        {
            $_SESSION['user_error'] = 7; // Custom error code for activation
            header("Location: ../Public/users.php");
        }
        else
        {
            $_SESSION['user_error'] = 5;
            header("Location: ../Public/users.php");
        }
    }
    else
    {
        $_SESSION['user_error'] = 5;
        header("Location: ../Public/users.php");
    }
}
else if(isset($_POST['chng-pwd']))
{
    if(!empty($_POST['euid']))
    {
        if (!empty($_POST['epassword'])) 
        {
            if (!empty($_POST['e-cpassword'])) 
            {
                $eudi=$_POST['euid'];
                $epassword=$_POST['epassword'];
                $ecpassword=$_POST['e-cpassword'];
                if ($epassword==$ecpassword)
                {
                    
                    $password = password_hash($epassword,PASSWORD_DEFAULT);
                    $pwd_chang=$userObj->change_password($password,$eudi);
                    if ($pwd_chang==1) 
                    {
                        $_SESSION['user_error']=4;//no username
                        header("Location: ../Public/users.php");
                        
                    }
                    else
                    {
                        $_SESSION['user_error']=5;//no username
                        header("Location: ../Public/users.php");
                    }
                }
                else
                {
                    $_SESSION['user_error']=5;//no username
                    header("Location: ../Public/users.php");
                }
            }
            else
            {
                $_SESSION['user_error']=5;//no username
                header("Location: ../Public/users.php");
            }            
        }
        else
        {
            $_SESSION['user_error']=5;//no username
            header("Location: ../Public/users.php");
        }
    }
    else
    {
        $_SESSION['user_error']=5;//no username
        header("Location: ../Public/users.php");
    }
}
else if(isset($_POST['uchng-pwd']))
{
    //a user changes their own password (profile menu): always the signed-in user's own account,
    //never an id taken from the form, and only with the current password
    if(!isset($_SESSION['user_id']))
    {
        header("Location: ../Public/login.php");
        exit;
    }//not signed in

    $me = $userObj->getOneUser($_SESSION['user_id']);
    $new_password = isset($_POST['uepassword']) && is_string($_POST['uepassword']) ? $_POST['uepassword'] : '';
    $confirm_password = isset($_POST['ue_cpassword']) && is_string($_POST['ue_cpassword']) ? $_POST['ue_cpassword'] : '';
    $current_password = isset($_POST['ue_current_password']) && is_string($_POST['ue_current_password']) ? $_POST['ue_current_password'] : '';

    if(!posted_csrf_valid())
    {
        $_SESSION['user_error'] = 11;
    }//stale or forged form
    elseif(empty($me) || !password_verify($current_password, $me[0]['UserPwd']))
    {
        $_SESSION['user_error'] = 12;
    }//wrong current password
    elseif($new_password === '' || $new_password !== $confirm_password)
    {
        $_SESSION['user_error'] = 13;
    }//empty or not confirmed
    else
    {
        $userObj->change_password(password_hash($new_password, PASSWORD_DEFAULT), $me[0]['USID']);
        if(isset($_SESSION['remember_me']))
        {
            (new RememberMe())->rememberUser($me[0]['USID']); //the old token was signed over the old password
        }//stay remembered in this browser
        $_SESSION['user_error'] = 4;
    }//changed
    header("Location: ../Public/home.php");
    exit;
}
elseif(isset($_POST['btn_log_in']))
{
    //fetch data
    $username = $_POST['user_name'];
    $userpwd = $_POST['user_pwd'];

    $userObj = new User();
    $data = $userObj->getUserByName($username);

    if(empty($data))
    {
        $_SESSION['user_error']=2;
        header("Location: ../Public/login.php");
    }//user not exist
    elseif($data[0]['UserStat']!=1)
    {
        $_SESSION['user_error'] = 8;
        header("Location: ../Public/login.php");
    }//user inactive
    elseif(!password_verify($userpwd, $data[0]['UserPwd']))
    {
        $_SESSION['user_error'] = 3;
        header("Location: ../Public/login.php");
    }//wrong password
    else
    {
        $logObj = new User();
        //update user log
        $logObj->editUserLogStat($data[0]['USID']);

        //the shops this user may enter, each through the role held there (Model/shop_access_class.php)
        $shopAccess = new ShopAccess();
        $shops = $shopAccess->getSelectableShops($data[0]['USID']);
        if(empty($shops))
        {
            //7: assigned, but the role they hold is inactive - 9: nothing to enter
            $_SESSION['user_error'] = $shopAccess->hasInactiveRoleAssignment($data[0]['USID']) ? 7 : 9;
            header("Location: ../Public/login.php");
        }//no shop to enter
        elseif(count($shops)==1 && ($reason = ShopAccess::unavailableReason($shops[0], $data[0]['UserType'])) !== null)
        {
            if($reason == ShopAccess::ERR_COMPANY_EXPIRED)
            {
                $_SESSION["expired"]=1;
            }
            else
            {
                $_SESSION['user_error'] = 10;
            }
            header("Location: ../Public/login.php");
        }//the only shop's company is closed
        else
        {
            session_regenerate_id(true); //a fresh session id for the signed in user
            $_SESSION['user_id'] = $data[0]['USID'];
            $_SESSION['user'] = $data;
            $login_date_time = date("Y-m-d H:i:s");
            $logObj->setUserLog($login_date_time, $login_date_time, 1, $data[0]['USID']);

            if(isset($_POST["remember_me"]))
            {
                $_SESSION["remember_me"]=1;
                setcookie('remember_meS', '1', time() + (30 * 24 * 60 * 60), "/"); 
                (new RememberMe())->rememberUser($data[0]['USID']); //signed token, see Includes/remember_me.php
            }//remember me

            //always the shop screen, even with a single shop: entering a shop takes its own sign in
            $_SESSION["toast"]=1;
            header("Location: ../Public/dashboard.php");
        }//signed in
    }//user exists, active, right password
}//log into system
elseif(isset($_POST['btn_log_out']))
{
    date_default_timezone_set("Asia/Colombo");
    $logout_date_time = date("Y-m-d h:i:s");

    $logObj = new User("", "", "", "", "", "");
    $logObj->editUserLog($logout_date_time, $_SESSION['user_id']);

    unset($_SESSION["user_id"]);
    unset($_SESSION['shop_id']);

    header("Location: ../Public/login.php");
}//logout
else
{
    $_SESSION['user_error']=1;//no permission
    // echo $_SESSION['user_error'];
    header("Location: ../Public/login.php");
}