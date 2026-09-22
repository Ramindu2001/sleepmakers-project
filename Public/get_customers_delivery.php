<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';
$dbObj = new DBTransactions();



if(isset($_GET["customer_id"]))
{
    $customer_id=$_GET["customer_id"];
    $sql = "SELECT * FROM invoiceheader WHERE customers_CTID='$customer_id'";
    $itemData = $dbObj->getData($sql);
    if(count($itemData)==0)
    {
        ?>
        <h1 class="text-danger text-center">
            No Invoices Found
        </h1>
        <?php
    }
    else
    {
        ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th class="text-center"><i class="ti ti-edit"></i></th>
                        <th>Invoiced Date Time</th>
                        <th>Invoice No</th>
                        <th>Customer</th>
                        <th>Sales Rep</th>
                        <th>Delivery Charge</th>
                        <th>Invoice Total</th>
                        <th>Balance Due</th>
                        <th>Status</th>
                    </tr>
                </thead>
        <?php
        $sql = "SELECT * FROM invoiceheader ih 
        LEFT JOIN salesmans sm ON sm.SLID=ih.Salesmans_SLID
        LEFT JOIN customers c ON c.CTID=ih.customers_CTID
        WHERE ih.customers_CTID = '$customer_id' AND ih.InvStat=1 ORDER BY ih.IHID DESC LIMIT 50;";
        $dbObj = new DBTransactions();
        $invData = $dbObj->getData($sql);
        foreach ($invData as $row) 
        {
            ?>
            <tr>
                <td class="text-center">
                    <input type="checkbox" name="invoice_id[]" id="" value="<?=$row["IHID"]?>">                    
                </td>
                <td><?php echo $row['InvEndTime'];?></td>
                <td><?php echo $row['BillNo'];?></td>
                <td><?=$row["CustName"]?></td>
                <td><?=$row["SalesmansName"]?></td>
                <td><?=number_format($row["deliveryCharge"],2,'.')?></td>
                <td><?=number_format($row["NetAmount"],2,'.')?></td>
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
            </tr>
            <?php
        }
        ?>
            </table>
        </div>
        <?php
    }
    
}
else
{
    ?>
    <h1 class="text-danger text-center">
        Select a valid customer
    </h1>
    <?php
    
}