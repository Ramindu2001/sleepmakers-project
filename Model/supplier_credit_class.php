<?php 

class SupplierCredit extends Dbh
{
    public function setSupplierCredit($TransferAmount, $TransactionStat, $paymethod_PMID, $GRNHeader_GHID, $returnheader_id)
    {
        try 
        {            
            $sql="INSERT INTO `suppliertransactions`(`TransferAmount`, `TransactionStat`, `paymethod_PMID`, `GRNHeader_GHID`, `returnheader_id`) VALUES (?,?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$TransferAmount, $TransactionStat, $paymethod_PMID, $GRNHeader_GHID, $returnheader_id]);
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Salesman: " . $e->getMessage());
        }
    }//SET supplier credit
    public function setSupplierCredit2($TransferAmount, $TransactionStat, $GRNHeader_GHID, $paymethod_PMID, $supplier_id)
    {
        try 
        {    
            $createDate=date("Y-m-d");        
            $created_dateTime=date("Y-m-d H:i:s");        
            $sql="INSERT INTO `supcredittransactions`(`supcreditTransactionAmount`, `supcreditTransactionStat`, `grn_GHI`, `paymethod_id`, `createDate`, `created_dateTime`,`supplier_id`) VALUES ('$TransferAmount', '$TransactionStat', '$GRNHeader_GHID', '$paymethod_PMID','$createDate','$created_dateTime','$supplier_id');";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            $query = "SELECT MAX(SCTID) AS SCTID FROM `supcredittransactions`";
            $result = $this->connect()->prepare($query);
            $result->execute();
            return $result->fetchAll();
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to supcredittransactions: " . $e->getMessage());
        }
    }//SET supplier credit

    public function supplierCreditPay($CreditAmount,$Balance,$grn_header_id,$pay_m_id,$Supplier_ID,$user_id,$shop_id,$CreditStat=1)
    {
        try 
        {    
            $EffectiveDate=date("Y-m-d");        
            $SubmitDate=date("Y-m-d");            
            $sql="INSERT INTO `creditsupplier`(`EffectiveDate`, `CreditAmount`, `Balance`, `SubmitDate`, `invoice_header_id`, `pay_m_id`, `CreditStat`, `Supplier_ID`, `user_USID`, `shop_SHID`) VALUES ('$EffectiveDate','$CreditAmount','$Balance','$SubmitDate','$grn_header_id','$pay_m_id','$CreditStat','$Supplier_ID','$user_id','$shop_id');";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            $query = "SELECT MAX(SCID) AS SCID FROM `creditsupplier`";
            $result = $this->connect()->prepare($query);
            $result->execute();
            return $result->fetchAll();
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to creditsupplier: " . $e->getMessage());
        }

    }

    public function updatesupplierTran($CreditSupplier_SCID,$SCTID)
    {
        try 
        {            
            $sql="UPDATE supcredittransactions SET CreditSupplier_SCID ='$CreditSupplier_SCID',supcreditTransactionStat=1 WHERE SCTID = '$SCTID';";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to Update data to supcredittransactions: " . $e->getMessage());
        }
    }

    public function getSupplierNames($SPID) {
        $query = "SELECT * FROM suppliers WHERE SPID = :SPID";
        $result = $this->connect()->prepare($query);
        $result->bindParam(':SPID', $SPID, PDO::PARAM_STR);
        $result->execute();
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }
    

    public function editCreditBalance($credit, $debit, $balance, $credit_supplier_id)
    {
        try 
        {            
            $sql="UPDATE creditsupplier SET CreditAmount = CreditAmount + ?, DebitAmount = DebitAmount-?, Balance = Balance - ? WHERE CCID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$credit, $debit, $balance, $credit_supplier_id]);
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Salesman: " . $e->getMessage());
        }
    }//edit credit Balance
    public function editSupplierCredit($credit, $debit, $balance, $credit_supplier_id)
    {
        try 
        {            
            $sql="UPDATE creditsupplier SET CreditAmount = ? , Balance =  ? WHERE CCID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$debit, $balance, $credit_supplier_id]);
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Salesman: " . $e->getMessage());
        }
    }//edit credit Balance

    public function editSupplierTransaction($grn_header_id)
    {
        try 
        {            
            $sql="UPDATE suppliertransactions SET TransactionStat = 1 WHERE GRNHeader_GHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$grn_header_id]);
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Salesman: " . $e->getMessage());
        }
    }//edit credit Balance

    public function deleteSupplierCredit($supplier_transaction_id)
    {
        try 
        {            
            $sql="DELETE FROM suppliertransactions WHERE TRID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$supplier_transaction_id]);
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Salesman: " . $e->getMessage());
        }
    }//SET supplier credit
    public function deleteSupplierCredit2($supplier_transaction_id)
    {
        try 
        {            
            $sql="DELETE FROM supcredittransactions WHERE SCTID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$supplier_transaction_id]);
            return true;
        }
        catch (PDOException $e) 
        {
            return "Error: Unable to Delete data to supcredittransactions: " . $e->getMessage();
        }
    }//SET supplier credit


}//supplier credit class