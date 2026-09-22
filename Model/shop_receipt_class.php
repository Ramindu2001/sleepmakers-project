<?php 

class ShopReceipt extends Dbh
{
    public function setShopReceipt($receiptName, $shop_id, $is_default, $ReceiptStat, $ReceiptPath, $RecieptType)
    {
        try 
        {
            $sql="INSERT INTO shopreceipts(receiptName, shop_id, is_default, ReceiptStat, ReceiptPath, RecieptType) VALUES(?,?,?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$receiptName, $shop_id, $is_default, $ReceiptStat, $ReceiptPath, $RecieptType]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//save shop receipt

    public function editAllShopReceipt($receiptName, $shop_id, $is_default, $ReceiptStat, $ReceiptPath, $receipt_id,$wholesaleRecipt)
    {
        try 
        {
            $sql="UPDATE shopreceipts SET receiptName=?, shop_id=?, is_default=?, ReceiptStat=?, ReceiptPath=?, RecieptType=? WHERE SRID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$receiptName, $shop_id, $is_default, $ReceiptStat, $ReceiptPath,$wholesaleRecipt, $receipt_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//update all shop receipt

    public function editShopReceipt($receiptName, $shop_id, $is_default, $ReceiptStat, $receipt_id,$wholesaleRecipt)
    {
        try 
        {
            $sql="UPDATE shopreceipts SET receiptName=?, shop_id=?, is_default=?, ReceiptStat=?,RecieptType=? WHERE SRID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$receiptName, $shop_id, $is_default, $ReceiptStat,$wholesaleRecipt, $receipt_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//update all shop receipt

    public function editAllDefaultReceipt($shop_id)
    {
        try 
        {
            $sql="UPDATE shopreceipts SET is_default = 0 WHERE shop_id = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//update shop receipt

    public function deleteOneReceipt($receipt_id)
    {
        try 
        {
            $sql="DELETE FROM shopreceipts WHERE SRID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$receipt_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//update shop receipt

}//shop receipt class