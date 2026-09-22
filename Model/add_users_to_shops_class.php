<?php

class AddUsersModels extends Dbh
{
    //assign a user to a shop with the role they will hold there. Returns 'assigned', or
    //'exists' when they are already assigned to that shop (change the role with updateRole).
    //Unlike the older methods here this one throws PDOException - the JSON controller
    //(Controller/AddUsersToShopsController.php) turns it into an answer.
    public function assignUser($shop_SHID, $user_USID, $role_id)
    {
        $pdo = $this->connect();
        $check = $pdo->prepare("SELECT COUNT(*) FROM shopusers WHERE shop_SHID = ? AND user_USID = ?");
        $check->execute([$shop_SHID, $user_USID]);
        if ($check->fetchColumn() > 0) {
            return 'exists';
        }//already assigned

        try {
            $stmt = $pdo->prepare("INSERT INTO shopusers (shop_SHID, user_USID, UserRoles_URID, is_active) VALUES (?, ?, ?, 1)");
            $stmt->execute([$shop_SHID, $user_USID, $role_id]);
        } catch (PDOException $e) {
            if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
                return 'exists';
            }//assigned by someone else a moment ago (uq_shopusers_shop_user)
            throw $e;
        }//catch
        return 'assigned';
    }//assignUser

    //the role the user holds in the shop of this assignment
    public function updateRole($SUID, $role_id)
    {
        $stmt = $this->connect()->prepare("UPDATE shopusers SET UserRoles_URID = ? WHERE SUID = ?");
        $stmt->execute([$role_id, $SUID]);
    }//updateRole

    //revoke (0) or restore (1) access; the assignment and its history stay
    public function setActive($SUID, $active)
    {
        $stmt = $this->connect()->prepare("UPDATE shopusers SET is_active = ? WHERE SUID = ?");
        $stmt->execute([$active ? 1 : 0, $SUID]);
    }//setActive

    public function isActiveRole($role_id)
    {
        $stmt = $this->connect()->prepare("SELECT 1 FROM userroles WHERE URID = ? AND ur_status = 1");
        $stmt->execute([$role_id]);
        return $stmt->fetch() !== false;
    }//isActiveRole

    public function shopExists($shop_id)
    {
        $stmt = $this->connect()->prepare("SELECT 1 FROM shop WHERE SHID = ?");
        $stmt->execute([$shop_id]);
        return $stmt->fetch() !== false;
    }//shopExists

    public function userExists($user_id)
    {
        $stmt = $this->connect()->prepare("SELECT 1 FROM user WHERE USID = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch() !== false;
    }//userExists

    //the roles an assignment may be given
    public function getActiveRoles()
    {
        $stmt = $this->connect()->prepare("SELECT URID, UserRoleName FROM userroles WHERE ur_status = 1 ORDER BY UserRoleName");
        $stmt->execute();
        return $stmt->fetchAll();
    }//getActiveRoles

    public function getShops()
    {
        try {
            $stmt = $this->connect()->prepare("SELECT SHID, ShopName FROM shop");
            $stmt->execute();
            $Shops = $stmt->fetchAll();
            return $Shops;
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            die("Error: Unable to fetch Shops. " . $e->getMessage());
        }
    }

    public function getUsers()
    {
        try {
            //UserRoles_URID: the user's default role, offered when they are added to a shop
            $stmt = $this->connect()->prepare("SELECT USID, UserName, UserRoles_URID FROM user");
            $stmt->execute();
            $Users = $stmt->fetchAll();
            return $Users;
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            die("Error: Unable to fetch Users. " . $e->getMessage());
        }
    }

    public function getAssignedUsers()
    {
        try {
            $stmt = $this->connect()->prepare("SELECT su.SUID, su.shop_SHID, su.user_USID, su.UserRoles_URID, su.is_active,
                s.ShopName, u.UserName, ur.UserRoleName
                FROM shopusers su
                INNER JOIN shop s ON s.SHID = su.shop_SHID
                INNER JOIN user u ON u.USID = su.user_USID
                LEFT JOIN userroles ur ON ur.URID = su.UserRoles_URID
                ORDER BY s.ShopName, u.UserName");
            $stmt->execute();
            $Users = $stmt->fetchAll();
            return $Users;
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            die("Error: Unable to fetch Users. " . $e->getMessage());
        }
    }

    public function getUserShopData($SUID)
    {
        try {
            $stmt = $this->connect()->prepare("SELECT shop_SHID, user_USID FROM shopusers WHERE SUID = ?");
            $stmt->execute([$SUID]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            return $userData;
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            return false;
        }
    }

    public function deleteUserFromShop($SUID)
    {
        try {
            // Fetch the user_USID and shop_SHID based on SUID
            $sql = "SELECT user_USID, shop_SHID FROM shopusers WHERE SUID = ?";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$SUID]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $user_USID = $row['user_USID'];
                $shop_SHID = $row['shop_SHID'];

                // Check if the pair exists in any of the specified tables
                $checkSql = "
                SELECT 1 FROM grnheader WHERE user_USID = ? AND shop_SHID = ?
                UNION
                SELECT 1 FROM adjustheader WHERE user_USID = ? AND shop_SHID = ?
                UNION
                SELECT 1 FROM invoiceheader WHERE user_USID = ? AND shop_SHID = ?
                UNION
                SELECT 1 FROM transferheader WHERE user_USID = ? AND shop_SHID = ?
            ";

                $checkStmt = $this->connect()->prepare($checkSql);
                $checkStmt->execute([
                    $user_USID,
                    $shop_SHID,
                    $user_USID,
                    $shop_SHID,
                    $user_USID,
                    $shop_SHID,
                    $user_USID,
                    $shop_SHID
                ]);

                // Log the result of the check query
                $exists = $checkStmt->fetch();
                error_log("Check query result for user_USID $user_USID and shop_SHID $shop_SHID: " . ($exists ? "Exists" : "Does not exist"));

                if ($exists) {
                    return "Cannot delete. This user-shop pair exists in related tables.";
                } else {
                    // Proceed to delete
                    $deleteSql = "DELETE FROM shopusers WHERE SUID = ?";
                    $deleteStmt = $this->connect()->prepare($deleteSql);
                    $deleteStmt->execute([$SUID]);
                    return "User deleted successfully.";
                }
            } else {
                return "User not found.";
            }
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            return "Error occurred: " . $e->getMessage();
        }
    }

    public function checkUserDelete($SUID)
    {
        try {
            // Fetch the user_USID and shop_SHID based on SUID
            $sql = "SELECT user_USID, shop_SHID FROM shopusers WHERE SUID = ?";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$SUID]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $user_USID = $row['user_USID'];
                $shop_SHID = $row['shop_SHID'];

                // Check if the pair exists in any of the specified tables
                $checkSql = "
                SELECT 1 FROM grnheader WHERE user_USID = ? AND shop_SHID = ?
                UNION
                SELECT 1 FROM adjustheader WHERE user_USID = ? AND shop_SHID = ?
                UNION
                SELECT 1 FROM invoiceheader WHERE user_USID = ? AND shop_SHID = ?
                UNION
                SELECT 1 FROM transferheader WHERE user_USID = ? AND shop_SHID = ?
            ";

                $checkStmt = $this->connect()->prepare($checkSql);
                $checkStmt->execute([
                    $user_USID,
                    $shop_SHID,
                    $user_USID,
                    $shop_SHID,
                    $user_USID,
                    $shop_SHID,
                    $user_USID,
                    $shop_SHID
                ]);

                $exists = $checkStmt->fetch();

                if ($exists) {
                    return "Cannot delete. This user-shop pair exists in related tables.";
                } else {
                    return "User can be deleted.";
                }
            } else {
                return "User not found.";
            }
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            return "Error occurred: " . $e->getMessage();
        }
    }

    public function deleteUserShop($SUID)
    {
        try {
            $sql = "DELETE FROM shopusers WHERE SUID = ?";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$SUID]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            return false;
        }
    }
}