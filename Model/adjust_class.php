<?php 

class Adjustment extends Dbh
{
    public function setAdjustHeader($AdjustNo, $EffectiveDate, $AdjustCount, $AdjustAmount, $AdjustStat, $AdjustmentType_ITID, $shop_SHID, $user_USID)
    {
        try 
        {
            $sql="INSERT INTO adjustheader(AdjustNo, EffectiveDate, AdjustCount, AdjustAmount, AdjustStat, AdjustmentType_ITID, shop_SHID, user_USID) VALUES(?,?,?,?,?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$AdjustNo, $EffectiveDate, $AdjustCount, $AdjustAmount, $AdjustStat, $AdjustmentType_ITID, $shop_SHID, $user_USID]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//set ADJUSTMENT

    public function editAdjustHeaderStat($header_stat, $adjust_header_id)
    {
        try 
        {
            $sql="UPDATE adjustheader SET AdjustStat=? WHERE AHID =?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$header_stat, $adjust_header_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to update table: " . $e->getMessage());
        }//catch
    }//edit ADJUSTMENT

    public function editAdjustHeaderTotal($effective_date, $item_count, $total_price, $adjust_header_id)
    {
        try 
        {
            $sql="UPDATE adjustheader SET EffectiveDate=?, AdjustCount =?, AdjustAmount=? WHERE AHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$effective_date, $item_count, $total_price, $adjust_header_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to update table: " . $e->getMessage());
        }//catch
    }//edit ADJUSTMENT

    public function getAdjustMax($shop_id)
    {
        try
        {
            $sql = "SELECT MAX(AHID) AS MaxAdjust FROM adjustheader WHERE shop_SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get max adjustment

    public function getAllAdjustment($shop_id)
    {
        try
        {
            $sql = "SELECT * FROM adjustheader
            INNER JOIN user ON user.USID = adjustheader.user_USID
            WHERE shop_SHID = ? ORDER BY AHID DESC;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get max adjustment

    public function getOneAdjustHeader($adjust_header_id)
    {
        try
        {
            $sql = "SELECT * FROM adjustheader WHERE AHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$adjust_header_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get max adjustment

//===============================================================================================//
//====================================== Adjustment Details =====================================//
//===============================================================================================//
    public function setAdjustDetail($AdjustProdQty, $UnitPurchasePrice, $UnitSellingPrice, $MnfDate, $ExpDate, $InventoryID, $VariationID, $AdjustStat, $AdjustProdAmount, $products_PDID, $AdjustHeader_AHID, $batch_id)
    {
        try 
        {
            $sql="INSERT INTO adjustproddetails(AdjustProdQty, UnitPurchasePrice, UnitSellingPrice, MnfDate, ExpDate, InventoryID, VariationID, AdjustStat, AdjustProdAmount, products_PDID, AdjustHeader_AHID, batch_id) VALUES(?,?,?,?,?,?,?,?,?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$AdjustProdQty, $UnitPurchasePrice, $UnitSellingPrice, $MnfDate, $ExpDate, $InventoryID, $VariationID, $AdjustStat, $AdjustProdAmount, $products_PDID, $AdjustHeader_AHID, $batch_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//set ADJUSTMENT

    public function editAdjustDetail($AdjustProdQty, $UnitPurchasePrice, $UnitSellingPrice, $MnfDate, $ExpDate, $InventoryID, $VariationID, $RackID, $AdjustProdAmount, $products_PDID, $adjust_detail_id)
    {
        try 
        {
            $sql="UPDATE adjustproddetails SET AdjustProdQty=?, UnitPurchasePrice=?, UnitSellingPrice=?, MnfDate=?, ExpDate=?, InventoryID=?, VariationID=?, RackID=?, AdjustProdAmount=?, products_PDID=? WHERE APID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$AdjustProdQty, $UnitPurchasePrice, $UnitSellingPrice, $MnfDate, $ExpDate, $InventoryID, $VariationID, $RackID, $AdjustProdAmount, $products_PDID, $adjust_detail_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//edit ADJUSTMENT

    public function editAdjustDetailStat($detail_stat, $adjust_header_id)
    {
        try 
        {
            $sql="UPDATE adjustproddetails SET AdjustStat = ? WHERE AdjustHeader_AHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$detail_stat, $adjust_header_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to update table: " . $e->getMessage());
        }//catch
    }//edit ADJUSTMENT

    public function deleteAdjustDetail($adjust_detail_id)
    {
        try
        {
            $sql="DELETE FROM adjustproddetails WHERE APID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$adjust_detail_id]);
        }//try 
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//edit ADJUSTMENT
}//class adjustment