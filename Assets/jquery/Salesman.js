$(document).ready(function(){
    $('#btn_Add_Salesman_modal').click(function(){       
        $("#Salesman_modal").modal('toggle');

        $("h4#NewSalesman").text('Add New Salesman');

        $("#SalNo").val("");
        $("#SalsName").val("");
        $("#Contact").val("");
        $("#SalesmanStat").attr('checked', false);

        $("#btn_delete_salesman").css('display', 'none');
        $("#btn_delete_salesman").attr('disabled', true);

        $("#btn_Update_Salesman").css('display', 'none');
        $("#btn_Update_Salesman").attr('disabled', true);

        $("#btn_save_Salesman").css('display', 'block');
        $("#btn_save_Salesman").attr('disabled', false);
    });//open modal - salesman add new

    $("#tbl_salesman").on('click', '.btn_edit_salesman', function(){
        let row = $(this).closest('tr');
	    let id = row.data('id');

        $("#hide_salesman_id").val(id);
        $("#Salesman_modal").modal('toggle');
        $("h4#NewSalesman").text('Update Salesman');

        $.get("../AJAX/salesman/getOneSalesman.php", {
            salesman_id: id
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);

            var salesman_name = obj[0]['SalesmansName'];
            var salesman_contact = obj[0]['SalesmansContact'];
            var commision_rate = obj[0]['commision_rate'];
            var salesman_stat = parseFloat(obj[0]['SalesmanStat']);
            
            $("#SalsName").val(salesman_name);
            $("#Contact").val(salesman_contact);
            $("#commision_rate").val(commision_rate);

            if(salesman_stat == 1)
            {
                $("#SalesmanStat").attr('checked', true);
            }//active
            else
            {
                $("#SalesmanStat").attr('checked', false);
            }//inactive

            $("#btn_delete_salesman").css('display', 'none');
            $("#btn_delete_salesman").attr('disabled', true);

            $("#btn_Update_Salesman").css('display', 'block');
            $("#btn_Update_Salesman").attr('disabled', false);

            $("#btn_save_Salesman").css('display', 'none');
            $("#btn_save_Salesman").attr('disabled', true);
        });

    });//update salesman
    
    $("#tbl_salesman").on('click', '.btn_delete_salesman', function(){
        let row = $(this).closest('tr');
	    let id = row.data('id');

        $("#hide_salesman_id").val(id);
        $("#Salesman_modal").modal('toggle');
        $("h4#NewSalesman").text('Delete Salesman');

        $.get("../AJAX/salesman/getOneSalesman.php", {
            salesman_id: id
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);

            var salesman_name = obj[0]['SalesmansName'];
            var salesman_contact = obj[0]['SalesmansContact'];
            var commision_rate = obj[0]['commision_rate'];
            var salesman_stat = parseFloat(obj[0]['SalesmanStat']);
            
            $("#SalsName").val(salesman_name);
            $("#Contact").val(salesman_contact);
            $("#commision_rate").val(commision_rate);

            if(salesman_stat == 1)
            {
                $("#SalesmanStat").attr('checked', true);
            }//active
            else
            {
                $("#SalesmanStat").attr('checked', false);
            }//inactive

            $("#btn_delete_salesman").css('display', 'block');
            $("#btn_delete_salesman").attr('disabled', false);

            $("#btn_Update_Salesman").css('display', 'none');
            $("#btn_Update_Salesman").attr('disabled', true);

            $("#btn_save_Salesman").css('display', 'none');
            $("#btn_save_Salesman").attr('disabled', true);
        });
    });//delete salesman

    $("#Contact").change(function(){
        var contact = $(this).val();
        if(!validatePhone(contact))
        {
            $("#contact_warning").css('display', 'block');
            $(this).css('border-color', 'firebrick');
            $(this).val("");
            $(this).focus();
        }
        else
        {
            $("#contact_warning").css('display', 'none');
            $(this).css('border-color', 'lime');
        }//valid contact
    });//validate contact

    $("#commision_rate").change(function(){
        var commision_rate = parseFloat($(this).val());
        if(commision_rate > 100 || commision_rate < 0)
        {
            $("#commision_warning").css('display', 'block');
            $("#commision_rate").css('border-color', 'red');
            $(this).val("");
        }//not valid
        else
        {
            $("#commision_warning").css('display', 'none');
            $("#commision_rate").css('border-color', 'lime');
        }
    });

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
});//salesman jquery