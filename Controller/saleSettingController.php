<?php 
include "../Includes/includes.php";

if(isset($_POST['btn_save_salesettings']))
{
    $shop_id = $_POST['cmb_shop'];
    $item_add_option = $_POST['cmb_add_option'];
    $item_add_duration = $_POST['item_add_duration'];
    $invoice_header_text = $_POST['invoice_header_text'];
    $counter_type = $_POST['cmb_counter_type'];
    $Winvoice_header_text = $_POST['Winvoice_header_text'];

    $setting_stat = isset($_POST['setting_stat']) ? 1 : 0;

    $saleObj = new SaleSettings();
    $saleObj->setSaleSettings($item_add_option, $item_add_duration, $invoice_header_text, $setting_stat, $shop_id, $counter_type,$Winvoice_header_text);

    header("Location: ../Public/SaleSettings.php");
    $_SESSION['setting_update'] = 0;
}//save sale settings

if(isset($_POST['btn_update_salesettings']))
{
    $setting_id = $_POST['hide_setting_id'];
    $shop_id = $_POST['cmb_shop'];
    $item_add_option = $_POST['cmb_add_option'];
    $item_add_duration = $_POST['item_add_duration'];
    $invoice_header_text = $_POST['invoice_header_text'];
    $counter_type = $_POST['cmb_counter_type'];
    $Winvoice_header_text = $_POST['Winvoice_header_text'];

    $setting_stat = isset($_POST['setting_stat']) ? 1 : 0;

    $saleObj = new SaleSettings();
    $saleObj->editSaleSettings($item_add_option, $item_add_duration, $invoice_header_text, $setting_stat, $shop_id, $counter_type, $Winvoice_header_text, $setting_id);

    header("Location: ../Public/SaleSettings.php");
    $_SESSION['setting_update'] = 1;
}//update sale settings

if(isset($_POST['btn_delete_salesettings']))
{
    $setting_id = $_POST['hide_setting_id'];

    $saleObj = new SaleSettings();
    $saleObj->deleteSaleSettings($setting_id);

    header("Location: ../Public/SaleSettings.php");
    $_SESSION['setting_update'] = 2;

}//delete sale settings