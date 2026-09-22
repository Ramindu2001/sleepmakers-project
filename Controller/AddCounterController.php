<?php
include "../Includes/includes.php";

//insert
if (isset($_POST['btn_save_counter'])) {
    $counterNo = $_POST['counterNo'];

    $CounterObj = new AddCounterModels();
    if ($CounterObj->setCounterModels($counterNo)) {
        echo "Counter Added Successfully";
    } else {
        echo "Error: Unable to add the counter";
    }
}

//delete
if (isset($_POST['delete_counter'])) {
    $CTID = $_POST['CTID'];

    if (empty($CTID)) {
        die("Error: Counter ID is missing.");
    }

    $CounterObj = new AddCounterModels();
    $result = $CounterObj->deleteCounters($CTID);
    if ($result) {
        echo "Counter Number Deleted Successfully";
    } else {
        echo "Error: Couldn't delete the Counter Number";
    }
    exit();
}