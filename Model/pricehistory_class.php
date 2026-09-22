<?php 

class PriceHistory extends Dbh
{
    public function setPriceHistory($ProductID, $VariationID, $EffectiveDate, $PurchasePrice, $SellingPrice, $labelPrice, $MnfDate, $ExpDate, $BatchID, $Inventory_INID, $grn_detail_id)
    {
        try
        {
            $sql="INSERT INTO pricehistory(ProductID, VariationID, EffectiveDate, PurchasePrice, SellingPrice, labelPrice, MnfDate, ExpDate, BatchID, Inventory_INID, GrnDetailID) VALUES(?,?,?,?,?,?,?,?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$ProductID, $VariationID, $EffectiveDate, $PurchasePrice, $SellingPrice, $labelPrice, $MnfDate, $ExpDate, $BatchID, $Inventory_INID, $grn_detail_id]);
        }//try 
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//save inventory

    public function editPriceHistoryPrice($PurchasePrice, $SellingPrice, $price_history_id)
    {
        try
        {
            $sql="UPDATE pricehistory SET PurchasePrice=?, SellingPrice=? WHERE PHID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$PurchasePrice, $SellingPrice, $price_history_id]);
        }//try 
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//save inventory

    public function getPriceByProduct($product_id)
    {
        try
        { 
            $sql = "SELECT * FROM pricehistory
            LEFT JOIN variations ON variations.VRID = pricehistory.VariationID
            WHERE ProductID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$product_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get company type
}//price history class