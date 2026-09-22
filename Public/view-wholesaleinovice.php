<?php 

include '../Includes/includes.php';
include '../Includes/authcheck.php';

$user_id = $_SESSION['user_id'];
$shop_id = $_SESSION['shop_id'];
//check cash counter

?>

<!doctype html>
<html lang="en">

<head>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <?php 
  $invoice=$_GET["INVID"];
  $dbObj = new DBTransactions();
  //Invoice data
  $sql_1 = "SELECT ih.*, ih.shop_SHID AS Invoiceshop_SHID,c.CTID AS CustomerID, c.CustName As CustomerName, ih.Salesmans_SLID AS Salesmans_SLID, c.CustContact AS CustContact, c.CustAddress AS CustAddress, u.UserName AS officer, sm.SalesmansName AS Salesmen FROM `invoiceheader` ih
  INNER JOIN customers c ON c.CTID=ih.customers_CTID
  INNER JOIN user u on u.USID=ih.user_USID
  INNER JOIN salesmans sm ON sm.SLID=ih.Salesmans_SLID
  WHERE ih.IHID= '$invoice';";
 
 $invoiceData = $dbObj->getData($sql_1);
 $invoiceCount = count($invoiceData);
 $customers_CTID  = $invoiceData[0]['CustomerID'];
 $Salesmans_SLID  = $invoiceData[0]['Salesmans_SLID'];
 $cus_name = $invoiceData[0]['CustomerName'];
 $cus_contact = $invoiceData[0]['CustContact'];
 $CustAddress = $invoiceData[0]['CustAddress'];
 $officer = $invoiceData[0]['officer'];
 $Salesman = $invoiceData[0]['Salesmen'];
 $BillNo = $invoiceData[0]['BillNo'];
 $date = $invoiceData[0]['EffectiveDate'];
 $GrossAmount = $invoiceData[0]['GrossAmount'];
 $remarks = $invoiceData[0]['remarks'];
 $lineDiscount = $invoiceData[0]['lineDiscount'];
 $FixedDiscount = $invoiceData[0]['FixedDiscount'];
 $DiscountAmount = $invoiceData[0]['DiscountAmount'];
 $deliveryCharge = $invoiceData[0]['deliveryCharge'];
 $otherCharge = $invoiceData[0]['otherCharge'];
 $NetAmount = $invoiceData[0]['NetAmount'];
 $print_count = $invoiceData[0]['print_count'];
 $excessAmount = $invoiceData[0]['excessAmount'];
 $returnAmount = $invoiceData[0]['returnAmount'];
 $EffectiveDate = $invoiceData[0]['EffectiveDate'];
 $Invoiceshop_SHID = $invoiceData[0]['Invoiceshop_SHID'];

 if($Invoiceshop_SHID != $shop_id || $invoiceCount == 0)
 {
    ?>
    <script>
    // //window.location="../Public/invoice-list.php";
window.history.back()
    </script>
    <?php
 }
 
 //print date time
 date_default_timezone_set("Asia/Colombo");
 $print_date = date("Y-m-d");
 $EffectiveDate = date("d-m-Y", strtotime($EffectiveDate));
 $print_time = date("H:i:s");
  include '../View/head.php';
//   // include '../View/loader.php';
  include '../View/modals/add-customer-wholesale.php';
  include "../View/modals/add-products.php";
  $wholesaleObj= new wholesale_invoice();
  $shopObj=new Shop();
  $SelectRow=$wholesaleObj->SelectRow($shop_id);
  $row_number = 0;
  $invoiceTot = count($SelectRow);
  $previous=$wholesaleObj->previouseInvoice($shop_id,$invoice);
  if(isset($previous[0]["IHID"]))
  {
    $previousInv=$previous[0]["IHID"];
  }
  else
  {
    $previousInv=0;
  }
  
  $next= $wholesaleObj->nextInvoice($shop_id,$invoice);
  
  if(isset($next[0]["IHID"]))
  {
    $nextInv=$next[0]["IHID"];
  }
  else
  {
    $nextInv=0;
  }
  $maxminInvoice= $wholesaleObj->maxminInvoice($shop_id);
  foreach ($SelectRow as $select) 
  {
    $row_number++; 
    if ($select['IHID'] == $invoice) {
        // We found the invoice, break out of the loop
        break;
    }
  }
  $hasMinus=$shopObj->hasMinus($shop_id);
  $hasPrescription=$shopObj->hasPrescription($shop_id);
  $hasSalesman=$shopObj->hasSalesman($shop_id);
  $minus=0;
  $prescription=0;
  $saleaman=0;
  if($hasMinus==1)
  {
      $minus=1;
  }
  else
  {
      $minus=0;
  }
  if($hasSalesman==1)
  {
      $saleaman=1;
  }
  else
  {
      $saleaman=0;
  }
  if($hasPrescription==1)
  {
      $prescription=1;
  }
  else
  {
      $prescription=0;
  }
  if($prescription==1)
  {
    include "../View/modals/prescription.php";
  }
  $doc=$wholesaleObj->select_docno($shop_id);
    if(count($doc)==0)
    {
        $inser_doc=$wholesaleObj->insert_doc_no($shop_id);
        $doc=$wholesaleObj->select_docno($shop_id);
    }
    $ws_no=$doc[0]["ws_no"] + 1;
    $ws_no=$wholesaleObj->getSequence($ws_no);
    $year=date("y");
    $salesetings=$wholesaleObj->getSaleSettings($shop_id);
    if(count($salesetings)>0)
    {
        if(isset($salesetings[0]["WbillNoHeader"]))
        {
            $ws_no=$salesetings[0]["WbillNoHeader"]."-".$ws_no;
        }
        else
        {
            $ws_no="INV-".$ws_no;
        }
    }
    else
    {
        $ws_no="INV-".$ws_no;
    }
    
  $custObj= new Customer;
  $salemObj= new Salesman;
  $customer=$custObj->getOneCustomer($customers_CTID);
  $salesman=$salemObj->getOneSalesman($Salesmans_SLID);
  ?>
  <style>
    .btn-group>.btn:not(:last-child):not(.dropdown-toggle)
    {
        border-right: 2px solid #0042ff !important;
    }
  </style>
</head>

<body>
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
        <style>
            .table>:not(caption)>*>* 
            {
                padding: 10px;
                border: 1px solid #dfdfdf;
            }
            input[readonly]
            {
                background-color: rgb(235, 235, 235) !important;
                border-color: rgb(235, 235, 235) !important;
            }
            .card-title
            {
                font-size: 13px;
            }
            .btn-primary,
            .btn-danger
            {
                font-size: 10px;
            }
        </style>
        <!--  Header End -->

        <div class="container-fluid">
            <!-- messages -->
        <div class="container">
        <?php
            $shop_id = $_SESSION['shop_id'];
            $date=date('Y-m-d');
            ?>
        </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="col-md-12">
                    <?php 
                    $cus_id=$customers_CTID;
                    $IHIDHeader=$invoiceData[0]["IHID"];
                    $sql2="SELECT SUM(cc.CreditAmount) AS total_credit, 
                    SUM(cc.DebitAmount) AS total_debit FROM `creditcustomer` cc 
                    WHERE cc.invoice_header_id='$IHIDHeader' AND cc.CreditStat=1 GROUP BY cc.invoice_header_id;";
                    $dueData = $dbObj->getData($sql2);
                    $count=count($dueData);
                    if($count > 0)
                    {
                        $pending=$dueData[0]["total_credit"]-$dueData[0]["total_debit"];
                        if($invoiceData[0]["InvStat"]==1)
                        {
                            if(0 > $pending)
                            {
                                $invoiceData[0]["InvStat"]=2;
                            }

                            if($pending > 0)
                            {
                                $invoiceData[0]["InvStat"]=3;
                            }
                        }
                    }
                    else
                    {
                        $pending=0;
                    }
                        if($invoiceData[0]["InvStat"]==1)
                        {
                            ?>
                            <div class="alert alert-success text-center"><b>Finalized</b></div>
                            <?php
                        }
                        elseif($invoiceData[0]["InvStat"]==0)
                        {
                            ?>
                            <div class="alert  alert-danger  text-center"><b>Cancelled</b></div>
                            <?php
                        }
                        elseif($invoiceData[0]["InvStat"]==3)
                        {
                            ?>
                            <div class="alert customize-alert alert-dismissible border-warning text-warning fade show remove-close-icon text-center"><b>Due Today</b></div>
                            <?php
                        }
                        elseif($invoiceData[0]["InvStat"]==2)
                        {
                            ?>
                            <div class="alert customize-alert alert-dismissible border-warning text-warning fade show remove-close-icon text-center" ><b>Overpaid</b></div>
                            <?php
                        }
                        elseif($invoiceData[0]["InvStat"]==5)
                        {
                            ?>
                            <div class="alert customize-alert alert-dismissible border-danger text-danger fade show text-center"><b>Claim Bill</b></div>
                            <?php
                        }
                        ?>
                    </div>
                    <div class="card" style="border-right: 0.5px solid #c5c5c5; border-bottom: 0.5px solid #c5c5c5;">
                        <div class="card-header row" style=" padding-left: 12px; padding-top: 0; padding-bottom: 0;">
                            <?php 
                            include "../View/sub-header.php";
                            ?>
                            <div class="col-md-6 p-1 row">
                                <div class="col-md-4" style="border-right: 1px solid #c5c5c5;">
                                    <h5  class="card-title fw-semibold" style=" padding: 10px; text-align: center; font-size: 12px !important; ">Invoice No <?=$BillNo?> </h5>
                                </div>
                                <div class="col-md-8" style="border-right: 1px solid #c5c5c5;">
                                    <?php 
                                    if($invoiceData[0]['InvStat']!=0 && $invoiceData[0]['InvStat']!=5)
                                    {
                                        if($edit==1 || $userType==1)
                                        {
                                            ?>
                                            <a href="../Controller/cancelwholesaleinovice.php?INVID=<?=$invoice?>" class="btn btn-danger">Cancel</a>
                                            <?php 
                                            if($invoiceData[0]["Inv_Type"]==1)
                                            {
                                                ?>
                                                <a href="../Public/editwholesaleinovice.php?INVID=<?=$invoice?>" class="btn btn-primary">Edit</a>
                                                <?php
                                            }
                                        }
                                    }
                                    ?>
                                    
                                    <a href="javascript:void;" class="btn btn-primary" id="print-invoice">Print</a>
                                    <a href="javascript:void;" class="btn btn-primary" id="print-delivery">Delivery Note</a>
                                    <a href="javascript:void;" class="btn btn-primary" id="PDF-invoice">PDF</a>
                                    <!-- <button class="btn btn-success" class="btn btn-primary" onclick="generatePDF()">Download PDF</button> -->
                            
                                </div>
                            </div>
                            <div class="col-md-6 p-1 row">
                                <div class="col-md-6"></div>
                                <div class="col-md-6 d-flex justify-content-end">
                                    <div class="btn-group me-2 mb-2" role="group" aria-label="First group">                                        
                                        <a href="../Public/view-wholesaleinovice.php?INVID=<?=$maxminInvoice[0]["maxIHID"]?>" class="btn btn-primary">
                                            <i class="ti ti-player-track-prev"></i>
                                        </a>
                                        <?php 
                                        if($nextInv==0)
                                        {
                                            ?>
                                            <button type="button" class="btn btn-primary" disabled>
                                                <i class="ti ti-player-skip-back"></i>
                                            </button>
                                            <?php 
                                        }
                                        else
                                        {
                                            ?>
                                            <a href="../Public/view-wholesaleinovice.php?INVID=<?=$nextInv?>" class="btn btn-primary">
                                                <i class="ti ti-player-skip-back"></i>
                                            </a>
                                            <?php
                                        }
                                        ?>
                                    </div>   
                                    <p class="w-30 text-center"> <?=$row_number?> / <?=$invoiceTot?></p>
                                    <div class="btn-group me-2 mb-2" role="group" aria-label="First group">
                                        <?php 
                                        if($previousInv==0)
                                        {
                                            ?>
                                            <button type="button" class="btn btn-primary" disabled>
                                                <i class="ti ti-player-skip-forward"></i>
                                            </button>
                                            <?php
                                        }
                                        else
                                        {
                                            ?>
                                            <a href="../Public/view-wholesaleinovice.php?INVID=<?=$previousInv?>" class="btn btn-primary">
                                                <i class="ti ti-player-skip-forward"></i>
                                            </a>
                                            <?php
                                        }
                                        ?>
                                        <a href="../Public/view-wholesaleinovice.php?INVID=<?=$maxminInvoice[0]["minIHID"]?>" class="btn btn-primary">
                                            <i class="ti ti-player-track-next"></i>
                                        </a>
                                    </div> 
                                </div>
                            </div>
                        </div>
                        <?php 
                        if($invoiceData[0]["Inv_Type"]==1)
                        {
                            $sql = "SELECT * FROM shopreceipts WHERE ReceiptStat = 1 AND shop_id='$shop_id' AND RecieptType=2";
                            $dbObj = new DBTransactions();
                            $dbData = $dbObj->getData($sql);
                            $invoices = empty($dbData) ? "wholesaleInvoice.php" : $dbData[0]['ReceiptPath'];
                        }
                        else
                        {
                            //80mm receipt, or the A4 invoice when this shop is set to print A4
                            $receiptFormatObj = new ReceiptFormat();
                            $invoices = $receiptFormatObj->getTemplate($shop_id, ReceiptFormat::TYPE_RETAIL);
                        }
                            
                        ?>
                        <div class="card-body">
                            <iframe src="../Receipts/<?=$invoices?>?invoice=<?=$invoice?>&Iframe=1" id="iframe" style="width:100%; height:100vh;" title="Iframe Example"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<div id="print"></div>
<!--  Body Wrapper End -->
    <!-- footer Start  -->
    <?php include '../View/footer.php';?> 
    <!-- footer End  -->
    <script src="../Assets/jquery/wholesale_invoice.js"></script>
    <script>
        $(document).ready(function() 
        {
           $("#print-invoice").click(function(){
                    var domain= window.location.hostname;
                    var strWindowFeatures = "location=yes,height=700,width=520,scrollbars=yes,status=yes";
                    var URL = "../Receipts/<?=$invoices?>?invoice=<?=$invoice?>&print=1";
                    var win = window.open(URL, "_blank", strWindowFeatures);
           });
           $("#print-delivery").click(function(){
                    var domain= window.location.hostname;
                    var strWindowFeatures = "location=yes,height=700,width=520,scrollbars=yes,status=yes";
                    var URL = "../Receipts/deliveryNote.php?invoice_id=<?=$invoice?>&print=1";
                    var win = window.open(URL, "_blank", strWindowFeatures);
           });
           $("#PDF-invoice").click(function(){
                    var domain= window.location.hostname;
                    var strWindowFeatures = "location=yes,height=700,width=520,scrollbars=yes,status=yes";
                    var URL = "../Receipts/<?=$invoices?>?invoice=<?=$invoice?>&pdf=1?invNo=<?=$BillNo?>";
                    var win = window.open(URL, "_blank", strWindowFeatures);
           });
        });
    </script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
</body>
</html>