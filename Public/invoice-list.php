<?php 
include "../Includes/includes.php";
include '../Includes/authcheck.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
 include '../View/head.php';
 // include '../View/loader.php';
  ?>
  <style>
    .table>:not(caption)>*>* 
    {
        padding: 10px;
    }
    td 
    {
        padding: 5px;
    }
    th 
    {
        padding: 10px;
    }
  </style>
</head>
<body>
<?php 
    include '../View/modals/SysFeatures.php';
    $user_id = $_SESSION['user_id'];
    $shop_id = $_SESSION['shop_id'];
    $shopObj=new Shop();
    $hasPrescription=$shopObj->hasPrescription($shop_id);
?>
<!--  Body Wrapper -->
<div class="h-100vh">
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
        <!-- Sidebar Start -->
        <?php 
            include '../View/sidebar.php';
            $feature_id=56;
            include '../Includes/viewPermission.php';
        ?>
        <!--  Sidebar End -->
        <!--  Main wrapper -->
        <div class="body-wrapper">
            <!--  Header Start -->
            <?php 
                include '../View/header.php';
            ?>
            <!--  Header End -->
            <div class="container-fluid">
                
                    <div class="card">
                        <div class="card-header row">
                            
                            <form action="" method="get">
                                <div class="col-md-12 row mb-2">
                                    <div class="col-md-3 pt-3">
                                        <label for="fdate" class="form-label">From</label>
                                        <input type="date" name="fdate" id="fdate" <?php 
                                        if(isset($_GET["fdate"]))
                                        {
                                            ?>
                                            value="<?=$_GET["fdate"]?>"
                                            <?php
                                        }
                                        ?> class="form-control">
                                    </div>
                                    <div class="col-md-3 pt-3">
                                        <label for="tdate" class="form-label">To</label>
                                        <input type="date" name="tdate" id="tdate" <?php 
                                        if(isset($_GET["tdate"]))
                                        {
                                            ?>
                                            value="<?=$_GET["tdate"]?>"
                                            <?php
                                        }
                                        ?> class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="submit" class="btn btn-primary mt-5" value="Filter">
                                    </div>
                                </div>    
                            </form>
                            <div class="col-md-6"><h5>Invoice list</h5></div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-end">
                                    <form action="../Controller/bulkInvoice-Controller.php" method="POST">
                                        <label class="form-label me-3">No of Results: <span id="results"></span></label>
                                    <button type="submit" name="bulkInvoice" class="btn btn-primary">
                                        Bulk Print Invoice
                                    </button>
                                </div>
                            </div>                        
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-10">
                                  <label for="search_invoice"  class="form-label">Invoice No</label> 
                                    <input type="text" id="search_invoice" class="form-control" placeholder="Invoice No / Customer Name / Customer Contact / Salemen">  
                                </div>
                                <div class="col-md-2">
                                    <div class="btn btn-primary mt-4" id="invoiceSrchBtn">Search</div>
                                </div>
                            </div>
                            
                            
                            <div  id="tbl_invoice_list">
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
                                        $shop_id = $_SESSION['shop_id'];
                                        $sql = "SELECT * FROM invoiceheader ih 
                                                LEFT JOIN salesmans sm ON sm.SLID=ih.Salesmans_SLID
                                                LEFT JOIN customers c ON c.CTID=ih.customers_CTID
                                                WHERE ih.shop_SHID = '$shop_id' ORDER BY ih.IHID DESC LIMIT 50;";
                                        if(isset($_GET["fdate"]) && isset($_GET["tdate"]))
                                        {
                                            $fdate = $_GET["fdate"];
                                            $tdate = $_GET["tdate"];
                                            $sql = "SELECT * FROM invoiceheader ih 
                                                    LEFT JOIN salesmans sm ON sm.SLID=ih.Salesmans_SLID
                                                    LEFT JOIN customers c ON c.CTID=ih.customers_CTID
                                                    WHERE ih.shop_SHID = '$shop_id' AND ih.EffectiveDate BETWEEN '$fdate' AND '$tdate' ORDER BY ih.IHID DESC;";

                                        }
                                        $dbObj = new DBTransactions();
                                        $invData = $dbObj->getData($sql);
                                        $count=count($invData);
                                        ?>
                                        <script>
                                            $(document).ready(function(){
                                                $("#results").text("<?=$count?>")
                                            })
                                        </script>
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
                                                        
                                                        if($edit==1 || $userType==1)
                                                        {
                                                            if($row["InvStat"]!=0 && $row["InvStat"]!=5 && $row["Inv_Type"]==1)
                                                            {
                                                                ?>
                                                                <a href="../Public/editwholesaleinovice.php?INVID=<?=$row["IHID"]?>" class="btn btn-primary" target="_blank">Edit Invoice</a>
                                                                <?php
                                                            } 
                                                            else
                                                            {
                                                                ?>
                                                                <button class="btn btn-primary" disabled cursor="not-allowed" >Edit Invoice</button>
                                                                <?php
                                                            }
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
                                                        elseif($row["InvStat"]==6)
                                                        {
                                                            ?>
                                                            <span class="badge bg-danger" >Claim BillS </span>
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
                                        
                                    ?>
                                </table>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
    <!-- footer Start  -->
    <?php include '../View/footer.php';?> 
    <!-- footer End  -->
    <script>
    $(function () {
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
    </script>
    <script src="../Assets/jquery/invoicelist.js"></script>
    <script>
        $(document).ready(function(){
            if ($(window).width() >= 1099) 
            {
                $('.left-sidebar').css("margin-left","-270px");
            }
            $('#headerCollapse2').css("display","block");
            $('#headerCollapse3').css("display","none");
            $('.body-wrapper').css("margin-left","0");
            $("#side-closes").css("display","block");
            $(".app-header").css("width","100%");
    
        });
    </script>
    <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
</body>

</html>


