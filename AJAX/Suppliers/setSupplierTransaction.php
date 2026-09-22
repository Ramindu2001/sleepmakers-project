<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/supplier_credit_class.php";
include "../../Model/wholesale_invoice_class.php";
if(isset($_GET["type"]))
{
    $grn_header_id = $_GET['grn_header_id'];
    $paymethod_id = $_GET['paymethod_id'];
    $pay_amount = $_GET['pay_amount'];
    $supplier_id = $_GET['supplier_id'];
    $shop_SHID=$_SESSION['shop_id'];
    $user_id = $_SESSION['user_id'];
    $wholesale=new wholesale_invoice();
    $dbObj = new DBTransactions();

    //subit = 1, pending = 0
    $transaction_stat = 0; //will verify after submit

    $creditObj = new SupplierCredit();
    if($paymethod_id==5 || $paymethod_id==13)
    {
        $insert1=$creditObj->setSupplierCredit2($pay_amount, 1, $grn_header_id, $paymethod_id, $supplier_id); 
        $insert2=$creditObj->supplierCreditPay($pay_amount,$pay_amount,$grn_header_id,$paymethod_id,$supplier_id,$user_id,$shop_SHID); 
        $SCTID=$insert1[0]["SCTID"];
        $update=$creditObj->updatesupplierTran($insert2[0]["SCID"],$SCTID);
    
    }
    else
    {
        $creditObj->setSupplierCredit2($pay_amount, $transaction_stat, $grn_header_id, $paymethod_id, $supplier_id); 
    }
    $sql = "SELECT * FROM grnheader WHERE GHID='$grn_header_id'";
    $grn = $dbObj->getData($sql);
    if($paymethod_id==5 && !empty($_GET["chqNo"]))
    {
        for ($j=0; $j <count($_GET["chqNo"]) ; $j++) 
        { 
            $sql = "SELECT count(*) AS ChqNo FROM supcheq ";
            $dbData = $dbObj->getData($sql);
            $count = $dbData[0]["ChqNo"]+1;
            $chq_no="SCHQ-".$wholesale->getSequence($count);
            $date=date("Y-m-d H:i:s");
            if(isset($_GET["chqNo"][$j]))
            {
                $chequeNo=$_GET["chqNo"][$j];
            }
            else
            {
                $chequeNo="N/A";
            }
            if(isset($_GET["chqdate"][$j]))
            {
                $chqdate=$_GET["chqdate"][$j];
            }
            else
            {
                $chqdate="N/A";
            }
            if(isset($_GET["chqbank"][$j]))
            {
                $bank=$_GET["chqbank"][$j];
            }
            else
            {
                $bank="N/A";
            }
            if(isset($_GET["chqAmount"][$j]))
            {
                $chqAmount=$_GET["chqAmount"][$j];
            }
            else
            {
                $chqAmount="N/A";
            }
            $supObj = new SupplierCredit();
            $sql = "SELECT sum(TransferAmount) as TotalTransferAmount FROM `suppliertransactions` WHERE TransactionStat = 0 AND GRNHeader_GHID = ".$grn_header_id.";";
        
            $transData = $dbObj->getData($sql);
        
            $total_transfer_amount = $transData[0]['TotalTransferAmount'];

           
            $SupplierID=$grn[0]["Suppliers_SPID"];
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
    if($paymethod_id==13 && !empty($_GET["transferCheque"]))
    {
        for ($j=0; $j <count($_GET["transferCheque"]) ; $j++) 
        { 
            $sql = "SELECT count(*) AS ChqNo FROM supcheq ";
            $wholesale=new wholesale_invoice();
            $dbObj = new DBTransactions();
            $dbData = $dbObj->getData($sql);
            $count = $dbData[0]["ChqNo"]+1;
            $chq_no="SCHQ-".$wholesale->getSequence($count);
            $date=date("Y-m-d H:i:s");
            $CCQID=$_GET["transferCheque"][$j];
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
            $sql="INSERT INTO `supcheq`(`type`, `chq_stat`, `chq_no`, `sup_SPID`, `effectiveDate`, `user_USID`, `shop_SHID`, `createdDate`,`GRNHeader_GHID`,`transferedFrom`) VALUES ('1','1','$chq_no','$supplier_id','$EffectiveDate','$user_id','$shop_SHID','$date','$grn_header_id','$CCQID')";
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
else
{
    $grn_header_id = $_GET['grn_header_id'];
    $paymethod_id = $_GET['paymethod_id'];
    $pay_amount = $_GET['pay_amount'];
    $supplier_id = $_GET['supplier_id'];
    $shop_SHID=$_SESSION['shop_id'];
    $user_id = $_SESSION['user_id'];
    $wholesale=new wholesale_invoice();
    $dbObj = new DBTransactions();

    //subit = 1, pending = 0
    $transaction_stat = 0; //will verify after submit

    $creditObj = new SupplierCredit();
    if($paymethod_id==5 || $paymethod_id==13)
    {
    $creditObj->setSupplierCredit($pay_amount, 1, $paymethod_id, $grn_header_id, 0);  
    }
    else
    {
        $creditObj->setSupplierCredit($pay_amount, $transaction_stat, $paymethod_id, $grn_header_id, 0); 
    }
    $sql = "SELECT * FROM grnheader WHERE GHID='$grn_header_id'";
    $grn = $dbObj->getData($sql);
    if($paymethod_id==5 && !empty($_GET["chqNo"]))
            {
                for ($j=0; $j <count($_GET["chqNo"]) ; $j++) 
                { 
                    $sql = "SELECT count(*) AS ChqNo FROM supcheq ";
                    $dbData = $dbObj->getData($sql);
                    $count = $dbData[0]["ChqNo"]+1;
                    $chq_no="SCHQ-".$wholesale->getSequence($count);
                    $date=date("Y-m-d H:i:s");
                    if(isset($_GET["chqNo"][$j]))
                    {
                        $chequeNo=$_GET["chqNo"][$j];
                    }
                    else
                    {
                        $chequeNo="N/A";
                    }
                    if(isset($_GET["chqdate"][$j]))
                    {
                        $chqdate=$_GET["chqdate"][$j];
                    }
                    else
                    {
                        $chqdate="N/A";
                    }
                    if(isset($_GET["chqbank"][$j]))
                    {
                        $bank=$_GET["chqbank"][$j];
                    }
                    else
                    {
                        $bank="N/A";
                    }
                    if(isset($_GET["chqAmount"][$j]))
                    {
                        $chqAmount=$_GET["chqAmount"][$j];
                    }
                    else
                    {
                        $chqAmount="N/A";
                    }
                    $supObj = new SupplierCredit();
                    $sql = "SELECT sum(TransferAmount) as TotalTransferAmount FROM `suppliertransactions` WHERE TransactionStat = 0 AND GRNHeader_GHID = ".$grn_header_id.";";
                
                    $transData = $dbObj->getData($sql);
                
                    $total_transfer_amount = $transData[0]['TotalTransferAmount'];

                    $supObj->editCreditBalance($total_transfer_amount, $total_transfer_amount, $total_transfer_amount, $credit_supplier_id);
                    $SupplierID=$grn[0]["Suppliers_SPID"];
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
            if($paymethod_id==13 && !empty($_GET["transferCheque"]))
            {
                for ($j=0; $j <count($_GET["transferCheque"]) ; $j++) 
                { 
                    $sql = "SELECT count(*) AS ChqNo FROM supcheq ";
                    $wholesale=new wholesale_invoice();
                    $dbObj = new DBTransactions();
                    $dbData = $dbObj->getData($sql);
                    $count = $dbData[0]["ChqNo"]+1;
                    $chq_no="SCHQ-".$wholesale->getSequence($count);
                    $date=date("Y-m-d H:i:s");
                    $CCQID=$_GET["transferCheque"][$j];
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
                    $sql="INSERT INTO `supcheq`(`type`, `chq_stat`, `chq_no`, `sup_SPID`, `effectiveDate`, `user_USID`, `shop_SHID`, `createdDate`,`GRNHeader_GHID`,`transferedFrom`) VALUES ('1','1','$chq_no','$supplier_id','$EffectiveDate','$user_id','$shop_SHID','$date','$grn_header_id','$CCQID')";
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


// echo $paymethod_id."<br>";
// print_r($_GET["transferCheque"]);