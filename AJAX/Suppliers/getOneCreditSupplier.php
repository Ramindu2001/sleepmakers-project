<?php
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
$shop_id = $_SESSION['shop_id'];
$dbObj = new DBTransactions();
if(isset($_GET['credit_supplier_id']))
{
    $credit_supplier_id = $_GET['credit_supplier_id'];

    $sql = "SELECT * FROM `creditsupplier` 
    INNER JOIN grnheader ON grnheader.GHID = creditsupplier.invoice_header_id
    INNER JOIN suppliers ON suppliers.SPID = creditsupplier.Supplier_ID
    WHERE CCID = ".$credit_supplier_id.";";

    
    $prodData = $dbObj->getData($sql);

    if(!empty($prodData))
    {
        echo json_encode($prodData);
    }//has data
}
elseif(isset($_GET["supplier_id"]) && isset($_GET["GHID"]))
{
    $supplier_id=$_GET["supplier_id"];
    $GHID=$_GET["GHID"];
    $sql = "SELECT cs.*,gh.GHID,gh.GRNHeaderNo,gh.TotalPurchasePrice, gh.EffectiveDate AS GRNdate,SUM(cs.DebitAmount) AS DebitAmount, SUM(cs.CreditAmount) AS CreditAmount FROM `creditsupplier` cs
    LEFT JOIN grnheader gh ON gh.GHID=cs.invoice_header_id
    WHERE cs.Supplier_ID='$supplier_id' AND cs.CreditStat=1 AND cs.shop_SHID='$shop_id' AND cs.invoice_header_id='$GHID' GROUP BY cs.Supplier_ID,cs.invoice_header_id;";
    $suppData = $dbObj->getData($sql);    
    if(!empty($suppData))
    {
        echo json_encode($suppData);
    }//has data

}
