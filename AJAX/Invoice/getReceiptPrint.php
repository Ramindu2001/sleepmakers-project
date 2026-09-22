<?php 
session_start();

include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/receipt_format_class.php";

$shop_id = $_SESSION['shop_id'];
$invoice_no = $_GET['invoice_no'];

$sql = "SELECT * FROM invoiceheader ih 
        LEFT JOIN salesmans sm ON sm.SLID=ih.Salesmans_SLID
        LEFT JOIN customers c ON c.CTID=ih.customers_CTID 
        WHERE ih.shop_SHID='$shop_id' AND (ih.BillNo LIKE '%$invoice_no%' OR   c.CustName LIKE '%$invoice_no%'  OR  c.CustContact LIKE '%$invoice_no%'  OR  sm.SalesmansName LIKE '%$invoice_no%') ORDER BY ih.IHID DESC;";
// if($invoice_no!="" && isset($_GET['fdate']) && isset($_GET['tdate']) )
// {
//     $fdate = $_GET['fdate'];
//     $tdate = $_GET['tdate'];
//     $sql = "SELECT * FROM invoiceheader ih 
//             LEFT JOIN salesmans sm ON sm.SLID=ih.Salesmans_SLID
//             LEFT JOIN customers c ON c.CTID=ih.customers_CTID 
//             WHERE  ih.shop_SHID='$shop_id' AND ( ih.BillNo LIKE '%$invoice_no%' OR   c.CustName LIKE '%$invoice_no%'  OR  c.CustContact LIKE '%$invoice_no%'  OR  sm.SalesmansName LIKE '%$invoice_no%') AND ih.EffectiveDate BETWEEN '$fdate' AND '$tdate' ORDER BY ih.IHID DESC;";
// }
// echo $sql;
$dbObj = new DBTransactions();
$invData = $dbObj->getData($sql);
$count=count($invData);
?>

<script>
    $(document).ready(function(){
        $("#results").text("<?=$count?>")
    })
</script>
<table>
<tr>
    <th class="text-center"><i class="ti ti-checkbox"></i></th>
    <th class="text-center"><i class="ti ti-edit"></i></th>
    <th class="text-center"><i class="ti ti-eye"></i></th>
    <th>Invoiced Date Time</th>
    <th>Invoice No</th>
    <th>Customer</th>
    <th>Sales Rep</th>
    <th>Invoice Total</th>
    <th>Balance Due</th>
    <th>Status</th>
    <th class="text-center">Action</th>
</tr>
<?php
if($count > 0)
{
    foreach($invData as $row)
    {
        ?>    
        <tr>
            <td>
                <input type="checkbox" name="invoice[]" value="<?=$row["IHID"]?>" class="form-check">
            </td>
            <td class="text-center">
                <?php
                    if($row["InvStat"]!=0 && $row["InvStat"]!=5 && $row["Inv_Type"]==1)
                    {
                        ?>
                        <a href="../Public/editwholesaleinovice.php?INVID=<?=$row["IHID"]?>" class="btn btn-primary" target="_blank">Edit</a>
                        <?php
                    } 
                    else
                    {
                        ?>
                        <button class="btn btn-primary" disabled cursor="not-allowed">Edit</button>
                        <?php
                    }
                
                ?>
                
            </td>
            <td class="text-center">
                <a href="../Public/view-wholesaleinovice.php?INVID=<?=$row["IHID"]?>" class="btn btn-primary">View</a>
            </td>
            <td><?php echo $row['InvEndTime'];?></td>
            <td><?php echo $row['BillNo'];?></td>
            <td>
                <?=$row["CustName"]?><br>
                <?=$row["CustContact"]?>
            </td>
            <td><?=$row["SalesmansName"]?></td>
            <td><?=$row["NetAmount"]?></td>
            <td>
                <?php 
                $cus_id=$row["CTID"];
                $sql2="SELECT SUM(cc.CreditAmount) AS total_credit, 
                SUM(cc.DebitAmount) AS total_debit FROM `creditcustomer` cc 
                WHERE cc.invoice_header_id='$row[IHID]' AND cc.CreditStat=1 GROUP BY cc.invoice_header_id;";
                $dueData = $dbObj->getData($sql2);
                $count=count($dueData);
                if($count > 0)
                {
                    $pending=$dueData[0]["total_credit"]-$dueData[0]["total_debit"];
                    if($row["InvStat"]==1)
                    {
                        if(0 > $pending)
                        {
                            $row["InvStat"]=2;
                        }

                        if($pending > 0)
                        {
                            $row["InvStat"]=3;
                        }
                    }
                }
                else
                {
                    $pending=0;
                }
                ?>
                <!-- <?="Customer ID ".$cus_id."<br>"?>
                <?="Invoice ID ".$row['IHID']."<br>"?> -->
                <?=number_format((float)$pending, 2,'.',',')?>
            </td>
            <td>
                <?php 
                if($row["InvStat"]==1)
                {
                    ?>
                    <span class="badge bg-success">Finalized</span>
                    <?php
                }
                elseif($row["InvStat"]==0)
                {
                    ?>
                    <span class="badge bg-danger">Cancelled</span>
                    <?php
                }
                elseif($row["InvStat"]==3)
                {
                    ?>
                    <span class="badge bg-warning">Due Today</span>
                    <?php
                }
                elseif($row["InvStat"]==2)
                {
                    ?>
                    <span class="badge bg-warning" >Overpaid</span>
                    <?php
                }
                elseif($row["InvStat"]==5)
                {
                    ?>
                    <span class="badge bg-danger" >Claim Bill</span>
                    <?php
                }
                ?>
            </td>
            <td>
                <?php 
                //get receipts
                if($row["Inv_Type"]==2)
                {
                    $sql_1 = "SELECT * FROM shopreceipts WHERE ReceiptStat = 1 AND shop_id='$shop_id' AND RecieptType=1;";
                    $receiptData = $dbObj->getData($sql_1);
                    if(count($receiptData) > 0)
                    {
                        foreach($receiptData as $row_1)
                        {
                        $receipt_file_name = $row_1['ReceiptPath'];
                        $receipt_name = $row_1['receiptName'];
                        ?>
                        <a href="../Receipts/<?php echo $receipt_file_name;?>?invoice_id=<?php echo $row['IHID'];?>&invoiceList=1" class="mt-2 btn btn-info mt-1"><i class="ti ti-printer"></i> <?php echo $receipt_name;?></a>
                        <?php 
                        }//foreach 1
                    }
                    else
                    {
                        //80mm receipt, or the A4 invoice when this shop is set to print A4
                        $receiptFormatObj = new ReceiptFormat();
                        $retail_receipt = $receiptFormatObj->getTemplate($shop_id, ReceiptFormat::TYPE_RETAIL);
                        $retail_receipt_name = ($receiptFormatObj->getShopFormat($shop_id) == ReceiptFormat::FORMAT_A4) ? "A4 Invoice" : "Retail Invoice";
                        ?>
                        <a href="../Receipts/<?php echo $retail_receipt;?>?invoice_id=<?php echo $row['IHID'];?>&invoiceList=1" class="mt-2 btn btn-info mt-1"><i class="ti ti-printer"></i> <?php echo $retail_receipt_name;?></a>
                        <?php

                    }
                }
                else
                {
                    $sql = "SELECT * FROM shopreceipts WHERE ReceiptStat = 1 AND shop_id='$shop_id' AND RecieptType=2";
                    $dbObj = new DBTransactions();
                    $dbData = $dbObj->getData($sql);
                    $invoice = empty($dbData) ? "wholesaleInvoice.php" : $dbData[0]['ReceiptPath'];
                    ?> </br>
                    <a href="../Receipts/<?=$invoice?>?invoice=<?php echo $row['IHID'];?>&invoiceList=1" class="mt-2 btn btn-primary mt-1">Wholesale Receipt</a>
                    <?php 
                    
                }
                ?>
                
                
            </td>
        </tr>
        <?php
    }//foreach
}
else
{
    ?>
    
    <tr>
        <td colspan="11">
            <span class="w-100 text-danger text-center d-block">No Results Found</span>
        </td>
    </tr>
    <?php
}
?></table>