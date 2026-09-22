<?php 
include "../Includes/includes.php";

if(isset($_POST['btn_submit_pay']))
{
    /*
    * --- submit invoice ---
    * check balance --------------------- Done
    * check paymethod -- 
    * check qty in inventory
    * check expire date
    * Insert into Invoice Header
    * Insert into Invoice Detail
    * Delete Sales Details
    * Delete Sales Header
    */
    $user_id = $_SESSION['user_id'];
    $shop_id = $_SESSION['shop_id'];

    $sale_header_id = $_POST['hide_sales_header'];

    $discounted_total = floatval($_POST['hide_discounted_total']);
    $cust_payment = floatval($_POST['cust_payment']);
    $pending_amount = floatval($_POST['hide_pending_amount']);

    $balance = $discounted_total - $pending_amount;

    $rdb_receipt = $_POST['rdb_receipt'];

    if($balance >= 0)
    {

        CreateInvoiceHeader();

        //get active header
        $sql = "SELECT * FROM invoiceheader WHERE user_USID = ".$user_id." AND shop_SHID=".$shop_id." AND InvStat=0;";
        $dbObj = new DBTransactions();
        $headerData = $dbObj->getData($sql);

        $invoice_header_id = $headerData[0]['IHID'];

        CreateInvoiceDetail($shop_id, $invoice_header_id);

        //add service
        AddService($invoice_header_id);

        //update inventory
        UpdateInventory($shop_id);

        //add payment to transaction
        CreateTransaction($sale_header_id, $invoice_header_id);

        //delete multipay
        DeleteMultipay($sale_header_id);

        //delete sales detail
        DeleteSaleDetail($sale_header_id);

        //delete sale detail
        DeleteSaleHeader($sale_header_id);

        //update invoice header stat
        $invObj = new Invoice();
        $invObj->editInvoiceHeaderStat(1, $invoice_header_id);

        //goto receipt print
        header("Location: ../Receipts/".$rdb_receipt."?invoice_id=" . $invoice_header_id);

    }//paid full
    else
    {
        echo "Issue Credit Note";//---------------------------------------->> Flag issue credit note
    }//Issue Credit Note
   
}//submit payment

//============================= Hold Invoice ===============================//
if(isset($_POST['btn_add_hold']))
{
    $user_id = $_SESSION['user_id'];
    $shop_id = $_SESSION['shop_id'];

    $tmp_bill_no = $_POST['hide_hold_tmp_no'];

    $invoiceObj = new Invoice();

    //SHID, BillNo, ItemCount, grossAmount, SellStat, user_id, shop_id, cashcounter_id
    $item_count = 0;
    $gross_amount = 0;
    $sell_stat = 0;

    $sql = "SELECT * FROM cashcounter WHERE user_USID=".$user_id." AND shop_SHID=".$shop_id." AND CounterStat = 1;";

    $dbObj = new DBTransactions();
    $dbData = $dbObj->getData($sql);

    $counter_id = $dbData[0]['CCID'];

    $invoiceObj->setSaleHeader($tmp_bill_no, $item_count, $gross_amount, $sell_stat, $user_id, $shop_id, $counter_id);
    
    header("Location: ../Public/gui-pos.php");
}//hold sale header

if(isset($_POST['btn_open_holded_sale']))
{
    $user_id = $_SESSION['user_id'];
    $shop_id = $_SESSION['shop_id'];

    $sell_header_id = $_POST['hide_sell_header_id'];

    $invObj = new Invoice();

    $invObj->holdAllSaleHeader($user_id, $shop_id);

    $invObj->editSaleHeaderStatOnly(1, $sell_header_id);

    header("Location: ../Public/gui-pos.php");

}//open holded invoice

//========================= test =====================//
if(isset($_POST['btn_test']))
{
    //get active header
    $sql = "SELECT * FROM invoiceheader WHERE user_USID = ".$user_id." AND shop_SHID=".$shop_id." AND InvStat=0;";
    $dbObj = new DBTransactions();
    $headerData = $dbObj->getData($sql);

    $invoice_header_id = $headerData[0]['IHID'];

    AddService($invoice_header_id);
}//test


//========================================= Functions ========================================//
function CreateInvoiceHeader()
{
    $user_id = $_SESSION['user_id'];
    $shop_id = $_SESSION['shop_id'];

    $sale_header_id = $_POST['hide_sales_header'];
    $sale_total = floatval($_POST['hide_sell_total']);
    $discounted_total = floatval($_POST['hide_discounted_total']);
    $cust_payment = floatval($_POST['cust_payment']);

    $discount = $sale_total - $discounted_total;

    $balance = $cust_payment - $discounted_total;

    //IHID, InvoiceNo, EffectiveDate, BillNo, InvStartTime, InvEndTime, InvItemCount, GrossAmount, DiscountAmount, NetAmount, CustPayment, CustBalance, InvStat, user_USID, customers_CTID, Salesmans_SLID, shop_SHID, CashCounter_CCID
    $dbObj = new DBTransactions();
    $comObj = new Common();

    $sql = "SELECT COUNT(IHID) AS HeaderCount FROM invoiceheader WHERE InvStat = 1;";
    $headData = $dbObj->getData($sql);
    $header_count = floatval($headData[0]['HeaderCount']) + 1;

    //get header text
    $sql_1 = "SELECT * FROM salesettings WHERE shop_id = ".$shop_id." AND settingStat=1;";
    $settingData = $dbObj->getData($sql_1);
    $invoice_header_text = $settingData[0]['billNoHeader'];

    $invoice_no = $comObj->createCount($invoice_header_text, $header_count);

    $bill_no = $invoice_no;//=====------------------------------ >>> Should change later

    //effective date
    date_default_timezone_set("Asia/Colombo");
    $effective_date = date("Y-m-d");

    $start_time = date("Y-m-d H:m:s");
    $end_time = date("Y-m-d H:m:s");

    $item_count = $_POST['hide_row_count'];

    $line_discount = 0; //------------------------------------------ >>> get line discount from detail table

    $inv_stat = 0;

    $customer_id = isset($_POST['cmb_customer']) ? $_POST['cmb_customer'] : '1';
    $salesman_id = isset($_POST['cmb_salesman']) ? $_POST['cmb_salesman'] : '1';

    //get cash counter id
    $sql = "SELECT * FROM cashcounter WHERE user_USID = ".$user_id." AND shop_SHID=".$shop_id." AND CounterStat = 1;";
    $counterData = $dbObj->getData($sql);
    $counter_id = $counterData[0]['CCID'];

    $pay_method = $_POST['rdb_paymethod'];

    $percent_discount = 0;
    $fixed_discount = 0;

    echo "header - " . $sale_header_id . "<br>";

    echo "invoice - " . $invoice_no . "<br>";
    echo "date - " . $effective_date . "<br>";
    echo "bill - " . $bill_no . "<br>";
    echo "start - " . $start_time . "<br>";
    echo "end - " . $end_time . "<br>";
    echo "item count - " . $item_count . "<br>";
    echo "gross - " . $sale_total . "<br>";
    echo "discount - " . $discount . "<br>";
    echo "net amount - " . $discounted_total . "<br>";
    echo "cust pay - " . $cust_payment . "<br>";
    echo "balance - " . $balance . "<br>";
    echo "user - " . $user_id . "<br>";
    echo "customer - " . $customer_id . "<br>";
    echo "salesman - " . $salesman_id . "<br>";
    echo "shop - " . $shop_id . "<br>";
    echo "counter - " . $counter_id . "<br>";
    echo "pay - " . $pay_method . "<br>";
    echo "-------- Create Invoice Header -------<br>";

    $invObj = new Invoice();
    $invObj->setInvoiceHeader($invoice_no, $effective_date, $bill_no, $start_time, $end_time, $item_count, $sale_total, $line_discount, $percent_discount, $fixed_discount, $discount, $discounted_total, $cust_payment, $balance, $inv_stat, $user_id, $customer_id, $salesman_id, $shop_id, $counter_id);

}//create invoice header

function CreateInvoiceDetail($shop_id, $invoice_header_id)
{
    $sale_header_id = $_POST['hide_sales_header'];

    $sql = "SELECT * FROM selldetail
    INNER JOIN pricehistory ON pricehistory.PHID = selldetail.pricehistory_id
    INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
    INNER JOIN products ON products.PDID = inventory.products_PDID
    WHERE sellheader_id = ".$sale_header_id.";";

    $dbObj = new DBTransactions();
    $shopObj = new Shop();

    $saleData = $dbObj->getData($sql);
    foreach($saleData as $row)
    {
        $sell_qty = floatval($row['sellQty']);
        $current_qty = floatval($row['CurrentQty']);
        $expire_date = $row['ExpDate'];
        $is_expired = true;
        $batch_id = $row['BatchID'];
        $sell_price = floatval($row['SellingPrice']);
        $percent_discount = $row['itemPercentDiscount'];
        $direct_discount = $row['itemWiseDiscount'];

        $conversion_rate = empty($row['UnitConversion']) ? 1 : floatval($row['UnitConversion']);

        //get unit converted qty
        $unit_sell_qty = $sell_qty / $conversion_rate;

        //get unit sell price
        $unit_sell_price = $sell_price / $conversion_rate;

        if($current_qty > $unit_sell_qty)
        { 
            if($shopObj->hasExpiry($shop_id))
            {
                //current date
                date_default_timezone_set("Asia/Colombo");
                $current_date = date("Y-m-d");

                echo "current date - " . $current_date . "<br>";
                echo "expire date - " . $expire_date . "<br>";

                if($expire_date > $current_date)
                {
                    $is_expired = false;
                }//not expired
                else
                {
                    $is_expired = true;
                    $_SESSION['invoice_update'] = 1; //Item expired
                    header('Location: ../Public/gui-pos.php');
                    die('Error: Not enought qty');
                }//item expired
            }//check expire date
            else
            {
                $is_expired = false;
            }//no expiry date

            if(!$is_expired)
            {
                //submit invoice
                echo "sell qty - " . $row['sellQty'] . "<br>";
                echo "current qty - " . $row['CurrentQty'] . "<br>";

                //IDID, SellQty, SellAmount, SellDiscount, SoldAmount, WarrantyStart, WarrantyEnd, ReferenceNo, InvoiceHeader_IHID, products_PDID, pricehistory_id
                $sell_qty = floatval($row['sellQty']);
                $sell_amount = floatval($row['sellAmount']);
                $sell_discount = floatval($row['sellDiscount']);
                $sold_amount = floatval($row['soldAmount']);

                $warranty_start = date("Y-m-d");
                $warranty_end = date("Y-m-d");
                $reference_no = "";

                $product_id = $row['ProductID'];
                $pricehistory_id = $row['pricehistory_id'];

                $invObj = new Invoice();
                $invObj->setInvoiceDetail($sell_qty, $unit_sell_price, $sell_amount, $percent_discount, $direct_discount, $sell_discount, $sold_amount, $warranty_start, $warranty_end, $reference_no, $invoice_header_id, $product_id, $batch_id);

            }//not expired

        }//check current qty
        else
        {
            $_SESSION['invoice_update'] = 0; //not enough qty
            header('Location: ../Public/gui-pos.php');
            die('Error: Not enought qty');
        }//not enought current qty
        
    }//foreach 
}//create invoice

function AddService($invoice_header_id)
{
    $sale_header_id = $_POST['hide_sales_header'];

    $sql = "SELECT * FROM selldetail
    INNER JOIN products ON products.PDID = selldetail.product_id
    WHERE pricehistory_id=0 AND sellheader_id = $sale_header_id;";

    $dbObj = new DBTransactions();
    $sellData = $dbObj->getData($sql);

    if(!empty($sellData))
    {
        foreach($sellData as $row)
        {
            echo "ItemName = " . $row['ItemName'];
            $sell_qty = 1;
            $unit_sell_price = $row['unitSellAmount'];
            $sell_amount = $row['sellAmount'];
            $percent_discount = $row['itemPercentDiscount'];
            $direct_discount = $row['itemWiseDiscount'];
            $sell_discount = $row['sellDiscount'];
            $sold_amount = $row['soldAmount'];
            $product_id = $row['PDID'];
            $batch_id = "0";
    
            //current date
            date_default_timezone_set("Asia/Colombo");
            $warranty_start = date("Y-m-d");
            $warranty_end = date("Y-m-d");
            $reference_no = "";
    
            $invObj = new Invoice();
            $invObj->setInvoiceDetail($sell_qty, $unit_sell_price, $sell_amount, $percent_discount, $direct_discount, $sell_discount, $sold_amount, $warranty_start, $warranty_end, $reference_no, $invoice_header_id, $product_id, $batch_id);
    
        }//foreach
    }//has records
}//add service

function UpdateInventory($shop_id)
{
    $sale_header_id = $_POST['hide_sales_header'];

    $sql = "SELECT * FROM selldetail
    INNER JOIN pricehistory ON pricehistory.PHID = selldetail.pricehistory_id
    INNER JOIN products ON products.PDID = pricehistory.ProductID
    INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
    WHERE sellheader_id = ".$sale_header_id.";";

    $dbObj = new DBTransactions();
    $saleData = $dbObj->getData($sql);

    foreach($saleData as $row)
    {
        $inventory_id = $row['INID'];
        $current_qty = floatval($row['CurrentQty']);

        $conversion_rate = floatval($row['UnitConversion']) == 0 ? 1: floatval($row['UnitConversion']);
        $sell_qty = floatval($row['sellQty']);

        //getting qty with respect to sell unit (divide by unit convertion rate)
        $new_sell_qty = $sell_qty / $conversion_rate;

        $new_current_qty = $current_qty - $new_sell_qty;

        $bill_qty = floatval($row['BillQty']);
        $new_bill_qty = $bill_qty + $new_sell_qty;

        // echo "inventory - " . $inventory_id . "<br>";
        // echo "new qty - " . $new_current_qty . "<br>";
        // echo "bill qty - " . $new_bill_qty . "<br>";

        //calling inventory class
        $ivtObj = new Inventory();
        $ivtObj->editInvCurrentQty($new_current_qty, $inventory_id);
        $ivtObj->editInvBillQty($new_bill_qty, $inventory_id);
    }//foreach

}//update inventory

function DeleteSaleDetail($sale_header_id)
{
    $sql = "SELECT * FROM selldetail WHERE sellheader_id = ".$sale_header_id.";";
    $dbObj = new DBTransactions();
    $saleData = $dbObj->getData($sql);

    foreach($saleData as $row)
    {
        $sale_detail_id = $row['SDID'];

        $invObj = new Invoice();
        $invObj->deleteSaleDetail($sale_detail_id);
    }//foreach
}//delete sales detail

function DeleteSaleHeader($sale_header_id)
{
    $invObj = new Invoice();
    $invObj->deleteSaleHeader($sale_header_id);
}//delete sale header

function DeleteMultipay($sale_header_id)
{
    //get sale header id and multipay
    $sql = "SELECT * FROM multipay 
    INNER JOIN paymethod ON paymethod.PMID = multipay.paymethod_id
    WHERE sellheader_id = ".$sale_header_id.";";

    $dbObj = new DBTransactions();
    $multiData = $dbObj->getData($sql);

    $invObj = new Invoice();

    foreach($multiData as $row)
    {
        $multipay_id = $row['MPID'];

        $invObj->deleteMultipay($multipay_id);
    }//foreach
}//delete multi pay

function CreateTransaction($sale_header_id, $invoice_header_id)
{
    //get sale header id and multipay
    $sql = "SELECT * FROM multipay 
    INNER JOIN paymethod ON paymethod.PMID = multipay.paymethod_id
    WHERE sellheader_id = ".$sale_header_id.";";

    $dbObj = new DBTransactions();
    $multiData = $dbObj->getData($sql);

    $tranObj = new Transaction();

    foreach($multiData as $row)
    {
        $multipay_id = $row['MPID'];
        $paid_amount = $row['paidAmount'];
        $transaction_stat = 1;
        $pay_method = $row['paymethod_id'];
        $return_header_id = floatval($row['returnheader_id']);

        $tranObj->setTransaction($paid_amount, $transaction_stat, $pay_method, $invoice_header_id, $return_header_id);
    }//foreach

}//create transaction

//================== Sales Header =====================//
function createNewSaleHeader($user_id, $shop_id)
{
    $invoiceObj = new Invoice();
    $bill_count = floatval($invoiceObj->getBillCount($shop_id));
    $bill_count += 1;

    $comObj = new Common();
    $bill_no = $comObj->createCount("INV", $bill_count);

    //SHID, BillNo, ItemCount, grossAmount, SellStat, user_id, shop_id, cashcounter_id
    $item_count = 0;
    $gross_amount = 0;
    $sell_stat = 1;

    $sql = "SELECT * FROM cashcounter WHERE user_USID=".$user_id." AND shop_SHID=".$shop_id." AND CounterStat = 1;";

    $dbObj = new DBTransactions();
    $dbData = $dbObj->getData($sql);

    $counter_id = $dbData[0]['CCID'];

    $invoiceObj->setSaleHeader($bill_no, $item_count, $gross_amount, $sell_stat, $user_id, $shop_id, $counter_id);

    //get next sell header id
    $sql_1 = "SELECT MAX(SHID) AS maxSaleHeader FROM sellheader;";
    $sellData = $dbObj->getData($sql_1);
    //next salesheader id
    $next_sale_header_id = floatval($sellData[0]['maxSaleHeader']);

    //get doc count
    $sql_2 = "SELECT COUNT(DNID) AS docCount FROM docno WHERE shop_id = ".$shop_id.";";
    $docData = $dbObj->getData($sql_2);
    $tmp_count = floatval($docData[0]['docCount']) + 1;

    $tmp_bill_no = $comObj->createCount("tmp", $tmp_count);

    $doc_stat = 0;

    $invoiceObj->setDoc($bill_no, $tmp_bill_no, $next_sale_header_id, $doc_stat, $shop_id);

}//create new sale header