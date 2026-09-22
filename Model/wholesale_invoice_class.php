<?php 
class wholesale_invoice extends Dbh
{
    public function select_batch_with_pro_id($pro_id,$shop_id,$minus=null,$commonStock=null,$company=null)
    {
        try 
        {
            
            if($minus==1)
            {
                    $sql="SELECT i.*,i.BatchID AS batch, ph.SellingPrice AS rate , ph.PurchasePrice AS Costrate FROM `inventory` i 
                    INNER JOIN pricehistory ph ON ph.Inventory_INID=i.INID    
                    WHERE i.products_PDID='$pro_id' AND i.is_default=1 AND i.shop_SHID='$shop_id'";
                    if ($commonStock==1) 
                    {
                        $sql="SELECT i.*,i.BatchID AS batch, ph.SellingPrice AS rate FROM `inventory` i 
                        INNER JOIN pricehistory ph ON ph.Inventory_INID=i.INID
                        INNER JOIN shop s ON s.SHID = i.shop_SHID       
                        WHERE i.products_PDID='$pro_id' AND i.CurrentQty > 0 AND s.Company_CMID='$company'";
                    }
            }
            else
            {
                $sql="SELECT i.*,i.BatchID AS batch, ph.SellingPrice AS rate , ph.PurchasePrice AS Costrate FROM `inventory` i 
                INNER JOIN pricehistory ph ON ph.Inventory_INID=i.INID
                WHERE i.products_PDID='$pro_id' AND i.CurrentQty > 0 AND i.shop_SHID='$shop_id'";
                if ($commonStock==1) 
                {
                    $sql="SELECT i.*,i.BatchID AS batch, ph.SellingPrice AS rate FROM `inventory` i 
                    INNER JOIN pricehistory ph ON ph.Inventory_INID=i.INID
                    INNER JOIN shop s ON s.SHID = i.shop_SHID       
                    WHERE i.products_PDID='$pro_id' AND i.CurrentQty > 0 AND s.Company_CMID='$company'";
                }
            }
            
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to Select Batch: " . $e->getMessage());
        }//catch
    }

    public function userBillAmount($user_id)
    {
        $date=date("Y-m-d");
        $sql="SELECT SUM(NetAmount) AS totalamount FROM `invoiceheader` WHERE user_USID='$user_id' AND InvStat=1 AND EffectiveDate='$date'";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function salesSource()
    {
        try 
        {
            $sql="SELECT * FROM `salessources`";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to Select Batch: " . $e->getMessage());
        }//catch
    }
    public function SelectRow($shop_id)
    {           
        try 
        {
            $sql="SELECT ih.*, sm.*, c.*
          FROM invoiceheader ih
          LEFT JOIN salesmans sm ON sm.SLID = ih.Salesmans_SLID
          LEFT JOIN customers c ON c.CTID = ih.customers_CTID
          WHERE ih.shop_SHID = '$shop_id'
          ORDER BY ih.IHID DESC";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to Select Batch: " . $e->getMessage());
        }//catch
    }
    public function previouseInvoice($shop_id,$invoiceID)
    {           
        try 
        {
            $sql="SELECT ih.IHID
                FROM invoiceheader ih
                WHERE ih.shop_SHID = '$shop_id'
                AND ih.IHID < $invoiceID
                ORDER BY ih.IHID DESC
                LIMIT 1;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to Select Batch: " . $e->getMessage());
        }//catch
    }
    public function nextInvoice($shop_id,$invoiceID)
    {           
        try 
        {
            $sql="SELECT ih.IHID
                FROM invoiceheader ih
                WHERE ih.shop_SHID = '$shop_id'
                AND ih.IHID > $invoiceID
                ORDER BY ih.IHID ASC
                LIMIT 1;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to Select Batch: " . $e->getMessage());
        }//catch
    }
    public function maxminInvoice($shop_id)
    {           
        try 
        {
            $sql="SELECT MAX(ih.IHID) AS maxIHID, MIN(ih.IHID) AS minIHID
                FROM invoiceheader ih
                WHERE ih.shop_SHID = '$shop_id'
                ORDER BY ih.IHID ASC
                LIMIT 1;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to Select Batch: " . $e->getMessage());
        }//catch
    }

    public function select_with_pro_id_batch_id($pro_id,$batch_id,$hasbatchNo=1)
    {
        
        try 
        {            
                    $sql="SELECT ph.SellingPrice AS rate, ph.PurchasePrice AS Costrate, i.CurrentQty AS avlQty, i.BatchID AS batch,p.* FROM `pricehistory` ph 
                    INNER JOIN inventory i ON i.INID=ph.Inventory_INID
                    INNER JOIN products p ON p.PDID=i.products_PDID
                    WHERE i.INID='$batch_id' AND i.products_PDID='$pro_id';";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
            // return $sql;
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to Select Batch: " . $e->getMessage());
        }//catch
    }
    public function getAvlQty($pro_id,$shop_id)
    {
        
        try 
        {
            $sql="SELECT SUM(i.CurrentQty) AS avlQty, i.* FROM inventory i
                    WHERE i.products_PDID='$pro_id' AND i.shop_SHID='$shop_id' GROUP BY i.products_PDID";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
            // return $sql;
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to Select Batch: " . $e->getMessage());
        }//catch
    }
    public function setInvoiceHeader( $EffectiveDate,$BillNo,$InvStartTime,$InvEndTime,$InvItemCount,$GrossAmount,$lineDiscount,$FixedDiscount,$DiscountAmount,$deliveryCharge,$otheCharge,$NetAmount,$CustPayment,$CustBalance,$InvStat,$user_USID,$customers_CTID,$Salesmans_SLID,$shop_SHID,$CashCounter_CCID,$remarks,$excessamount,$returnAmount,$return_header_id,$saleDiscountType,$is_delivery,$deliveryPartner,$sales_source)
    {
        try 
        {
            if($saleDiscountType==1)
            {
                // percentage discount
                
                $sql="INSERT INTO `invoiceheader`(`Inv_Type`,`EffectiveDate`, `BillNo`, `InvStartTime`, `InvEndTime`, `InvItemCount`, `GrossAmount`, `lineDiscount`, `PercentDiscount`, `DiscountAmount`, `deliveryCharge`, `otherCharge`, `NetAmount`, `CustPayment`, `CustBalance`, `InvStat`, `user_USID`, `customers_CTID`, `Salesmans_SLID`, `shop_SHID`, `CashCounter_CCID`,`remarks`,`excessAmount`,`returnAmount`,`ReturnHeader_RHID`,`is_delivery`,`deliveryPartner`,`sales_source`,`discountType`) VALUES ('1','$EffectiveDate','$BillNo','$InvStartTime','$InvEndTime','$InvItemCount','$GrossAmount','$lineDiscount','$FixedDiscount','$DiscountAmount','$deliveryCharge','$otheCharge','$NetAmount','$CustPayment','$CustBalance','$InvStat','$user_USID','$customers_CTID','$Salesmans_SLID','$shop_SHID','$CashCounter_CCID','$remarks','$excessamount','$returnAmount','$return_header_id','$is_delivery','$deliveryPartner','$sales_source','1');";
            }
            else
            {
                // fixed discount
                $sql="INSERT INTO `invoiceheader`(`Inv_Type`,`EffectiveDate`, `BillNo`, `InvStartTime`, `InvEndTime`, `InvItemCount`, `GrossAmount`, `lineDiscount`, `FixedDiscount`, `DiscountAmount`, `deliveryCharge`, `otherCharge`, `NetAmount`, `CustPayment`, `CustBalance`, `InvStat`, `user_USID`, `customers_CTID`, `Salesmans_SLID`, `shop_SHID`, `CashCounter_CCID`,`remarks`,`excessAmount`,`returnAmount`,`ReturnHeader_RHID`,is_delivery,deliveryPartner,`sales_source`,`discountType`) VALUES ('1','$EffectiveDate','$BillNo','$InvStartTime','$InvEndTime','$InvItemCount','$GrossAmount','$lineDiscount','$FixedDiscount','$DiscountAmount','$deliveryCharge','$otheCharge','$NetAmount','$CustPayment','$CustBalance','$InvStat','$user_USID','$customers_CTID','$Salesmans_SLID','$shop_SHID','$CashCounter_CCID','$remarks','$excessamount','$returnAmount','$return_header_id','$is_delivery','$deliveryPartner','$sales_source','2');";
            }
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $sql;
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data header invoice: " . $e->getMessage());
        }//catch
    }//save invoice header

    public function getProductInfor($PDID)
    {        
        try 
        {
            $sql="SELECT * FROM `products` WHERE PDID='$PDID';";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to Select Batch: " . $e->getMessage());
        }//catch
    }

    public function setInvoiceDetails($sqllQty,$unitPrice,$sellAmount,$discount,$totalDiscount,$total,$invoiceHeader,$pro_id,$batc_id,$discountType,$prodDes,$shop_id,$Inventory_INID,$Item_Name)
    {
        try 
        {
            if($discountType==1)
            {
                $sql="INSERT INTO `invoicedetails`(`Item_Name`, `SellQty`, `UnitPrice`, `SellAmount`, `PercentDiscount`, `SoldAmount`, `InvoiceHeader_IHID`, `products_PDID`, `batch_no`,`item_des`,`disc_type`,`shop_id`,`Inventory_INID`) VALUES ('$Item_Name','$sqllQty','$unitPrice','$sellAmount','$discount','$total','$invoiceHeader','$pro_id','$batc_id','$prodDes','$discountType','$shop_id','$Inventory_INID');";
            }
            else
            {
                $sql="INSERT INTO `invoicedetails`(`Item_Name`, `SellQty`, `UnitPrice`, `SellAmount`, `DirectDiscount`, `SellDiscount`, `SoldAmount`, `InvoiceHeader_IHID`, `products_PDID`, `batch_no`,`item_des`,`disc_type`,`shop_id`,`Inventory_INID`) VALUES ('$Item_Name','$sqllQty','$unitPrice','$sellAmount','$discount','$totalDiscount','$total','$invoiceHeader','$pro_id','$batc_id','$prodDes','$discountType','$shop_id','$Inventory_INID');";
            }            
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return true;
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }

    public function select_inventory($pro_id, $batch_id)
    { 
        try 
        {
            $sql="SELECT i.INID, i.CurrentQty AS avlQty, i.BillQty AS BillQty FROM `pricehistory` ph 
            INNER JOIN inventory i ON i.INID=ph.Inventory_INID
            WHERE i.INID='$batch_id' AND i.products_PDID='$pro_id';";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to Select Batch: " . $e->getMessage());
        }//catch
    }

    public function select_docno($shop_id)
    {
        try 
        {
            $sql="SELECT * FROM `docno` WHERE shop_id='$shop_id';";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to Select doc: " . $e->getMessage());
        }//catch
    }

    public function getSequence($num) 
    {
        return sprintf("%'.06d", $num);
    }

    public function getSaleSettings($shop_id)
    {
        try 
        {
            $sql="SELECT * FROM `salesettings` WHERE shop_id='$shop_id' ORDER BY SSID DESC LIMIT 1;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to Select Sale Settings: " . $e->getMessage());
        }//catch
    }
    public function insert_doc_no($shop_id)
    {
        try 
        {
            $sql="INSERT INTO docno (shop_id) VALUE ('$shop_id')";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }
    }

    public function doc_update($num,$shop_id)
    {
        try 
        {
            $sql="UPDATE docno SET ws_no ='$num'  WHERE shop_id='$shop_id'";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }
    }
    public function select_product($pro_id)
    {
        try 
        {
            $sql="SELECT * FROM `products` WHERE PDID='$pro_id';";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to Select Batch: " . $e->getMessage());
        }//catch
    }
    public function update_inventory($newavlQty,$newBillQty, $inventory_id)
    {
        try 
        {
            $sql="UPDATE `inventory` SET `CurrentQty`='$newavlQty',`BillQty`='$newBillQty' WHERE INID='$inventory_id'";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return true;
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }

    public function setCreditCust($EffectiveDate,$amount,$date,$invoice_header_id,$payment,$customers_CTID,$user,$paymentMode=1)
    { 
        $shop_id=$_SESSION["shop_id"];
        try 
        {
            $sql="INSERT INTO `creditcustomer`(`EffectiveDate`, `CreditAmount`, `Balance`, `SubmitDate`, `invoice_header_id`, `pay_m_id`,  `Customers_CTID`, `user_USID`,`shop_SHID`,`paymentMode`) VALUES ('$EffectiveDate','$amount','$amount','$date','$invoice_header_id','$payment','$customers_CTID','$user','$shop_id','$paymentMode');";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $sql;
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }
    public function setCreditCustDebit($EffectiveDate,$amount,$date,$invoice_header_id,$payment,$customers_CTID,$user,$paymentMode=1)
    {
        $shop_id=$_SESSION["shop_id"];
        try 
        {
            $sql="INSERT INTO `creditcustomer`(`EffectiveDate`, `DebitAmount`, `Balance`, `SubmitDate`, `invoice_header_id`, `pay_m_id`,  `Customers_CTID`, `user_USID`,`shop_SHID`, `paymentMode`) VALUES ('$EffectiveDate','$amount','$amount','$date','$invoice_header_id','$payment','$customers_CTID','$user','$shop_id', '$paymentMode');";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return true;
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data creditcustomer: " . $e->getMessage());
        }//catch
    }

    public function invoice_remark($remarks,$user,$invoice_header_id,$from_invoice=0)
    {
        try 
        {
            $date=date("Y-m-d H:i:s");
            $sql="INSERT INTO `invoice_remarks`(`remarks`, `from_invoice`, `user_USID`, `invoiceheader_IHID`, `date_time`) VALUES ('$remarks','$from_invoice','$user','$invoice_header_id','$date');";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return true;
        }//try 
        catch (PDOException $e) 
        {
            return "Error: Unable to insert data invoice_remarks: " . $e->getMessage();
            // die("Error: Unable to insert data invoice_remarks: " . $e->getMessage());
        }//catch
    }

    public function updateremarks($IRID, $remarks)
    {
        try 
        {
            $sql="UPDATE invoice_remarks SET remarks = ? WHERE IRID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$remarks, $IRID]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }

    //===================== Prescription ====================//
    //added by chinthana 2024-10-22
    public function updatePrescription($invoice_id, $prescription_id)
    {
        try 
        {
            $sql="UPDATE prescriptionheader SET invoice_IHID = ? WHERE PRHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$invoice_id, $prescription_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//
}
?>