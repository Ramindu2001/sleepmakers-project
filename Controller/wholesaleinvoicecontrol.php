<?php 


include "../Includes/includes.php";
$shop_id = $_SESSION['shop_id'];
$wholesale=new wholesale_invoice();
$dbObj = new DBTransactions();
$comObj = new Common();
$shop= new Shop();
$shop_SHID=$_SESSION['shop_id'];

$SMS = new SMS();
$cust = new Customer();
$user_USID=$_SESSION["user_id"];

$hasbatchNo=$shop->hasbatchNo($shop_SHID);

if(isset($_POST["wholesale"]) || isset($_POST["wholesales"]))
{
     // Check if the token exists and is valid

    if (isset($_SESSION['form_token']) && $_SESSION['form_token'] === $_POST['form_token']) {
        $_SESSION["Invoice_Error"]=1; // Duplicate Entry // Duplicate Entry
        header("Location: ../Public/wholesale-invoice.php");
        exit(); 
    }
    
    if (isset($_POST['formValid']) && $_POST['formValid'] == 'true') {
        // Form is valid, proceed with processing
        echo "Form is valid!";
    } else {
        // Form validation failed
        echo "Form validation failed. Please fix the errors.";
    }
    
    // if($_POST['formValid'] == 'false')
    // {
    //     $_SESSION["Invoice_Error"]=1;
    //     $_SESSION["Invoice_Error"]["message"]="Invalid Form Submission!";      
    //     exit(); 
    // }
    
    for ($i=0; $i <count($_POST["batch_id"]) ; $i++) 
    { 
        $item_id=$_POST["item_id"][$i];
        $batch_id=$_POST["batch"][$i];
        $batch=$_POST["batch_id"][$i];
        $qty=$_POST["qty"][$i];
        $rate=$_POST["rate"][$i];

        if(!empty($item_id) && !empty($batch_id) && !empty($batch) && !empty($qty) && !empty($rate) && $qty!=0 && $rate!=0 && $qty!="0.00" && $rate!="0.00")
        {

        }
        else
        {
            $_SESSION["Invoice_Error"]=2;// qty cannot be 0
            $_SESSION["Invoice_Error"]["message"]="Invalid Quantity";// qty cannot be 0
            $_SESSION["Invoice_Error"]["message"]["message"]="Quantity cannot be 0 or nun-numeric";// qty cannot be 0
            header("Location: ../Public/wholesale-invoice.php");
            exit(); 
        }
    }
    
    // Generate a new token and store it in the session
    $_SESSION['form_token'] = $_POST['form_token'];

    $UserLimit = $_POST["EffectiveDate"];
    $EffectiveDate=$_POST["EffectiveDate"];
    $doc=$wholesale->select_docno($shop_id);
    if(count($doc)==0)
    {
        $inser_doc=$wholesale->insert_doc_no($shop_SHID);
        $doc=$wholesale->select_docno($shop_SHID);
    }

    $ws_no=$doc[0]["ws_no"] + 1;
    $update_doc=$wholesale->doc_update($ws_no,$shop_SHID);

    $ws_no=$wholesale->getSequence($ws_no);
    $year=date("y");
    $salesetings=$wholesale->getSaleSettings($shop_id);
    if(count($salesetings)>0)
    {
        if(isset($salesetings[0]["WbillNoHeader"]))
        {
            $ws_no=$salesetings[0]["WbillNoHeader"]."-".$ws_no;
        }
        else
        {
            $ws_no="INV-".$ws_no;
        }
    }
    else
    {
        $ws_no="INV-".$ws_no;
    }
    if(isset($_POST["claimBill"]))
    {
        $claim=1;
    }
    else
    {
        $claim=0;
    }
    $BillNo = $ws_no;
    $InvStartTime=date("Y-m-d H:i:s");
    $InvEndTime=date("Y-m-d H:i:s");
    $InvItemCount=count($_POST["item_id"]);
    $GrossAmount=(int)$_POST["grossAmount"];
    $lineDiscount=$_POST["totalDiscountLine"];
    $saleDiscountType=$_POST["SaleDiscountType"];
    $FixedDiscount=$_POST["saleDiscount"];
    $DiscountAmount=$_POST["totalDiscount"];
    $deliveryCharge=$_POST["deliverycharges"];
    $otheCharge=$_POST["otherCharges"];
    $NetAmount=$_POST["netamount"];
    $customers_CTID=$_POST["customer_id"];
    
    $CustPayment=0;
    for ($i=0; $i < count($_POST["paid"]) ; $i++) 
    { 
        $CustPayment=$CustPayment+(int)$_POST["paid"][$i];

    }
    $balance=$CustPayment-$NetAmount;
    if($balance > 0)
    {
        $CustBalance=$balance;
    }
    else
    {
        $CustBalance=0;        
    }
    if($claim==1)
    {
        $InvStat=5;
    }
    else if(isset($_POST["claimBillI"]))
    {
        $InvStat=6;
    }
    else
    {
      $InvStat=1;  
    }    
    $Salesmans_SLID = isset($_POST['salesman_id']) ? $_POST['salesman_id']: '1';
    if($shop->hascounter($shop_SHID)==1)
    {
        $sql = "SELECT * FROM cashcounter WHERE user_USID = ".$user_USID." AND shop_SHID=".$shop_SHID." AND CounterStat = 1;";
        $counterData = $dbObj->getData($sql);
        $CashCounter_CCID = $counterData[0]['CCID'];
    }
    else
    {
        $CashCounter_CCID =0;
    }
    
    $remarks=$_POST["details"];
    $excessamount=0;
    $is_delivery=0;
    $deliveryPartner="";
    $returnAmount=0;

    if(isset($_POST["excessamount"]))
    {
        $excessamount=$_POST["excessamount"];
    }
    if(isset($_POST["deliveryNote"]))
    {
        $is_delivery=$_POST["deliveryNote"];
    }
    else
    {
        $is_delivery=0;
    }
    if(isset($_POST["deliveryPartner"]))
    {
        $deliveryPartner=$_POST["deliveryPartner"];
    }
    else
    {
        $deliveryPartner=" ";
    }
    if(isset($_POST["salesSource"]))
    {
        $sales_source=$_POST["salesSource"];
    }
    else
    {
        $sales_source=" ";
    }
    if(isset($_POST["returnamount"]))
    {
        $returnAmount=$_POST["returnamount"];
    }
    if(isset($_POST["return_id"]))
    {
        $return_header_id=$_POST["return_id"];
        $sql = "UPDATE `retrun_invoice_header` SET `return_header_stat`='1' WHERE RIHID= '$return_header_id';";
        $update = $dbObj->executeTransaction($sql);
    }
    else
    {
        $return_header_id=0;
    }
    
     //Sms gateway integration added by Imila Madushan 2024-11-02 ------------------//
    //get the SMS Details
    try {
        $sql = "SELECT is_enable, UserName, Password, Mask FROM sms_details";
        $smsData = $dbObj->getData($sql);
    
        if (!empty($smsData)) {
            $isSMSEnable = $smsData[0]['is_enable'];
            $SMSUserName = $smsData[0]['UserName'];
            $SMSPassword = $smsData[0]['Password'];
            $SMSMask = $smsData[0]['Mask'];
    
            if ($isSMSEnable == 1) {
                $AccessToken = $SMS->login($SMSUserName, $SMSPassword);
    
                if (!$AccessToken) {
                    throw new Exception("Failed to get SMS access token.");
                }
    
                $MessageBody = 
                    "Dear Customer,\n" .
                    "Invoice #" . htmlspecialchars($BillNo) . "\n" .
                    "Your invoice is ready.\n" .
                    "Total Amount: Rs." . htmlspecialchars($NetAmount) . "\n" .
                    "Due Date: " . htmlspecialchars($EffectiveDate) . "\n" .
                    "Thank you for your purchase!";
    
                // Fetch customer phone number safely
                $query = "SELECT CustContact FROM customers WHERE CTID = '$customers_CTID'";
                $cusData = $dbObj->getData($query);
    
                if (empty($cusData)) {
                    throw new Exception("Customer data not found for CTID: $customers_CTID");
                }
    
                $phoneNumb = $cusData[0]['CustContact'];
    
                // Send SMS
                if (!$SMS->SendSMS($AccessToken, $phoneNumb, $MessageBody, $SMSMask)) {
                    throw new Exception("Failed to send SMS.");
                }
            }
        }
    } catch (Exception $e) {
        error_log("SMS Process Skipped: " . $e->getMessage());
    }
    //---------------SMS ends here---------------------------------------------------//    
    
    // echo "Bill No ".$BillNo."<br>";
    $insert=$wholesale->setInvoiceHeader( $EffectiveDate,$BillNo,$InvStartTime,$InvEndTime,$InvItemCount,$GrossAmount,$lineDiscount,$FixedDiscount,$DiscountAmount,$deliveryCharge,$otheCharge,$NetAmount,$CustPayment,$CustBalance,$InvStat,$user_USID,$customers_CTID,$Salesmans_SLID,$shop_SHID,$CashCounter_CCID,$remarks,$excessamount,$returnAmount,$return_header_id,$saleDiscountType,$is_delivery,$deliveryPartner,$sales_source);
    // echo $insert;
    $sql = "SELECT max(IHID) AS MAXIHID FROM invoiceheader;";
    $dbMax = $dbObj->getData($sql);
    $invoice_header_id = $dbMax[0]['MAXIHID'];
    // echo "\$invoice_header_id ".$invoice_header_id;
    $payment=$_POST["pay_id"][0];
    
    if(isset($_POST["excessamount"]))
    {
        if($_POST["excessamount"]!="" AND $_POST["excessamount"]!="0" AND $_POST["excessamount"]!=0 )
        {
            // use excess
            $cust=$wholesale->setCreditCust($EffectiveDate,$excessamount,$EffectiveDate,$invoice_header_id,$payment,$customers_CTID,$user_USID,6);
            
        }
    }
    for ($i=0; $i <count($_POST["batch_id"]) ; $i++) 
    { 
        $item_id=$_POST["item_id"][$i];
        $batch_id=$_POST["batch"][$i];
        $batch=$_POST["batch_id"][$i];
        $product=$wholesale->getProductInfor($item_id);
        $discountType=$_POST["discountType"][$i];
        $product=$wholesale->select_product($item_id);
        //// echo  $i."<br>";
        //// echo  $item_id."<br>";
        $qty=$_POST["qty"][$i];
        if(isset($_POST["prodDes"][$i]))
        {
            $prodDes=$_POST["prodDes"][$i];
        }
        else
        {
            $prodDes=" ";
        }
        $rate=$_POST["rate"][$i];
        $sellAmount=$qty*$rate;
        if($discountType==1)
        {
            $ldiscount=$sellAmount*$_POST["discount"][$i]/100;
        }
        else
        {
            $ldiscount=$_POST["discount"][$i];
        }
        $total=($_POST["rate"][$i]*$_POST["qty"][$i])-$ldiscount;
        //// echo  $total;
        $original_rate=$_POST["original_rate"][$i];
        $discount=$_POST["discount"][$i];
        $original_total=$_POST["original_total"][$i];
        $is_minus=$shop->hasMinus($shop_SHID);

        $inventory=$wholesale->select_inventory($item_id, $batch);
        $avlQty=$inventory[0]["avlQty"];
        $BillQty=$inventory[0]["BillQty"];
        $newavlQty=$avlQty - $qty;
        $newBillQty=$BillQty + $qty;
        $inventory_id=$inventory[0]["INID"];
        $Item_Name=$product[0]["ItemName"];
        if($product[0]["ItemType"]=="P")
        {
            if($newavlQty < 0)
            {
                if($is_minus==1)
                {
                    $inventoryDetails=$wholesale->setInvoiceDetails($qty,$rate,$sellAmount,$discount,$discount,$total,$invoice_header_id,$item_id,$batch_id,$discountType,$prodDes,$shop_id,$inventory_id,$Item_Name);
                    $newavlQty=$newavlQty / $product[0]["UnitConversion"];
                    $newBillQty=$newBillQty / $product[0]["UnitConversion"];
                    if($claim==0)
                    {
                        $stockUpdate=$wholesale->update_inventory($newavlQty,$newBillQty, $inventory_id);
                    }
                }
                else
                {
                    ?>
                    <script>
                        alert("Minus Quantity Not Allowed For <?=$product[0]["ItemName"]?>")
                    </script>
                    <?php
                }
            }
            else
            {
                $inventoryDetails=$wholesale->setInvoiceDetails($qty,$rate,$sellAmount,$discount,$discount,$total,$invoice_header_id,$item_id,$batch_id,$discountType,$prodDes,$shop_id,$inventory_id,$Item_Name);
                $newavlQty=$newavlQty * $product[0]["UnitConversion"];
                $newBillQty=$newBillQty * $product[0]["UnitConversion"];
                if($claim==0)
                {
                    $stockUpdate=$wholesale->update_inventory($newavlQty,$newBillQty, $inventory_id);
                }
                
            }
        }
        else
        {
            $inventoryDetails=$wholesale->setInvoiceDetails($qty,$rate,$sellAmount,$discount,$discount,$total,$invoice_header_id,$item_id,$batch_id,$discountType,$prodDes,$shop_id,$inventory_id,$Item_Name);
            if($claim==0)
            {
                $stockUpdate=$wholesale->update_inventory(0,$newBillQty, $inventory_id);
        
            }
        }
   

    }
    
    for ($i=0; $i <count($_POST["pay_id"]) ; $i++) 
    { 
        $TransferAmount=$_POST["paid"][$i];
        $paymethod_PMID=$_POST["pay_id"][$i];
        if(isset($_POST["return_id"]))
        {
            $return_header_id=$_POST["return_id"];
        }
        else
        {
            $return_header_id=0;
        }
        if($paymethod_PMID==9)
        {
            //IF CREADIT NOTE
            $wholesale->setCreditCustDebit($EffectiveDate,$TransferAmount,$EffectiveDate,$invoice_header_id,$paymethod_PMID,$customers_CTID,$user_USID,3);
        }
        
        $sql="INSERT INTO `transactions`(`TransferAmount`, `paymethod_PMID`, `InvoiceHeader_IHID`, `returnheader_id`) VALUES ('$TransferAmount','$paymethod_PMID','$invoice_header_id','')";
        $dbObj->executeTransaction($sql);     
        if($paymethod_PMID==5 && !empty($_POST["chqNo"]))
        {
            for ($j=0; $j <count($_POST["chqNo"]) ; $j++) 
            { 
                $sql = "SELECT count(*) AS ChqNo FROM custcheq ";
                $dbObj = new DBTransactions();
                $dbData = $dbObj->getData($sql);
                $count = $dbData[0]["ChqNo"]+1;
                $chq_no="CCHQ-".$wholesale->getSequence($count);
                $date=date("Y-m-d H:i:s");
                if(isset($_POST["chqNo"][$j]))
                {
                    $chequeNo=$_POST["chqNo"][$j];
                }
                else
                {
                    $chequeNo="N/A";
                }
                if(isset($_POST["chqdate"][$j]))
                {
                    $chqdate=$_POST["chqdate"][$j];
                }
                else
                {
                    $chqdate="N/A";
                }
                if(isset($_POST["chqbank"][$j]))
                {
                    $bank=$_POST["chqbank"][$j];
                }
                else
                {
                    $bank="N/A";
                }
                if(isset($_POST["chqAmount"][$j]))
                {
                    $chqAmount=$_POST["chqAmount"][$j];
                }
                else
                {
                    $chqAmount="N/A";
                }
                $date=date("Y-m-d H:i:s");
                $sql="INSERT INTO `custcheq`(`type`, `chq_stat`, `chq_no`, `cust_CTID`, `effectiveDate`, `user_USID`, `shop_SHID`, `createdDate`,`invoiceID`) VALUES ('2','1','$chq_no','$customers_CTID','$EffectiveDate','$user_USID','$shop_SHID','$date','$invoice_header_id')";
                $dbObj->executeTransaction($sql);
                $sql="SELECT MAX(CCQID) AS CCQID FROM custcheq";
                $CCQID=$dbObj->getData($sql);
                $CCQID=$CCQID[0]["CCQID"];
                $sql="INSERT INTO `custchqdetail`(`bank`, `chqAmount`, `chqNo`, `chqDate`, `invoiceID`, `CCQID`) VALUES ('$bank','$chqAmount','$chequeNo','$chqdate','$invoice_header_id','$CCQID')";
                $dbObj->executeTransaction($sql);
            }
        }
           
    }
    // // echo "NetAmount ".$NetAmount."<br>";
    // // echo "CustPayment ".$CustPayment."<br>";
    if($NetAmount>$CustPayment)
    {
        $amount=$NetAmount-$CustPayment;
        $payment=$_POST["pay_id"][0];
        // credit sale 
        $cust=$wholesale->setCreditCust($EffectiveDate,$amount,$EffectiveDate,$invoice_header_id,$payment,$customers_CTID,$user_USID,1);
    }
    if(isset($_POST["deliveryNote"]))
    {
        $_SESSION["deliveryNote"]=1;
    }
    else
    {
        
        $_SESSION["deliveryNote"]=0;
    }

    //check previous prescription added by Chinthana 2024-10-22
    if(isset($_POST['chk_prescription']))
    {
        $prescription_id = $_POST['cmb_prescription'];
        if($prescription_id != 0)
        {
            $wholesale->updatePrescription($invoice_header_id, $prescription_id);
        }//update
    }//add exiting prescription
    $remarks="Invoice Created";
    $invoice_remark=$wholesale->invoice_remark(remarks: $remarks,user: $user_USID,invoice_header_id: $invoice_header_id,from_invoice: 0);

    if(isset($_POST["remark"]))
    {
        $remarks=$_POST["remark"];
        $invoice_remark=$wholesale->invoice_remark(remarks: $remarks,user: $user_USID,invoice_header_id: $invoice_header_id,from_invoice: 1);
    }
    $sql = "SELECT * FROM shopreceipts WHERE ReceiptStat = 1 AND shop_id='$shop_SHID' AND RecieptType=2";
    $dbObj = new DBTransactions();
    $dbData = $dbObj->getData($sql);
    $invoice = empty($dbData) ? "wholesaleInvoice.php" : $dbData[0]['ReceiptPath'];
    if($claim==1)
    {
        $_SESSION["claim"]=0;
    }
    if(isset($_POST["wholesale"]))
    {
        header("Location:../Receipts/$invoice?invoice=$invoice_header_id");
    }
    elseif(isset($_POST["wholesales"]))
    {
        header("Location: ../Public/wholesale-invoice.php");
    }
    else
    {
        header("Location: ../Public/wholesale-invoice.php");
    }

}
elseif(isset($_POST["EditInvoice"]))
{
    $InvoiceID=$_POST["InvoiceID"];
    // echo $InvoiceID;
    $sql_2 = "SELECT id.*, p.ItemName AS ItemName, p.Barcode AS ItemCode, p.ProdDescription AS Description FROM `invoicedetails` id 
    INNER JOIN products p ON p.PDID=id.products_PDID
    WHERE id.InvoiceHeader_IHID='$InvoiceID';";
    $shopData = $dbObj->getData($sql_2);
    foreach ($shopData as $row) 
    {
        // print_r($row);
        $sql_3 = "SELECT * FROM `inventory` WHERE products_PDID='$row[products_PDID]' AND BatchID='$row[batch_no]'; ";
        $inventory = $dbObj->getData($sql_3);
        $INID = $inventory[0]["INID"];
        $newSoldQty=$inventory[0]["BillQty"]-$row["SellQty"];
        if($newSoldQty <= 0)
        {
            $sql4 = "UPDATE `inventory` SET `CurrentQty`=CurrentQty + '$row[SellQty]',`BillQty`=0 WHERE INID= '$INID';";
        }
        else
        {
        $sql4 = "UPDATE `inventory` SET `CurrentQty`=CurrentQty + '$row[SellQty]',`BillQty`= BillQty - '$row[SellQty]' WHERE INID= '$INID';";  
        }    
        $update = $dbObj->executeTransaction($sql4);     
        $sql5 = "DELETE FROM invoicedetails WHERE `invoicedetails`.`InvoiceHeader_IHID` ='$InvoiceID'";       
        $update = $dbObj->executeTransaction($sql5);          
    }
    // print_r($_POST);
    $sql_1 = "SELECT ih.*, ih.shop_SHID AS Invoiceshop_SHID,c.CTID AS CustomerID, c.CustName As CustomerName, ih.Salesmans_SLID AS Salesmans_SLID, c.CustContact AS CustContact, c.CustAddress AS CustAddress, u.UserName AS officer, sm.SalesmansName AS Salesmen FROM `invoiceheader` ih
    INNER JOIN customers c ON c.CTID=ih.customers_CTID
    INNER JOIN user u on u.USID=ih.user_USID
    INNER JOIN salesmans sm ON sm.SLID=ih.Salesmans_SLID
    WHERE ih.IHID= '$InvoiceID';";
   
   $invoiceData = $dbObj->getData($sql_1);
   $customers_CTID=$invoiceData[0]["CustomerID"];
   $EffectiveDate=$invoiceData[0]["EffectiveDate"];
    $return_header_id=$invoiceData[0]["ReturnHeader_RHID"];
    $sql = "SELECT count(*)  AS creditcustomerCount FROM `creditcustomer` WHERE invoice_header_id='$InvoiceID'";
    $creditcustomer = $dbObj->getData($sql);
    $creditcustomerCount=$creditcustomer[0]["creditcustomerCount"];
    if($creditcustomerCount > 0)
    {
        $sql = "UPDATE `creditcustomer` SET `CreditStat`='0' WHERE invoice_header_id= '$InvoiceID' ";
        $update = $dbObj->executeTransaction($sql); 
    }
    for ($i=0; $i <count($_POST["batch_id"]) ; $i++) 
    { 
        $item_id=$_POST["item_id"][$i];
        $batch_id=$_POST["batch"][$i];
        $batch=$_POST["batch_id"][$i];
        $product=$wholesale->getProductInfor($item_id);
        $discountType=$_POST["discountType"][$i];
        // echo $batch_id." ".$i."<br>";
        $discount=$_POST["discount"][$i];
        $product=$wholesale->select_product($item_id);
        //// echo  $i."<br>";
        //// echo  $item_id."<br>";
        $qty=$_POST["qty"][$i];
        if(isset($_POST["prodDes"][$i]))
        {
            $prodDes=$_POST["prodDes"][$i];
        }
        else
        {
            $prodDes=" ";
        }
        $rate=$_POST["rate"][$i];
        $sellAmount=$qty*$rate;
        if($discountType==1)
        {
            $ldiscount=$sellAmount*(float)$discount/100;
        }
        else
        {
            $ldiscount=$_POST["discount"][$i];
        }
        $total=($_POST["rate"][$i]*$_POST["qty"][$i])-$ldiscount;
        //// echo  $total;
        $original_rate=$_POST["original_rate"][$i];
        $discount=$_POST["discount"][$i];
        $original_total=$_POST["original_total"][$i];
        $is_minus=$shop->hasMinus($shop_SHID);

        $inventory=$wholesale->select_inventory($item_id, $batch);
        $avlQty=$inventory[0]["avlQty"];
        $BillQty=$inventory[0]["BillQty"];
        $newavlQty=$avlQty - $qty;
        $newBillQty=$BillQty + $qty;
        $inventory_id=$inventory[0]["INID"];
        $Item_Name=$product[0]["ItemName"];
        if($product[0]["ItemType"]=="P")
        {
            if($newavlQty < 0)
            {
                if($is_minus==1)
                {
                    $inventoryDetails=$wholesale->setInvoiceDetails($qty,$rate,$sellAmount,$discount,$discount,$total,$InvoiceID,$item_id,$batch_id,$discountType,$prodDes,$shop_id,$inventory_id,$Item_Name);
                    $newavlQty=$newavlQty * $product[0]["UnitConversion"];
                    $newBillQty=$newBillQty * $product[0]["UnitConversion"];
                    $stockUpdate=$wholesale->update_inventory($newavlQty,$newBillQty, $inventory_id);
                }
                else
                {
                 ?>
                    <script>
                        alert("Minus Quantity Not Allowed For <?=$product[0]["ItemName"]?>")
                    </script>
                    <?php
                }
            }
            else
            {
                $inventoryDetails=$wholesale->setInvoiceDetails($qty,$rate,$sellAmount,$discount,$discount,$total,$InvoiceID,$item_id,$batch_id,$discountType,$prodDes,$shop_id,$inventory_id,$Item_Name);
                $newavlQty=$newavlQty * $product[0]["UnitConversion"];
                $newBillQty=$newBillQty * $product[0]["UnitConversion"];
                $stockUpdate=$wholesale->update_inventory($newavlQty,$newBillQty, $inventory_id);
            }
        }
        else
        {
            $inventoryDetails=$wholesale->setInvoiceDetails($qty,$rate,$sellAmount,$discount,$discount,$total,$InvoiceID,$item_id,$batch_id,$discountType,$prodDes,$shop_id,$inventory_id,$Item_Name);
            $stockUpdate=$wholesale->update_inventory(0,$newBillQty, $inventory_id);
        }
    }
    $saleDiscount=$_POST["SaleDiscountType"];
    $InvItemCount=count($_POST["item_id"]);
    $GrossAmount=$_POST["grossAmount"];
    $lineDiscount=$_POST["totalDiscountLine"];
    $FixedDiscount=$_POST["saleDiscount"];
    $DiscountAmount=$_POST["totalDiscount"];
    $deliveryCharge=$_POST["deliverycharges"];
    $otheCharge=$_POST["otherCharges"];
    $NetAmount=$_POST["netamount"];
    $Salesmans_SLID = isset($_POST['salesman_id']) ? $_POST['salesman_id']: '1';
    $remarks=$_POST["details"];
    $CustPayment=0;
    for ($i=0; $i < count($_POST["paid"]) ; $i++) 
    { 
        $CustPayment=$CustPayment+(int)$_POST["paid"][$i];

    }
    $balance=$CustPayment-$NetAmount;
    if($balance > 0)
    {
        $CustBalance=$balance;
    }
    else
    {
        $CustBalance=0;        
    }
    if(isset($_POST["deliveryNote"]))
    {
        $is_delivery=$_POST["deliveryNote"];
    }
    else
    {
        $is_delivery=0;
    }
    if(isset($_POST["deliveryPartner"]))
    {
        $deliveryPartner=$_POST["deliveryPartner"];
    }
    else
    {
        $deliveryPartner=" ";
    }
    $saleDiscountType=$_POST["SaleDiscountType"];
    if($saleDiscountType==1)
    {
        $sqlHeader="UPDATE `invoiceheader` SET `InvItemCount`='$InvItemCount', `GrossAmount`='$GrossAmount',`lineDiscount`='$lineDiscount', `FixedDiscount`=0,`PercentDiscount`='$FixedDiscount', `DiscountAmount`='$DiscountAmount', `discountType`='1', `deliveryCharge`='$deliveryCharge', `otherCharge`='$otheCharge', `NetAmount`='$NetAmount', `CustPayment`='$CustPayment', `CustBalance`='$CustBalance',`Salesmans_SLID`='$Salesmans_SLID', `is_delivery`='$is_delivery',`deliveryPartner`='$deliveryPartner',`remarks`='$remarks' WHERE IHID= '$InvoiceID';";
    }
    else
    {
        $sqlHeader="UPDATE `invoiceheader` SET `InvItemCount`='$InvItemCount', `GrossAmount`='$GrossAmount',`lineDiscount`='$lineDiscount', `PercentDiscount`=0,`FixedDiscount`='$FixedDiscount', `DiscountAmount`='$DiscountAmount', `discountType`='2', `deliveryCharge`='$deliveryCharge', `otherCharge`='$otheCharge', `NetAmount`='$NetAmount', `CustPayment`='$CustPayment', `CustBalance`='$CustBalance',`Salesmans_SLID`='$Salesmans_SLID', `is_delivery`='$is_delivery',`deliveryPartner`='$deliveryPartner',`remarks`='$remarks' WHERE IHID= '$InvoiceID';";
    }
    $update = $dbObj->executeTransaction($sqlHeader); 
    $sql_5 = "DELETE FROM `transactions` WHERE InvoiceHeader_IHID= '$InvoiceID';";
    $update = $dbObj->executeTransaction($sql_5);
    for ($i=0; $i <count($_POST["paid"]) ; $i++) 
    { 
        $TransferAmount=$_POST["paid"][$i];
        $paymethod_PMID=$_POST["pay_id"][$i];
        if(isset($_POST["return_id"]))
        {
            $return_header_id=$_POST["return_id"];
        }
        else
        {
            $return_header_id=0;
        }
        if($paymethod_PMID==9)
        {
            //IF CREADIT NOTE
            $wholesale->setCreditCustDebit($EffectiveDate,$TransferAmount,$EffectiveDate,$InvoiceID,$paymethod_PMID,$customers_CTID,$user_USID,3);
        }
        
        $sql="INSERT INTO `transactions`(`TransferAmount`, `paymethod_PMID`, `InvoiceHeader_IHID`, `returnheader_id`) VALUES ('$TransferAmount','$paymethod_PMID','$InvoiceID','')";
        $dbObj->executeTransaction($sql);
        if($paymethod_PMID==5 && !empty($_POST["chqNo"]))
        {
            for ($j=0; $j <count($_POST["chqNo"]) ; $j++) 
            { 
                $sql = "SELECT count(*) AS ChqNo FROM custcheq ";
                $dbObj = new DBTransactions();
                $dbData = $dbObj->getData($sql);
                $count = $dbData[0]["ChqNo"]+1;
                $chq_no="CCHQ-".$wholesale->getSequence($count);
                $date=date("Y-m-d H:i:s");
                if(isset($_POST["chqNo"][$j]))
                {
                    $chequeNo=$_POST["chqNo"][$j];
                }
                else
                {
                    $chequeNo="N/A";
                }
                if(isset($_POST["chqdate"][$j]))
                {
                    $chqdate=$_POST["chqdate"][$j];
                }
                else
                {
                    $chqdate="N/A";
                }
                if(isset($_POST["chqbank"][$j]))
                {
                    $bank=$_POST["chqbank"][$j];
                }
                else
                {
                    $bank="N/A";
                }
                if(isset($_POST["chqAmount"][$j]))
                {
                    $chqAmount=$_POST["chqAmount"][$j];
                }
                else
                {
                    $chqAmount="N/A";
                }
                $date=date("Y-m-d H:i:s");
                $sql="INSERT INTO `custcheq`(`type`, `chq_stat`, `chq_no`, `cust_CTID`, `effectiveDate`, `user_USID`, `shop_SHID`, `createdDate`,`invoiceID`) VALUES ('2','1','$chq_no','$customers_CTID','$EffectiveDate','$user_USID','$shop_SHID','$date','$InvoiceID')";
                $dbObj->executeTransaction($sql);
                $sql="SELECT MAX(CCQID) AS CCQID FROM custcheq";
                $CCQID=$dbObj->getData($sql);
                $CCQID=$CCQID[0]["CCQID"];
                $sql="INSERT INTO `custchqdetail`(`bank`, `chqAmount`, `chqNo`, `chqDate`, `invoiceID`, `CCQID`) VALUES ('$bank','$chqAmount','$chequeNo','$chqdate','$InvoiceID','$CCQID')";
                $dbObj->executeTransaction($sql);
            }
        }    
    }
    $CustPayment=0;
    for ($i=0; $i < count($_POST["paid"]) ; $i++) 
    { 
        $CustPayment=$CustPayment+(int)$_POST["paid"][$i];
    }
    $NetAmount=$_POST["netamount"];
    if($NetAmount>$CustPayment)
    {
        $amount=$NetAmount-$CustPayment;
        // credit sale
        $cust=$wholesale->setCreditCust($EffectiveDate,$amount,$EffectiveDate,$InvoiceID,$payment,$customers_CTID,$user_USID,1);
    }
    $remarks="Invoice Edited";
    
    $user_USID=$_SESSION["user_id"];
    $invoice_remark=$wholesale->invoice_remark(remarks: $remarks,user: $user_USID,invoice_header_id: $InvoiceID,from_invoice: 0);
    if(isset($_POST["IRID"]))
    {
        $invoice_remark=$wholesale->updateremarks($_POST["IRID"],$_POST["remark"]);
    }
    elseif(isset($_POST["remark"]))
    {
        $remarks=$_POST["remark"];
        $invoice_remark=$wholesale->invoice_remark(remarks: $remarks,user: $user_USID,invoice_header_id: $InvoiceID,from_invoice: 1);
    }
    header("Location:../Public/view-wholesaleinovice.php?INVID=$InvoiceID");
}
elseif(isset($_POST["add-remark"]))
{
    $IHIDHeader=$_POST["IHIDHeader"];
    $user_USID=$_SESSION["user_id"];
    if(isset($_POST["remark"]))
    {
        $remarks=$_POST["remark"];
        $invoice_remark=$wholesale->invoice_remark(remarks: $remarks,user: $user_USID,invoice_header_id: $IHIDHeader,from_invoice: 0);    
        header("Location: ../Public/invoice-remarks.php?INVID=$IHIDHeader");    
    }
    else
    {
        header("Location: ../Public/invoice-remarks.php?INVID=$IHIDHeader");
    }
}
else
{
    header("Location: ../Public/wholesale-invoice.php");
}
?>