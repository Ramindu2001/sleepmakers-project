<?php 
include "../Includes/includes.php";
$dbObj = new DBTransactions();
$grnObj = new GRN();
$wholesale=new wholesale_invoice();
$shop_SHID=$_SESSION['shop_id'];
$user_id = $_SESSION['user_id'];
if(isset($_POST["editCusChq"]))
{
    $chqid=$_POST["chqid"];
    
    $sql = "SELECT * FROM custcheq sc
                    INNER JOIN custchqdetail scd ON sc.CCQID=scd.CCQID
                    INNER JOIN customers s ON s.CTID=sc.cust_CTID
                    INNER JOIN invoiceheader g ON g.IHID=sc.invoiceID
                    WHERE sc.CCQID='$chqid' ORDER BY sc.chq_no DESC;";
    $dbObj = new DBTransactions();
    $invData = $dbObj->getData($sql);
    if($invData[0]["chq_stat"]==1 || $invData[0]["chq_stat"]==2 )
    {
        if(isset($_POST["chqNo"]))
        {
            $chqNo=$_POST["chqNo"];
        }
        else
        {
            $chqNo="";
        }

        if(isset($_POST["bank"]))
        {
            $bank=$_POST["bank"];
        }
        else
        {
            $bank="";
        }

        if(isset($_POST["chqDate"]))
        {
            $chqDate=$_POST["chqDate"];
        }
        else
        {
            $chqDate="";
        }

        if(isset($_POST["chqAmount"]))
        {
            $chqAmount=$_POST["chqAmount"];
        }
        else
        {
            $chqAmount="";
        }

        if(isset($_POST["chq_stat"]) && $_POST["chq_stat"]!="")
        {
            $chq_stat=$_POST["chq_stat"];
            $sql="UPDATE `custcheq` SET `chq_stat`='$chq_stat' WHERE CCQID='$chqid'";
            $dbObj->executeTransaction($sql);
            if($chq_stat==3)
            {
                $EffectiveDate=date("Y-m-d");
                $InvoiceID=$invData[0]["invoiceID"];
                $customers_CTID=$invData[0]["cust_CTID"];
                $cust=$wholesale->setCreditCust($EffectiveDate,$chqAmount,$EffectiveDate,$InvoiceID,5,$customers_CTID,$user_id,2);
                $sql = "SELECT * FROM supcheq  WHERE transferedFrom='$chqid' ";
                $dbObj = new DBTransactions();
                $invData = $dbObj->getData($sql);
                if(isset($invData[0]["transferedFrom"]) && $invData[0]["transferedFrom"] > 0)
                {
                    $transferedFrom=$invData[0]["transferedFrom"];
                    $grn_header_id=$invData[0]["GRNHeader_GHID"];
                    $SupplierID=$invData[0]["sup_SPID"];
                    $sql="UPDATE `supcheq` SET `chq_stat`='$chq_stat' WHERE transferedFrom='$transferedFrom'";
                    $dbObj->executeTransaction($sql);
                    $grnObj->setCreditDebitSupplier($EffectiveDate,$chqAmount,$EffectiveDate,$grn_header_id,5,$SupplierID,$user_id);
                }
                
            }
        }
        $sql="UPDATE `custchqdetail` SET `bank`='$bank',`chqAmount`='$chqAmount',`chqNo`='$chqNo',`chqDate`='$chqDate' WHERE CCQID='$chqid'";
        $dbObj->executeTransaction($sql);
    }


    header("Location:../Reports/cheque-notification.php?type=2&chq=$chqid");
}
?>