<?php 

// session_start();
include "../Includes/includes.php";
$shop_id = $_SESSION['shop_id'];
$salesreturn=new Sales_return_class;
$dbObj =New DBTransactions();
$invObj =New Inventory();
$priceObj =New PriceHistory();
$wholesale=new wholesale_invoice();
$shopObj= new Shop();
if(isset($_POST["submit"]))
{
    if(empty($_POST["invoiceNo"]))
    {
        $_SESSION["salesreturn_update"] = 5;
       header("Location:../Public/sales-return.php");
    }
    else
    {
        $invoiceNo=$_POST["invoiceNo"];
        $shop_id = $_SESSION['shop_id'];
        $dbObj =New DBTransactions();
        //get company stat
        $sql = "SELECT * FROM shop
        INNER JOIN company ON company.CMID = shop.Company_CMID
        WHERE SHID = ".$shop_id.";";

        $shopData = $dbObj->getData($sql);
        $multi_category = floatval($shopData[0]['is_multicategory']);
        $company_id = floatval($shopData[0]['CMID']);
        $return=$salesreturn->getinvoicebyinvoiceno($invoiceNo,$shop_id,$multi_category,$company_id);
        // echo $return;
        if(count($return)== 0)
        {
            $_SESSION["salesreturn_update"] = 0;
           header("Location:../Public/sales-return.php");
        }
        else
        {
            $invoiceID= $return[0]["IHID"];
            $return_check=$salesreturn->check_return($invoiceID);
            if(count($return_check)>0)
            {
                $_SESSION["salesreturn_update"] = 1;                
               header("Location:../Public/sales-return.php");
            }
            else
            {  
               header("Location:../Public/sales_return.php?invoice=$invoiceID");
            }
            
        }
    }
}
elseif(isset($_GET["submitForm"]))
{
    $alert=[];
    $allow=true;
    $effective_date=date("Y-m-d");
    $invoiceID="";
    $return_count = 0;
    if(!isset($_POST["customer"]) || $_POST["customer"]=="")
    {
        $alert["Error"][]="No Customer Selected <br> Error Code: #SRT-0001";
        $allow=false;
    }
    else
    {
        $customer=$_POST["customer"];
    }
    if(!isset($_POST["shop_id"]) || $_POST["shop_id"]=="")
    {
        $alert["Error"][]="No Shop Selected <br> Error Code: #SRT-0002";
        $allow=false;
    }
    else
    {
        $shop_id=$_POST["shop_id"];
    }
    if(!isset($_POST["return-type"]) || $_POST["return-type"]=="")
    {
        $alert["Error"][]="No Shop Selected <br> Error Code: #SRT-0003";
        $allow=false;
    }
    else
    {
        $return_type=$_POST["return-type"];
        if($return_type==3 && !isset($_POST["search-invoices"]))
        {
            $alert["Error"][]="No Invoice Selected <br> Error Code: #SRT-0004";
            $allow=false;
        }
        else
        {
            $invoiceID=$_POST["search-invoices"];
        }
    }
    if(isset($_POST["with-invoice"]) && $_POST["with-invoice"]!="" &&  !isset($_POST["search-invoices"]) && $_POST["search-invoices"]=="")
    {
        $alert["Error"][]="No Invoice Selected <br> Error Code: #SRT-0005";
        $allow=false;
    }
    else if(!isset($_POST["with-invoice"]) &&  !isset($_POST["search-invoices"]) && $_POST["search-invoices"] =="")
    {
        $invoiceID="";
    }
    else
    {
        $invoiceID=$_POST["search-invoices"];
    }
    if(!isset($_POST["remarks"]) && $_POST["remarks"]=="")
    {
        $alert["Error"][]="Reason Is Mandatory <br> Error Code: #SRT-0006";
        $allow=false;
    }
    else
    {
        $remarks=$_POST["remarks"];
    }
    if(!isset($_POST["item_id"]) || count($_POST["item_id"])==0)
    {
        $alert["Error"][]="Cart Cannot Be Empty <br> Error Code: #SRT-0007";
        $allow=false;
    }
    if(isset($_POST["customer"]) && isset($_POST["return-type"]))
    {
        if($_POST["customer"]==1 && $_POST["return-type"]==3)
        {
            $alert["Error"][]="Common Customer Cannot Have Credit Reduction <br> Error Code: #SRT-0009";
            $allow=false;
        }
    }
    if(isset($_POST["item_id"]))
    {
        for ($i=0; $i < count($_POST["item_id"]) ; $i++) 
        { 
            $item_id=$_POST["item_id"][$i];
            $inventory=$salesreturn->GetLatestInventory($shop_id,$item_id);
            if(count($inventory)==0)
            {
                $alert["Error"][]="No Inventory Found <br> Error Code: #SRT-0010";
                $allow=false;
            }
            $return_count = $return_count +1;
        }
    }
    if($allow==true)
    {
        $InvoiceNo="";
        $IHID="";
        if(isset($_POST["search-invoices"]) && $_POST["search-invoices"]!="")
        {
            $invoiceData=$salesreturn->invoiceID($_POST["search-invoices"]);
            $InvoiceNo=$invoiceData[0]["BillNo"];
            $IHID=$_POST["search-invoices"];
        }
        $returnby=$_SESSION["user_id"];
        $return_no=$salesreturn->max_return_invoice_header($shop_id);
        $return_no=$return_no[0]["maxId"] + 1;
        $return_no=$salesreturn->getSequence($return_no);
        $salesetings=$salesreturn->getSaleSettings($shop_id);
        if(count($salesetings)>0)
        {
            if(isset($salesetings[0]["billNoHeader"]))
            {
                $return_no="#R".$salesetings[0]["billNoHeader"]."-".$return_no;
            }
            else
            {
                $return_no="#RINV-".$return_no;
            }
        }
        else
        {
            $return_no="#RINV-".$return_no;
        }
        if($shopObj->hascounter($shop_id)==1)
        {
            $sql = "SELECT * FROM cashcounter WHERE user_USID = ".$returnby." AND shop_SHID=".$shop_id." AND CounterStat = 1;";
            $counterData = $dbObj->getData($sql);
            if(count($counterData) > 0)
            {
               $CashCounter_CCID = $counterData[0]['CCID']; 
            }
            else
            {
                $CashCounter_CCID = 0;
            }
            
        }
        else
        {
            $CashCounter_CCID = 0;
        }
        $return_amount=$_POST["netamount"];
        $return_discount=$_POST["totalDiscount"];
        $return_gross_amount=$_POST["grossTotal"];
        $customer=$_POST["customer"];
        $return_header=$salesreturn->insert_return_invoice_header(reason: $remarks,return_type: $return_type,InvoiceNo: $InvoiceNo,EffectiveDate: $effective_date,InvStartTime: $effective_date,returnby: $returnby,IHID: $IHID,shop_id: $shop_id,return_no: $return_no,return_amount: $return_amount,return_discount: $return_discount,return_gross_amount: $return_gross_amount,return_count: $return_count,Customer_CTID: $customer,CashCounter_CCID:$CashCounter_CCID);
        $returnID=$salesreturn->max_return_invoice_header($shop_id);
        $ReturnHeader_RHID=$returnID[0]["maxId"];
        if($return_header==true)
        {
            // $alert["Success"][]="Sales Return Created Successfully <br> Return ID: ".$returnID;
            $alert["Success"][]="Sales Return Created Successfully";
            // $alert["Success"][]="netamount: ".$return_amount;
            // $alert["Success"][]="grossTotal: ".$return_gross_amount;
            $allows=true;
        }
        else
        {
            $alert["Error"][]="Sales Return Cannot Be Created <br> Error Code: #SRT-0011";
            $allows=false;
        }
        if($allows==true)
        {
            if(isset($_POST["item_id"]))
            {
                for ($i=0; $i < count($_POST["item_id"]) ; $i++) 
                { 
                    $item_id=$_POST["item_id"][$i];
                    $inventory=$salesreturn->GetLatestInventory($shop_id,$item_id);
                    if(count($inventory)==0)
                    {
                        $alert["Error"][]="No Inventory Found <br> Error Code: #SRT-0010";
                        $allow=false;
                    }
                    else
                    {
                        $ReturnQty=$_POST["qty"][$i];
                        $ReturnAmount=$_POST["totals"][$i];
                        $products_PDID=$_POST["item_id"][$i];
                        $return_unit_price=$_POST["rate"][$i];
                        $discountType=$_POST["discountType"][$i];
                        $return_discount=0;
                        if($discountType==1)
                        {
                            $tot=$return_unit_price * $ReturnQty;
                            $return_discount=$tot * $_POST["discount"][$i] / 100;
                        }
                        else
                        {
                            $return_discount=$_POST["discount"][$i] * $ReturnQty;

                        }
                        $InvoiceDetails_IDID="";
                        $batch_id=$inventory[0]["BatchID"];
                        $inventory_INID=$inventory[0]["INID"];
                        $returnDetails=$salesreturn->insert_return_invoice_details($ReturnQty,$ReturnAmount,$ReturnHeader_RHID,$InvoiceDetails_IDID,$products_PDID,$batch_id,$inventory_INID,$return_unit_price,$return_discount,$discountType);
                        $inventoryUpdate=$salesreturn->updateInventory($ReturnQty
                        ,$inventory_INID);
                    }
                }
            }
            if($return_type ==1 || $return_type ==3)
            {
                $salesreturn->editReturnHeaderStat(1,$ReturnHeader_RHID);
            }
            if($return_type ==3)
            {
                $returnby=$_SESSION["user_id"];
                $cust=$wholesale->setCreditCustDebit($effective_date,$return_amount,$effective_date,$IHID,1,$customer,$returnby,5);

            }
            $alert["returnID"][]=$ReturnHeader_RHID;
        }
    }
    if($allow==false)
    {
        $alert["Error"][]="Sales Return Cannot Be Created <br> Error Code: #SRT-0008";
    }
    if(!empty($alert))
    echo json_encode($alert);
}
elseif(isset($_POST["submitbarcode"]))
{
    if(!isset($_POST["barcode"]))
    {
        $_SESSION["salesreturn_update"] = 5;
       header("Location:../Public/sales-return.php");
    }
    else
    {
        $barcode=$_POST["barcode"];
        $check_barcode=$salesreturn->check_barcode($barcode);
        if(count($check_barcode)==0)
        {
            $_SESSION["salesreturn_update"] = 4;
           header("Location:../Public/sales-return.php");
        }
        else
        {
           $check_barcode_sale=$salesreturn->check_barcode_sale($barcode);
           if(count($check_barcode_sale)==0)
           {
                $_SESSION["salesreturn_update"] = 6;
               header("Location:../Public/sales-return.php");
           }
           else
           {
                $product_id= $check_barcode[0]["PDID"];

               header("Location:../Public/sales-return-product.php?product_id=".$product_id);
           }
        }

    }
}
elseif (isset($_POST["sales_return_product"])) 
{
    if(!isset($_POST["p_id"]))
    {
        $_SESSION["salesreturn_update"] = 5;
       header("Location:../Public/sales-return-product.php?product_id=".$_POST["p_id"]);
    }
    else
    {
        $return_count = 0;
        for ($i=0; $i < count($_POST["returnqty"]) ; $i++) 
        { 
            $return_count = $return_count + (int)$_POST["returnqty"][ $i ];
        }
        $reason=$_POST["reason"];
        $return_type=$_POST["returntype"];
        $InvoiceNo="";
        $EffectiveDate=date("Y-m-d");
        $InvStartTime=date("Y-m-d H:i:s");
        $count=$salesreturn->count_reqturn();
        $count=$count[0]["invoice_count"]+1;
        $count=$salesreturn->getSequence($count);
        $return_no="IRT_".$count;
        $returnby=$_SESSION["user_id"];
        $IHID="";
        $return_amount=$_POST["total_return"];
        $return_gross_amount=$_POST["total_return"];
        $usability=$_POST["usability"];
        $return_discount="";
        $return_invoice_header=$salesreturn->insert_return_invoice_header($reason,$return_type,$InvoiceNo,$EffectiveDate,$InvStartTime,$returnby,$IHID,$shop_id,$return_no,$return_amount,$return_discount,$return_gross_amount,$return_count,$usability);
        $ReturnHeader_RHID = $salesreturn->max_return_invoice_header($shop_id);
        $ReturnHeader_RHID = $ReturnHeader_RHID[0]["maxId"];
        echo $ReturnHeader_RHID;
        for ($i=0; $i < count($_POST["check"]); $i++) 
        { 
            $id=$_POST["batch_id"][$i];
            $inventory=$salesreturn->getBatchwithpricehistory($id);
            $return_unit_price=$_POST["rate"][$i];
            $products_PDID=$_POST["product_id"][$i];
            $BillQty=$_POST["BillQty"][$i];
            $returnqty=$_POST["returnqty"][$i];
            $item_total=$_POST["item_total"][$i];
            $inventory_INID=$inventory[0]["INID"];
            $batch_id=$inventory[0]["pro_batch"];
            $newreturn=(int)$inventory[0]["ReturnQty"]+$returnqty;
            $newCurrentQty=(int)$inventory[0]["CurrentQty"]+$returnqty;
            $InvoiceDetails_IDID="";
            $update=$salesreturn->update_inventory($newreturn,$newCurrentQty,$inventory_INID);
            $insert_return_invoice_details=$salesreturn->insert_return_invoice_details($returnqty,$item_total,$ReturnHeader_RHID,$InvoiceDetails_IDID,$products_PDID,$batch_id,$inventory_INID,$return_unit_price,$return_discount);
        }
        $_SESSION["returnid"]=$ReturnHeader_RHID;
        $_SESSION["salesreturn_update"] = 2;
       header("Location:../Receipts/sales-return-credit-note.php?invoice_id=".$_SESSION["returnid"]);
    }
}
elseif (isset($_POST["sales_return"]) || isset($_POST["sales_returni"])) 
{
    if(!isset($_POST["customer_name"]) || !isset($_POST["customer_id"]) || !isset($_POST["invoice_id"]) || !isset($_POST["IDate"]) || !isset($_POST["product_name"]) || !isset($_POST["SellQty"]) || !isset($_POST["SellAmount"]) || !isset($_POST["SellDiscount"]) || !isset($_POST["SoldAmount"]) || !isset($_POST["productID"]) || !isset($_POST["invoicedetailsid"]) || !isset($_POST["returnqty"]) || !isset($_POST["item_total"]) || !isset($_POST["item_discount"]) || !isset($_POST["item_total_amount"]) || !isset($_POST["check"]) || !isset($_POST["reason"]) || !isset($_POST["usability"]) || !isset($_POST["total_return"]) || !isset($_POST["total_discount"]) || !isset($_POST["total_amount"]) || !isset($_POST["returntype"]) || !isset($_POST["invoice_No"]))
    {
        $_SESSION["salesreturn_update"] = 5;
       header("Location:../Public/sales_return.php?invoice=".$_POST["invoice_id"]);
    }
    else
    {
        $return_count = 0;
        for ($i=0; $i < count($_POST["returnqty"]) ; $i++) 
        { 
            $return_count = $return_count + (int)$_POST["returnqty"][ $i ];
        }
        $salesreturn=new Sales_return_class;
        $IHID=$_POST["invoice_id"];
        $InvoiceNo=$_POST["invoice_No"];
        $EffectiveDate=date("Y-m-d");
        $InvStartTime=date("Y-m-d H:i:s");
        $reason=$_POST["reason"];
        $return_type=$_POST["returntype"];
        $usability=$_POST["usability"];
        $return_amount=$_POST["total_return"];
        $return_discount=$_POST["total_discount"];
        $return_gross_amount=$_POST["total_amount"];
        $returntype=$_POST["returntype"];

        $count=$salesreturn->count_reqturn();
        $count=$count[0]["invoice_count"]+1;
        $count=$salesreturn->getSequence($count);
        $return_no="IRT_".$count;
        $returnby=$_SESSION["user_id"];
        $invoiceHeader=$salesreturn->SelectInvoiceHeader($IHID);
        $insert=$salesreturn->insert_return_invoice_header($reason,$return_type,$InvoiceNo,$EffectiveDate,$InvStartTime,$returnby,$IHID,$shop_id,$return_no,$return_amount,$return_discount,$return_gross_amount,$return_count,$usability);
        $ReturnHeader_RHID = $salesreturn->max_return_invoice_header($shop_id);
        $ReturnHeader_RHID = $ReturnHeader_RHID[0]["maxId"];
        echo $ReturnHeader_RHID;

        for ($i=0; $i < count($_POST["check"]); $i++) 
        { 
            if($invoiceHeader[0]["shop_SHID"]==$shop_id)
            {
                $products_PDID=$_POST["productID"][$i];
                $batch_id=$_POST["batchid"][$i];
                echo "Product_id ".$products_PDID."<br>";
                echo "batch_id ".$batch_id."<br>";
                $inventory=$salesreturn->select_inventory_with_batchid_product_id($batch_id,$products_PDID,$shop_id);
                print_r($inventory);
                $check=$_POST["check"][$i];
                $ReturnQty=$_POST["returnqty"][$i];
                $ReturnAmount=$_POST["item_total"][$i];
                $InvoiceDetails_IDID=$_POST["check"][$i];
                $return_unit_price=$_POST["UnitPrice"][$i];
                $return_discount=$_POST["SellDiscount"][$i];
                $newreturn=(int)$inventory[0]["ReturnQty"]+(int)$ReturnQty;
                $newCurrentQty=(int)$inventory[0]["CurrentQty"]+(int)$ReturnQty;
                $inventory_INID=$inventory[0]["INID"];
                $update=$salesreturn->update_inventory($newreturn,$newCurrentQty,$inventory_INID);
                $insert_return_invoice_details=$salesreturn->insert_return_invoice_details($ReturnQty,$ReturnAmount,$ReturnHeader_RHID,$InvoiceDetails_IDID,$products_PDID,$batch_id,$inventory_INID,$return_unit_price,$return_discount);
            }
            else
            {
                $products_PDID=$_POST["productID"][$i];
                $inventory=$salesreturn->select_inventory_last_batch_product_id($products_PDID,$shop_id);
                if(count($inventory)>0)
                {
                    $check=$_POST["check"][$i];
                    $ReturnQty=$_POST["returnqty"][$i];
                    $ReturnAmount=$_POST["item_total"][$i];
                    $InvoiceDetails_IDID=$_POST["check"][$i];
                    $return_unit_price=$_POST["UnitPrice"][$i];
                    $return_discount=$_POST["SellDiscount"][$i];
                    $newreturn=(int)$inventory[0]["ReturnQty"]+(int)$ReturnQty;
                    $newCurrentQty=(int)$inventory[0]["CurrentQty"]+(int)$ReturnQty;
                    $inventory_INID=$inventory[0]["INID"];
                    $batch_id=$inventory[0]["BatchID"];
                    $update=$salesreturn->update_inventory($newreturn,$newCurrentQty,$inventory_INID);
                    $insert_return_invoice_details=$salesreturn->insert_return_invoice_details($ReturnQty,$ReturnAmount,$ReturnHeader_RHID,$InvoiceDetails_IDID,$products_PDID,$batch_id,$inventory_INID,$return_unit_price,$return_discount);
                }
                else
                {
                    $count=$invObj->getInventorywithproductIDshop($products_PDID,$shop_id);
                    $count=$count[0]["procount"];
                    $count=$count+1;
                    $batch_id=$invObj->getSequence($count);
                    $batch_id="B".$batch_id;
                    $batch_ids=$_POST["batchid"][$i];
                    $CurrentQty=0;
                    $BillQty=0;
                    $ReturnQty=0;
                    $TransfeInQty=0;
                    $TransferOutQty=0;
                    $RackID=1;
                    $default=1;
                    $invObj->setInventory2($CurrentQty, $BillQty, $ReturnQty, $TransfeInQty, $TransferOutQty, $products_PDID, $shop_id, $RackID, $batch_id,$default);
                    $inventory=$salesreturn->select_inventory_with_batchid_product_id($batch_ids,$products_PDID,shop_id: $invoiceHeader[0]["shop_SHID"]);
                    $priceHistory=$salesreturn->pricehistorywithINID($inventory[0]["INID"]);
                    $effective_date=date("Y-m-d");
                    $prod_purchase_price = $priceHistory[0]["PurchasePrice"];
                    $prod_selling_price = $priceHistory[0]["SellingPrice"];
                    $mnf_date = $priceHistory[0]["MnfDate"];
                    $exp_date = $priceHistory[0]["ExpDate"];
                    $grn_detail_id = 0;
                    $sql = "SELECT max(INID) AS MAXSID FROM inventory WHERE shop_SHID='$shop_id';";
                    $dbMax = $dbObj->getData($sql);
                    $max_id = floatval($dbMax[0]['MAXSID']);
                    $new_inventory_id = $max_id ;
                    $grn_detail_id = 0;
                    $priceObj->setPriceHistory($products_PDID, 0, $effective_date, $prod_purchase_price, $prod_selling_price, $prod_selling_price, $mnf_date, $exp_date, $batch_id, $new_inventory_id, $grn_detail_id);
                    $inventory=$salesreturn->select_inventory_last_batch_product_id($products_PDID,$shop_id);
                    $check=$_POST["check"][$i];
                    $ReturnQty=$_POST["returnqty"][$i];
                    $ReturnAmount=$_POST["item_total"][$i];
                    $InvoiceDetails_IDID=$_POST["check"][$i];
                    $return_unit_price=$_POST["UnitPrice"][$i];
                    $return_discount=$_POST["SellDiscount"][$i];
                    $newreturn=(int)$inventory[0]["ReturnQty"]+(int)$ReturnQty;
                    $newCurrentQty=(int)$inventory[0]["CurrentQty"]+(int)$ReturnQty;
                    $inventory_INID=$inventory[0]["INID"];
                    $update=$salesreturn->update_inventory($newreturn,$newCurrentQty,$inventory_INID);
                    $insert_return_invoice_details=$salesreturn->insert_return_invoice_details($ReturnQty,$ReturnAmount,$ReturnHeader_RHID,$InvoiceDetails_IDID,$products_PDID,$batch_id,$inventory_INID,$return_unit_price,$return_discount);
                }
            }
            
        }
        if(isset($_POST["actualItem"]))
        {
            for ($i=0; $i <count($_POST["actualItem"]) ; $i++) 
            { 
                $actualItem=$_POST["actualItem"][$i];
                $changeitem=$_POST["changeitem"][$i];
                $changeitemBatch=$_POST["changeitemBatch"][$i];
                $ReturnQty=$_POST["returnqty"][$i];
                echo "Change Batch".$changeitemBatch;
                $sql2="SELECT * FROM invoicedetails WHERE products_PDID='$actualItem' AND InvoiceHeader_IHID='$IHID'";
                $itemData2 = $dbObj->getData($sql2);
                $invoiceDetailID=$itemData2[0]["IDID"];
                $sql3="SELECT * FROM inventory WHERE INID='$changeitemBatch'";
                $itemData3 = $dbObj->getData($sql3);
                $newbatch=$itemData3[0]["BatchID"];
                $sql4="UPDATE invoicedetails SET products_PDID='$changeitem', batch_no='$newbatch' WHERE IDID='$invoiceDetailID'";
                $updateInvoiceDetail4=$dbObj->executeTransaction($sql4);
                $sql5="UPDATE inventory SET CurrentQty=CurrentQty-$ReturnQty,BillQty=BillQty+$ReturnQty WHERE INID='$changeitemBatch'";
                $updateInvoiceDetail5=$dbObj->executeTransaction($sql5);
            }
            
            $sql6="UPDATE retrun_invoice_header SET return_header_stat=1 WHERE RIHID='$ReturnHeader_RHID'";
            $updateInvoiceDetail5=$dbObj->executeTransaction($sql6);

        }

    }
    $_SESSION["returnid"]=$ReturnHeader_RHID;
    $_SESSION["salesreturn_update"] = 2;
    if(isset($_POST["sales_returni"]))
    {
        $_SESSION["sales_returni"]=1;
    }
   header("Location:../Public/sales-return-credit-note.php?invoice_id=".$_SESSION["returnid"]);
}
else
{    
    $_SESSION["salesreturn_update"] = 5;
   header("Location:../Public/sales-return.php");
}
?>