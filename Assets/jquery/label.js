$(document).ready(function(){
    
    //open modal
    $("#btn_open_label_modal").click(function(){
        $("#add_barcode_modal").modal('toggle');

        $("#btn_save_label").css('display', 'block');
        $("#btn_save_label").attr('disabled', false);

        $("#btn_update_label").css('display', 'none');
        $("#btn_update_label").attr('disabled', true);

        $("#file_name").css('display', 'none');
        $("#file_name").text("");

        //show blank
        $("#lbl_name").val("");
        $("#dpi").val("");
        $("#num_row").val("");
        $("#num_col").val("");
        $("#lbl_width").val("");
        $("#lbl_height").val("");
        $("#stk_width").val("");
        $("#stk_height").val("");
        $("#stk_margin_left").val("");
        $("#stk_margin_right").val("");
        $("#stk_margin_top").val("");
        $("#stk_margin_bottom").val("");
        $("#chk_lbl_stat").attr('checked', false);

    });//open barcode label add modal

    $("#tbl_labels").on('click', '.btn_edit_label', function(){
        let row = $(this).closest('tr');
	    let id = row.data('id');

        // alert("working... " + id);

        $.get("../AJAX/Label/getOneLabel.php", {
            label_id: id
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);

            var file_name = obj[0]['LabelPath'];
            var label_name = obj[0]['LabelName'];
            var dpi = obj[0]['dpi'];
            var num_rows = obj[0]['numRow'];
            var num_columns = obj[0]['numCol'];
            var lbl_width = obj[0]['lblWidth'];
            var lbl_height = obj[0]['lblHeight'];
            var stk_width = obj[0]['stkWidth'];
            var stk_height = obj[0]['stkHeight'];
            var stk_margin_left = obj[0]['stkMarginLeft'];
            var stk_margin_rigth = obj[0]['stkMarginRight'];
            var stk_margin_top = obj[0]['stkMarginTop'];
            var stk_margin_bottom = obj[0]['stkMarginBottom'];
            var lbl_stat = obj[0]['lblStat'];
            var label_shop_id = obj[0]['shop_id'];

            $("#add_barcode_modal").modal('toggle');

            $("#hide_label_id").val(id);
            $("#file_name").css('display', 'block');
            $("#file_name").text(file_name);

            $("#lbl_name").val(label_name);
            $("#dpi").val(dpi);
            $("#num_row").val(num_rows);
            $("#num_col").val(num_columns);
            $("#lbl_width").val(lbl_width);
            $("#lbl_height").val(lbl_height);
            $("#stk_width").val(stk_width);
            $("#stk_height").val(stk_height);
            $("#stk_margin_left").val(stk_margin_left);
            $("#stk_margin_right").val(stk_margin_rigth);
            $("#stk_margin_top").val(stk_margin_top);
            $("#stk_margin_bottom").val(stk_margin_bottom);
            $("#cmb_shop").val(label_shop_id);

            if(lbl_stat == 1)
            {
                $("#chk_lbl_stat").attr('checked', true);
            }//active stat
            else
            {
                $("#chk_lbl_stat").attr('checked', false);
            }//inactive

            $("#btn_save_label").css('display', 'none');
            $("#btn_save_label").attr('disabled', true);

            $("#btn_update_label").css('display', 'block');
            $("#btn_update_label").attr('disabled', false);

        });
    });

});//label jquery