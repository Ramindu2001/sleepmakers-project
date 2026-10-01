<?php
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";

$shop_id = $_SESSION['shop_id'];
$grn_header_id = (int) $_GET['grn_header_id'];

$shopObj = new Shop();
$dbObj = new DBTransactions();

/*
 * The lines of one GRN, for the stock screen: what came in and how much of it.
 * No price is selected or sent - the GRN screen adds stock and never shows money, so a price
 * cannot be read out of the page either.
 */
$sql = "SELECT GDID, PDID, Barcode, ItemName, VRID, VariationName, InitQty, MnfDate, ExpDate, SEID, SectionName, RKID, RackName, UNID, ShortName FROM grndetails
        LEFT JOIN products ON products.PDID = grndetails.products_PDID
        LEFT JOIN variations ON variations.VRID = grndetails.VariationID
        LEFT JOIN units ON units.UNID = products.PurchaseUnit
        LEFT JOIN rack ON rack.RKID = grndetails.Rack_RKID
        LEFT JOIN sections ON sections.SEID = rack.Sections_SEID
        WHERE GRNHeader_GHID = ".$grn_header_id." ORDER BY GDID DESC;";

$dbData = $dbObj->getData($sql);

    echo "<tr>";
    echo "<th style='min-width:250px;'>Product</th>";
    if($shopObj->hasVariation($shop_id))
    {
        echo "<th style='min-width:100px;'>Variation</th>";
    }

    echo "<th style='min-width:100px;'>Qty</th>";

    if($shopObj->hasExpiry($shop_id))
    {
        echo "<th style='min-width:120px;'>Mnf Date</th>";
        echo "<th style='min-width:120px;'>Exp Date</th>";
    }

    if($shopObj->hasRacks($shop_id))
    {
        echo "<th style='min-width:100px;'>Section & Racks</th>";
    }

    echo "<th style='min-width:150px;'>Action</th>";
    echo "</tr>";

foreach($dbData as $row)
{
    //data-qty: the item count at the bottom of the screen is added up from these
    echo "<tr data-id='".$row['GDID']."' data-qty='".$row['InitQty']."'>";

    echo "<td>".$row['Barcode']." <br> ".$row['ItemName']."</td>";

    if($shopObj->hasVariation($shop_id))
    {
        echo "<td>".$row['VariationName']."</td>";
    }

    echo "<td>".$row['InitQty']." ".$row['ShortName']."</td>";

    if($shopObj->hasExpiry($shop_id))
    {
        echo "<td>".$row['MnfDate']."</td>";
        echo "<td>".$row['ExpDate']."</td>";
    }

    if($shopObj->hasRacks($shop_id))
    {
        echo "<td>".$row['SectionName']." <br> ".$row['RackName']."</td>";
    }

    echo "<td>";
    echo "<button type='button' id='btn_grndetail_".$row['GDID']."' class='btn_grn_edit btn border border-primary'><i class='ti ti-edit'></i></button>";
    echo "<button type='button' id='btn_grndetail_delete_".$row['GDID']."' class='btn_grn_delete btn border border-danger'><i class='ti ti-x'></i></button>";
    echo "</td>";
    echo "</tr>";
}//foreach
