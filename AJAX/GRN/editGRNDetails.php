<?php
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/GRN_class.php";

/*
 * Correct one GRN line: its quantity, and the rack it went to.
 *
 * The prices, dates, variation and product of the line are read back from the line itself and written
 * again unchanged - the stock screen shows no price, so no price is accepted from the browser either.
 * The line totals follow the new quantity.
 */
$grn_detail_id = (int) $_GET['grn_detail_id'];
$prod_qty = floatval($_GET['prod_qty']);
$rack_id = isset($_GET['rack_id']) ? (int) $_GET['rack_id'] : 1;

if($grn_detail_id <= 0 || $prod_qty <= 0)
{
    echo 0;
    exit;
}//nothing to change

$dbObj = new DBTransactions();
$line = $dbObj->getMultipleData("SELECT UnitPurchasePrice, UnitLabelPrice, UnitSellPrice, MnfDate, ExpDate, VariationID, products_PDID FROM grndetails WHERE GDID = ?;", [$grn_detail_id]);

if(empty($line))
{
    echo 0;
    exit;
}//gone

$line = $line[0];
$total_purchase = floatval($line['UnitPurchasePrice']) * $prod_qty;
$total_selling = floatval($line['UnitSellPrice']) * $prod_qty;

$grnObj = new GRN();
$grnObj->editGRNDetails($prod_qty, $prod_qty, $line['UnitPurchasePrice'], $line['UnitLabelPrice'], $line['UnitSellPrice'],
    $total_purchase, $total_selling, $line['MnfDate'], $line['ExpDate'], $line['VariationID'], $line['products_PDID'],
    $rack_id, $grn_detail_id);

echo 1;
