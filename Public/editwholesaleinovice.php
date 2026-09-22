<?php 

include '../Includes/includes.php';
include '../Includes/authcheck.php';

$user_id = $_SESSION['user_id'];
$shop_id = $_SESSION['shop_id'];
//check cash counter

$shopObj=new Shop();
if($shopObj->hascounter($shop_id)==1)
{
    //check cash counter
    if(!CounterCheck($user_id, $shop_id))
    {
        $_SESSION['status']=3;
        ?>
        <script>
            window.location="../Public/home.php";
        </script>
        <?php
    }//no counter
}
function CounterCheck($user_id, $shop_id)
{
    //get current date time
    date_default_timezone_set("Asia/Colombo");
    $current_date = date("Y-m-d");

    $sql = "SELECT * FROM cashcounter WHERE user_USID=".$user_id." AND shop_SHID=".$shop_id." AND CounterStat = 1 AND CounterDate = '".$current_date."';";

    $dbObj = new DBTransactions();
    $dbData = $dbObj->getData($sql);

    if(!empty($dbData))
    {
        return true;
    }//has counter
    else
    {
        return false;
    }//no counter
}//counter check
?>

<!doctype html>
<html lang="en">

<head>
  <?php
    $invoice=$_GET["INVID"];
    $dbObj = new DBTransactions();
    $hasbatchNo=$shopObj->hasbatchNo($shop_id);
    //Invoice data
    $sql_1 = "SELECT ih.*, ih.shop_SHID AS Invoiceshop_SHID,c.CTID AS CustomerID, c.CustName As CustomerName, ih.Salesmans_SLID AS Salesmans_SLID, c.CustContact AS CustContact, c.CustAddress AS CustAddress, u.UserName AS officer, sm.SalesmansName AS Salesmen FROM `invoiceheader` ih
    LEFT JOIN customers c ON c.CTID=ih.customers_CTID
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
   $PercentDiscount = $invoiceData[0]['PercentDiscount'];   
   $InvoicediscountType=$invoiceData[0]["discountType"];
   $FixedDiscount = $invoiceData[0]['FixedDiscount'];
   $DiscountAmount = $invoiceData[0]['DiscountAmount'];
   $deliveryCharge = $invoiceData[0]['deliveryCharge'];
   $otherCharge = $invoiceData[0]['otherCharge'];
   $NetAmount = $invoiceData[0]['NetAmount'];
   $print_count = $invoiceData[0]['print_count'];
   $excessAmount = $invoiceData[0]['excessAmount'];
   $returnAmount = $invoiceData[0]['returnAmount'];
   $is_delivery = $invoiceData[0]['is_delivery'];
   $deliveryPartner = $invoiceData[0]['deliveryPartner'];
   $EffectiveDate = $invoiceData[0]['EffectiveDate'];
   $Invoiceshop_SHID = $invoiceData[0]['Invoiceshop_SHID'];
  
   if($Invoiceshop_SHID != $shop_id || $invoiceCount == 0)
   {
      ?>
      <script>
    //   //window.location="../Public/invoice-list.php";
window.history.back()
      </script>
      <?php
   }
   
   //print date time
   date_default_timezone_set("Asia/Colombo"); 
  include '../View/head.php';
  // include '../View/loader.php';
  include '../View/modals/add-customer-wholesale.php';
  include "../View/modals/add-products.php";
  $wholesale= new wholesale_invoice();
  $shopObj=new Shop();
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
    $ws_no=$BillNo;
    $year=date("y");
    
  $custObj= new Customer;
  $salemObj= new Salesman;
  $Cus_id=$invoiceData[0]['customers_CTID'];
  $Salesmans_SLID=$invoiceData[0]['Salesmans_SLID'];
  $customer=$custObj->getOneCustomerWholesale($Cus_id);
  $salesman=$salemObj->getOneSalesman($Salesmans_SLID);
  $CustName=$customer[0]["CustName"];
  $CustContact=$customer[0]["CustContact"];
  $string=$CustName." - ".$CustContact;
  ?>
  <style>
    @media (max-width: 1200px) 
    {
       .table>:not(caption)>*>* {
            padding: 2px !important;
        } 
    }
    
    .body-wrapper>.container-fluid, .body-wrapper>.container-lg, .body-wrapper>.container-md, .body-wrapper>.container-sm, .body-wrapper>.container-xl, .body-wrapper>.container-xxl
    {
        padding: 12px;
    }
    .card-header 
    {
        background: #fff;
        border-bottom: 1px solid #f5f5f5;
        padding: 10px;
    }
    .select2-container--default .select2-selection--single
    {
        border: none !important;
    }
    input[disabled]
    {
        cursor: not-allowed;
    }
    .select2-container--default .select2-selection--single {
    border-color: #aaa;
}

.select2-container--default .select2-selection--single:focus,
.select2-container--default .select2-selection--single.select2-selection--focus,
.select2-selection:focus .select2 {
    border:1px solid red !important;
    border-color: red !important;
}
    button:focus,
    a:focus,
    .btn:focus,
    .form-control:focus,
    .form-select:focus,
    .select2:focus,
    input:focus,
    .select2-container--default .select2-selection--single:focus
    {
        border-color: #ff0000 !important; /* Change this to your desired border color */
        outline: none !important; /* Optional: Removes the default focus outline */
    }
    .btn-danger:focus
    {
        border: 3px solid #5D87FF !important;
    }
    span.select2-dropdown.select2-dropdown--below {
    margin-top: -13px;
    }
    .select2-container--default{
        padding: 8px 16px;
        font-size: 0.875rem;
        font-weight: 400;
        line-height: 1.5;
        color: #5A6A85;
        background-color: transparent;
        background-clip: padding-box;
        border: 1px solid #DFE5EF;
        appearance: none;
        border-radius: 7px;
    }
    .focused-border {
        border:1px solid red !important;
    border-color: red !important;
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
    if($edit==1 || $userType==1)
    {

    }
    else
    {
        ?>
        <script>
            window.location.href="../Public/home.php";
        </script>
        <?php
    }
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
        <input type="hidden" name="" id="prescriptionFlag" value="<?=$prescription?>">
            <h5 class="card-title fw-semibold mb-3">Edit Invoice</h5>
            <div class="row">
                <div class="col-md-12">
                    <div class="card p-2">
                        <div class="card-body">
                            <form action="../Controller/wholesaleinvoicecontrol.php" method="post" id="wholesale-invoice-form">
                                <div class="row">
                                    <div class="col-md-3">
                                        <?php 
                                        $shopObj = new Shop();
                                        ?>
                                            <span id="p_customer" class="mt-2"><br><small>Customer </small><b><?=$invoiceData[0]["CustomerName"]?></b><br></span>
                                            <input type="hidden" name="customer_id" id="customer_id" value="<?=$customers_CTID?>">
                                            <input type="hidden" name="customer_excess_amount" id="customer_excess_amount" value="">
                                            <span id="excess_amount" class="mt-2 md-2"> </span><br>
                                            <a href="javascript:void(0)" class="btn btn-primary" id="use_exccess" style="display:none;">Use Excess Amount</a><br>
                                        <?php 
                                        if($saleaman==1)
                                        {
                                            ?>
                                            <label for="cmb_salesman" class="form-label w-100">Salesman Name</label>
                                            <select name="cmb_salesman" id="cmb_salesman" class="form-select"></select>
                                            <span id="p_salesman" class="mt-3"><br><small>Salesman </small><b><?=$salesman[0]["SalesmansName"]?></b><br></span>
                                            
                                            <?php
                                        }
                                        ?>
                                            <input type="hidden" name="salesman_id" id="salesman_id" value="<?=$Salesmans_SLID?>">
                                    </div>
                                    <div class="col-md-9 text-end">
                                        <h5 class="text-end mt-2"><b>Invoice No: <?=$ws_no?></b></h5>
                                        <input type="hidden" name="InvoiceID" value="<?=$invoice?>" readonly>                                                     
                                    </div>
                                    <div class="col-md-12 mt-5">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Item Information <span class="text-danger">*</span></th>
                                                        <?php 
                                                        if($prescription==1)
                                                        {
                                                            ?>
                                                            <th>Item Description </th>
                                                            <?php
                                                        }?>
                                                        <th style="min-width: 200px;">Batch No <span class="text-danger">*</span></th>
                                                        <th>Av.Qty <span class="text-danger">*</span></th>
                                                        <th>Qty <span class="text-danger">*</span></th>
                                                        <th>Rate <span class="text-danger">*</span></th>
                                                        <th>Discount Type</th>
                                                        <th>Discount</th>
                                                        <th>Total <span class="text-danger">*</span></th>
                                                        <th>Action 
                                                        <a href="javascript:void(0)" class="add btn btn-primary p-2 ms-2" id="add"><i class="ti ti-plus"></i></a></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbody">
                                                    <?php 
                                                    $i=1;
                                              
                                                     $sql_2 = "SELECT p.PDID AS productid, p.ItemName AS ItemName, p.Barcode AS ItemCode, p.ProdDescription AS Description, i.INID AS InveID, 
                                                     id.item_des, id.batch_no,i.CurrentQty,id.SellQty,p.SellingUnit AS Unit,id.PercentDiscount AS PercentDiscount,id.DirectDiscount AS DirectDiscount,id.SoldAmount,UnitPrice, id.disc_type,id.* FROM `invoicedetails` id 
                                                    LEFT JOIN products p ON p.PDID=id.products_PDID
                                                    LEFT JOIN invoiceheader IHD ON IHD.IHID = id.InvoiceHeader_IHID 
                                                    LEFT JOIN inventory i ON id.Inventory_INID = i.INID
                                                    WHERE id.InvoiceHeader_IHID='$invoice';";
                                                    
                                                    $shopData = $dbObj->getData($sql_2);
                                                    foreach ($shopData as $row) 
                                                    {
                                                        $avlQty=$row["CurrentQty"];
                                                        if($hasbatchNo==0)
                                                        {
                                                            $sql_2 = "SELECT *, SUM(i.CurrentQty) AS avlQty FROM `inventory` i 
                                                           WHERE i.products_PDID='$row[productid]' GROUP BY i.products_PDID;";
                                                           
                                                           $shopData = $dbObj->getData($sql_2);
                                                           $avlQty=$shopData[0]["CurrentQty"];
                                                        }

                                                        ?>
                                                            <script>
                                                                // Listen for the opening of the Select2 dropdown and apply red border
                                                                (function(i, productid, barcode) {
                                                                $(document).ready(function () {
                                                                    // Initialize select2 for the current item
                                                                    $("#item" + i).select2({
                                                                        ajax: {
                                                                            url: '../AJAX/WholeSaleInvoice/getItems.php',
                                                                            dataType: 'json',
                                                                            delay: 250,
                                                                            data: function(params) {
                                                                                return {
                                                                                    search: params.term,
                                                                                    type: 'item_search'
                                                                                };
                                                                            },
                                                                            processResults: function(data) {
                                                                                return {
                                                                                    results: data
                                                                                };
                                                                            }
                                                                        },
                                                                        cache: true,
                                                                        placeholder: 'Search for Items',
                                                                        minimumInputLength: 1,
                                                                        width: '100%'
                                                                    });

                                                                    // Set predefined value for the current item
                                                                    var option = new Option(barcode, productid, true, true);
                                                                    $("#item" + i).append(option);
                                                                });
                                                            })(<?=$i?>, "<?=$row["productid"]?>", "<?=$row["ItemCode"]?> - <?=$row["ItemName"]?>");                                                                  
                                                            </script>
                                                            <?php 
                                                            if($row["PercentDiscount"]!=null)
                                                            {
                                                                $PercentDiscount=$row["PercentDiscount"];
                                                            }
                                                            else
                                                            {
                                                                $PercentDiscount="0.00";
                                                            }
                                                            if($row["DirectDiscount"]!=null)
                                                            {
                                                                $DirectDiscount=$row["DirectDiscount"];
                                                            }
                                                            else
                                                            {
                                                                $DirectDiscount="0.00";
                                                            }
                                                            if($row["disc_type"]==1)
                                                            {
                                                                $ldiscount=$PercentDiscount;
                                                            }
                                                            elseif($row["disc_type"]==2)
                                                            {
                                                                $ldiscount=$DirectDiscount;
                                                            }
                                                            else
                                                            {
                                                                $ldiscount="0.00";
                                                            }
                                                            ?>
                                                        <tr id="<?=$i?>" class="tr" data-example="<?=$i?>">
                                                            <td style="max-width:200px;">
                                                                <span class="text-danger" id="alert<?=$i?>"></span>
                                                                <select name="item_id[]" onchange="item_id(<?=$i?>)" id="item<?=$i?>" class="item form-control" data-item="<?=$row["productid"]?>">
                                                                    <option value=""></option>
                                                                </select>
                                                            </td>
                                                            <?php 
                                                            if($prescription==1)
                                                            {
                                                                ?>
                                                                <td>
                                                                    <input type="text" name="prodDes[]" id="prodDes<?=$i?>" class="form-control" value="<?=$row["item_des"]?>">
                                                                </td>
                                                                <?php
                                                            }?>
                                                            <td>
                                                                <select name="batch_id[]" id="batch_id<?=$i?>" class="form-select" onchange="batch(<?=$i?>)" readonly>
                                                                    <option value="<?=$row["InveID"]?>"><?=$row["batch_no"]?></option>
                                                                </select>                                                                
                                                                <input type="hidden" name="batch[]" id="batch<?=$i?>" class="form-control" value="<?=$row["batch_no"]?>" readonly>
                                                            </td>
                                                            <td>
                                                                <input type="text" name="avl_qty[]" id="avl_qty<?=$i?>" class="form-control text-end" value="<?=$avlQty?>" required readonly>
                                                            </td>
                                                            <td>
                                                                <span class="text-danger" id="alertqty<?=$i?>"></span>
                                                                <input type="text" name="qty[]" id="qty<?=$i?>" onkeyup="ttotal(<?=$i?>)" onkeydown="ttotal(<?=$i?>)" onkeypress="ttotal(<?=$i?>)" class="qty form-control text-end" value="<?=$row["SellQty"]?>" required >
                                                            </td>
                                                            <td>
                                                            <span class="text-danger" id="alertrate<?=$i?>"></span>
                                                                <input type="text" name="rate[]" id="rate<?=$i?>" class="rate form-control text-end" onkeyup="ttotal(<?=$i?>)" onkeydown="ttotal(<?=$i?>)" onkeypress="ttotal(<?=$i?>)" value="<?=$row["UnitPrice"]?>" required >
                                                                <input type="hidden" name="original_rate[]" id="original_rate<?=$i?>" class="form-control" readonly>
                                                            </td>
                                                            <td>
                                                                <select name="discountType[]" id="discountType<?=$i?>" onchange="ttotal(<?=$i?>)" class="discountType form-select" >
                                                                    <option value="1" <?php if($row["disc_type"]==1){echo "Selected";}?>>Percentage</option>
                                                                    <option value="2" <?php if($row["disc_type"]==2){echo "Selected";}?>>Flat Amount</option>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <input type="text" name="discount[]" id="discount<?=$i?>" class="discount form-control text-end" onkeyup="ttotal(<?=$i?>)" onkeydown="ttotal(<?=$i?>)" onkeypress="ttotal(<?=$i?>)"  value="<?=$ldiscount?>" >
                                                            </td>
                                                            <td>
                                                                <input type="text" name="totals[]" id="total<?=$i?>" value="<?=$row["SoldAmount"]?>" class="total form-control text-end" readonly>
                                                                <input type="hidden" name="original_total[]" id="original_total<?=$i?>" class="form-control" readonly>
                                                            </td>
                                                            <td>
                                                                <?php 
                                                                if($i==1)
                                                                {
                                                                    ?>
                                                                    <a href="javascript:void(0)" class="add btn btn-primary" id="add"><i class="ti ti-plus"></i></a>
                                                                    <?php
                                                                }
                                                                else
                                                                {
                                                                    ?>
                                                                    <div class="row">
                                                                        <div class="col-md-4  p-2">
                                                                          <a href="javascript:void(0)" class="add btn btn-primary p-2" id="add" style=" margin-right: 50px;"><i class="ti ti-plus"></i></a>  
                                                                        </div>
                                                                        <div class="col-md-4  p-2">
                                                                           <a href="javascript:void(0)" class="btn btn-danger p-2" id="remove"><i class='ti ti-trash'></i></a> 
                                                                        </div>
                                                                    </div>
                                                                    <?php
                                                                }
                                                                ?>
                                                            </td>
                                                        </tr>
                                                        <?php
                                                        $i++;
                                                    }
                                                    ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td 
                                                        
                                                        <?php 
                                                            if($prescription==1)
                                                            {
                                                                ?>
                                                                colspan="7"
                                                                <?php 
                                                            }
                                                            else
                                                            {
                                                                ?>
                                                                colspan="6"
                                                                <?php
                                                            }
                                                        ?>
                                                        rowspan="3">
                                                            <label for="details" class="form-label text-center w-100">Sale Details</label>
                                                            <textarea name="details" id="details" class="form-control" style="height:100px;"><?=$remarks?></textarea>
                                                            <script>
                                                                CKEDITOR.replace( 'details' );
                                                            </script>
                                                        </td>
                                                        <td class="text-end">
                                                            <b>Gross Amount</b>
                                                        </td>
                                                        <td colspan="2">
                                                            <input type="text" name="grossAmount" id="grossAmount" class="form-control text-end" onkeyup="grandTotal()" onkeydown="grandTotal()" onkeypress="grandTotal()" value="<?=$GrossAmount?>" readonly>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-end">
                                                            <b>Sale Discount Type</b>
                                                        </td>
                                                        <td colspan="2">
                                                            <select name="SaleDiscountType" id="SaleDiscountType" class="form-select" onchange="grandTotal()">
                                                                <option value="1" <?php if ($InvoicediscountType==1){echo"Selected";}?>>Percentage</option>
                                                                <option value="2" <?php if ($InvoicediscountType==2){echo"Selected";}?>>Flat Amount</option>                                                                
                                                            </select>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-end">
                                                            <b>Sale Discount</b>
                                                        </td>
                                                        <td colspan="2">
                                                            <input type="text" name="saleDiscount" id="saleDiscount" class="form-control text-end" onkeyup="grandTotal()" onkeydown="grandTotal()" onkeypress="grandTotal()" value="<?php if ($PercentDiscount !=null && $PercentDiscount!="0.00") { echo $PercentDiscount;}else if($FixedDiscount !=null && $FixedDiscount!="0.00"){ echo $FixedDiscount;}?>">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td 
                                                        
                                                        <?php 
                                                            if($prescription==1)
                                                            {
                                                                ?>
                                                                colspan="7"
                                                                <?php 
                                                            }
                                                            else
                                                            {
                                                                ?>
                                                                colspan="6"
                                                                <?php
                                                            }
                                                        ?>
                                                        >
                                                        <?php 
                                                        $sql_1 = "SELECT * FROM `invoice_remarks` WHERE invoiceheader_IHID= '$invoice' AND from_invoice=1;";                                                       
                                                       $invoice_remarks = $dbObj->getData($sql_1);
                                                       if(!empty($invoice_remarks[0]["remarks"]))
                                                       {
                                                            $invoice_remark=$invoice_remarks[0]["remarks"];
                                                       }
                                                       else
                                                       {
                                                            $invoice_remark="";
                                                       }
                                                       if(!empty($invoice_remarks[0]["IRID"]))
                                                       {
                                                            $IRID=$invoice_remarks[0]["IRID"];
                                                       }
                                                       else
                                                       {
                                                            $IRID="";
                                                       }
                                                        ?>
                                                            <label for="remark" class="form-label text-center w-100">Internal Remark</label>
                                                            <textarea name="remark" id="remark" class="form-control" style="height:70px;"><?=$invoice_remark?></textarea>
                                                            <input type="hidden" name="IRID" value="<?=$IRID?>">
                                                            
                                                        </td>
                                                        <td class="text-end">
                                                            <b>Total Discount</b>
                                                        </td>
                                                        <td colspan="2">
                                                            <input type="text" name="totalDiscount" id="totalDiscount" class="form-control text-end" value="<?=$DiscountAmount?>" readonly>
                                                            <input type="hidden" name="totalDiscountLine" id="totalDiscountLine" class="form-control" value="<?=$lineDiscount?>" readonly>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td 
                                                        
                                                        <?php 
                                                            if($prescription==1)
                                                            {
                                                                ?>
                                                                colspan="7"
                                                                <?php 
                                                            }
                                                            else
                                                            {
                                                                ?>
                                                                colspan="6"
                                                                <?php
                                                            }
                                                        ?>
                                                         class="text-end">
                                                         <div class="d-flex justify-content-end">
                                                            <label for="deliveryPartner" class="form-lable me-2"><b>Select Delivery Partner</b></label>&nbsp;&nbsp;&nbsp;
                                                            
                                                            <select name="deliveryPartner" id="deliveryPartner" class="form-select me-5 w-40">
                                                                <option value="">Select Delivery Partner</option>
                                                                <option <?php if($deliveryPartner=="Any Delivery"){ echo "selected";}?> value="Any Delivery">Any Delivery</option>
                                                                <option <?php if($deliveryPartner=="Fardar"){ echo "selected";}?> value="Fardar">Fardar</option>
                                                                <option <?php if($deliveryPartner=="Pick me"){ echo "selected";}?> value="Pick me">Pick me</option>
                                                                <option <?php if($deliveryPartner=="Imran"){ echo "selected";}?> value="Imran">Imran</option>
                                                                <option <?php if($deliveryPartner=="Other"){ echo "selected";}?> value="Other">Other</option>
                                                            </select>

                                                            <label for="deliveryNote" class="form-lable me-3"><b>Delivery Note</b></label>&nbsp;&nbsp;&nbsp;
                                                            <input type="checkbox" name="deliveryNote" id="deliveryNote" <?php if($is_delivery==1){echo "checked";}?> value="1">
                                                         </div>
                                                            
                                                        </td>
                                                        <td class="text-end">
                                                            <b>Delivery Charges</b>
                                                        </td>
                                                        <td colspan="2">
                                                            <input type="text" name="deliverycharges" id="deliverycharges" class="form-control text-end"  onkeyup="grandTotal()" onkeydown="grandTotal()" onkeypress="grandTotal()" value="<?=$deliveryCharge?>" >
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td 
                                                        
                                                        <?php 
                                                            if($prescription==1)
                                                            {
                                                                ?>
                                                                colspan="7"
                                                                <?php 
                                                            }
                                                            else
                                                            {
                                                                ?>
                                                                colspan="6"
                                                                <?php
                                                            }
                                                        ?>
                                                        ></td>
                                                        <td class="text-end">
                                                            <b>Other Charges</b>
                                                        </td>
                                                        <td colspan="2">
                                                            <input type="text" name="otherCharges" id="otherCharges" class="form-control text-end"  onkeyup="grandTotal()" onkeydown="grandTotal()" onkeypress="grandTotal()" value="<?=$otherCharge?>" >
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td 
                                                        
                                                        <?php 
                                                            if($prescription==1)
                                                            {
                                                                ?>
                                                                colspan="7"
                                                                <?php 
                                                            }
                                                            else
                                                            {
                                                                ?>
                                                                colspan="6"
                                                                <?php
                                                            }
                                                        ?>
                                                        ></td>
                                                        <td class="text-end">
                                                            <b>Excess Amount </b>
                                                        </td>
                                                        <td colspan="2">
                                                            <input type="text" name="excessamount" id="excessamount" class="form-control text-end" value="<?=$excessAmount?>" readonly>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td 
                                                        
                                                        <?php 
                                                            if($prescription==1)
                                                            {
                                                                ?>
                                                                colspan="7"
                                                                <?php 
                                                            }
                                                            else
                                                            {
                                                                ?>
                                                                colspan="6"
                                                                <?php
                                                            }
                                                        ?>
                                                        ></td>
                                                        <td class="text-end">
                                                            <b>Return Amount </b>
                                                        </td>
                                                        <td colspan="2">
                                                            <input type="text" name="returnamount" id="returnamount" class="form-control text-end" value="<?=$returnAmount?>" readonly>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td 
                                                        
                                                        <?php 
                                                            if($prescription==1)
                                                            {
                                                                ?>
                                                                colspan="7"
                                                                <?php 
                                                            }
                                                            else
                                                            {
                                                                ?>
                                                                colspan="6"
                                                                <?php
                                                            }
                                                        ?>
                                                        ></td>
                                                        <td class="text-end">
                                                            <b>Net Amount <span class="text-danger">*</span></b>
                                                        </td>
                                                        <td colspan="2">
                                                            <input type="text" name="netamount" id="netamount" class="form-control text-end" value="<?=$NetAmount?>" required readonly>
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    
                                    <div class="col-md-8 mt-2">
                                        <div class=" p-2">
                                            <div class="row mb-3" id="payment_methods">
                                            <?php 
                                                    $sql_2 = "SELECT * FROM `transactions` t
                                                    INNER JOIN paymethod p ON p.PMID=t.paymethod_PMID
                                                    WHERE t.InvoiceHeader_IHID= '$invoice' AND TransactionStat=1;";
                        
                                                $transactionData = $dbObj->getData($sql_2);
                                                $totalPaid=0;
                                                $trancount=count($transactionData);
                                                if($trancount > 0)
                                                {
                                                    foreach ($transactionData as $row) 
                                                    {
                            
                                                        ?>
                                                        <input type="hidden" name="TRID[]" value="<?=$row["TRID"]?>">
                                                        <div id="payment_method" class="row">                                                    
                                                            <div class="col-md-4 mb-2">
                                                                <label for="" class="form-label">Payment Type</label>
                                                                <select name="pay_id[]" id="pay_id" class="pay_id form-select">
                                                                    <option value="">Select Payment Method</option>
                                                                <?php 
                                                                    $sql = "SELECT * FROM shoppaymethod sm
                                                                    INNER JOIN paymethod pm ON pm.PMID = sm.paymethod_PMID
                                                                    WHERE sm.shop_SHID = ".$shop_id."  AND (pm.PMID!=6 AND pm.PMID!=4);";
                                                                    $dbObj = new DBTransactions();
                                                                    $dbPaymethods = $dbObj->getData($sql);
                                                                    $count = 0;
                                                                    foreach($dbPaymethods as $row2)
                                                                    {
                                                                        $is_checked = $count==0 ? 'checked' : '';
                                                                        ?>
                                                                        <option <?php if($row2['paymethod_PMID']==$row['PMID']){echo "Selected";}?> value="<?php echo $row2['paymethod_PMID'];?>"><?php echo $row2['PaymethodName'];?></option>
                                                                        <?php 
                                                                        $count += 1;
                                                                    }//foreach
                                                                ?>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="col-md-4 mb-2">
                                                                <label for="paid-input" class="form-label">Paid</label>
                                                                <input type="text" class="form-control" name="paid[]" id="paid-input" placeholder="0.00" value="<?=$row["TransferAmount"]?>">
                                                            </div>
                                                            <div class='col-md-4 row'>
                                                                <div class='col-md-3'>
                                                                    <a href='javascript:void(0);' class='remove-payment btn btn-danger mt-4' id='remove-payment' onclick='remove_payment(this)' ><i class='ti ti-trash'></i></a>
                                                                </div>
                                                                <div class='col-md-3'>
                                                                    <a href='javascript:void(0);' class='btn btn-primary mt-4' id='add-payment' onclick='addPayment()' ><i class='ti ti-plus'></i></a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <?php
                                                    }
                                                }
                                                else
                                                {
                                                    ?>
                                                        <div id="payment_method" class="row">                                                    
                                                            <div class="col-md-4 mb-2">
                                                                <label for="" class="form-label">Payment Type</label>
                                                                <select name="pay_id[]" id="pay_id" class="pay_id form-select">
                                                                <?php 
                                                                    $sql = "SELECT * FROM shoppaymethod sm
                                                                    INNER JOIN paymethod pm ON pm.PMID = sm.paymethod_PMID
                                                                    WHERE sm.shop_SHID = ".$shop_id."  AND (pm.PMID!=6 AND pm.PMID!=4);";
                                                                    $dbObj = new DBTransactions();
                                                                    $dbPaymethods = $dbObj->getData($sql);
                                                                    $count = 0;
                                                                    foreach($dbPaymethods as $row2)
                                                                    {
                                                                        $is_checked = $count==0 ? 'checked' : '';
                                                                        ?>
                                                                        <option  value="<?php echo $row2['paymethod_PMID'];?>"><?php echo $row2['PaymethodName'];?></option>
                                                                        <?php 
                                                                        $count += 1;
                                                                    }//foreach
                                                                ?>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="col-md-4 mb-2">
                                                                <label for="paid-input" class="form-label">Paid</label>
                                                                <input type="text" class="form-control" name="paid[]" id="paid-input" placeholder="0.00" value="0.00">
                                                            </div>
                                                            <div class='col-md-4 row'>
                                                                <div class='col-md-3'>
                                                                    <a href='javascript:void(0);' class='remove-payment btn btn-danger mt-4' id='remove-payment' onclick='remove_payment(this)' ><i class='ti ti-trash'></i></a>
                                                                </div>
                                                                <div class='col-md-3'>
                                                                    <a href='javascript:void(0);' class='btn btn-primary mt-4' id='add-payment' onclick='addPayment()' ><i class='ti ti-plus'></i></a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <?php
                                                }
                                                
                                                    ?>
                                                                                        
                                            </div>                     
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <?php 
                                        if($shopObj->hasPrescription($shop_id))
                                        {
                                            ?>
                                            <label for="" class="form-label">Select an existing prescription</label>
                                            <select name="cmb_prescription" id="cmb_prescription" class="form-select">
                                                <option value="0">=== Select Prescription ===</option>
                                                
                                            </select>
                                            <?php 
                                        }//has prescription
                                        ?>
                                    </div>
                                    <div class="col-md-8">       
                                    </div>
                                    <div class="col-md-12 d-flex justify-content-end mt-3">
                                        <input type="submit" name="EditInvoice" value="Edit Invoice" class="btn btn-primary" style=" height: fit-content;">
                                    </div>
                                </div>
                            </form>
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
    <script src="../Assets/jquery/editwholesale_invoice.js"></script>
    <script>
        $(document).ready(function() {
            // grandTotal();
            $(document).on("change",".pay_id",function(){
                var selectedValue=$(this).val();
                var isDuplicate = false;
                if(selectedValue!="")
                {
                    $('.pay_id').not(this).each(function() {
                        if ($(this).val() === selectedValue) {
                            isDuplicate = true;
                            return false; // break out of the loop
                        }
                    });
                    
                    if (isDuplicate) {
                        alert('Duplicate value selected!');
                        $(this).val("");
                    }
                    else
                    {
                        if($(this).val()==5)
                        {
                            var payment =`
                        <div id="chequeDiv" class="col-md-12 mb-2 row">
                                <div class="col-md-2">
                                    <label for="chqNo" class="form-label">Cheque No</label>
                                    <input type="text" name="chqNo[]" id="chqNo" class="form-control" placeholder="Cheque No">
                                </div>
                                <div class="col-md-2">
                                    <label for="chqdate" class="form-label">Cheque Date</label>
                                    <input type="date" name="chqdate[]" id="chqdate" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label for="chqbank" class="form-label">Bank</label>
                                    <input type="text" name="chqbank[]" id="chqbank" class="form-control" placeholder="Ex: BOC">
                                </div>
                                <div class="col-md-2">
                                    <label for="chqamount" class="form-label">Cheque Amount</label>
                                    <input type="text" name="chqAmount[]" id="chqamount" class="form-control" placeholder="Ex: 2000">
                                </div>
                                <div class="col-md-4">
                                <a href='javascript:void(0);' class='remove-payment btn btn-danger mt-4' id='remove-payment' onclick='remove_cheque(this)' ><i class='ti ti-trash'></i></a>
                                <a href='javascript:void(0);' class='btn btn-primary mt-4' onclick='addChq()' ><i class='ti ti-plus'></i></a>
                                </div>
                            </div>
                            `;
                            if($("#chequeDiv").length==0)
                            {
                                $("#payment_method").append(payment);
                            }
                        }
                        else
                        {
                            
                        }
                    }
                }
            });
            
            setPredefinedSupplier("<?=$Cus_id?>", "<?=$string?>");
            $("#add-item-btn").click(function(){
                $("#product_modal").modal("toggle");
                $("#cmb_category").focus();
                $("#btn_update_product").css('display', 'none');
                $("#btn_save_product").css('display', 'none');
                $(".image").css('display', 'none');
                $(".service").css('display', 'none');
                $("#btn_save_product2").css('display', 'block');
                $(".text-alrt").css('display', 'inline-block');
            });
            $('#wholesale-invoice-form').submit(function() {
                return validateForm();
            });
            $("body #remove").click(function(){
                $(this).closest("tr").remove();
                grandTotal();
                
            });
            $("#clear_return").click(function(){
                $("#return_id").val("");
                $("#return_no").val(null).trigger('change');
                $("#returnamount").val("");
                $("#return_no").focus();
                grandTotal();
            });
            $("#use_exccess").click(function() {
                var customer_excess_amount = $("#customer_excess_amount").val();
                $("#excessamount").val(customer_excess_amount);
            })
            $("#return_no").on("change",function(){
                $("#return_id").val("");
                $("#returnamount").val("");
                var return_id=$(this).val();
                $("#return_id").val(return_id);
                $.ajax({
                    url:'../AJAX/WholeSaleInvoice/returninvoice.php',
                        method:'post',
                        data:{
                            return_id:return_id
                            },
                        success:function(response)
                        {
                            const obj = JSON.parse(response);
                            var amount=obj[0]["amount"];
                            var id=obj[0]["id"];
                            $("#return_id").val(id);
                            $("#returnamount").val(amount);

                        }
                    });
                grandTotal();
            })
            setInterval(function(){
                // grandTotal();
            }, 100);
            $("#cmb_customer").change(function(){
                var cust_id = $(this).val();
                var cust_text = $(this).text();
                if(cust_id=="Add")
                {
                  $("#customer_modal").modal("toggle"); 
                }
                else
                {
                    $.get("../AJAX/WholeSaleInvoice/getCustDetail.php", {
                        cust_id: cust_id
                    }, function(data){
                        const obj = JSON.parse(data);
                        var cust_contact = obj[0]['cust_contact'];
                        var cust_name = obj[0]['cust_name'];
                        var cust_credit = obj[0]['cust_credit'];
                        var CTID = obj[0]['CTID'];
                        var txt = "<br><small>Customer </small><b>"+cust_name+ " - " + cust_contact +"</b><br>";
                        $("#excessamount").val("0.00");
                        if(cust_credit<0)
                        {
                            cust_credit=Math.abs(cust_credit);
                            txt+=" - <b>" + cust_credit +"</b><br>";
                            $("#use_exccess").css("display","block");
                        }
                        else
                        {
                            cust_credit=0;
                            txt+="";
                            $("#use_exccess").css("display","none");
                        }
                        txt+="<a href='../Public/customerProfile.php?cus_id="+CTID+"' target='_blank'> View Customer Profile</a><br>";
                        $("#p_customer").html(txt);
                        $("#customer_excess_amount").val(cust_credit);
                        $("#customer_id").val(cust_id);
                        grandTotal();
                    });//get customer detail 
                }
                   
            });//cmb changes
            $("#cmb_salesman").change(function(){
                var saleaman_id = $(this).val();
                    $.get("../AJAX/WholeSaleInvoice/getSalesmanDetail.php", {
                        saleaman_id: saleaman_id
                    }, function(data){
                        const obj = JSON.parse(data);
                        var SalesmansContact = obj[0]['SalesmansContact'];
                        var SalesmansName = obj[0]['SalesmansName'];
                        var SLID = obj[0]['SLID'];
                        var txt = "<br><small>Salesman </small><b>"+SalesmansName+ " - " + SalesmansContact +"</b><br>";
                        $("#excessamount").val("0.00");
                        cust_credit=0;
                        txt+="";
                        $("#p_salesman").html(txt);
                        $("#salesman_id").val(SLID);
                        grandTotal();
                    });//get customer detail 
                   
            });//cmb changes
        });
        
        function addChq()
        {
            var payment =`
                        <div id="chequeDiv" class="col-md-12 mb-2 row">
                                <div class="col-md-2">
                                    <label for="chqNo" class="form-label">Cheque No</label>
                                    <input type="text" name="chqNo[]" id="chqNo" class="form-control" placeholder="Cheque No">
                                </div>
                                <div class="col-md-2">
                                    <label for="chqdate" class="form-label">Cheque Date</label>
                                    <input type="date" name="chqdate[]" id="chqdate" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label for="chqbank" class="form-label">Bank</label>
                                    <input type="text" name="chqbank[]" id="chqbank" class="form-control" placeholder="Ex: BOC">
                                </div>
                                <div class="col-md-2">
                                    <label for="chqamount" class="form-label">Cheque Amount</label>
                                    <input type="text" name="chqAmount[]" id="chqamount" class="form-control" placeholder="Ex: 2000">
                                </div>
                                <div class="col-md-4">
                                <a href='javascript:void(0);' class='remove-payment btn btn-danger mt-4' id='remove-payment' onclick='remove_cheque(this)' ><i class='ti ti-trash'></i></a>
                                <a href='javascript:void(0);' class='btn btn-primary mt-4' onclick='addChq()' ><i class='ti ti-plus'></i></a>
                                </div>
                            </div>
                        `;
                            $("#payment_method").append(payment);
        }
        function remove_cheque(item)
        {
            var value = $(item).attr("id");
            $(item).parent().parent().remove();
            
        }
        function validateForm()
        {
            const checkboxes = $('.item');
            let isChecked = false;

            checkboxes.each(function() {
                if ($(this).val()!="") {
                    isChecked = true;
                    return false; // break out of the loop
                }
            });
            
            if (!isChecked) {
                alert('Please select at least one item');
                $(".item").focus();
                return false;
            }
            var netamount=parseFloat($("#netamount").val());
            var paid=0;
            $("body #paid-input").each(function(){
                if($(this).val()==0 && $(this).val()=="0" || $(this).val()=="")
                {
                    paid=paid+0;
                    console.log("No value"+paid);
                }
                else
                {
                    paid=paid+parseFloat($(this).val());
                    console.log("value"+paid);
                }
            });
            if(paid==0)
            {
                
            }
            else if(paid=="")
            {
                alert('Paid Amount Cannot be Empty');
                $("#paid-input").focus();
                return false;
            }
            if(netamount > paid)
            {
                if($("#customer_id").val()==1)
                {
                    alert('Default Customer Cannot Have Credit');
                    $("#add-customer").focus();
                    return false;
                }
            }
            
            return true;
        }
        <?php 
        $j=1;
        foreach ($shopData as $row) 
        { 
            ?>
            // Handle the focus event
            $('#item<?=$j?>').on('select2:open', function (e) {
                $('.select2-selection').css('border-color', 'red');
            });

            // Handle the blur event
            $('#item<?=$j?>').on('select2:close', function (e) {
                $('.select2-selection').css('border-color', '');
            });
            $("#item<?=$j?>").select2({
                ajax:{
                    url: '../AJAX/WholeSaleInvoice/getItems.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params){
                        var query = {
                            search: params.term,
                            type: 'item_search'
                        };
                        return query;
                    },
                    processResults: function(data){
                        return {
                            results: data
                        }
                    }
                },
                cache: true,
                placeholder: 'Search for Items',
                minimumInputLength: 1,
                width: '100%',
            });//get item search\
            <?php
            $j++;
        }
        ?>
        function addPayment()
        {
            var payment ="<div class='col-md-12 mb-2 row' id='payment_method'>"+
            "<div class='col-md-4'>"+
                            "<label for='' class='form-label'>Payment Type</label>"+
                            "<select name='pay_id[]' id='pay_id' class='pay_id form-select'>"+
                            "<option value=''>Select Payment Method</option>"+
                            <?php 
                                $sql = "SELECT * FROM shoppaymethod
                                INNER JOIN paymethod ON paymethod.PMID = shoppaymethod.paymethod_PMID
                                WHERE shop_SHID = ".$shop_id.";";
                                $dbObj = new DBTransactions();
                                $dbPaymethods = $dbObj->getData($sql);
                                $count = 0;
                                foreach($dbPaymethods as $row)
                                {
                                    $is_checked = $count==0 ? 'checked' : '';
                                    ?>
                                    "<option value='<?php echo $row['paymethod_PMID'];?>'><?php echo $row['PaymethodName'];?></option>"+
                                    <?php 
                                    $count += 1;
                                }//foreach
                            ?>
                            "</select>"+
                            "</div>"+
                            "<div class='col-md-4'>"+
                            "<label for='paid-input' class='form-label'>Paid</label>"+
                            "<input type='text' class='form-control' name='paid[]' id='paid-input' placeholder='0.00'>"+
                            "</div>"+
                            "<div class='col-md-4 row'>"+
                                "<div class='col-md-3'>"+
                                    "<a href='javascript:void(0);' class='remove-payment btn btn-danger mt-4' id='remove-payment' onclick='remove_payment(this)' ><i class='ti ti-trash'></i></a>"+
                                "</div>"+
                                "<div class='col-md-3'>"+
                                    "<a href='javascript:void(0);' class='btn btn-primary mt-4' id='add-payment' onclick='addPayment()' ><i class='ti ti-plus'></i></a>"+
                                "</div>"+
                            "</div>"+
                         "<div>";
           
            $("#payment_methods").append(payment);
        }
        function setPredefinedSupplier(customerId, customerName) {
                // Create a new option with the predefined value
                var option = new Option(customerName, customerId, true, true);

                // Append the option to Select2 and trigger the change event
                $("#cmb_customer").append(option).trigger('change');
            }
        function setPredefinedItem(productId, barcode,id) {
                // Create a new option with the predefined value
                var option = new Option(barcode, productId, true, true);

                // Append the option to Select2 and trigger the change event
                $("#item"+id).append(option).trigger('change');
            }
        function ttotal(item) 
        {
            $("#qty"+item).val($("#qty"+item).val().replace(/[^0-9.]/, ''));
            $("#rate"+item).val($("#rate"+item).val().replace(/[^0-9.]/, ''));
            $("#discount"+item).val($("#discount"+item).val().replace(/[^0-9.]/, ''));
            var avl_qty = $("#avl_qty"+item);    
            var qty = $("#qty"+item);    
            var alertqty = $("#alertqty"+item);    
            var alertrate = $("#alertrate"+item);    
            var rate = $("#rate"+item);    
            var original_rate = $("#original_rate"+item);    
            var discountType = $("#discountType"+item);    
            var discount = $("#discount"+item);    
            var total = $("#total"+item);    
            var original_total = $("#original_total"+item); 
            var qty_val=parseFloat(qty.val());   
            var avl_qty_val=parseFloat(avl_qty.val()); 
            var rate_val = parseFloat(rate.val());    
            var original_rate_val = parseFloat(original_rate.val());  
            var discountType_val = discountType.val();  
            var discount_val = parseFloat(discount.val());  
            var discount2=0;
            var full_total=0;
            var original_full_total=0;
            if(discount_val==0 || discount_val=="" || discount_val==undefined || discount_val==null)
            {
                discount.val("0.00");
                discount2=0;   
            }
            else
            {
                if(discountType_val==1)
                {
                    tot=rate_val*qty_val;
                    discount_val=tot*discount_val/100;                    
                }
                discount2=discount_val;
            }
            <?php 
            if($minus==1)
            {
                ?>
                alertqty.text("");
                if(qty.val()=="" || qty.val()==null || qty.val()==undefined)
                {
                    alertqty.text("");
                    qty.val("");
                    full_total=(rate_val*1)-parseFloat(discount2);
                    original_full_total=(original_rate_val*1);
                    total.val(full_total.toFixed(2));
                    original_total.val(original_full_total);
                    qty.focus();
                }
                else
                {
                    if(rate_val==0 || rate.val()=="" || rate.val()==null || rate.val()==undefined || isNaN(rate.val()))
                    {
                        rate_val=0;
                        
                    }
                    else
                    {
                        rate_val=rate_val;
                    }
                        alertrate.text("");
                        full_total=(rate_val*qty_val)-parseFloat(discount2);
                        original_full_total=(qty_val*original_rate_val);
                        total.val(full_total.toFixed(2));
                        original_total.val(original_full_total);
                }
                <?php
            }
            else
            {
                ?>
                if(qty_val > avl_qty_val)
                {
                    alertqty.text("Quantity Cannot be greater than the available Quantity!");
                    qty.val(avl_qty_val);
                    full_total=(rate_val*avl_qty_val)-parseFloat(discount2);
                    original_full_total=(original_rate_val*avl_qty_val);
                    total.val(full_total.toFixed(2));
                    original_total.val(original_full_total);
                    qty.focus();
                }
                else
                {
                    alertqty.text("");
                    if(qty.val()=="" || qty.val()==null || qty.val()==undefined)
                    {
                        alertqty.text("Quantity Cannot Be 0 or Null Valued");
                        qty.val("1");
                        full_total=(rate_val*1)-parseFloat(discount2);
                        original_full_total=(original_rate_val*1);
                        total.val(full_total.toFixed(2));
                        original_total.val(original_full_total);
                        qty.focus();
                    }
                    else
                    {
                        if(rate_val==0 || rate.val()=="" || rate.val()==null || rate.val()==undefined)
                        {
                                alertrate.text("Rate Cannot Be empty or 0");
                                rate.val("1.00");
                                full_total=(1*qty_val)-parseFloat(discount2);
                                original_full_total=(qty_val*original_rate_val);
                                total.val(full_total.toFixed(2));
                                original_total.val(original_full_total);
                                rate.focus();
                            
                        }
                        else
                        {
                            alertrate.text("");
                            full_total=(rate_val*qty_val)-parseFloat(discount2);
                            original_full_total=(qty_val*original_rate_val);
                            total.val(full_total.toFixed(2));
                            original_total.val(original_full_total);
                        }
                    }
                }
                <?php
            }
            ?>
            
            grandTotal();
        }
        function add_tr()
    {
       // Get the last data-example value and increment it for the new row
var lastValue = $('.tr').last().data('example') || 0;
var newValue = lastValue + 1;

// Create the HTML for the new row
var tr = 
    "<tr  style='max-width:300px;' id='" + newValue + "' class='tr' data-example='" + newValue + "'>"+
        "<td>"+
        "    <span class='text-danger' id='alert" + newValue + "'></span>"+
        "    <select name='item_id[]' onchange='item_id(" + newValue + ")' id='item" + newValue + "' class='item form-control'>"+
        "        <option value=''></option>"+
        "    </select>"+
        "</td>"+    
        <?php 
        if($prescription==1)
        {   
            ?> 
            "<td>"+
            "   <input type='text' name='prodDes[]' id='prodDes" + newValue + "' class='form-control' disabled>"+
            "</td>"+
            <?php 
        }
        ?>
        "<td>"+
        "    <select name='batch_id[]' id='batch_id" + newValue + "' class='form-control' onchange='batch(" + newValue + ")' disabled>"+
        "        <option value=''></option>"+
        "    </select>"+
        "<input type='hidden' name='batch[]' id='batch" + newValue + "' class='form-control' >"+
        "</td>"+
        "<td>"+
        "    <input type='text' name='avl_qty[]' id='avl_qty" + newValue + "' class='form-control text-end' required readonly>"+
        "</td>"+
        "<td>"+
        "    <span class='text-danger' id='alertqty" + newValue + "'></span>"+
        "    <input type='text' name='qty[]' id='qty" + newValue + "' onkeyup='ttotal(" + newValue + ")' onkeydown='ttotal(" + newValue + ")' onkeypress='ttotal(" + newValue + ")' class='qty form-control text-end' required disabled>"+
        "</td>"+
        "<td>"+
        "<span class='text-danger' id='alertrate" + newValue + "'></span>"+
        "    <input type='text' name='rate[]' id='rate" + newValue + "' class='rate form-control text-end' onkeyup='ttotal(" + newValue + ")' onkeydown='ttotal(" + newValue + ")' onkeypress='ttotal(" + newValue + ")'  required disabled>"+
        "    <input type='hidden' name='original_rate[]' id='original_rate" + newValue + "' class='form-control' readonly>"+
        "</td>"+
        "<td>"+
        "   <select name='discountType[]' id='discountType" + newValue + "' onchange='ttotal(" + newValue + ")' class='discountType form-select' disabled>"+
        "       <option value='1'>Percentage</option>"+
        "       <option value='2'>Flat Amount</option>"+
        "   </select>"+
        "</td>"+
        "<td>"+
        "    <input type='text' name='discount[]' id='discount" + newValue + "' class='discount form-control text-end' onkeyup='ttotal(" + newValue + ")' onkeydown='ttotal(" + newValue + ")' onkeypress='ttotal(" + newValue + ")'  value='0.00' disabled>"+
        "</td>"+
        "<td>"+
        "    <input type='text' name='totals[]' id='total" + newValue + "' class='total form-control text-end' required readonly>"+
        "    <input type='hidden' name='original_total[]' id='original_total" + newValue + "' class='form-control' readonly>"+
        "</td>"+
        "<td>"+
        "   <div class='row'>"+
        "       <div class='col-md-4'>"+
        "         <a href='javascript:void(0)' class='add btn btn-primary p-2' id='add" + newValue + "'  style='margin-right: 50px;'><i class='ti ti-plus'></i></a>  "+
        "       </div>"+
        "       <div class='col-md-4'>"+
        "         <a href='javascript:void(0)' class='btn btn-danger p-2' id='remove" + newValue + "'><i class='ti ti-trash'></i></a> "+
        "       </div>"+
        "   </div>"+
        "</td>"+
    "</tr>";

// Append the new row to the table body
$("#tbody").append(tr);

// Initialize Select2 on the new select element
$("#item"+newValue).select2({
    ajax:{
        url: '../AJAX/WholeSaleInvoice/getItems.php',
        dataType: 'json',
        delay: 250,
        data: function(params){
            var query = {
                search: params.term,
                type: 'item_search'
            };
            return query;
        },
        processResults: function(data){
            return {
                results: data
            }
        }
    },
    cache: true,
    placeholder: 'Search for Items',
    minimumInputLength: 1,
    width: '100%',
});

// Event delegation for dynamically added elements
$("body").on("click", "#remove" + newValue, function(){
    $(this).closest("tr").remove();
    grandTotal();
});

$("body").on("click", "#add" + newValue, function(){
    add_tr();
});

    }
        function grandTotal()
        {
            var total=0;
            var grosstotal=0;
            var discount=0;
            var saleDiscount=0;
            var deliverycharges=0;
            var otherCharges=0;
            var returnamount=0;
            var excessamount=0;
            $("body .total").each(function(){
                var value = $(this).val();
                var value2= parseFloat($(this).val());
                if(value=="" || value==null || value==undefined || value2==0)
                {
                    value2=0;   
                }
                else
                {
                    value2=value2;
                }

                total = total+value2;
            });
            $("body .discount").each(function(){
                var value = $(this).val();
                var value2= parseFloat($(this).val());
                var discountType=$(this).parent().parent().find(".discountType").val();
                var rate_val=parseFloat($(this).parent().parent().find(".rate").val());
                var qty_val=parseFloat($(this).parent().parent().find(".qty").val());
                
                if(value=="" || value==null || value==undefined || value2==0)
                {
                    value2=0;   
                }
                else
                {
                    if(discountType==1)
                    {
                        tot=rate_val*qty_val;
                        value2=tot*value2/100;                    
                    }
                    else
                    {
                        value2=value2;
                    }                    
                }

                discount = discount+value2;
            });
            
            if($("#saleDiscount").val()=="")
            {
                saleDiscount=0;
            }
            else
            {
                if($("#SaleDiscountType").val()==1)
                {
                    saleDiscount=total*parseFloat($("#saleDiscount").val())/100
                }
                else
                {
                    saleDiscount=parseFloat($("#saleDiscount").val());
                }
                
            }
            if($("#deliverycharges").val()=="")
            {
                deliverycharges=0;
            }
            else
            {
                deliverycharges=parseFloat($("#deliverycharges").val());
            }
            if($("#otherCharges").val()=="")
            {
                otherCharges=0;
            }
            else
            {
                otherCharges=parseFloat($("#otherCharges").val());
            }
            if($("#excessamount").val()=="")
            {
                excessamount=0;
            }
            else
            {
                excessamount=parseFloat($("#excessamount").val());
            }
            if($("#returnamount").val()=="")
            {
                returnamount=0;
            }
            else
            {
                returnamount=parseFloat($("#returnamount").val());
            }
            var grossAmount=total+discount;
            $("body #grossAmount").val(grossAmount.toFixed(2));

            $("#totalDiscountLine").val(discount.toFixed(2));
            var total_discount=saleDiscount+discount;
            
            $("#totalDiscount").val(total_discount.toFixed(2));
            total=total-saleDiscount+deliverycharges+otherCharges-returnamount-excessamount;
            $("#netamount").val(total.toFixed(2));
            // $("#paid-input").val(total.toFixed(2));
        }
        function batch(item)
        {
            var batch = $("#batch_id"+item);
            var items = $("#item"+item);
            var batch_val = $("#batch"+item);
            var qty = $("#qty"+item);
            var avl_qty = $("#avl_qty"+item);
            var rate = $("#rate"+item);
            var original_rate = $("#original_rate"+item);
            var discount = $("#discount"+item);
            var discountType = $("#discountType"+item);
            var total = $("#total"+item);
            if((items.val()!=0 || items.val()!="") && (batch.val()!=0 || batch.val()!=""))
            {
                qty.removeAttr("disabled");
                rate.removeAttr("disabled");
                discount.removeAttr("disabled");
                discountType.removeAttr("disabled");
                var item_id =items.val();
                var batch_id =batch.val();
                $.ajax({
                    url:'../AJAX/WholeSaleInvoice/editinvoice.php',
                        method:'post',
                        data:{
                            item_id:item_id,
                            batch_id:batch_id,
                            },
                            success: function(response) {
                                
                                var data = JSON.parse(response);
                                if (data.length > 0) {
                                    avl_qty.val(data[0]["avlQty"]);
                                    rate.val(data[0]["rate"]);
                                    original_rate.val(data[0]["rate"]);
                                    batch_val.val(data[0]["batch"]);
                                    qty.focus();
                                } else {
                                    alert("Something went wrong");
                                }
                            }
                    });
            }
            else
            {
                qty.val(0);
                avl_qty.val(0);
                rate.val(0);
                discount.val(0);
                total.val(0);
                discountType.val(0);
                qty.attr("disabled", true);
                rate.attr("disabled", true);
                discount.attr("disabled", true);
                discountType.attr("disabled", true);
                total.attr("disabled", true);
                
            }
        }
        function item_id(item)
        {
            var batch = $("#batch_id"+item);
            var items = $("#item"+item);
            var selectedValue = items.val();
            var isDuplicate = false;
            batch.html("");
            $("#avl_qty"+item).val("");
            $("#qty"+item).val("");
            $("#rate"+item).val("");
            $("#original_rate"+item).val("");
            $("#discountType"+item).val("1");
            $("#discount"+item).val("0.00");
            $("#total"+item).val("");
            $("#original_total"+item).val("");

            $("#qty"+item).attr("disabled", true);
            $("#rate"+item).attr("disabled", true);
            $("#discount"+item).attr("disabled", true);
            $("#total"+item).attr("disabled", true);
            $("#discountType"+item).attr("disabled", true);
            if(items.val()!=0 || items.val()=="")
            {
                    $("#alert"+item).text("");
                    $.ajax({
                    url:'../AJAX/WholeSaleInvoice/editinvoice.php',
                        method:'post',
                        data:{
                            items:selectedValue,
                            SelectedID:item
                            },
                        success:function(response)
                        {
                            batch.removeAttr("disabled");
                            batch.html(response);
                            batch.trigger("change");
                            batch.focus();
                        }
                    });
            }
        }
        function remove_payment(item)
        {
            var value = $(item).attr("id");
            var payment_method = 0;
            $("body #payment_method").each(function(){
                payment_method++;
            })
            if(payment_method <= 1)
            {
                alert("Atleast one payment method must be available");
            }
            else
            {
                $(item).parent().parent().parent().remove();
            }
            
            
        }
        $(document).ready(function(){
            $("body").focus(function(){
               let focusedElement = document.activeElement;
                let focusedElementClass = focusedElement.className;
                console.log(focusedElementClass);  
                let focusedElementClasses = focusedElement.className.split(' ');
                console.log(focusedElementClasses); 
            })
            
        })
    </script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
</body>
</html>