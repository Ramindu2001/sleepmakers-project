<?php 
ini_set('display_errors', 0);
include "../Includes/includes.php";
include "../Includes/authcheck.php";
$guiObj=new guiPOS();
$dbObj = new DBTransactions();
$comObj = new Common();
$shopObj = new Shop();
$SMS = new SMS();
$wholesale=new wholesale_invoice();
$cust = new Customer();
$user_USID=$_SESSION["user_id"];
$shop_id = $_SESSION['shop_id'];
$shopData=$shopObj->getOneShop($shop_id);
$alert=[];
$company_id=$shopData[0]["Company_CMID"];
$is_commonStock=$shopData[0]["is_commonStock"];
$stockType=$shopData[0]["StockTypes_STID"];
$is_expire=$shopData[0]["is_expire"];
$is_minus=$shopData[0]["is_minus"];

if(isset($_GET["cash"]) || isset($_POST["cash"]))
// if(isset($_GET["cash"]))
{
    $allow=true;
    if(isset($_POST["item_id"]) && count($_POST["item_id"]) > 0)
    {

        for ($i=0; $i <count($_POST["item_id"]) ; $i++) { 
            $item_id = $_POST["item_id"][$i];
            $Item_name = $_POST["Item_name"][$i];
            $productType = $_POST["productType"][$i];
            $qty = $_POST["qty"][$i];
            $original_rate = $_POST["original_rate"][$i];
            $rate = $_POST["rate"][$i];
            $discountType = $_POST["discountType"][$i];
            $Original_discounttype = $_POST["Original_discounttype"][$i];
            $discount = $_POST["discount"][$i];
            $original_discount = $_POST["original_discount"][$i];
            $totals = $_POST["totals"][$i];
            $original_total = $_POST["original_total"][$i];
            $productData=$guiObj->select_product($item_id);
            if($productData[0]["ItemType"]=="P")
            {
                $inventoryData=$guiObj->getInventory($item_id,$original_rate, $shop_id, $company_id, $is_commonStock, $stockType, false, $is_expire);
                if(count($inventoryData) > 0)
                {
                    if($qty > $inventoryData[0]["TotalCurrentQty"])
                    {
                        if($is_minus==1)
                        {
                            $inventoryData=$guiObj->getInventory($item_id,$original_rate, $shop_id, $company_id, $is_commonStock, $stockType, true, $is_expire);
                            if(count($inventoryData) > 0)
                            {
    
                            }
                            else
                            {
                                $alert["Error"][]="No Stock Available For ".$productData[0]["ItemName"]."<br> Error Code: #Inv-0013";
                                $allow=false;
                            }
                        }
                        else
                        {
                            $alert["Error"][]="Quantity Cannot Be Greater Than ".$inventoryData[0]["TotalCurrentQty"]."<br> Product: - ".$productData[0]["ItemName"]."<br> Error Code: #Inv-0003";
                            $allow=false;
                        }
                    }
                    else
                    {

                    }
                }
                else 
                {
                    if($is_minus==1)
                    {
                        $inventoryData=$guiObj->getInventory($item_id,$original_rate, $shop_id, $company_id, $is_commonStock, $stockType, true, $is_expire);
                        if(count($inventoryData) > 0)
                        {

                        }
                        else
                        {
                            $alert["Error"][]="No Stock Available For ".$productData[0]["ItemName"]."<br> Error Code: #Inv-0004";
                            $allow=false;
                        }
                    }
                    else
                    {
                        $alert["Error"][]="No Stock Available For ".$productData[0]["ItemName"]."<br> Error Code: #Inv-0005";
                        $allow=false;
                    }
                    
                }
            }
        }
        if($shopObj->hascounter($shop_id)==1)
        {
            $sql = "SELECT * FROM cashcounter WHERE user_USID = ".$user_USID." AND shop_SHID=".$shop_id." AND CounterStat = 1;";
            $counterData = $dbObj->getData($sql);
            if(count($counterData)==0)
            {
                $alert["Error"][]=" No Counter Available <br> Please Hold The Invoice <br> Error Code: #Inv-0012";
                $allow=false;
            }
            else
            {
                $CashCounter_CCID = $counterData[0]['CCID'];
            }
            
        }
        else
        {
        }
        if(isset($_POST["InvoiceNo"]))
        {
            $InvoiceNo=$_POST["InvoiceNo"];
        }
        else
        {
            $InvoiceNo="";  
        }        
        $EffectiveDate=date("Y-m-d");
        $doc=$guiObj->select_docno($shop_id);
        if(count($doc)==0)
        {
            $inser_doc=$guiObj->insert_doc_no($shop_id);
            $doc=$guiObj->select_docno($shop_id);
        }
        $ws_no=$doc[0]["org_no"] + 1;
        $newws_no=$doc[0]["org_no"] + 1;
        $ws_no=$guiObj->getSequence($ws_no);
        $salesetings=$guiObj->getSaleSettings($shop_id);
        if(count($salesetings)>0)
        {
            if(isset($salesetings[0]["billNoHeader"]))
            {
                $BillNo=$salesetings[0]["billNoHeader"]."-".$ws_no;
            }
            else
            {
                $BillNo="INV-".$ws_no;
            }
        }
        else
        {
            $BillNo="INV-".$ws_no;
        }
        $InvItemCount=count($_POST["item_id"]);
        $deliveryCharge = isset($_POST["deliveryCharge"]) ? $_POST["deliveryCharge"] : 0;
        $InvStartTime=date("Y-m-d H:i:s");
        $InvEndTime=date("Y-m-d H:i:s");
        $GrossAmount=$_POST["grossTotal"];
        $lineDiscount=$_POST["totalDiscountLine"];
        $saleDiscountType=isset($_POST["invoice_discount_type"]) ? $_POST["invoice_discount_type"] : 2;
        $FixedDiscount=isset($_POST["invoice_discount"]) ? $_POST["invoice_discount"] : 0;
        // Compute the total discount server-side from authoritative posted values so
        // the DiscountAmount column is always correct (line discount + invoice discount),
        // even if the posted total field is stale.
        $invoiceSaleDiscount = ($saleDiscountType == 1)
            ? (floatval($GrossAmount) * floatval($FixedDiscount) / 100)
            : floatval($FixedDiscount);
        $DiscountAmount = number_format(floatval($lineDiscount) + $invoiceSaleDiscount, 2, '.', '');
        $otheCharge = isset($_POST["otherCharges"]) ? $_POST["otherCharges"] : 0;
        $NetAmount=$_POST["netamount"];
        $CustPayment=$_POST["netamount"];
        $CustBalance=0;
        $InvStat=1;
        $customers_CTID=$_POST["customerid"];
        $Salesmans_SLID=$_POST["salesmanid"];
        
        if(isset($_POST["detail"]))
        {
            $remarksdetails=$_POST["detail"];
        }
        else
        {
            $remarksdetails="";
        }
        $excessamount= isset($_POST["excessamount"]) ? $_POST["excessamount"] : 0;
        if(isset($_POST["returnamount"]) && $_POST["returnamount"]!=0)
        {
            $returnAmount=  $_POST["returnamount"] ;
        }
        else
        {
            $returnAmount="";
        }
        if(isset($_POST["return_id"]) && $_POST["return_id"]!=0 && !empty($_POST["return_id"]))
        {
            $return_header_id=  $_POST["return_id"] ;
            $status=1;
            $update_return_status = $guiObj->update_return_status($status,$return_header_id);
            if($update_return_status == true)
            {
                $alert["Success"][]="Returned Successfully";
            }
            else
            {
                $alert["Error"][]="Return Unsuccessfull"."<br> Error Code: #Inv-0010";
            }
        }
        else
        {
            $return_header_id="";
        }
        
        $is_delivery= isset($_POST["is_delivery"]) ? $_POST["is_delivery"] : "";
        $deliveryPartner= isset($_POST["deliveryPartner"]) ? $_POST["deliveryPartner"] : "";
        $sales_source= isset($_POST["sales_source"]) ? $_POST["sales_source"] : "";
        if($allow==true)
        {
            if($shopObj->hascounter($shop_id)==1)
            {
                $sql = "SELECT * FROM cashcounter WHERE user_USID = ".$user_USID." AND shop_SHID=".$shop_id." AND CounterStat = 1;";
                $counterData = $dbObj->getData($sql);
                $CashCounter_CCID = $counterData[0]['CCID'];
            }
            else
            {
                $CashCounter_CCID =0;
            }
            
            $dupCheck = $dbObj->getData("SELECT IHID FROM invoiceheader WHERE BillNo='$BillNo' AND shop_SHID='$shop_id' LIMIT 1");
            if(count($dupCheck) > 0)
            {
                $alert["Error"][]="Duplicate invoice detected. Please try again. Error Code: #Inv-0020";
                $allow=false;
            }
            if($allow==false)
            {
                if(!empty($alert)) echo json_encode($alert);
                exit;
            }
            $guiObj->update_docno(shop_id: $shop_id,org_no:$newws_no );
            $HIID=0;
            if(isset($_POST["HIID"]) && $_POST["HIID"]!=0 && !empty($_POST["HIID"]))
            {
                $HIID = $_POST["HIID"];
                $sql="UPDATE hold_invoice SET InvStat=2 WHERE HIID='$HIID'";
                $dbObj->executeTransaction($sql);
            }
            $invoice_header=$guiObj->setInvoiceHeader(InvoiceNo: $InvoiceNo, EffectiveDate: $EffectiveDate,BillNo: $BillNo,InvStartTime: $InvStartTime,InvEndTime: $InvEndTime,InvItemCount: $InvItemCount,GrossAmount: $GrossAmount,lineDiscount: $lineDiscount,FixedDiscount: $FixedDiscount,DiscountAmount: $DiscountAmount,deliveryCharge: $deliveryCharge,otheCharge: $otheCharge,NetAmount: $NetAmount,CustPayment: $CustPayment,CustBalance: $CustBalance,InvStat: $InvStat,user_USID: $user_USID,customers_CTID: $customers_CTID,Salesmans_SLID: $Salesmans_SLID,shop_SHID: $shop_id,CashCounter_CCID: $CashCounter_CCID,remarks: $remarksdetails,excessamount: $excessamount,returnAmount: $returnAmount,return_header_id: $return_header_id,saleDiscountType: $saleDiscountType,is_delivery: $is_delivery,deliveryPartner: $deliveryPartner,sales_source: $sales_source,HIID:$HIID);
            $invoice_IHID=$guiObj->getLatestIHID($user_USID,$shop_id);
            $DNID=$doc[0]["DNID"];
            for ($i=0; $i <count($_POST["item_id"]) ; $i++) { 
                $item_id = $_POST["item_id"][$i];
                $Item_name = $_POST["Item_name"][$i];
                $prodDes = $_POST["Item_name"][$i];
                $productType = $_POST["productType"][$i];
                $qty = $_POST["qty"][$i];
                $original_rate = $_POST["original_rate"][$i];
                $rate = $_POST["rate"][$i];
                $discountType = $_POST["discountType"][$i];
                $Original_discounttype = $_POST["Original_discounttype"][$i];
                $discount = $_POST["discount"][$i];
                $original_discount = $_POST["original_discount"][$i];
                $totals = $_POST["totals"][$i];
                $original_total = $_POST["original_total"][$i];
                $productData=$guiObj->select_product($item_id);
                if($productData[0]["ItemType"]=="P")
                {
                    $inventoryData=$guiObj->getInventory($item_id,$original_rate, $shop_id, $company_id, $is_commonStock, $stockType, false, $is_expire);
                    
                    if(count($inventoryData) > 0)
                    {
                        if($qty > $inventoryData[0]["TotalCurrentQty"])
                        {
                            if($is_minus==1)
                            {
                                $inventoryData=$guiObj->getInventory($item_id,$original_rate, $shop_id, $company_id, $is_commonStock, $stockType, true, $is_expire);
                                if(count($inventoryData) > 0)
                                {
                                    $inventory=$guiObj->selectInventory($item_id, $original_rate, $shop_id, $company_id, $is_commonStock, $stockType,true);
                                    $total_consumption = $qty;
                                    foreach($inventory AS $row)
                                    {
                                        if($total_consumption > 0)
                                        {
                                            $currentQty=$row["CurrentQty"];
                                            $INID=$row["INID"];
                                            if($currentQty <= $total_consumption)
                                            {
                                                $total_consumption = $total_consumption - $currentQty;
                                                $update_sql = "UPDATE inventory SET CurrentQty = 0, BillQty=(BillQty+$currentQty) WHERE INID = $INID";
                                                $status=1;
                                                $batchNo=$guiObj->selectBatch($INID);
                                                $inventoryConsumtion=$guiObj->inventory_consumption($invoice_IHID,$status,$INID,$original_rate,$rate,$batchNo,$item_id,$currentQty,$shop_id);
                                            }
                                            else
                                            {
                                                // Partially reduce this entry and set remaining quantity to 0
                                                $remaining_quantity = $currentQty - $total_consumption;
                                                $update_sql = "UPDATE inventory SET CurrentQty = '$remaining_quantity', BillQty=(BillQty+$total_consumption) WHERE INID = $INID";
                                                $status=1;
                                                $batchNo=$guiObj->selectBatch($INID);
                                                $inventoryConsumtion=$guiObj->inventory_consumption($invoice_IHID,$status,$INID,$original_rate,$rate,$batchNo,$item_id,$total_consumption,$shop_id);
                                                $total_consumption = 0;
                                            }
                                            $update = $dbObj->executeTransaction($update_sql);
                                        }
                                    }
                                    if($total_consumption > 0)
                                    {
                                        $inventory=$guiObj->selectInventory(product_id: $item_id, price: $original_rate, shop_id: $shop_id, company_id: $company_id, is_commonStock: $is_commonStock, stockType: $stockType,is_default: false);
                                        foreach($inventory AS $row)
                                        {
                                            if($total_consumption > 0)
                                            {
                                                $currentQty=$row["CurrentQty"];
                                                $INID=$row["INID"];
                                                if($currentQty <= $total_consumption)
                                                {
                                                    $total_consumption = $total_consumption - $currentQty;
                                                    $update_sql = "UPDATE inventory SET CurrentQty = 0, BillQty=(BillQty+$currentQty) WHERE INID = $INID";
                                                    $status=1;
                                                    $batchNo=$guiObj->selectBatch($INID);
                                                    $inventoryConsumtion=$guiObj->inventory_consumption($invoice_IHID,$status,$INID,$original_rate,$rate,$batchNo,$item_id,$currentQty,$shop_id);
                                                }
                                                else
                                                {
                                                    $remaining_quantity = $currentQty - $total_consumption;
                                                    $update_sql = "UPDATE inventory SET CurrentQty = '$remaining_quantity', BillQty=(BillQty+$total_consumption) WHERE INID = $INID";
                                                    $status=1;
                                                    $batchNo=$guiObj->selectBatch($INID);
                                                    $inventoryConsumtion=$guiObj->inventory_consumption($invoice_IHID,$status,$INID,$original_rate,$rate,$batchNo,$item_id,$total_consumption,$shop_id);
                                                    $total_consumption = 0;
                                                }
                                                $update = $dbObj->executeTransaction($update_sql);
                                            }
                                        }
                                    }
                                    if($total_consumption > 0)
                                    {
                                        $defaultInv=$guiObj->selectInventory($item_id, $original_rate, $shop_id, $company_id, $is_commonStock, $stockType, true);
                                        if(count($defaultInv) > 0)
                                        {
                                            $defINID=$defaultInv[0]["INID"];
                                            $update_sql = "UPDATE inventory SET CurrentQty = CurrentQty - $total_consumption, BillQty=(BillQty+$total_consumption) WHERE INID = $defINID";
                                            $dbObj->executeTransaction($update_sql);
                                            $batchNo=$guiObj->selectBatch($defINID);
                                            $guiObj->inventory_consumption($invoice_IHID,1,$defINID,$original_rate,$rate,$batchNo,$item_id,$total_consumption,$shop_id);
                                        }
                                        $total_consumption = 0;
                                    }
                                    $inventoryDetails=$guiObj->setInvoiceDetails(sqllQty: $qty,unitPrice: $rate,sellAmount: $original_total,discount: $discount,totalDiscount: $discount,total: $totals,invoiceHeader: $invoice_IHID,pro_id: $item_id,discountType: $discountType,prodDes: $prodDes,shop_id: $shop_id, Item_Name: $Item_name);
                                    
                                }
                                else
                                {
                                    // no stock
                                }
                            }
                            else
                            {
                                //Quantity Greater Than Available
                            }
                        }
                        else
                        {
                            $inventory=$guiObj->selectInventory($item_id, $original_rate, $shop_id, $company_id, $is_commonStock, $stockType);
                            
                            $total_consumption=$qty;
                            foreach($inventory AS $row)
                            {
                                if($total_consumption > 0)
                                {
                                    $currentQty=$row["CurrentQty"];
                                    $INID=$row["INID"];
                                    if($currentQty <= $total_consumption)
                                    {
                                        $total_consumption = $total_consumption - $currentQty;
                                        $update_sql = "UPDATE inventory SET CurrentQty = 0, BillQty=(BillQty+$currentQty) WHERE INID = $INID";
                                        $status=1;
                                        $batchNo=$guiObj->selectBatch($INID);
                                        $inventoryConsumtion=$guiObj->inventory_consumption($invoice_IHID,$status,$INID,$original_rate,$rate,$batchNo,$item_id,$currentQty,$shop_id);
                                    }
                                    else
                                    {
                                        // Partially reduce this entry and set remaining quantity to 0
                                        $remaining_quantity = $currentQty - $total_consumption;
                                        $update_sql = "UPDATE inventory SET CurrentQty = '$remaining_quantity', BillQty=(BillQty+$total_consumption) WHERE INID = $INID";
                                        $status=1;
                                        $batchNo=$guiObj->selectBatch($INID);
                                        $inventoryConsumtion=$guiObj->inventory_consumption($invoice_IHID,$status,$INID,$original_rate,$rate,$batchNo,$item_id,$total_consumption,$shop_id);
                                        $total_consumption = 0;
                                    }
                                    $update = $dbObj->executeTransaction($update_sql);
                                }
                            } 
                            $inventoryDetails=$guiObj->setInvoiceDetails(sqllQty: $qty,unitPrice: $rate,sellAmount: $original_total,discount: $discount,totalDiscount: $discount,total: $totals,invoiceHeader: $invoice_IHID,pro_id: $item_id,discountType: $discountType,prodDes: $prodDes,shop_id: $shop_id, Item_Name: $Item_name);                           
                        }
                    }
                    else 
                    {
                        if($is_minus==1)
                        {
                            $inventoryData=$guiObj->getInventory($item_id,$original_rate, $shop_id, $company_id, $is_commonStock, $stockType, true, $is_expire);
                            if(count($inventoryData) > 0)
                            {
                                $inventory=$guiObj->selectInventory($item_id, $original_rate, $shop_id, $company_id, $is_commonStock, $stockType,true);
                                $total_consumption=$qty;
                                foreach($inventory AS $row)
                                {
                                    if($total_consumption > 0)
                                    {
                                        $currentQty=$row["CurrentQty"];
                                        $INID=$row["INID"];
                                        if($currentQty <= $total_consumption)
                                        {
                                            $total_consumption = $total_consumption - $currentQty;
                                            $update_sql = "UPDATE inventory SET CurrentQty = 0, BillQty=(BillQty+$currentQty) WHERE INID = $INID";
                                            $status=1;
                                            $batchNo=$guiObj->selectBatch($INID);
                                            $inventoryConsumtion=$guiObj->inventory_consumption($invoice_IHID,$status,$INID,$original_rate,$rate,$batchNo,$item_id,$currentQty,$shop_id);
                                        }
                                        else
                                        {
                                            // Partially reduce this entry and set remaining quantity to 0
                                            $remaining_quantity = $currentQty - $total_consumption;
                                            $update_sql = "UPDATE inventory SET CurrentQty = '$remaining_quantity', BillQty=(BillQty+$total_consumption) WHERE INID = $INID";
                                            $status=1;
                                            $batchNo=$guiObj->selectBatch($INID);
                                            $inventoryConsumtion=$guiObj->inventory_consumption($invoice_IHID,$status,$INID,$original_rate,$rate,$batchNo,$item_id,$total_consumption,$shop_id);
                                            $total_consumption = 0;
                                        }
                                        if($total_consumption > 0)
                                        {
                                            $update = $dbObj->executeTransaction($update_sql);
                                        }
                                    }
                                }
                                if($total_consumption > 0)
                                {
                                    $inventory=$guiObj->selectInventory(product_id: $item_id, price: $original_rate, shop_id: $shop_id, company_id: $company_id, is_commonStock: $is_commonStock, stockType: $stockType,is_default: false);
                                    foreach($inventory AS $row)
                                    {
                                        if($total_consumption > 0)
                                        {
                                            $currentQty=$row["CurrentQty"];
                                            $INID=$row["INID"];
                                            if($currentQty <= $total_consumption)
                                            {
                                                $total_consumption = $total_consumption - $currentQty;
                                                $update_sql = "UPDATE inventory SET CurrentQty = 0, BillQty=(BillQty+$currentQty) WHERE INID = $INID";
                                                $status=1;
                                                $batchNo=$guiObj->selectBatch($INID);
                                                $inventoryConsumtion=$guiObj->inventory_consumption($invoice_IHID,$status,$INID,$original_rate,$rate,$batchNo,$item_id,$currentQty,$shop_id);
                                            }
                                            else
                                            {
                                                $remaining_quantity = $currentQty - $total_consumption;
                                                $update_sql = "UPDATE inventory SET CurrentQty = '$remaining_quantity', BillQty=(BillQty+$total_consumption) WHERE INID = $INID";
                                                $status=1;
                                                $batchNo=$guiObj->selectBatch($INID);
                                                $inventoryConsumtion=$guiObj->inventory_consumption($invoice_IHID,$status,$INID,$original_rate,$rate,$batchNo,$item_id,$total_consumption,$shop_id);
                                                $total_consumption = 0;
                                            }
                                            if($total_consumption > 0)
                                            {
                                                $update = $dbObj->executeTransaction($update_sql);
                                            }
                                        }
                                    }
                                }
                                if($total_consumption > 0)
                                {
                                    $defaultInv=$guiObj->selectInventory($item_id, $original_rate, $shop_id, $company_id, $is_commonStock, $stockType, true);
                                    if(count($defaultInv) > 0)
                                    {
                                        $defINID=$defaultInv[0]["INID"];
                                        $update_sql = "UPDATE inventory SET CurrentQty = CurrentQty - $total_consumption, BillQty=(BillQty+$total_consumption) WHERE INID = $defINID";
                                        $dbObj->executeTransaction($update_sql);
                                        $batchNo=$guiObj->selectBatch($defINID);
                                        $guiObj->inventory_consumption($invoice_IHID,1,$defINID,$original_rate,$rate,$batchNo,$item_id,$total_consumption,$shop_id);
                                    }
                                    $total_consumption = 0;
                                }
                                $inventoryDetails=$guiObj->setInvoiceDetails(sqllQty: $qty,unitPrice: $rate,sellAmount: $original_total,discount: $discount,totalDiscount: $discount,total: $totals,invoiceHeader: $invoice_IHID,pro_id: $item_id,discountType: $discountType,prodDes: $prodDes,shop_id: $shop_id, Item_Name: $Item_name);

                            }
                            else
                            {
                                // No Stock
                            }
                        }
                        else
                        {
                            // No Stock
                        }
                        
                    }
                }
                else
                {
                    $inventoryDetails=$guiObj->setInvoiceDetails(sqllQty: $qty,unitPrice: $rate,sellAmount: $original_total,discount: $discount,totalDiscount: $discount,total: $totals,invoiceHeader: $invoice_IHID,pro_id: $item_id,discountType: $discountType,prodDes: $prodDes,shop_id: $shop_id, Item_Name: $Item_name,ItemType:2);

                }

                
            }
            $paymethod_PMID=1;
            $TransferAmount=$NetAmount;
            $sql="INSERT INTO `transactions`(`TransferAmount`, `paymethod_PMID`, `InvoiceHeader_IHID`, `returnheader_id`) VALUES ('$TransferAmount','$paymethod_PMID','$invoice_IHID','')";
            if($dbObj->executeTransaction($sql)==true)
            {
                
            }
            else
            {
                $alert["Error"][]="Transaction Cannot be Added "."<br> Error Code: #Inv-0008";
            }
            $remarks="Invoice Created";
            $invoice_remark=$wholesale->invoice_remark(remarks: $remarks,user: $user_USID,invoice_header_id: $invoice_IHID,from_invoice: 0);
            if(isset($_POST["internalRemark"]))
            {
                $remarks=$_POST["internalRemark"];
                $invoice_remark=$wholesale->invoice_remark(remarks: $remarks,user: $user_USID,invoice_header_id: $invoice_IHID,from_invoice:1);
            }
            if($invoice_remark==true)
            {
                
            }
            if($invoice_header==true)
            {
                $alert["Success"][]="Invoice Created Successfully";
                $alert["invoiceID"][]=$invoice_IHID;
            }
            else
            {
                $alert["Error"][]="Oops, Something Went Wrong. Error Code: #Inv-0001";

            }
        }      
        else
        {
            $alert["Error"][]="Invoice Creation Failed. Error Code: #Inv-0002"; 
        }
    }
    else
    {
        $alert["Error"][]="Cart Cannot Be Empty"."<br> Error Code: #Inv-0011";
        // return false;
    }    
    if(!empty($alert))
    echo json_encode($alert);
    
}
if(isset($_GET["invoiceHold"]))
{
    if(isset($_POST["item_id"]) && count($_POST["item_id"]) > 0)
    {
        if(isset($_POST["InvoiceNo"]))
        {
            $InvoiceNo=$_POST["InvoiceNo"];
        }
        else
        {
            $InvoiceNo="";  
            // $alert["Error"][]="No Invoice No";  
        }        
        $EffectiveDate=date("Y-m-d");
        $doc=$guiObj->select_docno($shop_id);
        if(count($doc)==0)
        {
            $inser_doc=$guiObj->insert_doc_no($shop_id);
            $doc=$guiObj->select_docno($shop_id);
        }
        $ws_no=$doc[0]["tmp_no"] + 1;
        $newws_no=$doc[0]["tmp_no"] + 1;
        $ws_no=$guiObj->getSequence($ws_no);
        $BillNo="TEMP_".$ws_no;
        $InvItemCount=count($_POST["item_id"]);
        $deliveryCharge = isset($_POST["deliveryCharge"]) ? $_POST["deliveryCharge"] : 0;
        $InvStartTime=date("Y-m-d H:i:s");
        $InvEndTime=date("Y-m-d H:i:s");
        $GrossAmount=$_POST["grossTotal"];
        $lineDiscount=$_POST["totalDiscountLine"];
        $saleDiscountType=isset($_POST["invoice_discount_type"]) ? $_POST["invoice_discount_type"] : 2;
        $FixedDiscount=isset($_POST["invoice_discount"]) ? $_POST["invoice_discount"] : 0;
        // Compute the total discount server-side from authoritative posted values so
        // the DiscountAmount column is always correct (line discount + invoice discount),
        // even if the posted total field is stale.
        $invoiceSaleDiscount = ($saleDiscountType == 1)
            ? (floatval($GrossAmount) * floatval($FixedDiscount) / 100)
            : floatval($FixedDiscount);
        $DiscountAmount = number_format(floatval($lineDiscount) + $invoiceSaleDiscount, 2, '.', '');
        $otheCharge = isset($_POST["otherCharges"]) ? $_POST["otherCharges"] : 0;
        $NetAmount=$_POST["netamount"];
        $CustPayment=$_POST["netamount"];
        $CustBalance=0;
        $InvStat=1;
        $customers_CTID=$_POST["customerid"];
        $Salesmans_SLID=$_POST["salesmanid"];
        if($shopObj->hascounter($shop_id)==1)
        {
            $sql = "SELECT * FROM cashcounter WHERE user_USID = ".$user_USID." AND shop_SHID=".$shop_id." AND CounterStat = 1;";
            $counterData = $dbObj->getData($sql);
            $CashCounter_CCID = $counterData[0]['CCID'];
        }
        else
        {
            $CashCounter_CCID =0;
            // $alert["Error"][]="No Counter";  
        }
        if(isset($_POST["details"]))
        {
            $remarksdetails=$_POST["details"];
        }
        else
        {
            $remarksdetails="";
        }
        $excessamount= isset($_POST["excessamount"]) ? $_POST["excessamount"] : 0;
        if(isset($_POST["returnamount"]) && $_POST["returnamount"]!=0)
        {
            $returnAmount=  $_POST["returnamount"] ;
        }
        else
        {
            $returnAmount="";
        }
        if(isset($_POST["return_id"]) && $_POST["return_id"]!=0 && !empty($_POST["return_id"]) && !empty($_POST["return_id"]))
        {
            $return_header_id=  $_POST["return_id"] ;
            $status=1;
            $update_return_status = $guiObj->update_return_status($status,$return_header_id);
            if($update_return_status == true)
            {
                $alert["Success"][]="Returned Successfully";
            }
            else
            {
                $alert["Error"][]="Return Unsuccessfull"."<br> Error Code: #Inv-0010";
            }
        }
        else
        {
            $return_header_id="";
        }
        $return_header_id= isset($_POST["return_header_id"]) ? $_POST["return_header_id"] : "";
        $is_delivery= isset($_POST["is_delivery"]) ? $_POST["is_delivery"] : "";
        $deliveryPartner= isset($_POST["deliveryPartner"]) ? $_POST["deliveryPartner"] : "";
        $sales_source= isset($_POST["sales_source"]) ? $_POST["sales_source"] : "";
        if(isset($_POST["HIID"]) && $_POST["HIID"]!=0 && !empty($_POST["HIID"]))
        {
            $invoice_IHID = $_POST["HIID"];
            $delete=$guiObj->deleteHoldInvoiceDetail($invoice_IHID);
            $invoice_header=$guiObj->UpdateHoldInvoiceHeader(HIID: $invoice_IHID,InvoiceNo: $InvoiceNo, EffectiveDate: $EffectiveDate,BillNo: $BillNo,InvStartTime: $InvStartTime,InvEndTime: $InvEndTime,InvItemCount: $InvItemCount,GrossAmount: $GrossAmount,lineDiscount: $lineDiscount,FixedDiscount: $FixedDiscount,DiscountAmount: $DiscountAmount,deliveryCharge: $deliveryCharge,otheCharge: $otheCharge,NetAmount: $NetAmount,CustPayment: $CustPayment,CustBalance: $CustBalance,InvStat: $InvStat,user_USID: $user_USID,customers_CTID: $customers_CTID,Salesmans_SLID: $Salesmans_SLID,shop_SHID: $shop_id,CashCounter_CCID: $CashCounter_CCID,remarks: $remarksdetails,excessamount: $excessamount,returnAmount: $returnAmount,return_header_id: $return_header_id,saleDiscountType: $saleDiscountType,is_delivery: $is_delivery,deliveryPartner: $deliveryPartner,sales_source: $sales_source);
            $update=1;
        }
        else
        {
            $guiObj->update_docno(shop_id: $shop_id,tmp_no:$newws_no );
            $invoice_header=$guiObj->setHoldInvoiceHeader($InvoiceNo, $EffectiveDate,$BillNo,$InvStartTime,$InvEndTime,$InvItemCount,$GrossAmount,$lineDiscount,$FixedDiscount,$DiscountAmount,$deliveryCharge,$otheCharge,$NetAmount,$CustPayment,$CustBalance,$InvStat,$user_USID,$customers_CTID,$Salesmans_SLID,$shop_id,$CashCounter_CCID,$remarksdetails,$excessamount,$returnAmount,$return_header_id,$saleDiscountType,$is_delivery,$deliveryPartner,$sales_source);
            $invoice_IHID=$guiObj->getLatestHIID($user_USID,$shop_id);
            $create=1;
        }
        
        
        for ($i=0; $i <count($_POST["item_id"]) ; $i++) { 
            $item_id = $_POST["item_id"][$i];
            $Item_name = $_POST["Item_name"][$i];
            $prodDes = $_POST["Item_name"][$i];
            $productType = $_POST["productType"][$i];
            $qty = $_POST["qty"][$i];
            $original_rate = $_POST["original_rate"][$i];
            $rate = $_POST["rate"][$i];
            $discountType = $_POST["discountType"][$i];
            $Original_discounttype = $_POST["Original_discounttype"][$i];
            $discount = $_POST["discount"][$i];
            $original_discount = $_POST["original_discount"][$i];
            $totals = $_POST["totals"][$i];
            $original_total = $_POST["original_total"][$i];
            $productData=$guiObj->select_product($item_id);
            $item_detail=$guiObj->setSellDetails(sqllQty: $qty,unitPrice: $rate,sellAmount: $original_total,discount: $discount,totalDiscount: $discount,total: $totals,invoiceHeader: $invoice_IHID,pro_id: $item_id,discountType: $discountType,prodDes: $prodDes,shop_id: $shop_id, Item_Name: $Item_name,origi_UnitPrice:$original_rate);
            if($item_detail==false)
            {
                $alert["Error"][]="Oops! Hold Items Aren't Created.Error Code: #Inv-0016";
            }
        }
        if(isset($create) && $create==1)
        {
            $alert["Success"][]="Hold Invoice Created Successfully";
        }
        if(isset($update) && $update==1)
        {
            $alert["Success"][]="Hold Invoice Updated Successfully";
        }
        
    }
    else
    {
        $alert["Error"][]="Cart Cannot Be Empty";
        // return false;
    }    
    if (!empty($alert)) {
        header('Content-Type: application/json');
        echo json_encode($alert);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON Encoding Error: " . json_last_error_msg());
        }
    }
    
}
if(isset($_GET["btn_submit_invoice"]) || isset($_GET["btn_submit"]))
{
    $allow=true;
    if(isset($_POST["item_id"]) && count($_POST["item_id"]) > 0)
    {

        for ($i=0; $i <count($_POST["item_id"]) ; $i++) { 
            $item_id = $_POST["item_id"][$i];
            $Item_name = $_POST["Item_name"][$i];
            $productType = $_POST["productType"][$i];
            $qty = $_POST["qty"][$i];
            $original_rate = $_POST["original_rate"][$i];
            $rate = $_POST["rate"][$i];
            $discountType = $_POST["discountType"][$i];
            $Original_discounttype = $_POST["Original_discounttype"][$i];
            $discount = $_POST["discount"][$i];
            $original_discount = $_POST["original_discount"][$i];
            $totals = $_POST["totals"][$i];
            $original_total = $_POST["original_total"][$i];
            $productData=$guiObj->select_product($item_id);
            if($productData[0]["ItemType"]=="P")
            {
                $inventoryData=$guiObj->getInventory($item_id,$original_rate, $shop_id, $company_id, $is_commonStock, $stockType, false, $is_expire);
                if(count($inventoryData) > 0)
                {
                    if($qty > $inventoryData[0]["TotalCurrentQty"])
                    {
                        if($is_minus==1)
                        {
                            $inventoryData=$guiObj->getInventory($item_id,$original_rate, $shop_id, $company_id, $is_commonStock, $stockType, true, $is_expire);
                            if(count($inventoryData) > 0)
                            {
    
                            }
                            else
                            {
                                $alert["Error"][]="No Stock Available For ".$productData[0]["ItemName"]."<br> Error Code: #Inv-0013";
                                $allow=false;
                            }
                        }
                        else
                        {
                            $alert["Error"][]="Quantity Cannot Be Greater Than ".$inventoryData[0]["TotalCurrentQty"]."<br> Product: - ".$productData[0]["ItemName"]."<br> Error Code: #Inv-0003";
                            $allow=false;
                        }
                    }
                    else
                    {

                    }
                }
                else 
                {
                    if($is_minus==1)
                    {
                        $inventoryData=$guiObj->getInventory($item_id,$original_rate, $shop_id, $company_id, $is_commonStock, $stockType, true, $is_expire);
                        if(count($inventoryData) > 0)
                        {

                        }
                        else
                        {
                            $alert["Error"][]="No Stock Available For ".$productData[0]["ItemName"]."<br> Error Code: #Inv-0004";
                            $allow=false;
                        }
                    }
                    else
                    {
                        $alert["Error"][]="No Stock Available For ".$productData[0]["ItemName"]."<br> Error Code: #Inv-0005";
                        $allow=false;
                    }
                    
                }
            }
        }
        for ($i=0; $i <count($_POST["paymentType"]) ; $i++) 
        { 
            if(!isset($_POST["paymentType"][$i]) || empty(isset($_POST["paymentType"][$i])))
            {
                $alert["Error"][]=" No Payment Type <br> Please Select a Payment Type<br> Error Code: #Inv-0014";
                $allow=false;
            }
            if(!isset($_POST["Amount"][$i]) || empty(isset($_POST["Amount"][$i])))
            {
                $alert["Error"][]=" No Payment Type <br> Please Select a Payment Type<br> Error Code: #Inv-0015";
                $allow=false;
            }
        }
        for ($i=0; $i <count($_POST["Amount"]) ; $i++) 
        { 
            if(!isset($_POST["Amount"][$i]) || empty(isset($_POST["Amount"][$i])))
            {
                $alert["Error"][]=" No Payment Amount <br>Error Code: #Inv-0015";
                $allow=false;
            }
        }
        if($shopObj->hascounter($shop_id)==1)
        {
            $sql = "SELECT * FROM cashcounter WHERE user_USID = ".$user_USID." AND shop_SHID=".$shop_id." AND CounterStat = 1;";
            $counterData = $dbObj->getData($sql);
            if(count($counterData)==0)
            {
                $alert["Error"][]=" No Counter Available <br> Please Hold The Invoice <br> Error Code: #Inv-0012";
                $allow=false;
            }
            else
            {
                $CashCounter_CCID = $counterData[0]['CCID'];
            }
            
        }
        else
        {
        }
        if(isset($_POST["InvoiceNo"]))
        {
            $InvoiceNo=$_POST["InvoiceNo"];
        }
        else
        {
            $InvoiceNo="";  
        }        
        $EffectiveDate=date("Y-m-d");
        $doc=$guiObj->select_docno($shop_id);
        if(count($doc)==0)
        {
            $inser_doc=$guiObj->insert_doc_no($shop_id);
            $doc=$guiObj->select_docno($shop_id);
        }
        $ws_no=$doc[0]["org_no"] + 1;
        $newws_no=$doc[0]["org_no"] + 1;
        $ws_no=$guiObj->getSequence($ws_no);
        $salesetings=$guiObj->getSaleSettings($shop_id);
        if(count($salesetings)>0)
        {
            if(isset($salesetings[0]["billNoHeader"]))
            {
                $BillNo=$salesetings[0]["billNoHeader"]."-".$ws_no;
            }
            else
            {
                $BillNo="INV-".$ws_no;
            }
        }
        else
        {
            $BillNo="Temp_-".$ws_no;
        }
        $InvItemCount=count($_POST["item_id"]);
        $deliveryCharge = isset($_POST["deliveryCharge"]) ? $_POST["deliveryCharge"] : 0;
        $InvStartTime=date("Y-m-d H:i:s");
        $InvEndTime=date("Y-m-d H:i:s");
        $GrossAmount=$_POST["grossTotal"];
        $lineDiscount=$_POST["totalDiscountLine"];
        $saleDiscountType=isset($_POST["invoice_discount_type"]) ? $_POST["invoice_discount_type"] : 2;
        $FixedDiscount=isset($_POST["invoice_discount"]) ? $_POST["invoice_discount"] : 0;
        // Compute the total discount server-side from authoritative posted values so
        // the DiscountAmount column is always correct (line discount + invoice discount),
        // even if the posted total field is stale.
        $invoiceSaleDiscount = ($saleDiscountType == 1)
            ? (floatval($GrossAmount) * floatval($FixedDiscount) / 100)
            : floatval($FixedDiscount);
        $DiscountAmount = number_format(floatval($lineDiscount) + $invoiceSaleDiscount, 2, '.', '');
        $otheCharge = isset($_POST["otherCharges"]) ? $_POST["otherCharges"] : 0;
        $NetAmount=$_POST["netamount"];
        $CustPayment=$_POST["netamount"];
        $CustBalance=0;
        $InvStat=1;
        $customers_CTID=$_POST["customerid"];
        $Salesmans_SLID=$_POST["salesmanid"];
        
        if(isset($_POST["detail"]))
        {
            $remarksdetails=$_POST["detail"];
        }
        else
        {
            $remarksdetails="";
        }
        $excessamount= isset($_POST["excessamount"]) ? $_POST["excessamount"] : 0;
        if(isset($_POST["returnamount"]) && $_POST["returnamount"]!=0)
        {
            $returnAmount=  $_POST["returnamount"] ;
        }
        else
        {
            $returnAmount="";
        }
        if(isset($_POST["return_id"]) && $_POST["return_id"]!=0 && !empty($_POST["return_id"]))
        {
            $return_header_id=  $_POST["return_id"] ;
            $status=1;
            $update_return_status = $guiObj->update_return_status($status,$return_header_id);
            if($update_return_status == true)
            {
                $alert["Success"][]="Returned Successfully";
            }
            else
            {
                $alert["Error"][]="Return Unsuccessfull"."<br> Error Code: #Inv-0010";
            }
        }
        else
        {
            $return_header_id="";
        }
        $is_delivery= isset($_POST["is_delivery"]) ? $_POST["is_delivery"] : "";
        $deliveryPartner= isset($_POST["deliveryPartner"]) ? $_POST["deliveryPartner"] : "";
        $sales_source= isset($_POST["sales_source"]) ? $_POST["sales_source"] : "";
        if($allow==true)
        {
            if($shopObj->hascounter($shop_id)==1)
            {
                $sql = "SELECT * FROM cashcounter WHERE user_USID = ".$user_USID." AND shop_SHID=".$shop_id." AND CounterStat = 1;";
                $counterData = $dbObj->getData($sql);
                $CashCounter_CCID = $counterData[0]['CCID'];
            }
            else
            {
                $CashCounter_CCID =0;
            }
            
            $dupCheck = $dbObj->getData("SELECT IHID FROM invoiceheader WHERE BillNo='$BillNo' AND shop_SHID='$shop_id' LIMIT 1");
            if(count($dupCheck) > 0)
            {
                $alert["Error"][]="Duplicate invoice detected. Please try again. Error Code: #Inv-0020";
                $allow=false;
            }
            if($allow==false)
            {
                if(!empty($alert)) { header('Content-Type: application/json'); echo json_encode($alert); }
                exit;
            }
            $guiObj->update_docno(shop_id: $shop_id,org_no:$newws_no );
            $HIID=0;
            if(isset($_POST["HIID"]) && $_POST["HIID"]!=0 && !empty($_POST["HIID"]))
            {
                $HIID = $_POST["HIID"];
                $sql="UPDATE hold_invoice SET InvStat=2 WHERE HIID='$HIID'";
                $dbObj->executeTransaction($sql);
            }
            $invoice_header=$guiObj->setInvoiceHeader(InvoiceNo: $InvoiceNo, EffectiveDate: $EffectiveDate,BillNo: $BillNo,InvStartTime: $InvStartTime,InvEndTime: $InvEndTime,InvItemCount: $InvItemCount,GrossAmount: $GrossAmount,lineDiscount: $lineDiscount,FixedDiscount: $FixedDiscount,DiscountAmount: $DiscountAmount,deliveryCharge: $deliveryCharge,otheCharge: $otheCharge,NetAmount: $NetAmount,CustPayment: $CustPayment,CustBalance: $CustBalance,InvStat: $InvStat,user_USID: $user_USID,customers_CTID: $customers_CTID,Salesmans_SLID: $Salesmans_SLID,shop_SHID: $shop_id,CashCounter_CCID: $CashCounter_CCID,remarks: $remarksdetails,excessamount: $excessamount,returnAmount: $returnAmount,return_header_id: $return_header_id,saleDiscountType: $saleDiscountType,is_delivery: $is_delivery,deliveryPartner: $deliveryPartner,sales_source: $sales_source,HIID:$HIID);
            $invoice_IHID=$guiObj->getLatestIHID($user_USID,$shop_id);
            $DNID=$doc[0]["DNID"];
            for ($i=0; $i <count($_POST["item_id"]) ; $i++) 
            { 
                $item_id = $_POST["item_id"][$i];
                $Item_name = $_POST["Item_name"][$i];
                $prodDes = $_POST["Item_name"][$i];
                $productType = $_POST["productType"][$i];
                $qty = $_POST["qty"][$i];
                $original_rate = $_POST["original_rate"][$i];
                $rate = $_POST["rate"][$i];
                $discountType = $_POST["discountType"][$i];
                $Original_discounttype = $_POST["Original_discounttype"][$i];
                $discount = $_POST["discount"][$i];
                $original_discount = $_POST["original_discount"][$i];
                $totals = $_POST["totals"][$i];
                $original_total = $_POST["original_total"][$i];
                $productData=$guiObj->select_product($item_id);
                if($productData[0]["ItemType"]=="P")
                {
                    $inventoryData=$guiObj->getInventory($item_id,$original_rate, $shop_id, $company_id, $is_commonStock, $stockType, false, $is_expire);
                    if(count($inventoryData) > 0)
                    {
                        if($qty > $inventoryData[0]["TotalCurrentQty"])
                        {
                            if($is_minus==1)
                            {
                                $inventoryData=$guiObj->getInventory($item_id,$original_rate, $shop_id, $company_id, $is_commonStock, $stockType, true, $is_expire);
                                if(count($inventoryData) > 0)
                                {
                                    $inventory=$guiObj->selectInventory($item_id, $original_rate, $shop_id, $company_id, $is_commonStock, $stockType, true);
                                    $total_consumption = $qty;
                                    foreach($inventory AS $row)
                                    {
                                        if($total_consumption > 0)
                                        {
                                            $currentQty=$row["CurrentQty"];
                                            $INID=$row["INID"];
                                            if($currentQty <= $total_consumption)
                                            {
                                                $total_consumption = $total_consumption - $currentQty;
                                                $update_sql = "UPDATE inventory SET CurrentQty = 0, BillQty=(BillQty+$currentQty) WHERE INID = $INID";
                                                $status=1;
                                                $batchNo=$guiObj->selectBatch($INID);
                                                $inventoryConsumtion=$guiObj->inventory_consumption($invoice_IHID,$status,$INID,$original_rate,$rate,$batchNo,$item_id,$currentQty,$shop_id);
                                            }
                                            else
                                            {
                                                $remaining_quantity = $currentQty - $total_consumption;
                                                $update_sql = "UPDATE inventory SET CurrentQty = '$remaining_quantity', BillQty=(BillQty+$total_consumption) WHERE INID = $INID";
                                                $status=1;
                                                $batchNo=$guiObj->selectBatch($INID);
                                                $inventoryConsumtion=$guiObj->inventory_consumption($invoice_IHID,$status,$INID,$original_rate,$rate,$batchNo,$item_id,$total_consumption,$shop_id);
                                                $total_consumption = 0;
                                            }
                                            $update = $dbObj->executeTransaction($update_sql);
                                        }
                                    }
                                    if($total_consumption > 0)
                                    {
                                        $inventory=$guiObj->selectInventory(product_id: $item_id, price: $original_rate, shop_id: $shop_id, company_id: $company_id, is_commonStock: $is_commonStock, stockType: $stockType,is_default: false);
                                        foreach($inventory AS $row)
                                        {
                                            if($total_consumption > 0)
                                            {
                                                $currentQty=$row["CurrentQty"];
                                                $INID=$row["INID"];
                                                if($currentQty <= $total_consumption)
                                                {
                                                    $total_consumption = $total_consumption - $currentQty;
                                                    $update_sql = "UPDATE inventory SET CurrentQty = 0, BillQty=(BillQty+$currentQty) WHERE INID = $INID";
                                                    $status=1;
                                                    $batchNo=$guiObj->selectBatch($INID);
                                                    $inventoryConsumtion=$guiObj->inventory_consumption($invoice_IHID,$status,$INID,$original_rate,$rate,$batchNo,$item_id,$currentQty,$shop_id);
                                                }
                                                else
                                                {
                                                    $remaining_quantity = $currentQty - $total_consumption;
                                                    $update_sql = "UPDATE inventory SET CurrentQty = '$remaining_quantity', BillQty=(BillQty+$total_consumption) WHERE INID = $INID";
                                                    $status=1;
                                                    $batchNo=$guiObj->selectBatch($INID);
                                                    $inventoryConsumtion=$guiObj->inventory_consumption($invoice_IHID,$status,$INID,$original_rate,$rate,$batchNo,$item_id,$total_consumption,$shop_id);
                                                    $total_consumption = 0;
                                                }
                                                $update = $dbObj->executeTransaction($update_sql);
                                            }
                                        }
                                    }
                                    if($total_consumption > 0)
                                    {
                                        $defaultInv=$guiObj->selectInventory($item_id, $original_rate, $shop_id, $company_id, $is_commonStock, $stockType, true);
                                        if(count($defaultInv) > 0)
                                        {
                                            $defINID=$defaultInv[0]["INID"];
                                            $update_sql = "UPDATE inventory SET CurrentQty = CurrentQty - $total_consumption, BillQty=(BillQty+$total_consumption) WHERE INID = $defINID";
                                            $dbObj->executeTransaction($update_sql);
                                            $batchNo=$guiObj->selectBatch($defINID);
                                            $guiObj->inventory_consumption($invoice_IHID,1,$defINID,$original_rate,$rate,$batchNo,$item_id,$total_consumption,$shop_id);
                                        }
                                        $total_consumption = 0;
                                    }
                                    $inventoryDetails=$guiObj->setInvoiceDetails(sqllQty: $qty,unitPrice: $rate,sellAmount: $original_total,discount: $discount,totalDiscount: $discount,total: $totals,invoiceHeader: $invoice_IHID,pro_id: $item_id,discountType: $discountType,prodDes: $prodDes,shop_id: $shop_id, Item_Name: $Item_name);
                                }
                                else
                                {
                                    // no stock
                                }
                            }
                            else
                            {
                                //Quantity Greater Than Available
                            }
                        }
                        else
                        {
                            $inventory=$guiObj->selectInventory($item_id, $original_rate, $shop_id, $company_id, $is_commonStock, $stockType);
                            
                            $total_consumption=$qty;
                            foreach($inventory AS $row)
                            {
                                if($total_consumption > 0)
                                {
                                    $currentQty=$row["CurrentQty"];
                                    $INID=$row["INID"];
                                    if($currentQty <= $total_consumption)
                                    {
                                        $total_consumption = $total_consumption - $currentQty;
                                        $update_sql = "UPDATE inventory SET CurrentQty = 0, BillQty=(BillQty+$currentQty) WHERE INID = $INID";
                                        $status=1;
                                        $batchNo=$guiObj->selectBatch($INID);
                                        $inventoryConsumtion=$guiObj->inventory_consumption($invoice_IHID,$status,$INID,$original_rate,$rate,$batchNo,$item_id,$currentQty,$shop_id);
                                    }
                                    else
                                    {
                                        // Partially reduce this entry and set remaining quantity to 0
                                        $remaining_quantity = $currentQty - $total_consumption;
                                        $update_sql = "UPDATE inventory SET CurrentQty = '$remaining_quantity', BillQty=(BillQty+$total_consumption) WHERE INID = $INID";
                                        $status=1;
                                        $batchNo=$guiObj->selectBatch($INID);
                                        $inventoryConsumtion=$guiObj->inventory_consumption($invoice_IHID,$status,$INID,$original_rate,$rate,$batchNo,$item_id,$total_consumption,$shop_id);
                                        $total_consumption = 0;
                                    }
                                    $update = $dbObj->executeTransaction($update_sql);
                                }
                            } 
                            $inventoryDetails=$guiObj->setInvoiceDetails(sqllQty: $qty,unitPrice: $rate,sellAmount: $original_total,discount: $discount,totalDiscount: $discount,total: $totals,invoiceHeader: $invoice_IHID,pro_id: $item_id,discountType: $discountType,prodDes: $prodDes,shop_id: $shop_id, Item_Name: $Item_name);                       
                        }
                    }
                    else 
                    {
                        if($is_minus==1)
                        {
                            $inventoryData=$guiObj->getInventory($item_id,$original_rate, $shop_id, $company_id, $is_commonStock, $stockType, true, $is_expire);
                            if(count($inventoryData) > 0)
                            {
                                $inventory=$guiObj->selectInventory($item_id, $original_rate, $shop_id, $company_id, $is_commonStock, $stockType,true);
                                $total_consumption=$qty;
                                foreach($inventory AS $invRow)
                                {
                                    if($total_consumption > 0)
                                    {
                                        $currentQty=$invRow["CurrentQty"];
                                        $INID=$invRow["INID"];
                                        if($currentQty <= $total_consumption)
                                        {
                                            $total_consumption = $total_consumption - $currentQty;
                                            $update_sql = "UPDATE inventory SET CurrentQty = 0, BillQty=(BillQty+$currentQty) WHERE INID = $INID";
                                            $status=1;
                                            $batchNo=$guiObj->selectBatch($INID);
                                            $inventoryConsumtion=$guiObj->inventory_consumption($invoice_IHID,$status,$INID,$original_rate,$rate,$batchNo,$item_id,$currentQty,$shop_id);
                                        }
                                        else
                                        {
                                            $remaining_quantity = $currentQty - $total_consumption;
                                            $update_sql = "UPDATE inventory SET CurrentQty = '$remaining_quantity', BillQty=(BillQty+$total_consumption) WHERE INID = $INID";
                                            $status=1;
                                            $batchNo=$guiObj->selectBatch($INID);
                                            $inventoryConsumtion=$guiObj->inventory_consumption($invoice_IHID,$status,$INID,$original_rate,$rate,$batchNo,$item_id,$total_consumption,$shop_id);
                                            $total_consumption = 0;
                                        }
                                        $update = $dbObj->executeTransaction($update_sql);
                                    }
                                }
                                if($total_consumption > 0)
                                {
                                    $inventory=$guiObj->selectInventory(product_id: $item_id, price: $original_rate, shop_id: $shop_id, company_id: $company_id, is_commonStock: $is_commonStock, stockType: $stockType,is_default: false);
                                    foreach($inventory AS $invRow)
                                    {
                                        if($total_consumption > 0)
                                        {
                                            $currentQty=$invRow["CurrentQty"];
                                            $INID=$invRow["INID"];
                                            if($currentQty <= $total_consumption)
                                            {
                                                $total_consumption = $total_consumption - $currentQty;
                                                $update_sql = "UPDATE inventory SET CurrentQty = 0, BillQty=(BillQty+$currentQty) WHERE INID = $INID";
                                                $status=1;
                                                $batchNo=$guiObj->selectBatch($INID);
                                                $inventoryConsumtion=$guiObj->inventory_consumption($invoice_IHID,$status,$INID,$original_rate,$rate,$batchNo,$item_id,$currentQty,$shop_id);
                                            }
                                            else
                                            {
                                                $remaining_quantity = $currentQty - $total_consumption;
                                                $update_sql = "UPDATE inventory SET CurrentQty = '$remaining_quantity', BillQty=(BillQty+$total_consumption) WHERE INID = $INID";
                                                $status=1;
                                                $batchNo=$guiObj->selectBatch($INID);
                                                $inventoryConsumtion=$guiObj->inventory_consumption($invoice_IHID,$status,$INID,$original_rate,$rate,$batchNo,$item_id,$total_consumption,$shop_id);
                                                $total_consumption = 0;
                                            }
                                            $update = $dbObj->executeTransaction($update_sql);
                                        }
                                    }
                                }
                                if($total_consumption > 0)
                                {
                                    $defaultInv=$guiObj->selectInventory($item_id, $original_rate, $shop_id, $company_id, $is_commonStock, $stockType, true);
                                    if(count($defaultInv) > 0)
                                    {
                                        $defINID=$defaultInv[0]["INID"];
                                        $update_sql = "UPDATE inventory SET CurrentQty = CurrentQty - $total_consumption, BillQty=(BillQty+$total_consumption) WHERE INID = $defINID";
                                        $dbObj->executeTransaction($update_sql);
                                        $batchNo=$guiObj->selectBatch($defINID);
                                        $guiObj->inventory_consumption($invoice_IHID,1,$defINID,$original_rate,$rate,$batchNo,$item_id,$total_consumption,$shop_id);
                                    }
                                    $total_consumption = 0;
                                }
                                $inventoryDetails=$guiObj->setInvoiceDetails(sqllQty: $qty,unitPrice: $rate,sellAmount: $original_total,discount: $discount,totalDiscount: $discount,total: $totals,invoiceHeader: $invoice_IHID,pro_id: $item_id,discountType: $discountType,prodDes: $prodDes,shop_id: $shop_id, Item_Name: $Item_name);
                            }
                            else
                            {
                                // No Stock
                            }
                        }
                        else
                        {
                            // No Stock
                        }
                        
                    }
                }  
                else
                {
                    $inventoryDetails=$guiObj->setInvoiceDetails(sqllQty: $qty,unitPrice: $rate,sellAmount: $original_total,discount: $discount,totalDiscount: $discount,total: $totals,invoiceHeader: $invoice_IHID,pro_id: $item_id,discountType: $discountType,prodDes: $prodDes,shop_id: $shop_id, Item_Name: $Item_name,ItemType:2);
                }              
            }

            $totalAmountPaid=0;
            for ($i=0; $i <count($_POST["paymentType"]) ; $i++) 
            { 
                $paymethod_PMID=$_POST["paymentType"][$i];
                $TransferAmount=$_POST["Amount"][$i];
                $totalAmountPaid+=$TransferAmount;
                $sql="INSERT INTO `transactions`(`TransferAmount`, `paymethod_PMID`, `InvoiceHeader_IHID`, `returnheader_id`) VALUES ('$TransferAmount','$paymethod_PMID','$invoice_IHID','')";
                if($dbObj->executeTransaction($sql)==true)
                {

                }
                else
                {
                    $alert["Error"][]="Transaction Cannot be Added "."<br> Error Code: #Inv-0008";
                }
            }
            if($NetAmount > $totalAmountPaid)
            {
                $amount=$NetAmount-$totalAmountPaid;
                $payment=$_POST["paymentType"][0];
                $cust=$guiObj->setCreditCust($EffectiveDate,$amount,$EffectiveDate,$invoice_IHID,$payment,$customers_CTID,$user_USID,1);
                $alert["Success"][]="Credit Customer Created";
                
            }

            //update customer payment and balances
            $Balance = $totalAmountPaid - $NetAmount;

            $sql="UPDATE invoiceheader SET CustPayment = '$totalAmountPaid', CustBalance = '$Balance' WHERE IHID = '$invoice_IHID' AND shop_SHID = '$shop_id'";
            if($dbObj->executeTransaction($sql)==true)
            {

            }
            else
            {
                $alert["Error"][]="Customer payment cannot be Added "."<br> Error Code: #Inv-0008";
            }



            $remarks="Invoice Created";
            $invoice_remark=$wholesale->invoice_remark(remarks: $remarks,user: $user_USID,invoice_header_id: $invoice_IHID,from_invoice: 0);
            if(isset($_POST["internalRemark"]))
            {
                $remarks=$_POST["internalRemark"];
                $invoice_remark=$wholesale->invoice_remark(remarks: $remarks,user: $user_USID,invoice_header_id: $invoice_IHID,from_invoice:1);
            }
            if($invoice_remark==true)
            {
                
            }
            if($invoice_header==true)
            {
                $alert["Success"][]="Invoice Created Successfully";
                if(isset($_GET["btn_submit_invoice"]))
                {
                    $alert["invoiceID"][]=$invoice_IHID;
                }
            }
            else
            {
                $alert["Error"][]="Oops, Something Went Wrong. Error Code: #Inv-0001";

            }
        }      
        // else
        // {
        //     $alert["Error"][]="Invoice Creation Failed. Error Code: #Inv-0002"; 
        // }
    }
    else
    {
        $alert["Error"][]="Cart Cannot Be Empty"."<br> Error Code: #Inv-0011";
        // return false;
    } 
    if (!empty($alert)) {
        header('Content-Type: application/json');
        echo json_encode($alert);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON Encoding Error: " . json_last_error_msg());
        }
    }
    
}

?>
