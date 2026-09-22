<?php 

class Batch extends Dbh
{
    public function getActiveBatch($shop_id)
    {
        try{
            $sql = "SELECT * FROM batch WHERE BatchStat = 1 AND shop_SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get category count
}//class batch