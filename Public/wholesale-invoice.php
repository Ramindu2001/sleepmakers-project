<?php 

include '../Includes/includes.php';
include '../Includes/authcheck.php';

$user_id = $_SESSION['user_id'];
$shop_id = $_SESSION['shop_id'];
include '../View/head.php';
include '../View/loader.php';
include '../View/modals/add-customer-wholesale.php';
include "../View/modals/add-products.php";
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
  $wholesale= new wholesale_invoice();
  $hasMinus=$shopObj->hasMinus($shop_id);
  $isUnderCost = $shopObj->hasUnderCost($shop_id);  
  $hasPrescription=$shopObj->hasPrescription($shop_id);
  $hasbatchNo=$shopObj->hasbatchNo($shop_id);
  $hasexcess=$shopObj->hasexcess($shop_id);
  $hasSalesman=$shopObj->hasSalesman($shop_id);
  $minus=0;
  $prescription=0;
  $saleaman=0;
  $shopData=$shopObj->getOneShop($shop_id);
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
    // include "../View/modals/prescription.php";
  }
  $doc=$wholesale->select_docno($shop_id);
    if(count($doc)==0)
    {
        $inser_doc=$wholesale->insert_doc_no($shop_id);
        $doc=$wholesale->select_docno($shop_id);
    }
    $ws_no=$doc[0]["ws_no"] + 1;
    $ws_no=$wholesale->getSequence($ws_no);
    $salesSource=$wholesale->salesSource();
    $year=date("y");
    $salesetings=$wholesale->getSaleSettings($shop_id);
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
  $userObj= new User;
  $Cus_id=1;
  $userData=$userObj->getOneUser($user_id);
  $userbillAmount=$wholesale->userBillAmount($user_id);
  if(isset($userbillAmount[0]["totalamount"]) && $userbillAmount[0]["totalamount"]!=null)
  {
    $userAmount=$userbillAmount[0]["totalamount"];
  }
  else
  {
    $userAmount=0;
  }
  $customer=$custObj->getOneCustomer($Cus_id);
  $salesman=$salemObj->getOneSalesman($Cus_id);
  $maxminInvoice= $wholesale->maxminInvoice($shop_id);
  ?>
  <style>
    @media (max-width: 1200px) 
    {
       .table>:not(caption)>*>* {
            padding: 2px !important;
        } 
    }
    
    .card-body
    {
        padding: var(--bs-card-spacer-y) 0;
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
    .w90p
    {
        width: 90px;
    }
    .w300p
    {
        width: 300px;
    }
    .w150p
    {
        width: 150px;
    }
    .det
    {
        font-size: 13px;
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
        <input type="hidden" name="" id="excessFlag" value="<?=$hasexcess?>">
        <input type="hidden" name="" id="hasbatchNo" value="<?=$hasbatchNo?>">
        <input type="hidden" name="userlimit" id="userlimit" value="<?=$userData[0]["paylimit"]?>">
        <input type="hidden" name="userAmount" id="userAmount" value="<?=$userAmount?>">
            <h5 class="card-title fw-semibold mb-3">Wholesale Invoice</h5>
            <?php 
            if(isset($maxminInvoice[0]["maxIHID"]))
            {
                ?>
                <a href="../Public/view-wholesaleinovice.php?INVID=<?=$maxminInvoice[0]["maxIHID"]?>" target="_blank" class="btn btn-primary mb-3">
                    <i class="ti ti-player-skip-back"></i>
                </a> 
                <?php
            }
            ?>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="d-flex justify-content-center">
                        <?php 
                        if(isset($_SESSION["Invoice_Error"]) && $_SESSION["Invoice_Error"]==1)
                        {
                            ?>
                            <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show w-50" role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                <strong>Duplicate Entry!</strong> Invoice Duplicate Has Been Disabled!
                            </div> 
                            <?php
                            unset($_SESSION["Invoice_Error"]);
                        }
                        ?>
                           
                    </div>
                    
                    <div class="card p-2">
                        <div class="card-body">
                            <form action="../Controller/wholesaleinvoicecontrol.php" method="post" id="wholesale-invoice-form">
                                <div class="row">
                                    <div class="col-md-3">
                                        <?php 
                                        
                                        $shopObj = new Shop();
                                        if($shopObj->hasCustomers($shop_id))
                                        {
                                            ?>
                                            <label for="cmb_customer" class="form-label">Customer Name/Phone No</label>
                                            <div class="input-group">
                                                <select name="cmb_customer" id="cmb_customer" class="form-select"></select>
                                                <a href="javascript:void(0);" class="input-group-text" id="add-customer"><i class="ti ti-user-plus" ></i></a>
                                            </div>
                                            <span id="p_customer" class="mt-2"><br><small>Customer -</small><b> <?=$customer[0]["CustName"]?></b><br></span>
                                            <?php
                                        }
                                        ?>
                                            <input type="hidden" name="customer_id" id="customer_id" value="1">
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
                                            <input type="hidden" name="salesman_id" id="salesman_id" value="1">
                                        <label for="date" class="form-label mt-3">Date</label>
                                        <input type="date" name="EffectiveDate" value="<?=$date?>" id="EffectiveDate" class="form-control">
                                    </div>
                                    <div class="col-md-9 text-end">
                                        <?php 
                                            if(!$shopObj->hasInventory($shop_id))
                                            {
                                                ?>
                                                <!-- <a href="javascript:void(0)" class="btn btn-primary mt-3" id="moveToSecondScreen">Move To Second Screen</a> -->
                                                <a href="javascript:void(0)" class="btn btn-primary mt-3" id="add-item-btn">Add Item</a>
                                                <?php 
                                            }
                                        ?>
                                        <h5 class="text-end mt-2"><b>Invoice No: <?=$ws_no?></b></h5>
                                        <div id="returns">
                                            <h4 class="text-end mt-3"><b>Return No</b></h4>
                                            <div class="row w-100">
                                                <div class="col-md-3"></div>
                                                <div class="col-md-3"></div>
                                                <div class="col-md-3">
                                                </div>
                                                <div class="col-md-3">
                                                    <select name="return_no" id="return_no" class="form-select"></select>
                                                    <input type="hidden" name="return_id" id="return_id" class="form-control" readonly>
                                                </div>
                                            </div>
                                            <a href="javascript:void(0)" class="btn btn-danger mt-2" id="clear_return">Clear Return</a>
                                        </div>
                                                                                
                                        <?php 
                                        if($prescription==1)
                                        {   
                                            ?>
                                            <a href="javascript:void(0)" class="btn btn-primary mt-2" id="add-prescriptions">Add Prescription</a>
                                            <?php 
                                        }
                                        ?>
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
                                                            <th colspan="2" style=" text-align:center;">Item Description </th>
                                                            <?php
                                                        }
                                                        else
                                                        {
                                                            if($hasbatchNo==1)
                                                            {
                                                                ?>
                                                                <th style="min-width: 200px;">Batch No <span class="text-danger">*</span></th>
                                                                <?php
                                                            }
                                                            else
                                                            {
                                                                ?>
                                                                <th style="display:none;"></th>
                                                                <?php
                                                            }
                                                            
                                                        }
                                                        ?>                                                        
                                                        <th style="text-align:center;">Av.Qty <span class="text-danger" >*</span></th>
                                                        <th style="text-align:center;">Qty <span class="text-danger">*</span></th>
                                                        <th style="text-align:center;"> Rate <span class="text-danger">*</span></th>
                                                        <th>Disc. Type</th>
                                                        <th>Disc. (Per Unit)</th>
                                                        <th style="text-align:center;">Total <span class="text-danger">*</span></th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbody">
                                                    <?php 
                                                    $count=3;
                                                    for ($i=0; $i < $count; $i++) 
                                                    { 
                                                        ?>
                                                            <script>
                                                                // Listen for the opening of the Select2 dropdown and apply red border
                                                                $("#item<?=$i?>").on('select2:open', function (e) {
                                                                    $('.select2-selection--focus').parent().parent().addClass('focused-border');
                                                                });

                                                                // Listen for the closing of the Select2 dropdown and remove the red border
                                                                $("#item<?=$i?>").on('select2:close', function (e) {
                                                                    $('.select2-selection--focus').parent().parent().removeClass('focused-border');
                                                                });
                                                                    $("#item<?=$i?>").select2({
                                                                        ajax:{
                                                                            url: '../AJAX/WholeSaleInvoice/getItems.php',
                                                                            dataType: 'json',
                                                                            delay: 250,
                                                                            data: function(params){
                                                                                alert(params);
                                                                                console.log(params);
                                                                                
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
                                                            </script>
                                                        <tr id="<?=$i?>" class="tr" data-example="<?=$i?>">
                                                            <td style="max-width:250px;">
                                                                <span class="text-danger" id="alert<?=$i?>"></span>
                                                                <select name="item_id[]" onchange="item_id(<?=$i?>)" id="item<?=$i?>" class="item form-control">
                                                                    <option value=""></option>
                                                                </select>
                                                            </td>
                                                            <?php 
                                                            if($prescription==1)
                                                            {
                                                                ?>
                                                                <td colspan="2">
                                                                    <input type="text" name="prodDes[]" id="prodDes<?=$i?>" class="form-control w300p" disabled>
                                                                    <select name="batch_id[]" required id="batch_id<?=$i?>" class="form-select" onchange="batch(<?=$i?>)" style=" width: 400px; display: none;" disabled>
                                                                        <option value=""></option>
                                                                    </select>                                                                
                                                                    <input type="hidden" name="batch[]" id="batch<?=$i?>" class="form-control" readonly>
                                                                </td>
                                                                <?php
                                                            }
                                                            else
                                                            {
                                                                if($hasbatchNo==1)
                                                                {
                                                                    ?>
                                                                    <td>
                                                                        <select name="batch_id[]" required  id="batch_id<?=$i?>" class="form-select" onchange="batch(<?=$i?>)" disabled>
                                                                            <option value=""></option>
                                                                        </select>                                                                
                                                                        <input type="hidden" name="batch[]" id="batch<?=$i?>" class="form-control" readonly>
                                                                    </td>
                                                                    <?php
                                                                }
                                                                else
                                                                {
                                                                    ?>
                                                                    <td style="display:none;">
                                                                        <select name="batch_id[]" required  id="batch_id<?=$i?>" class="form-select" onchange="batch(<?=$i?>)" disabled>
                                                                            <option value=""></option>
                                                                        </select>                                                                
                                                                        <input type="hidden" name="batch[]" id="batch<?=$i?>" class="form-control" readonly>
                                                                    </td>
                                                                    <?php
                                                                }
                                                                
                                                            }
                                                            ?>
                                                            
                                                            <td >
                                                                <input type="text" name="avl_qty[]" id="avl_qty<?=$i?>" class="form-control text-end 
                                                                <?php 
                                                                if($hasPrescription==1)
                                                                {
                                                                    echo "w90p";
                                                                }
                                                                ?>
                                                                " required readonly>
                                                            </td>
                                                            <td>
                                                                <span class="text-danger" id="alertqty<?=$i?>"></span>
                                                                <input type="text" name="qty[]" id="qty<?=$i?>" onkeyup="ttotal(<?=$i?>)" onkeydown="ttotal(<?=$i?>)" onkeypress="ttotal(<?=$i?>)" class="qty form-control text-end 
                                                                <?php 
                                                                if($hasPrescription==1)
                                                                {
                                                                    echo "w90p";
                                                                }
                                                                ?>
                                                                " required disabled>
                                                            </td>
                                                            <td>
                                                            <span class="text-danger" id="alertrate<?=$i?>"></span>
                                                                <input type="text" name="rate[]" id="rate<?=$i?>" class="rate form-control text-end
                                                                <?php 
                                                                if($hasPrescription==1)
                                                                {
                                                                    echo "w90p";
                                                                }
                                                                ?>
                                                                " onkeyup="ttotal(<?=$i?>)" onkeydown="ttotal(<?=$i?>)" onkeypress="ttotal(<?=$i?>)"  required disabled>
                                                                <input type="hidden" name="original_rate[]" id="original_rate<?=$i?>" class="form-control" readonly>
                                                                <input type="hidden" name="cost_rate[]" id="cost_rate<?=$i?>" class="form-control" readonly>
                                                            </td>
                                                            <td>
                                                                <select name="discountType[]" id="discountType<?=$i?>" onchange="ttotal(<?=$i?>)" class="discountType form-select
                                                                <?php 
                                                                if($hasPrescription==1)
                                                                {
                                                                    echo "w90p";
                                                                }
                                                                ?>
                                                                " disabled>
                                                                    <option value="1">Percentage</option>
                                                                    <option value="2">Flat Amount</option>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <input type="text" name="discount[]" id="discount<?=$i?>" class="discount form-control text-end
                                                                <?php 
                                                                if($hasPrescription==1)
                                                                {
                                                                    echo "w90p";
                                                                }
                                                                ?>
                                                                " onkeyup="ttotal(<?=$i?>)" onkeydown="ttotal(<?=$i?>)" onkeypress="ttotal(<?=$i?>)"  value="0" disabled>
                                                            </td>
                                                            <td>
                                                                <input type="text" name="totals[]" id="total<?=$i?>" class="total form-control text-end
                                                                <?php 
                                                                if($hasPrescription==1)
                                                                {
                                                                    echo "w150p";
                                                                }
                                                                ?>
                                                                " readonly>
                                                                <input type="hidden" name="original_total[]" id="original_total<?=$i?>" class="form-control" readonly>
                                                            </td>
                                                            <td>
                                                                <?php 
                                                                if($i==0)
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
                                                            <label for="details" class="form-label text-center w-100 det">Sale Details</label>
                                                            <textarea name="details" id="details" class="form-control" style="height:100px;"></textarea>                                                      
                                                            <script>
                                                                CKEDITOR.replace( 'details' );
                                                            </script>
                                                        </td>
                                                        <td class="text-end">
                                                            <b class="det">Gross Amount</b>
                                                        </td>
                                                        <td colspan="2">
                                                            <input type="text" name="grossAmount" id="grossAmount" class="form-control text-end" onkeyup="grandTotal()" onkeydown="grandTotal()" onkeypress="grandTotal()" value="0.00" readonly>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-end">
                                                            <b class="det">Sale Disc. Type</b>
                                                        </td>
                                                        <td colspan="2">
                                                            <select name="SaleDiscountType" id="SaleDiscountType" class="form-select" onchange="grandTotal()">
                                                                <option value="1">Percentage</option>
                                                                <option value="2">Flat Amount</option>                                                                
                                                            </select>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-end">
                                                            <b class="det">Sale Disc.</b>
                                                        </td>
                                                        <td colspan="2">
                                                            <input type="text" name="saleDiscount" id="saleDiscount" class="form-control text-end" onkeyup="grandTotal()" onkeydown="grandTotal()" onkeypress="grandTotal()" value="0.00">
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
                                                            <label for="remark" class="form-label text-center w-100 det">Internal Remark</label>
                                                            <textarea name="remark" id="remark" class="form-control" style="height:70px;"></textarea>   
                                                        </td>
                                                        <td class="text-end">
                                                            <b class="det">Total Disc.</b>
                                                        </td>
                                                        <td colspan="2">
                                                            <input type="text" name="totalDiscount" id="totalDiscount" class="form-control text-end" value="0.00" readonly>
                                                            <input type="hidden" name="totalDiscountLine" id="totalDiscountLine" class="form-control" value="0.00" readonly>
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
                                                            <label for="deliveryPartner" class="form-lable me-2 det"><b>Select Delivery Partner</b></label>&nbsp;&nbsp;&nbsp;
                                                            
                                                            <select name="deliveryPartner" id="deliveryPartner" class="form-select me-5 w-40">
                                                                <option value="">Select Delivery Partner</option>
                                                                <option value="Any Delivery">Any Delivery</option>
                                                                <option value="Fardar">Fardar</option>
                                                                <option value="Pick me">Pick me</option>
                                                                <option value="Imran">Imran</option>
                                                                <option value="Other">Other</option>
                                                            </select>
                                                            <label for="deliveryNote" class="form-lable me-3 det"><b>Delivery Note</b></label>&nbsp;&nbsp;&nbsp;
                                                            <input type="checkbox" name="deliveryNote" id="deliveryNote" value="1">
                                                         </div>
                                                            
                                                        </td>
                                                        <td class="text-end">
                                                            <b class="det">Delivery Charges</b>
                                                        </td>
                                                        <td colspan="2">
                                                            <input type="text" name="deliverycharges" id="deliverycharges" class="form-control text-end"  onkeyup="grandTotal()" onkeydown="grandTotal()" onkeypress="grandTotal()" value="0.00" >
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
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="d-flex justify-content-end">
                                                                <label for="claimBill" class="form-lable me-3 det"><b>Claim Bill</b></label>&nbsp;&nbsp;&nbsp;
                                                                    <input type="checkbox" name="claimBill" id="claimBill" value="1">
                                                                </div>    
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="d-flex justify-content-end">
                                                                    <label for="claimBillI" class="form-lable me-3 det"><b>Claim Bill With Inventory</b></label>&nbsp;&nbsp;&nbsp;
                                                                    <input type="checkbox" name="claimBillI" id="claimBillI" value="1">
                                                                </div>    
                                                            </div>
                                                        </div>
                                                         
                                                         
                                                        </td>
                                                        <td class="text-end">
                                                            <b class="det">Other Charges</b>
                                                        </td>
                                                        <td colspan="2">
                                                            <input type="text" name="otherCharges" id="otherCharges" class="form-control text-end"  onkeyup="grandTotal()" onkeydown="grandTotal()" onkeypress="grandTotal()" value="0.00" >
                                                        </td>
                                                    </tr>
                                                    <?php
                                                    if($hasexcess==1)
                                                    {
                                                        ?>
                                                    <tr>
                                                        <?php
                                                    }
                                                    else
                                                    {
                                                        ?>
                                                    <tr style="display:none;">
                                                        <?php
                                                    }
                                                    ?>
                                                    
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
                                                            
                                                        </td>
                                                        <td class="text-end">
                                                            <b class="det">Excess Amount </b>
                                                        </td>
                                                        <td colspan="2">
                                                            <input type="text" name="excessamount" id="excessamount" class="form-control text-end" value="0.00" readonly>
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
                                                         <div class="d-flex justify-content-end">
                                                            <label for="salesSource" class="form-lable me-2 det"><b>Select Sales Source</b></label>&nbsp;&nbsp;&nbsp;
                                                            
                                                            <select name="salesSource" id="salesSource" class="form-select me-5 w-40">
                                                                <option value="">Select Sales Source</option>
                                                                <?php 
                                                                foreach($salesSource AS $source)
                                                                {
                                                                    ?>
                                                                    <option value="<?=$source["SSUID"]?>"><?=$source["source_name"]?></option>
                                                                    <?php
                                                                }
                                                                ?>
                                                            </select>
                                                         </div>
                                                        </td>
                                                        <td class="text-end">
                                                            <b class="det">Return Amount </b>
                                                        </td>
                                                        <td colspan="2">
                                                            <input type="text" name="returnamount" id="returnamount" class="form-control text-end" value="0.00" onkeyup="grandTotal()" onkeydown="grandTotal()" onkeypress="grandTotal()" readonly>
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
                                                            <b class="det">Net Amount <span class="text-danger">*</span></b>
                                                        </td>
                                                        <td colspan="2">
                                                            <input type="text" name="netamount" id="netamount" class="form-control text-end" value="0.00" required readonly>
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="col-md-8 mt-2">
                                        <div class=" p-2">
                                            <div class="row mb-3" id="payment_methods">
                                                <div id="payment_method" class="row">
                                                    <div class="col-md-4 mb-2">
                                                        <label for="" class="form-label">Payment Type</label>
                                                        <select name="pay_id[]" id="pay_id" class="pay_id form-select">
                                                        <?php 
                                                            $sql = "SELECT * FROM shoppaymethod sm
                                                            INNER JOIN paymethod pm ON pm.PMID = sm.paymethod_PMID
                                                            WHERE sm.shop_SHID = ".$shop_id."  AND (pm.PMID!=6 AND pm.PMID!=4 AND pm.PMID!=13);";
                                                            $dbObj = new DBTransactions();
                                                            $dbPaymethods = $dbObj->getData($sql);
                                                            $count = 0;
                                                            $payCount=count($dbPaymethods);
                                                            if($payCount > 0)
                                                            {
                                                                foreach($dbPaymethods as $row)
                                                                {
                                                                    $is_checked = $count==0 ? 'checked' : '';
                                                                    ?>
                                                                    <option value="<?php echo $row['PMID'];?>"><?php echo $row['PaymethodName'];?></option>
                                                                    <?php 
                                                                    $count += 1;
                                                                }//foreach
                                                            }
                                                            else
                                                            {
                                                                $sql="SELECT * FROM paymethod WHERE PMID=1 OR PMID=2 OR PMID=3";
                                                                $dbObj = new DBTransactions();
                                                                $dbPaymethods = $dbObj->getData($sql);
                                                                $count = 0;
                                                                $payCount=count($dbPaymethods);
                                                                if($payCount > 0)
                                                                {
                                                                    foreach($dbPaymethods as $row)
                                                                    {
                                                                        $is_checked = $count==0 ? 'checked' : '';
                                                                        ?>
                                                                        <option value="<?php echo $row['PMID'];?>"><?php echo $row['PaymethodName'];?></option>
                                                                        <?php 
                                                                        $count += 1;
                                                                    }//foreach
                                                                }
                                                            }
                                                            
                                                        ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4 mb-2">
                                                        <label for="paid-input" class="form-label">Paid</label>
                                                        <input type="text" class="paid-input form-control" name="paid[]" id="paid-input" placeholder="0.00">
                                                    </div>
                                                    <div class="col-md-4 mb-2">
                                                        <a href="javascript:void(0);" class="btn btn-primary mt-4" id="add-payment" onclick="addPayment()">Add Payment</a>
                                                    </div>
                                                </div>                                        
                                            </div>  
                                            <div class="row">
                                                <div class="col-md-4 text-end">
                                                    <p><b>Balance</b></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <span id="balanceAmount">0.00</span>
                                                </div>
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
                                        <?php 
                                        if($shopObj->hasPrescription($shop_id))
                                        {
                                            ?>
                                                <div class="col-md-2">
                                                    <div class="form-check mt-3">
                                                        <input class="form-check-input" type="checkbox" name="chk_prescription" id="chk_prescription">
                                                        <label class="form-check-label" for="chk_prescription">
                                                            Add Prescription
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php 
                                        }
                                    ?>
                                    <input type="hidden" name="form_token" value="<?php echo uniqid(); ?>">
                                    <input type="hidden" name="formValid" id="formValid" value="false">
                                    <div class="col-md-8 mt-3">       
                                        <?php 
                                            if($shopObj->hasinvoice_print($shop_id)==1)
                                            {
                                                ?>
                                                <input type="submit" name="wholesale" value="Submit & Print Invoice" class="btn btn-primary" style=" height: fit-content;">
                                                <?php
                                            }
                                            ?>
                                            
                                            <input type="submit" name="wholesales" value="Submit" class="btn btn-primary" style=" height: fit-content;" id="submitBtn">
                                            
                                    </div>
                                    <div class="col-md-4 d-flex justify-content-end">
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
<script>
        $(document).ready(function () {
            $("#moveToSecondScreen").on("click", function () {
                // Check if multiple screens are available
                if (window.screen.availWidth !== screen.width) {
                    alert("Multiple monitors detected. Attempting to move the window...");

                    // Open a new window and position it on the second screen
                    const newWindow = window.open(
                        window.location.href, // Open the same URL
                        "_blank", 
                        "width=800,height=600,top=0,left=" + window.screen.availWidth
                    );

                    if (!newWindow) {
                        alert("Unable to open new window. Please allow pop-ups.");
                    }
                } else {
                    alert("A second monitor is not detected.");
                }
            });
        });
    </script>

<!--  Body Wrapper End -->
    <!-- footer Start  -->
    <?php include '../View/footer.php';?> 
    <!-- footer End  -->
    <script src="../Assets/jquery/wholesale_invoice.js"></script>
    <script>
        $(document).ready(function() {

            var formValid = false; // Form validation flag

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
                                $("#payment_method").append(payment);
                        }
                        else
                        {
                            
                        }
                    }
                }
            });
            
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
                $("#returnamount").val("0.00");
                $("#return_no").focus();
                grandTotal();
            });
            $("body #remove").click(function(){
                $(this).closest("tr").remove();
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
                            $("#returnamount").val(amount).trigger('change');
                            grandTotal();

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
                var excessFlag = $("#excessFlag").val();
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
                        var txt = "<br><small>Customer -</small><b> "+cust_name+ " - " + cust_contact +"</b><br>";
                        $("#excessamount").val("0.00");
                        if(cust_credit<0)
                        {
                            cust_credit=Math.abs(cust_credit);
                            txt+=" - <b>" + cust_credit +"</b><br>";
                            if(excessFlag==1)
                            {
                                $("#use_exccess").css("display","block");
                            }
                            else
                            {
                                $("#use_exccess").css("display","none");                                
                            }                            
                        }
                        else if(cust_credit > 0)
                        {
                            txt+="<b> Credit Balance - "+cust_credit+"</b><br>";
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

            var IsUnderCost = "<?php echo $isUnderCost ?>";
        
            if(IsUnderCost==1) //check if the user is allowed to sell under cost
            {  
                //Added by Imila Madushan on 2025-03-13
                var formValid = true;
                $("input[id^='rate']").each(function () {
                    var item = $(this).attr("id").replace("rate", ""); // Extract item ID
                    var rate = $("#rate" + item);
                    var cost_rate = $("#cost_rate" + item);
                    var qty = $("#qty" + item);
                    var total = $("#total" + item);

                    var rate_val = parseFloat(rate.val()) || 0;
                    var cost_rate_val = parseFloat(cost_rate.val()) || 0;
                    var qty_val = parseFloat(qty.val()) || 0;
                    var full_total = parseFloat(total.val()) || 0;
                    var full_Cost_total = cost_rate_val * qty_val;

                
                    if (rate_val < cost_rate_val) {
                        rate.css("border", "2px solid red");
                        formValid = false;
                        if (!$("#rate-warning" + item).length) {
                            rate.after(`<small id="rate-warning${item}" style="color: red;"> Rate cannot be less than cost rate (Rs.${cost_rate_val.toFixed(2)}) </small>`);
                        }
                        //alert("Rate: " + rate_val + ", Cost Rate: " + cost_rate_val);
                    
                        rate.focus();
                        return false;
                    }

                    //alert("Total: " + full_total + ", Total Cost: " + full_Cost_total);
                    
                    if (full_total < full_Cost_total) {
                        total.css("border", "2px solid red");
                        formValid = false;
                        if (!$("#total-warning" + item).length) {
                            total.after(`<small id="total-warning${item}" style="color: red;"> Total cannot be less than cost price (Rs.${full_Cost_total.toFixed(2)}) </small>`);
                        }
                        total.focus();
                        return false;
                    }
                });


                if (!formValid) {
                    event.preventDefault(); // Prevent form submission
                    alert("Please fix the errors before submitting!");
                    return false;
                }
            }
            //-----------------------------------------//



            var netamount=parseFloat($("#netamount").val());
            //added by Imila on 28/11/2024
            var userAmount = parseFloat($("#userAmount").val());
            var userLimit = parseFloat($("#userlimit").val());
            var TotalInvoiceVal = (userAmount + netamount);
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
            var pay_id=1
            $("body #pay_id").each(function(){
                if($(this).val()==0 || $(this).val()=="0" || $(this).val()=="")
                {
                    pay_id=0;
                }
            });
            $("body #qty").each(function(){
                var tr=$(this).parent().parent().find('select[name="batch_id[]"]');
                if($(this).val()==0 || $(this).val()=="0" )
                {
                    alert("Quantity 0 Cannot Be Allowed"); 
                    $(this).focus();
                    return false;
                }
                else if((tr.val()!="" && tr.val()!=0 ) && ($(this).val()==0 || $(this).val()=="0" || $(this).val()==""))
                {
                    alert("Quantity 0 Cannot Be Allowed"); 
                    $(this).focus();
                    return false;
                }
            });
            if(pay_id==0)
            {
                alert("Please double check the payment methods");
                return false;
            }
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
            
            if(TotalInvoiceVal > userLimit && userLimit > 0)
            {
                alert("Unable to exceed the daily allocated invoicing limit!");
                return false;
            }
            
            return true;
        }
        <?php 
        $count=3;
        for ($i=0; $i < $count; $i++) 
        { 
            ?>
            // Handle the focus event
            $('#item<?=$i?>').on('select2:open', function (e) {
                $('.select2-selection').css('border-color', 'red');
            });

            // Handle the blur event
            $('#item<?=$i?>').on('select2:close', function (e) {
                $('.select2-selection').css('border-color', '');
            });
            $("#item<?=$i?>").select2({
                ajax:{
                    url: '../AJAX/WholeSaleInvoice/getItems.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params){
                        console.log(params);
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
        }
        ?>
        function balance()
        {
            var paid_input=0;
            var netamount=parseFloat($("#netamount").val());
            $(".paid-input").each(function(){
                
                if($(this).val()==0 && $(this).val()=="0" || $(this).val()=="")
                {
                    paid_input=paid_input+0;
                }
                else
                {
                    paid_input=paid_input+parseFloat($(this).val());
                }
            });

            var balance=paid_input-netamount;
            balance=balance.toFixed(2);
            $("#balanceAmount").text(balance)
        }
        $("body").on("keyup",".paid-input", function(){
            balance();            
        });
        function addPayment()
        {
            var payment ="<div class='col-md-12 mb-2 row'>"+
            "<div class='col-md-4'>"+
                            "<label for='' class='form-label'>Payment Type</label>"+
                            "<select name='pay_id[]' id='pay_id' class='pay_id form-select'>"+
                            "<option value=''>Select Payment</option>"+
                            <?php 
                                $sql = "SELECT * FROM shoppaymethod
                                INNER JOIN paymethod ON paymethod.PMID = shoppaymethod.paymethod_PMID
                                WHERE shop_SHID = ".$shop_id."  AND (paymethod.PMID!=6 AND paymethod.PMID!=4 AND paymethod.PMID!=13);";
                                $dbObj = new DBTransactions();
                                $dbPaymethods = $dbObj->getData($sql);
                                $count = 0;
                                $payCount=count($dbPaymethods);
                                if($payCount > 0)
                                {
                                    foreach($dbPaymethods as $row)
                                    {
                                        $is_checked = $count==0 ? 'checked' : '';
                                        ?>
                                        "<option value='<?php echo $row['PMID'];?>'><?php echo $row['PaymethodName'];?></option>"+
                                        <?php 
                                        $count += 1;
                                    }//foreach
                                }
                                else
                                {
                                    $sql="SELECT * FROM paymethod WHERE PMID=1 OR PMID=2 OR PMID=3";
                                    $dbObj = new DBTransactions();
                                    $dbPaymethods = $dbObj->getData($sql);
                                    $count = 0;
                                    $payCount=count($dbPaymethods);
                                    if($payCount > 0)
                                    {
                                        foreach($dbPaymethods as $row)
                                        {
                                            $is_checked = $count==0 ? 'checked' : '';
                                            ?>
                                            "<option value='<?php echo $row['PMID'];?>'><?php echo $row['PaymethodName'];?></option>"+
                                            <?php 
                                            $count += 1;
                                        }
                                    }
                                }
                            ?>
                            "</select>"+
                            "</div>"+
                            "<div class='col-md-4'>"+
                            "<label for='paid-input' class='form-label'>Paid</label>"+
                            "<input type='text' class='paid-input form-control' name='paid[]' id='paid-input' value='0.00'>"+
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
           
            $("#payment_method").append(payment);
            balance();
        }
    function setPredefinedSupplier(customerId, customerName) 
    {
        // Create a new option with the predefined value
        var option = new Option(customerName, customerId, true, true);

        // Append the option to Select2 and trigger the change event
        $("#cmb_customer").append(option).trigger('change');
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
        var cost_rate = $("#cost_rate"+item);    
        var discountType = $("#discountType"+item);    
        var discount = $("#discount"+item);    
        var total = $("#total"+item);    
        var original_total = $("#original_total"+item); 
        var qty_val=parseFloat(qty.val());   
        var avl_qty_val=parseFloat(avl_qty.val()); 
        var rate_val = parseFloat(rate.val());    
        var original_rate_val = parseFloat(original_rate.val());  
        var cost_rate_val = parseFloat(cost_rate.val());  
        var discountType_val = discountType.val();  
        var discount_val = 0;  
        
        var IsUnderCost = "<?php echo $isUnderCost ?>";
        
        if(IsUnderCost==1) //check if the user is allowed to sell under cost
        {          
            var rateCheckTimeout;
            clearTimeout(rateCheckTimeout); 

            rateCheckTimeout = setTimeout(function() {
                if (rate_val < cost_rate_val) {
                    rate.css("border", "2px solid red");
                    formValid = false;
                    

                    if (!$("#rate-warning"+item).length) {
                        rate.after(`<small id="rate-warning${item}" style="color: red;"> Rate cannot be less than cost rate (Rs.${cost_rate_val.toFixed(2)}) </small>`);
                    }
                    
                    rate.focus();
                } else {
                    rate.css("border", "");
                    $("#rate-warning"+item).remove();
                    formValid = true;
                    
                }
            }, 500);
        }

        if(isNaN(discount.val()))
        {
            discount_val=0;   
        }
        else
        {
            discount_val=parseFloat(discount.val());
        }
        var discount2=0;
        var full_total=0;
        var original_full_total=0;
        var full_Cost_total=0;

        if(discount_val==0 || discount_val=="" || discount_val==undefined || discount_val==null || isNaN(discount_val))
        {
            <?php 
            if($hasPrescription==1)
            {
                ?>
                // discount.val("0");                    
                <?php
            }   
            else{
                ?>
                
            discount.val("0.00");
                <?php
            } 
            ?>
            discount2=0;   
        }
        else
        {
            if (discountType_val == 1) {
                // Percentage discount
                tot = rate_val * qty_val;
                discount2 = (tot * discount_val) / 100;
                discount.val(discount_val); // Keep input as percentage
            } else {
                // Fixed discount per unit
                discount2 = discount_val * qty_val;

                // Ensure the discount input is updated correctly
                discount.val(parseFloat(discount_val).toFixed(2)); // Only store unit discount, not multiplied
            }
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

                
                if (parseInt(IsUnderCost) === 1) {  // Ensure IsUnderCost is a number
                    let full_Cost_total = parseFloat(cost_rate_val) * parseFloat(qty_val);

                    if (full_total < full_Cost_total) {
                        
                        // Highlight the input field
                        total.css("border", "2px solid red");

                        // Show the warning message below the total field
                        if (!$("#total-warning").length) {
                            total.after(`<small id="total-warning" style="color: red;">
                                Total amount cannot be less than cost price (Rs.${full_Cost_total.toFixed(2)})
                            </small>`);
                        }
                        
                        // Keep the warning until it's fixed (don't remove automatically)
                        formValid = false;
                       
                        return false; 
                    } else {
                        // Remove warning if the issue is resolved
                        total.css("border", ""); 
                        $("#total-warning").remove();
                        formValid = true;                        
                    }
                }

            }
            <?php
        }
        else
        {
            ?>
            if(qty_val > avl_qty_val)
            {
                alertqty.text("Quantity Cannot be greater than the available Quantity!");
                qty.val("");
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
                    qty.val("");
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

                    if (parseInt(IsUnderCost) === 1) 
                    {  // Ensure IsUnderCost is a number
                        let full_Cost_total = parseFloat(cost_rate_val) * parseFloat(qty_val);

                        if (full_total < full_Cost_total) {
                            
                            // Highlight the input field
                            total.css("border", "2px solid red");

                            // Show the warning message below the total field
                            if (!$("#total-warning").length) {
                                total.after(`<small id="total-warning" style="color: red;">
                                    Total amount cannot be less than cost price (Rs.${full_Cost_total.toFixed(2)})
                                </small>`);
                            }

                            
                            // Keep the warning until it's fixed (don't remove automatically)
                            formValid = false;
                            
                            return false; 
                        } else {
                            // Remove warning if the issue is resolved
                            total.css("border", ""); 
                            $("#total-warning").remove();
                            formValid = true;                        
                        }
                    }
                }
            }
            
            <?php
        }
        ?>
        
        grandTotal();
    }
    
        // Prevent form submission if validation fails
    $("#wholesale-invoice-form").submit(function(e) {
    if (!formValid) {
        e.preventDefault();
        alert("Please correct the errors before submitting!");
    }
    });
       

    function add_tr()
    {
       // Get the last data-example value and increment it for the new row
        var lastValue = $('.tr').last().data('example') || 0;
        var newValue = lastValue + 1;

        // Create the HTML for the new row
        var tr = 
        "<tr  style='max-width:200px;' id='" + newValue + "' class='tr' data-example='" + newValue + "'>"+
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
            "<td colspan='2'>"+
            "   <input type='text' name='prodDes[]' id='prodDes" + newValue + "' class='form-control w300p' disabled>"+
            "    <select name='batch_id[]' required  id='batch_id" + newValue + "' class='form-control' onchange='batch(" + newValue + ")' disabled style='display:none;'>"+
            "        <option value=''></option>"+
            "    </select>"+
            "   <input type='hidden' name='batch[]' id='batch" + newValue + "' class='form-control' readonly>"+
            "</td>"+
            <?php 
        }
        else
        {
            if($hasbatchNo==1)
            {
                ?>
            "<td>"+
            "    <select name='batch_id[]' required  id='batch_id" + newValue + "' class='form-control' onchange='batch(" + newValue + ")' disabled>"+
            "        <option value=''></option>"+
            "    </select>"+
            "<input type='hidden' name='batch[]' id='batch" + newValue + "' class='form-control' readonly>"+
            "</td>"+    
                <?php
            }
            else
            {
                ?>
                "<td style='display:none;'>"+
                "    <select name='batch_id[]' required  id='batch_id" + newValue + "' class='form-control' onchange='batch(" + newValue + ")' disabled>"+
                "        <option value=''></option>"+
                "    </select>"+
                "<input type='hidden' name='batch[]' id='batch" + newValue + "' class='form-control' readonly>"+
                "</td>"+    
                <?php
            }
        }
        ?>
        
        <?php 
        if($hasPrescription==1)
        {
            ?>
            "<td>"+
            "    <input type='text' name='avl_qty[]' id='avl_qty" + newValue + "' class='form-control text-end w90p' required readonly>"+
            "</td>"+
            "<td>"+
            "    <span class='text-danger' id='alertqty" + newValue + "'></span>"+
            "    <input type='text' name='qty[]' id='qty" + newValue + "' onkeyup='ttotal(" + newValue + ")' onkeydown='ttotal(" + newValue + ")' onkeypress='ttotal(" + newValue + ")' class='qty form-control text-end w90p' required disabled>"+
            "</td>"+
            "<td>"+
                "<span class='text-danger' id='alertrate" + newValue + "'></span>"+
            "    <input type='text' name='rate[]' id='rate" + newValue + "' class='rate form-control text-end w90p' onkeyup='ttotal(" + newValue + ")' onkeydown='ttotal(" + newValue + ")' onkeypress='ttotal(" + newValue + ")'  required disabled>"+
            "    <input type='hidden' name='original_rate[]' id='original_rate" + newValue + "' class='form-control' readonly>"+
            "</td>"+
            "<td>"+
            "   <select name='discountType[]' id='discountType" + newValue + "' onchange='ttotal(" + newValue + ")' class='discountType form-select w90p' disabled>"+
            "       <option value='1'>Percentage</option>"+
            "       <option value='2'>Flat Amount</option>"+
            "   </select>"+
            "</td>"+
            "<td>"+
            "    <input type='text' name='discount[]' id='discount" + newValue + "' class='discount form-control text-end w90p' onkeyup='ttotal(" + newValue + ")' onkeydown='ttotal(" + newValue + ")' onkeypress='ttotal(" + newValue + ")'  value='0.00' disabled>"+
            "</td>"+
            "<td>"+
            "    <input type='text' name='totals[]' id='total" + newValue + "' class='total form-control text-end w150p' required readonly>"+
            "    <input type='hidden' name='original_total[]' id='original_total" + newValue + "' class='form-control' readonly>"+
            "</td>"+
            <?php
        }
        else
        {
            ?>
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
            <?php
        }
        ?>
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
            console.log(params);
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
                value2=value2*qty_val;
                value2=value2;
            }                    
        }

        discount = discount+value2;
    });
    var grossAmount=total+discount;
    
    if($("#saleDiscount").val()=="")
    {
        saleDiscount=0;
    }
    else
    {
        if($("#SaleDiscountType").val()==1)
        {
            saleDiscount=grossAmount*parseFloat($("#saleDiscount").val())/100
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
    $("body #grossAmount").val(grossAmount.toFixed(2));

    $("#totalDiscountLine").val(discount.toFixed(2));
    var total_discount=saleDiscount+discount;
    
    $("#totalDiscount").val(total_discount.toFixed(2));
    total=total-saleDiscount+deliverycharges+otherCharges-returnamount-excessamount;
    $("#netamount").val(total.toFixed(2));
    $("#paid-input").val(total.toFixed(2));
    console.log(returnamount);
    
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
    var cost_rate = $("#cost_rate"+item);
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
            url:'../AJAX/WholeSaleInvoice/invoice.php',
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
                            cost_rate.val(data[0]["Costrate"]);
                            
                            <?php 
                            if($hasPrescription==1)
                            {
                                ?>
                                discountType.val("1");
                                discount.val("0");
                                <?php
                            }
                            else
                            {
                                
                                ?>
                                discountType.val(data[0]["DiscountType"]);
                                discount.val(data[0]["prodDiscount"]);
                                <?php
                            }
                            ?>
                            // discount.val(data[0]["prodDiscount"]);
                            qty.val("1");
                            qty.trigger("keyup")
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
        cost_rate.val(0);
        discount.val(0);
        total.val(0);
        discountType.val(0);
        qty.attr("disabled", true);
        rate.attr("disabled", true);
        discount.attr("disabled", true);
        discountType.attr("disabled", true);
        total.attr("disabled", true);
        $("#item").val("");
        $("#batch").val("");
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
    $("#cost_rate"+item).val("");
    $("#discountType"+item).val("1");
    <?php 
    if($hasPrescription==1)
    {
        ?>
        $("#discount"+item).val("0");
        <?php
    }
    else
    {
        ?>
        $("#discount"+item).val("0.00");                
        <?php
    }
    ?>
    
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
            url:'../AJAX/WholeSaleInvoice/invoice.php',
                method:'post',
                data:{
                    items:selectedValue,
                    SelectedID:item
                    },
                success:function(response)
                {
                    batch.removeAttr("disabled");
                    batch.html(response);
                    if ($.trim(response).includes("No Batch")) 
                    {
                        // alert("No Batch Available");
                        items.html('');
                    }
                    else
                    {
                        batch.trigger("change");
                        batch.focus();
                    }
                }
            });
    }
}
function remove_payment(item)
{
    var value = $(item).attr("id");
    $(item).parent().parent().parent().remove();
    balance();
    
}
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

  <script>
    document.addEventListener('DOMContentLoaded', () => {
    const inputs = document.getElementsByClassName('qty');

    // Loop through each input with the class 'qty'
    for (let i = 0; i < inputs.length; i++) {
        inputs[i].addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault(); // Prevent form submission
        }
        });
    }
    });

  </script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
</body>
</html>