function ttotal(itemRow)
{
    var qty=$("body #qty-"+itemRow);
    var rate=$("body #rate-"+itemRow);
    var original_rate=$("body #original_rate-"+itemRow);
    var discountType=$("body #discountType-"+itemRow);
    var discount=$("body #discount-"+itemRow);
    var total=$("body #total-"+itemRow);
    var original_total=$("body #original_total-"+itemRow);
    var qty_val=$("body #qty-"+itemRow).val();
    var rate_val=$("body #rate-"+itemRow).val();
    var original_rate_val=original_rate.val();
    var discountType_val=discountType.val();
    var discount_val=$("body #discount-"+itemRow).val();
    var total_val=$("body #total-"+itemRow).val();
    var original_total_val=$("body #original_total-"+itemRow).val();
    if(discount_val==0 || discount_val=="" || discount_val==undefined || discount_val==null || isNaN(discount_val))
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
            discount2=discount_val;              
        }
        else
        {
            discount2 = discount_val * qty_val;
        }
    }
    if(qty.val()=="" || qty.val()==null || qty.val()==undefined || qty.val()==0  || qty.val()=="0.00")
        {
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
                    // alertrate.text("Rate Cannot Be empty or 0");
                    rate.val("1.00");
                    full_total=(1*qty_val)-parseFloat(discount2);
                    original_full_total=(qty_val*original_rate_val);
                    total.val(full_total.toFixed(2));
                    original_total.val(original_full_total);
                    rate.focus();
                
            }
            else
            {
                // alertrate.text("");
                full_total=(rate_val*qty_val)-parseFloat(discount2);
                original_full_total=(qty_val*original_rate_val);
                total.val(full_total.toFixed(2));
                original_total.val(original_full_total);
            }
        }
        var rateCheckTimeout;
        clearTimeout(rateCheckTimeout); 

        rateCheckTimeout = setTimeout(function() {
            if (full_total <= 0) {
                total.css("border", "2px solid red");                

                if (!$("#rate-warnings"+itemRow).length) {
                    total.after(`<small id="rate-warnings${itemRow}" class="rate-warnings" style="color: red;">Subtotal cannot be less than or equal to (Rs. 0.00) </small>`);
                }
                else
                {
                    $("#rate-warnings"+itemRow).text(`Subtotal cannot be less than or equal to (Rs. 0.00)`)
                }
                
            } else {
                total.css("border", "");
                $("#rate-warnings"+itemRow).remove();
            }
        }, 500);
        grandTotal();
}
function grandTotal()
{
    var grosstotal=0;
    var discount=0;
    var saleDiscount=0;
    var netTotal=0;
    var TotalQty=0;
    var itemTotal=0;
    $("body .qty").each(function(){
        var Qtyvalue = $(this).val();
        TotalQty=TotalQty+parseFloat(Qtyvalue);
    });
    $("body .original_total").each(function(){
        var original_totalvalue = $(this).val();
        grosstotal=grosstotal+parseFloat(original_totalvalue);
    });
    $("body .totals").each(function(){
        var totalsvalue = $(this).val();
        itemTotal=itemTotal+parseFloat(totalsvalue);
    });
    if($("#invoice_discount").val()=="" || $("#invoice_discount").val()==undefined || $("#invoice_discount").val()==null || isNaN($("#invoice_discount").val()))
    {
        saleDiscount=0;
    }
    else
    {
        if($("#invoice_discount_type").val()==1)
            {
                saleDiscount=grosstotal*parseFloat($("#invoice_discount").val())/100
            }
            else
            {
                saleDiscount=parseFloat($("#invoice_discount").val());
            }
        // saleDiscount=$("#invoice_discount").val();
    }
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
                discount = discount+value2;                 
            }
            else
            {
                value2=value2 * qty_val;
                discount = discount+value2;
            }                    
        }

    });
    var TotalDiscount=discount+saleDiscount;
    netTotal=itemTotal;
     

    $("#totalQty").text(TotalQty.toFixed(2));
    $("#totalOriginalRateTotal").text("Rs. "+grosstotal.toFixed(2));
    $("#totalDiscount").text("Rs. "+TotalDiscount.toFixed(2));
    $("#NetTotal").text("Rs. "+netTotal.toFixed(2));
    $("body #netamount").val(netTotal);
    $("body #totQty").val(TotalQty);
    $("body #totalDiscounti").val(TotalDiscount);
    $("body #grossTotal").val(grosstotal);


}
function initializeSelect2() {
    var shop_id = $("#select-shop").val();

    $("#search-customers").select2({
        ajax: {
            url: '../AJAX/salesreturn/getCustomers.php?shop_id=' + shop_id,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term,
                    type: 'item_search'
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            }
        },
        cache: true,
        placeholder: 'Search for Customer',
        minimumInputLength: 1,
        width: '80%',
    });
    $(document).off("click", ".increase-qty").on("click",".increase-qty",function(){
        var existingRow = $(this).closest("tr");
        var itemRow = existingRow;
        var item_qty = itemRow.find(".qty");

        var currentQty = parseFloat(item_qty.val()) || 0;
        var newQty = currentQty + 1;

        
        item_qty.val(newQty);
        item_qty.trigger("change");
    });
    $("body").off("click", ".decrease-qty").on("click",".decrease-qty",function(){
        var existingRow = $(this).closest("tr");
        var itemRow = existingRow;
        var item_qty = itemRow.find(".qty");

        var currentQty = parseFloat(item_qty.val()) || 0;
        var newQty = currentQty - 1;

        if (newQty == 0 || newQty < 0) {
            item_qty.val(1);
        } else {
            item_qty.val(newQty);
            item_qty.trigger("change");
        }
    });
    $("#search-invoices").select2({
        ajax: {
            url: '../AJAX/salesreturn/getinvoices.php?shop_id=' + shop_id,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term,
                    type: 'item_search'
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            }
        },
        cache: true,
        placeholder: 'Search For Invoice No',
        minimumInputLength: 1,
        width: '80%',
    });
    
    $("#search-items").select2({
        ajax:{
            url: '../AJAX/salesreturn/getItems.php?shop_id=' + shop_id,
            dataType: 'json',
            delay: 250,
            data: function(params){
                // console.log(params);
                
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
        placeholder: 'Item name/Barcode/Itemcode',
        minimumInputLength: 1,
        width: '90%',
    });
    $("#cart").html("");
    grandTotal();
}
function setPredefinedCustomer(customerId, customerName) 
{
    // Create a new option with the predefined value
    var option = new Option(customerName, customerId, true, true);

    // Append the option to Select2 and trigger the change event
    $("#search-customers").append(option).trigger('change');
}
function setPredefinedinvoice(invoiceID, invoiceNo) 
{
    // Create a new option with the predefined value
    var option = new Option(invoiceNo, invoiceID, true, true);

    // Append the option to Select2 and trigger the change event
    $("#search-invoices").append(option).trigger('change');
}
function clearCart()
{
    $("#cart").html("");
    var shop_id=$("#ori_shop").val();
    $("#select-shop").val(shop_id).trigger("change");
    $("#return-type").val("1").trigger("change");
    $("#with-invoice").prop("checked", false);
    $(".invoices").fadeOut();
    $("#search-invoices").removeAttr("required");
    $("#remarks").val("");
    grandTotal();
}
function submitForm()
{
    
    $.ajax({
        url: '../Controller/SalesReturnControl.php?submitForm=1',
        method: 'post',
        data: $("#form").serialize(),
        dataType: 'json',  // Ensure that jQuery automatically parses the JSON
        success: function(response) {
            // Show success messages if any
            if (response["Success"]) {
                if (Array.isArray(response["Success"])) {
                    response["Success"].forEach(function(successMsg) {
                        toastr.success(successMsg, "Success");
                    });
                } else {
                    toastr.success(response["Success"], "Success");
                }
                if (response["Success"].includes("Sales Return Created Successfully")) 
                    {
                        clearCart(); // Call the function to clear the cart
                    }
            }
            
            if(response["returnID"])
            {
                var domain= window.location.hostname;
                var strWindowFeatures = "location=yes,height=700,width=520,scrollbars=yes,status=yes";
                var URL = "../Receipts/sales-return-credit-note.php?invoice_id="+response["returnID"]+"&print=1";
                var win = window.open(URL, "_blank", strWindowFeatures);
            }
            // Show error messages if any
            if (response["Error"]) {
                if (Array.isArray(response["Error"])) {
                    response["Error"].forEach(function(errorMsg) {
                        toastr.error(errorMsg, "Error");
                    });
                } else {
                    toastr.error(response["Error"], "Error");
                }
            }
        },
        error: function(xhr, status, error) {
            console.log("AJAX Error:", status, error);
            console.log("Response:", xhr.responseText);
            toastr.error("An error occurred while processing.", "Error");
        }
    });
}
function validateForm() {// Get CKEditor content
    var allow =true;
    var editorData=$("#remarks").val().trim();
    $("#error-msg").toggle(editorData === "");
    if (editorData === "") 
    {
        toastr.error("Reason is mandatory", "Error");
        allow = false;
    }
    if(($("#search-customers").val()=="" || $("#search-customers").val()=="0"))
    {
        allow=false;
        $("#error-msg-customer").fadeIn();
        toastr.error("Customer is mandatory", "Error");
    }
    if($("#with-invoice").is(":checked") || $("#return-type").val()==3)
    { 
        if(($("#search-invoices").val()=="" || $("#search-invoices").val()=="0"))
        {
            allow=false;
            $("#error-msg-invoice").fadeIn();
            toastr.error("Invoice is mandatory", "Error");
            $("#invoices").fadeIn();
        }
    }
    if($("body .Item_name").length==0)
    {
        allow = false;
        toastr.error("Cart Cannot Be Empty", "Error");
    }
    if($("body .rate-warnings").length > 0)
    {
        allow = false;
        toastr.error("Please Resolve The Errors", "Error");
    }
    if($("#search-customers").val()==1 && $("#return-type").val()==3)
    {
        toastr.error("Common Customer Cannot Have Credit Reduction", "Error");
    }
    
    if(allow==true)
    {
        return true;
    }
    else
    {
        return false;
    }
    
}

$(document).ready(function()
{
    $("body").on("click",".printRIHID", function(){
        var RIHID=$(this).data("rihid");
        
        var domain= window.location.hostname;
        var strWindowFeatures = "location=yes,height=700,width=520,scrollbars=yes,status=yes";
        var URL = "../Receipts/sales-return-credit-note.php?invoice_id="+RIHID+"&print=1";
        var win = window.open(URL, "_blank", strWindowFeatures);
    })
    $("#subBtn").click(function(){
        if (validateForm()==true) {
            submitForm();
        }
        // submitForm();
    });
    $("#remarks").keyup(function(){
        var editorData=$("#remarks").val().trim();
        if (editorData !== "") {
            $("#error-msg").fadeOut();
        }
        else
        {
            $("#error-msg").fadeIn();
        }

    })
    $("#return-type").change(function(){
        var value = $(this).val();
        if(value == 3)
        {
            $("#with-invoice").prop("checked",true);
            $("#with-invoice").trigger("click")
        }
    })
    $("#close").click(function(){
        if(confirm("Are You Sure You Want To Clear The Form?"))
        {
            clearCart();
        }
        
        // window.location.href = "../Public/salesReturn.php";
    });
    $("body").on("click",".removeid", function(){
        $(this).closest("tr").remove();
        grandTotal();
    })
    $("#search-items").change(function(){
        var shop_id = $("#select-shop").val();
        var selectedValue = $("#search-items").val();
        var trCount=$("#cart tr").length;
        var exists=false;
        var PDID=0;
        var qty=0;
        $("#cart tr").each(function () {
            if ($(this).data("product") == selectedValue) {
                PDID=$(this).closest('tr').attr("class");
                exists = true;
                return false; // Stop the loop
            }
        });

        if (exists == true) 
        {
            qty = $("#qty-"+PDID).val();
            qty = parseFloat(qty) + 1;
            $("#qty-"+PDID).val(qty.toFixed(2));
        } 
        else 
        {
            $.ajax({
                url:'../AJAX/salesreturn/getItems.php?shop_id=' + shop_id,
                    method:'post',
                    data:{
                        PDID:selectedValue,
                        trCount:trCount
                        },
                    success:function(response)
                    {
                        if ($.trim(response).includes("No Inventory Found")) 
                        {
                            alert("No Inventory Found");
                        }
                        else
                        {
                            $("#cart").append(response);
                            grandTotal();
                        }
                    }
                });    
        }
        $("#search-items").val(null); // Clear the selection
        $("#search-items").select2("destroy"); 
        var shop_id = $("#select-shop").val();

        $("#search-items").select2({
            ajax:{
                url: '../AJAX/salesreturn/getItems.php?shop_id=' + shop_id,
                dataType: 'json',
                delay: 250,
                data: function(params){
                    // console.log(params);
                    
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
            placeholder: 'Item name/Barcode/Itemcode',
            minimumInputLength: 1,
            width: '90%',
        });
        $("#search-items").focus();
        grandTotal();
        
        
    });
    $("#invoiceNo").focus();
    $('#invoiceNo').on('input', function() {
        $(this).val($(this).val().replace(/[^a-zA-Z0-9_-]/, ''));
    });
    if ($(window).width() >= 1099) {
        $('.left-sidebar').css("margin-left","-270px");
    }
    $("#with-invoice").click(function(){
        var value = $("#return-type").val();
        if(value==3 && !$(this).prop("checked"))
        {
            $(this).prop("checked", true); // Re-check it
            $(".invoices").fadeIn();
        }
        else
        {
           if($(this).is(":checked"))
            {
                $(".invoices").fadeIn();
                $("#search-invoices").attr("required", true);
            }
            else
            {
                $(".invoices").fadeOut();
                $("#search-invoices").removeAttr("required");
            } 
        }
        
    })
    $('#headerCollapse2').css("display","block");
    $('#headerCollapse3').css("display","none");
    $('.body-wrapper').css("margin-left","0");
    $("#side-closes").css("display","block");
    $('input:text[readonly]').css("background-color","#ebebeb");
    $('input:text[readonly]').css("border-color","#ebebeb");
    $('input:hidden[readonly]').css("background-color","#ebebeb");
    $('input:hidden[readonly]').css("border-color","#ebebeb");
    $("body #returnqty").on('input', function() {
        $(this).val($(this).val().replace(/[^0-9.]/, ''));
    });
    $(".app-header").css("width","100%");
    $("#clearFilter").removeClass("active");

    // Initialize Select2 on page load
    initializeSelect2();
        $("#search-customers").on("change", function(){
            $("#error-msg-customer").fadeOut();
        });
        $("#search-invoices").on("change", function(){
            $("#error-msg-invoice").fadeOut();
        });

    // When #select-shop changes, reinitialize #search-customers
    $("#select-shop").on("change", function () {
        $("#search-customers").val(null).trigger("change"); // Clear the selection
        $("#search-customers").select2("destroy"); // Destroy the existing Select2 instance
        $("#search-invoices").val(null).trigger("change"); // Clear the selection
        $("#search-invoices").select2("destroy"); // Destroy the existing Select2 instance
        $("#search-items").val(null); // Clear the selection
        $("#search-items").select2("destroy"); // Destroy the existing Select2 instance
        initializeSelect2(); // Reinitialize Select2 with the new shop_id
    });
    
    var shop_name = $("#shop_name").val();
    var shop_address_one = $('#shop_address_one').val();
    var shop_address_two = $('#shop_address_two').val();
    var shop_city = $('#shop_city').val();
    var shop_number = $('#shop_number').val();
    var start = $('#start').val() || "";
    var end = $('#end').val() || "";
    var reportname="Sales Return";
    $("#tbl_sales_return").DataTable({
        paging: true,
        lengthChange: true,
        searching: true,
        pageLength: 5,
        layout:{
            topStart:{
                buttons:[
                    //====================== PDF
                    {
                        extend: 'pdf',
                        customize: function (doc){
                            doc.content.splice(0,1,{
                                text: [
                                    {text: shop_name + "\n", bold: true, fontSize: 16},
                                    {text: shop_address_one + ",\n", bold: true, fontSize: 12},
                                    {text: shop_address_two + ",\n", bold: true, fontSize: 12},
                                    {text: shop_number + ",\n", bold: true, fontSize: 12},
                                    {text: reportname+" from "+start+" to "+end + ",\n", bold: true, fontSize: 14}
                                ]
                            }) 
                        },
                        download: 'open'
                    },
                    
                    //===================== excel
                    {
                        extend: 'excel',
                        title: reportname+" from "+start+" to "+end,
                    },
                ]
            } 
        }
    });
})