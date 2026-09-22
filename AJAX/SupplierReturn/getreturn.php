<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";

$shop_id = $_SESSION['shop_id'];
$srn_header_id = $_GET['srn_header_id'];

$shopObj = new Shop();
$dbObj = new DBTransactions();

//filter data with shop specifics variations, racks, exp_dates, label_price
$sql = "SELECT SRDID,ProductID,products.ProdDescription,SD.UnitPurchasePrice,SD.VariationID,variations.VariationName,SD.Batch,SD.ReturnQty,SD.ReturnAmount FROM supplierreturndetails SD INNER JOIN supplierreturn SR ON SR.SRID = SD.supplierreturn_SRID   
        LEFT JOIN products ON products.PDID = SD.ProductID
        LEFT JOIN variations ON variations.VRID = SD.VariationID
        LEFT JOIN units ON units.UNID = products.PurchaseUnit    
        WHERE SR.ReturnNo = '".$srn_header_id."' ORDER BY SRDID DESC";

$dbData = $dbObj->getData($sql);

    echo "<tr>";
    echo "<th style='min-width:250px;'>Product</th>";
    if($shopObj->hasVariation($shop_id))
    {
        echo "<th style='min-width:100px;'>Variation</th>";
    }
    
    echo "<th style='min-width:100px;'>Batch</th>";
    echo "<th style='min-width:100px;'>Qty</th>";
    echo "<th style='min-width:100px;'>Purchase Price</th>";

    echo "<th style='min-width:100px;'>Total Purchase</th>";
    
    echo "<th style='min-width:150px;'>Action</th>";
    echo "</tr>";

foreach($dbData as $row)
{
    echo "<tr data-id='".$row['SRDID']."'>";
    echo "<td style='display:none;'>".$row['SRDID']."</td>";//--> 0
    echo "<td style='display:none;'>".$row['ReturnQty']."</td>";//--> 1   
    echo "<td>".$row['ProdDescription']."</td>";//--> 1

    if($shopObj->hasVariation($shop_id))
    {
        echo "<td>".$row['VariationName']."</td>";//--> 2
    }
    
    echo "<td>".$row['Batch']."</td>";//--> 3
    echo "<td>".$row['ReturnQty']."</td>";//--> 4    
    echo "<td>".$row['UnitPurchasePrice']."</td>";//--> 6
    echo "<td>".$row['ReturnAmount']."</td>";//--> 7
    

    echo "<td style='display:none;'>".$row['ProductID']."</td>";//--> 12    
    echo "<td style='display:none;'>".$row['VariationID']."</td>";//--> 16
    echo "<td>";
    echo "<button type='button' id='btn_srndetail_".$row['SRDID']."' class='btn_srn_edit btn border border-primary'><i class='ti ti-edit'></i></button>";
    echo "<button type='button' class='btn_srn_remove btn border border-danger'><i class='ti ti-x'></i></button>";
    echo "</td>";
    echo "</tr>";
}//foreach