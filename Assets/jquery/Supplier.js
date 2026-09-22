$(document).ready(function(){
    
    $("#btn_open_supplier").click(function(){
        $("#Supplier_modal").modal('toggle');

        //hide supplier no
        $("#lbl_supplier_no").css('display', 'none');
        $("#SuppNo").css('display', 'none');

        $("h4#myModalLabel").text('Add New Supplier');

        $("#SuppNo").val("");
        $("#distributer").val("");
        $("#SuppName").val("");
        $("#Contact").val("");
        $("#SuppStatus").attr('checked', false);

        $("#btn_delete_supplier").css('display', 'none');
        $("#btn_delete_supplier").attr('disabled', true);

        $("#btn_save_Supplier").css('display', 'block');
        $("#btn_save_Supplier").attr('disabled', false);

        $("#btn_Update_Supplier").css('display', 'none');
        $("#btn_Update_Supplier").attr('disabled', true);
    });//open modal

    $("#tbl_supplier").on('click', '.btn_edit_supplier', function(){
        let row = $(this).closest('tr');
	    let id = row.data('id');

        $("#Supplier_modal").modal('toggle');

        $("#hide_supplier_id").val(id);

        $.get("../AJAX/Suppliers/getOneSupplier.php", {
            supplier_id : id
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);

            var supplier_no = obj[0]['SupplierNo'];
            var supplier_name = obj[0]['SupplierName'];
            var distributer = obj[0]['Distributer'];
            var supplier_contact = obj[0]['Contact'];
            var supplier_stat = parseFloat(obj[0]['SupplierStat']);

            //assign values
            $("#SuppNo").val(supplier_no);
            $("#distributer").val(distributer);
            $("#SuppName").val(supplier_name);
            $("#Contact").val(supplier_contact);

            if(supplier_stat == 1)
            {
                $("#SuppStatus").attr('checked', true);
            }
            else
            {
                $("#SuppStatus").attr('checked', false);
            }

            $("h4#myModalLabel").text('Edit Supplier');

            $("#btn_delete_supplier").css('display', 'none');
            $("#btn_delete_supplier").attr('disabled', true);
    
            $("#btn_save_Supplier").css('display', 'none');
            $("#btn_save_Supplier").attr('disabled', true);
    
            $("#btn_Update_Supplier").css('display', 'block');
            $("#btn_Update_Supplier").attr('disabled', false);
        });
    });//edit supplier

    $("#tbl_supplier").on('click', '.btn_delete_supplier', function(){
        let row = $(this).closest('tr');
	    let id = row.data('id');

        $("#Supplier_modal").modal('toggle');

        $("#hide_supplier_id").val(id);

        $.get("../AJAX/Suppliers/getOneSupplier.php", {
            supplier_id : id
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);

            var supplier_no = obj[0]['SupplierNo'];
            var supplier_name = obj[0]['SupplierName'];
            var distributer = obj[0]['Distributer'];
            var supplier_contact = obj[0]['Contact'];
            var supplier_stat = parseFloat(obj[0]['SupplierStat']);

            //assign values
            $("#SuppNo").val(supplier_no);
            $("#distributer").val(distributer);
            $("#SuppName").val(supplier_name);
            $("#Contact").val(supplier_contact);

            if(supplier_stat == 1)
            {
                $("#SuppStatus").attr('checked', true);
            }
            else
            {
                $("#SuppStatus").attr('checked', false);
            }

            $("h4#myModalLabel").text('Delete Supplier');

            $("#btn_delete_supplier").css('display', 'block');
            $("#btn_delete_supplier").attr('disabled', false);
    
            $("#btn_save_Supplier").css('display', 'none');
            $("#btn_save_Supplier").attr('disabled', true);
    
            $("#btn_Update_Supplier").css('display', 'none');
            $("#btn_Update_Supplier").attr('disabled', true);
        });
    });

    // $("#tbl_supplier").on('mousedown', 'tr', function(){
    //     var currentRow = $(this).closest('tr');
    //     row_id = currentRow.find('td').eq(0).html();

    //     //get details
    //     var supplierNo = currentRow.find('td').eq(1).html();
    //     var distributer = currentRow.find('td').eq(2).html();
    //     var supplierName = currentRow.find('td').eq(3).html();
    //     var contactNo = currentRow.find('td').eq(4).html();
    //     var suppStat = currentRow.find('td').eq(6).html();

    //     $("#btn_supplier_" + row_id).on('click', function(){
    //          //assign values for hidden input
    //          $("#hide_supplier_id").val(row_id);

    //         $("#Supplier_modal").modal('toggle');

    //         $("h4#myModalLabel").text('Edit Supplier');

    //         $("#btn_delete_supplier").css('display', 'none');
    //         $("#btn_delete_supplier").attr('disabled', true);

    //         $("#btn_save_Supplier").css('display', 'none');
    //         $("#btn_save_Supplier").attr('disabled', true);

    //         $("#btn_Update_Supplier").css('display', 'block');
    //         $("#btn_Update_Supplier").attr('disabled', false);

    //         $("#SuppNo").val(supplierNo);
    //         $("#distributer").val(distributer);
    //         $("#SuppName").val(supplierName);
    //         $("#Contact").val(contactNo);
            
    //         if(suppStat == '1')
    //         {
    //             $("#SuppStatus").attr('checked', true);
    //         }//active
    //         else
    //         {
    //             $("#SuppStatus").attr('checked', false);
    //         }//inactive
    //     });//edit click

    //     $("#btn_supplier_delete_" + row_id).on('click', function(){
    //          //assign values for hidden input
    //          $("#hide_supplier_id").val(row_id);

    //         $("#Supplier_modal").modal('toggle');

    //         $("h4#myModalLabel").text('Delete Supplier');

    //         $("#btn_delete_supplier").css('display', 'block');
    //         $("#btn_delete_supplier").attr('disabled', false);

    //         $("#btn_save_Supplier").css('display', 'none');
    //         $("#btn_save_Supplier").attr('disabled', true);

    //         $("#btn_Update_Supplier").css('display', 'none');
    //         $("#btn_Update_Supplier").attr('disabled', true);

    //         $("#SuppNo").val(supplierNo);
    //         $("#distributer").val(distributer);
    //         $("#SuppName").val(supplierName);
    //         $("#Contact").val(contactNo);

    //         if(suppStat == '1')
    //         {
    //             $("#SuppStatus").attr('checked', true);
    //         }//active
    //         else
    //         {
    //             $("#SuppStatus").attr('checked', false);
    //         }//inactive
    //     });//delete click
    // });//table mouse down

//========================== Validate =========================//
//validate data
$("#Contact").change(function(){
    var contactNo = $(this).val();
    if(!validatePhone(contactNo))
    {
        $("#contact_invalid").css('display', 'block');
        $("#Contact").val("");
        $("#Contact").focus();
    }//validate phone no
    else
    {
        $("#contact_invalid").css('display', 'none');
    }//valid
});//validate contact

//=================== Suppliers Purchase Report ===================//
$("#cmb_suppliers").select2({
    ajax:{
        url: '../AJAX/Suppliers/getSuppliers.php',
        dataType: 'json',
        delay: 250,
        data: function(params){
            var query = {
                search: params.term,
                type: 'supplier_search'
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
    placeholder: 'Search for items',
    minimumInputLength: 1,
    width: '50%'
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

});