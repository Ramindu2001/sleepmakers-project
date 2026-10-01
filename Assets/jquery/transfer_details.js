/*
 * Transfer detail screen - stock only.
 *
 * Lines arrive through the scanner upload (Assets/jquery/scan_upload.js): the sending shop scans what
 * goes out, the receiving shop scans what arrived. This file corrects the quantity of one line, removes
 * a line, and keeps the counts in step.
 *
 * It never touches a price. A transferred line carries the prices of the stock it came from; this screen
 * does not show them and cannot change them.
 */
$(document).ready(function(){

//======================= Correct one line's quantity ==========================//
    $("#tbl_transfer_detail").on('click', '.btn_transfer_edit', function(){
        var row = $(this).closest('tr');
        var id = row.data('id');

        $("#hide_transfer_detail_id").val(id);

        $.get("../AJAX/Transfer/getOneTransferDetail.php", {
            detail_id: id
        }, function(data){
            var line = JSON.parse(data)[0];

            $("#edit_line_product").text(line['Barcode'] + " - " + line['ItemName']);
            $("#ids").val(line['products_PDID']);
            $("#hide_inventory_id").val(line['InventoryID']);
            $("#hide_transfer_qty").val(line['TransferQty']);
            $("#td_current_qty").text(parseFloat(line['CurrentQty']) + 0);
            $("#transfer_qty").val(parseFloat(line['TransferQty']) + 0).css('border-color', '');
            $("#receive_qty").val(parseFloat(line['ReceivedQty']) + 0).css('border-color', '');

            $("#transfer_qty_warning, #receive_qty_warning").css('display', 'none');
            $("#card_edit_line").css('display', 'block');
            $("#transfer_qty").focus();
        });//get one transfer detail
    });//edit button on a line

    $("#btn_cancel_edit_line").click(function(){
        closeEditLine();
    });//cancel

    $("#btn_edit_transfer_detail").click(function(){
        var transferHeaderID = $("#hide_header_id").val();
        var transfer_detail_id = $("#hide_transfer_detail_id").val();

        //the sending shop sets how much goes out, the receiving shop how much arrived; a side that is
        //not on this screen keeps the quantity the line already has
        var transfer_qty = $("#transfer_qty").length ? parseFloat($("#transfer_qty").val()) : parseFloat($("#hide_transfer_qty").val());
        var request = { transfer_detail_id: transfer_detail_id, transfer_qty: transfer_qty };

        if(!(transfer_qty > 0))
        {
            $("#transfer_qty_warning").css('display', 'block');
            $("#transfer_qty").css('border-color', 'red').focus();
            return;
        }//no transfer qty

        if($("#receive_qty").length)
        {
            var receive_qty = parseFloat($("#receive_qty").val());

            if(!(receive_qty >= 0) || receive_qty > transfer_qty)
            {
                $("#receive_qty_warning").css('display', 'block');
                $("#receive_qty").css('border-color', 'red').focus();
                return;
            }//receive qty not valid

            request.receive_qty = receive_qty;
        }//receiving shop

        $.get("../AJAX/Transfer/editTransferDetail.php", request, function(data){
            if($.trim(data) != '1')
            {
                alert($.trim(data));
                return;
            }//not saved

            closeEditLine();
            reload(transferHeaderID);
        });//edit transfer detail
    });//save the corrected quantity

//================================ Remove a line ===============================//
    $("#tbl_transfer_detail").on('click', '.btn_transfer_delete', function(){
        var id = $(this).closest('tr').data('id');
        var transferHeaderID = $("#hide_header_id").val();

        $.get("../AJAX/Transfer/deleteTransferDetail.php", {
            transfer_detail_id: id
        }, function(data){
            if($.trim(data) != '1')
            {
                alert($.trim(data));
            }//not deleted

            closeEditLine();
            reload(transferHeaderID);
        });//delete transfer detail
    });//delete button on a line

//=============================== Scanner upload ===============================//
    //a scanner upload changed the lines (Assets/jquery/scan_upload.js)
    $(document).on('scanupload:applied', function(e, result){
        reload(result.doc_id);
    });//scan applied

//================================== Functions =================================//
    function closeEditLine()
    {
        $("#card_edit_line").css('display', 'none');
        $("#hide_transfer_detail_id").val("0");
        $("#edit_line_product").text("");
        $("#transfer_qty, #receive_qty").val("").css('border-color', '');
        $("#transfer_qty_warning, #receive_qty_warning").css('display', 'none');
    }//close the edit row

    function reload(transfer_header_id)
    {
        $.get("../AJAX/Transfer/getTransferDetail.php", {
            header_id: transfer_header_id
        }, function(data){
            $("#tbl_transfer_detail").html(data);
        });//get table

        $.get("../AJAX/Transfer/getTransferDetailTotal.php", {
            transfer_header_id: transfer_header_id
        }, function(data){
            var totals = JSON.parse(data);

            $("h4#sub_row_count").text(parseFloat(totals.row_count));
            $("h4#sub_transfer_count").text(parseFloat(totals.transfer_qty));
            $("h4#sub_receive_count").text(parseFloat(totals.receive_qty));
        });//get detail totals
    }//reload the lines and the counts

});//transfer jquery
