$(document).ready(function(){
//initialize
$("#btn_update_salesettings").attr('disabled', true);
$("#btn_update_salesettings").css('display', 'none');

$("#btn_delete_salesettings").attr('disabled', true);
$("#btn_delete_salesettings").css('display', 'none');

    $("#btn_add_settings").click(function(){
        $("#sale_settings_modal").modal('toggle');

        //show save button
        $("#btn_save_salesettings").attr('disabled', false);
        $("#btn_save_salesettings").css('display', 'block');
        //hide update button
        $("#btn_update_salesettings").attr('disabled', true);
        $("#btn_update_salesettings").css('display', 'none');
        //hide delete button
        $("#btn_delete_salesettings").attr('disabled', true);
        $("#btn_delete_salesettings").css('display', 'none');

        //assign values
        $("#cmb_shop").val(1);
        $("#cmb_add_option").val(0);
        $("#item_add_duration").val("");
        $("#invoice_header_text").val("");
        $("#cmb_counter_type").val(0);
    });//open modal
    $("body #modal_close").click(function(){
        $(".modal").modal("hide")
    })

    $("#tbl_sale_settings").on('click', '.setting_row_edit', function(){
        let row = $(this).closest('tr');
        let id = row.data('id');

        $("#hide_setting_id").val(id);

        $.get("../AJAX/SaleSettings/getOneSetting.php", {
            setting_id: id
        }, function(data){
            $("#sale_settings_modal").modal('toggle');

            const obj = JSON.parse(data);

            var shop_id = obj[0]['shop_id'];
            var add_option = obj[0]['billAddOption'];
            var duration = obj[0]['qtyAddDuration'];
            var invoice_header = obj[0]['billNoHeader'];
            var counter_type = obj[0]['countertype_id'];
            var WbillNoHeader = obj[0]['WbillNoHeader'];
            var stat = obj[0]['settingStat'];

            //assign values
            $("#cmb_shop").val(shop_id);
            $("#cmb_add_option").val(add_option);
            $("#item_add_duration").val(duration);
            $("#invoice_header_text").val(invoice_header);
            $("#Winvoice_header_text").val(WbillNoHeader);
            $("#cmb_counter_type").val(counter_type);

            if(stat == 1)
            {
                $("#setting_stat").attr('checked', true);
            }//active
            else
            {
                $("#setting_stat").attr('checked', false);
            }//inactive
            
            //hide save button
            $("#btn_save_salesettings").attr('disabled', true);
            $("#btn_save_salesettings").css('display', 'none');
            //show update button
            $("#btn_update_salesettings").attr('disabled', false);
            $("#btn_update_salesettings").css('display', 'block');
            //hide delete button
            $("#btn_delete_salesettings").attr('disabled', true);
            $("#btn_delete_salesettings").css('display', 'none');
        });//get edit data
    });

    //delete sale setting
    $("#tbl_sale_settings").on('click', '.setting_row_delete', function(){
        let row = $(this).closest('tr');
        let id = row.data('id');

        $("#hide_setting_id").val(id);

        $.get("../AJAX/SaleSettings/getOneSetting.php", {
            setting_id: id
        }, function(data){
            $("#sale_settings_modal").modal('toggle');

            const obj = JSON.parse(data);

            var shop_id = obj[0]['shop_id'];
            var add_option = obj[0]['billAddOption'];
            var duration = obj[0]['qtyAddDuration'];
            var invoice_header = obj[0]['billNoHeader'];
            var counter_type = obj[0]['countertype_id'];
            var stat = obj[0]['settingStat'];

            //assign values
            $("#cmb_shop").val(shop_id);
            $("#cmb_add_option").val(add_option);
            $("#item_add_duration").val(duration);
            $("#invoice_header_text").val(invoice_header);
            $("#cmb_counter_type").val(counter_type);

            if(stat == 1)
            {
                $("#setting_stat").attr('checked', true);
            }//active
            else
            {
                $("#setting_stat").attr('checked', false);
            }//inactive
            
            //show save button
            $("#btn_save_salesettings").attr('disabled', true);
            $("#btn_save_salesettings").css('display', 'none');
            //hide update button
            $("#btn_update_salesettings").attr('disabled', true);
            $("#btn_update_salesettings").css('display', 'none');
            //hide delete button
            $("#btn_delete_salesettings").attr('disabled', false);
            $("#btn_delete_salesettings").css('display', 'block');
        });//get edit data

    });//delete sale setting

});//sale settings jQuery