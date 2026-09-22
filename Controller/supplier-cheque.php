<?php 
include "../Includes/includes.php";
$dbObj = new DBTransactions();
$grnObj = new GRN();
$wholesale=new wholesale_invoice();
$shop_SHID=$_SESSION['shop_id'];
$user_id = $_SESSION['user_id'];

if(isset($_POST["editSupChq"]))
{
    $chqid=$_POST["chqid"];
    
    $sql = "SELECT * FROM supcheq sc
    INNER JOIN supchqdetail scd ON sc.SCQID=scd.SCQID
    INNER JOIN suppliers s ON s.SPID=sc.sup_SPID
    INNER JOIN grnheader g ON g.GHID=sc.GRNHeader_GHID
    WHERE sc.SCQID='$chqid' ORDER BY sc.chq_no DESC;";
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
            $sql="UPDATE `supcheq` SET `chq_stat`='$chq_stat' WHERE SCQID='$chqid'";
            $dbObj->executeTransaction($sql);
            if($chq_stat==3)
            {
                $grn_header_id=$invData[0]["GRNHeader_GHID"];
                $SupplierID=$invData[0]["sup_SPID"];
                $effective_date=date("Y-m-d");
                $grnObj->setCreditDebitSupplier($effective_date,$chqAmount,$effective_date,$grn_header_id,5,$SupplierID,$user_id);
                if(isset($invData[0]["transferedFrom"]) && $invData[0]["transferedFrom"] > 0)
                {
                    $transferedFrom=$invData[0]["transferedFrom"];
                    $sql="UPDATE `custcheq` SET `chq_stat`='$chq_stat' WHERE CCQID='$transferedFrom'";
                    $dbObj->executeTransaction($sql);
                    $EffectiveDate=date("Y-m-d");
                    $sql = "SELECT * FROM custcheq WHERE CCQID='$transferedFrom' ;";
                    $dbObj = new DBTransactions();
                    $invData = $dbObj->getData($sql);
                    $InvoiceID=$invData[0]["invoiceID"];
                    $customers_CTID=$invData[0]["cust_CTID"];
                    $cust=$wholesale->setCreditCust($EffectiveDate,$chqAmount,$EffectiveDate,$InvoiceID,5,$customers_CTID,$user_id,2);
                }
                
            }
        }
        $sql="UPDATE `supchqdetail` SET `bank`='$bank',`chqAmount`='$chqAmount',`chqNo`='$chqNo',`chqDate`='$chqDate' WHERE SCQID='$chqid'";
        $dbObj->executeTransaction($sql);
    }
    header("Location:../Reports/cheque-notification.php?type=1&chq=$chqid");
}
?>