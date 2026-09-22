<?php 

class Transaction extends Dbh
{
    public function setTransaction($TransferAmount, $TransactionStat, $paymethod_PMID, $InvoiceHeader_IHID, $return_header_id)
    {
        try 
        {
            $sql="INSERT INTO transactions(TransferAmount, TransactionStat, paymethod_PMID, InvoiceHeader_IHID, returnheader_id) VALUES(?,?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$TransferAmount, $TransactionStat, $paymethod_PMID, $InvoiceHeader_IHID, $return_header_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//save invoice header
}//class Transaction