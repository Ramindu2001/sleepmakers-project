$(document).ready(function(){

    $('#btn_Add_Customer_modal').click(function(){  

        $("#Customer_modal").modal('toggle');

        $("h4#ModalLabel").text("Add New Customer");

        $("#CusNo").val("");
        $("#CusName").val("");
        $("#Contact").val("");
        $("#Address").val("");
        $("#CreditAmount").val("");
        $("#payment_term").val("");

        $("#btn_delete_customer").css('display', 'none');
        $("#btn_delete_customer").attr('disabled', true);

        $("#btn_update_customer").css('display', 'none');
        $("#btn_update_customer").attr('disabled', true);

        $("#btn_save_customer").css('display', 'block');
        $("#btn_save_customer").attr('disabled', false);
    });

$("#tbl_customer").on('click', '.btn_edit_customer', function(){
    let row = $(this).closest('tr');
	let id = row.data('id');
	
    $.get("../AJAX/Customer/getOneCustomer.php", {
        customer_id: id
    }, function(data){
        const obj = JSON.parse(data);

        $("#Customer_modal").modal('toggle');

        var cust_no = obj[0]['CustomerNo'];
        var cust_name = obj[0]['CustName'];
        var cust_contact = obj[0]['CustContact'];
        var cust_address = obj[0]['CustAddress'];
        var cust_max_credit = obj[0]['MaxCreditAmount'];
        var cust_pay_term = obj[0]['PaymentTerm'];
        var cust_stat = parseFloat(obj[0]['CustStat']);

        $("#hide_customer_id").val(id);
        $("#CusNo").val(cust_no);
        $("#CusName").val(cust_name);
        $("#Contact").val(cust_contact);
        $("#Address").val(cust_address);
        $("#CreditAmount").val(cust_max_credit);
        $("#payment_term").val(cust_pay_term);

        if(cust_stat == 1)
        {
            $("#CustomerStat").attr('checked', true);
        }//active 
        else
        {
            $("#CustomerStat").attr('checked', false);
        }//inactive

        $("h4#ModalLabel").text("Edit Customer");

        $("#btn_delete_customer").css('display', 'none');
        $("#btn_delete_customer").attr('disabled', true);

        $("#btn_update_customer").css('display', 'block');
        $("#btn_update_customer").attr('disabled', false);

        $("#btn_save_customer").css('display', 'none');
        $("#btn_save_customer").attr('disabled', true);
    });//get customer data

});//edit customer

$("#tbl_customer").on('click', '.btn_delete_customer', function(){
    let row = $(this).closest('tr');
	let id = row.data('id');

    $.get("../AJAX/Customer/getOneCustomer.php", {
        customer_id: id
    }, function(data){
        const obj = JSON.parse(data);

        $("#Customer_modal").modal('toggle');

        var cust_no = obj[0]['CustomerNo'];
        var cust_name = obj[0]['CustName'];
        var cust_contact = obj[0]['CustContact'];
        var cust_address = obj[0]['CustAddress'];
        var cust_max_credit = obj[0]['MaxCreditAmount'];
        var cust_pay_term = obj[0]['PaymentTerm'];
        var cust_stat = parseFloat(obj[0]['CustStat']);

        $("#hide_customer_id").val(id);
        $("#CusNo").val(cust_no);
        $("#CusName").val(cust_name);
        $("#Contact").val(cust_contact);
        $("#Address").val(cust_address);
        $("#CreditAmount").val(cust_max_credit);
        $("#payment_term").val(cust_pay_term);

        if(cust_stat == 1)
        {
            $("#CustomerStat").attr('checked', true);
        }//active 
        else
        {
            $("#CustomerStat").attr('checked', false);
        }//inactive

        $("h4#ModalLabel").text("Delete Customer");

        $("#btn_delete_customer").css('display', 'block');
        $("#btn_delete_customer").attr('disabled', false);

        $("#btn_update_customer").css('display', 'none');
        $("#btn_update_customer").attr('disabled', true);

        $("#btn_save_customer").css('display', 'none');
        $("#btn_save_customer").attr('disabled', true);
    });//get customer data
});

//========================== Validate =============================//
$("#Contact").change(function(){
    var contactNo = $(this).val();
    if(!validatePhone(contactNo))
    {
        $("#contact_invalid").css('display', 'block');
        $("#Contact").css('border-color', 'red');
        $("#Contact").val("");
        $("#Contact").focus();
    }//not valid
    else
    {
        $("#contact_invalid").css('display', 'none');
        $("#Contact").css('border-color', 'lime');
    }//valid 
});//check input

$("#CredAmount").change(function(){
    var creditAmount = parseFloat($(this).val());
    if(creditAmount <=0)
    {
        $("#credit_invalid").css('display', 'block');
        $("#CredAmount").css('border-color', 'red');
        $("#CredAmount").val('');
        $("#CredAmount").focus();
    }//invalid
    else
    {
        $("#credit_invalid").css('display', 'none');
        $("#CredAmount").css('border-color', 'lime');
    }
});//check value

//check DOB
$("#DOB").change(function(){
    var dob = $("#DOB").val();
    const this_date = new Date();

    var date_of_birth = new Date(dob);

    var date_difference = this_date - date_of_birth;

    if(date_difference < 0)
    {
        $("#dob_warning").css('display', 'block');
        $("#dob_warning").text("Please enter valid Date.");
        $("#DOB").val("");
        $("#DOB").css('border-color', 'red');
    }//wrong date
    else
    {
        $("#dob_warning").css('display', 'none');
        $("#DOB").css('border-color', 'lime');
    }
});//dob changed

//========================== Functions ===========================//
function validatePhone(phone_number) {
    //var a = document.getElementById(phone_number).value;
    var filter = /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/;   
    if (filter.test(phone_number)) {
        return true;
    }
    else {
        return false;
    }
}//validate phone number
});