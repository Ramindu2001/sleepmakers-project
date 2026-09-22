<?php 
include "../Includes/includes.php";
$shop_id = $_SESSION['shop_id'];
$user_id = $_SESSION['user_id'];

if(isset($_POST['btn_add_grn_transfer']))
{
    $transfer_from = $_POST['hide_transfer_from'];
    $transfer_to = $_POST['cmb_transfer_shop'];

    $transObj = new Transfer();
    $transData = $transObj->getTransferMax($shop_id);
    //$max_transfer = $transData[0]['maxTransfer'];

    $commObj = new Common();

    if(!empty($transfer_to))
    {
        $transfer_no = 0;
      
        if(!empty($transData))
        {
            $max_value = floatval($transData[0]['maxTransfer']);
            $max_value += 1;
            $transfer_no = $commObj->createCount("GT", $max_value);
        }//empty
        else
        {
            $transfer_no = $commObj->createCount("GT", 1);
        }//has max
    
        //effective date
        date_default_timezone_set("Asia/Colombo");
        $effective_date = date("Y-m-d");
    
        //count
        $transfer_count = 0;
        //start amount
        $transfer_amount = 0;
        //on hold
        $transfer_stat = 0;
    
        $transObj->setTransfer($transfer_no, $effective_date, $transfer_from, $transfer_to, $transfer_count, $transfer_amount, $transfer_stat, $shop_id, $user_id);
        
        header("Location: ../Public/transfer-header.php");
        $_SESSION['transfer_update'] = 1;

    }//has transfer shop
    else
    {
        $_SESSION['transfer_update'] = 4;
        header("Location: ../Public/transfer-header.php");
        die("error: no shop to transfer");
    }//no shop to transfer
}//create new transfer header

//================================= GRN Transfer =====================================//
if(isset($_POST['btn_pending_transfer']))
{
    $transfer_header_id = (int)$_POST['hide_transferheader_id'];
    $tranObj = new Transfer();
    $transfer_stat = 1;

    //only the sending shop sends its own transfer
    $headerRow = getTransferForShop($transfer_header_id, $shop_id);
    if($headerRow === null || $headerRow['TransferFrom'] != $shop_id)
    {
        $_SESSION['transfer_update'] = 8;
        header("Location: ../Public/transfer-header.php");
        exit;
    }//not this shop's transfer

    if(empty($tranObj->getTransferDetailByHeader($transfer_header_id)))
    {
        $_SESSION['transfer_detail_update'] = 1;
        header("Location: ../Public/transfer-details.php?id=".$transfer_header_id);
        exit;
    }//nothing to send

    $tranObj->beginDbTransaction();

    //update header stat (only a transfer that is still on hold can be sent)
    if($tranObj->editTransferHeaderStatIfIn($transfer_stat, $transfer_header_id, [0]) == 0)
    {
        $tranObj->rollbackDbTransaction();
        $_SESSION['transfer_update'] = 8;
        header("Location: ../Public/transfer-header.php");
        exit;
    }//already sent / verified / cancelled

    //update transfer detail
    $tranObj->editTransferDetailStat($transfer_stat, $transfer_header_id);

    $tranObj->commitDbTransaction();

    $_SESSION['transfer_update'] = 6;
    header("Location: ../Public/transfer-header.php");
}//pending transfer

if(isset($_POST['btn_verify_transfer']))
{
    $transfer_header_id = (int)$_POST['hide_transferheader_id'];
    $details_page = "Location: ../Public/transfer-details.php?id=".$transfer_header_id;

    $tranObj = new Transfer();

    //only the sending or the receiving shop can verify
    $headerRow = getTransferForShop($transfer_header_id, $shop_id);
    if($headerRow === null)
    {
        $_SESSION['transfer_update'] = 8;
        header("Location: ../Public/transfer-header.php");
        exit;
    }//not this shop's transfer

    //payment is optional; when an amount is entered it needs a valid pay method
    $paymethod_id = 0;
    $payment_amount = 0;
    $payment_error = readTransferPayment($shop_id, $paymethod_id, $payment_amount);
    if($payment_error > 0)
    {
        $_SESSION['transfer_detail_update'] = $payment_error;
        header($details_page);
        exit;
    }//payment not valid

    $from_shop_id = $headerRow['TransferFrom'];
    $transfer_shop_id = $headerRow['TransferTo'];

    //the stock leaves one shop and arrives in the other in a single database transaction:
    //every line is moved, or nothing is
    $tranObj->beginDbTransaction();

    //claim the transfer first: only a transfer that is still on hold / pending can be verified,
    //so a double click, a refresh or a second user can never move the stock twice
    $transfer_stat = 2;
    if($tranObj->editTransferHeaderStatIfIn($transfer_stat, $transfer_header_id, [0, 1]) == 0)
    {
        $tranObj->rollbackDbTransaction();
        $_SESSION['transfer_update'] = 8;
        header("Location: ../Public/transfer-header.php");
        exit;
    }//already verified / cancelled

    $dbObj = new DBTransactions();

    $sql = "SELECT * FROM transferdetails WHERE TransferHeader_THID = ?;";
    $dbData = $dbObj->getMultipleData($sql, [$transfer_header_id]);

    if(empty($dbData))
    {
        $tranObj->rollbackDbTransaction();
        $_SESSION['transfer_detail_update'] = 1;
        header($details_page);
        exit;
    }//not transfer rows

    //check Qty in the sending shop
    $short_items = checkQty($transfer_header_id, $from_shop_id);
    if(!empty($short_items))
    {
        $tranObj->rollbackDbTransaction();
        $_SESSION['transfer_detail_update'] = 0;
        $_SESSION['transfer_short_items'] = $short_items;
        header($details_page);
        exit;
    }//qty check failed

    $row_count = 0;
    $transfer_amount = 0;
    foreach($dbData as $row)
    {
        $row_count += 1;
        $transfer_amount += floatval($row['TransferTotalAmount']);

        $product_id = $row['products_PDID'];
        $transfer_qty = floatval($row['TransferQty']);
        $receive_qty = floatval($row['ReceivedQty']);
        $purchase_price = $row['UnitPurchasePrice'];
        $selling_price = $row['UnitSellingPrice'];
        $inventory_id = $row['InventoryID'];
        $variation_id = $row['VariationID'];
        $mnf_date = $row['MnfDate'];
        $exp_date = $row['ExpDate'];
        $batch_id = $row['Batch_ID'];

        //nothing received for this line: it stays in the history but no stock moves
        if($receive_qty <= 0)
        {
            continue;
        }

        //the stock is booked against the receiving shop's own product (added to that shop's
        //product list the first time it arrives), so its POS, product list and barcode search see it
        $dest_product_id = $tranObj->getDestinationProductID($product_id, $transfer_shop_id, $user_id);
        if(empty($dest_product_id))
        {
            $tranObj->rollbackDbTransaction();
            $_SESSION['transfer_detail_update'] = 1;
            header($details_page);
            exit;
        }//product no longer exists
        $dest_variation_id = $tranObj->getDestinationVariationID($variation_id, $product_id, $dest_product_id);

        //deduct current qty from this shop
        $deduct_qty = -1 * $receive_qty;
        updateInvCurrentQty($deduct_qty, $inventory_id);

        //add transfer out qty
        updateInvTransferOutQty($receive_qty, $inventory_id);

        //get current date time
        date_default_timezone_set("Asia/Colombo");
        $effective_date = date("Y-m-d");

        //default values
        $bill_qty = 0;
        $return_qty = 0;
        $transfer_in_qty = $receive_qty;
        $transfer_out_qty = 0;
        $current_qty = $receive_qty;
        $rack_id = 1;
        $label_price = $selling_price;
        $grn_detail_id = 0;

        //insert into transfer shop inventory, and link the price history to the row
        //that was really created (not a guessed max(INID)+1)
        $sql = "INSERT INTO inventory(CurrentQty, BillQty, ReturnQty, TransferInQty, TransferOutQty, products_PDID, shop_SHID, RackID, BatchID) VALUES(?,?,?,?,?,?,?,?,?);";
        $new_inventory_id = $dbObj->executeTransactionAndReturnLastInsertID($sql, [$current_qty, $bill_qty, $return_qty, $transfer_in_qty, $transfer_out_qty, $dest_product_id, $transfer_shop_id, $rack_id, $batch_id]);

        //add to price history table
        $priceObj = new PriceHistory();
        $priceObj->setPriceHistory($dest_product_id, $dest_variation_id, $effective_date, $purchase_price, $selling_price, $label_price, $mnf_date, $exp_date, $batch_id, $new_inventory_id, $grn_detail_id);

        //the receiving shop's product record shows the prices this stock sells at (POS tiles, product list)
        $prodObj = new Product();
        $prodObj->setCurrentPrices($dest_product_id, $purchase_price, $selling_price);

    }//foreach

    //effective date
    date_default_timezone_set("Asia/Colombo");
    $effective_date = date("Y-m-d");
    //update totals
    $tranObj->editTransferTotals($effective_date, $row_count, $transfer_amount, $transfer_header_id);

    //update transfer detail
    $tranObj->editTransferDetailStat($transfer_stat, $transfer_header_id);

    //add transfer transaction only when a payment was actually entered
    if($payment_amount > 0)
    {
        $tranObj->setTransferTransaction($payment_amount, 1, $transfer_header_id, $paymethod_id);
    }//has payment

    $tranObj->commitDbTransaction();

    $_SESSION['transfer_update'] = 5;
    header("Location: ../Public/transfer-header.php");
}//transfer

if(isset($_POST['btn_cancle_transfer']))
{
    $transfer_header_id = (int)$_POST['hide_transferheader_id'];
    $tranObj = new Transfer();
    $transfer_stat = 3;

    if(getTransferForShop($transfer_header_id, $shop_id) === null)
    {
        $_SESSION['transfer_update'] = 8;
        header("Location: ../Public/transfer-header.php");
        exit;
    }//not this shop's transfer

    $tranObj->beginDbTransaction();

    //update header stat (a verified transfer has already moved stock, so it can't just be cancelled)
    if($tranObj->editTransferHeaderStatIfIn($transfer_stat, $transfer_header_id, [0, 1]) == 0)
    {
        $tranObj->rollbackDbTransaction();
        $_SESSION['transfer_update'] = 8;
        header("Location: ../Public/transfer-header.php");
        exit;
    }//already verified / cancelled

    //update transfer detail
    $tranObj->editTransferDetailStat($transfer_stat, $transfer_header_id);

    $tranObj->commitDbTransaction();

    $_SESSION['transfer_update'] = 7;
    header("Location: ../Public/transfer-header.php");
}//close transfer

//============================ Functions ===============================//
//the logged-in shop must be the sending or the receiving shop of the transfer
function getTransferForShop($transfer_header_id, $shop_id)
{
    $tranObj = new Transfer();
    $headerData = $tranObj->getOneTransferHeader($transfer_header_id);

    if(empty($headerData))
    {
        return null;
    }
    if($headerData[0]['TransferFrom'] != $shop_id && $headerData[0]['TransferTo'] != $shop_id)
    {
        return null;
    }
    return $headerData[0];
}//get transfer for shop

//payment is optional: no amount (or 0) means no payment is recorded.
//returns 0 when fine, 3 = amount without a valid pay method, 4 = amount not valid
function readTransferPayment($shop_id, &$paymethod_id, &$payment_amount)
{
    $paymethod_id = (isset($_POST['cmb_paymethod']) && $_POST['cmb_paymethod'] !== '') ? (int)$_POST['cmb_paymethod'] : 0;
    $amount_text = isset($_POST['transfer_amount']) ? trim($_POST['transfer_amount']) : '';
    $payment_amount = 0;

    if($amount_text === '')
    {
        return 0;
    }//no payment
    if(!is_numeric($amount_text) || floatval($amount_text) < 0 || floatval($amount_text) > 99999999.99)
    {
        return 4;
    }//not a valid amount

    $payment_amount = round(floatval($amount_text), 2);
    if($payment_amount > 0)
    {
        //the pay method must be one this shop accepts
        $dbObj = new DBTransactions();
        $sql = "SELECT SPID FROM shoppaymethod WHERE shop_SHID = ? AND paymethod_PMID = ?;";
        if($paymethod_id <= 0 || empty($dbObj->getMultipleData($sql, [$shop_id, $paymethod_id])))
        {
            return 3;
        }
    }//has payment
    return 0;
}//read transfer payment

//each line is checked against the exact source inventory row it will be deducted from,
//for the qty that will really move (the received qty). The rows stay locked until the
//transfer is committed, so a sale can't take the same stock in between.
//returns the names of the items that are short (empty = all good)
function checkQty($transfer_header_id, $shop_id)
{
    $dbObj = new DBTransactions();

    $sql = "SELECT transferdetails.InventoryID, SUM(transferdetails.ReceivedQty) AS moveQty, MAX(products.ItemName) AS ItemName
    FROM transferdetails
    INNER JOIN products ON products.PDID = transferdetails.products_PDID
    WHERE transferdetails.TransferHeader_THID = ?
    GROUP BY transferdetails.InventoryID
    ORDER BY transferdetails.InventoryID;";
    $dbData = $dbObj->getMultipleData($sql, [$transfer_header_id]);

    $short_items = [];

    foreach ($dbData as $row)
    {
        $move_qty = round(floatval($row['moveQty']), 3);

        $sql_1 = "SELECT CurrentQty, shop_SHID FROM inventory WHERE INID = ? FOR UPDATE;";
        $invData = $dbObj->getMultipleData($sql_1, [$row['InventoryID']]);

        //check current qty
        if(empty($invData) || $invData[0]['shop_SHID'] != $shop_id || round(floatval($invData[0]['CurrentQty']), 3) < $move_qty)
        {
            $short_items[] = $row['ItemName'];
        }//qty check failed
    }//foreach

    return $short_items;
}//check qty

function updateInvCurrentQty($update_qty, $inventory_id)
{
    //single atomic update, so a sale made at the same moment is never overwritten
    $dbObj = new DBTransactions();
    $sql = "UPDATE inventory SET CurrentQty = IFNULL(CurrentQty, 0) + ? WHERE INID = ?;";
    $dbObj->executeTransactionWithArray($sql, [$update_qty, $inventory_id]);
}//update inventory current qty

function updateInvTransferInQty($update_qty, $inventory_id)
{
    $invObj = new Inventory();
    $invData = $invObj->getOneInventory($inventory_id);

    $transfer_in_qty = floatval($invData[0]['TransferInQty']);
    $new_transfer_in_qty = $transfer_in_qty + $update_qty;

    $invObj->editInvTransferInQty($new_transfer_in_qty, $inventory_id);
}//update transfer in qty

function updateInvTransferOutQty($update_qty, $inventory_id)
{
    //adds to the row's own TransferOutQty (it used to start from TransferInQty,
    //which overwrote earlier transfers out of the same row)
    $dbObj = new DBTransactions();
    $sql = "UPDATE inventory SET TransferOutQty = IFNULL(TransferOutQty, 0) + ? WHERE INID = ?;";
    $dbObj->executeTransactionWithArray($sql, [$update_qty, $inventory_id]);
}//update transfer out qty

function addPriceHistory($product_id, $purchase_price, $selling_price, $shop_id)
{
    //PHID, ProductID, VariationID, EffectiveDate, PurchasePrice, SellingPrice, MnfDate, ExpDate, BatchID, Inventory_INID
    $dbObj = new DBTransactions();
    $sql = "SELECT * FROM inventory WHERE shop_SHID = ".$shop_id." AND products_PDID = ".$product_id." LIMIT 1;";

    $dbData = $dbObj->getData($sql);
    $inventory_id = $dbData[0]['INID'];

    $sql_1 = "SELECT * FROM pricehistory WHERE Inventory_INID = ".$inventory_id." AND ProductID = ".$product_id." ORDER BY PHID DESC;";
    $phData = $dbObj->getData($sql_1);

    $variation_id = $phData[0]['VariationID'];
    $mnf_date = $phData[0]['MnfDate'];
    $exp_date = $phData[0]['ExpDate'];
    $batch_id = 1;
    $label_price = $selling_price;

    //effective date
    date_default_timezone_set("Asia/Colombo");
    $effective_date = date("Y-m-d");
    $grn_detail_id = 0;

    $priceObj = new PriceHistory();
    $priceObj->setPriceHistory($product_id, $variation_id, $effective_date, $purchase_price, $selling_price, $label_price, $mnf_date, $exp_date, $batch_id, $inventory_id, $grn_detail_id);

}//add to pricelist