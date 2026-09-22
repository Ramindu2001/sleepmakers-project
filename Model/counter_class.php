<?php 

Class Counter extends Dbh
{

    public function setCounter($CounterDate, $StartBalance, $ActualBalance, $CounterStartTime, $CounterEndTime, $CounterStat, $user_id, $shop_id)
    {
        $sql = "INSERT INTO cashcounter(CounterDate, StartBalance, EndBalance, CounterStartTime, CounterEndTime, CounterStat, user_USID, shop_SHID) VALUES(?,?,?,?,?,?,?,?);";
        $stmt = $this->connect()->prepare($sql);
        // $stmt->execute([$CounterDate, $StartBalance, $EndBalance, $CounterStartTime, $CounterEndTime, $CounterStat, $user_id, $shop_id]);
        if ($stmt->execute([$CounterDate, $StartBalance, $ActualBalance, $CounterStartTime, $CounterEndTime, $CounterStat, $user_id, $shop_id]))
        {
            return true;
        } 
        else
        {
            return false;
        }
    }//set category

    public function closeCounter($EndBalance, $CounterEndTime, $CounterStat, $counter_id)
    {
        $sql="UPDATE cashcounter SET EndBalance=?, CounterEndTime = ?, CounterStat = ? WHERE CCID = ?;";
        $stmt = $this->connect()->prepare($sql);
        // $stmt->execute([$EndBalance, $CounterEndTime, $CounterStat, $counter_id]);
        if ( $stmt->execute([$EndBalance, $CounterEndTime, $CounterStat, $counter_id])) 
        {
            $sql="UPDATE cashcounter SET CounterStat = ? WHERE CounterStat = 1 AND CCID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$CounterStat,$counter_id]);
            return true;
        }
        else 
        {
            return false;
        }
    }//set category

    public function closeAllUserCounters($counter_id)
    {
        $sql="UPDATE cashcounter SET CounterStat = 0 WHERE CCID = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$counter_id]);
    }//close all active user counters

    public function getCounterByUserID($user_id,$shop_id)
    {
        $sql = "SELECT * FROM cashcounter WHERE user_USID = ? AND CounterStat = 1 AND shop_SHID=?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$user_id,$shop_id]);
        return $stmt->fetchAll();
    }//get categories

    public function getOneCounterByUser($user_id)
    {
        $sql = "SELECT * FROM cashcounter WHERE user_USID = ? AND CounterStat = 1; ";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }//get categories

    public function getCounterTotalByUser($user_id)
    {
        $sql = "SELECT SUM(NetAmount) AS CounterTotal FROM invoiceheader
        INNER JOIN cashcounter ON cashcounter.CCID = invoiceheader.CashCounter_CCID
        WHERE cashcounter.user_USID = ? AND CounterStat = 1;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }//get categories


//========================== Denomination =========================//
public function setDenomination($CountDate, $CounterType, $RS5000, $RS1000, $RS500, $RS100, $RS50, $RS20, $RS10, $RS5, $RS2, $RS1, $shop_SHID, $user_USID, $counter_id)
{
    try 
    {
        $sql="INSERT INTO `tbl_denomination`(`CountDate`, `CounterType`, `RS5000`, `RS1000`, `RS500`, `RS100`, `RS50`, `RS20`, `RS10`, `RS5`, `RS2`, `RS1`, `shop_SHID`, `user_USID`, `counter_id`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$CountDate, $CounterType, $RS5000, $RS1000, $RS500, $RS100, $RS50, $RS20, $RS10, $RS5, $RS2, $RS1, $shop_SHID, $user_USID, $counter_id]);
    }//try 
    catch (PDOException $e)
    {
        die("Error: Unable to insert data: " . $e->getMessage());
    }//catch
}//save label


}//class counter