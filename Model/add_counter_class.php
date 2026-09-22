<?php

class AddCounterModels extends Dbh
{
    public function setCounterModels($counterNo)
    {
        try {
            $sql = "INSERT INTO counters (counterNo) VALUES (?)";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$counterNo]);
            $run = 1;
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            die("Error: Unable to Add the Counter. " . $e->getMessage());
            $run = 2;
        }
        if ($run == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function getCounters()
    {
        try {
            $stmt = $this->connect()->prepare("SELECT CTID, counterNo FROM counters");
            $stmt->execute();
            $Counters = $stmt->fetchAll();
            return $Counters;
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            die("Error: Unable to fetch Counter. " . $e->getMessage());
        }
    }
    public function deleteCounters($CTID)
    {
        try {
            $sql = "DELETE FROM counters WHERE CTID = ?";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$CTID]);
            return true;
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            die("Error: Unable to delete counter number. " . $e->getMessage());
        }
        return false;
    }
}