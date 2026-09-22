<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/shop_class.php";

$shopObj = new Shop();
$dbObj = new DBTransactions();

$shop_id = $_SESSION['shop_id'];

$sql = "SELECT * FROM `temp_grnupload` WHERE shop_id = ".$shop_id.";";

$uploadData = $dbObj->getData($sql);

echo "<tr>";
echo "<th>No</th>";
echo "<th>Barcode</th>";
echo "<th>Item Name</th>";
echo "<th>Qty</th>";
echo "<th>Purchase Price</th>";
echo "<th>Selling Price</th>";

if($shopObj->hasExpiry($shop_id))
{
    echo "<th>Mnf Date</th>";
    echo "<th>Exp Date</th>";
}//has expire

if($shopObj->hasRacks($shop_id))
{
    echo "<th>Section</th>";
    echo "<th>Rack</th>";
}//has racks
echo "<th>Status</th>";
echo "<th>Action</th>";
echo "</tr>";

$row_count = 0;
foreach($uploadData as $row)
{
    $row_count += 1;
    //find section and rack
    $section_id = $row['section_id'];
    $sql = "SELECT * FROM `sections` WHERE SEID = ".$section_id.";";
    $secData = $dbObj->getData($sql);
    $section = $secData[0]['SectionName'];

    $rack_id = $row['section_id'];
    $sql = "SELECT * FROM `rack` WHERE RKID = ".$rack_id.";";
    $rackData = $dbObj->getData($sql);
    $rack = $rackData[0]['RackName'];

    echo "<tr data-id='".$row['upload_id']."'>";
    echo "<td>".$row_count."</td>";
    echo "<td>".$row['barcode']."</td>";
    echo "<td>".$row['itemname']."</td>";
    echo "<td>".$row['qty'] + 0 ."</td>";
    echo "<td>".$row['purchaseprice']."</td>";
    echo "<td>".$row['sellingprice']."</td>";

    if($shopObj->hasExpiry($shop_id))
    {
        echo "<td>".$row['mnfdate']."</td>";
        echo "<td>".$row['expdate']."</td>";
    }//has expire

    if($shopObj->hasRacks($shop_id))
    {
        echo "<td>".$section."</td>";
        echo "<td>".$rack."</td>";
    }//has racks

    if($row['prod_stat'] == 1)
    {
        echo "<td>";
        echo "<p class='text-success m-1' style='font-weight: bold;'>Available</p>";
        echo "</td>";
    }
    else
    {
        echo "<td>";
        echo "<p class='text-warning m-1' style='font-weight: bold;'>Not Available</p>";
        echo "</td>";
    }

    echo "<td>";
    echo "<button class='btn border border-success text-success btn_edit_item'><i class='ti ti-edit'></i></button>";
    echo "<button class='btn border border-danger text-danger btn_delete_item'>";
    echo "<i class='ti ti-trash'></i>";
    echo "</button>";
    echo "</td>";

    echo "</tr>";
}//upload data
//echo json_encode($uploadData);
