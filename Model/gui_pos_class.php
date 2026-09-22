<?php 
class guiPOS extends Dbh
{
    public function select_docno($shop_id)
    {
        try 
        {
            $sql="SELECT * FROM `docno` WHERE shop_id='$shop_id' ORDER BY DNID DESC LIMIT 1;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to Select doc: " . $e->getMessage());
        }//catch
    }

    public function update_docno($shop_id,$tmp_no=null,$org_no=null)
    {
        try 
        {
            if($org_no!=null)
            {
                $sql="UPDATE docno SET org_no='$org_no' WHERE shop_id='$shop_id';";
            }
            else if($tmp_no!=null)
            {
                $sql="UPDATE docno SET tmp_no='$tmp_no' WHERE shop_id='$shop_id';";
            }            
            $stmt = $this->connect()->prepare($sql);      
            $stmt->execute();      
            return true;
        }//try 
        catch (PDOException $e) 
        {
            return false;
            // die("Error: Unable to Select doc: " . $e->getMessage());
        }
    }
    public function insert_doc_no($shop_id)
    {
        try 
        {
            $sql="INSERT INTO docno (shop_id) VALUE ('$shop_id')";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return true;
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }
    }
    public function getSequence($num) 
    {
        return sprintf("%'.06d", $num);
    }
    public function getSaleSettings($shop_id)
    {
        try 
        {
            $sql="SELECT * FROM `salesettings` WHERE shop_id='$shop_id' AND settingStat=1 ORDER BY SSID DESC LIMIT 1;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to Select Sale Settings: " . $e->getMessage());
        }//catch
    }

    public function getLatestIHID($user,$shop_id)
    {
        try 
        {
            $sql="SELECT IHID FROM `invoiceheader` WHERE shop_SHID='$shop_id' AND user_USID='$user' ORDER BY IHID DESC LIMIT 1;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            $fetch=$stmt->fetchAll();
            return $fetch[0]["IHID"];
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to Select Sale Settings: " . $e->getMessage());
        }//catch

    }
    public function inventory_consumption($invoice_header,$status,$INID,$original_price,$price,$batchNo,$product_ID,$qty,$shop_id)
    {
        try 
        {
            $sql="INSERT INTO `inventory_consumption`(`invoice_headerID`, `status`, `inventory_INID`, `price`,`sold_price`, `batch No`, `product_PDID`, `quantity`, `shop_SHID`) VALUES ('$invoice_header','$status','$INID','$original_price','$price','$batchNo','$product_ID','$qty','$shop_id')";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return true;
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data Inventory Consumption: " . $e->getMessage());
        }
    }
    public function selectBatch($INID)
    {
        try 
        {
            $sql="SELECT BatchID FROM `inventory` WHERE INID='$INID' ";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            $fetch=$stmt->fetchAll();
            return $fetch[0]["BatchID"];
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to Select Sale Settings: " . $e->getMessage());
        }//catch
    }
    public function setInvoiceDetails($sqllQty,$unitPrice,$sellAmount,$discount,$totalDiscount,$total,$invoiceHeader,$pro_id,$discountType,$prodDes,$shop_id,$Item_Name,$ItemType=1)
    {
        try 
        {
            if($discountType==1)
            {
                $sql="INSERT INTO `invoicedetails`(`Item_Name`, `SellQty`, `UnitPrice`, `SellAmount`, `PercentDiscount`, `SoldAmount`, `InvoiceHeader_IHID`, `products_PDID`, `item_des`,`disc_type`,`shop_id`,`ItemType`) VALUES ('$Item_Name','$sqllQty','$unitPrice','$sellAmount','$discount','$total','$invoiceHeader','$pro_id','$prodDes','$discountType','$shop_id','$ItemType');";
            }
            else
            {
                $sql="INSERT INTO `invoicedetails`(`Item_Name`, `SellQty`, `UnitPrice`, `SellAmount`, `DirectDiscount`, `SellDiscount`, `SoldAmount`, `InvoiceHeader_IHID`, `products_PDID`,`item_des`,`disc_type`,`shop_id`,`ItemType`) VALUES ('$Item_Name','$sqllQty','$unitPrice','$sellAmount','$discount','$totalDiscount','$total','$invoiceHeader','$pro_id','$prodDes','$discountType','$shop_id','$ItemType');";
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
    public function selectInventory($product_id, $price, $shop_id, $company_id, $is_commonStock, $stockType, $is_default = false)
    {
        try {
            $order = ($stockType == 3) ? "DESC" : "ASC"; 
            $defaultCondition = $is_default ? "AND i.is_default = 1" : "AND i.CurrentQty > 0"; 
            $priceCondition = $is_default ? "" : "AND ph.SellingPrice = '$price'";  
            $limit = $is_default ? "LIMIT 1" : ""; 

            $sql = "SELECT i.*, ph.SellingPrice FROM inventory i 
                    INNER JOIN pricehistory ph ON ph.Inventory_INID = i.INID
                    WHERE i.`products_PDID` = '$product_id' 

                    $priceCondition 
                    AND i.shop_SHID = '$shop_id'
                    $defaultCondition 
                    ORDER BY i.INID $order $limit;";

            if ($is_commonStock == 1) {
                $sql = "SELECT i.*, ph.SellingPrice FROM inventory i 
                        INNER JOIN pricehistory ph ON ph.Inventory_INID = i.INID
                        INNER JOIN shop s ON s.SHID = i.shop_SHID 
                        WHERE i.`products_PDID` = '$product_id' 


                        $priceCondition 
                        AND s.Company_CMID = '$company_id'
                        $defaultCondition 
                        ORDER BY i.INID $order $limit;";
            }

            $stmt = $this->connect()->prepare($sql);
            
            $stmt->execute();

            // return $sql;
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            die("Error: Unable to Select Inventory: " . $e->getMessage());
        }
    }

    public function getInventory($product_id,$price, $shop_id, $company_id, $is_commonStock, $stockType, $is_default, $is_expire)
    {
        try 
        {
            $condition = " ";
            $date = date("Y-m-d");
        
            $condition .= $is_default ? " AND i.is_default=1" : " AND i.CurrentQty > 0";

            if ($is_expire) {
                $condition .= " AND (ph.ExpDate IS NULL OR ph.ExpDate = '0000-00-00' OR ph.ExpDate > '$date')";
            }

            $order = ($stockType == 3) ? "DESC" : "ASC"; // LIFO vs FIFO

            $sql="SELECT *, SUM(i.CurrentQty) AS TotalCurrentQty FROM inventory i 
            INNER JOIN pricehistory ph ON ph.Inventory_INID = i.INID
            ";
            if ($is_commonStock == 1) {
                $sql .= "INNER JOIN shop s ON s.SHID = i.shop_SHID 
                         WHERE ph.SellingPrice='$price' AND i.products_PDID = '$product_id' 
                         AND s.Company_CMID = '$company_id' $condition";
            } else {
                $sql .= "WHERE ph.SellingPrice='$price' AND i.products_PDID = '$product_id' 
                         AND i.shop_SHID = '$shop_id' $condition ";
            }
            $sql .= "GROUP BY ph.SellingPrice ORDER BY i.INID $order ";
        
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to Select Inventory Data: " . $e->getMessage());
        }//catch
    }
    public function getLatestHIID($user,$shop_id)
    {
        try 
        {
            $sql="SELECT HIID FROM `hold_invoice` WHERE shop_SHID='$shop_id' AND user_USID='$user' ORDER BY HIID DESC LIMIT 1;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            $fetch=$stmt->fetchAll();
            return $fetch[0]["HIID"];
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to Select Sale Settings: " . $e->getMessage());
        }//catch

    }
    function fetchInventoryData($product_id, $shop_id, $company_id, $is_commonStock, $stockType, $is_default, $is_expire,$price) {
        $condition = " ";
        $date = date("Y-m-d");
    
        $condition .= $is_default ? " AND i.is_default=1" : " AND i.CurrentQty > 0";
    
        if ($is_expire && $is_default==false) {
            $condition .= " AND (ph.ExpDate IS NULL OR ph.ExpDate = '0000-00-00' OR ph.ExpDate > '$date')";
        }
    
        $order = ($stockType == 3) ? "DESC" : "ASC"; // LIFO vs FIFO
        
        $sql = "SELECT i.INID, SUM(i.CurrentQty) AS TotalCurrentQty, ph.SellingPrice AS SellingPrice 
                FROM `inventory` i
                INNER JOIN pricehistory ph ON ph.Inventory_INID = i.INID ";
    
        if ($is_commonStock == 1) {
            $sql .= "INNER JOIN shop s ON s.SHID = i.shop_SHID 
                     WHERE ph.SellingPrice='' AND i.products_PDID = '$product_id' 
                     AND s.Company_CMID = '$company_id' $condition ";
        } else {
            $sql .= "WHERE ph.SellingPrice='' AND i.products_PDID = '$product_id' 
                     AND i.shop_SHID = '$shop_id' $condition ";
        }
    
        $sql .= "GROUP BY ph.SellingPrice ORDER BY i.INID $order ";
        
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();// Return all records
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
    public function update_return_status($status, $RIHID)
    {
        try 
        {
            $sql="UPDATE `retrun_invoice_header` SET `return_header_stat`='$status' WHERE RIHID='$RIHID' ;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return true;
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to Update Return: " . $e->getMessage());
        }
    }
    public function setInvoiceHeader($InvoiceNo, $EffectiveDate,$BillNo,$InvStartTime,$InvEndTime,$InvItemCount,$GrossAmount,$lineDiscount,$FixedDiscount,$DiscountAmount,$deliveryCharge,$otheCharge,$NetAmount,$CustPayment,$CustBalance,$InvStat,$user_USID,$customers_CTID,$Salesmans_SLID,$shop_SHID,$CashCounter_CCID,$remarks,$excessamount,$returnAmount,$return_header_id,$saleDiscountType,$is_delivery,$deliveryPartner,$sales_source,$Inv_Type=2,$HIID=0)
    {
        try 
        {
            if($saleDiscountType==1)
            {
                // percentage discount
                
                $sql="INSERT INTO `invoiceheader`(`InvoiceNo`,`Inv_Type`,`EffectiveDate`, `BillNo`, `InvStartTime`, `InvEndTime`, `InvItemCount`, `GrossAmount`, `lineDiscount`, `PercentDiscount`, `DiscountAmount`, `deliveryCharge`, `otherCharge`, `NetAmount`, `CustPayment`, `CustBalance`, `InvStat`, `user_USID`, `customers_CTID`, `Salesmans_SLID`, `shop_SHID`, `CashCounter_CCID`,`remarks`,`excessAmount`,`returnAmount`,`ReturnHeader_RHID`,`is_delivery`,`deliveryPartner`,`sales_source`,`HIID`,`discountType`) VALUES ('$InvoiceNo','$Inv_Type','$EffectiveDate','$BillNo','$InvStartTime','$InvEndTime','$InvItemCount','$GrossAmount','$lineDiscount','$FixedDiscount','$DiscountAmount','$deliveryCharge','$otheCharge','$NetAmount','$CustPayment','$CustBalance','$InvStat','$user_USID','$customers_CTID','$Salesmans_SLID','$shop_SHID','$CashCounter_CCID','$remarks','$excessamount','$returnAmount','$return_header_id','$is_delivery','$deliveryPartner','$sales_source','$HIID','1');";
            }
            else
            {
                // fixed discount
                $sql="INSERT INTO `invoiceheader`(`InvoiceNo`,`Inv_Type`,`EffectiveDate`, `BillNo`, `InvStartTime`, `InvEndTime`, `InvItemCount`, `GrossAmount`, `lineDiscount`, `FixedDiscount`, `DiscountAmount`, `deliveryCharge`, `otherCharge`, `NetAmount`, `CustPayment`, `CustBalance`, `InvStat`, `user_USID`, `customers_CTID`, `Salesmans_SLID`, `shop_SHID`, `CashCounter_CCID`,`remarks`,`excessAmount`,`returnAmount`,`ReturnHeader_RHID`,is_delivery,deliveryPartner,`sales_source`,`HIID`,`discountType`) VALUES ('$InvoiceNo','$Inv_Type','$EffectiveDate','$BillNo','$InvStartTime','$InvEndTime','$InvItemCount','$GrossAmount','$lineDiscount','$FixedDiscount','$DiscountAmount','$deliveryCharge','$otheCharge','$NetAmount','$CustPayment','$CustBalance','$InvStat','$user_USID','$customers_CTID','$Salesmans_SLID','$shop_SHID','$CashCounter_CCID','$remarks','$excessamount','$returnAmount','$return_header_id','$is_delivery','$deliveryPartner','$sales_source','$HIID','2');";
            }
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return true;
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }
    }
    public function setCreditCust($EffectiveDate,$amount,$date,$invoice_header_id,$payment,$customers_CTID,$user,$paymentMode=1)
    {
        $shop_id=$_SESSION["shop_id"];
        try 
        {
            $sql="INSERT INTO `creditcustomer`(`EffectiveDate`, `CreditAmount`, `Balance`, `SubmitDate`, `invoice_header_id`, `pay_m_id`,  `Customers_CTID`, `user_USID`,`shop_SHID`,`paymentMode`) VALUES ('$EffectiveDate','$amount','$amount','$date','$invoice_header_id','$payment','$customers_CTID','$user','$shop_id', '$paymentMode');";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $sql;
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data creditcustomer: " . $e->getMessage());
        }//catch
    }

    public function deleteHoldInvoiceDetail($HIID)
    {
        try 
        {
           
                // fixed discount
                $sql="DELETE FROM `selldetail` WHERE sellHeader_SHID='$HIID';"; 
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return true;
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }
    }
    public function setHoldInvoiceHeader($InvoiceNo, $EffectiveDate,$BillNo,$InvStartTime,$InvEndTime,$InvItemCount,$GrossAmount,$lineDiscount,$FixedDiscount,$DiscountAmount,$deliveryCharge,$otheCharge,$NetAmount,$CustPayment,$CustBalance,$InvStat,$user_USID,$customers_CTID,$Salesmans_SLID,$shop_SHID,$CashCounter_CCID,$remarks,$excessamount,$returnAmount,$return_header_id,$saleDiscountType,$is_delivery,$deliveryPartner,$sales_source,$Inv_Type=2)
    {
        try 
        {
            if($saleDiscountType==1)
            {
                // percentage discount
                
                $sql="INSERT INTO `hold_invoice`(`Temp_No`,`Inv_Type`,`EffectiveDate`, `BillNo`, `InvStartTime`, `InvEndTime`, `InvItemCount`, `GrossAmount`, `lineDiscount`, `PercentDiscount`, `DiscountAmount`, `deliveryCharge`, `otherCharge`, `NetAmount`, `CustPayment`, `CustBalance`, `InvStat`, `user_USID`, `customers_CTID`, `Salesmans_SLID`, `shop_SHID`, `CashCounter_CCID`,`remarks`,`excessAmount`,`returnAmount`,`ReturnHeader_RHID`,`is_delivery`,`deliveryPartner`,`sales_source`) VALUES ('$BillNo','$Inv_Type','$EffectiveDate','$InvoiceNo','$InvStartTime','$InvEndTime','$InvItemCount','$GrossAmount','$lineDiscount','$FixedDiscount','$DiscountAmount','$deliveryCharge','$otheCharge','$NetAmount','$CustPayment','$CustBalance','$InvStat','$user_USID','$customers_CTID','$Salesmans_SLID','$shop_SHID','$CashCounter_CCID','$remarks','$excessamount','$returnAmount','$return_header_id','$is_delivery','$deliveryPartner','$sales_source');";
            }
            else
            {
                // fixed discount
                $sql="INSERT INTO `hold_invoice`(`Temp_No`,`Inv_Type`,`EffectiveDate`, `BillNo`, `InvStartTime`, `InvEndTime`, `InvItemCount`, `GrossAmount`, `lineDiscount`, `FixedDiscount`, `DiscountAmount`, `deliveryCharge`, `otherCharge`, `NetAmount`, `CustPayment`, `CustBalance`, `InvStat`, `user_USID`, `customers_CTID`, `Salesmans_SLID`, `shop_SHID`, `CashCounter_CCID`,`remarks`,`excessAmount`,`returnAmount`,`ReturnHeader_RHID`,is_delivery,deliveryPartner,`sales_source`) VALUES ('$BillNo','$Inv_Type','$EffectiveDate','$InvoiceNo','$InvStartTime','$InvEndTime','$InvItemCount','$GrossAmount','$lineDiscount','$FixedDiscount','$DiscountAmount','$deliveryCharge','$otheCharge','$NetAmount','$CustPayment','$CustBalance','$InvStat','$user_USID','$customers_CTID','$Salesmans_SLID','$shop_SHID','$CashCounter_CCID','$remarks','$excessamount','$returnAmount','$return_header_id','$is_delivery','$deliveryPartner','$sales_source');";
            }
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return true;
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }
    }
    public function UpdateHoldInvoiceHeader($HIID,$InvoiceNo, $EffectiveDate,$BillNo,$InvStartTime,$InvEndTime,$InvItemCount,$GrossAmount,$lineDiscount,$FixedDiscount,$DiscountAmount,$deliveryCharge,$otheCharge,$NetAmount,$CustPayment,$CustBalance,$InvStat,$user_USID,$customers_CTID,$Salesmans_SLID,$shop_SHID,$CashCounter_CCID,$remarks,$excessamount,$returnAmount,$return_header_id,$saleDiscountType,$is_delivery,$deliveryPartner,$sales_source,$Inv_Type=2)
    {
        try 
        {
            if($saleDiscountType==1)
            {
                // percentage discount
                
                $sql="UPDATE `hold_invoice` SET `Inv_Type`='$Inv_Type',`EffectiveDate`='$EffectiveDate', `BillNo`='$InvoiceNo', `InvStartTime`='$InvStartTime', `InvEndTime`='$InvEndTime', `InvItemCount`='$InvItemCount', `GrossAmount`='$GrossAmount', `lineDiscount`='$lineDiscount', `PercentDiscount`='$FixedDiscount', `DiscountAmount`='$DiscountAmount', `deliveryCharge`='$deliveryCharge', `otherCharge`='$otheCharge', `NetAmount`='$NetAmount', `CustPayment`='$CustPayment', `CustBalance`='$CustBalance', `InvStat`='$InvStat', `user_USID`='$user_USID', `customers_CTID`='$customers_CTID', `Salesmans_SLID`='$Salesmans_SLID', `shop_SHID`='$shop_SHID', `CashCounter_CCID`='$CashCounter_CCID',`remarks`='$remarks',`excessAmount`='$excessamount',`returnAmount`='$returnAmount',`ReturnHeader_RHID`='$return_header_id',`is_delivery`='$is_delivery',`deliveryPartner`='$deliveryPartner',`sales_source`='$sales_source' WHERE HIID='$HIID' ;";
            }
            else
            {
                // fixed discount
                
                $sql="UPDATE `hold_invoice` SET `Inv_Type`='$Inv_Type',`EffectiveDate`='$EffectiveDate', `BillNo`='$InvoiceNo', `InvStartTime`='$InvStartTime', `InvEndTime`='$InvEndTime', `InvItemCount`='$InvItemCount', `GrossAmount`='$GrossAmount', `lineDiscount`='$lineDiscount', `FixedDiscount`='$FixedDiscount', `DiscountAmount`='$DiscountAmount', `deliveryCharge`='$deliveryCharge', `otherCharge`='$otheCharge', `NetAmount`='$NetAmount', `CustPayment`='$CustPayment', `CustBalance`='$CustBalance', `InvStat`='$InvStat', `user_USID`='$user_USID', `customers_CTID`='$customers_CTID', `Salesmans_SLID`='$Salesmans_SLID', `shop_SHID`='$shop_SHID', `CashCounter_CCID`='$CashCounter_CCID',`remarks`='$remarks',`excessAmount`='$excessamount',`returnAmount`='$returnAmount',`ReturnHeader_RHID`='$return_header_id',`is_delivery`='$is_delivery',`deliveryPartner`='$deliveryPartner',`sales_source`='$sales_source' WHERE HIID='$HIID' ;";
            }
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return true;
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }
    }
    public function setSellDetails($origi_UnitPrice,$sqllQty,$unitPrice,$sellAmount,$discount,$totalDiscount,$total,$invoiceHeader,$pro_id,$discountType,$prodDes,$shop_id,$Item_Name)
    {
        try 
        {
            if($discountType==1)
            {
                $sql="INSERT INTO `selldetail`(`Item_Name`, `SellQty`, `origi_UnitPrice`, `UnitPrice`, `SellAmount`, `PercentDiscount`, `SoldAmount`, `sellHeader_SHID`, `products_PDID`, `item_des`,`disc_type`,`shop_id`) VALUES ('$Item_Name','$sqllQty','$origi_UnitPrice','$unitPrice','$sellAmount','$discount','$total','$invoiceHeader','$pro_id','$prodDes','$discountType','$shop_id')";
            }
            else
            {
                $sql="INSERT INTO `selldetail`(`Item_Name`, `SellQty`, `origi_UnitPrice`, `UnitPrice`, `SellAmount`, `DirectDiscount`, `SellDiscount`, `SoldAmount`, `sellHeader_SHID`, `products_PDID`,`item_des`,`disc_type`,`shop_id`) VALUES ('$Item_Name','$sqllQty','$origi_UnitPrice','$unitPrice','$sellAmount','$discount','$totalDiscount','$total','$invoiceHeader','$pro_id','$prodDes','$discountType','$shop_id');";
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
}

?>