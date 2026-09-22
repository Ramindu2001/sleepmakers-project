$(document).ready(function(){
//initialize
$("#btn_credit_pay").css('display', 'none');

var _keyupDiscountTimer = null;


//====================== Esc Pressed =======================//
$('body').keyup(function(e){
    if(e.keyCode == 27)
    {
        var sell_header_id = $("#hide_sell_header_id").val();
        //get cart table total
        var row_count = 0;
        $("#tbl_cart tr").each(function(){
            row_count += 1;
        });//go through table

        if(row_count == 0)
        {
            return;
        }//no items in cart

        $("#modal_payment").modal('toggle');
        var sale_header_id = $("#hide_header_id").val();
        var tmp_bill_no = $("#hide_tmp_no").val();

        var invoice_discount = 0;
        var net_total = 0;

        //get cart data
        var row_count = 0;
        var subtotal = 0;
        var totalItems = 0;
        $("#tbl_cart tr").each(function(){
            row_count += 1;
            var price = $(this).find("td").eq(7).html();
            var itemCount = $(this).find("td").eq(1).html();
    
            if(price != undefined)
            {
                subtotal += parseFloat(price);
                totalItems += parseFloat(itemCount);
            }//has value
        });//go through table
    
        $("#hide_row_count").val(row_count);
        $("#hide_item_count").val(totalItems);
        $("#hide_sell_total").val(subtotal);

        //get sale discounts
        $.get("../AJAX/Invoice/getSellDiscount.php", {
            sell_header_id:sell_header_id
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);
            
            invoice_discount = obj[0]['DiscountAmount'] == null ? 0: obj[0]['DiscountAmount'];
            invoice_discount = isNaN(invoice_discount) ? 0 : parseFloat(invoice_discount);

            net_total = subtotal - invoice_discount;

            //load multipay table
            loadMultipayTable();

            $("#hide_discounted_total").val(net_total);
            $("#inv_bill_total").text("Rs: " + subtotal);
            $("#inv_bill_discount").text("Rs: " + invoice_discount);
            $("#inv_net_total").text("Rs: " + net_total);

            //set focus to payment
            $("#cust_payment").val(net_total);
            $("#cust_payment").focus();
            $("#cust_payment").select();
        });//get sale   
    }//if esc key pressed the modal opened and focused
});//body keyup

$("#btn_open_payment").click(function(){
    var sell_header_id = $("#hide_sell_header_id").val();
    //get cart table total
    var row_count = 0;
    $("#tbl_cart tr").each(function(){
        row_count += 1;
    });//go through table

    if(row_count == 0)
    {
        return;
    }//no items in cart

    $("#modal_payment").modal('toggle');
    var sale_header_id = $("#hide_header_id").val();
    var tmp_bill_no = $("#hide_tmp_no").val();

    var invoice_discount = 0;
    var net_total = 0;

    //get cart data
    var row_count = 0;
    var subtotal = 0;
    var totalItems = 0;
    $("#tbl_cart tr").each(function(){
        row_count += 1;
        var price = $(this).find("td").eq(7).html();
        var itemCount = $(this).find("td").eq(1).html();

        if(price != undefined)
        {
            subtotal += parseFloat(price);
            totalItems += parseFloat(itemCount);
        }//has value
    });//go through table

    $("#hide_row_count").val(row_count);
    $("#hide_item_count").val(totalItems);
    $("#hide_sell_total").val(subtotal);

    //get sale discounts
    $.get("../AJAX/Invoice/getSellDiscount.php", {
        sell_header_id: sell_header_id
    }, function(data){
        // alert(data);
        const obj = JSON.parse(data);
        
        invoice_discount = obj[0]['DiscountAmount'] == null ? 0: obj[0]['DiscountAmount'];
        invoice_discount = isNaN(invoice_discount) ? 0 : parseFloat(invoice_discount);

        net_total = subtotal - invoice_discount;

        //load multipay table
        loadMultipayTable();

        $("#hide_discounted_total").val(net_total);
        $("#inv_bill_total").text("Rs: " + subtotal);
        $("#inv_bill_discount").text("Rs: " + invoice_discount);
        $("#inv_net_total").text("Rs: " + net_total);

        //set focus to payment
        $("#cust_payment").val(net_total);
        $("#cust_payment").focus();
        $("#cust_payment").select();

    });//get sale
});//open modal

$("#cust_payment").keyup(function(e){
    var receipt_name = $('input[name="rdb_receipt"]:checked').val();
    var sell_header_id = $("#hide_sell_header_id").val();

    var cust_pay = parseFloat($("#cust_payment").val());
    var tmp_bill_no = $("#hide_tmp_no").val();
    var counter_id = $("#hide_counter_id").val();
    var paymethod_id = $('input[name="rdb_paymethod"]:checked').val();

    var customer_id = $("#cmb_customer").val();
    customer_id = customer_id == undefined ? 1 : $("#cmb_customer").val();

    var salesman_id = $("#cmb_salesman").val();
    salesman_id = salesman_id == undefined ? 1 : $("#cmb_salesman").val();

    var sub_total = getCartTotal();
    var invoice_discount = 0;
    var net_total = 0;
    var multipay_total = 0;
    var pending_payment = 0;
    var balance = 0;
    var is_credit = false;
    var _keyupEnterKey = e.keyCode;

    clearTimeout(_keyupDiscountTimer);
    _keyupDiscountTimer = setTimeout(function(){

    $.get("../AJAX/Invoice/getSellDiscount.php", {
        sell_header_id: sell_header_id
    }, function(data){
        const obj = JSON.parse(data);
        invoice_discount = obj[0]['DiscountAmount']==null ? 0 : obj[0]['DiscountAmount'];

        net_total = sub_total - invoice_discount;
        
        multipay_total = getMultipayTotal();
        
        pending_payment = net_total - multipay_total;

        balance = cust_pay - pending_payment;

        var show_balance = "<small>Return Amount</small><br><b>"+ balance +"</b>";
        $("#p_balance").html(show_balance);

        if(balance < 0)
        {
            is_credit = true;
            //show credit button
            $("#btn_credit_pay").css('display', 'block');
            $("#btn_make_payment").css('display', 'none');
        }//minus
        else
        {
            is_credit = false;
            //hide credit button
            $("#btn_credit_pay").css('display', 'none');
            $("#btn_make_payment").css('display', 'block');
        }//plus

        if(e.keyCode == 13)
        {
            if(sub_total == 0)
            {
                return;
            }//no sub total
            else
            {
                //add multi pay
                if(is_credit)
                {
                    //check customer
                    if(customer_id != 1)
                    {
                        //add multipay table
                        addMultipay(cust_pay, paymethod_id);

                        //make credit payment
                        //makeCreditPayment(tmp_bill_no, cust_pay, customer_id, salesman_id);
                    }//has customer
                    else
                    {
                        // alert("Please select a customer for Credit.");
                        $("#p_invoice_message").css('display', 'block');
                        $("#p_invoice_message").html("Please select a <b>Customer</b>!");
                        return;
                    }//no customer
                    
                }//credit (half pay)
                else
                {
                    $.get("../AJAX/Invoice/makePayment.php", {
                        sell_header_id: sell_header_id,
                        cust_pay: cust_pay,
                        tmp_bill_no: tmp_bill_no,
                        paymethod_id: paymethod_id,
                        customer_id: customer_id, 
                        salesman_id: salesman_id,
                        counter_id: counter_id
                    }, function(data){
                        // alert(data);
                        const obj = JSON.parse(data);
                        var stat = obj['inv_stat'];
                        var header_id = obj['invoice_id'];
                
                        switch(stat)
                        {
                            case 3:
                                alert("You have exceed daily sale limit, please logout");
                                $("#p_invoice_message").css('display', 'block');
                                $("#p_invoice_message").html("You have exceed daily sale limit, please <b>logout</b>");
                            break;
                
                            case 2:
                                $("#p_invoice_message").css('display', 'none');
                                //print receipt
                                window.location.href = "../Receipts/"+receipt_name+"?invoice_id=" + header_id;
                            break;
                            case 1:
                                alert("expired items");
                                $("#p_invoice_message").css('display', 'block');
                                $("#p_invoice_message").html("Some of the items in the cart are <b>Expired!</b>");
                            break;
                            case 0:
                                alert("out of stock");
                                $("#p_invoice_message").css('display', 'none');
                            break;
                        }//switch
                    });//get.makepayment
                }//full payment
            }//has rows

        }//enter key pressed
    });//get invoice discount

    }, _keyupEnterKey == 13 ? 0 : 300);//debounce

});//Customer balance

$("#btn_make_payment").click(function(){
    var receipt_name = $('input[name="rdb_receipt"]:checked').val();

    var sell_header_id = $("#hide_header_id").val();
    var cust_pay = parseFloat($("#cust_payment").val());
    var tmp_bill_no = $("#hide_tmp_no").val();
    var counter_id = $("#hide_counter_id").val();
    var paymethod_id = $('input[name="rdb_paymethod"]:checked').val();

    var customer_id = $("#cmb_customer").val();
    customer_id = customer_id == undefined ? 1 : $("#cmb_customer").val();

    var salesman_id = $("#cmb_salesman").val();
    salesman_id = salesman_id==undefined ? 1 : $("#cmb_salesman").val();

    //disable button to prevent double-submission
    $("#btn_make_payment").prop('disabled', true);

    $.get("../AJAX/Invoice/makePayment.php", {
        sell_header_id: sell_header_id,
        cust_pay: cust_pay,
        tmp_bill_no: tmp_bill_no,
        paymethod_id: paymethod_id,
        customer_id: customer_id, 
        salesman_id: salesman_id,
        counter_id: counter_id
    }, function(data){
        // alert(data);
        const obj = JSON.parse(data);
        var stat = obj['inv_stat'];
        var header_id = obj['invoice_id'];

        switch(stat)
        {
            case 3:
                alert("You have exceed daily sale limit, please logout");
                $("#p_invoice_message").css('display', 'block');
                $("#p_invoice_message").html("You have exceed daily sale limit, please <b>logout</b>");
                $("#btn_make_payment").prop('disabled', false);
            break;

            case 2:
                $("#p_invoice_message").css('display', 'none');
                //print receipt
                window.location.href = "../Receipts/"+receipt_name+"?invoice_id=" + header_id;
            break;
            case 1:
                alert("expired items");
                $("#p_invoice_message").css('display', 'block');
                $("#p_invoice_message").html("Some of the items in the cart are <b>Expired!</b>");
                $("#btn_make_payment").prop('disabled', false);
            break;
            case 0:
                alert("out of stock");
                $("#p_invoice_message").css('display', 'none');
                $("#btn_make_payment").prop('disabled', false);
            break;
            default:
                $("#btn_make_payment").prop('disabled', false);
            break;
        }//switch
    }).fail(function(){
        $("#btn_make_payment").prop('disabled', false);
    });//get.makepayment
});//one click pay

$("#btn_add_multipay").click(function(){

    var cust_pay = parseFloat($("#cust_payment").val());
    var paymethod_id = $('input[name="rdb_paymethod"]:checked').val();

    //add multipay table
    addMultipay(cust_pay, paymethod_id);

});//multi pay

$("#tbl_multipay_modal").on('click', '.btn_delete_multipay', function(){
    let row = $(this).closest('tr');
    let id = row.data('id');

    $.get("../AJAX/Invoice/deleteMultipay.php", {
        multipay_id: id
    }, function(data){
        loadMultipayTable();
    });//delete multipay
});//delete multipay click

$("#btn_credit_pay").click(function(){
    var receipt_name = $('input[name="rdb_receipt"]:checked').val();

    var sell_header_id = $("#hide_header_id").val();
    var cust_pay = parseFloat($("#cust_payment").val());
    var tmp_bill_no = $("#hide_tmp_no").val();
    var counter_id = $("#hide_counter_id").val();
    var paymethod_id = $('input[name="rdb_paymethod"]:checked').val();

    var customer_id = $("#cmb_customer").val();
    customer_id = customer_id == undefined ? 1 : $("#cmb_customer").val();

    var salesman_id = $("#cmb_salesman").val();
    salesman_id = salesman_id==undefined ? 1 : $("#cmb_salesman").val();

    if(isNaN(customer_id))
    {
        $("#p_invoice_message").css('display', 'block');
        $("#p_invoice_message").html("Please select a <b>Customer</b>!");
        return;
    }//no customer
    else
    {
        //disable button to prevent double-submission
        $("#btn_credit_pay").prop('disabled', true);

        $.get("../AJAX/Invoice/makeCreditPayment.php", {
            sell_header_id: sell_header_id,
            cust_pay: cust_pay,
            tmp_bill_no: tmp_bill_no,
            paymethod_id: paymethod_id,
            customer_id: customer_id, 
            salesman_id: salesman_id,
            counter_id: counter_id
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);
            var stat = obj['inv_stat'];
            var header_id = obj['invoice_id'];
    
            switch(stat)
            {
                case 3:
                    alert("You have exceed daily sale limit, please logout");
                    $("#p_invoice_message").css('display', 'block');
                    $("#p_invoice_message").html("You have exceed daily sale limit, please <b>logout</b>");
                    $("#btn_credit_pay").prop('disabled', false);
                break;
    
                case 2:
                    $("#p_invoice_message").css('display', 'none');
                    //print receipt
                    window.location.href = "../Receipts/"+receipt_name+"?invoice_id=" + header_id;
                break;
                case 1:
                    alert("expired items");
                    $("#p_invoice_message").css('display', 'block');
                    $("#p_invoice_message").html("Some of the items in the cart are <b>Expired!</b>");
                    $("#btn_credit_pay").prop('disabled', false);
                break;
                case 0:
                    alert("out of stock");
                    $("#p_invoice_message").css('display', 'none');
                    $("#btn_credit_pay").prop('disabled', false);
                break;
                default:
                    $("#btn_credit_pay").prop('disabled', false);
                break;
            }//switch
        }).fail(function(){
            $("#btn_credit_pay").prop('disabled', false);
        });
    }//has customer  
});

//================================= return ==============================//
$(".rdb_paymethod").change(function(){
    var paymethod_id = $('input[name="rdb_paymethod"]:checked').val();
    
    if(paymethod_id == 6)
    {
        //show return field
        $("#div_return_invoice").css('display', 'block');
        //disable 
        $("#cust_payment").attr('disabled', true);
        $("#btn_make_payment").attr('disabled', true);
        //focus
        $("#return_no").focus();
    }//return
    else
    {
        //hide return field
        $("#div_return_invoice").css('display', 'none');
        //enable 
        $("#cust_payment").attr('disabled', false);
        $("#btn_make_payment").attr('disabled', false);
    }//not return type
});//paymethod changed

$("#return_no").keyup(function(event){
    var keycode = (event.keyCode ? event.keyCode : event.which);
    if(keycode == '13')
    {
        var return_code = $(this).val();

        $.get("../AJAX/Invoice/getReturnAmount.php", {
            return_code: return_code
        }, function(data){
            //alert(data);
            const obj = JSON.parse(data);

            var return_stat = obj['return_stat'];
            switch (return_stat) {
                //no return invoice found
                case 0:
                    $("#p_invoice_message").css('display', 'block');
                    $("#p_invoice_message").html("Can't find any available <b>return invoices</b> for " + return_code + " !");    
                break;

                //return invoice is already in use
                case 1:
                    $("#p_invoice_message").css('display', 'block');
                    $("#p_invoice_message").html("Return invoice: <b>" + return_code + "</b> is alerady used in another invoice.");    
                break;

                //can make return payment
                case 2:
                    $("#p_invoice_message").css('display', 'none');
                    var return_header_id = obj['return_header_id'];
                    var return_amount = obj['return_amount'];

                    var show_value = "<small>Return Amount</small><br><b>"+ return_amount +"</b>";

                    $("#p_return_amount").html(show_value);
                    $("#hide_return_header").val(return_header_id);
                    $("#hide_return_amount").val(return_amount);

                break;
            
                default:
                    $("#p_invoice_message").css('display', 'none');
                break;
            }//switch

        });//get return code
    }//enter key
});//enter key press

$("#btn_add_return").click(function(){
    var return_header_id = parseInt($("#hide_return_header").val());
    var return_amount = parseFloat($("#hide_return_amount").val());
    var sell_header_id = $("#hide_sales_header").val();
    var paymethod_id = $('input[name="rdb_paymethod"]:checked').val();
    var return_header_id = $("#hide_return_header").val();

    if(return_header_id != 0)
    {
        //add to multipay
        addMultipay(return_amount, paymethod_id);

        //show return field
        $("#div_return_invoice").css('display', 'none');

        //add default value for retun header
        $("#hide_return_header").val("0");

        $("#rdb_pay_1").prop("checked", true);

        //enable
        $("#cust_payment").attr('disabled', false);
        $("#btn_make_payment").attr('disabled', false);

        $("#return_no_warning").css('display', 'none');
        $("#return_no").css('border-color', 'lime');
    }//has return id
    else
    {
        $("#return_no_warning").css('display', 'block');
        $("#return_no").css('border-color', 'red');
    }//no header id
});//add return to multipay

//=========================== Functions ==============================//
function makePayment(tmp_bill_no, cust_pay, customer_id, salesman_id)
{
    var receipt_name = $('input[name="rdb_receipt"]:checked').val();

    $.get("../AJAX/Invoice/setPayment.php", {
        tmp_bill_no: tmp_bill_no,
        customer_id: customer_id,
        salesman_id: salesman_id,
        cust_payment: cust_pay
    }, function(data){
        // alert(data);
        const obj = JSON.parse(data);
        var stat = obj['stat'];
        var header_id = obj['header_id'];

        switch(stat)
        {
            case 2:
                $("#p_invoice_message").css('display', 'none');
                //print receipt
                window.location.href = "../Receipts/"+receipt_name+"?invoice_id=" + header_id;
            break;
            case 1:
                alert("expired items");
                $("#p_invoice_message").css('display', 'block');
                $("#p_invoice_message").html("Some of the items in the cart are <b>Expired!</b>");
            break;
            case 0:
                alert("out of stock");
                $("#p_invoice_message").css('display', 'none');
            break;
        }//switch
    });//set payment
}//make payment

function makeCreditPayment(tmp_bill_no, cust_pay, customer_id, salesman_id)
{
    var receipt_name = $('input[name="rdb_receipt"]:checked').val();

    $.get("../AJAX/Invoice/setCreditPayment.php", {
        tmp_bill_no: tmp_bill_no,
        customer_id: customer_id,
        salesman_id: salesman_id,
        cust_payment: cust_pay
    }, function(data){
        const obj = JSON.parse(data);
        var stat = obj['stat'];
        var header_id = obj['header_id'];

        switch(stat)
        {
            case 2:
                $("#p_invoice_message").css('display', 'none');
                //print receipt
                window.location.href = "../Receipts/"+receipt_name+"?invoice_id=" + header_id;
            break;
            case 1:
                alert("expired items");
                $("#p_invoice_message").css('display', 'block');
                $("#p_invoice_message").html("Some of the items in the cart are <b>Expired!</b>");
            break;
            case 0:
                alert("out of stock");
                $("#p_invoice_message").css('display', 'none');
            break;
        }//switch
        
    });//set credit payment

}//make credit payment

function addMultipay(paid_amount, paymethod_id)
{
    var cust_payment = parseFloat($("#cust_payment").val());
    var sell_header_id = $("#hide_header_id").val();
    var return_header_id = $("#hide_return_header").val();
    var tmp_bill_no = $("#hide_tmp_no").val();

    //add to multipay
    $.get("../AJAX/Invoice/setMultipay.php", {
        paid_amount: paid_amount,
        tmp_bill_no: tmp_bill_no,
        paymethod_id: paymethod_id,
        sell_header_id:sell_header_id,
        return_header_id: return_header_id
    }, function(data){
        // alert(data);
        const obj = JSON.parse(data);

        var pay_stat = obj['pay_stat'];

        if(pay_stat == 1)
        {
            loadMultipayTable();
        }//add payment
        else
        {
            alert("All payments are paid, please issue the bill.");
        }//paid
        
    });//add multipay

}//add multi pay

function loadMultipayTable()
{
    var header_id = $("#hide_header_id").val();
    var bill_total = parseFloat($("#hide_sell_total").val());
    var payment_total = 0;

    $.get("../AJAX/Invoice/getMultipay.php", {
        sale_header_id: header_id
    }, function(data){
        // alert(data);
        const obj = JSON.parse(data);

        let tbl_data = "";

        tbl_data += "<tr>";
        tbl_data += "<th>Paymethod</th>";
        tbl_data += "<th>Amount</th>";
        tbl_data += "<th>Action</th>";
        tbl_data += "</tr>";

        $.each(obj, function(i, item){
            var paymethod = item.PaymethodName;
            var amount = item.paidAmount;
            var paymethod_id = item.MPID;
            payment_total += parseFloat(item.paidAmount);

            tbl_data += "<tr data-id='"+paymethod_id+"'>";
            tbl_data += "<td>"+ paymethod +"</td>";
            tbl_data += "<td>"+ amount +"</td>";
            tbl_data += "<td><button type='button' class='btn btn_delete_multipay'>";
            tbl_data += "<img src='../Assets/Images/icons/close.png' style='height:25px; width:auto;'>";
            tbl_data += "</td>";
            tbl_data += "</tr>";
        });//iterate

        $("#tbl_multipay_modal").html(tbl_data);

        var balance = bill_total - payment_total;

        let show_balance = "Bill Total: " + bill_total + "<br> Paid Total: " + payment_total + "<br> Balance: " + balance;
        $("#p_multipay_total").html(show_balance);

        getBalance();
    });//get multi pay
}//load multi pay table

function getBalance()
{
    // var sell_header_id = $("#hide_sales_header").val();
    var sell_header_id = $("#hide_sell_header_id").val();
    var header_id = $("#hide_sell_header_id").val();
    var cust_payment = parseFloat($("#cust_payment").val());
    var tmp_bill_no = $("#hide_tmp_no").val();
 
    var invoice_total = getCartTotal();
    var multipay_total = getMultipayTotal();
    var invoice_discount = 0;
    var pending_amount = 0;

    //get invoice discount
    $.get("../AJAX/Invoice/getSellDiscount.php", {
        sell_header_id: sell_header_id
    }, function(data){
        const obj = JSON.parse(data);

        invoice_discount = obj[0]['DiscountAmount']==null ? 0 : obj[0]['DiscountAmount'];
        invoice_discount = parseFloat(invoice_discount);

        pending_amount = invoice_total - invoice_discount - multipay_total;

        let tbl_data = "";

        // tbl_data += "<tr>";
        // tbl_data += "<td>Sub Total</td>";
        // tbl_data += "<td>"+ invoice_total +"</td>";
        // tbl_data += "</tr>";

        // tbl_data += "<tr>";
        // tbl_data += "<td>Discount</td>";
        // tbl_data += "<td>"+ invoice_discount +"</td>";
        // tbl_data += "</tr>";

        tbl_data += "<tr>";
        tbl_data += "<td>Paid Amount</td>";
        tbl_data += "<td>"+ multipay_total +"</td>";
        tbl_data += "</tr>";

        tbl_data += "<tr>";
        tbl_data += "<td>Pending Amount</td>";
        tbl_data += "<td><b>"+ pending_amount +"</b></td>";
        tbl_data += "</tr>";

        $("#tbl_payment").html(tbl_data);

    });//get invoice discount

}//get balance

function getCartTotal()
{
    var row_count = 0;
    var cart_total = 0;
    var totalItems = 0;
    $("#tbl_cart tr").each(function(){
        row_count += 1;
        var price = $(this).find("td").eq(7).html();
        var itemCount = $(this).find("td").eq(1).html();

        if(price != undefined)
        {
            cart_total += parseFloat(price);
            totalItems += parseFloat(itemCount);
        }//has value
    });//go through table

    return cart_total;
}//get cart total

function getMultipayTotal()
{
    var row_count = 0;
    var multipay_total = 0;
    $("#tbl_multipay_modal tr").each(function(){
        row_count += 1;
        var price = $(this).find("td").eq(1).html();

        if(price != undefined)
        {
            multipay_total += parseFloat(price); 
        }//has value
    });//go through table
    return multipay_total;
}//get multipay

});//payment jquery