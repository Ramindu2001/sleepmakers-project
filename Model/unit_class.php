<?php 

final class Unit extends Dbh
{
    public function setUnit($UnitName, $ShortName, $shop_SHID)
    {
        try
        {
            $sql="INSERT INTO units(UnitName, ShortName, shop_SHID) VALUES(?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$UnitName, $ShortName, $shop_SHID]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//save unit

    public function editUnit($UnitName, $ShortName, $unit_id)
    {
        try
        {
            $sql="UPDATE units SET UnitName=?, ShortName=? WHERE UNID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$UnitName, $ShortName, $unit_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//edit unit

    public function deleteUnit($unit_id)
    {
        try
        {
            $sql="DELETE FROM units WHERE UNID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$unit_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//delete unit

    public function getUnitDuplicate($unit_name, $short_name, $shop_id)
    {
        try
        {
            $sql = "SELECT * FROM units WHERE UnitName=? AND ShortName=? AND shop_SHID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$unit_name, $short_name, $shop_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get unit duplicate

    public function getAllUnits($shop_id, $multi=null,$company=null)
    {
        try
        {
            if($multi==null)
            {
                $sql = "SELECT * FROM units WHERE shop_SHID=?;";
                $stmt = $this->connect()->prepare($sql);
                $stmt->execute([$shop_id]);
            }
            else
            {
                if($multi==1)
                {
                    $sql = "SELECT * FROM units c 
                    INNER JOIN shop s ON s.SHID = c.shop_SHID
                    WHERE s.Company_CMID = ?;";
                    $stmt = $this->connect()->prepare($sql);
                    $stmt->execute([$company]);
                }
                else
                {
                    $sql = "SELECT * FROM units WHERE shop_SHID=?;";
                    $stmt = $this->connect()->prepare($sql);
                    $stmt->execute([$shop_id]);
                }
            }
                
            $sql = "SELECT * FROM units WHERE shop_SHID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get all units
}//unit class
