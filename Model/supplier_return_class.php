<?php

class SupplierReturn extends Dbh
{
    public function check_srn_status($srn_header_id)
    {
        $sql="SELECT ReturnStat FROM supplierreturn WHERE ReturnNo=?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$srn_header_id]);
        return $stmt->fetchAll();
    }

    public function get_srnId($srn_header_id)
    {
        $sql="SELECT SRID FROM supplierreturn WHERE ReturnNo=?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$srn_header_id]);
        return $stmt->fetchAll();
    }

    public function get_ProductId($Inventory_id)
    {
        $sql="SELECT PDID FROM inventory IND INNER JOIN products P ON IND.products_PDID = P.PDID WHERE IND.INID = ?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$Inventory_id]);
        return $stmt->fetchAll();
    }
    public function setSupplierReturn($ReturnNo, $EffectiveDate, $ReturnAmount, $ReturnStat, $Supplier_SPID, $shop_SHID, $user_USID)
    {
        try {
            $sql = "INSERT INTO supplierreturn(ReturnNo, EffectiveDate, ReturnAmount, ReturnStat, Supplier_SPID, shop_SHID, user_USID) VALUES(?,?,?,?,?,?,?)";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$ReturnNo, $EffectiveDate, $ReturnAmount, $ReturnStat, $Supplier_SPID, $shop_SHID, $user_USID]);
        }//try 
        catch (PDOException $e) {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//set ADJUSTMENT

    public function editSupplierReturnStat($ReturnStat, $supplier_SRID)
    {
        try {
            $sql = "UPDATE supplierreturn SET ReturnStat=? WHERE ReturnNo =?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$ReturnStat, $supplier_SRID]);
        }//try 
        catch (PDOException $e) {
            die("Error: Unable to update table: " . $e->getMessage());
        }//catch
    }//edit Supplier return

    public function editSupplierReturnTotal($effective_date, $ReturnAmount, $SupplierName)
    {
        try {
            $sql = "UPDATE supplierreturn SET EffectiveDate=?, ReturnAmount=?, SupplierName=? WHERE SRID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$effective_date, $ReturnAmount, $SupplierName]);
        }//try 
        catch (PDOException $e) {
            die("Error: Unable to update table: " . $e->getMessage());
        }//catch
    }//edit ADJUSTMENT

    public function getReturnMax($shop_id)
    {
        $sql = "SELECT MAX(CAST(SUBSTRING(ReturnNo, 4) AS UNSIGNED)) as MaxReturn FROM supplierreturn WHERE shop_SHID = ?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$shop_id]);

        // Debugging: Log the raw result
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Raw result from getReturnMax: " . print_r($result, true));

        return $result;
    }


    public function getAllReturns($shop_id)
    {
        try {
            $stmt = $this->connect()->prepare("
            SELECT sr.SRID, sr.ReturnNo, sr.EffectiveDate, sr.ReturnAmount, sp.SupplierName, sr.ReturnStat, s.shopName, u.UserName 
            FROM supplierreturn sr
            INNER JOIN shop s ON s.SHID = sr.shop_SHID
            INNER JOIN user u ON u.USID = sr.user_USID
            LEFT JOIN suppliers sp ON sp.SPID = sr.Supplier_SPID
            WHERE sr.shop_SHID = ?"); // Ensure correct filtering by shop_id
            $stmt->execute([$shop_id]);
            $Returns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Debugging: Log the fetched data and the query
            error_log("SQL Query: " . $stmt->queryString);
            error_log("SQL Parameters: " . print_r([$shop_id], true));
            error_log("Fetched Returns: " . print_r($Returns, true));

            return $Returns;
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            die("Error: Unable to Fetch Supplier Return. " . $e->getMessage());
        }
    }

    public function getproducts($shop_id)
    {
        try {
            $stmt = $this->connect()->prepare("SELECT * FROM products WHERE shop_SHID = ?;");
            $stmt->execute([$shop_id]);
            $productData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $productData;
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            return [];
        }
    }


    public function getOnesupplierreturn($return_header_id)
    {
        try {
            $sql = "SELECT * FROM supplierreturn 
            INNER JOIN user ON user.USID = supplierreturn.user_USID
            WHERE SRID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$return_header_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get max adjustment

    //===============================================================================================//
    //====================================== Supplier Return Details =====================================//
    //===============================================================================================//
    public function setReturnDetail($return_qty, $purchase_price, $inventory_id, $variation_id, $return_stat, $total_amount,$Return_No,$batchId)
    {
        try {

            //get SRID
            $SRID = $this->get_srnId($Return_No);
            if (is_array($SRID)) {             
                $SRID = isset($SRID[0]['SRID']) ? $SRID[0]['SRID'] : null;
            }

            //get ProductID
            $prodId = $this->get_ProductId($inventory_id);
            if (is_array($prodId)) {             
                $prodId = isset($prodId[0]['PDID']) ? $prodId[0]['PDID'] : null;
            }

            $sql = "INSERT INTO supplierreturndetails (ReturnQty, UnitPurchasePrice, InventoryID, VariationID, ReturnStat, ReturnAmount,supplierreturn_SRID, ProductID, Batch) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$return_qty, $purchase_price, $inventory_id, $variation_id, $return_stat, $total_amount, $SRID, $prodId, $batchId]); 
            return true;
        } catch (PDOException $e) {
            throw new Exception("Error: Unable to insert into table " . $e->getMessage());
        }
    }

    public function editReturnDetail($prod_qty, $purchase_price, $total_purchase, $variation_id, $product_id, $srn_detail_id, $batch_id)
    {
        try {
            // Debugging: Log the input parameters
            error_log("editReturnDetail called with params: prod_qty={$prod_qty}, purchase_price={$purchase_price}, total_purchase={$total_purchase}, variation_id={$variation_id}, product_id={$product_id}, batch_id={$batch_id}, srn_detail_id={$srn_detail_id}");

            $sql = "UPDATE supplierreturndetails SET ReturnQty=?, UnitPurchasePrice=?, ReturnAmount=?, VariationID=?, ProductID=?, Batch=? WHERE SRDID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$prod_qty, $purchase_price, $total_purchase, $variation_id, $product_id, $batch_id, $srn_detail_id]);

            // Debugging: Log the query execution result
            error_log("Query executed successfully.");

        } catch (PDOException $e) {
            // Debugging: Log the error message
            error_log("Error: Unable to update data: " . $e->getMessage());
            die("Error: Unable to update data: " . $e->getMessage());
        }
    }

    public function editSRNDetailStat($detail_stat, $SRN_header_id)
    {
        try {
            $sql = "UPDATE supplierreturndetails SD INNER JOIN supplierreturn SR ON SR.SRID = SD.supplierreturn_SRID SET SD.ReturnStat = ? WHERE SR.ReturnNo = ?";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$detail_stat, $SRN_header_id]);
        }//try 
        catch (PDOException $e) {
            die("Error: Unable to update table: " . $e->getMessage());
        }//catch
    }//edit ADJUSTMENT

    public function deleteSRNDetail($return_detail_id)
    {
        try
        {
            $sql="DELETE FROM supplierreturndetails WHERE SRDID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$return_detail_id]);
        }//try 
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }

    public function editSRNHeaderVerify($Total,$srn_header_id)
    {
        try 
        {
            $sql="UPDATE supplierreturn SET ReturnAmount=?, ReturnStat=2 WHERE ReturnNo=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$Total, $srn_header_id]);
        }//try
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//edit grn header stat


}//class supplier return