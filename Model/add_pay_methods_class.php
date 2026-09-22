<?php

class AddPaymentModels extends Dbh
{
    public function setPaymentModels($shop_SHID, $paymethod_PMID)
    {
        try {
            // Check if the user is already assigned to the shop
            $checkSql = "SELECT COUNT(*) FROM shoppaymethod WHERE shop_SHID = ? AND paymethod_PMID = ?";
            $checkStmt = $this->connect()->prepare($checkSql);
            $checkStmt->execute([$shop_SHID, $paymethod_PMID]);
            $count = $checkStmt->fetchColumn();

            if ($count > 0) {
                return "Payment method already assigned to this shop";
            }

            $sql = "INSERT INTO shoppaymethod (shop_SHID, paymethod_PMID) VALUES (?, ?)";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_SHID, $paymethod_PMID]);
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

    public function getPaymentMethods()
    {
        try {
            // Correct SQL query using JOIN and proper table references
            $stmt = $this->connect()->prepare("
            SELECT sp.SPID, s.ShopLogo, s.ShopName, p.PaymethodName, p.image_path 
            FROM shoppaymethod sp
            JOIN shop s ON sp.shop_SHID = s.SHID
            JOIN paymethod p ON sp.paymethod_PMID = p.PMID
        ");
            $stmt->execute();
            $payments = $stmt->fetchAll();
            return $payments;
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            die("Error: Unable to fetch Payment Methods. " . $e->getMessage());
        }
    }

    public function getPayMethods()
    {
        try {
            $stmt = $this->connect()->prepare("SELECT PMID, PaymethodName FROM paymethod");
            $stmt->execute();
            $Shops = $stmt->fetchAll();
            return $Shops;
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            die("Error: Unable to fetch Shops. " . $e->getMessage());
        }
    }

    public function deletePaymentMethod($SPID)
    {
        try 
        {
            $sql = "DELETE FROM shoppaymethod WHERE SPID = ?";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$SPID]);
        } 
        catch (PDOException $e) 
        {
            error_log("PDOException: " . $e->getMessage());
            die("Error: Unable to delete payment method. " . $e->getMessage());
        }
    }

    //========================== Payment Methods ==========================//
    public function setPaymethod($PaymethodName, $image_path)
    {
        try 
        {
            $sql="INSERT INTO paymethod(PaymethodName, image_path) VALUES(?, ?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$PaymethodName, $image_path]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//save category
}//clas pay method