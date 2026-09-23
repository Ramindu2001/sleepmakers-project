<?php 

class User extends Dbh
{
    public function check_username($username,$UserEmail)
    {
            $sql="SELECT * FROM user WHERE UserName=? OR UserEmail=?";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$username,$UserEmail]);
        
        $count= $stmt->fetchColumn();
        
        if ($count>0) 
        {
            return 0;
        }
        else 
        {
            return 1;
        }
    }

    public function checkusertype($USID)
    {
        $sql="SELECT * FROM user WHERE USID=? ";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$USID]);
    
        $count= $stmt->fetchAll();
        return $count[0]["UserType"];
    }

    public function getUsername($username)
    {
        $sql="SELECT * FROM user WHERE UserName=?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$username]);
    
        $count= $stmt->fetchColumn();
        
        if ($count>0) 
        {
            return 0;
        }
        else 
        {
            return 1;
        }
    }
    public function edit_check_username($username,$uid)
    {
        // $sql="SELECT COUNT(*)  FROM user WHERE  UserName=? AND USID!=?;";
        // $stmt = $this->connect()->prepare($sql);
        // $stmt->execute([$username,$uid]);
        
        $sql = "SELECT count(*) FROM user WHERE UserName=? AND USID!=? AND UserStat=1;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$username,$uid]);
        $count= $stmt->fetchColumn();
        
        if ($count==0) 
        {
            return 1;
        }
        else 
        {
            return 0;
        }

    }
    public function edit_user($uid,$username,$userEmail,$userContact,$userRole,$eprofile,$epaylimit,$status)
    {
        $check_username=$this->edit_check_username($username,$uid);
        if ($check_username==0) 
        {
            return $check_username;
        }
        else
        {
            $sql = "UPDATE user SET `UserProfile`=?,`UserName`=?,`UserEmail`=?,`ContactNo`=?,`UserRoles_URID`=?,`paylimit`=?,UserStat=? WHERE USID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$eprofile,$username,$userEmail,$userContact,$userRole,$epaylimit,$status,$uid]);
            return 1;
        }
        
    }
    public function change_password($password,$uid)
    {

        $sql="UPDATE user SET UserPwd=? WHERE USID=?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$password,$uid]);
        return 1;
    }
    public function select_all_users_with_role($userType)
    {
        if($userType==1)
        {
            $sql="SELECT u.*,ur.* FROM `user` u inner join userroles ur ON u.UserRoles_URID=ur.URID";
        }
        else
        {
          $sql="SELECT u.*,ur.* FROM `user` u inner join userroles ur ON u.UserRoles_URID=ur.URID WHERE u.UserType!=1";
        }
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
  
    public function pwd_chng_user($user)
    {

        $sql="SELECT * FROM user WHERE UserName=? OR UserEmail=?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$user,$user]);
        return $stmt->fetchAll();
    }
    public function update_token($token,$uid)
    {
        $sql="UPDATE user SET PwdChange=? WHERE USID=?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$token,$uid]);
        return 1;
    }
    public function setUser($profile_image_name, $username, $UserEmail, $UserContact, $hash, $userRole, $type,$paylimit)
    {
        $check_username=$this->check_username($username,$UserEmail);
        if ($check_username==0) 
        {
            return 0;
        }
        else
        {    
            $pdo = $this->connect();
            $sql = "INSERT INTO `user`( `UserProfile`, `UserName`, `UserEmail`, `ContactNo`, `UserPwd`, `UserRoles_URID`,`UserType`,`paylimit`) VALUES (?,?,?,?,?,?,?,?);";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$profile_image_name, $username, $UserEmail, $UserContact, $hash, $userRole, $type,$paylimit]);
            return $pdo->lastInsertId();
        }
    }

    public function getUserByName($username)
    {
        $sql = "SELECT * FROM user WHERE UserName = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$username]);
        return $stmt->fetchAll();
    }//get user by name

//=========================== User Log ==============================//
    public function setUserLog($logStart, $logEnd, $logStat, $user_USID)
    {
        //ULID, logStart, logEnd, logStat, user_USID
        $sql = "INSERT INTO userlog(logStart, logEnd, logStat, user_USID) VALUES(?,?,?,?);";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$logStart, $logEnd, $logStat, $user_USID]);
    }//set user log

    public function CheckUserRoleStatus($userRoleID)
    {
        $sql = "SELECT * FROM userroles WHERE URID = ? AND ur_status=1;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$userRoleID]);
        return $stmt->fetchAll();   
    }//set user log

    public function editUserLog($log_out_time, $user_id)
    {
        $sql = "UPDATE userlog SET LogOutTime = ? , LogStat = 2 WHERE Users_USID = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$log_out_time, $user_id]);
    }//edit user log

    public function editUserLogStat($user_id)
    {
        $sql = "UPDATE userlog SET logStat = 2 WHERE ULID = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$user_id]);
    }//edit user log 

    public function getOneUser($user_id)
    {
        $sql = "SELECT * FROM user WHERE USID = ? LIMIT 1;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();   
    }//get user by name

    //What a role that has not been ticked for this shop at all may do: nothing. The pages read
    //$rights[0]['is_view'] without looking first, so the answer is always one row.
    const NO_RIGHTS = ['is_create' => 0, 'is_edit' => 0, 'is_view' => 0, 'is_delete' => 0, 'is_verify' => 0, 'is_print' => 0];

    //Which shop's ticks to read. A role is ticked once per shop (db/SHOP_PERMISSIONS_MODULE.md),
    //so every permission lookup below is about the shop the page is open in. The pages pass no
    //shop of their own: it is the one entered at the shop sign in. No shop open means no rights.
    private static function ticksOfShop($shop_id = null)
    {
        if($shop_id !== null)
        {
            return (int)$shop_id;
        }//the caller knows which shop

        return isset($_SESSION['shop_id']) ? (int)$_SESSION['shop_id'] : 0;
    }//ticks of shop

    public function getUserRoleModuleAccess($userRole_id, $shop_id = null)
    {
        $sql = "SELECT * FROM `usermoduleaccess` WHERE UserRoles_URID=? AND shop_SHID=?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$userRole_id, self::ticksOfShop($shop_id)]);
        return $stmt->fetchAll();
    }

    public function userAcces($userRole_id,$feature_id, $shop_id = null)
    {
        $sql = "SELECT * FROM `userroleaccess` WHERE UserRolls_URID=? AND shop_SHID=? AND SysFeatures_SFID=?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$userRole_id, self::ticksOfShop($shop_id), $feature_id]);
        $data=$stmt->fetchAll();
        return empty($data) ? [self::NO_RIGHTS] : $data;
    }

    public function getUserRoleFeatureAccess($userRole_id,$feature_id, $shop_id = null)
    {
        $sql = "SELECT * FROM `userroleaccess` WHERE UserRolls_URID=? AND shop_SHID=? AND SysFeatures_SFID=?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$userRole_id, self::ticksOfShop($shop_id), $feature_id]);
        $data = $stmt->fetchAll();
        return empty($data) ? [self::NO_RIGHTS] : $data;
    }

    public function getRoleViewAccess($userRole_id,$feature_id, $shop_id = null)
    {
        $sql = "SELECT * FROM `userroleaccess` WHERE UserRolls_URID=? AND shop_SHID=? AND SysFeatures_SFID=?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$userRole_id, self::ticksOfShop($shop_id), $feature_id]);
        $data = $stmt->fetchAll();
        $access = empty($data[0]['is_view']) ? 0 : 1;
        return $access;
    }

    //the user's rights on a feature in a shop - through the role they hold in that shop, as
    //that role is ticked in that shop
    public function getUserFeatureAccess($user_id,$feature_id,$shop_id)
    {
        $sql = "SELECT RAID, is_create, is_edit, is_view, is_delete, is_verify, is_print, UserRolls_URID, SysFeatures_SFID FROM userroleaccess
        INNER JOIN userroles ON userroles.URID = userroleaccess.UserRolls_URID
        INNER JOIN shopusers ON shopusers.UserRoles_URID = userroleaccess.UserRolls_URID
        AND shopusers.shop_SHID = userroleaccess.shop_SHID
        WHERE shopusers.user_USID = ? AND shopusers.shop_SHID = ? AND shopusers.is_active = 1
        AND userroles.ur_status = 1 AND SysFeatures_SFID = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$user_id,$shop_id,$feature_id]);
        $data = $stmt->fetchAll();

        return $data;
    }

    protected function getLogByUserID($user_id)
    {
        $sql = "SELECT * FROM userlog WHERE Users_USID = ? AND LogStat = 1;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }//get user by name
    
    protected function getUserByQuery($sql)
    {
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }//get user by name

//=========================== User Roles =============================//
    public function getUserRoles()
    {
        $sql = "SELECT * FROM userroles;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }//get users rolse

    public function delete_user($user_id)
    {
        $sql="UPDATE user SET UserStat=0 WHERE USID=?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$user_id]);
        return 1;
    }

    public function activate_user($user_id)
    {
        $sql="UPDATE user SET UserStat=1 WHERE USID=?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$user_id]);
        return 1;
    }

}//class user