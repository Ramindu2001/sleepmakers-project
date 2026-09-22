<?php 
include "../Includes/includes.php";
include "../Includes/authcheck.php";

//Label templates (Barcodes/*.php) are program code that the server runs.
//
//This controller used to accept a .php upload into Barcodes/ - and, when editing a label, a file
//of any type - without checking that anyone was logged in, so anybody on the internet could place
//a file there and run any code on this server and every application and database it hosts. So:
//  - it needs a logged in user (printing labels keeps working as before)
//  - a label template can no longer be uploaded from the browser. Templates are installed by the
//    developer into Barcodes/ (under version control); the barcode module's label printer
//    (Products > Print Barcode) needs no template file at all
//  - an existing label's settings can still be edited

if(isset($_POST['btn_save_label']))
{
    //a new label always needs an uploaded template file, which is no longer accepted
    $_SESSION['label_update'] = 4;
    header("Location: ../Public/label.php");
    exit();
}//save label

if(isset($_POST['btn_update_label']))
{
    if(!empty($_FILES['barcode_file']['name']) || (isset($_FILES['barcode_file']['size']) && $_FILES['barcode_file']['size'] > 0))
    {
        //replacing the template file is an upload too
        $_SESSION['label_update'] = 4;
        header("Location: ../Public/label.php");
        exit();
    }//has file

    $dbObj = new DBTransactions();

    $label_id = filter_var($_POST['hide_label_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $lbl_name = $_POST['lbl_name'];

    $dpi = $_POST['dpi'];
    $num_row = $_POST['num_row'];
    $num_col = $_POST['num_col'];
    $lbl_width = $_POST['lbl_width'];
    $lbl_height = $_POST['lbl_height'];

    $stk_width = $_POST['stk_width'];
    $stk_height = $_POST['stk_height'];
    $stk_margin_left = $_POST['stk_margin_left'];
    $stk_margin_right = $_POST['stk_margin_right'];
    $stk_margin_top = $_POST['stk_margin_top'];
    $stk_margin_bottom = $_POST['stk_margin_bottom'];

    $lbl_stat = isset($_POST['chk_lbl_stat']) ? 1 : 0;

    $shop_id = $_POST['cmb_shop'];

    $labelData = ($label_id === false) ? [] : $dbObj->getMultipleData("SELECT * FROM label
        INNER JOIN shop ON shop.SHID = label.shop_id 
        WHERE LBID = ?;", [$label_id]);

    if(empty($labelData))
    {
        $_SESSION['label_update'] = 0;
        header("Location: ../Public/label.php");
        exit();
    }//no such label

    //settings only: the label keeps its installed template file
    $previous_file = $labelData[0]['LabelPath'];

    $lblObj = new Label();
    $lblObj->editLabel($lbl_name, $previous_file, $dpi, $num_row, $num_col, $lbl_width, $lbl_height, $stk_width, $stk_height, $stk_margin_left, $stk_margin_right, $stk_margin_top, $stk_margin_bottom, $lbl_stat, $shop_id, $label_id);

    $_SESSION['label_update'] = 3;
    header("Location: ../Public/label.php");
    exit();
}//update

if(isset($_POST['btn_print_barcode']))
{
    $dbObj = new DBTransactions();
    $shop_id = $_SESSION['shop_id'];

    $sql = "SELECT * FROM `label` WHERE shop_id =".$shop_id." and lblStat = 1;";
    $lblData = $dbObj->getData($sql);

    $barcode_file_name = "";
    if(!empty($lblData))
    {
        $barcode_file_name = $lblData[0]['LabelPath'];
    }
    else
    {
        $barcode_file_name = "barcode_one.php";
    }

    $product_id = $_POST['hide_label_product_id'];
    $label_price = $_POST['label_price'];

    // echo "prod - " . $product_id . "<br>";
    // echo "shop - " . $shop_id . "<br>";

    header("Location: ../Barcodes/" . $barcode_file_name . "?label=" . $product_id . "_" . $label_price);
}

//=================================== Function ================================//
function activeLabel($label_id, $shop_id)
{
    $dbObj = new DBTransactions();
    $lblObj = new Label();

    $sql = "SELECT * FROM `label` WHERE shop_id = ".$shop_id.";";
    $lblData = $dbObj->getData($sql);

    if(!empty($lblData))
    {
        foreach($lblData as $row)
        {
            $all_label_id = $row['LBID'];
            $lbl_stat = 0;
            
            $lblObj->editLabelStatus($lbl_stat, $all_label_id);
        }//foreach

        $lbl_stat = 1;
        //update 
        $lblObj->editLabelStatus($lbl_stat, $label_id);
    }//has data
    else
    {
        $lbl_stat = 1;
        //update 
        $lblObj->editLabelStatus($lbl_stat, $label_id);
    }
    
}//active label