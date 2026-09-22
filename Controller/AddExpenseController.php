<?php
include "../Includes/includes.php";

// Check if session variables are set
if (!isset($_SESSION['user_id']) || !isset($_SESSION['shop_id'])) {
    die("Error: Shop ID or User ID is not set.");
}

$shop_SHID = $_SESSION['shop_id'];
$user_USID = $_SESSION['user_id'];

$dbObj = new DBTransactions();
$ExpenseObj = new AddExpensesModels();

if(isset($_GET["edit"]))
{
    $EPID = $_POST["EPID"];
    $sql ="SELECT * FROM expenses e
    INNER JOIN expensetransactions et ON et.expense_id=e.EPID
    WHERE e.EPID= $EPID";
    $fetch = $dbObj->getData($sql);
    echo json_encode($fetch);
}

// Insert
if (isset($_POST['btn_save_expense'])) {
    $expense_category_id = $_POST['cmb_expense_category'];
    $expense_paymethod_id = $_POST['cmb_paymethod'];
    $amount = $_POST['amount'];
    $remark = $_POST['remark'];

    // Get current date time
    date_default_timezone_set("Asia/Colombo");
    $effective_date = date("Y-m-d h:i:s");

    // Get next expense id
    $sql = "SELECT max(EPID) as max_expense_id FROM `expenses`;";
    $expData = $dbObj->getData($sql);
    $next_expense_id = floatval($expData[0]['max_expense_id']) + 1;

    // Get counter id
    $sql = "SELECT * FROM cashcounter WHERE user_USID = $user_USID AND CounterStat = 1 AND shop_SHID = $shop_SHID;";
    $counterData = $dbObj->getData($sql);
    $counter_id = empty($counterData) ? 1 : $counterData[0]['CCID'];

    // Save expense
    $ExpenseObj->setExpensesModels($effective_date, $amount, $remark, $expense_category_id, $user_USID, $shop_SHID, $counter_id);
    
    // Save expense transaction
    $exp_transaction_stat = 1;
    $ExpenseObj->setExpensesTransaction($amount, $exp_transaction_stat, $next_expense_id, $expense_paymethod_id);

    header("Location: ../Public/AddExpences.php");
    exit();
}

// Edit (Update) Expense
if (isset($_POST['btn_edit_expense'])) {
    $EPID = $_POST['EPID'];
    $EffectiveDate = $_POST['date'];
    $expense_category_id = $_POST['cmb_expense_category'];
    $expense_paymethod_id = $_POST['cmb_paymethod'];
    $amount = $_POST['amount'];
    $remark = $_POST['remark'];

    $sql="UPDATE expenses SET EffectiveDate = '$EffectiveDate', ExpenseAmount = '$amount', expensecategory_id = '$expense_category_id', ExpenseReason = '$remark' WHERE EPID = '$EPID';";
    echo $sql."<br>";
    $update=$dbObj->executeTransaction($sql);

    $sql="DELETE FROM expensetransactions WHERE expense_id = '$EPID';";
    echo $sql."<br>";
    $update=$dbObj->executeTransaction($sql);
    $sql="INSERT INTO expensetransactions (expTransactionAmount, expTransactionStat, expense_id, paymethod_id) VALUES ('$amount', 1, '$EPID', '$expense_paymethod_id');";
    echo $sql."<br>";
    $update=$dbObj->executeTransaction($sql);
    $_SESSION["exp_edit"]=1;
    header("Location: ../Public/AddExpences.php");

}

// Delete Expense
if (isset($_POST['delete_expense'])) {
    $EPID = $_POST['EPID'];

    if (empty($EPID)) {
        die("Error: Expense ID is missing.");
    }

    $ExpenseObj->deleteExpensesTransaction($EPID);
    $result = $ExpenseObj->deleteExpenses($EPID);
    
    if ($result) {
        echo "Expense Deleted Successfully";
    } else {
        echo "Error: Couldn't delete the expense";
    }
    exit();
}
?>