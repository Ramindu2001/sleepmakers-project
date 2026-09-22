<?php

class AddExpenseTypeModels extends Dbh
{
    public function setTypeModels($expense_type)
    {
        try {
            $sql = "INSERT INTO expensetype (expense_type) VALUES (?)";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$expense_type]);
            $run = 1;
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            die("Error: Unable to Add the Type. " . $e->getMessage());
            $run = 2;
        }
        if ($run == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function getTypes()
    {
        try {
            $stmt = $this->connect()->prepare("SELECT ETID, expense_type FROM expensetype");
            $stmt->execute();
            $Type = $stmt->fetchAll();
            return $Type;
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            die("Error: Unable to fetch Type. " . $e->getMessage());
        }
    }

    public function checktypes($ETID)
    {
        $sql="SELECT * FROM expensecategory WHERE expense_ETID='$ETID'";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute();
        $Type = $stmt->fetchAll();
        return $Type;
    }
    public function deletetypes($ETID)
    {
        try {
            $sql = "DELETE FROM expensetype WHERE ETID = ?";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$ETID]);
            return true;
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            die("Error: Unable to delete type. " . $e->getMessage());
        }
        return false;
    }
}