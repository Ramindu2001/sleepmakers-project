<?php 

Class Supplier extends Dbh
{
    public function setSupplier($SupplierNo,$distributer,$SupplierName,$Contact,$SupplierStat,$shop_SHID)
    {
        try 
        {            
            $sql="INSERT INTO suppliers(SupplierNo, Distributer, SupplierName, Contact, SupplierStat, shop_SHID) VALUES(?,?,?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$SupplierNo,$distributer,$SupplierName,$Contact,$SupplierStat,$shop_SHID]);

        } 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Supplier: " . $e->getMessage());
        }
        
    }//insert Supplier

    public function editSupplier($distributer, $supplier_name, $contact,$supplier_stat, $supplier_id)
    {
        try
        {
            $sql = "UPDATE suppliers SET Distributer=?, SupplierName=?, Contact=?, SupplierStat=? WHERE SPID=?;";
            $stmt = $this->connect()->prepare($sql);           
             $stmt->execute([$distributer, $supplier_name, $contact,$supplier_stat, $supplier_id]);
        } 
        catch (PDOException $e) 
        {
            die("Error: Unable to update the Supplier" . $e->getMessage());
        }
    } //update supplier

    public function deleteSupplier($supplier_id)
    {
        try 
        {            
            $sql="DELETE FROM suppliers WHERE SPID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$supplier_id]);
        } 
        catch (PDOException $e) 
        {
            die("Error: Unable to DELETE data from Supplier: " . $e->getMessage());
        }
        
    }//insert Supplier

    public function getOneSupplier($Supp_id)
    {
        try 
        {           
            $sql = "SELECT * FROM suppliers S
            INNER JOIN shop SH ON SH.SHID = S.shop_SHID
            INNER JOIN company C ON C.CMID = SH.Company_CMID
            WHERE S.SPID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$Supp_id]);
            return $stmt->fetchAll();
        }
        catch (PDOException $e) {
            die("Error: Unable to fetch Supplier data " . $e->getMessage());
        }
    }//get one supplier

    public function getSuppliers()
    {
        try 
        {
            $stmt = $this->connect()->prepare("SELECT * FROM suppliers S INNER JOIN shop SH ON SH.SHID = S.shop_SHID INNER JOIN company C ON C.CMID = SH.Company_CMID");
            $stmt->execute();            
            $Suppliers = $stmt->fetchAll();
            return $Suppliers;
        } 
        catch (PDOException $e) {
            die("Error: Unable to fetch Suppliers. " . $e->getMessage());
        }
    }

    public function getSupplierCount()
    {
        $sql = "SELECT max(SPID) as SuppCount FROM suppliers;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }//get supplier count

    public function getAllSuppliers($shop_id)
    {
        try
        {
            $sql = "SELECT * FROM suppliers WHERE shop_SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);
            return $stmt->fetchAll();
        }//try
        catch (PDOException $e) 
        {
            die("Error: Unable to fetch from Supplier" . $e->getMessage());
        }//catch
    }//get supplier count
    public function getAllActiveSuppliers($shop_id, $status)
    {
        try
        {
            $sql = "SELECT * FROM suppliers WHERE shop_SHID = ? AND SupplierStat=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id,$status]);
            return $stmt->fetchAll();
        }//try
        catch (PDOException $e) 
        {
            die("Error: Unable to fetch from Supplier" . $e->getMessage());
        }//catch
    }//get supplier count


    public function getShopSupplier($shop_id,$Supp_id)
    {
        try
        {
            $sql = "SELECT * FROM suppliers WHERE shop_SHID = ? AND SPID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id,$Supp_id]);
            return $stmt->fetchAll();
        }//try
        catch (PDOException $e) 
        {
            die("Error: Unable to fetch from Supplier" . $e->getMessage());
        }//catch
    }//get supplier count

}//class Shop