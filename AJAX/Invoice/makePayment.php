<?php
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/invoice_class.php";
include "../../Model/common_class.php";
include "../../Model/inventory_class.php";
include "../../Model/transaction_class.php";
include "../../Model/sales_return_class.php";
include "../../Model/shop_class.php";

$user_id = $_SESSION['user_id'];
$shop_id = $_SESSION['shop_id'];

$sell_header_id = $_GET['sell_header_id'];
$cust_pay = floatval($_GET['cust_pay']);
$tmp_bill_no = $_GET['tmp_bill_no'];
$paymethod_id = $_GET['paymethod_id'];
$customer_id = $_GET['customer_id'];
$salesman_id = $_GET['salesman_id'];
$counter_id = $_GET['counter_id'];

$dbObj = new DBTransactions();
$invObj = new Invoice();

$invoice_stat = "null";
$invoice_header_id = 0;

//check if sell header is already processed (prevent double-submission)
$sql = "SELECT SellStat FROM sellheader WHERE SHID = ".$sell_header_id.";";
$statData = $dbObj->getData($sql);
if(!empty($statData) && intval($statData[0]['SellStat']) === 1)
{
    $data = array("inv_stat"=>5, "invoice_id"=>0);
    echo json_encode($data);
    exit;
}//already processed

//get invoice total
$sql = "SELECT SUM(sellDiscount) AS sell_discount, sum(soldAmount) as sell_amount FROM `selldetail` WHERE sellheader_id = ".$sell_header_id.";";
$sellData = $dbObj->getData($sql);

$line_discount = floatval($sellData[0]['sell_discount']);
$sell_amount = floatval($sellData[0]['sell_amount']);

//get invoice discount
$sql = "SELECT * FROM `sellheader` WHERE SHID = ".$sell_header_id.";";
$sellData = $dbObj->getData($sql);

$invoice_discount = empty($sellData[0]['DiscountAmount']) ? 0 : $sellData[0]['DiscountAmount'];

$net_total = $sell_amount - $invoice_discount;

$balance = $cust_pay - $net_total;

//effective date
date_default_timezone_set("Asia/Colombo");
$effective_date = date("Y-m-d");

//check user invoice limit
$sql = "SELECT sum(NetAmount) as user_sale FROM `invoiceheader` WHERE user_USID = ".$user_id." AND EffectiveDate = '".$effective_date."';";
$saleData = $dbObj->getData($sql);
$user_sale = floatval($saleData[0]['user_sale']) + $net_total;

//user invoice limit
$sql = "SELECT * FROM `user` WHERE USID = ".$user_id.";";
$userData = $dbObj->getData($sql);
$user_sale_limit = floatval($userData[0]['paylimit']);

if($user_sale_limit==0 OR $user_sale_limit > $user_sale)
{
    //get multi pay
    $sql = "SELECT sum(paidAmount) as paid_amount FROM `multipay` WHERE sellheader_id = ".$sell_header_id.";";
    $payData = $dbObj->getData($sql);
    $paid_amount = floatval($payData[0]['paid_amount']);

    if(qtyCheck($sell_header_id, $shop_id))
    {
        if($paid_amount < $net_total)
        {
            if(expireCheck($sell_header_id, $shop_id))
            {
                //check customer payment
                if($cust_pay >= $net_total)
                {
                    $total_payment = $paid_amount + $cust_pay;
                    $balance_payment = $total_payment - $net_total;
        
                    if($balance_payment >= 0)
                    {
                        //create multipay
                        $transfer_amount = $net_total - $paid_amount;
        
                        //set multi pay
                        createMultipay($transfer_amount, $paymethod_id, $sell_header_id);
        
                        //create invoice
                        $invoice_header_id = createInvoice($sell_header_id, $cust_pay, $balance_payment, $user_id, $customer_id, $salesman_id, $shop_id, $counter_id);
        
                        //update inventory
                        UpdateInventory($sell_header_id);
        
                        UpdateSaleHeaderStat($sell_header_id);
        
                        // echo "check multi pay, Invoice, inventory ->>> sell id - " . $sell_header_id;
                        //issue bill
                        $invoice_stat = 2;
                    }
                }//get payment
                else
                {
                    $invoice_stat = 1;
                }//credit payment
            }//expire check
            else
            {
                $invoice_stat = 0;
            }
        }//not paid enough
        else
        {
            //already fully paid via multipay - check if invoice was already created
            $sql_chk = "SELECT IHID FROM invoiceheader WHERE InvStat = 1 AND CashCounter_CCID = ".$counter_id." ORDER BY IHID DESC LIMIT 1;";
            $invoice_stat = 5;
        }//already paid
    }//qty check
}//user invoice limit
else
{
    $invoice_stat = 3;
}

$data = array("inv_stat"=>$invoice_stat, "invoice_id"=>$invoice_header_id);

echo json_encode($data);

//===================== Function ===================//

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

function createMultipay($paid_amount, $paymethod_id, $sell_header_id)
{
    $invObj = new Invoice();
    $pay_stat = 0;
    $return_header_id = 0;

    $invObj->setMultipay($paid_amount, $pay_stat, $paymethod_id, $sell_header_id, $return_header_id);
}//create multi pay

function expireCheck($sell_header_id, $shop_id)
{   
    $expire_check = false;
    $shopObj = new Shop();
    $dbObj = new DBTransactions();

    if($shopObj->hasExpiry($shop_id))
    {
        $sql = "SELECT * FROM selldetail 
        INNER JOIN products ON products.PDID = selldetail.product_id
        INNER JOIN pricehistory ON pricehistory.PHID = selldetail.pricehistory_id
        WHERE sellheader_id = ".$sell_header_id.";";
    
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

function createInvoice($sell_header_id, $cust_pay, $balance_payment, $user_id, $customer_id, $salesman_id, $shop_id, $counter_id)
{
    $dbObj = new DBTransactions();
    $comObj = new Common();
    $invObj = new Invoice(); 

    //InvoiceNo, EffectiveDate, BillNo, InvStartTime, InvEndTime, InvItemCount, GrossAmount, lineDiscount, PercentDiscount, FixedDiscount, DiscountAmount, NetAmount, CustPayment, CustBalance, InvStat, user_USID, customers_CTID, Salesmans_SLID, shop_SHID, CashCounter_CCID
    
    //effective date
    date_default_timezone_set("Asia/Colombo");
    $effective_date = date("Y-m-d");

    $start_time = date("Y-m-d H:m:s");
    $end_time = date("Y-m-d H:m:s");

    $sql = "SELECT COUNT(IHID) AS HeaderCount FROM invoiceheader WHERE InvStat = 1 AND shop_SHID = ".$shop_id.";";
    $headData = $dbObj->getData($sql);
    $header_count = floatval($headData[0]['HeaderCount']) + 1;

    //get header text
    $sql_1 = "SELECT * FROM salesettings WHERE shop_id = ".$shop_id." AND settingStat=1;";
    $settingData = $dbObj->getData($sql_1);
    $invoice_header_text = $settingData[0]['billNoHeader'];

    $invoice_no = $comObj->createCount("INV", $header_count);

    $bill_no = $comObj->createCount($invoice_header_text, $header_count);

    //get invoice total
    $sql = "SELECT count(SDID) as item_count, SUM(sellDiscount) AS sell_discount, sum(soldAmount) as sell_amount FROM `selldetail` WHERE sellheader_id = ".$sell_header_id.";";
    $sellData = $dbObj->getData($sql);

    $item_count = $sellData[0]['item_count'];
    $gross_amount = $sellData[0]['sell_amount'];
    $line_discount = $sellData[0]['sell_discount'];

    //get invoice header
    $sql = "SELECT * FROM `sellheader` WHERE SHID = ".$sell_header_id.";";
    $sellData = $dbObj->getData($sql);

    $percent_discount = empty($sellData[0]['PercentDiscount']) ? 0 : $sellData[0]['PercentDiscount'];
    $fixed_discount = empty($sellData[0]['FixedDiscount']) ? 0 : $sellData[0]['FixedDiscount'];
    $invoice_discount = empty($sellData[0]['DiscountAmount']) ? 0 : $sellData[0]['DiscountAmount'];

    $net_total = $gross_amount - $invoice_discount;

    $inv_stat = 1;

    //get next invoice id
    $sql = "SELECT max(IHID) as max_id FROM invoiceheader;";
    $invData = $dbObj->getData($sql);
    $next_header_id = empty($invData[0]['max_id']) ? 0 : $invData[0]['max_id'];
    $next_header_id = floatval($next_header_id) + 1;
 
    $invObj->setInvoiceHeader($invoice_no, $effective_date, $bill_no, $start_time, $end_time, $item_count, $gross_amount, $line_discount, $percent_discount, $fixed_discount, $invoice_discount, $net_total, $cust_pay, $balance_payment, $inv_stat, $user_id, $customer_id, $salesman_id, $shop_id, $counter_id);

    //insert invoice details
    CreateInvoiceDetail($next_header_id, $sell_header_id);

    //create transaction
    CreateTransaction($sell_header_id, $next_header_id);

    //Add service
    AddService($next_header_id, $sell_header_id);

    return $next_header_id;
}//create invoice

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