/*
 * GRN detail screen - stock only.
 *
 * Lines arrive through the scanner upload (Assets/jquery/scan_upload.js). This file corrects the
 * quantity of a line that was counted wrong, removes a line, and keeps the row / item counts in step.
 *
 * It never touches a price. The prices of a line are the ones the stock came in at; this screen does
 * not show them and cannot change them (Items > Price Change does that).
 */
$(document).ready(function(){

    //validate quantity
    $("#prod_qty").change(function(){
        var prodQty = parseFloat($(this).val());
        if(!(prodQty > 0))
        {
            $("#qty_warning").css('display', 'block');
            $("#prod_qty").css('border-color', 'red');
            return;
        }//no qty
        $("#qty_warning").css('display', 'none');
        $("#prod_qty").css('border-color', 'LimeGreen');
    });//qty changed

//========================= Correct one line's quantity ========================//
    $("#tbl_grn_details").on('click', '.btn_grn_edit', function(){
        var id = $(this).closest('tr').data('id');
        $("#hide_detail_id").val(id);

        $.get("../AJAX/GRN/getOneGRN.php", {
            grn_detail_id: id
        }, function(data){
            var line = JSON.parse(data)[0];

            $("#edit_line_product").text(line['Barcode'] + " - " + line['ItemName']);
            $("#prod_qty").val(line['InitQty']).css('border-color', '');
            $("#ids").val(line['PDID']);
            if(line['SEID'] != null){ $("#cmb_section").val(line['SEID']); }
            if(line['RKID'] != null){ $("#cmb_racks").val(line['RKID']); }

            $("#qty_warning").css('display', 'none');
            $("#tbl_edit_line").css('display', 'table');
            $("#prod_qty").focus();
        });//get one grn detail
    });//edit button on a line

    $("#btn_cancel_edit_line").click(function(){
        closeEditLine();
    });//cancel

    $("#btn_edit_grn_detail").click(function(){
        var grn_header_id = $("#hide_header_id").val();
        var grn_detail_id = $("#hide_detail_id").val();
        var prod_qty = parseFloat($("#prod_qty").val());

        if(!(prod_qty > 0))
        {
            $("#qty_warning").css('display', 'block');
            $("#prod_qty").css('border-color', 'red').focus();
            return;
        }//no qty

        //only the quantity and the rack travel: the line keeps the prices it came in with
        $.get("../AJAX/GRN/editGRNDetails.php", {
            grn_detail_id: grn_detail_id,
            prod_qty: prod_qty,
            rack_id: $("#cmb_racks").val() == undefined ? 1 : $("#cmb_racks").val()
        }, function(){
            closeEditLine();
            loadTable(grn_header_id);
        });//edit grn detail
    });//save the corrected quantity

//================================ Remove a line ===============================//
    $("#tbl_grn_details").on('click', '.btn_grn_delete', function(){
        var id = $(this).closest('tr').data('id');
        var grn_header_id = $("#hide_header_id").val();

        $.get("../AJAX/GRN/deleteGRNDetails.php", {
            grn_detail_id: id
        }, function(){
            closeEditLine();
            loadTable(grn_header_id);
        });//delete grn detail
    });//delete button on a line

//=============================== Scanner upload ===============================//
    //a scanner upload added lines (Assets/jquery/scan_upload.js)
    $(document).on('scanupload:applied', function(){
        loadTable($("#hide_header_id").val());
    });//scan applied

//================================== Functions =================================//
    function closeEditLine()
    {
        $("#tbl_edit_line").css('display', 'none');
        $("#hide_detail_id").val("0");
        $("#prod_qty").val("").css('border-color', '');
        $("#edit_line_product").text("");
        $("#qty_warning").css('display', 'none');
    }//close the edit row

    function loadTable(grn_header_id)
    {
        $.get("../AJAX/GRN/getGRNDetails.php", {
            grn_header_id: grn_header_id
        }, function(data){
            $("#tbl_grn_details").html(data);
            countLines();
        });//load table
    }//load table

    //how many lines, and how many units altogether - the only totals this screen has
    function countLines()
    {
        var rows = $("#tbl_grn_details tr[data-id]");
        var items = 0;

        rows.each(function(){
            items += parseFloat($(this).data('qty')) || 0;
        });//each line

        $("h4#sub_row_count").text(rows.length);
        $("h4#sub_item_count").text(items);
    }//count lines

});//jQuery grn details
