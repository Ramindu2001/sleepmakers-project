<?php
session_start(); 
include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";

$shop_id = $_SESSION['shop_id'];
$shopObj = new Shop();
$dbObj = new DBTransactions();

$adjust_header_id = $_GET['adjust_header_id'];

$sql = "SELECT * FROM `adjustheader` WHERE AHID = ".$adjust_header_id.";";
$adjustData = $dbObj->getData($sql);

$adjust_type = $adjustData[0]['AdjustmentType_ITID'];

$sql = "SELECT * FROM adjustproddetails
INNER JOIN products ON products.PDID = adjustproddetails.products_PDID
LEFT JOIN variations ON variations.VRID = adjustproddetails.VariationID
WHERE AdjustHeader_AHID = ".$adjust_header_id.";";

$dbData = $dbObj->getData($sql);

echo "<tr>";
echo "<th>Product</th>";
if($shopObj->hasVariation($shop_id))
{
    echo "<th>Variation</th>";
}
echo "<th>Transfer Qty</th>";
echo "<th>Unit purchase Price</th>";
echo "<th>Unit Selling Price</th>";
echo "<th>Total Amount</th>";
echo "<th>Action</th>";
echo "</tr>";

foreach($dbData as $row)
{
    echo "<tr data-id='".$row['APID']."'>";
    echo "<td style='display:none;'>".$row['APID']."</td>";//--- 0
    echo "<td>";
    echo $row['Barcode'] . "<br>";
    echo $row['ItemName'];
    echo "</td>";//--- 1
    if($shopObj->hasVariation($shop_id))
    {
        echo "<td>".$row['VariationName']."</td>";//--- 2
    }
    echo "<td>".$row['AdjustProdQty']."</td>";//--- 3
    echo "<td>".$row['UnitPurchasePrice']."</td>";//--- 4
    echo "<td>".$row['UnitSellingPrice']."</td>";//--- 5
    echo "<td>".$row['AdjustProdAmount']."</td>";//--- 6

    echo "<td>";
    echo "<button type='button' class='btn border border-danger btn_delete_adjust'><i class='ti ti-x'></i></button>";
    echo "</td>";
    echo "</tr>";
}//foreach