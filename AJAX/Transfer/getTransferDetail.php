<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";

$shop_id = $_SESSION['shop_id'];
$transfer_header_id = (int)$_GET['header_id'];

$sql = "SELECT * FROM transferdetails 
INNER JOIN inventory ON inventory.INID = transferdetails.InventoryID
INNER JOIN products ON products.PDID = transferdetails.products_PDID
LEFT JOIN variations ON variations.VRID = transferdetails.VariationID
WHERE TransferHeader_THID = ".$transfer_header_id.";";

$shopObj = new Shop();
$dbObj = new DBTransactions();
$dbData = $dbObj->getData($sql);

//header data
$sql_1 = "SELECT * FROM transferheader WHERE THID = ".$transfer_header_id.";";
$headerData = $dbObj->getData($sql_1);

$from_shop = $headerData[0]['TransferFrom'];
$to_shop = $headerData[0]['TransferTo'];

echo "<tr>";
echo "<th>Product</th>";

//has variations
if($shopObj->hasVariation($shop_id))
{
    echo "<th>Variation</th>";
}//has variation

if($from_shop == $shop_id)
{
    echo "<th>Transfer Qty</th>";
}//transfer shop

if($to_shop == $shop_id)
{
    echo "<th>Receive Qty</th>";
}//receive shop

echo "<th>Avl Qty</th>";
echo "<th>Unit Purchase Price</th>";
echo "<th>Unit Selling Price</th>";
echo "<th>Total Amount</th>";
echo "<th>Action</th>";
echo "</tr>";
foreach($dbData as $row)
{
    echo "<tr data-id='".$row['TDID']."' id='".$row['INID']."'>";
    echo "<td style='display:none;'>".$row['TDID']."</td>";
    echo "<td>";
    echo $row['Barcode'] . "<br>";
    echo $row['ItemName'];
    echo "</td>";

    //has variations
    if($shopObj->hasVariation($shop_id))
    {
        echo "<td>".$row['VariationName']."</td>";
    }//has variation
    
    if($from_shop == $shop_id)
    {
        echo "<td>".$row['TransferQty']."</td>";
    }//transfer shop

    if($to_shop == $shop_id)
    {
        echo "<td>".$row['ReceivedQty']."</td>";
    }//receive shop
    
    echo "<td>".$row['CurrentQty']."</td>";
    echo "<td>".$row['UnitPurchasePrice']."</td>";
    echo "<td>".$row['UnitSellingPrice']."</td>";
    echo "<td>".$row['TransferTotalAmount']."</td>";
    echo "<td>";
    echo "<button type='button' class='btn_transfer_edit btn border border-primary mr-1'><i class='ti ti-edit'></i></button>";
    echo "<button type='button' class='btn_transfer_delete btn border border-danger'><i class='ti ti-x'></i></button>";
    echo "</td>";
    echo "</tr>";
}//foreach  