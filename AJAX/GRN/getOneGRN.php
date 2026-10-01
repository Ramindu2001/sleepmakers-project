<?php
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";

/*
 * One GRN line, for the quantity correction row on the stock screen.
 * Only what that row shows: the product, how many, and where it went. No price leaves the server -
 * the GRN screen does not show money.
 */
$grn_detail_id = (int) $_GET['grn_detail_id'];

$sql = "SELECT GDID, products_PDID AS PDID, Barcode, ItemName, InitQty, MnfDate, ExpDate, VariationID AS VRID, SEID, RKID
        FROM grndetails
        INNER JOIN products ON products.PDID = grndetails.products_PDID
        LEFT JOIN rack ON rack.RKID = grndetails.Rack_RKID
        LEFT JOIN sections ON sections.SEID = rack.Sections_SEID
        WHERE GDID = ".$grn_detail_id.";";

$dbObj = new DBTransactions();
$grnData = $dbObj->getData($sql);

echo json_encode($grnData, JSON_FORCE_OBJECT);
