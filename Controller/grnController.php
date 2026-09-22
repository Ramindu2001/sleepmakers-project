<?php 
include "../Includes/includes.php";
$shop_id = $_SESSION['shop_id'];
$grnObj = new GRN();
$shop_SHID=$_SESSION['shop_id'];

if(isset($_POST['btn_add_grn']))
{
    //GHID, GRNHeaderNo, EffectiveDate, InvoiceNo, ItemCount, TotalPurchasePrice, TotalSellPrice, GRNStartTime, GRNEndTime, GRNStat, user_USID, shop_SHID, Suppliers_SPID
    $grnCount = $grnObj->getGRNCount($shop_id);
    $grn_count = intval($grnCount[0]['GRNCount']);
    $grn_count += 1;

    $commObj = new Common();
    $grn_no = $commObj->createCount("GRN", $grn_count);
    
    $invoice_no = $_POST['invoice_no'];

    $item_count = 0;
    $total_purchase_price = 0;
    $total_selling_price = 0;

    //get date
    date_default_timezone_set("Asia/Colombo");
    $effective_date = date("Y-m-d");

    $grn_start = date("Y-m-d h:m:s");
    $grn_end = date("Y-m-d h:m:s");
    $grn_stat = 0; //initialize as hold
    $user_id = $_SESSION['user_id'];
    if(isset($_POST['cmb_supplier']))
    {
        $supplier_id = $_POST['cmb_supplier'];
    }
    else
    {
        $supplier_id = 1;
    }
    if(isset($_POST['Reference']))
    {
        $Reference = $_POST['Reference'];
    }
    else
    {
        $Reference = "";
    }
    
    //check date
    if(!empty($effective_date))
    {
        //check grn invoice no
        if(!empty($invoice_no))
        {
            //create new GRN
            $grnObj->setGRNHeader($grn_no, $effective_date, $invoice_no, $item_count, $total_purchase_price, $total_selling_price, $grn_start, $grn_end, $grn_stat, $user_id, $shop_id, $supplier_id, 0, 0, 0,$Reference);

            $_SESSION['grnheader_update'] = 2;
            header("Location: ../Public/grn-details.php");
        }//has invoice no
        else
        {
            $_SESSION['grnheader_update'] = 1;
            header("Location: ../Public/grn-header.php");
            die("Error: No GRN invoice number.");
        }//no invoice no
    }//has date
    else
    {
        $_SESSION['grnheader_update'] = 0;
        header("Location: ../Public/grn-header.php");
        die("Error: No effective date.");
    }//no date
}//create new grn

//================================ GRN Details =================================//
if(isset($_POST['btn_pending_grn']))
{
    $grn_header_id = $_POST['hide_grnheader_id'];
    $grn_stat = 1;

    $SuppPayment =0;
    $SupBalance = 0; 
    $excessamount = 0;
    $supplier_id = $_POST['cmb_edit_supplier'];

    $SaleDiscount = 0;
    $TotalDiscount = 0;
    $DiscType = 0;

    $SaleDiscount = $_POST['hiddenSaleDiscount'];
    $TotalDiscount = $_POST['hiddenTotalDiscount'];
    $DiscType = $_POST['hiddenDiscountType'];

    // echo "SaleDiscount: ".$SaleDiscount."<br>";
    // echo "TotalDiscount: ".$TotalDiscount."<br>";       
    // echo "DiscType: ".$DiscType."<br>";

   $grnObj->grnEditSupplier($grn_header_id,$supplier_id);
    
    //update grn header stat to 1
    $grnObj->editGRNHeaderStat($grn_stat, $grn_header_id,$SuppPayment , $SupBalance , $excessamount, intval($DiscType), floatval($SaleDiscount), floatval($TotalDiscount));
    
   
    //update grn detail stat to 1
    $grnObj->editGRNDetailStat($grn_stat, $grn_header_id);

    header("Location: ../Public/grn-header.php");
}//pending grn

if(isset($_POST['btn_verify_grn']))
{
    // Increase execution time for large GRN verification (6000+ items)
    set_time_limit(600);
    ini_set('memory_limit', '1024M');
    
    $user_id = $_SESSION['user_id'];
    $grn_header_id = (int)$_POST['hide_grnheader_id'];
    $SupplierID = 0;

    //the whole GRN is booked in one database transaction - every line's stock, or none of it - and it is
    //claimed first: only a GRN still on hold / pending can be verified, so a double click or a refresh
    //can never add its stock twice
    $grnObj->beginDbTransaction();
    if($grnObj->editGRNHeaderStatIfIn(2, $grn_header_id, $shop_id, [0, 1]) == 0)
    {
        $grnObj->rollbackDbTransaction();
        $_SESSION['grnheader_update'] = 4;
        header("Location: ../Public/grn-header.php");
        exit;
    }//already verified / cancelled

    $sql = "SELECT GDID, PDID, Barcode, ItemName, VRID, VariationName, InitQty, UnitPurchasePrice, UnitLabelPrice, UnitSellPrice, MnfDate, ExpDate, SEID, SectionName, RKID, RackName, UNID, ShortName , Suppliers_SPID FROM grndetails
    INNER JOIN grnheader ON grnheader.GHID = grndetails.GRNHeader_GHID
    LEFT JOIN products ON products.PDID = grndetails.products_PDID
    LEFT JOIN variations ON variations.VRID = grndetails.VariationID
    LEFT JOIN units ON units.UNID = products.PurchaseUnit
    LEFT JOIN rack ON rack.RKID = grndetails.Rack_RKID
    LEFT JOIN sections ON sections.SEID = rack.Sections_SEID
    WHERE GRNHeader_GHID = ".$grn_header_id.";";
    $supplier_id = $_POST['cmb_edit_supplier'];
    $grnObj->grnEditSupplier($grn_header_id,$supplier_id);

    $invObj = new Inventory();
    $dbObj = new DBTransactions();
    $dbData = $dbObj->getData($sql);

    //get current date time
    date_default_timezone_set("Asia/Colombo");
    $effective_date = date("Y-m-d");

    $row_count = 0;
    $total_purchase_price = 0;
    $total_selling_price = 0;

    foreach($dbData as $row)
    {
        $grn_detail_id = $row['GDID'];
        $SupplierID  = $row['Suppliers_SPID'];      
        $product_id = $row['PDID'];        
        $current_qty = floatval($row['InitQty']);
        $rack_id = $row['RKID'];
        $variation_id = $row['VRID'];
        $purchase_price = floatval($row['UnitPurchasePrice']);
        $selling_price = floatval($row['UnitSellPrice']);
        $label_price = $row['UnitLabelPrice'];
        $mnf_date = $row['MnfDate'];
        $exp_date = $row['ExpDate'];

        $count=$invObj->getInventorywithproductID($product_id);
        $count=$count[0]["procount"];
        $count=$count+1;
        $batch_id=$invObj->getSequence($count);
        $batch_id="B".$batch_id;
        //totals
        $row_count += 1;

        $item_purchase_total = $purchase_price * $current_qty;
        $item_selling_total = $selling_price * $current_qty;

        $total_purchase_price += $item_purchase_total;
        $total_selling_price += $item_selling_total;

        $bill_qty = 0;
        $return_qty = 0;
        $transfer_in_qty = 0;
        $transfer_out_qty = 0;

        //insert the stock row and link the price history to the row that was really created
        //(a guessed max(INID)+1 points at the wrong row as soon as the ids have a gap)
        $sql = "INSERT INTO inventory(CurrentQty, BillQty, ReturnQty, TransferInQty, TransferOutQty, products_PDID, shop_SHID, RackID, BatchID) VALUES(?,?,?,?,?,?,?,?,?);";
        $new_inventory_id = $dbObj->executeTransactionAndReturnLastInsertID($sql, [$current_qty, $bill_qty, $return_qty, $transfer_in_qty, $transfer_out_qty, $product_id, $shop_id, $rack_id, $batch_id]);

        //add to price history table
        $priceObj = new PriceHistory();
        $priceObj->setPriceHistory($product_id, $variation_id, $effective_date, $purchase_price, $selling_price, $label_price, $mnf_date, $exp_date, $batch_id, $new_inventory_id, $grn_detail_id);

        //the product record shows the new prices (POS product tiles, product list, labels)
        $prodObj = new Product();
        $prodObj->setCurrentPrices($product_id, $purchase_price, $selling_price);

    }//foreach 

    $NetAmount =  $total_purchase_price;

    //Added by Imila on 2024/09/27
    $excessamount=0;
    $SuppPayment=0;

    if (isset($_POST['paid']) && is_array($_POST['paid'])) {
        for ($i = 0; $i < count($_POST['paid']); $i++) {
            $SuppPayment = $SuppPayment + (int)$_POST['paid'][$i];
        }
    } else {
        // Handle the case where 'paid' is not set or is not an array
        $SuppPayment = 0;
    }
    
    $balance=$SuppPayment-$NetAmount;

    if($balance > 0)
    {
        $SupBalance=$balance;
    }
    else
    {
        $SupBalance=0;        
    }

    //get the excess amount
    if($SuppPayment > $NetAmount)
    {
        $excessamount= $SuppPayment - $NetAmount;   
    }

    //a shop without payment methods posts no pay_id at all
    $payment = isset($_POST["pay_id"][0]) ? $_POST["pay_id"][0] : 0;
    
    if($excessamount > 0)
    {        
        //echo "excessamount".$_POST["excessamount"]."<br>";
        $cust=$grnObj->setCreditDebitSupplier($effective_date,$excessamount,$effective_date,$grn_header_id,$payment,$SupplierID,$user_id);                    
    }

    $grn_stat = 2;

    $SaleDiscount = 0;
    $TotalDiscount = 0;
    $DiscType = 0;

    $SaleDiscount = $_POST['hiddenSaleDiscount'];
    $TotalDiscount = $_POST['hiddenTotalDiscount'];
    $DiscType = $_POST['hiddenDiscountType'];

    // echo "SaleDiscount: ".$SaleDiscount."<br>";
    // echo "TotalDiscount: ".$TotalDiscount."<br>";       
    // echo "DiscType: ".$DiscType."<br>";

    // exit();
    $New_purchase_price = $total_purchase_price - $TotalDiscount;

    //update grn header stat and supplier payments --edited by Imila on 2024/09/27
    $grnObj->editGRNHeaderStat($grn_stat, $grn_header_id, $payment , $SupBalance , $excessamount, intval($DiscType), floatval($SaleDiscount), floatval($TotalDiscount));    
    //update grn detail stat to 2
    $grnObj->editGRNDetailStat($grn_stat, $grn_header_id);

    $grnObj->editGRNHeaderVerify($effective_date, $row_count, $total_purchase_price,$New_purchase_price, $total_selling_price, $grn_header_id);

    //Added by Imila on 2024-09-27
    //payments for the supplier
    $paid_rows = (isset($_POST["paid"]) && is_array($_POST["paid"])) ? $_POST["paid"] : array();
    for ($i=0; $i <count($paid_rows) ; $i++)
    {
        $TransferAmount=$paid_rows[$i];
        $paymethod_PMID = isset($_POST["pay_id"][$i]) ? $_POST["pay_id"][$i] : 0;
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
            //IF CREDIT
            $grnObj->setCreditDebitSupplier($effective_date,$TransferAmount,$effective_date,$grn_header_id,$paymethod_PMID,$SupplierID,$user_id);
        }
        
        $sql="INSERT INTO `suppliertransactions`(`TransferAmount`, `paymethod_PMID`, `GRNHeader_GHID`, `returnheader_id`) VALUES ('$TransferAmount','$paymethod_PMID','$grn_header_id','')";
        $dbObj->executeTransaction($sql);        
        
        if($paymethod_PMID==5 && !empty($_POST["chqNo"]))
        {
            for ($j=0; $j <count($_POST["chqNo"]) ; $j++) 
            { 
                $sql = "SELECT count(*) AS ChqNo FROM supcheq ";
                $wholesale=new wholesale_invoice();
                $dbObj = new DBTransactions();
                $dbData = $dbObj->getData($sql);
                $count = $dbData[0]["ChqNo"]+1;
                $chq_no="SCHQ-".$wholesale->getSequence($count);
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
                $EffectiveDate=date("Y-m-d");
                $sql="INSERT INTO `supcheq`(`type`, `chq_stat`, `chq_no`, `sup_SPID`, `effectiveDate`, `user_USID`, `shop_SHID`, `createdDate`,`GRNHeader_GHID`) VALUES ('2','1','$chq_no','$SupplierID','$EffectiveDate','$user_id','$shop_SHID','$date','$grn_header_id')";
                $dbObj->executeTransaction($sql);
                $sql="SELECT MAX(SCQID) AS SCQID FROM supcheq";
                $SCQID=$dbObj->getData($sql);
                $SCQID=$SCQID[0]["SCQID"];
                $sql="INSERT INTO `supchqdetail`(`bank`, `chqAmount`, `chqNo`, `chqDate`, `GRNHeader_GHID`, `SCQID`) VALUES ('$bank','$chqAmount','$chequeNo','$chqdate','$grn_header_id','$SCQID')";
                $dbObj->executeTransaction($sql);
            }
        }
        if($paymethod_PMID==13 && !empty($_POST["transferCheque"]))
        {
            for ($j=0; $j <count($_POST["transferCheque"]) ; $j++) 
            { 
                $sql = "SELECT count(*) AS ChqNo FROM supcheq ";
                $wholesale=new wholesale_invoice();
                $dbObj = new DBTransactions();
                $dbData = $dbObj->getData($sql);
                $count = $dbData[0]["ChqNo"]+1;
                $chq_no="SCHQ-".$wholesale->getSequence($count);
                $date=date("Y-m-d H:i:s");
                $CCQID=$_POST["transferCheque"][$i];
                $sql = "SELECT * FROM custcheq cc
                INNER JOIN custchqdetail ccd ON ccd.CCQID = cc.CCQID
                WHERE cc.CCQID=$CCQID";
                $customerChq = $dbObj->getData($sql);
                if(isset($customerChq[0]["chqNo"]))
                {
                    $chequeNo=$customerChq[0]["chqNo"];
                }
                else
                {
                    $chequeNo="N/A";
                }
                if(isset($customerChq[0]["chqdate"]))
                {
                    $chqdate=$customerChq[0]["chqdate"];
                }
                else
                {
                    $chqdate="N/A";
                }
                if(isset($customerChq[0]["chqbank"]))
                {
                    $bank=$customerChq[0]["chqbank"];
                }
                else
                {
                    $bank="N/A";
                }
                if(isset($customerChq[0]["chqAmount"]))
                {
                    $chqAmount=$customerChq[0]["chqAmount"];
                }
                else
                {
                    $chqAmount="N/A";
                }
                $date=date("Y-m-d H:i:s");
                $EffectiveDate=date("Y-m-d");
                $sql="INSERT INTO `supcheq`(`type`, `chq_stat`, `chq_no`, `sup_SPID`, `effectiveDate`, `user_USID`, `shop_SHID`, `createdDate`,`GRNHeader_GHID`,`transferedFrom`) VALUES ('1','1','$chq_no','$SupplierID','$EffectiveDate','$user_id','$shop_SHID','$date','$grn_header_id','$CCQID')";
                $dbObj->executeTransaction($sql);
                $sql="SELECT MAX(SCQID) AS SCQID FROM supcheq";
                $SCQID=$dbObj->getData($sql);
                $SCQID=$SCQID[0]["SCQID"];
                $sql="INSERT INTO `supchqdetail`(`bank`, `chqAmount`, `chqNo`, `chqDate`, `GRNHeader_GHID`, `SCQID`) VALUES ('$bank','$chqAmount','$chequeNo','$chqdate','$grn_header_id','$SCQID')";
                $dbObj->executeTransaction($sql);
                $sql="UPDATE `custcheq` SET chq_stat=2 WHERE CCQID='$CCQID'";
                $dbObj->executeTransaction($sql);
            }
        }
    }

    //if there`s a credit
    if($NetAmount > $SuppPayment)
    {
        $amount=$NetAmount-$SuppPayment;
        $grnObj->setCreditDebitSupplier($effective_date,$amount,$effective_date,$grn_header_id,$payment,$SupplierID,$user_id);
    }

    $grnObj->commitDbTransaction();

    header("Location: ../Public/grn-header.php");
}//test grn

if(isset($_POST['btn_cancle_grn']))
{
    $grn_header_id = $_POST['hide_grnheader_id'];
    $grn_stat = 3;
    $SuppPayment =0;
    $SupBalance = 0; 
    $excessamount = 0;
    $supplier_id = $_POST['cmb_edit_supplier'];
    $grnObj->grnEditSupplier($grn_header_id,$supplier_id);

    //update grn header stat to 1
    $grnObj->editGRNHeaderStat($grn_stat, $grn_header_id,$SuppPayment,$SupBalance,$excessamount);
    //update grn detail stat to 1
    $grnObj->editGRNDetailStat($grn_stat, $grn_header_id);

    header("Location: ../Public/grn-header.php");
}//cancle grn

//========================== Functions ===========================//
function CheckProduct($product_id, $purchase_price, $selling_price, $mnf_date, $exp_date, $variation_id, $shop_id)
{
    //check inventory
    $sql = "SELECT * FROM inventory WHERE products_PDID = ".$product_id." AND shop_SHID = ".$shop_id." AND CurrentQty > 0;";

    $dbObj = new DBTransactions();
    $invData = $dbObj->getData($sql);

    $output = true; //insert

    if(!empty($invData))
    {
        foreach($invData as $row)
        {
            $store_id = $row['INID'];
            $sql_1 = "SELECT * FROM pricehistory WHERE Inventory_INID = ".$store_id.";";
            $priceData = $dbObj->getData($sql_1);

            foreach($priceData as $row_1)
            {
                if($purchase_price == floatval($row_1['PurchasePrice']) AND $selling_price == floatval($row_1['SellingPrice']))
                {
                    if($mnf_date == $row_1['MnfDate'] AND $exp_date == $row_1['ExpDate'])
                    {
                        if($variation_id == $row_1['VariationID'])
                        {
                            $output = false;
                        }//check variation id
                        else
                        {
                            $output = true;
                        }
                    }//check mnf and exp date
                    else
                    {
                        $output = true;
                    }//else
                }//check price
                else
                {
                    $output = true;
                }
            }//foreach
        }//foreach
    }//has items in inventory
    else
    {
        $output = true;
    }//else

    return $output;
}//check product