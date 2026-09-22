<?php
include "../Includes/includes.php";
include "../Includes/authcheck.php";

//Receipt templates (Receipts/*.php) are program code that the server runs.
//
//This controller used to accept a .php upload into Receipts/ without even checking that anyone
//was logged in, so anybody on the internet could place a file there and run any code on this
//server - and every other application and database it hosts. So:
//  - only a logged in administrator (UserType 1, as for the Sale Settings page) can reach it
//  - a template can no longer be uploaded from the browser at all. Templates are installed by
//    the developer into Receipts/ (under version control); already installed ones keep working,
//    and their name / shop / default / active settings can still be changed here
//  - deleting a receipt removes its setting only, never a program file
if($userObj->checkusertype($_SESSION['user_id']) != 1)
{
    header("Location: ../Public/home.php");
    exit();
}//administrators only

if(isset($_POST['btn_save_receipt']))
{
    //a new receipt always needs an uploaded template file, which is no longer accepted
    $_SESSION['setting_update'] = 9;
    header("Location: ../Public/SaleSettings.php");
    exit();
}//add shop receipt

if(isset($_POST['btn_update_receipt']))
{
    $receipt_id = filter_var($_POST['hide_receipt_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $shop_id = filter_var($_POST['cmb_shop_receipts'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $receipt_name = isset($_POST['receipt_name']) ? trim((string)$_POST['receipt_name']) : "";
    $default_receipt = isset($_POST['default_receipt']) ? 1 : 0;
    $receipt_stat = isset($_POST['active_receipt']) ? 1 : 0;
    $wholesaleRecipt = isset($_POST['wholesaleRecipt']) ? 2 : 1;

    if(!empty($_FILES['shop_receipt']['name']))
    {
        //replacing the template file is an upload too
        $_SESSION['setting_update'] = 9;
        header("Location: ../Public/SaleSettings.php");
        exit();
    }//has a file

    if($receipt_id === false || $shop_id === false)
    {
        $_SESSION['setting_update'] = 3;
        header("Location: ../Public/SaleSettings.php");
        exit();
    }//not a valid receipt or shop

    //settings only
    $receiptObj = new ShopReceipt();

    if($default_receipt == 1)
    {
        RemoveAllDefault($shop_id);
    }//update default receipt

    $receiptObj->editShopReceipt($receipt_name, $shop_id, $default_receipt, $receipt_stat, $receipt_id,$wholesaleRecipt);

    $_SESSION['setting_update']=7;
    header("Location: ../Public/SaleSettings.php");
    exit();
}//update receipt

if(isset($_POST['btn_delete_receipt']))
{
    $receipt_id = filter_var($_POST['hide_receipt_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if($receipt_id !== false)
    {
        //the setting only: the template file is program code and stays where the developer installed it
        $receiptObj = new ShopReceipt();
        $receiptObj->deleteOneReceipt($receipt_id);
    }//valid receipt

    $_SESSION['setting_update']=8;
    header("Location: ../Public/SaleSettings.php");
    exit();
}//delete shop receipt

//============================ Functions ==========================//
function RemoveAllDefault($shop_id)
{
    $receiptObj = new ShopReceipt();
    $receiptObj->editAllDefaultReceipt($shop_id);
}//remove all defaults
