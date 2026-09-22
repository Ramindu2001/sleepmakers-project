<?php 

class GRN extends Dbh
{
    public function setGRNHeader($GRNHeaderNo, $EffectiveDate, $InvoiceNo, $ItemCount, $TotalPurchasePrice, $TotalSellPrice, $GRNStartTime, $GRNEndTime, $GRNStat, $user_USID, $shop_SHID, $Suppliers_SPID, $SuppPayment , $SuppBalance , $excessAmount,$Reference)
    {
        try 
        {
            $sql="INSERT INTO grnheader(GRNHeaderNo, EffectiveDate, InvoiceNo, ItemCount, TotalPurchasePrice, TotalSellPrice, GRNStartTime, GRNEndTime, GRNStat, user_USID, shop_SHID, Suppliers_SPID , SuppPayment , SuppBalance ,excessAmount,refference) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$GRNHeaderNo, $EffectiveDate, $InvoiceNo, $ItemCount, $TotalPurchasePrice, $TotalSellPrice, $GRNStartTime, $GRNEndTime, $GRNStat, $user_USID, $shop_SHID, $Suppliers_SPID, $SuppPayment , $SuppBalance , $excessAmount,$Reference]);
        }//try
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//save category

    public function editGRNHeaderStat($grn_stat, $grn_header_id, $SuppPayment, $SuppBalance, $excessAmount, $PurchDiscType, $PurchDisc, $TotalDisc)
    {
        try 
        {
            $sql = "UPDATE grnheader 
                    SET GRNStat=?, SuppPayment=?, SuppBalance=?, excessAmount=?, PurchDiscType=?, PurchDisc=?, TotalDisc=? 
                    WHERE GHID=?;";
            
            $stmt = $this->connect()->prepare($sql);
            
            // ✅ Corrected the parameter order
            $stmt->execute([$grn_stat, $SuppPayment, $SuppBalance, $excessAmount, $PurchDiscType, $PurchDisc, $TotalDisc, $grn_header_id]);           
            
            // echo "SaleDiscountType: " . htmlspecialchars($PurchDiscType) . 
            //     " SaleDiscount: " . htmlspecialchars($PurchDisc) . 
            //     " SaleDiscountTotal: " . htmlspecialchars($TotalDisc);

        } // try
        catch (PDOException $e)
        {
            die("Error: Unable to update data: " . $e->getMessage()); // ✅ Fixed error message
        } // catch
    } // editGRNHeaderStat


    Public function grnEditSupplier($grnid,$supplier_id)
    {
        try 
        {
            $sql="UPDATE grnheader SET Suppliers_SPID=? WHERE GHID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$supplier_id , $grnid]);
        }//try
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }

    public function editGRNHeaderVerify($effective_date, $item_count, $TotalPurchasePrice, $TotalOrignalPurchasePrice, $TotalSellPrice, $grn_header_id)
    {
        try 
        {
            $sql="UPDATE grnheader SET EffectiveDate=?, ItemCount=?, TotalPurchasePrice=?, TotalOriginalPurchase =?, TotalSellPrice=?, GRNStat=2 WHERE GHID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$effective_date, $item_count, $TotalPurchasePrice, $TotalOrignalPurchasePrice, $TotalSellPrice, $grn_header_id]);
        }//try
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//edit grn header stat

    public function getGRNCount($shop_id)
    {
        try{
            $sql = "SELECT count(GHID) AS GRNCount FROM grnheader WHERE shop_SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get category count

    public function getAllGRNHeader($shop_id)
    {
        try{
            $sql = "SELECT * FROM grnheader 
            INNER JOIN user ON user.USID = grnheader.user_USID
            INNER JOIN suppliers ON suppliers.SPID = grnheader.Suppliers_SPID
            WHERE grnheader.shop_SHID = ? ORDER BY GHID DESC LIMIT 50;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get category count

    public function getOneGRNHeader($grn_header_id)
    {
        try{
            $sql = "SELECT * FROM grnheader 
            INNER JOIN user ON user.USID = grnheader.user_USID
            INNER JOIN suppliers ON suppliers.SPID = grnheader.Suppliers_SPID
            WHERE GHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$grn_header_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get category count

    //=====================================================================================//
    //==================================== GRN Details ====================================//
    //=====================================================================================//
    public function check_grn_status($grn_header_id)
    {
        $sql="SELECT GRNStat FROM grnheader WHERE GHID=?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$grn_header_id]);
        return $stmt->fetchAll();
    }
    public function setGRNDetails($InitQty, $CurrentQty, $UnitPurchasePrice, $UnitLabelPrice, $UnitSellPrice, $TotalPurchasePrice, $TotalSellPrice, $MnfDate, $ExpDate, $GRNStat, $VariationID, $products_PDID, $GRNHeader_GHID, $Rack_RKID)
    {
        try 
        {
            $sql="INSERT INTO grndetails(InitQty, CurrentQty, UnitPurchasePrice, UnitLabelPrice, UnitSellPrice, TotalPurchasePrice, TotalSellPrice, MnfDate, ExpDate, GRNStat, VariationID, products_PDID, GRNHeader_GHID, Rack_RKID) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $query=$stmt->execute([$InitQty, $CurrentQty, $UnitPurchasePrice, $UnitLabelPrice, $UnitSellPrice, $TotalPurchasePrice, $TotalSellPrice, $MnfDate, $ExpDate, $GRNStat, $VariationID, $products_PDID, $GRNHeader_GHID, $Rack_RKID]);
            
        }//try
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
        if($query)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }//save category

    public function editGRNDetails($InitQty, $CurrentQty, $UnitPurchasePrice, $UnitLabelPrice, $UnitSellPrice, $TotalPurchasePrice, $TotalSellPrice, $MnfDate, $ExpDate, $VariationID, $products_PDID, $Rack_RKID, $grn_detail_id)
    {
        try 
        {
            $sql="UPDATE grndetails SET InitQty=?, CurrentQty=?, UnitPurchasePrice=?, UnitLabelPrice=?, UnitSellPrice=?, TotalPurchasePrice=?, TotalSellPrice=?, MnfDate=?, ExpDate=?, VariationID=?, products_PDID=?, Rack_RKID=? WHERE GDID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$InitQty, $CurrentQty, $UnitPurchasePrice, $UnitLabelPrice, $UnitSellPrice, $TotalPurchasePrice, $TotalSellPrice, $MnfDate, $ExpDate, $VariationID, $products_PDID, $Rack_RKID, $grn_detail_id]);
        }//try
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//edit category

    public function editGRNDetailStat($grn_stat, $grn_header_id)
    {
        try 
        {
            $sql="UPDATE grndetails SET GRNStat=? WHERE GRNHeader_GHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$grn_stat, $grn_header_id]);
        }//try
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//edit grn header stat

    //move this shop's GRN to a new status only while it is still in one of $allowed_stats;
    //returns 0 when the GRN had already moved on (verified / cancelled / double submit)
    public function editGRNHeaderStatIfIn($grn_stat, $grn_header_id, $shop_id, $allowed_stats)
    {
        try
        {
            $placeholders = implode(',', array_fill(0, count($allowed_stats), '?'));
            $sql="UPDATE grnheader SET GRNStat = ? WHERE GHID = ? AND shop_SHID = ? AND GRNStat IN (".$placeholders.");";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute(array_merge([$grn_stat, $grn_header_id, $shop_id], $allowed_stats));
            return $stmt->rowCount();
        }//try
        catch (PDOException $e)
        {
            die("Error: Unable to update data: " . $e->getMessage());
        }//catch
    }//edit grn header stat if still allowed

    public function beginDbTransaction()
    {
        $this->connect()->beginTransaction();
    }

    public function commitDbTransaction()
    {
        $this->connect()->commit();
    }

    public function rollbackDbTransaction()
    {
        if($this->connect()->inTransaction())
        {
            $this->connect()->rollBack();
        }
    }

    public function deleteGRNDetails($grn_detail_id)
    {
        try 
        {
            $sql="DELETE FROM grndetails WHERE GDID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$grn_detail_id]);
        }//try
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//edit category

    public function setCreditDebitSupplier($EffectiveDate,$amount,$date,$invoice_header_id,$payment,$SupplierID,$user)
    {
        try 
        {
            $shop_SHID = $_SESSION['shop_id'];
            $sql="INSERT INTO `creditsupplier`(`EffectiveDate`, `DebitAmount`, `Balance`, `SubmitDate`, `invoice_header_id`, `pay_m_id`,  `Supplier_ID`, `user_USID`,`shop_SHID`) VALUES ('$EffectiveDate','$amount','$amount','$date','$invoice_header_id','$payment','$SupplierID','$user','$shop_SHID');";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }


}//class GRN