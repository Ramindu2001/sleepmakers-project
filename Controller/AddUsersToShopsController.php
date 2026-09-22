<?php
include "../Includes/includes.php";

if (isset($_POST['btn_save_Shop'])) {
    // Get data from AJAX request
    $shop_SHID = $_POST['shop_SHID'];
    $user_USID = $_POST['user_USID'];

    if (empty($shop_SHID) || empty($user_USID)) {
        error_log("Shop ID or User ID is missing.");
        die("Error: Shop ID or User ID is missing.");
    }

    $shopObj = new AddUsersModels();
    $result = $shopObj->setUserModels($shop_SHID, $user_USID);
    if ($result == true) {
        echo "User Assigned";
    } else {
        echo "Error: Couldn't Assigned the User";
    }
}

if (isset($_POST['check_user_shop'])) {
    $shop_SHID = $_POST['shop_SHID'];
    $user_USID = $_POST['user_USID'];

    if (empty($shop_SHID) || empty($user_USID)) {
        die("Error: Shop ID or User ID is missing.");
    }

    $shopObj = new AddUsersModels();
    $result = $shopObj->setUserModels($shop_SHID, $user_USID);

    if ($result === "User already assigned to this shop") {
        echo "User already assigned to this shop.";
    } else {
        echo "User not assigned to this shop yet.";
    }
}

if (isset($_POST['check_user_delete'])) {
    $SUID = $_POST['SUID'];
    $ShopObj = new AddUsersModels();
    echo $ShopObj->checkUserDelete($SUID);
    exit();
}

if (isset($_POST['delete_user'])) {
    $SUID = $_POST['SUID'];
    if (empty($SUID)) {
        echo "Error: SUID is missing.";
        exit();
    }
    $ShopObj = new AddUsersModels();
    // Double-check before deleting
    $checkResult = $ShopObj->checkUserDelete($SUID);
    if ($checkResult === "User can be deleted.") {
        $result = $ShopObj->deleteUserShop($SUID);
        if ($result) {
            echo "User deleted successfully.";
        } else {
            echo "Error: Could not delete user.";
        }
    } else {
        echo $checkResult;
    }
    exit();
}


// Handle AJAX request to fetch current values for editing
if (isset($_POST['get_user_shop_data'])) {
    $SUID = $_POST['SUID'];

    if (empty($SUID)) {
        die("Error: SUID is missing.");
    }

    $shopObj = new AddUsersModels();
    $userData = $shopObj->getUserShopData($SUID);

    if ($userData) {
        echo json_encode(array("success" => true, "shop_SHID" => $userData['shop_SHID'], "user_USID" => $userData['user_USID']));
    } else {
        echo json_encode(array("success" => false));
    }
}