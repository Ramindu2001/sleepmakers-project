<?php
include "../Includes/includes.php";

// Insert
if (isset($_POST['btn_save_type'])) {
    $expense_type = $_POST['expense_type'];

    $ExpenseTypeObj = new AddExpenseTypeModels();
    if ($ExpenseTypeObj->setTypeModels($expense_type)) {
        echo "Type Added Successfully";
    } else {
        echo "Error: Unable to add the type";
    }
}

// Delete
if (isset($_POST['delete_type'])) {
    $ETID = $_POST['ETID'];

    if (empty($ETID)) {
        die("Error: Type ID is missing.");
    }

    $TypeObj = new AddExpenseTypeModels();
    $check=$TypeObj->checktypes($ETID);
    $count=count($check);
    if($count==0)
    {
        $result = $TypeObj->deletetypes($ETID);
        if ($result) {
            echo "Type Deleted Successfully";
        } else {
            echo "Error: Couldn't delete the type";
        }
    }
    else
    {
        echo "Type Cannot Be Deleted As There Are Categories Assigned";
    }
    
    exit();
}
?>