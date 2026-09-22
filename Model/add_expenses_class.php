<?php

class AddExpensesModels extends Dbh
{
    public function setExpensesModels($EffectiveDate, $ExpenseAmount, $ExpenseReason, $expensecategory_id, $user_USID, $shop_SHID, $counter_id)
    {
        try {
            $sql="INSERT INTO expenses(EffectiveDate, ExpenseAmount, ExpenseReason, expensecategory_id, user_USID, shop_SHID, counter_id) VALUES(?,?,?,?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$EffectiveDate, $ExpenseAmount, $ExpenseReason, $expensecategory_id, $user_USID, $shop_SHID, $counter_id]);
            return true;
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            die("Error: Unable to Add the Expense. " . $e->getMessage());
        }
    }

    public function getExpenses()
    {
        try {
            $stmt = $this->connect()->prepare("
            SELECT ep.EPID, ep.EffectiveDate, ec.expense_ctg, ep.ExpenseAmount, ep.ExpenseReason, u.UserName, s.ShopName             
            FROM expenses ep 
            JOIN shop s ON ep.shop_SHID = s.SHID
            JOIN user u ON ep.user_USID = u.USID 
            JOIN expensecategory ec ON ep.ExpenseCategory = ec.ECID");
            $stmt->execute();
            $Expense = $stmt->fetchAll();
            return $Expense;
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            die("Error: Unable to fetch Expenses. " . $e->getMessage());
        }
    }

    public function getOneExpense($EPID)
    {
        try {
            $sql = "SELECT * FROM expenses ep
            INNER JOIN company C ON C.CMID = S.Company_CMID
            INNER JOIN stocktypes ST ON ST.STID = S.StockTypes_STID
            WHERE S.SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$EPID]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            die("Error: Unable to fetch Shop data " . $e->getMessage());
        }
    }

    public function getCategories()
    {
        try {
            $stmt = $this->connect()->prepare("SELECT ECID, expense_ctg FROM expensecategory");
            $stmt->execute();
            $Expense = $stmt->fetchAll();
            return $Expense;
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            die("Error: Unable to fetch Counter. " . $e->getMessage());
        }
    }

    public function updateExpense($EPID, $EffectiveDate, $expense_paymethod_id, $amount, $expense_category_id, $remark,$expTransactionStat,$expTransactionAmount)
    {
      
    try {
        $conn = $this->connect();

        $stmt = $conn->prepare("UPDATE expenses SET EffectiveDate = ?, paymethod_id = ?, ExpenseAmount = ?, expensecategory_id = ?, ExpenseReason = ? WHERE EPID = ?;");
        $stmt->execute([$EPID,$EffectiveDate,$amount,$expense_category_id,$remark]);
        $transaction = $stmt->fetch();

        if ($transaction) {
            $ETID = $transaction['ETID'];

            $deleteStmt = $conn->prepare("DELETE FROM expensetransactions WHERE ETID = ?");
            $deleteStmt->execute([$ETID]);
        }

        $insertStmt = $conn->prepare("INSERT INTO expensetransactions (expTransactionAmount, expTransactionStat, expense_id, paymethod_id) VALUES (?, ?, ?, ?)");
        $insertStmt->execute([$expTransactionAmount, $expTransactionStat, $EPID, $expense_paymethod_id]);

        return true;
    } catch (PDOException $e) {
        error_log("PDOException: " . $e->getMessage());
        die("Error: Unable to update expense transaction. " . $e->getMessage());
    }
}

    public function deleteExpenses($EPID)
    {
        try {
            $sql = "DELETE FROM expenses WHERE EPID = ?";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$EPID]);
            return true;
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            die("Error: Unable to delete expense. " . $e->getMessage());
        }
    }
    //================== expense transaction =================//
    public function setExpensesTransaction($expTransactionAmount, $expTransactionStat, $expense_id, $paymethod_id)
    {
        try 
        {
            $sql="INSERT INTO `expensetransactions`(`expTransactionAmount`, `expTransactionStat`, `expense_id`, `paymethod_id`) VALUES (?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$expTransactionAmount, $expTransactionStat, $expense_id, $paymethod_id]);
            return true;
        } catch (PDOException $e)
        {
            error_log("PDOException: " . $e->getMessage());
            die("Error: Unable to Add the Expense. " . $e->getMessage());
        }
    }//add expense transaction


    public function deleteExpensesTransaction($exp_transaction_id)
    {
        try 
        {
            $sql="DELETE FROM `expensetransactions` WHERE ETID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$exp_transaction_id]);
            return true;
        } catch (PDOException $e)
        {
            error_log("PDOException: " . $e->getMessage());
            die("Error: Unable to Add the Expense. " . $e->getMessage());
        }
    }//add expense transaction
}
?>