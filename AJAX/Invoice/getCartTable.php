<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$shop_id = $_SESSION['shop_id'];

$tmp_bill_no = $_GET['tmp_bill_no'];

$dbObj = new DBTransactions();

$sql = "SELECT * FROM selldetail 
INNER JOIN sellheader ON sellheader.SHID = selldetail.sellheader_id
INNER JOIN pricehistory ON pricehistory.PHID = selldetail.pricehistory_id
INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
INNER JOIN products ON products.PDID = inventory.products_PDID
LEFT JOIN variations ON variations.VRID = pricehistory.VariationID
WHERE tmp_bill_no='".$tmp_bill_no."' AND sellheader.shop_id= ".$shop_id.";";

$dbData = $dbObj->getData($sql);

if(!empty($dbData))
{
    echo "<tr>";
    echo "<th>Item</th>";
    echo "<th>Qty</th>";
    echo "<th>Unit Price</th>";
    echo "<th>Amount</th>";
    echo "<th>Discount</th>";
    echo "<th>Total</th>";
    echo "<th>Action</th>";
    echo "</tr>";
    foreach($dbData as $row)
    {
        echo "<tr data-id='".$row['SDID']."'>";
        echo "<td style='display:none;'>".$row['SDID']."</td>";
        echo "<td style='display:none;'>".$row['sellQty']."</td>";
        echo "<td>".$row['Barcode']." <br> ".$row['ItemName']."</td>";
        echo "<td>".$row['sellQty'] + 0 ."</td>";
        
        echo "<td align='right'>".$row['SellingPrice']."</td>";
        echo "<td align='right'>".$row['sellAmount']."</td>";
        echo "<td align='right'>".$row['sellDiscount']."</td>";
        echo "<td align='right'>".$row['soldAmount']."</td>";
        echo "<td>";
        echo "<button id='btn_sell_".$row['SDID']."' class='tbl_cart_row btn border border-primary'><i class='ti ti-edit'></i></button>";
        echo "<button id='btn_sell_delete_".$row['SDID']."' class='btn border border-danger cart_row_delete'><i class='ti ti-x'></i></button>";
        echo "</td>";

        echo "</tr>";
    }//foreach
}//has data

//get service
$sql_1 = "SELECT * FROM selldetail
INNER JOIN sellheader ON sellheader.SHID = selldetail.sellheader_id
INNER JOIN products ON products.PDID = selldetail.product_id
WHERE pricehistory_id = 0 AND tmp_bill_no='".$tmp_bill_no."' AND sellheader.shop_id= ".$shop_id.";";

$serviceData = $dbObj->getData($sql_1);

if(!empty($serviceData))
{
    foreach($serviceData as $row)
    {
        echo "<tr data-id='".$row['SDID']."'>";
        echo "<td style='display:none;'>".$row['SDID']."</td>";
        echo "<td style='display:none;'>".$row['sellQty']."</td>";
        echo "<td>".$row['Barcode']." <br> ".$row['ItemName']."</td>";
        echo "<td class='text-primary'><b>Service</b></td>";
        echo "<td>".$row['unitSellAmount']."</td>";
        echo "<td>".$row['sellAmount']."</td>";
        echo "<td>".$row['sellDiscount']."</td>";
        echo "<td>".$row['soldAmount']."</td>";
        echo "<td>";
        echo "<button id='btn_sell_delete_".$row['SDID']."' class='btn border border-danger cart_row_delete'><i class='ti ti-x'></i></button>";
        echo "</td>";
        echo "</tr>";
    }//foreach
}//has data