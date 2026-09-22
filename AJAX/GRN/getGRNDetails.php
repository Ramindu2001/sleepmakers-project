<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";

$shop_id = $_SESSION['shop_id'];
$grn_header_id = $_GET['grn_header_id'];

$shopObj = new Shop();
$dbObj = new DBTransactions();

//filter data with shop specifics variations, racks, exp_dates, label_price


$sql = "SELECT GDID, PDID, Barcode, ItemName, VRID, VariationName, InitQty, UnitPurchasePrice, UnitLabelPrice, UnitSellPrice, TotalPurchasePrice, TotalSellPrice, MnfDate, ExpDate, SEID, SectionName, RKID, RackName, UNID, ShortName FROM grndetails
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
    echo "<th style='min-width:100px;'>Purchase Price</th>";

    if($shopObj->hasLabelPrice($shop_id))
    {
        echo "<th style='min-width:100px;'>Label Price</th>";
    }
    
    echo "<th style='min-width:100px;'>Selling Price</th>";
    echo "<th style='min-width:100px;'>Total Purchase</th>";
    echo "<th style='min-width:100px;'>Total Selling</th>";

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
    echo "<tr data-id='".$row['GDID']."'>";
    echo "<td style='display:none;'>".$row['GDID']."</td>";//--> 0
    echo "<td style='display:none;'>".$row['InitQty']."</td>";//--> 1
    echo "<td style='display:none;'>".$row['TotalPurchasePrice']."</td>";//--> 2

    echo "<td>".$row['Barcode']." <br> ".$row['ItemName']."</td>";//--> 1

    if($shopObj->hasVariation($shop_id))
    {
        echo "<td>".$row['VariationName']."</td>";//--> 2
    }
    
    echo "<td>".$row['InitQty']." ".$row['ShortName']."</td>";//--> 3
    echo "<td>".$row['UnitPurchasePrice']."</td>";//--> 4

    if($shopObj->hasLabelPrice($shop_id))
    {
        echo "<td>".$row['UnitLabelPrice']."</td>";//--> 5
    }
    
    echo "<td>".$row['UnitSellPrice']."</td>";//--> 6
    echo "<td>".$row['TotalPurchasePrice']."</td>";//--> 7
    echo "<td>".$row['TotalSellPrice']."</td>";//--> 8

    if($shopObj->hasExpiry($shop_id))
    {
        echo "<td>".$row['MnfDate']."</td>";//--> 9
        echo "<td>".$row['ExpDate']."</td>";//--> 10
    }
    
    if($shopObj->hasRacks($shop_id))
    {
        echo "<td>".$row['SectionName']." <br> ".$row['RackName']."</td>";//--> 11
    }
    
    echo "<td style='display:none;'>".$row['PDID']."</td>";//--> 12
    echo "<td style='display:none;'>".$row['InitQty']."</td>";//--> 13
    echo "<td style='display:none;'>".$row['SEID']."</td>";//--> 14
    echo "<td style='display:none;'>".$row['RKID']."</td>";//--> 15
    echo "<td style='display:none;'>".$row['VRID']."</td>";//--> 16
    echo "<td>";
    echo "<button type='button' id='btn_grndetail_".$row['GDID']."' class='btn_grn_edit btn border border-primary'><i class='ti ti-edit'></i></button>";
    echo "<button type='button' id='btn_grndetail_delete_".$row['GDID']."' class='btn_grn_delete btn border border-danger'><i class='ti ti-x'></i></button>";
    echo "</td>";
    echo "</tr>";
}//foreach