<?php

class AddExpenseCtgModels extends Dbh
{
    public function setCategoryModels($expense_ctg, $expense_ETID)
    {
        try {
            // Check if the user is already assigned to the shop
            $checkSql = "SELECT COUNT(*) FROM expensecategory WHERE expense_ctg = ? AND expense_ETID = ?";
            $checkStmt = $this->connect()->prepare($checkSql);
            $checkStmt->execute([$expense_ctg, $expense_ETID]);
            $count = $checkStmt->fetchColumn();

            if ($count > 0) {
                return "Payment method already assigned to this shop";
            }

            $sql = "INSERT INTO expensecategory (expense_ctg, expense_ETID) VALUES (?, ?)";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$expense_ctg, $expense_ETID]);
            $run = 1;
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            die("Error: Unable to Add Users. " . $e->getMessage());
            $run = 2;
        }
        if ($run == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function getCategories()
    {
        try {
            $stmt = $this->connect()->prepare("SELECT ec.ECID, ec.expense_ctg, et.expense_type FROM expensecategory ec JOIN expensetype et ON ec.expense_ETID = et.ETID");
            $stmt->execute();
            $Categories = $stmt->fetchAll();
            return $Categories;
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            die("Error: Unable to fetch Categories. " . $e->getMessage());
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

    public function checkexpensecat($ECID)
    {
        $sql="SELECT * FROM expenses WHERE expensecategory_id='$ECID'";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute();
        $Type = $stmt->fetchAll();
        return $Type;
    }

    public function deleteCategories($ECID)
    {
        try {
            $sql = "DELETE FROM expensecategory WHERE ECID = ?";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$ECID]);
            return true;
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            die("Error: Unable to delete category. " . $e->getMessage());
        }
        return false;
    }
}
