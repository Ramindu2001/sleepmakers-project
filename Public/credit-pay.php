<?php 
include "../Includes/includes.php";
include '../Includes/authcheck.php';
$shopObj=new Shop();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php 
    if(!isset($_GET["cus_id"]) || $_GET["cus_id"]==0 || $_GET["cus_id"]=="" || $_GET["cus_id"]==null)
    {
        $_SESSION["credit"]=5;
        header("Location:../Public/credit-customers.php");
        exit; //without this the page kept running with no customer and crashed after the redirect
    }
    else
    {
        $credit = new credit_customer();
        $id=$_GET["cus_id"];
        $ModuleName = $credit->select_credit_customer($id);
        if(count($ModuleName)==0)
        {
            $_SESSION["credit"]=5;
            // header("Location:../Public/credit-customers.php");
        } 
    }
 include '../View/head.php';
 // include '../View/loader.php';
 include "../View/modals/view-invoice.php";
 include "../View/modals/customer_payment.php";
 $hasexcess=$shopObj->hasexcess($shop_id);
  ?>
  <style>
    .table>:not(caption)>*>* 
    {
        padding: 10px;
    }
    input[readonly]
    {
        background-color: rgb(235, 235, 235);
        border-color: rgb(235, 235, 235);
    }
  </style>
   <?php
    require_once '../View/head.php';
    // require_once '../View/loader.php';
    require_once '../View/datatables.php';
    ?>
</head>

<body>

<?php 

    include '../View/modals/SysFeatures.php';
    
    $sql = "SELECT * FROM shopreceipts WHERE ReceiptStat = 1 AND shop_id='$shop_id' AND RecieptType=2";
    $dbObj = new DBTransactions();
    $dbData = $dbObj->getData($sql);
    $invoices = empty($dbData) ? "wholesaleInvoice.php" : $dbData[0]['ReceiptPath'];
?>


<!--  Body Wrapper -->
<div class="h-100vh">
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
            data-sidebar-position="fixed" data-header-position="fixed">
            <!-- Sidebar Start -->
            <?php 
             include '../View/sidebar.php';
             $feature_id=9;
             include '../Includes/editPermission.php';
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
                    <h5 class="card-title fw-semibold mb-4" style="color:black;">Credit Customers List</h5>
                    <?php 
                    if(isset($_SESSION["credit_customer"]))
                    {
                        if($_SESSION["credit_customer"]==0)
                        {
                            ?>
                            <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                <strong>Oops! </strong> Something went wrong, Please try again!
                            </div>
                            <?php
                        }
                        elseif ($_SESSION["credit_customer"]==1) 
                        {
                            ?>
                            <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                <strong>Success </strong> Payment Added Successfully!
                            </div>
                            <?php
                        }
                        unset($_SESSION["credit_customer"]);
                    }
                    ?>
                    <br>
                    
                    <div class="card">
                        <div class="card-body">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="text-danger">
                                        Note: - Total payable minus = exccess amount or advance payment.
                                        <input type="hidden" name="" id="invoices" value="<?=$invoices?>">
                                    </p>
                                </div>
                                <div class="col-md-12">
                                    <div style="display: flex; justify-content: flex-end;">
                                        <div class="form-check form-switch py-2">
                                            <input class="form-check-input" type="checkbox" id="flexSwitchCheckChecked">
                                            <label class="form-check-label" for="flexSwitchCheckChecked">Multiple Invoice Single payment</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <h3 style="color:black;">Customer Credits</h3>
                                    <div style="display: flex; justify-content: flex-end;">
                                        <button class="btn btn-success" onclick="generatePDF()">Download PDF</button>
                                    </div>
                                    <div class="table-responsive">
                                      <table class="table search-table align-middle text-nowrap" id="tbl_active_store">
                                        <thead class="header-item">  
                                            <tr>
                                                <th>SL</th>
                                                <th>Invoice No</th>  
                                                <th>Invoice Date</th>
                                                <th>Invoice Amount</th>   
                                                <th>Credit Amount</th> 
                                                <th>Settled Amount</th>                     
                                                <th class="text-end">Due Amount</th>                     
                                                <th class="text-center">View Invoice</th>                     
                                                <!-- <th class="text-center">Make Payment</th> -->
                                            </tr>                                        
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $i=1;
                                            $amount=0;
                                            $details=$credit->credit_customer_details($id,$shop_id);
                                            foreach ($details as $row)
                                            {
                                                $pending=$row["total_credit"]-$row["total_debit"];
                                                ?>
                                                <tr>
                                                    <td><?=$i?></td>
                                                    <td><?=$row["invoice"]?></td>
                                                    <td><?=$row["EffectiveDate"]?></td>
                                                    <td><?=$row["invoiceAmount"]?></td>
                                                    <td><?=number_format((float)$row["total_credit"], 2,'.',',')?></td>
                                                    <td><?=number_format((float)$row["total_debit"], 2,'.',',')?></td>
                                                    <td class="text-end"><?=number_format((float)$pending, 2,'.',',')?></td>
                                                    <td class="text-center">
                                                        <input type="hidden" name="" id="invoiceID" value="<?=$row["IHID"]?>">
                                                        <?php 
                                                        $sql="SELECT * FROM invoiceheader WHERE IHID='$row[IHID]'";
                                                        $query=$dbObj->getData($sql);
                                                        $count=count($query);
                                                        if($count>0)
                                                        {
                                                            ?>
                                                            <a href="javascript:void(0);" class="btn btn-primary viewInvoice">View Invoice</a>
                                                            <?php
                                                        }
                                                        ?>
                                                        
                                                    </td>
                                                    
                                                    <td class="text-center">
                                                        <input type="hidden" name="" id="invoiceID" value="<?=$row["IHID"]?>">
                                                        <a href="javascript:void(0);" class="btn btn-success PaymentPay" data-invoice-id="<?=$row["IHID"]?>" data-due="<?=$pending?>">Pay</a>
                                                    </td>
                                                </tr>
                                                <?php
                                                $amount=$amount+$pending;
                                                $i++;
                                            }
                                            ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="5" class="text-end"><b>Total Due</b></td>
                                                <td class="text-end"><b><?=number_format((float)$amount, 2,'.',',')?></b></td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                      </table>
                                    </div>
                                </div>
                                <form action="../Controller/CreditCustomer.php" method="post">
                                    <input type="hidden" name="cus_id" id="cus_id" readonly value="<?=$id?>" class="form-control">
                                    <div class="col-md-12">
                                        <h3>Make Payment</h3>
                                        <div class="table-responsive">
                                            <table class="table search-table align-middle text-nowrap" id="tbl_active_store">
                                                <thead class="header-item">  
                                                    <th>Invoice No <span class="text-danger">*</span></th>
                                                    <th>Due Amount <span class="text-danger">*</span></th>
                                                    <th>Amount <span class="text-danger">*</span></th>
                                                    <th>Action</th>
                                                </thead>
                                                <tbody id="tbody">
                                                    <tr>
                                                        <td>
                                                            <select name="invoice_id[]" id="invoice_id" class="form-control invoice_id" required>
                                                                <option value="">Select Invoice</option>
                                                                <?php 
                                                                $details=$credit->credit_customer_details($id,$shop_id);
                                                                foreach ($details as $row)
                                                                {
                                                                    $pending=$row["total_credit"]-$row["total_debit"];
                                                                    if($pending>0)
                                                                    {
                                                                        ?>
                                                                        <option value="<?=$row["invoice_header_id"]?>"><?=$row["invoice"]?></option>
                                                                        <?php
                                                                    }
                                                                    else
                                                                    {

                                                                    }
                                                                }
                                                                ?>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="text" name="due[]" id="due" class="form-control" readonly required>
                                                        </td>
                                                        <td>
                                                            <input type="text" name="amount[]" id="amount" class="form-control amount" required>
                                                        </td>
                                                        <td> 
                                                            <!-- <a href="javascript:void(0)" class="btn btn-primary" id="add"><i class="ti ti-plus"></i></a> -->
                                                             <button type="button"  class="btn btn-primary" id="add" disabled><i class="ti ti-plus"></i></button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="2" class="text-end"><b>Total Paid</b></td>
                                                        <td><input type="text" name="total_paid" id="total_paid" class="form-control" readonly required></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card p-2">
                                            <h5 class="card-title">Payment</h5>
                                            <div class="row mb-3" id="payment_methods">
                                                <div id="payment_method" class="row">
                                                    <div class="col-md-4 mb-2">
                                                        <label for="" class="form-label">Payment Type</label>
                                                        <select name="pay_id[]" id="pay_id" class="pay_id form-select">
                                                        <?php 
                                                                    $sql = "SELECT * FROM shoppaymethod
                                                                    INNER JOIN paymethod ON paymethod.PMID = shoppaymethod.paymethod_PMID
                                                                    WHERE shop_SHID = ".$shop_id."  AND (paymethod.PMID!=6 AND paymethod.PMID!=4 AND paymethod.PMID!=13);";
                                                                    $dbObj = new DBTransactions();
                                                                    $dbPaymethods = $dbObj->getData($sql);
                                                                    $count = 0;
                                                                    foreach($dbPaymethods as $row)
                                                                    {
                                                                        $is_checked = $count==0 ? 'checked' : '';
                                                                        ?>
                                                                        <option value="<?php echo $row['paymethod_PMID'];?>"><?php echo $row['PaymethodName'];?></option>
                                                                        <?php 
                                                                        $count += 1;
                                                                    }//foreach
                                                                ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4 mb-2">
                                                        <label for="" class="form-label">Paid</label>
                                                        <input type="text" class="form-control" name="paid[]" id="paid-input" placeholder="0.00">
                                                    </div>
                                                    <div class="col-md-4 mb-2">
                                                        <button type="button" class="btn btn-primary mt-4" id="add-payment" onclick="addPayment()">Add Payment</button>
                                                    </div>
                                                </div>                                        
                                            </div>                     
                                        </div>
                                    </div>

                                    <input type="hidden" name="form_token" value="<?php echo uniqid(); ?>">
                                    
                                    <?php 
                                    if($userType=1 || $create==1 || $edit==1 || $verify==1)
                                    {
                                        ?>
                                        <div class="col-md-12 d-flex justify-content-end">
                                            <input type="submit" name="credit_repayi" value="Submit & Print Invoice" class="btn btn-primary me-2" onsubmit="disableButton()">
                                            <input type="submit" name="credit_repay" value="Submit" class="btn btn-primary" onsubmit="disableButton()">
                                        </div>
                                        
                                        <?php
                                    }
                                    else
                                    {
                                        ?>
                                        <script>
                                            window.location.href = "./home.php";
                                        </script>
                                        <?php
                                    }
                                    ?>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="hidden"></div>
    <!-- footer Start  -->
    <?php include '../View/footer.php';?> 
    <!-- footer End  -->
     <script>
        function remove_cheque(item)
        {
            $(item).parent().parent().remove();
            $(document).on("keyup",".chqamount", function(){
                var chqamount=0;
                $(".chqamount").each(function(){
                    chqamount = chqamount + parseFloat($(this).val());
                })
                $("#paid-input").val(chqamount);
            });
        }
        function addChq()
        {
                var cheked = 0;

                // if($("#invoice_id").lenght>1)
                // {
                //     cheked=1;
                // }

                // if ($("#flexSwitchCheckChecked").is(':checked')) {
                //     cheked=1
                // }
              var payment =`
                        <div id="chequeDiv" class="chequeDivs col-md-12 mb-2 row">
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
                                    <input type="text" name="chqAmount[]" id="chqamount" class="chqamount form-control" placeholder="Ex: 2000">
                                </div>
                                <div class="col-md-4">
                                <a href='javascript:void(0);' class='remove-payment btn btn-danger mt-4' id='remove-payment' onclick='remove_cheque(this)' ><i class='ti ti-trash'></i></a>
                                <a href='javascript:void(0);' class='btn btn-primary mt-4' onclick='addChq()' ><i class='ti ti-plus'></i></a>
                                </div>
                            </div>
                            `;
                            if(cheked==0)
                            {
                                $("#payment_method").append(payment);
                            }            
        }
        // $("#flexSwitchCheckChecked").change()
        $(document).ready(function() {
            $('#flexSwitchCheckChecked').on('change', function() {
                if ($(this).is(':checked')) {
                    $("#add").removeAttr("disabled");
                    $("#add-payment").attr("disabled",true);
                    $("body .chequeDivs").not(":first").remove(); 
                } else {
                    $("#add-payment").removeAttr("disabled");
                    $("#add").attr("disabled",true);
                    $("body .invoicesAdd").remove();
                }
            });
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
                                "<input type='text' class='paid-input form-control' name='paid[]' id='paid-input' value='0.00'>"+
                                "</div>"+
                                "<div class='col-md-4 row'>"+
                                    "<div class='col-md-3'>"+
                                        "<a href='javascript:void(0);' class='remove-payment btn btn-danger mt-4' id='remove-payment' onclick='remove_payment(this)' ><i class='ti ti-trash'></i></a>"+
                                    "</div>"+
                                    "<div class='col-md-3'>"+
                                        "<button type='button'  class='btn btn-primary mt-4' id='add-payment' onclick='addPayment()' ><i class='ti ti-plus'></i></button>"+
                                    "</div>"+
                                "</div>"+
                            "<div>";
            
                $("#payment_method").append(payment);
            }
            
        function remove_payment(item)
        {
            var value = $(item).attr("id");
            $(item).parent().parent().parent().remove();
            
        }
        $(document).ready(function(){
            $(document).on("keyup",".chqamount", function(){
                var chqamount=0;
                $(".chqamount").each(function(){
                    chqamount = chqamount + parseFloat($(this).val());
                })
                $("#paid-input").val(chqamount);
                console.log(chqamount);
            });
            $(document).on("change",".pay_id",function(){
                var selectedValue=$(this).val();
                var isDuplicate = false;
                var cheked = 0;
                if ($("#flexSwitchCheckChecked").is(':checked')) {
                    cheked=1
                }
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
                        if($(this).val()==5 && cheked==0)
                        {
                            var payment =`
                        <div id="chequeDiv" class="chequeDivs col-md-12 mb-2 row">
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
                                    <input type="text" name="chqAmount[]" id="chqamount" class="chqamount form-control" placeholder="Ex: 2000">
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
            if ($(window).width() >= 1099) {
                $('.left-sidebar').css("margin-left","-270px");
            }
            
            $('#headerCollapse2').css("display","block");
            $('#headerCollapse3').css("display","none");
            $('.body-wrapper').css("margin-left","0");
            $("#side-closes").css("display","block");
            $(".app-header").css("width","100%");
            $(".viewInvoice").click(function(){
                var invoices= $("#invoices").val();
                var invoiceID= $(this).parent().find("#invoiceID").val();
                var src="../Receipts/<?=$invoices?>?invoice="+invoiceID+"&Iframe=1";
                $("#iframe").attr("src",src);
                $("#viewInvoice_modal").modal("toggle");
            });
            $(".PaymentPay").click(function(){
                var invoices= $("#invoices").val();
                var invoiceID= $(this).parent().find("#invoiceID").val();
                var src="../Receipts/<?=$invoices?>?invoice="+invoiceID+"&Iframe=1";
                $("#iframe").attr("src",src);
                $("#customer_payment_modal").modal("toggle");
            });
            $("#add").click(function () 
            {
                var tr ="<tr class='invoicesAdd'>"+
                            "<td>"+
                                "<select name='invoice_id[]' id='invoice_id' class='form-control invoice_id' required>"+
                                "<option value=''>Select Invoice</option>"+
                                <?php 
                                $details=$credit->credit_customer_details($id,$shop_id);
                                foreach ($details as $row)
                                {
                                    $pending=$row['total_credit']-$row['total_debit'];
                                    if($pending>0)
                                    {
                                        ?>
                                        "<option value='<?=$row['invoice_header_id']?>'><?=$row['invoice']?></option>"+
                                        <?php
                                    }
                                    else
                                    {

                                    }
                                }
                                ?>
                                "</select>"+
                            "</td>"+
                            "<td>"+
                                "<input type='text' name='due[]' id='due' class='form-control' readonly required>"+
                            "</td>"+
                            "<td>"+
                                "<input type='text' name='amount[]' id='amount' class='form-control amount' required>"+
                            "</td>"+
                            "<td> <a href='javascript:void(0)' class='btn btn-danger' id='remove'><i class='ti ti-trash'></i></a></td>"+
                        "</tr>";
                $("#tbody").append(tr);
            });
            
            function grand_total()
            {
                var total=0;
                var amount=0;
                $("body #amount").each(function(){
                    if($(this).val()=="" || $(this).val()==undefined)
                    {
                        amount=0;   
                    }
                    else
                    {
                        amount=parseFloat($(this).val());   
                    }
                    total = total + amount;
                });
                var total2=total.toFixed(2);
                $("#total_paid").val(total2);
                $("#paid-input").val(total2);
            }
            $(document).on('keyup', '.amount', function(){
                $(this).val($(this).val().replace(/[^0-9.]/, ''));
                var invoice_id = $(this).closest("tr").find("#invoice_id");
                var due = $(this).closest("tr").find("#due").val();
                let floatVal = parseFloat(due.replace(/,/g, ''));
                if(invoice_id.val()==0 || invoice_id.val()==undefined)
                {
                    alert("Please Select An Invoice");
                    $(this).val("");
                    invoice_id.focus();
                }
                <?php 
                if($hasexcess==0)
                {
                    ?>
                    if($(this).val() > floatVal)
                    {
                        alert("No excess allowed");   
                        $(this).val(floatVal);
                    }
                    <?php
                }
                ?>
                grand_total();
            });
            $(document).on('change', '.invoice_id', function(){
                var selectedValue = $(this).val();
                var due=$(this).closest("tr").find("#due");
                var amount=$(this).closest("tr").find("#amount");
                var cus_id=$("#cus_id").val();
                var isDuplicate = false;
                if(selectedValue!="")
                {
                    $('.invoice_id').not(this).each(function() {
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
                        $.ajax({
                        url:'../AJAX/CreditPay/credit-pay-ajax.php',
                            method:'post',
                            data:{
                                invoice_id:selectedValue,
                                cus_id:cus_id
                                },
                            success:function(response)
                            {
                                due.val(response);
                                amount.focus();
                            }
                        });
                    }
                }
                else
                {
                    due.val("0.00");
                    amount.val("0.00");
                    $("#total_paid").val("0.00");
                }
                grand_total();
            });
            
            $(document).on('click', '#remove', function(){
                $(this).closest('tr').remove();
                grand_total();
            });
        });
        function generatePDF() {
            window.location.href = 'customerduepdf.php?id=<?= $id ?>';
        }
        
        // Handle Pay button click
        $(document).ready(function() {
            $('.PaymentPay').on('click', function() {
                var invoiceId = $(this).data('invoice-id');
                var dueAmount = $(this).data('due');
                
                // Clear previous entries
                $('#tbody').empty();
                
                // Add a single row with the selected invoice
                var tr = "<tr>" +
                    "<td>" +
                    "<select name='invoice_id[]' class='form-control invoice_id' required>" +
                    "<option value='" + invoiceId + "' data-due='" + dueAmount + "' selected>" + $(this).closest('tr').find('td:nth-child(2)').text() + "</option>" +
                    "</select>" +
                    "</td>" +
                    "<td>" +
                    "<input type='text' name='due[]' id='due' class='form-control' readonly value='" + dueAmount + "'>" +
                    "</td>" +
                    "<td>" +
                    "<input type='text' name='amount[]' id='amount' class='form-control amount' value='" + dueAmount + "'>" +
                    "</td>" +
                    "<td> <a href='javascript:void(0)' class='btn btn-danger' id='remove'><i class='ti ti-trash'></i></a></td>" +
                    "</tr>";
                
                $('#tbody').append(tr);
                $('#total_payment').text(dueAmount);
                
                // Open the payment modal
                $('#customer_payment_modal').modal('show');
            });
        });

        function disableButton() {
            document.getElementById("credit_repayi").disabled = true;
            document.getElementById("credit_repayi").value = "Processing...";
            document.getElementById("credit_repay").disabled = true;
            document.getElementById("credit_repay").value = "Processing...";
        }

</script>
    <script src="../Assets/jquery/credit-pay.js"></script>
    <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/jquery/expense_summary.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>                                           
</body>
</html>