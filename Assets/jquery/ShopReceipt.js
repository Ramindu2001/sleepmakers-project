$(document).ready(function(){
    $("#btn_wholesale_receipt").click(function(){
        $("#wholesale_shop_receipt_modal").modal("toggle");

        $("#cmb_shop_receipts").val(1);
        $("#wholesreceipt_name").val("");

        $("#wholesalespan_receipt").css('display', 'none');
        $("#wholesalespan_receipt").text("");

        //hide update button
        $("#btn_save_wholesale_receipt").attr('disabled', false);
        $("#btn_save_wholesale_receipt").css('display', 'block');
        //hide update button
        $("#btn_update_wholesale_receipt").attr('disabled', true);
        $("#btn_update_wholesale_receipt").css('display', 'none');
        //hide update button
        $("#btn_delete_wholesale_receipt").attr('disabled', true);
        $("#btn_delete_wholesale_receipt").css('display', 'none');
    })
    $("#btn_add_receipt").click(function(){
        $("#shop_receipt_modal").modal('toggle');

        $("#cmb_shop_receipts").val(1);
        $("#receipt_name").val("");

        $("#span_receipt").css('display', 'none');
        $("#span_receipt").text("");

        //hide update button
        $("#btn_save_receipt").attr('disabled', false);
        $("#btn_save_receipt").css('display', 'block');
        //hide update button
        $("#btn_update_receipt").attr('disabled', true);
        $("#btn_update_receipt").css('display', 'none');
        //hide update button
        $("#btn_delete_receipt").attr('disabled', true);
        $("#btn_delete_receipt").css('display', 'none');
    });//open shop receipt



    $("#tbl_shop_receipts").on('click', '.receipt_row_edit', function(){
        let row = $(this).closest('tr');
        let id = row.data('id');

        $("#hide_receipt_id").val(id);

        $.get("../AJAX/SaleSettings/getOneReceipt.php", {
            receipt_id: id
        }, function(data){
            $("#shop_receipt_modal").modal('toggle');
            const obj = JSON.parse(data);

            var shop_id = obj[0]['SHID'];
            var receipt_name = obj[0]['receiptName'];
            var is_default = obj[0]['is_default'];
            var receipt_stat = obj[0]['ReceiptStat'];
            var receipt_file_name = obj[0]['ReceiptPath'];
            var RecieptType = obj[0]['RecieptType'];

            if(is_default == '1')
            {
                $("#default_receipt").attr('checked', true);
            }//default
            else
            {
                $("#default_receipt").attr('checked', false);
            }//not default

            if(receipt_stat == 1)
            {
                $("#active_receipt").attr('checked', true);
            }//active
            else
            {
                $("#active_receipt").attr('checked', false);
            }
            if(RecieptType == 2)
            {
                $("#wholesaleRecipt").attr('checked', true);
            }//active
            else
            {
                $("#wholesaleRecipt").attr('checked', false);
            }//inactive

            $("#cmb_shop_receipts").val(shop_id);
            $("#receipt_name").val(receipt_name);

            $("#span_receipt").css('display', 'block');
            $("#span_receipt").text(receipt_file_name);

            //hide save button
            $("#btn_save_receipt").attr('disabled', true);
            $("#btn_save_receipt").css('display', 'none');
            //show update button
            $("#btn_update_receipt").attr('disabled', false);
            $("#btn_update_receipt").css('display', 'block');
            //hide delete button
            $("#btn_delete_receipt").attr('disabled', true);
            $("#btn_delete_receipt").css('display', 'none');
        });//get one data
        
    });//edit shop receipt

    $("#tbl_shop_receipts").on('click', '.receipt_row_delete', function(){
        let row = $(this).closest('tr');
        let id = row.data('id');

        $("#hide_receipt_id").val(id);

        $.get("../AJAX/SaleSettings/getOneReceipt.php", {
            receipt_id: id
        }, function(data){
            $("#shop_receipt_modal").modal('toggle');
            const obj = JSON.parse(data);

            var shop_id = obj[0]['SHID'];
            var receipt_name = obj[0]['receiptName'];
            var is_default = obj[0]['is_default'];
            var receipt_stat = obj[0]['ReceiptStat'];
            var receipt_file_name = obj[0]['ReceiptPath'];

            if(is_default == '1')
            {
                $("#default_receipt").attr('checked', true);
            }//default
            else
            {
                $("#default_receipt").attr('checked', false);
            }//not default

            if(receipt_stat == 1)
            {
                $("#active_receipt").attr('checked', true);
            }//active
            else
            {
                $("#active_receipt").attr('checked', false);
            }//inactive

            $("#cmb_shop_receipts").val(shop_id);
            $("#receipt_name").val(receipt_name);

            $("#span_receipt").css('display', 'block');
            $("#span_receipt").text(receipt_file_name);

            //hide save button
            $("#btn_save_receipt").attr('disabled', true);
            $("#btn_save_receipt").css('display', 'none');
            //show update button
            $("#btn_update_receipt").attr('disabled', true);
            $("#btn_update_receipt").css('display', 'none');
            //hide delete button
            $("#btn_delete_receipt").attr('disabled', false);
            $("#btn_delete_receipt").css('display', 'block');
        });//get one data
    });//delete  shop receipts
});//shop receipt jquery