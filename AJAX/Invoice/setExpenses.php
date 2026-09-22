<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/add_expenses_class.php";

$dbObj = new DBTransactions();
$expenseObj = new AddExpensesModels();

$shop_id = $_SESSION['shop_id'];
$user_id = $_SESSION['user_id'];

$expense_category_id = $_GET['expense_category_id'];
$expense_amount = $_GET['expense_amount'];
$expense_reason = $_GET['expense_reason'];
$expense_paymethod = $_GET['expense_paymethod'];

//get next expense id
$sql = "SELECT max(EPID) as max_expense_id FROM `expenses`;";
$expData = $dbObj->getData($sql);

$next_expense_id = floatval($expData[0]['max_expense_id']) + 1;

//get current date time
date_default_timezone_set("Asia/Colombo");
$effective_date = date("Y-m-d h:i:s");

//get counter id
$sql = "SELECT * FROM cashcounter WHERE user_USID = ".$user_id." AND CounterStat = 1 AND shop_SHID = ".$shop_id.";";
$counterData = $dbObj->getData($sql);

$counter_id = $counterData[0]['CCID'];

$expenseObj->setExpensesModels($effective_date, $expense_amount, $expense_reason, $expense_category_id, $user_id, $shop_id, $counter_id);

$expense_trasnaction_stat = 1;
$expenseObj->setExpensesTransaction($expense_amount, $expense_trasnaction_stat, $next_expense_id, $expense_paymethod);

echo "Expense added successfully... ";