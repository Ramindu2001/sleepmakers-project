<?php  
class UserRole extends Dbh
{
    //Add Users and Add User Role: managed by the system (super) admin only (Includes/super_admin.php),
    //so a role cannot grant them and the role editor does not offer them
    const ADMIN_ONLY_FEATURES = [20, 54];

    Public function select_modules()
    {
        $sql = "SELECT * FROM sysmodules ORDER BY sort_order;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    //the menus this role is shown IN ONE SHOP; $role_id is the role being edited
    Public function select_edit_rolemodules($role_id, $shop_id)
    {
        $sql = "SELECT
        sm.*,
        CASE WHEN uma.SysModules_SMID IS NOT NULL THEN 1 ELSE 0 END AS Access
    FROM
        sysmodules sm
    LEFT JOIN
        usermoduleaccess uma ON sm.SMID = uma.SysModules_SMID AND uma.UserRoles_URID = ? AND uma.shop_SHID = ?
    ORDER BY sm.sort_order;
    ";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$role_id, $shop_id]);
        return $stmt->fetchAll();
    }
    public function edit_role($role_id)
    {
        $sql="SELECT * FROM userroles WHERE URID =? ;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$role_id]);
        return $stmt->fetchAll();   
    }
    Public function select_features($module_id)
    {
        $sql = "SELECT * FROM sysfeatures WHERE SystemModules_SMID=? AND SFID NOT IN (" . implode(',', self::ADMIN_ONLY_FEATURES) . ") ORDER BY sort_order;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$module_id]);
        return $stmt->fetchAll();   
    }
    public function update_role_status($id,$status)
    {
        $sql="UPDATE userroles SET ur_status=? WHERE URID=?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$status,$id]);
        return 1;

    }
    //what this role is ticked for IN ONE SHOP, for one module of the editor form
    Public function select_edit_rolefeatures($module_id,$userrole,$shop_id)
    {
        $sql = "SELECT 
        sf.SFID,
        sf.FeatureName,
        sf.SystemModules_SMID,
        sm.SMID,
        CASE WHEN ura.is_create = 1 THEN 1 ELSE 0 END AS is_create,
        CASE WHEN ura.is_edit = 1 THEN 1 ELSE 0 END AS is_edit,
        CASE WHEN ura.is_view = 1 THEN 1 ELSE 0 END AS is_view,
        CASE WHEN ura.is_delete = 1 THEN 1 ELSE 0 END AS is_delete,
        CASE WHEN ura.is_verify = 1 THEN 1 ELSE 0 END AS is_verify,
        CASE WHEN ura.is_print = 1 THEN 1 ELSE 0 END AS is_print
    FROM 
        sysfeatures sf
    INNER JOIN 
        sysmodules sm ON sf.SystemModules_SMID = sm.SMID
    LEFT JOIN 
        userroleaccess ura ON sf.SFID = ura.SysFeatures_SFID AND ura.UserRolls_URID =? AND ura.shop_SHID =?
    WHERE
        sf.SystemModules_SMID = ? AND sf.SFID NOT IN (" . implode(',', self::ADMIN_ONLY_FEATURES) . ")
    ORDER BY sf.sort_order;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$userrole,$shop_id,$module_id]);
        return $stmt->fetchAll();
    }
    //saving the form clears what the role had IN THIS SHOP before writing it again; the other
    //shops keep their own ticks (db/SHOP_PERMISSIONS_MODULE.md)
    public function delete_user_modules($role_id,$shop_id)
    {
        $sql="DELETE FROM usermoduleaccess WHERE `usermoduleaccess`.`UserRoles_URID` = ? AND `usermoduleaccess`.`shop_SHID` = ?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$role_id,$shop_id]);
        return 1;
    }
    public function delete_user_feature($role_id,$shop_id)
    {
        $sql="DELETE FROM userroleaccess WHERE `userroleaccess`.`UserRolls_URID` = ? AND `userroleaccess`.`shop_SHID` = ?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$role_id,$shop_id]);
        return 1;
    }
    public function update_role_name($role_id,$role_name)
    {
        $sql="UPDATE userroles SET UserRoleName=? WHERE URID=?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$role_name,$role_id]);
        return 1;

    }
    public function check_role($role_name,$role_id=null)
    {
        if ($role_id!=null) 
        {
            $sql = "SELECT count(*) FROM userroles WHERE UserRoleName=? AND URID!=? AND ur_status=1;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$role_name,$role_id]);
            
        }
        else
        {
            $sql = "SELECT count(*) FROM userroles WHERE UserRoleName=? AND ur_status=1;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$role_name]);
        }
        
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
    public function add_role($role_name,$user)
    {
        $check_name=$this->check_role($role_name);
        if ($check_name==0) 
        {
            return 0;
        }
        else
        {
            $ip=$_SERVER['REMOTE_ADDR'];
            $sql = "INSERT INTO userroles(UserRoleName,added_by,user_ip) VALUES(?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$role_name,$user,$ip]);

            return 1;
        }
    }
    public function get_user_role_id($role_name,$user)
    {
        $ip=$_SERVER['REMOTE_ADDR'];
        $sql="SELECT max(URID) AS max_ids FROM userroles WHERE UserRoleName=? AND added_by=? AND user_ip=?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$role_name,$user,$ip]);
        return $stmt->fetchAll();
    }
    //one row per role, shop and module/feature: saving the same one twice overwrites it
    public function add_role_module($userrole,$module,$shop_id)
    {
        $sql = "INSERT INTO usermoduleaccess(UserRoles_URID,shop_SHID,SysModules_SMID) VALUES(?,?,?)
        ON DUPLICATE KEY UPDATE SysModules_SMID = VALUES(SysModules_SMID);";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$userrole,$shop_id,$module]);
        return 1;

    }
    public function add_userrole($create,$update,$view,$delete,$verify,$print,$userrole,$feature,$shop_id)
    {
        $sql="INSERT INTO `userroleaccess`(`is_create`, `is_edit`, `is_view`, `is_delete`, `is_verify`, `is_print`, `UserRolls_URID`, `shop_SHID`, `SysFeatures_SFID`) VALUES (?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE `is_create` = VALUES(`is_create`), `is_edit` = VALUES(`is_edit`), `is_view` = VALUES(`is_view`),
        `is_delete` = VALUES(`is_delete`), `is_verify` = VALUES(`is_verify`), `is_print` = VALUES(`is_print`);";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$create,$update,$view,$delete,$verify,$print,$userrole,$shop_id,$feature]);
        return 1;
    }
    
    public function select_all_userroles()
    {
        $sql="SELECT * FROM userroles ORDER BY ur_status DESC;";
        $stmt=$this->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    public function select_all_active_userroles()
    {
        $sql="SELECT * FROM userroles WHERE ur_status=1 ORDER BY ur_status DESC;";
        $stmt=$this->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    //for the role list: everything this role is ticked for IN ONE SHOP
    public function select_all_user_role_feature($userrole,$shop_id)
    {
        $sql="SELECT 
        ura.*,
        sf.SFID,
        sf.FeatureName,
        CASE 
            WHEN ura.is_create = 1 THEN CONCAT(sf.FeatureName, '-create')
        END AS careteAccess,
        CASE 
            WHEN ura.is_edit = 1 THEN CONCAT(sf.FeatureName, '-edit')
        END AS editAccess,
         CASE 
            WHEN ura.is_view = 1 THEN CONCAT(sf.FeatureName, '-view')
        END AS viewAccess,
         CASE 
            WHEN ura.is_delete = 1 THEN CONCAT(sf.FeatureName, '-delete')
        END AS deleteAccess,
         CASE 
            WHEN ura.is_verify = 1 THEN CONCAT(sf.FeatureName, '-verify')
        END AS verifyAccess,
         CASE 
            WHEN ura.is_print = 1 THEN CONCAT(sf.FeatureName, '-print')
        END AS printAccess
    FROM 
        userroleaccess ura
    JOIN 
        sysfeatures sf ON ura.SysFeatures_SFID = sf.SFID
    WHERE
        ura.UserRolls_URID = ? AND ura.shop_SHID = ? AND
        (ura.is_create = 1 OR ura.is_edit = 1 OR ura.is_view = 1 OR ura.is_delete = 1 OR ura.is_verify = 1 OR ura.is_print = 1);";
        $stmt=$this->connect()->prepare($sql);
        $stmt->execute([$userrole,$shop_id]);
        return $stmt->fetchAll();
    }
    public function select_all_user_role_module($userrole,$shop_id)
    {
        $sql="SELECT uma.*,sm.ModuleName FROM `usermoduleaccess` uma
        INNER JOIN sysmodules sm ON sm.SMID=uma.SysModules_SMID
        WHERE uma.UserRoles_URID=? AND uma.shop_SHID=?
        ;";
        $stmt=$this->connect()->prepare($sql);
        $stmt->execute([$userrole,$shop_id]);
        return $stmt->fetchAll();
    }

}
?>