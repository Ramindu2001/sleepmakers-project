<?php
include "../Includes/includes.php";

// Insert
if (isset($_POST['btn_save_category'])) {
    $expense_ctg = $_POST['expense_ctg'];
    $expense_ETID = $_POST['expense_ETID']; // Retrieve expense_ETID from POST request

    $CategoryObj = new AddExpenseCtgModels();
    if ($CategoryObj->setCategoryModels($expense_ctg, $expense_ETID)) {
        echo "Category Added Successfully";
    } else {
        echo "Error: Unable to add the category";
    }
}

// Delete
if (isset($_POST['delete_category'])) {
    $ECID = $_POST['ECID'];

    if (empty($ECID)) {
        die("Error: Category ID is missing.");
    }

    $CategoryObj = new AddExpenseCtgModels();
    $check = $CategoryObj->checkexpensecat($ECID);
    if(count($check)==0)
    {
        $result = $CategoryObj->deleteCategories($ECID);
        if ($result) {
            echo "Category Deleted Successfully";
        } else {
            echo "Error: Couldn't delete the category";
        }
    }
    else
    {
        echo "Category Cannot Be Deleted As There Are Expenses Assigned.";
    }
    
    exit();
}
