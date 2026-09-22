<?php 

class Inventory extends Dbh
{
    public function getSequence($num) 
    {
        return sprintf("%'.09d", $num);
    }
    public function setInventory($CurrentQty, $BillQty, $ReturnQty, $TransfeInQty, $TransferOutQty, $products_PDID, $shop_SHID, $RackID, $BatchID)
    {
        try 
        {
                $sql="INSERT INTO inventory(CurrentQty, BillQty, ReturnQty, TransferInQty, TransferOutQty, products_PDID, shop_SHID, RackID, BatchID) VALUES(?,?,?,?,?,?,?,?,?);";
                $stmt = $this->connect()->prepare($sql);
                $stmt->execute([$CurrentQty, $BillQty, $ReturnQty, $TransfeInQty, $TransferOutQty, $products_PDID, $shop_SHID, $RackID, $BatchID]);
            
        }//try 
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//save inventory
    public function setInventory2($CurrentQty, $BillQty, $ReturnQty, $TransfeInQty, $TransferOutQty, $products_PDID, $shop_SHID, $RackID, $BatchID,$default)
    {
        try 
        {
                $sql="INSERT INTO inventory(CurrentQty, BillQty, ReturnQty, TransferInQty, TransferOutQty, products_PDID, shop_SHID, RackID, BatchID, is_default) VALUES(?,?,?,?,?,?,?,?,?,?);";
                $stmt = $this->connect()->prepare($sql);
                $stmt->execute([$CurrentQty, $BillQty, $ReturnQty, $TransfeInQty, $TransferOutQty, $products_PDID, $shop_SHID, $RackID, $BatchID,$default]);
            
        }//try 
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//save inventory

    public function update_qty($qty,$INID)
    {
        try 
        {
            $sql="UPDATE inventory SET CurrentQty=CurrentQty+$qty WHERE INID='$INID';";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
        }//try 
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }
    public function getInventorywithproductID($product_id)
    {
        try 
        {
            $sql="SELECT count(*) AS procount  FROM inventory WHERE products_PDID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$product_id]);
            return $stmt->fetchAll();
            
        }//try 
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch

    }
    public function getInventorywithproductIDshop($product_id,$shop_id)
    {
        try 
        {
            $sql="SELECT count(*) AS procount  FROM inventory WHERE products_PDID=? AND shop_SHID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$product_id,$shop_id]);
            return $stmt->fetchAll();
            
        }//try 
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch

    }

    public function editInvCurrentQty($CurrentQty, $inventory_id)
    {
        try 
        {
            $sql="UPDATE inventory SET CurrentQty = ? WHERE INID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$CurrentQty, $inventory_id]);
        }//try 
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//edit current inventory

    public function deductInvQty($sell_qty, $inventory_id)
    {
        try
        {
            $sql = "UPDATE inventory SET CurrentQty = CurrentQty - ?, BillQty = BillQty + ? WHERE INID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$sell_qty, $sell_qty, $inventory_id]);
        }//try
        catch (PDOException $e)
        {
            die("Error: Unable to update inventory: " . $e->getMessage());
        }//catch
    }//atomic deduct inventory qty

    public function editInvSupplierReturnQty($RtnQty, $inventory_id)
    {
        try
        {
            // Validate input values
            if (!is_numeric($RtnQty) || !is_numeric($inventory_id)) {
                throw new Exception("Invalid input values");
            }
    
            $sql = "UPDATE inventory SET Sup_Rtn = :RtnQty, CurrentQty = (CurrentQty - :RtnQty) WHERE INID = :inventory_id;";
            $stmt = $this->connect()->prepare($sql);
    
            // Bind parameters to prevent SQL injection
            $stmt->bindParam(':RtnQty', $RtnQty, PDO::PARAM_INT);
            $stmt->bindParam(':inventory_id', $inventory_id, PDO::PARAM_INT);
    
            // Execute the statement
            if ($stmt->execute()) {
                // Check if any rows were updated
                if ($stmt->rowCount() > 0) {
                    return "Update successful";
                } else {
                    return "No rows affected";
                }
            } 
            else {
                return "Update failed";
            }
        }
        catch (PDOException $e)
        {
            die("Error: Unable to update data: " . $e->getMessage());
        }
        catch (Exception $e)
        {
            die("Validation error: " . $e->getMessage());
        }
    }//edit inventory qty    

    public function editRollbackInvSupplierReturnQty($RtnQty, $inventory_id)
    {
        try 
        {
            // Validate input values
            if (!is_numeric($RtnQty) || !is_numeric($inventory_id)) {
                throw new Exception("Invalid input values");
            }
    
            $sql = "UPDATE inventory SET Sup_Rtn_Qty = (Sup_Rtn_Qty-: RtnQty), CurrentQty = (CurrentQty + :RtnQty) WHERE INID = :inventory_id";
            $stmt = $this->connect()->prepare($sql);
    
            // Bind parameters to prevent SQL injection
            $stmt->bindParam(':RtnQty', $RtnQty, PDO::PARAM_INT);
            $stmt->bindParam(':inventory_id', $inventory_id, PDO::PARAM_INT);
    
            // Execute the statement
            if ($stmt->execute()) {
                // Check if any rows were updated
                if ($stmt->rowCount() > 0) {
                    return "Update successful";
                } else {
                    return "No rows affected";
                }
            } else {
                return "Update failed";
            }
        }
        catch (PDOException $e)
        {
            die("Error: Unable to update data: " . $e->getMessage());
        }
        catch (Exception $e)
        {
            die("Validation error: " . $e->getMessage());
        }
    }    

    public function editInvBillQty($bill_qty, $inventory_id)
    {
        try 
        {
            $sql="UPDATE inventory SET BillQty = ? WHERE INID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$bill_qty, $inventory_id]);
        }//try 
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//edit current inventory

    public function editInvTransferInQty($transfer_in_qty, $inventory_id)
    {
        try
        {
            $sql="UPDATE inventory SET TransferInQty = ? WHERE INID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$transfer_in_qty, $inventory_id]);
        }//try 
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//edit current inventory

    public function editInvTransferOurQty($transfer_out_qty, $inventory_id)
    {
        try 
        {
            $sql="UPDATE inventory SET TransferOutQty = ? WHERE INID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$transfer_out_qty, $inventory_id]);
        }//try 
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//edit current inventory

    public function getCurrentStore($shop_id)
    {
        try{
            $sql = "SELECT * FROM inventory
            INNER JOIN products ON products.PDID = inventory.products_PDID
            WHERE inventory.shop_SHID = ? AND CurrentQty > 0;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get category count

    public function getOneInventory($inventory_id)
    {
        try{
            $sql = "SELECT * FROM inventory
            INNER JOIN products ON products.PDID = inventory.products_PDID
            WHERE INID=? ;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$inventory_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get category count

    public function getInvByProduct($product_id, $shop_id)
    {
        try
        {
            $sql = "SELECT * FROM inventory
            INNER JOIN products ON products.PDID = inventory.products_PDID
            WHERE PDID = ? AND CurrentQty > 0 AND inventory.shop_SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$product_id, $shop_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get category count
}//class inventory