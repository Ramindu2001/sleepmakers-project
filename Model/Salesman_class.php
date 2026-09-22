<?php 

Class Salesman extends Dbh
{
    public function setSalesman($SalesmanNo, $SalesmansName, $SalesmansContact, $commision_rate, $SalesmanStat, $shop_SHID)
    {
        try 
        {            
            $sql="INSERT INTO salesmans(SalesmanNo, SalesmansName, SalesmansContact, commision_rate, SalesmanStat, shop_SHID) VALUES(?,?,?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$SalesmanNo, $SalesmansName, $SalesmansContact, $commision_rate, $SalesmanStat, $shop_SHID]);
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Salesman: " . $e->getMessage());
        }
    }//insert Salesman

    public function updateSalesman($SalesmansName, $SalesmansContact, $commision_rate, $Stat, $SLID)
    {
        try 
        {
            $stmt = $this->connect()->prepare("UPDATE salesmans SET SalesmansName =?,SalesmansContact =?, commision_rate=?, SalesmanStat=? WHERE SLID=?;");           
             $stmt->execute([$SalesmansName, $SalesmansContact, $commision_rate, $Stat, $SLID]);
        } 
        catch (PDOException $e) 
        {
            die("Error: Unable to update the Salesman" . $e->getMessage());
        }
    } //update Salesman

    public function deleteSalesman($salesman_id)
    {
        try 
        {
            $sql = "DELETE FROM salesmans WHERE SLID = ?;";
            $stmt = $this->connect()->prepare($sql);           
             $stmt->execute([$salesman_id]);
        } 
        catch (PDOException $e) 
        {
            die("Error: Unable to update the Salesman" . $e->getMessage());
        }
    } //delete Salesman

    public function getOneSalesman($Sal_id)
    {
        try 
        {           
            $sql = "SELECT * FROM salesmans SM INNER JOIN shop S ON SM.shop_SHID = S.SHID
            WHERE SM.SLID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$Sal_id]);
            return $stmt->fetchAll();
        }
        catch (PDOException $e) {
            die("Error: Unable to fetch Salesman data " . $e->getMessage());
        }
    }//get one supplier

    public function getSalesmans($shop_id)
    {
        try 
        {
            $stmt = $this->connect()->prepare("SELECT * FROM salesmans WHERE shop_SHID = ?;");
            $stmt->execute([$shop_id]);            
            $Salesmans = $stmt->fetchAll();
            return $Salesmans;
        } 
        catch (PDOException $e) {
            die("Error: Unable to fetch Salesman. " . $e->getMessage());
        }
    }

    public function getSalesmanCount()
    {
        try
        {
            $sql = "SELECT max(SLID) as Salcount FROM salesmans;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to fetch Salesman. " . $e->getMessage());
        }
        
    }//get Salesman count
}//class Shop