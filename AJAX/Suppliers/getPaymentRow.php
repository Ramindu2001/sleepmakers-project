<?php
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/product_class.php";

$supplier_id = $_GET['supplier_id'];

$dbObj = new DBTransactions();

echo "<tr>";
echo "<td>";
echo "<select name='invoice_id[]' id='invoice_id' class='form-control invoice_id' required>";
echo "<option value=''>Select Invoice</option>";

$sql = "SELECT * FROM `creditsupplier` 
INNER JOIN grnheader ON grnheader.GHID = creditsupplier.invoice_header_id
INNER JOIN suppliers ON suppliers.SPID = creditsupplier.Supplier_ID
WHERE Supplier_ID = ".$supplier_id." AND Balance > 0;";

$supData = $dbObj->getData($sql);
foreach($supData as $row)
{
    echo "<option value='".$row['CCID']."'>".$row['InvoiceNo']."</option>";
}//foreach

echo "</select>";
echo "</td>";

echo "<td>";
echo "<input type='text' name='due[]' id='due' class='form-control' readonly required>";
echo "</td>";

echo "<td>";
echo "<input type='text' name='amount[]' id='amount' class='form-control amount' required>";
echo "</td>";

echo "<td>";
echo "<a href='javascript:void(0)' class='btn btn-danger' id='remove'><i class='ti ti-trash'></i></a>";
echo "</td>";

echo "</tr>";