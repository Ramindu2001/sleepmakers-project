$(document).ready(function () {
    $("#tbl_upload").on('click', '.btn_delete_item', function(){
        let row = $(this).closest('tr');
	    let id = row.data('id');

        $.get("../AJAX/Upload/deleteUploadItem.php", {
            upload_id: id
        }, function(data){
            // alert(data);
            loadUploadTable();
        });
    });//delete item

    $("#btn_load_table").click(function(){
        loadUploadTable();
    });//load table

    $("#tbl_upload").on('click', '.btn_edit_item', function(){
        let row = $(this).closest('tr');
	    let id = row.data('id');

        $.get("../AJAX/Upload/getOneUploadItem.php", {
            upload_id: id
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);

            $("#edit_item_modal").modal('toggle');
            $("#hide_upload_id").val(id);

            var upload_id = obj[0]['upload_id'];
            var barcode = obj[0]['barcode'];
            var item_name = obj[0]['itemname'];
            var qty = parseFloat(obj[0]['qty']);
            var purchase_price = obj[0]['purchaseprice'];
            var label_price = obj[0]['labelprice'];
            var selling_price = obj[0]['sellingprice'];
            var mnf_date = obj[0]['mnfdate'];
            var exp_date = obj[0]['expdate'];
            var section_id = obj[0]['section_id'];
            var rack_id = obj[0]['rack_id'];

            var item_desc = "Barcode: " + barcode + "<br>ItemName: " + item_name;
            $("#p_item").html(item_desc);

            $("#item_qty").val(qty);
            $("#item_purchase_price").val(purchase_price);
            $("#item_label_price").val(label_price);
            $("#item_selling_price").val(selling_price);
            $("#item_mnf_date").val(mnf_date);
            $("#item_exp_date").val(exp_date);
            $("#cmb_item_section").val(section_id);
            $("#cmb_item_rack").val(rack_id);
        });
    });

    //close modal
    $("#btn_close_edit_modal").click(function(){
        $("#edit_item_modal").modal('hide');
    });

    $("#cmb_item_section").change(function(){
        var section_id = $("#cmb_item_section").val();
        $.get("../AJAX/Racks/getRackBySection.php", {
            section_id: section_id
        }, function(data){
            // alert(data);
            $("#cmb_item_rack").html(data);
        });
    });//cmb section change

    $("#btn_update_item").click(function(){
        var today = new Date();
        var dd = String(today.getDate()).padStart(2, '0');
        var mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
        var yyyy = today.getFullYear();

        today = yyyy +'-'+ mm + '-' + dd;

        var upload_id =   $("#hide_upload_id").val();
        var qty =   $("#item_qty").val();
        var purchase_price = $("#item_purchase_price").val();
        var label_price = $("#item_label_price").val() == undefined ? 0 : $("#item_label_price").val();
        var selling_price =   $("#item_selling_price").val();

        var mnf_date = $("#item_mnf_date").val() == undefined ? today : $("#item_mnf_date").val();
        var exp_date = $("#item_exp_date").val() == undefined ? today : $("#item_exp_date").val();

        var section_id = $("#cmb_item_section").val() == undefined ? 1 : $("#cmb_item_section").val();
        var rack_id = $("#cmb_item_rack").val() == undefined ? 1 : $("#cmb_item_rack").val();

        $.get("../AJAX/Upload/editUploadItem.php", {
            upload_id:upload_id,
            qty: qty,
            purchase_price:purchase_price,
            label_price: label_price,
            selling_price: selling_price, 
            mnf_date: mnf_date,
            exp_date: exp_date,
            section_id: section_id,
            rack_id: rack_id
        }, function(data){
            // alert(data);
            $("#edit_item_modal").modal('hide');
            //update table
            loadUploadTable();
        });

    });//update item 

//========== Functions ===========//
function loadUploadTable()
{
    $.get("../AJAX/Upload/getUploadItems.php", function(data){
        // alert(data);
        $("#tbl_upload").html(data);
    });
}//load upload table

});//grn upload js