<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';

?>

<!doctype html>
<html lang="en">

<head>
  <?php 
  include '../View/head.php';
  // include '../View/loader.php';
  if(!isset($_GET['invoice'])||$_GET['invoice']==0||$_GET['invoice']==''||$_GET['invoice']==null)
  {
    $_SESSION['salesreturn_update'] = 5;
    header('Location:./sales-return.php');
  }
  else
  {
    $invoice_id=$_GET['invoice'];
    $dbObj = new DBTransactions();
    //get company stat
    $sql = "SELECT * FROM shop
    INNER JOIN company ON company.CMID = shop.Company_CMID
    WHERE SHID = ".$shop_id.";";

    $shopData = $dbObj->getData($sql);
    $multi_category = floatval($shopData[0]['is_multicategory']);
    $company_id = floatval($shopData[0]['CMID']);
    $salesreturn=new Sales_return_class;
    $return_check=$salesreturn->check_return($invoice_id);
    $check_invoice=$salesreturn->getinvoicebyinvoiceid($invoice_id,$shop_id,$multi_category,$company_id);
    if(count($return_check)>0)
    {
        $_SESSION["salesreturn_update"] = 1;                
        header("Location:../Public/sales-return.php");
    }
    if(count($check_invoice)==0)
    {
        $_SESSION["salesreturn_update"] = 0;                
        // header("Location:../Public/sales-return.php");
    }
    if($multi_category!=1)
    {
        if($check_invoice[0]["shop_SHID"]!= $shop_id)
        {
            $_SESSION["salesreturn_update"] = 5;                
            header("Location:../Public/sales-return.php");
        }
    }
    
    $invoiceDetails=$salesreturn->getinvoicedetailsbyinvoiceid($invoice_id);
  }
  ?>
  <style>
    .card-header 
    {
        background: #fff;
        border-bottom: 1px solid #e7e7e7;
        padding: 10px;
    }
    td ,th
    {
        border: 1px solid #e7e7e7;
    }
    .table>:not(caption)>*>*
    {
        padding: 10px !important;
    }
    span.select2-dropdown.select2-dropdown--below {
    margin-top: -13px;
    }
    .select2-container--default .select2-selection--single
    {
        border:none !important;
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
  </style>
</head>

<body>
<!--  Body Wrapper -->
<div class="h-100vh">
<div class="page-wrapper mini-sidebar" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="mini-sidebar" data-sidebar-position="fixed" data-header-position="fixed">
    <!-- Sidebar Start -->
    <?php 
    include '../View/sidebar.php';
    $feature_id=10;
    if($userType==0)
    {
        $userObj=new User();
        $checkview=$userObj->userAcces($userRole_id,$feature_id);
        $create=$checkview[0]["is_create"];
        $view=$checkview[0]["is_view"];
        $edit=$checkview[0]["is_edit"];
        $delete=$checkview[0]["is_delete"];
        $verify=$checkview[0]["is_verify"];
        $print=$checkview[0]["is_print"];
        if($edit==1 || $verify==1 || $create==1)
        {
            
        }
        else
        {
            ?>
            <script>
                window.location.href = "./home.php";
            </script>
            <?php
        }
    }
    ?>
    <!--  Sidebar End -->
    <!--  Main wrapper -->
    <div class="body-wrapper">
        <!--  Header Start -->
        <?php 
        include '../View/header.php';
        include "../View/modals/main-category.php";
        ?>
        <!--  Header End -->

        <div class="container-fluid">
            <!-- messages -->
            <div class="row">
                <div class="col-md-12">
                    <?php 
                    if(isset($_SESSION["salesreturn_update"]))
                    {
                        if($_SESSION["salesreturn_update"]==1)
                        {

                        }
                        elseif ($_SESSION["salesreturn_update"]==2) 
                        {
                            # code...
                        }
                        else
                        {
                            ?>
                            <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                <strong>Oops!Something Went Wrong,</strong>Please Try Again
                            </div>
                            <?php
                        }
                        unset($_SESSION["salesreturn_update"]);
                    }
                    ?>
                    <div class="card p-2">
                        <div class="card-header">
                            <h5 class="card-title fw-semibold mb-3">Sales Return:-  <span style="color: #d71920;font-weight: 900;"><?=$check_invoice[0]["BillNo"]?></span> </h5>
                            <?php 
                            $customer=$salesreturn->customer_by_id($check_invoice[0]["customers_CTID"])
                            ?>
                        </div>
                        <div class="card-body">
                            <form id="sales-return-form" action="../Controller/SalesReturnControl.php" method="post">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="customer-name" class="form-label">Customer Name</label>
                                        <input type="text" name="customer_name" id="customer_name" value="<?=$customer[0]["CustName"]?>" class="form-control" readonly>
                                        <input type="hidden" name="customer_id" class="form-control mt-2" value="<?=$customer[0]["CTID"]?>" readonly>
                                        <input type="hidden" name="invoice_id" class="form-control mt-2" value="<?=$check_invoice[0]["IHID"]?>" readonly>
                                        <input type="hidden" name="invoice_No" class="form-control mt-2" value="<?=$check_invoice[0]["BillNo"]?>" readonly>
                                    </div>
                                    <div class="col-md-6"></div>
                                    <div class="col-md-6">
                                        <label for="IDate" class="form-label">Invoice Date</label>
                                        <input type="text" name="IDate" id="IDate" class="form-control" value="<?=$check_invoice[0]["EffectiveDate"]?>" readonly>
                                    </div>
                                    <div class="col-md-6"></div>
                                    <?php 
                                    if($userType==1 || $edit==1 || $verify==1 || $create==1)
                                    {
                                        ?>
                                        <div class="col-md-12 mt-5">
                                            <div class="table-responsive">
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                            <th>SN</th>
                                                            <th>Item Information</th>
                                                            <th>Sold Quantity</th>
                                                            <th>Return Quantity</th>
                                                            <th>Rate</th>
                                                            <th>Discount</th>
                                                            <th>Amount</th>
                                                            <th>Total</th>
                                                            <th>Check Return</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php 
                                                        $sl=1;
                                                        foreach ($invoiceDetails as $row) 
                                                        {
                                                            ?>
                                                            <tr id="idid_<?=$row["IDID"]?>">
                                                                <td>
                                                                <input type="hidden" name="product_name[]" id="product_name<?=$row['IDID']?>" class="form-control mt-2" value="<?=$row["product_name"]?>" readonly>
                                                                <input type="hidden" name="SellQty[]" id="SellQty<?=$row['IDID']?>" class="form-control mt-2" value="<?=$row["SellQty"]?>" readonly>
                                                                <input type="hidden" name="UnitPrice[]" id="UnitPrice<?=$row['IDID']?>" class="form-control mt-2" value="<?=$row["UnitPrice"]?>" readonly>
                                                                <input type="hidden" name="SellAmount[]" id="SellAmount<?=$row['IDID']?>" class="form-control mt-2" value="<?=$row["SellAmount"]?>" readonly>
                                                                <input type="hidden" name="SellDiscount[]" id="SellDiscount<?=$row['IDID']?>" class="form-control mt-2" value="<?=$row["SellDiscount"]?>" readonly>
                                                                <input type="hidden" name="SoldAmount[]" id="SoldAmount<?=$row['IDID']?>" class="form-control mt-2" value="<?=$row["SoldAmount"]?>" readonly>
                                                                <input type="hidden" name="productID[]" id="productID<?=$row['IDID']?>" class="form-control mt-2" value="<?=$row["products_PDID"]?>" readonly>
                                                                <input type="hidden" name="invoicedetailsid[]" id="invoicedetailsid<?=$row['IDID']?>" class="form-control mt-2" value="<?=$row["IDID"]?>" readonly>
                                                                <input type="hidden" name="batchid[]" id="batchid<?=$row['IDID']?>" class="form-control mt-2" value="<?=$row["batch_no"]?>" readonly>

                                                                    <?=$sl?>
                                                                </td>
                                                                <td>
                                                                    <?=$row["product_name"]?>
                                                                </td>
                                                                <td>
                                                                    <?=$row["SellQty"]?>
                                                                </td>
                                                                <td style="padding: 10px;">
                                                                    <input type="text" name="returnqty[]" onkeypress="returnCheck(<?=$row['IDID']?>)" onkeyup="returnCheck(<?=$row['IDID']?>)" id="returnqty<?=$row['IDID']?>" class="returnqty form-control" readonly>
                                                                    <span class="text-danger" id="alert-text<?=$row['IDID']?>"></span>
                                                                </td>
                                                                <td>
                                                                    <?php 
                                                                    $unitPrice=$row["SellAmount"]/$row["SellQty"];
                                                                    echo $unitPrice;
                                                                    ?>
                                                                </td>
                                                                <td>
                                                                    <?=$row["SellDiscount"]?>
                                                                </td>
                                                                <td>
                                                                    <?=$row["SoldAmount"]?>
                                                                </td>
                                                                <td>
                                                                    <input type="text" name="item_total[]" id="total_<?=$row['IDID']?>" class="total form-control" readonly>
                                                                    <input type="hidden" name="item_discount[]" id="discount_<?=$row['IDID']?>" class="discount form-control" value="<?=$row["SellDiscount"]?>" readonly>
                                                                    <input type="hidden" name="item_total_amount[]" id="total_amount_<?=$row['IDID']?>" class="total_amount form-control" readonly>
                                                                </td>
                                                                <td class="d-flex justify-content-center">
                                                                    <input type="checkbox" name="check[]" value="<?=$row['IDID']?>" onclick="checkbox(<?=$row['IDID']?>)" id="check_<?=$row['IDID']?>" class="check from-check" style="width: 40px; height: 40px;">
                                                                </td>
                                                            </tr>
                                                            <?php 
                                                            $sl=$sl+1;
                                                        }
                                                        ?>
                                                        
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <td colspan="6" rowspan="3">
                                                                <label for="reason" class="form-lable">Reason <span class="text-danger">*</span></label>
                                                                <textarea name="reason" id="reason" class="form-control" required></textarea>
                                                                <span class="form-label w-100 d-block">Usability</span>
                                                                <input type="radio" name="usability" id="usability1" class="form-radio" value="1" checked>
                                                                <span class="form-label">Adjust Stock</span><br>
                                                                <input type="radio" name="usability" id="usability2" class="form-radio" value="0">
                                                                <span class="form-label">Damage Stock</span>
                                                            </td>
                                                            <td>Total Return Amount</td>
                                                            <td><input type="text" name="total_return" id="total_return" class="form-control" readonly></td>
                                                            <td rowspan="3"></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Total Discount</td>
                                                            <td><input type="text" name="total_discount" id="total_discount" class="form-control" readonly></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Total Amount</td>
                                                            <td><input type="text" name="total_amount" id="total_amount" class="form-control" readonly></td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="return-type" class="form-label">Return Type <span class="text-danger">*</span></label>
                                                    <select name="returntype" id="return-type" class="form-select">
                                                        <option value="1">Invoice Return</option>
                                                        <option value="2">Exchange</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-12 mt-3" id="returnItem" style="display: none;">
                                                    <table class="table" id="returntable">
                                                        <thead>
                                                            <tr>
                                                                <th>Actual Item</th>
                                                                <th>Exchange Item</th>
                                                                <th>Batch</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="returnTableTbody">
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="col-md-6 d-flex justify-content-end">
                                                    <input type="submit" value="Submit & Print Invoice" name="sales_returni" id="btnInvoice" class="btn btn-primary mt-4" style="margin-right:10px; display:none;">
                                                    <input type="submit" value="Submit" name="sales_return" id="btn" class="btn btn-primary mt-4">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php
                                    }
                                    ?>
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
<!--  Body Wrapper End -->

    <!-- footer Start  -->
    <?php include '../View/footer.php';?> 
    <!-- footer End  -->

    <script src="../Assets/jquery/sales-return.js"></script>
    <script>
        function changeitemBatch(item)
        {
            var selectedValue=$("#changeitem"+item).val();
            var batch=$("#changeitemBatch"+item);
            $.ajax({
                    url:'../AJAX/WholeSaleInvoice/invoice.php',
                        method:'post',
                        data:{
                            items:selectedValue
                            },
                        success:function(response)
                        {
                            batch.removeAttr("disabled");
                            batch.html(response);
                            batch.focus();
                        }
                    });
        }
        function returnCheck(item) 
        {
            $("#returnqty" + item).on('input', function() {
                $("#returnqty" + item).val($("#returnqty" + item).val().replace(/[^0-9.]/, ''));
            });
            var returnqty = parseFloat($("#returnqty" + item).val());
            var returnqty2 = $("#returnqty" + item).val();
            var SellQty = parseFloat($("#SellQty" + item).val());
            var discount = parseFloat($("#SellDiscount" + item).val());
            var SoldAmount = parseFloat($("#SoldAmount" + item).val());
            var SellAmount = parseFloat($("#SellAmount" + item).val());
            if(returnqty2==0 || returnqty2=='')
            {
                $("#total_"+item).val("");
                $("#discount_"+item).val("");
                $("#total_amount_"+item).val("");
                $("#alert-text"+item).fadeIn();
                $("#alert-text"+item).text("Return quantity must be greater 0");
                $("#btn").attr("disabled", true);
                grand_total();

            }
            else
            {
                
                $("#alert-text"+item).fadeOut();
                $("#btn").removeAttr("disabled");
                if (returnqty > SellQty) 
                {
                    $("#alert-text"+item).fadeIn();
                    $("#btn").attr("disabled", true);
                    $("#alert-text"+item).text("Return quantity cannot be greater than sold quantity");
                    $("#returnqty" + item).val("");
                    $("#btn").attr("disabled", true);
                    $("#btn").removeAttr("disabled");
                    grand_total();
                }
                else
                {
                    $("#alert-text"+item).fadeOut();
                    $("#btn").removeAttr("disabled");
                    var peramount=SoldAmount/SellQty;
                    var persellamount=SellAmount/SellQty;
                    var perdiscount=discount/SellQty;
                    var total=peramount*returnqty;
                    var totaldiscount=perdiscount*returnqty;
                    var totalsell=persellamount*returnqty;
                    var totalsell2=totalsell.toFixed(2);
                    var totaldiscount2=totaldiscount.toFixed(2);
                    var total2=total.toFixed(2);
                    $("#total_"+item).val(total2);
                    $("#discount_"+item).val(totaldiscount2);
                    $("#total_amount_"+item).val(totalsell2);
                    grand_total();
                }
            }
        }
        function grand_total()
        {
            var discount = 0;
            var total_sell = 0;
            var tot=0;
            $("table tbody tr td .total").each(function(){
                var tot2=$(this).val();
                var tot3=0
                if(tot2==0 || tot2=='')
                {
                    tot3=0
                }
                else
                {
                    tot3=tot2;
                }
                tot+=parseFloat(tot3);
            });
            $("table tbody tr td .discount").each(function(){
                var discount2=$(this).val();
                var discount3=0
                if(discount2==0 || discount2=='')
                {
                    discount3=0
                }
                else
                {
                    discount3=discount2;
                }
                discount+=parseFloat(discount3);
            });
            $("table tbody tr td .total_amount").each(function(){
                var total_sell2=$(this).val();
                var total_sell3=0
                if(total_sell2==0 || total_sell2=='')
                {
                    total_sell3=0
                }
                else
                {
                    total_sell3=total_sell2;
                }
                total_sell+=parseFloat(total_sell3);
            });
            var tot4=tot.toFixed(2);
            var discount4=discount.toFixed(2);
            var total_sell4=total_sell.toFixed(2);
            $("#total_return").val(tot4);
            $("#total_discount").val(discount4);
            $("#total_amount").val(total_sell4);

        }
        function validateForm() {
            const checkboxes = $('input[type="checkbox"]');
            let isChecked = false;

            checkboxes.each(function() {
                if ($(this).is(':checked')) {
                    isChecked = true;
                    return false; // break out of the loop
                }
            });

            if (!isChecked) {
                alert('Please select at least one item to return.');
                $(".check").focus();
                return false;
            }

            return true;
        }
        function checkbox(item)
        {
            var checkbox = $("#check_"+item);
            var container = $("#idid_" + item);

            if(checkbox.prop('checked'))
            {
                container.find("input").attr("required", true);
                $("#returnqty" + item).removeAttr("readonly");
                $("#returnqty" + item).css("background-color","transparent");
                $("#returnqty" + item).css("border-color","#DFE5EF");
                $("#returnqty" + item).focus();
                if($("#return-type").val()==2)
                {
                        var product_name=$("#product_name"+item).val();
                        var productID=$("#productID"+item).val();
                        var invoicedetailsid=$("#invoicedetailsid"+item).val();
                    tr="<tr id='exchangeItem"+invoicedetailsid+"'>"+
                            "<td>"+product_name+
                            "<input type='hidden' name='actualItem[]' id='actualItem"+invoicedetailsid+"' value='"+productID+"'>"+
                            "</td>"+
                            "<td>"+
                            "<select name='changeitem[]' id='changeitem"+invoicedetailsid+"' class='form-control' onchange='changeitemBatch("+invoicedetailsid+")'></select>"+
                            "</td>"+
                            "<td>"+
                            "<select name='changeitemBatch[]' id='changeitemBatch"+invoicedetailsid+"' class='form-control' disabled></select>"+
                            "</td>"+
                        
                        "</tr>";
                        $("#returnTableTbody").append(tr);
                        $("#changeitem"+invoicedetailsid).select2({
                            ajax:{
                                url: '../AJAX/salesreturn/getItems.php?id='+productID,
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
                }
                grand_total();
            }
            else
            {
                if($("#exchangeItem"+item).attr("id")!=undefined)
                {
                    container.find("input").removeAttr("required");                    
                }
                
                $("#exchangeItem"+item).remove();
                $("#total_"+item).val("");
                $("#discount_"+item).val("");
                $("#total_amount_"+item).val("");
                $("#returnqty" + item).val("");
                $("#alert-text"+item).fadeOut();
                $("#returnqty" + item).attr("readonly",true);
                $("#returnqty" + item).css("background-color","rgb(235, 235, 235)");
                $("#returnqty" + item).css("border-color","rgb(235, 235, 235)");
                grand_total();
            }
        }
        $(document).ready(function() {
            $('#sales-return-form').submit(function() {
                return validateForm();
            });
        });
    </script>
    <!-- <script src="../Assets/libs/jquery/dist/jquery.min.js"></script> -->
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>

</body>
</html>