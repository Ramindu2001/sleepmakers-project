<?php
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
if(isset($_GET['grn_header_id']))
{
    $grn_header_id = $_GET['grn_header_id'];
    $sql = "SELECT * FROM `suppliertransactions` 
    INNER JOIN paymethod ON paymethod.PMID = suppliertransactions.paymethod_PMID
    WHERE GRNHeader_GHID = ".$grn_header_id.";";

    $dbObj = new DBTransactions();
    $grnData = $dbObj->getData($sql);

    if(!empty($grnData))
    {
        echo json_encode($grnData);
    }//has data
}
else if(isset($_GET["GHID"]) && isset($_GET["supplier_id"]))
{
    $supplier_id=$_GET["supplier_id"];
    $GHID=$_GET["GHID"];
    $sql = "SELECT * FROM `supcredittransactions` 
    INNER JOIN paymethod ON paymethod.PMID = supcredittransactions.paymethod_id
    WHERE supcredittransactions.grn_GHI = '$GHID';";

    $dbObj = new DBTransactions();
    $grnData = $dbObj->getData($sql);

    if(!empty($grnData))
    {
        echo json_encode($grnData);
    }//has data

}