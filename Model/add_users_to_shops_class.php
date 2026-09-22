<?php

class AddUsersModels extends Dbh
{
    public function setUserModels($shop_SHID, $user_USID)
    {
        try {
            // Check if the user is already assigned to the shop
            $checkSql = "SELECT COUNT(*) FROM shopusers WHERE shop_SHID = ? AND user_USID = ?";
            $checkStmt = $this->connect()->prepare($checkSql);
            $checkStmt->execute([$shop_SHID, $user_USID]);
            $count = $checkStmt->fetchColumn();

            if ($count > 0) {
                return "User already assigned to this shop";
            }

            //the user's default role becomes their role in this shop
            $sql = "INSERT INTO shopusers (shop_SHID, user_USID, UserRoles_URID)
            SELECT ?, USID, UserRoles_URID FROM user WHERE USID = ?";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_SHID, $user_USID]);
            $run = 1;
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            die("Error: Unable to Add Users. " . $e->getMessage());
            $run = 2;
        }
        if ($run == 1) {
            return true;
        } else {
            return false;
        }
    }

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
            $stmt = $this->connect()->prepare("SELECT USID, UserName FROM user");
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
            $stmt = $this->connect()->prepare("SELECT su.SUID, s.ShopName, u.UserName FROM shopusers su, shop s, user u WHERE su.shop_SHID = s.SHID AND su.user_USID = u.USID");
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