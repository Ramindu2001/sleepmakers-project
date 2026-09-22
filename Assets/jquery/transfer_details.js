$(document).ready(function(){
//hide edit button
$("#btn_edit_transfer_detail").css('display', 'none');

//=== Product Search ===//
$("#cmb_products").select2({
    ajax:{
        url: '../AJAX/Transfer/getTransferProducts.php',
        dataType: 'json',
        delay: 250,
        data: function(params){
            //console.log(params);
            
            var query = {
                search: params.term,
                type: 'item_search'
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
    width: '100%'
});

//get value
$("#cmb_products").change(function(){
    var productID = $(this).val();
    var transferHeaderID = $("#hide_header_id").val();
    var batch_count = 0;

    //check batches
    $.get("../AJAX/Transfer/getBatches.php", {
        product_id: productID
    }, function(data){
        const obj = JSON.parse(data);
        var batch_count = obj.length;
        var txt_data = "";

        $.each(obj, function(key, value)
        {   
            txt_data += "<option value='"+value.BatchID+"'>"+value.BatchID+" - "+value.SellingPrice+"</option>";
        });//each
        
        $("#cmb_batch").html(txt_data);

        var top_batch_id = obj[0]['BatchID'];
        $.get("../AJAX/Transfer/getCurrentQty.php", {
            product_id: productID,
            batch_id: top_batch_id
        }, function(data){
            const obj = JSON.parse(data);
            $("#td_current_qty").text(obj.current_qty);
            $("#td_variation_id").text(obj.variation_id);
            $("#td_variation_name").text(obj.variation_name);
            $("#transfer_unit_amount").val(obj.unit_price);
            $("#transfer_unit_sell").val(obj.sell_price);
            $("#mnf_date").val(obj.mnf_date);
            $("#exp_date").val(obj.exp_date);

            $("#transfer_qty").focus();

            $("#ids").val(productID);
            $("#product_detail").css('display', 'none');
        });//get current qty
    });//get batches
});//cmb product change

$("#cmb_batch").change(function(){
    var batch_id = $(this).val();
    var product_id = $("#cmb_products").val();

    $.get("../AJAX/Transfer/getCurrentQty.php", {
        product_id: product_id,
        batch_id: batch_id
    }, function(data){
        const obj = JSON.parse(data);
        $("#td_current_qty").text(obj.current_qty);
        $("#td_variation_id").text(obj.variation_id);
        $("#td_variation_name").text(obj.variation_name);
        $("#transfer_unit_amount").val(obj.unit_price);
        $("#transfer_unit_sell").val(obj.sell_price);
        $("#mnf_date").val(obj.mnf_date);
        $("#exp_date").val(obj.exp_date);

        $("#transfer_qty").focus();

        $("#ids").val(product_id);
        $("#product_detail").css('display', 'none');
    });//get current qty
});//batch changes

//check qty
$("#transfer_qty").change(function(){
    var current_qty = parseFloat($('#td_current_qty').text());
    var transfer_qty = parseFloat($(this).val());
    var unit_price = parseFloat($("#transfer_unit_amount").val());
    var total_amount = transfer_qty * unit_price;

    if(transfer_qty > 0 && current_qty >= transfer_qty)
    {
        $("#transfer_qty_warning").css('display', 'none');
        $("#transfer_qty").css('border-color', 'lime');
        $("#td_total_amount").text(total_amount);
    }//validate
    else
    {
        $("#transfer_qty_warning").css('display', 'block');
        $("#transfer_qty").css('border-color', 'red');
        $(this).val('');
        $(this).focus();
    }//else
});//change qty

$("#receive_qty").change(function(){
    var current_qty = parseFloat($('#td_current_qty').text());
    var transfer_qty = parseFloat($(this).val());
    var unit_price = parseFloat($("#transfer_unit_amount").val());
    var total_amount = transfer_qty * unit_price;
    //can't receive more than was sent
    var sent_qty = parseFloat($("#hide_transfer_qty").val());
    var within_sent = isNaN(sent_qty) || sent_qty <= 0 || transfer_qty <= sent_qty;

    if(transfer_qty > 0 && current_qty >= transfer_qty && within_sent)
    {
        $("#receive_qty_warning").css('display', 'none');
        $("#receive_qty").css('border-color', 'lime');
        $("#td_total_amount").text(total_amount);
    }//validate
    else
    {
        $("#receive_qty_warning").css('display', 'block');
        $("#receive_qty").css('border-color', 'red');
        $(this).val('');
        $(this).focus();
    }//else
});//change receive qty

$("#transfer_unit_amount").change(function(){
    var unit_price = parseFloat($("#transfer_unit_amount").val());
    var transfer_qty = parseFloat($("#transfer_qty").val());
    var total_amount = transfer_qty * unit_price;

    if(unit_price < 0)
    {
        $("#transfer_price_warning").css('display', 'block');
        $("#transfer_unit_amount").css('border-color', 'red');
        $(this).val('');
        $(this).focus();
    }//not valid amount
    else
    {
        $("#transfer_price_warning").css('display', 'none');
        $("#transfer_unit_amount").css('border-color', 'lime');
        $("#td_total_amount").text(total_amount);
    }//else

});//change unit price

//============================ Add Transfer details ============================//
$("#btn_add_transfer_detail").click(function(){
    var transferHeaderID = $("#hide_header_id").val();
    var current_qty = parseFloat($('#td_current_qty').text());
    var variation_id = $('#td_variation_id').text();
    var transfer_qty = parseFloat($("#transfer_qty").val());
    var purchase_price = parseFloat($("#transfer_unit_amount").val());
    var selling_price = parseFloat($("#transfer_unit_sell").val());
    var mnf_date = $("#mnf_date").val();
    var exp_date = $("#exp_date").val();
    var total_amount = parseFloat($("#td_total_amount").text());
    var product_id = $("#cmb_products").val();
    var batch_id = $("#cmb_batch").val();

    if(current_qty != 0)
    {
        if(transfer_qty > 0)
        {
            $.get("../AJAX/Transfer/addTransferDetail.php", {
                transfer_qty: transfer_qty,
                purchase_price: purchase_price,
                selling_price: selling_price,
                mnf_date: mnf_date,
                exp_date:exp_date,
                total_amount: total_amount,
                product_id: product_id,
                variation_id: variation_id,
                header_id: transferHeaderID,
                batch_id:batch_id
            }, function(response){
                
                console.log(response);
                if(response==0)
                {
                    alert("Item already added in cart");
                }
                else if(response==2)
                {
                    alert("Items can only be added by the sending shop while the transfer is on hold or pending.");
                }
                else if(response==3)
                {
                    alert("Not enough stock for this quantity. Please check the transfer qty.");
                }
                
                $("#transfer_qty").val("");
                $("#transfer_unit_amount").val("");
                $("#transfer_unit_sell").val("");
                $("#mnf_date").val("");
                $("#exp_date").val("");
                $("#transfer_qty").val("");
                $("#td_current_qty").text('0');
                $("#td_variation_id").text('0');
                $("#td_variation_name").text('0');
                $("#td_total_amount").text('0');
                //$("#cmb_products").select2('val', 0);

                //refresh table
                getTransferDetail(transferHeaderID);

                //get totals
                setTimeout(() => {
                    getFinalTotal(transferHeaderID);
                }, 1000);

            });//get data
        }
        else
        {
            $("#transfer_qty_warning").css('display', 'block');
            $("#transfer_qty").css('border-color', 'red');
            $("#transfer_qty").val('');
            $("#transfer_qty").focus();
            return;
        }//no transfer amount
    }//current qty check
    else
    {
        $("#cmb_products").css('border-color', 'red');
        return;
    }//no current qty
});//add grn transfer

$("#btn_edit_transfer_detail").click(function(){
    var transferDetailID = $("#hide_transfer_detail_id").val();
    var transferHeaderID = $("#hide_header_id").val();

    var variation_id = $("#td_variation_id").text();
    var current_qty = parseFloat($('#td_current_qty').text());
    var transfer_qty = parseFloat($("#transfer_qty").val());
    var receive_qty = parseFloat($("#receive_qty").val());
    var purchase_price = parseFloat($("#transfer_unit_amount").val());
    var selling_price = parseFloat($("#transfer_unit_sell").val());
    var mnf_date = $("#mnf_date").val();
    var exp_date = $("#exp_date").val();
    var total_amount = parseFloat($("#td_total_amount").text());
    var batch_id = $("#cmb_batch").val();
    
    // add default value for receive qty
    receive_qty = isNaN(receive_qty) ? transfer_qty : receive_qty;

    // add default value for transfer qty
    var old_transfer_qty = parseFloat($("#hide_transfer_qty").val());
    var transfer_qty = isNaN(transfer_qty) ? old_transfer_qty : transfer_qty;

    //product ID and inventory is
    var product_id = $("#ids").val();
    var inventory_id = $("#hide_inventory_id").val();

    if(current_qty != 0)
    {
        if(transfer_qty > 0)
        {
            $.get("../AJAX/Transfer/editTransferDetail.php", {
                inventory_id:inventory_id,
                variation_id:variation_id,
                transfer_qty: transfer_qty,
                receive_qty:receive_qty,
                purchase_price: purchase_price,
                selling_price: selling_price,
                mnf_date:mnf_date,
                exp_date:exp_date,
                total_amount: total_amount,
                product_id: product_id,
                batch_id:batch_id,
                transfer_detail_id: transferDetailID
            }, function(data){
                if($.trim(data) != '1')
                {
                    alert($.trim(data));
                }//not saved

                $("#transfer_qty").val("");
                $("#receive_qty").val("");
                $("#transfer_unit_amount").val("");
                $("#transfer_unit_sell").val("");
                $("#mnf_date").val("");
                $("#exp_date").val("");
                $("#transfer_qty").val("");
                $("#td_current_qty").text('0');
                $("#td_variation_id").text('0');
                $("#td_variation_name").text('0');
                $("#td_total_amount").text('0');
                $("#product_detail").text("");

                //hide edit button
                $("#btn_add_transfer_detail").css('display', 'block');
                $("#btn_edit_transfer_detail").css('display', 'none');

                //refresh table
                getTransferDetail(transferHeaderID);

                //get totals
                setTimeout(() => {
                    getFinalTotal(transferHeaderID);
                }, 1000);
                
            });//get data
        }
        else
        {
            $("#transfer_qty_warning").css('display', 'block');
            $("#transfer_qty").css('border-color', 'red');
            $("#transfer_qty").val('');
            $("#transfer_qty").focus();
            return;
        }//no transfer amount
    }//current qty check
    else
    {
        $("#cmb_products").css('border-color', 'red');
        return;
    }//no current qty
});//edit tranfer details

$("#tbl_transfer_detail").on('click', '.btn_transfer_edit', function(){
    let row = $(this).closest('tr');
    let id = row.data('id');

    //assign value
    $("#hide_transfer_detail_id").val(id);

    //show edit button
    $("#btn_add_transfer_detail").css('display', 'none');
    $("#btn_edit_transfer_detail").css('display', 'block');

    $.get("../AJAX/Transfer/getOneTransferDetail.php", {
        detail_id: id
    }, function(data){
        const obj = JSON.parse(data);
    
        var product_id = obj[0]['PDID'];
        var inventory_id = obj[0]['InventoryID'];
        var barcode = obj[0]['Barcode'];
        var item_name =  obj[0]['ItemName'];
        var variation_id =  obj[0]['VariationID'];
        var variation_name =  obj[0]['VariationName'];
        var batch_id =  obj[0]['Batch_ID'];
        var current_qty = parseFloat(obj[0]['CurrentQty']);
        var transfer_qty =  obj[0]['TransferQty'];
        var receive_qty =  obj[0]['ReceivedQty'];
        var unit_purchase_price =  obj[0]['UnitPurchasePrice'];
        var unit_selling_price =  obj[0]['UnitSellingPrice'];
        var mnf_date =  obj[0]['MnfDate'];
        var exp_date =  obj[0]['ExpDate'];
        var transfer_amount =  obj[0]['TransferTotalAmount'];

        //default transfer qty
        $("#hide_transfer_qty").val(transfer_qty);

        $("#product_detail").html(barcode + "<br>" + item_name);
        $("#ids").val(product_id);
        $("#hide_inventory_id").val(inventory_id);

        //load batch id
        $("#cmb_batch").html($('<option>', {
            value: batch_id,
            text: batch_id
        }));

        $("#cmb_batch").val(batch_id);

        //load variation id
        $("#td_variation_id").val(variation_id);
        $("#td_variation_name").text(variation_name);

        $("#td_current_qty").text(current_qty);

        $("#transfer_qty").val(transfer_qty);

        $("#receive_qty").val(receive_qty);

        //prices
        $("#transfer_unit_amount").val(unit_purchase_price);
        $("#transfer_unit_sell").val(unit_selling_price);

        //mnf & exp date
        $("#mnf_date").val(mnf_date);
        $("#exp_date").val(exp_date);

        $("#td_total_amount").text(transfer_amount);

        //get totals
        setTimeout(() => {
            getFinalTotal(transferHeaderID);
        }, 1000);

    });//get one transfer detail
});//edit transfer detail

$("#tbl_transfer_detail").on('click', '.btn_transfer_delete', function(){
    let row = $(this).closest('tr');
    let id = row.data('id');

    var transferHeaderID = $("#hide_header_id").val();

    $.get("../AJAX/Transfer/deleteTransferDetail.php", {
        transfer_detail_id: id
    }, function(data){
        if($.trim(data) != '1')
        {
            alert($.trim(data));
        }//not deleted

        //refresh table
        getTransferDetail(transferHeaderID);

        //get totals
        setTimeout(() => {
            getFinalTotal(transferHeaderID);
        }, 1000);
        
    });//delete transfer detail
});

$("#btn_submit_transfer").click(function(){
    $("#transferdetail_modal").modal('toggle');

    var transfer_header_id = $("#hide_header_id").val();

    $.get("../AJAX/Transfer/getTransferDetailTotal.php", {
        transfer_header_id: transfer_header_id
    }, function(data){
        const obj = JSON.parse(data);

        var rowCount = parseFloat(obj.row_count);
        var transfer_qty = parseFloat(obj.transfer_qty);
        var receive_qty = parseFloat(obj.receive_qty);
        var transfer_total = parseFloat(obj.transfer_total);

        //assign in to totalrowCount
        $("#data_row_count").text(rowCount);
        $("#transfer_item_count").text(transfer_qty);
        $("#receive_item_count").text(receive_qty);
        $("#data_purchase_price").text(transfer_total);

    });//get detail total    

    var transferHeaderID = $("#hide_header_id").val();
    $("#hide_transferheader_id").val(transferHeaderID);

});//open submit transfer

$("#transfer_amount").keyup(function(){
    var transfer_amount = 0;
    $("#tbl_transfer_total #sub_purchase_price").each(function(){
        // alert($(this).html());
        transfer_amount = parseFloat($(this).html());
    });

    var enter_value = $("#transfer_amount").val();

    if(transfer_amount == enter_value)
    {
        $("#transfer_amount").css('border-color' , 'lime');
    }//green
    else
    {
        $("#transfer_amount").css('border-color' , 'crimson');
    }
});

});//transfer jquery

//===================== total ==================//
function getFinalTotal(transfer_header_id)
{
    $.get("../AJAX/Transfer/getTransferDetailTotal.php", {
        transfer_header_id: transfer_header_id
    }, function(data){
        const obj = JSON.parse(data);

        var rowCount = parseFloat(obj.row_count);
        var transfer_qty = parseFloat(obj.transfer_qty);
        var receive_qty = parseFloat(obj.receive_qty);
        var transfer_total = parseFloat(obj.transfer_total);

        //assign in to totalrowCount
        $("h4#sub_row_count").text(rowCount);
        $("h4#sub_transfer_count").text(transfer_qty);
        $("h4#sub_receive_count").text(receive_qty);
        $("h4#sub_purchase_price").text(transfer_total);

    });//get detail total    
}//get final total

function getTransferDetail(header_id)
{
    $.get("../AJAX/Transfer/getTransferDetail.php", {
        header_id: header_id
    }, function(data){
        $("#tbl_transfer_detail").html(data);
    });//get table
    
}//load table