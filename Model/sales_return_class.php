<?php 
class Sales_return_class extends Dbh
{
    public function getSequence($num) 
    {
        return sprintf("%'.06d", $num);
    }
    
    public function selectAllReturnType()
    {
        try
        {            
            $sql="SELECT * FROM `salesreturntype`";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Supplier: " . $e->getMessage());
        }

    }
    public function getSaleSettings($shop_id)
    {
        try
        {            
            $sql="SELECT * FROM `salesettings` WHERE settingStat=1 && shop_id=?";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);
            return $stmt->fetchAll();
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Supplier: " . $e->getMessage());
        }
    }

    public function selectShop($userType,$user_id)
    {
        try
        {  
            if($userType==1)
            {
                $sql="SELECT * FROM `shop`";
            }  
            else  
            {
                $sql="SELECT * FROM shop s
                INNER JOIN shopusers su ON su.shop_SHID=s.SHID
                WHERE su.user_USID='$user_id'
                ";
            }      
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Supplier: " . $e->getMessage());
        }
    }
    public function GetLatestInventory($shop_id, $product_id)
    {
        try
        {
            $sql="SELECT * FROM `inventory` i INNER JOIN products p ON p.PDID=i.products_PDID WHERE i.products_PDID='$product_id' AND i.shop_SHID='$shop_id' ORDER BY i.INID DESC LIMIT 1;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
            // return $sql;
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to Select data From retrun_invoice_header: " . $e->getMessage());
        }
    }
    public function SelectAllFromSalesReturn($shop_id, $start=null, $end=null, $customer=null)
    {
        try
        {
            $add=" ";            
            if($customer!=null)
            {
                $add.="AND rih.Customer_CTID='$customer'";
            }
            if($start!=null && $end!=null)
            {
                $add.=" AND rih.EffectiveDate BETWEEN '$start' AND '$end'";
            }
            $sql="SELECT rih.return_no, rih.RIHID, rih.EffectiveDate, rih.return_header_stat, rih.return_amount, rih.reason, rih.return_count, ih.BillNo, sr.SRT_Name, c.CustName FROM `retrun_invoice_header` rih
                LEFT JOIN customers c ON c.CTID=rih.Customer_CTID
                LEFT JOIN invoiceheader ih ON ih.IHID=rih.IHID
                LEFT JOIN salesreturntype sr ON sr.SRTID=rih.return_type
                WHERE rih.shopID='$shop_id' $add ORDER BY rih.return_header_stat DESC";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
            // return $sql;
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to Select data From retrun_invoice_header: " . $e->getMessage());
        }
    }
    public function getinvoicebyinvoiceno($invoiceno,$shop_id,$multi_category,$company_id)
    {
        try
        {            
            $sql="SELECT * FROM `invoiceheader` WHERE BillNo='$invoiceno' && shop_SHID='$shop_id'";
            if($multi_category==1)
            {
                $sql="SELECT * FROM `invoiceheader` i
                INNER JOIN shop s ON s.SHID = i.shop_SHID 
                WHERE i.BillNo='$invoiceno' && s.Company_CMID='$company_id'";
            }
          $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Supplier: " . $e->getMessage());
        }
    }
    public function invoiceID($IHID)
    {
        try
        {            
            $sql="SELECT * FROM `invoiceheader` WHERE IHID='$IHID' ";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Supplier: " . $e->getMessage());
        }
    }
    public function getinvoicebyinvoiceid($invoiceid,$shop_id,$multi_category,$company_id)
    {
        try
        {            
            $sql="SELECT * FROM `invoiceheader` WHERE IHID='$invoiceid' && shop_SHID='$shop_id'";
            if($multi_category==1)
            {
                $sql="SELECT * FROM `invoiceheader` i
                    INNER JOIN shop s ON s.SHID = i.shop_SHID
                    WHERE i.IHID='$invoiceid' && s.Company_CMID = '$company_id';";
                
            }
          $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Supplier: " . $e->getMessage());
        }
    }
    public function getinvoicedetailsbyinvoiceid($invoiceid)
    {
        try
        {            
            $sql="SELECT *, p.ItemName AS product_name FROM `invoicedetails` id 
            INNER JOIN products p ON id.products_PDID=p.PDID
            WHERE id.InvoiceHeader_IHID=?";
          $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$invoiceid]);
            return $stmt->fetchAll();
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Supplier: " . $e->getMessage());
        }
    }
    public function pricehistorywithID($id)
    {
        try
        {            
            $sql="SELECT ph.*, i.* FROM `pricehistory` ph
            INNER JOIN inventory i ON ph.Inventory_INID=i.INID
            WHERE ph.PHID=?
            ";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetchAll();
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Supplier: " . $e->getMessage());
        }
    }
    public function pricehistorywithINID($id)
    {
        try
        {            
            $sql="SELECT * FROM `pricehistory` WHERE Inventory_INID=?
            ";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetchAll();
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Supplier: " . $e->getMessage());
        }
    }
    public function check_return($invoiceid)
    {
        try
        {            
            $sql="SELECT * FROM `retrun_invoice_header` WHERE IHID=?";
          $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$invoiceid]);
            return $stmt->fetchAll();
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Supplier: " . $e->getMessage());
        }
    }
    public function check_barcode($barcode)
    {
        try
        {            
            $sql="SELECT * FROM `products` WHERE Barcode=?";
          $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$barcode]);
            return $stmt->fetchAll();
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Supplier: " . $e->getMessage());
        }
    }
    public function check_product_sale($product_id)
    {
        try
        {            
            $sql="SELECT * FROM `products` WHERE PDID=?";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$product_id]);
            return $stmt->fetchAll();
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Supplier: " . $e->getMessage());
        }
    }
    public function select_batch($product_id,$shop_SHID)
    {
        
        try
        {            
            $sql="SELECT i.*,ph.*, ph.SellingPrice AS Pro_SellingPrice, i.BatchID AS pro_batch FROM `inventory` i 
                INNER JOIN pricehistory ph ON i.INID=ph.Inventory_INID
                WHERE i.products_PDID='$product_id' && i.shop_SHID='$shop_SHID';";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Supplier: " . $e->getMessage());
        }
    }
    public function check_barcode_sale($barcode)
    {
        try
        {            
            $sql="SELECT p.* FROM `products` p 
            INNER JOIN invoicedetails id ON id.products_PDID=p.PDID
            WHERE p.Barcode=?";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$barcode]);
            return $stmt->fetchAll();
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Supplier: " . $e->getMessage());
        }
    }
    public function customer_by_id($id)
    {
        try
        {            
            $sql="SELECT * FROM `customers` WHERE CTID=?";
          $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetchAll();
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Supplier: " . $e->getMessage());
        }
    }

    public function SelectInvoiceHeader($IHID)
    {
        try
        {            
            $sql="SELECT * FROM `invoiceheader` WHERE IHID=?";
          $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$IHID]);
            return $stmt->fetchAll();
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Supplier: " . $e->getMessage());
        }
    }
    public function count_reqturn()
    {
        try
        {        
            $shop_id = $_SESSION['shop_id'];    
            $sql="SELECT count(*) AS invoice_count FROM `retrun_invoice_header`";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Supplier: " . $e->getMessage());
        }
    }
    public function getBatchwithpricehistory($id)
    {
        try
        {            
            $sql="SELECT ph.*, i.*, ph.BatchID AS pro_batch FROM `pricehistory` ph INNER JOIN inventory i ON i.INID=ph.Inventory_INID WHERE ph.PHID='$id'";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to sup: " . $e->getMessage());
        }
    }
    public function insert_return_invoice_header($reason,$return_type,$InvoiceNo,$EffectiveDate,$InvStartTime,$returnby,$IHID,$shop_id,$return_no,$return_amount,$return_discount,$return_gross_amount,$return_count,$usability=1,$Customer_CTID=1, $CashCounter_CCID=0)
    {
        try
        {            
            $sql="INSERT INTO `retrun_invoice_header`(`reason`, `return_type`, `InvoiceNo`, `EffectiveDate`, `InvStartTime`, `returnby`, `IHID`, `shopID`, `return_no`, `return_amount`, `return_discount`, `return_gross_amount`, `return_count`,`usability`,`Customer_CTID`,`CashCounter_CCID`) VALUES ('$reason','$return_type','$InvoiceNo','$EffectiveDate','$InvStartTime','$returnby','$IHID','$shop_id','$return_no','$return_amount','$return_discount','$return_gross_amount','$return_count','$usability','$Customer_CTID','$CashCounter_CCID')";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            $lastId = $this->connect()->lastInsertId();
            
            return true;
        } 

        catch (PDOException $e) 
        {
            return false;
            // die("Error: Unable to insert data to retrun_invoice_header: " . $e->getMessage());
        }

    }

    public function updateInventory($qty,$INID)
    {
        try
        {            
            $sql="UPDATE inventory SET CurrentQty = CurrentQty + $qty, ReturnQty = ReturnQty + $qty WHERE INID=$INID;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to retrun_invoice_header: " . $e->getMessage());
        }
    }

    //edit return invoice header stat
    public function editReturnHeaderStat($return_stat, $return_header_id)
    {
        try
        {            
            $sql="UPDATE retrun_invoice_header SET return_header_stat =? WHERE RIHID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$return_stat, $return_header_id]);
            return $stmt->fetchAll();
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to retrun_invoice_header: " . $e->getMessage());
        }

    }//edit return header

    public function select_inventory_with_batchid_product_id($batch_id,$products_PDID,$shop_id)
    {
        try
        {            
            $sql="SELECT * FROM inventory WHERE BatchID='$batch_id' AND products_PDID='$products_PDID' AND shop_SHID='$shop_id'";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to sup: " . $e->getMessage());
        }
    }
    public function select_inventory_last_batch_product_id($products_PDID,$shop_id)
    {
        try
        {            
            $sql="SELECT * FROM inventory WHERE  products_PDID='$products_PDID' AND shop_SHID='$shop_id' ORDER BY INID DESC LIMIT 1";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to sup: " . $e->getMessage());
        }
    }


    public function max_return_invoice_header($shop_id)
    {
        try
        {            
            $sql="SELECT MAX(RIHID) AS maxId FROM `retrun_invoice_header` WHERE shopID='$shop_id' ";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Supplier: " . $e->getMessage());
        }
    }
    public function update_inventory($newreturn,$newCurrentQty,$inventory_INID)
    {        
        try
        {            
            $sql="UPDATE `inventory` SET `CurrentQty`='$newCurrentQty', `ReturnQty`='$newreturn' WHERE INID=$inventory_INID";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to : " . $e->getMessage());
        }
    }
    public function insert_return_invoice_details($ReturnQty,$ReturnAmount,$ReturnHeader_RHID,$InvoiceDetails_IDID,$products_PDID,$batch_id,$inventory_INID,$return_unit_price,$return_discount,$discountType=2)
    {
        try
        {            
            $sql="INSERT INTO `returndetails`(`ReturnQty`, `ReturnAmount`, `ReturnHeader_RHID`, `InvoiceDetails_IDID`, `products_PDID`, `batch_id`, `inventory_INID`,`return_unit_price`,`return_discount`,`return_discount_type`) VALUES ('$ReturnQty','$ReturnAmount','$ReturnHeader_RHID','$InvoiceDetails_IDID','$products_PDID','$batch_id','$inventory_INID','$return_unit_price','$return_discount','$discountType')";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } 

        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to invoice: " . $e->getMessage());
        }

    }
    public function return_details($id)
    {
        try
        {            
            $sql="SELECT rd.*, p.* FROM `returndetails` rd
            INNER JOIN products p ON p.PDID=rd.products_PDID
            WHERE rd.ReturnHeader_RHID='$id'; ";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Supplier: " . $e->getMessage());
        }

    }
    public function return_view_details($id,$shop_id)
    {
        try
        {            
            $sql="SELECT * FROM `retrun_invoice_header` WHERE RIHID='$id' AND shopID='$shop_id' ";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Supplier: " . $e->getMessage());
        }
    }

}

?>