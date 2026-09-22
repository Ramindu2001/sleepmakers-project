<?php
//Who may enter which shop, and with which role.
//
//Each shopusers row assigns one user to one shop with the role they hold THERE (UserRoles_URID)
//and whether that access is active (is_active). Permission checks ask for the role in the
//current shop, so one person can have different rights in each shop - or no access at all.
//user.UserRoles_URID is only the default offered when the user is added to a shop.
//
//A super admin (user.UserType = 1) may enter every shop and is not limited by roles.
//See db/SHOP_ACCESS_MODULE.md.
class ShopAccess extends Dbh
{
    const ERR_BAD_CREDENTIALS  = 'bad_credentials';
    const ERR_USER_INACTIVE    = 'user_inactive';
    const ERR_NO_ACCESS        = 'no_access';
    const ERR_ROLE_INACTIVE    = 'role_inactive';
    const ERR_COMPANY_EXPIRED  = 'company_expired';
    const ERR_COMPANY_INACTIVE = 'company_inactive';

    //what the user is told for each refusal
    const MESSAGES = [
        self::ERR_BAD_CREDENTIALS  => 'Invalid username or password',
        self::ERR_USER_INACTIVE    => 'Your account is inactive. Please contact your system admin.',
        self::ERR_NO_ACCESS        => 'You do not have access to this shop.',
        self::ERR_ROLE_INACTIVE    => 'Your role in this shop is inactive. Please contact your system admin.',
        self::ERR_COMPANY_EXPIRED  => 'Company Expired. Please Contact Synnex IT Solutions.',
        self::ERR_COMPANY_INACTIVE => 'Company Inactive. Please Contact Synnex IT Solutions.',
    ];

    //checked against when the username is unknown, so a wrong username takes as long as a wrong
    //password and the response time does not reveal which usernames exist
    const TIMING_HASH = '$2y$10$Bzs983rK8QxvLYrRfcNsTOuwam2aT.bZncnWPuk/YwOupQLEaf3am';

    public static function errorMessage($error)
    {
        return isset(self::MESSAGES[$error]) ? self::MESSAGES[$error] : 'Oops! Something went wrong';
    }//error message

    //is this shop closed to the user because of its company? $shop needs ComStat and
    //ComExpireDate. Super admins are never kept out. Returns an ERR_COMPANY_* code or null.
    public static function unavailableReason(array $shop, $userType)
    {
        if($userType == 1)
        {
            return null;
        }//super admin

        if($shop['ComStat'] == 0)
        {
            return self::ERR_COMPANY_INACTIVE;
        }//company switched off

        if(date('Y-m-d') > $shop['ComExpireDate'])
        {
            return self::ERR_COMPANY_EXPIRED;
        }//licence over

        return null;
    }//unavailable reason

    //may this user enter this shop?
    public function canAccessShop($user_id, $shop_id)
    {
        return $this->findAccess($user_id, $shop_id) !== null;
    }//can access shop

    //the role this user holds in this shop; null for a super admin (not limited by roles) and
    //for a user who may not enter the shop
    public function getShopRoleId($user_id, $shop_id)
    {
        $access = $this->findAccess($user_id, $shop_id);
        if($access === null || $access['UserType'] == 1)
        {
            return null;
        }//no role applies

        return (int)$access['UserRoles_URID'];
    }//get shop role id

    //the shops offered on the shop screen, with their company's name and state
    public function getSelectableShops($user_id)
    {
        $user = $this->findActiveUser($user_id);
        if($user === null)
        {
            return [];
        }//no such active user

        $columns = "shop.SHID, shop.ShopName, company.CMID, company.ComName, company.ComStat, company.ComExpireDate";
        if($user['UserType'] == 1)
        {
            $sql = "SELECT " . $columns . " FROM shop
            INNER JOIN company ON company.CMID = shop.Company_CMID
            ORDER BY shop.SHID;";
            $params = [];
        }//every shop
        else
        {
            $sql = "SELECT " . $columns . " FROM shopusers
            INNER JOIN shop ON shop.SHID = shopusers.shop_SHID
            INNER JOIN company ON company.CMID = shop.Company_CMID
            INNER JOIN userroles ON userroles.URID = shopusers.UserRoles_URID
            WHERE shopusers.user_USID = ? AND shopusers.is_active = 1
            AND shop.ShopStat = 1 AND userroles.ur_status = 1
            ORDER BY shop.SHID;";
            $params = [$user['USID']];
        }//assigned shops

        $stmt = $this->connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }//get selectable shops

    //is this user kept out of an assigned, active shop only because the role they hold there is
    //inactive? Tells "your role is inactive" apart from "no shops" at sign in.
    public function hasInactiveRoleAssignment($user_id)
    {
        $sql = "SELECT 1 FROM shopusers
        INNER JOIN shop ON shop.SHID = shopusers.shop_SHID
        LEFT JOIN userroles ON userroles.URID = shopusers.UserRoles_URID
        WHERE shopusers.user_USID = ? AND shopusers.is_active = 1 AND shop.ShopStat = 1
        AND (userroles.URID IS NULL OR userroles.ur_status <> 1) LIMIT 1;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }//has inactive role assignment

    //check a username and password for entering a shop. The user is looked up exactly as the
    //main login does (Controller/userController.php), so the same credentials work everywhere.
    //Returns ['ok' => true, 'user' => row, 'error' => null] or ['ok' => false, 'user' => null,
    //'error' => one of the ERR_* codes].
    public function authenticate($username, $password, $shop_id)
    {
        $username = is_string($username) ? $username : '';
        $password = is_string($password) ? $password : '';

        $user = null;
        if($username !== '')
        {
            $stmt = $this->connect()->prepare("SELECT * FROM user WHERE UserName = ? ORDER BY USID LIMIT 1;");
            $stmt->execute([$username]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $user = $row === false ? null : $row;
        }//look the user up

        //verified even for an unknown user, so both failures take the same time
        $valid = password_verify($password, $user !== null ? (string)$user['UserPwd'] : self::TIMING_HASH);
        if($user === null || !$valid)
        {
            return self::refuse(self::ERR_BAD_CREDENTIALS);
        }//unknown user or wrong password

        if($user['UserStat'] != 1)
        {
            return self::refuse(self::ERR_USER_INACTIVE);
        }//account switched off

        $shop_id = self::toId($shop_id);
        if($shop_id === null)
        {
            return self::refuse(self::ERR_NO_ACCESS);
        }//not a shop id

        $sql = "SELECT shop.ShopStat, company.ComStat, company.ComExpireDate,
        shopusers.is_active, userroles.ur_status
        FROM shop
        INNER JOIN company ON company.CMID = shop.Company_CMID
        LEFT JOIN shopusers ON shopusers.shop_SHID = shop.SHID AND shopusers.user_USID = ?
        LEFT JOIN userroles ON userroles.URID = shopusers.UserRoles_URID
        WHERE shop.SHID = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$user['USID'], $shop_id]);
        $shop = $stmt->fetch(PDO::FETCH_ASSOC);
        if($shop === false)
        {
            return self::refuse(self::ERR_NO_ACCESS);
        }//no such shop

        if($user['UserType'] != 1)
        {
            if($shop['is_active'] != 1 || $shop['ShopStat'] != 1)
            {
                return self::refuse(self::ERR_NO_ACCESS);
            }//not assigned, revoked, or shop closed

            if($shop['ur_status'] != 1)
            {
                return self::refuse(self::ERR_ROLE_INACTIVE);
            }//role switched off or deleted

            $reason = self::unavailableReason($shop, $user['UserType']);
            if($reason !== null)
            {
                return self::refuse($reason);
            }//company closed
        }//not a super admin

        return ['ok' => true, 'user' => $user, 'error' => null];
    }//authenticate

    //------------------------------------------------------------------------------------------

    private static function refuse($error)
    {
        return ['ok' => false, 'user' => null, 'error' => $error];
    }//refuse

    //a positive integer id, or null
    private static function toId($value)
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $id === false ? null : $id;
    }//to id

    private function findActiveUser($user_id)
    {
        $user_id = self::toId($user_id);
        if($user_id === null)
        {
            return null;
        }//not an id

        $stmt = $this->connect()->prepare("SELECT USID, UserType FROM user WHERE USID = ? AND UserStat = 1;");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user === false ? null : $user;
    }//find active user

    //the user's access to the shop (UserType and UserRoles_URID), or null when they may not
    //enter it - the one rule every check goes through
    private function findAccess($user_id, $shop_id)
    {
        $user_id = self::toId($user_id);
        $shop_id = self::toId($shop_id);
        if($user_id === null || $shop_id === null)
        {
            return null;
        }//not ids

        $sql = "SELECT user.UserType, shopusers.UserRoles_URID
        FROM user
        INNER JOIN shop ON shop.SHID = ?
        INNER JOIN company ON company.CMID = shop.Company_CMID
        LEFT JOIN shopusers ON shopusers.user_USID = user.USID AND shopusers.shop_SHID = shop.SHID
        LEFT JOIN userroles ON userroles.URID = shopusers.UserRoles_URID
        WHERE user.USID = ? AND user.UserStat = 1
        AND (user.UserType = 1
             OR (shopusers.is_active = 1 AND shop.ShopStat = 1 AND userroles.ur_status = 1))
        LIMIT 1;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$shop_id, $user_id]);
        $access = $stmt->fetch(PDO::FETCH_ASSOC);
        return $access === false ? null : $access;
    }//find access

}//class shop access
