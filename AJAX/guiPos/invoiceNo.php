<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/gui_pos_class.php";
$noRight=1;
$dbObj = new DBTransactions();
$shop_id = $_SESSION['shop_id'];
$guiObj= new guiPOS;
$user_id = $_SESSION['user_id'];

$doc=$guiObj->select_docno($shop_id);
    if(count($doc)==0) 
    {
        $inser_doc=$guiObj->insert_doc_no($shop_id);
        $doc=$guiObj->select_docno($shop_id);
    }
    $ws_no=$doc[0]["org_no"] + 1;
    $ws_no=$guiObj->getSequence($ws_no);
    $salesetings=$guiObj->getSaleSettings($shop_id);
    if(count($salesetings)>0)
    {
        if(isset($salesetings[0]["billNoHeader"]))
        {
            $ws_no=$salesetings[0]["billNoHeader"]."-".$ws_no;
        }
        else
        {
            $ws_no="RINV-".$ws_no;
        }
    }
    else
    {
        $ws_no="RINV-".$ws_no;
    }
    echo $ws_no;
?>