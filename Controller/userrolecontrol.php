<?php 
include "../Includes/includes.php";
include "../Includes/authcheck.php";

if (isset($_POST['add-role'])) 
{
    if (!empty($_POST['role_name'])) 
    {
        $role_name=$_POST['role_name'];
        echo "Role - ".$role_name."<br>";
        $role=new UserRole();
        $user=$_SESSION['user_id'];
        $user_role=$role->add_role($role_name,$user);
        if ($user_role==0) 
        {
            $_SESSION['role_status']=1;
            header("Location:../Public/add-role.php");
        }
        else
        {
            $ip=$_SERVER['REMOTE_ADDR'];
            $roles=$role->get_user_role_id($role_name,$user);
            $role_id=$roles[0][0];
            for ($i = 0; $i < count($_POST['module_id']); $i++) 
            { 
                echo "Module - " . $_POST['module_id'][$i] . "<br>";
                if (isset($_POST['module_user'][$_POST['module_id'][$i]])) 
                {
                    for ($mu=0; $mu < count($_POST['module_user'][$_POST['module_id'][$i]]) ; $mu++) 
                    { 
                        $user_module=$role->add_role_module($role_id,$_POST['module_user'][$_POST['module_id'][$i]][$mu]);
                    }
                }
                
                
                // $moduleid=$_POST['module_id'][$i]
                if(isset($_POST['feature_id'][$_POST['module_id'][$i]]))
                {
                    for ($j = 0; $j < count($_POST['feature_id'][$_POST['module_id'][$i]]); $j++) 
                    { 
                        echo "Feature -".$_POST['feature_id'][$_POST['module_id'][$i]][$j]."<br>";
                        
                        $create = isset($_POST['create'][$_POST['module_id'][$i]][$_POST['feature_id'][$_POST['module_id'][$i]][$j]]) ? "1" : "0";
                        echo $create . "<br>";
                        
                        $update = isset($_POST['update'][$_POST['module_id'][$i]][$_POST['feature_id'][$_POST['module_id'][$i]][$j]]) ? "1" : "0";
                        echo $update . "<br>";
                        
                        $view = isset($_POST['view'][$_POST['module_id'][$i]][$_POST['feature_id'][$_POST['module_id'][$i]][$j]]) ? "1" : "0";
                        echo $view . "<br>";
                        
                        $delete = isset($_POST['delete'][$_POST['module_id'][$i]][$_POST['feature_id'][$_POST['module_id'][$i]][$j]]) ? "1" : "0";
                        echo $delete . "<br>";
                        
                        $verify = isset($_POST['verify'][$_POST['module_id'][$i]][$_POST['feature_id'][$_POST['module_id'][$i]][$j]]) ? "1" : "0";
                        echo $verify . "<br>";
                        
                        $print = isset($_POST['print'][$_POST['module_id'][$i]][$_POST['feature_id'][$_POST['module_id'][$i]][$j]]) ? "1" : "0";
                        echo $print . "<br>";
                        $userrole=$role_id;
                        $feature=$_POST['feature_id'][$_POST['module_id'][$i]][$j];
                        $user_role_access=$role->add_userrole($create,$update,$view,$delete,$verify,$print,$userrole,$feature);
                    }
                }

                $_SESSION['role_status']=2;
                header("Location:../Public/add-role.php");
                
            }
        }
    }
    else
    {
        $_SESSION['role_status']=0;
        header("Location:../Public/add-role.php");
    }
}
elseif (isset($_POST['edit-role'])) 
{
    $role=new UserRole();
    if(!empty($_POST['role_id']))
    {
        if (!empty($_POST['role_name'])) 
        {
            $role_id=$_POST['role_id'];
            $role_name=$_POST['role_name'];
            $check_name=$role->check_role($role_name,$role_id);
            if ($check_name==0) 
            {
                $_SESSION['role_status']=2;
                header("Location:../Public/edit-role.php?id=$_POST[role_id]");
                
            }
            else
            {
                $delete_modules=$role->delete_user_modules($role_id);
                $delete_feature=$role->delete_user_feature($role_id);
                $update_role_nm=$role->update_role_name($role_id,$role_name);
                
                for ($i = 0; $i < count($_POST['module_id']); $i++) 
                { 
                    echo "Module - " . $_POST['module_id'][$i] . "<br>";
                    if (isset($_POST['module_user'][$_POST['module_id'][$i]])) 
                    {
                        for ($mu=0; $mu < count($_POST['module_user'][$_POST['module_id'][$i]]) ; $mu++) 
                        { 
                            $user_module=$role->add_role_module($role_id,$_POST['module_user'][$_POST['module_id'][$i]][$mu]);
                        }
                    }
                    
                    
                    // $moduleid=$_POST['module_id'][$i]
                    if(isset($_POST['feature_id'][$_POST['module_id'][$i]]))
                    {
                        for ($j = 0; $j < count($_POST['feature_id'][$_POST['module_id'][$i]]); $j++) 
                        { 
                            echo "Feature -".$_POST['feature_id'][$_POST['module_id'][$i]][$j]."<br>";
                            
                            $create = isset($_POST['create'][$_POST['module_id'][$i]][$_POST['feature_id'][$_POST['module_id'][$i]][$j]]) ? "1" : "0";
                            echo $create . "<br>";
                            
                            $update = isset($_POST['update'][$_POST['module_id'][$i]][$_POST['feature_id'][$_POST['module_id'][$i]][$j]]) ? "1" : "0";
                            echo $update . "<br>";
                            
                            $view = isset($_POST['view'][$_POST['module_id'][$i]][$_POST['feature_id'][$_POST['module_id'][$i]][$j]]) ? "1" : "0";
                            echo $view . "<br>";
                            
                            $delete = isset($_POST['delete'][$_POST['module_id'][$i]][$_POST['feature_id'][$_POST['module_id'][$i]][$j]]) ? "1" : "0";
                            echo $delete . "<br>";
                            
                            $verify = isset($_POST['verify'][$_POST['module_id'][$i]][$_POST['feature_id'][$_POST['module_id'][$i]][$j]]) ? "1" : "0";
                            echo $verify . "<br>";
                            
                            $print = isset($_POST['print'][$_POST['module_id'][$i]][$_POST['feature_id'][$_POST['module_id'][$i]][$j]]) ? "1" : "0";
                            echo $print . "<br>";
                            $userrole=$role_id;
                            $feature=$_POST['feature_id'][$_POST['module_id'][$i]][$j];
                            $user_role_access=$role->add_userrole($create,$update,$view,$delete,$verify,$print,$userrole,$feature);
                        }
                    }

                    $_SESSION['role_status']=3;
                    header("Location:../Public/edit-role.php?id=$_POST[role_id]");
                    
                }

            }
        }
        else 
        {
            $_SESSION['role_status']=1;
            header("Location:../Public/edit-role.php?id=$_POST[role_id]");
        }

    }
    else
    {
    
        $_SESSION['role_status']=0;
        header("Location:../Public/add-role.php");

    }
}
elseif(isset($_POST['activate-role']))
{
    if(!empty($_POST['activate_role_id']))
    {
        $role = new UserRole();
        $result = $role->update_role_status($_POST['activate_role_id'], 1);
        if($result == 1)
        {
            $_SESSION['role_status'] = 4; // Activated successfully
        }
        else
        {
            $_SESSION['role_status'] = 0;
        }
    }
    header("Location:../Public/user-roles.php");
}
elseif(isset($_POST['delete-role']))
{
    if(!empty($_POST['delete_role_id']))
    {
        $role = new UserRole();
        $result = $role->update_role_status($_POST['delete_role_id'], 0);
        if($result == 1)
        {
            $_SESSION['role_status'] = 5; // Deactivated successfully
        }
        else
        {
            $_SESSION['role_status'] = 0;
        }
    }
    header("Location:../Public/user-roles.php");
}
else
{
    
    $_SESSION['role_status']=0;
    header("Location:../Public/user-roles.php");
}
?>
