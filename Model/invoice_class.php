<?php 

class Invoice extends Dbh
{
//============================== Invoice Header ==============================//
    public function setInvoiceHeader($InvoiceNo, $EffectiveDate, $BillNo, $InvStartTime, $InvEndTime, $InvItemCount, $GrossAmount, $lineDiscount, $PercentDiscount, $FixedDiscount, $DiscountAmount, $NetAmount, $CustPayment, $CustBalance, $InvStat, $user_USID, $customers_CTID, $Salesmans_SLID, $shop_SHID, $CashCounter_CCID)
    {
        try 
        {
            $sql="INSERT INTO invoiceheader(InvoiceNo, EffectiveDate, BillNo, InvStartTime, InvEndTime, InvItemCount, GrossAmount, lineDiscount, PercentDiscount, FixedDiscount, DiscountAmount, NetAmount, CustPayment, CustBalance, InvStat, user_USID, customers_CTID, Salesmans_SLID, shop_SHID, CashCounter_CCID) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$InvoiceNo, $EffectiveDate, $BillNo, $InvStartTime, $InvEndTime, $InvItemCount, $GrossAmount, $lineDiscount, $PercentDiscount, $FixedDiscount, $DiscountAmount, $NetAmount, $CustPayment, $CustBalance, $InvStat, $user_USID, $customers_CTID, $Salesmans_SLID, $shop_SHID, $CashCounter_CCID]);
        }//try 
        catch (PDOException $e)
        {
            die("Error: Unable to insert data invoiceheader: " . $e->getMessage());
        }//catch
    }//save invoice header

    public function editInvoiceHeaderStat($inv_stat, $invoice_header_id)
    {
        try 
        {
            $sql="UPDATE invoiceheader SET InvStat = ? WHERE IHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$inv_stat, $invoice_header_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//save invoice header

    public function editInvoiceHeaderPrice($EffectiveDate, $InvEndTime, $ItemCount, $GrossAmount, $lineDiscount, $PercentDiscount, $FixedDiscount, $DiscountAmount, $NetAmount, $CustPayment, $CustBalance, $invoice_header_id)
    {
        try 
        {
            $sql=" UPDATE invoiceheader SET EffectiveDate=?, InvEndTime=?, InvItemCount=?, GrossAmount=?, lineDiscount=?, PercentDiscount=?, FixedDiscount=?, DiscountAmount=?, NetAmount=?, CustPayment=?, CustBalance=? WHERE IHID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$EffectiveDate, $InvEndTime, $ItemCount, $GrossAmount, $lineDiscount, $PercentDiscount, $FixedDiscount, $DiscountAmount, $NetAmount, $CustPayment, $CustBalance, $invoice_header_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//update invoice header price

    public function setInvoiceDetail($SellQty, $UnitPrice, $SellAmount, $PercentDiscount, $DirectDiscount, $SellDiscount, $SoldAmount, $WarrantyStart, $WarrantyEnd, $ReferenceNo, $InvoiceHeader_IHID, $products_PDID, $batch_no)
    {
        try 
        {
            $sql="INSERT INTO invoicedetails(SellQty, UnitPrice, SellAmount, PercentDiscount, DirectDiscount, SellDiscount, SoldAmount, WarrantyStart, WarrantyEnd, ReferenceNo, InvoiceHeader_IHID, products_PDID, batch_no) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$SellQty, $UnitPrice, $SellAmount, $PercentDiscount, $DirectDiscount, $SellDiscount, $SoldAmount, $WarrantyStart, $WarrantyEnd, $ReferenceNo, $InvoiceHeader_IHID, $products_PDID, $batch_no]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//save category

//================================ Sell ====================================//
    public function setSaleHeader($tmp_bill_no, $ItemCount, $grossAmount, $SellStat, $user_id, $shop_id, $cashcounter_id)
    {
        try 
        {
            $sql="INSERT INTO sellheader(tmp_bill_no, ItemCount, grossAmount, SellStat, user_id, shop_id, cashcounter_id) VALUES(?,?,?,?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$tmp_bill_no, $ItemCount, $grossAmount, $SellStat, $user_id, $shop_id, $cashcounter_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//save sale header

    public function editSaleHeaderStat($item_count, $gross_amount, $line_discount, $net_amount, $header_id)
    {
        try 
        {
            $sql="UPDATE sellheader SET ItemCount=?, GrossAmount=?, lineDiscount=?, NetAmount=?, SellStat=1 WHERE SHID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$item_count, $gross_amount, $line_discount, $net_amount, $header_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//update sale header

    public function editSaleHeaderStatOnly($header_stat, $header_id)
    {
        try 
        {
            $sql="UPDATE sellheader SET SellStat=? WHERE SHID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$header_stat, $header_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//update sale header

    public function editSaleHeader($ItemCount, $GrossAmount, $PercentDiscount, $FixedDiscount, $lineDiscount, $DiscountAmount, $NetAmount, $sell_header_id)
    {
        try 
        {
            $sql="UPDATE sellheader SET ItemCount=?, GrossAmount=?, PercentDiscount=?, FixedDiscount=?, lineDiscount=?, DiscountAmount=?, NetAmount=? WHERE SHID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$ItemCount, $GrossAmount, $PercentDiscount, $FixedDiscount, $lineDiscount, $DiscountAmount, $NetAmount, $sell_header_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//save sale header

    public function holdAllSaleHeader($user_id, $shop_id)
    {
        try
        {
            $sql="UPDATE sellheader SET SellStat = 2 WHERE user_id=? AND shop_id=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$user_id, $shop_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//save sale header

    public function deleteSaleHeader($sale_header_id)
    {
        try 
        {
            $sql="DELETE FROM sellheader WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$sale_header_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//save sale header

    public function getBillCount($shop_id)
    {
        try
        {
            $sql = "SELECT count(IHID) AS InvoiceCount FROM invoiceheader WHERE shop_SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);
            $data = $stmt->fetchAll();

            return $data[0]['InvoiceCount'];
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get bill Count

    public function getOneSellHeader($header_id)
    {
        try
        {
            $sql = "SELECT * FROM sellheader WHERE SHID=".$header_id.";";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$header_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get bill Count

//=============================== Sell Detail ==================================//  
    public function setSaleDetail($sellQty, $unitSellAmount, $sellAmount, $itemWiseDiscount, $itemPercentDiscount, $sellDiscount, $soldAmount, $sellheader_id, $product_id, $pricehistory_id)
    {
        try 
        {
            $sql="INSERT INTO selldetail(sellQty, unitSellAmount, sellAmount, itemWiseDiscount, itemPercentDiscount, sellDiscount, soldAmount, sellheader_id, product_id, pricehistory_id) VALUES(?,?,?,?,?,?,?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$sellQty, $unitSellAmount, $sellAmount, $itemWiseDiscount, $itemPercentDiscount, $sellDiscount, $soldAmount, $sellheader_id, $product_id, $pricehistory_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//save sell detail

    public function editSaleDetail($sellQty, $sellAmount, $itemWiseDiscount, $itemPercentDiscount, $sellDiscount, $soldAmount, $selldetail_id)
    {
        try 
        {
            $sql="UPDATE selldetail SET sellQty=?, sellAmount=?, itemWiseDiscount=?, itemPercentDiscount=?, sellDiscount=?, soldAmount=? WHERE SDID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$sellQty, $sellAmount, $itemWiseDiscount, $itemPercentDiscount, $sellDiscount, $soldAmount, $selldetail_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//edit sale detail

    public function editQtySaleDetail($sellQty, $sellAmount, $soldAmount, $selldetail_id)
    {
        try 
        {
            $sql="UPDATE selldetail SET sellQty=?, sellAmount=?, soldAmount=? WHERE SDID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$sellQty, $sellAmount, $soldAmount, $selldetail_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//edit sale detail

    public function deleteSaleDetail($selldetail_id)
    {
        try 
        {
            $sql="DELETE FROM selldetail WHERE SDID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$selldetail_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//edit sale detail

//=============================== doc no ===============================//
    public function setDoc($tmp_no, $org_no, $shop_id)
    {
        try 
        {
            $sql="INSERT INTO docno(tmp_no, org_no, shop_id) VALUES(?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$tmp_no, $org_no, $shop_id]);
        }//try
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//save sell detail

    public function editDoc($tmp_no, $org_no, $doc_id)
    {
        try 
        {
            $sql="UPDATE docno SET tmp_no=?, org_no=? WHERE DNID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$tmp_no, $org_no, $doc_id]);
        }//try
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//save sell detail
    
//============================== Multi Pay =============================//
    public function setMultipay($paidAmount, $payStat, $paymethod_id, $sellheader_id, $return_header_id)
    {
        try
        {
            $sql="INSERT INTO multipay(paidAmount, payStat, paymethod_id, sellheader_id, returnheader_id) VALUES(?,?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$paidAmount, $payStat, $paymethod_id, $sellheader_id, $return_header_id]);
        }//try
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//save sell detail

    public function deleteMultipay($multipay_id)
    {
        try
        {
            $sql="DELETE FROM multipay WHERE MPID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$multipay_id]);
        }//try
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//save sell detail

}//class Invoice