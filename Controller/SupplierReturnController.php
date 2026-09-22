<?php
include "../Includes/includes.php";
$shop_id = $_SESSION['shop_id'];
$user_id = $_SESSION['user_id'];

$returnObj = new SupplierReturn();

if (isset($_POST['btn_add_return'])) {
    // Create a new SupplierReturn object
    $returnObj = new SupplierReturn();

    // Retrieve the maximum return number for the current shop
    $returnData = $returnObj->getReturnMax($shop_id);

    // Create a new Common object
    $commObj = new Common();
    $return_no = 0;

    // Calculate the new return number
    if (!empty($returnData)) {
        $max_value = floatval($returnData[0]['MaxReturn']);
        $max_value += 1;
        $return_no = $commObj->createCount("SR", $max_value);
    } else {
        $return_no = $commObj->createCount("SR", 1);
    }

    // Set the effective date to the current date
    date_default_timezone_set("Asia/Colombo");
    $effective_date = date("Y-m-d");

    // Placeholder values for return amount and status
    $return_amount = 0;
    $return_stat = 0;

    // Retrieve the supplier ID from the POST data
    $Supplier_SPID = $_POST['cmb_supplier']; // Ensure this POST data is correctly set
    $shop_SHID = $shop_id;
    $user_USID = $user_id;

    // Print values for debugging purposes (optional, remove in production)
    //echo json_encode([$return_no, $effective_date, $return_amount, $return_stat, $Supplier_SPID, $shop_SHID, $user_USID]);

    // Insert the new supplier return record into the database
    $returnObj->setSupplierReturn($return_no, $effective_date, $return_amount, $return_stat, $Supplier_SPID, $shop_SHID, $user_USID);

    // Redirect to the supplier return page
     header("Location: ../Public/supplier_return.php");
}


if (isset($_POST['btn_goto_details'])) {
    $return_header_id = $_POST['return_header_id'];
    $return_header_stat = $_POST['return_header_stat'];

    error_log("Received Return Header ID: " . $return_header_id);
    error_log("Received Return Header Stat: " . $return_header_stat);

    // Redirect to the details page if needed
    // header("Location: details-page.php");
} else {
    // Handle the case where form data is not received
    error_log("Form data not received");
}

//================================ SRN Details =================================//
if (isset($_POST['btn_pending_srn'])) {
    // Retrieve SRN header ID from POST request
    $srn_header_id = $_POST['hide_srnheader_id'];
    $srn_stat = 1;
   
    // Update SRN header status to 1
    $result = $returnObj->editSupplierReturnStat($srn_stat, $srn_header_id);

    // Update SRN detail status to 1
    $returnObj->editSRNDetailStat($srn_stat, $srn_header_id);
    
    // Redirect to the supplier_return.php page
    header("Location: ../Public/supplier_return.php");
    exit(); // Ensure no further code is executed after redirection
}

if (isset($_POST['btn_verify_srn'])) {
    // Retrieve the supplier return note header ID from the POST data
    $srn_header_id = htmlspecialchars($_POST['hide_srnheader_id']);

    // SQL query to select supplier return details with named parameters
    $sql = "SELECT SRDID, ProductID, products.ProdDescription, SD.UnitPurchasePrice , SD.InventoryID , SD.VariationID, variations.VariationName, SD.Batch, SD.ReturnQty, SD.ReturnAmount
    FROM supplierreturndetails SD
    INNER JOIN supplierreturn SR ON SR.SRID = SD.supplierreturn_SRID
    LEFT JOIN products ON products.PDID = SD.ProductID
    LEFT JOIN variations ON variations.VRID = SD.VariationID
    WHERE SR.ReturnNo = '" . htmlspecialchars($srn_header_id) . "'
    ORDER BY SRDID DESC";

    // Instantiate database transaction and inventory objects
    $dbObj = new DBTransactions();
    $invObj = new Inventory();
    $returnObj = new SupplierReturn(); // Ensure this is defined correctly

    // Get data from the database with parameter binding
    $dbData = $dbObj->getData($sql, ['srn_header_id' => $srn_header_id]);

    $total_purchase_price = 0;

    // Process each row of data
    foreach ($dbData as $row) {
        $inventory_id = $row['InventoryID'];
        $rtn_qty = floatval($row['ReturnQty']);
        $purchase_price = $row['UnitPurchasePrice'];       
        // Calculate the total purchase price
        $item_purchase_total = $purchase_price * $rtn_qty;
        $total_purchase_price += $item_purchase_total;

        // Update inventory quantities
        $invObj->editInvSupplierReturnQty($rtn_qty, $inventory_id);
    }

    // Update supplier return note statuses
    $srn_stat = 2;
    $returnObj->editSupplierReturnStat($srn_stat, $srn_header_id);
    $returnObj->editSRNDetailStat($srn_stat, $srn_header_id);
    $returnObj->editSRNHeaderVerify($total_purchase_price, $srn_header_id);

    // Redirect to the supplier return page
    header("Location: ../Public/supplier_return.php");
    exit();
    
}//verify supplier return


if(isset($_POST['btn_cancel_srn']))
{
    $srn_header_id = $_POST['hide_srnheader_id'];
    $srn_stat = 3;
    
    //update srn header stat to 3
    $grnObj->editSupplierReturnStat($srn_stat, $srn_header_id);
    //update srn detail stat to 1
    $grnObj->editSRNDetailStat($srn_stat, $srn_header_id);

    header("Location: ../Public/supplier_return.php");
}//cancle srn
