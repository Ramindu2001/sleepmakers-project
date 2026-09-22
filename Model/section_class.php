<?php 

Class Section extends Dbh
{
    public function setSection($SectionNo, $SectionName, $shop_SHID)
    {
        try 
        {
            $sql="INSERT INTO sections(SectionNo, SectionName, shop_SHID) VALUES(?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$SectionNo, $SectionName, $shop_SHID]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//set section

    public function editSection($SectionName, $section_id)
    {
        try 
        {
            $sql="UPDATE sections SET SectionName = ? WHERE SEID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$SectionName, $section_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//set section

    public function deleteSection($section_id)
    {
        try 
        {
            $sql="DELETE FROM sections WHERE sections.SEID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$section_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//set section

    public function getSectionByName($section_name, $shop_id)
    {
        try
        {
            $sql = "SELECT * FROM sections WHERE SectionName = ? AND shop_SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$section_name, $shop_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get category by name

    public function getSectionCount($shop_id)
    {
        try
        {
            $sql = "SELECT count(SEID) as SectionCount FROM sections WHERE shop_SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get category by name

    public function getAllSections($shop_id)
    {
        try
        {
            $sql = "SELECT * FROM sections WHERE shop_SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get category by name

//================================ Racks ===============================//
    public function setRacks($RackNo, $RackName, $Sections_SEID)
    {
        try 
        {
            $sql="INSERT INTO rack(RackNo, RackName, Sections_SEID) VALUES(?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$RackNo, $RackName, $Sections_SEID]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//set section

    public function editRacks($RackName, $Sections_SEID, $rack_id)
    {
        try 
        {
            $sql="UPDATE rack SET RackName=?, Sections_SEID=? WHERE RKID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$RackName, $Sections_SEID, $rack_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//set section

    public function deleteRack($rack_id)
    {
        try 
        {
            $sql="DELETE FROM rack WHERE RKID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$rack_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//set section

    public function getRackDuplicate($section_id, $rack_name, $shop_id)
    {
        try
        {
            $sql = "SELECT * FROM rack 
            INNER JOIN sections ON sections.SEID = rack.Sections_SEID
            WHERE Sections_SEID =? AND RackName = ? AND shop_SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$section_id, $rack_name, $shop_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get category by name

    public function getRackCount($shop_id)
    {
        try
        {
            $sql = "SELECT count(RKID) AS RackCount FROM rack
            INNER JOIN sections ON sections.SEID = rack.Sections_SEID
            WHERE shop_SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get category by name

    public function getAllRack($shop_id)
    {
        try
        {
            $sql = "SELECT * FROM rack
            INNER JOIN sections ON sections.SEID = rack.Sections_SEID
            WHERE shop_SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get category by name

    public function getRackBySection($section_id)
    {
        try
        {
            $sql = "SELECT * FROM rack WHERE Sections_SEID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$section_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get category by name

}//class section