<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/invoice_class.php";
include "../../Model/shop_class.php";
include "../../Model/inventory_class.php";
include "../../Model/transaction_class.php";
include "../../Model/common_class.php";
include "../../Model/sales_return_class.php";

$shop_id = $_SESSION['shop_id'];
$user_id = $_SESSION['user_id'];

$tmp_bill_no = $_GET['tmp_bill_no'];
$customer_id = $_GET['customer_id'];
$salesman_id = $_GET['salesman_id'];
$cust_payment = $_GET['cust_payment'];

$invoice_header_id = 0;
$invoice_stat = 0;

$shopObj = new Shop();
$dbObj = new DBTransactions();

// echo $tmp_bill_no."<br>";
// echo $shop_id."<br>";
// echo $user_id."<br>";

$default_customer_id = 1;
if($customer_id == 1)
{
    $sql = "SELECT MIN(CTID) AS DefaultCustomer FROM customers WHERE shop_SHID = ".$shop_id.";";
    $custData = $dbObj->getData($sql);

    $default_customer_id = $custData[0]['DefaultCustomer'];
}//default customer
else
{
    $default_customer_id = $customer_id;
}

$sql = "SELECT * FROM sellheader WHERE tmp_bill_no='".$tmp_bill_no."' AND shop_id=".$shop_id." AND user_id=".$user_id.";";
$headData = $dbObj->getData($sql);
$sale_header_id = $headData[0]['SHID'];

//check if sell header is already processed (prevent double-submission)
if(!empty($headData) && intval($headData[0]['SellStat']) === 1)
{
    $data = array("stat"=>5, "header_id"=>0);
    echo json_encode($data);
    exit;
}//already processed

//qty check
if(qtyCheck($sale_header_id, $shop_id))
{
    //expire date check
    if(expireCheck($sale_header_id, $shop_id))
    {
        CreateInvoiceHeader($sale_header_id, $shop_id, $user_id, $default_customer_id, $salesman_id);

        //get active header
        $sql = "SELECT * FROM invoiceheader WHERE user_USID = ".$user_id." AND shop_SHID=".$shop_id." AND InvStat=0;";
        $dbObj = new DBTransactions();
        $headerData = $dbObj->getData($sql);

        $invoice_header_id = $headerData[0]['IHID'];

        //insert into invoice details
        CreateInvoiceDetail($invoice_header_id, $sale_header_id);

        //Add service
        AddService($invoice_header_id, $sale_header_id);

        //updated invoice header
        UpdateInvoiceHeader($sale_header_id, $cust_payment,  $invoice_header_id);

        //update Inventory
        UpdateInventory($sale_header_id);

        //create transaction
        CreateTransaction($sale_header_id, $invoice_header_id);

        //change sell header stat
        UpdateSaleHeaderStat($sale_header_id);

        // //delete multipay
        // DeleteMultipay($sale_header_id);

        // //delete sales detail
        // DeleteSaleDetail($sale_header_id);

        // //delete sale detail
        // DeleteSaleHeader($sale_header_id);

        // sleep 0.5 seconds
        // usleep(250000);

        //update invoice header stat
        $invObj = new Invoice();
        $invObj->editInvoiceHeaderStat(1, $invoice_header_id);

        $invoice_stat = 2;
    }//not expired
    else
    {
        //items expired
        $invoice_stat = 1;
    }//expired
}//qty check passed
else
{
    //out of stock
    $invoice_stat = 0;
}//qty check failed

$data = array("stat"=>$invoice_stat, "header_id"=>$invoice_header_id);

//send using json array
echo json_encode($data);

//============================= Functions ==============================//
function qtyCheck($sale_header_id, $shop_id)
{
    $qty_check = false;
    $shopObj = new Shop();
    $dbObj = new DBTransactions();

    if($shopObj->hasInventory($shop_id))
    {
        if($shopObj->hasMinus($shop_id))
        {
            $qty_check = true;
        }//has minus
        else
        {
            $sql = "SELECT sellQty, UnitConversion, CurrentQty FROM selldetail 
            INNER JOIN products ON products.PDID = selldetail.product_id 
            INNER JOIN pricehistory ON pricehistory.PHID = selldetail.pricehistory_id 
            INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID 
            WHERE sellheader_id = ". $sale_header_id ." AND pricehistory_id > 0;"; 

            $qtyData = $dbObj->getData($sql);

            if(!empty($qtyData))
            {
                foreach($qtyData as $row)
                {
                    $sell_qty = floatval($row['sellQty']);
                    $conversion_rate = floatval($row['UnitConversion'])==0 ? 1 : floatval($row['UnitConversion']);
                    $current_qty = floatval($row['CurrentQty']);
                    $converted_qty = $sell_qty /  $conversion_rate;
    
                    if($current_qty >= $converted_qty)
                    {
                        $qty_check = true;
                    }//has stock
                    else
                    {
                        $qty_check = false;
                        break;
                    }//out of stock
                }//foreach
            }//has rows
            else
            {
                $sql = "SELECT COUNT(SDID) AS rowCount FROM selldetail WHERE sellheader_id = ".$sale_header_id." AND pricehistory_id = 0;;";
                $countData = $dbObj->getData($sql);
                if(!empty($countData))
                {
                    $qty_check = true;
                }//has rows
                else
                {
                    $qty_check = false;
                }//no rows
            }//no rows
        }//check qty
    }//has inventory
    else
    {
        $qty_check = true;
    }//no inventory

    return $qty_check;
}//qty check

function expireCheck($sale_header_id, $shop_id)
{   
    $expire_check = false;
    $shopObj = new Shop();
    $dbObj = new DBTransactions();

    if($shopObj->hasExpiry($shop_id))
    {
        $sql = "SELECT * FROM selldetail 
        INNER JOIN products ON products.PDID = selldetail.product_id
        INNER JOIN pricehistory ON pricehistory.PHID = selldetail.pricehistory_id
        WHERE sellheader_id = ".$sale_header_id.";";
    
        $expireData = $dbObj->getData($sql);
        foreach($expireData as $row)
        {   
            //effective date
            date_default_timezone_set("Asia/Colombo");
            $current_date = date("Y-m-d");
            
            $expire_date = $row['ExpDate'];

            $current_date_stamp = strtotime($current_date);
            $expire_date_stamp = strtotime($expire_date);

            if($current_date_stamp < $expire_date_stamp)
            {
                $expire_check = true;
            }//not expired
            else
            {
                $expire_check = false;
                break;
            }//expired
        }//foreach
    }//shop has expire date
    else
    {
        $expire_check = true;
    }//no expire date

    return $expire_check;
}//expire check

function CreateInvoiceHeader($sale_header_id, $shop_id, $user_id, $default_customer_id, $salesman_id)
{
    //IHID, InvoiceNo, EffectiveDate, BillNo, InvStartTime, InvEndTime, InvItemCount, GrossAmount, PercentDiscount, FixedDiscount, DiscountAmount, NetAmount, CustPayment, CustBalance, InvStat, user_USID, customers_CTID, Salesmans_SLID, shop_SHID, CashCounter_CCID
    $dbObj = new DBTransactions();
    $comObj = new Common();

    $sql = "SELECT COUNT(IHID) AS HeaderCount FROM invoiceheader WHERE InvStat = 1 AND shop_SHID = ".$shop_id.";";
    $headData = $dbObj->getData($sql);
    $header_count = floatval($headData[0]['HeaderCount']) + 1;

    //get header text
    $sql_1 = "SELECT * FROM salesettings WHERE shop_id = ".$shop_id." AND settingStat=1;";
    $settingData = $dbObj->getData($sql_1);
    $invoice_header_text = $settingData[0]['billNoHeader'];

    $invoice_no = $comObj->createCount("INV", $header_count);

    $bill_no = $comObj->createCount($invoice_header_text, $header_count);

    //effective date
    date_default_timezone_set("Asia/Colombo");
    $effective_date = date("Y-m-d");

    $start_time = date("Y-m-d H:m:s");
    $end_time = date("Y-m-d H:m:s");

    $item_count = 0;
    $gross_amount = 0;
    $line_discount = 0;
    $percent_discount = 0;
    $fixed_discount = 0;   
    $discount_amount = 0;
    $net_amount = 0;
    $cust_payment = 0;
    $cust_balance = 0;
    $inv_stat = 0;
    
    //get cash counter id
    $sql = "SELECT * FROM cashcounter WHERE user_USID = ".$user_id." AND shop_SHID=".$shop_id." AND CounterStat = 1;";
    $counterData = $dbObj->getData($sql);
    $counter_id = $counterData[0]['CCID'];

    $invObj = new Invoice();
    $invObj->setInvoiceHeader($invoice_no, $effective_date, $bill_no, $start_time, $end_time, $item_count, $gross_amount, $line_discount, $percent_discount, $fixed_discount, $discount_amount, $net_amount, $cust_payment, $cust_balance, $inv_stat, $user_id,$default_customer_id, $salesman_id, $shop_id, $counter_id);
}//invoice header

function UpdateInvoiceHeader($sale_header_id, $cust_payment,  $invoice_header_id)
{
    //effective date
    date_default_timezone_set("Asia/Colombo");
    $issued_datetime = date("Y-m-d H:i:s");
    $effective_date = date("Y-m-d");

    $dbObj = new DBTransactions();

    $sql = "SELECT * FROM selldetail WHERE sellheader_id = ".$sale_header_id.";";

    $row_count = 0;
    $sub_total = 0;
    $line_discount = 0;

    $saleData = $dbObj->getData($sql);
    foreach($saleData as $row)
    {
        $row_count += 1;
        $line_discount += floatval($row['sellDiscount']);
        $sub_total += floatval($row['soldAmount']);
    }//foreach

    $sql_1 = "SELECT * FROM sellheader WHERE SHID = ".$sale_header_id.";";
    $saleData_1 =  $dbObj->getData($sql_1);

    $percent_discount = empty($saleData_1[0]['PercentDiscount']) ? 0 : $saleData_1[0]['PercentDiscount'];
    $fixed_discount = empty($saleData_1[0]['FixedDiscount']) ? 0 : $saleData_1[0]['FixedDiscount'];
    $discount_amount = empty($saleData_1[0]['DiscountAmount']) ? 0 : floatval($saleData_1[0]['DiscountAmount']); 

    $net_total = $sub_total - $discount_amount;

    $cust_balance = $cust_payment - $net_total;

    $invObj = new Invoice();
    $invObj->editInvoiceHeaderPrice($effective_date, $issued_datetime, $row_count, $sub_total, $line_discount, $percent_discount, $fixed_discount, $discount_amount, $net_total, $cust_payment, $cust_balance, $invoice_header_id);

}//update invoice header

function CreateInvoiceDetail($invoice_header_id, $sale_header_id)
{
    $dbObj = new DBTransactions();

    $sql = "SELECT * FROM selldetail
    INNER JOIN pricehistory ON pricehistory.PHID = selldetail.pricehistory_id
    INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
    INNER JOIN products ON products.PDID = inventory.products_PDID
    WHERE sellheader_id = ".$sale_header_id." AND pricehistory_id > 0;";

    $saleData = $dbObj->getData($sql);
    foreach($saleData as $row)
    {
        $sell_qty = floatval($row['sellQty']);
        $batch_id = $row['BatchID'];
        $sell_price = floatval($row['SellingPrice']);
        $percent_discount = $row['itemPercentDiscount'];
        $direct_discount = $row['itemWiseDiscount'];

        $conversion_rate = empty($row['UnitConversion']) ? 1 : floatval($row['UnitConversion']);

        //get unit sell price
        $unit_sell_price = $sell_price / $conversion_rate;

        $sell_qty = floatval($row['sellQty']);
        $sell_amount = floatval($row['sellAmount']);
        $sell_discount = floatval($row['sellDiscount']);
        $sold_amount = floatval($row['soldAmount']);

        $warranty_start = date("Y-m-d");
        $warranty_end = date("Y-m-d");
        $reference_no = "";

        $product_id = $row['ProductID'];

        $invObj = new Invoice();
        $invObj->setInvoiceDetail($sell_qty, $unit_sell_price, $sell_amount, $percent_discount, $direct_discount, $sell_discount, $sold_amount, $warranty_start, $warranty_end, $reference_no, $invoice_header_id, $product_id, $batch_id);
    }//foreach
}//create invoice detail

function AddService($invoice_header_id, $sale_header_id)
{
    $sql = "SELECT * FROM selldetail
    INNER JOIN products ON products.PDID = selldetail.product_id
    WHERE pricehistory_id=0 AND sellheader_id = ".$sale_header_id.";";

    $dbObj = new DBTransactions();
    $sellData = $dbObj->getData($sql);

    if(!empty($sellData))
    {
        foreach($sellData as $row)
        {
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
}//add service to invoice detail 

function UpdateInventory($sale_header_id)
{
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

        $conversion_rate = floatval($row['UnitConversion']) == 0 ? 1: floatval($row['UnitConversion']);
        $sell_qty = floatval($row['sellQty']);

        //atomic deduction - prevents race condition over-deduction
        $new_sell_qty = $sell_qty / $conversion_rate;

        $ivtObj = new Inventory();
        $ivtObj->deductInvQty($new_sell_qty, $inventory_id);
    }//foreach
}//update inventory

function CreateTransaction($sale_header_id, $invoice_header_id)
{
    //get sale header id and multipay
    $sql = "SELECT * FROM multipay 
    INNER JOIN paymethod ON paymethod.PMID = multipay.paymethod_id
    WHERE sellheader_id = ".$sale_header_id.";";

    $dbObj = new DBTransactions();
    $multiData = $dbObj->getData($sql);

    $tranObj = new Transaction();
    $returnObj = new Sales_return_class();

    foreach($multiData as $row)
    {
        $multipay_id = $row['MPID'];
        $paid_amount = $row['paidAmount'];
        $transaction_stat = 1;
        $pay_method = $row['paymethod_id'];
        $return_header_id = floatval($row['returnheader_id']);

        if($return_header_id > 0)
        {
            $returnObj->editReturnHeaderStat(1, $return_header_id);
        }//change return header stat

        $tranObj->setTransaction($paid_amount, $transaction_stat, $pay_method, $invoice_header_id, $return_header_id);
    }//foreach
}//create transaction

function UpdateSaleHeaderStat($sale_header_id)
{
    $dbObj = new DBTransactions();
    $sql = "SELECT * FROM sellheader WHERE SHID = ".$sale_header_id.";";
    $headerData = $dbObj->getData($sql);
    $sell_discount = floatval($headerData[0]['DiscountAmount']);

    $sql = "SELECT * FROM selldetail WHERE sellheader_id = ".$sale_header_id.";";
  
    $dbData = $dbObj->getData($sql);

    $row_count = 0;
    $gross_amount = 0;
    $line_discount = 0;
    $net_amount = 0;
    $sell_stat = 1;
    foreach($dbData as $row)
    {
        $row_count += 1;
        $gross_amount += floatval($row['sellAmount']);
        $line_discount += floatval($row['sellDiscount']);
    }//foreach

    $net_amount = $gross_amount - $sell_discount - $line_discount;

    $saleObj = new Invoice();
    $saleObj->editSaleHeaderStat($row_count, $gross_amount, $line_discount, $net_amount, $sale_header_id);

}//update sale header stat

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