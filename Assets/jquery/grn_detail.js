$(document).ready(function(){
//====================== Initialize ========================//
    $("#btn_edit_grn_detail").css('display', 'none');
    $("#product_detail").css('display', 'none');

    //validate input
    $("#prod_qty").change(function(){
        var prodQty = parseFloat($(this).val());
        if(prodQty <= 0)
        {
            $("#qty_warning").css('display', 'block');
            $("#prod_qty").css('border-color', 'red');
            $("#prod_qty").val("");
            $("#prod_qty").focus();
            return;
        }//no qty
        else
        {
            $("#qty_warning").css('display', 'none');
            $("#prod_qty").css('border-color', 'LimeGreen');
        }
    });//qty changed

    //validate purchase price
    $("#purchase_price").change(function(){
        var purchasePrice = parseFloat($(this).val());
        if(purchasePrice < 0)
        {
            $("#pur_warning").css('display', 'block');
            $("#purchase_price").css('border-color', 'red');
            $("#purchase_price").val("");
            $("#purchase_price").focus();
            return;
        }//purchase price less than 0
        else
        {
            $("#pur_warning").css('display', 'none');
            $("#purchase_price").css('border-color', 'LimeGreen');
        }
    });//purchase price changes

    //validate selling price
    $("#selling_price").change(function(){
        var sellingPrice = parseFloat($(this).val());
        if(sellingPrice < 0)
        {
            $("#sel_warning").css('display', 'block');
            $("#selling_price").css('border-color', 'red');
            $("#selling_price").val("");
            $("#selling_price").focus();
            return;
        }//purchase price less than 0
        else
        {
            $("#sel_warning").css('display', 'none');
            $("#selling_price").css('border-color', 'LimeGreen');
        }
    });//validate

    //validate label price
    $("#label_price").change(function(){
        var labelPrice = parseFloat($(this).val());
        if(labelPrice < 0)
        {
            $("#lab_warning").css('display', 'block');
            $("#label_price").css('border-color', 'red');
            $("#label_price").val("");
            $("#label_price").focus();
            return;
        }//purchase price less than 0
        else
        {
            $("#lab_warning").css('display', 'none');
            $("#label_price").css('border-color', 'LimeGreen');
        }
    });//label price changed

    $("#mnf_date").change(function(){
        //validate mnf date
        var mnfDate = $(this).val();
        mnfDate = new Date(mnfDate);

        var d = new Date();
        var currentDate = d.getFullYear() + "-" + (d.getMonth()+1) + "-" + d.getDate();
        currentDate = new Date(currentDate);

        var diff = new Date(currentDate - mnfDate);
        var days = diff/1000/60/60/24;

        if(days <= 0)
        {
            $("#mnf_warning").css('display', 'block');
            $("#mnf_date").css('border-color', 'red');
            $("#mnf_date").val("");
            $("#mnf_date").focus();
            return;
        }//not valid date
        else
        {
            $("#mnf_warning").css('display', 'none');
            $("#mnf_date").css('border-color', 'LimeGreen');
        }//else
    });//mnf date changed
   
    //validate expire date
    $("#exp_date").change(function(){
        var mnfDate = $("#mnf_date").val();
        mnfDate = new Date(mnfDate);

        var expireDate = $(this).val();
        expireDate = new Date(expireDate);

        if(Date.parse(mnfDate))
        {
            var diff = new Date(expireDate - mnfDate);
            var days = diff/1000/60/60/24;

            if(days > 0)
            {
                //check current date
                var d = new Date();
                var currentDate = d.getFullYear() + "-" + (d.getMonth()+1) + "-" + d.getDate();
                currentDate = new Date(currentDate);

                var diff = new Date(expireDate - currentDate);
                var days = diff/1000/60/60/24;

                if(days < 1)
                {
                    $("#exp_warning").css('display', 'block');
                    $("#exp_date").css('border-color', 'red');
                    $("#exp_date").val("");
                    $("#exp_date").focus();
                    return;
                }//no valid date
                else
                {
                    $("#exp_warning").css('display', 'none');
                    $("#exp_date").css('border-color', 'LimeGreen');
                }
            }//before mnf date
            else
            {
                $("#exp_warning").css('display', 'block');
                $("#exp_date").css('border-color', 'red');
                $("#exp_date").val("");
                $("#exp_date").focus();
                return;
            }//after expdate
        }//has mnf date
        else
        {
            $("#mnf_warning").css('display', 'block');
            $("#mnf_date").css('border-color', 'red');
            $("#mnf_date").focus();
            $("#exp_date").val("");
        }//no mnf date
    });//expdate change

    // $("body").on("click","#btn_add_grn_detail", function(){
    // $(".required").each(function()
    // {
    //     console.log($(this).val());
    //     var required = $(this).val();
    //     if (required == "") 
    //     {
    //         $(this).css("border-color","red");  
    //         $(this).parent().append("<span class='text-danger'>This field is required.</span>");
    //     }
    // });
    // });//add items to grn detail

    $("#cmb_product").select2({
        ajax:{
            url: '../AJAX/GRN/getProducts.php',
            dataType: 'json',
            delay: 250,
            data: function(params){
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
        minimumInputLength: 1
    });//cmb product

    $("#cmb_product").change(function(){
        var product_id = $(this).val();
        $("#ids").val(product_id);
        $.get("../AJAX/Products/getCmbVariation.php", {
            product_id: product_id
        }, function(data){
            $("#cmb_variation").html(data);

            $("#prod_qty").focus();
        });//get variations

        $.get("../AJAX/Products/getProductBatch.php", {
            product_id: product_id
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);
            var purchase_price = 0;
            var selling_price = 0;

            $.each(obj, function (key, value) { 
                console.log('val - ' + value['SellingPrice']);
                purchase_price = value['PurchasePrice'];
                selling_price = value['SellingPrice'];
            });

            $("#purchase_price").val(purchase_price);
            $("#selling_price").val(selling_price);
        });
    });//select

//======================= Add GRN Detail ====================//
    $("#btn_add_grn_detail").click(function(){
        var grn_header_id = $("#hide_header_id").val();
        var product_id = $("#cmb_product").val();
        var variation_id = $("#cmb_variation").val();
        var prod_qty = $("#prod_qty").val();
        var purchase_price = $("#purchase_price").val();
        var label_price = $("#label_price").val();
        var selling_price = $("#selling_price").val();
        var mnf_date = $("#mnf_date").val();
        var exp_date = $("#exp_date").val();
        var rack_id = $("#cmb_racks").val();
        $("body #ajaxshow").text("product_id");

        if(product_id == "")
        {
            alert("Please select a product.");
            return;
        }//no product
        else
        {
            $("#cmb_product").css('border-color', 'lime');
            $("#product_warning").css('display', 'none');
        }

        //check variation id
        variation_id = variation_id == undefined ? 1:$("#cmb_variation").val();

        if(prod_qty == "")
        {
            $("#qty_warning").css('display', 'block');
            $("#prod_qty").css('border-color', 'red');
            return;
        }//no prod qty
        else
        {
            $("#qty_warning").css('display', 'none');
            $("#prod_qty").css('border-color', 'lime');
        }

        if(purchase_price == "")
        {
            $("#pur_warning").css('display', 'block');
            $("#purchase_price").css('border-color', 'red');
            return;
        }//no purchase price
        else
        {
            $("#pur_warning").css('display', 'none');
            $("#purchase_price").css('border-color', 'lime');
        }//else

        //label price
        label_price = label_price == undefined ? 0 : $("#label_price").val();

        if(selling_price == "")
        {
            $("#sel_warning").css('display', 'block');
            $("#selling_price").css('border-color', 'red');
            return;
        }//no selling price
        else
        {
            $("#sel_warning").css('display', 'none');
            $("#selling_price").css('border-color', 'lime');
        }//else

        const date = new Date();
        let month = parseFloat(date.getMonth()) + 1;

        let current_date = date.getFullYear() +"-"+ month +"-"+ date.getDate();

        mnf_date = mnf_date == undefined ? current_date : $("#mnf_date").val();
        exp_date = exp_date == undefined ? current_date : $("#exp_date").val();

        rack_id = rack_id == undefined ? 1 : $("#cmb_racks").val();

        // $.ajax({
        //     url:'../AJAX/GRN/addGRNDetails.php',
        //         method:'post',
        //         data:{product_id: product_id,
        //             variation_id: variation_id,
        //             prod_qty: prod_qty,
        //             purchase_price: purchase_price,
        //             label_price: label_price,
        //             selling_price: selling_price,
        //             mnf_date: mnf_date,
        //             exp_date: exp_date,
        //             rack_id: rack_id,
        //             grn_header_id: grn_header_id},
        //         success:function(response)
        //         {
        //             $("body #ajaxshow").html(response);
        //         }
        // });
        $.get("../AJAX/GRN/addGRNDetails.php", {
            product_id: product_id,
            variation_id: variation_id,
            prod_qty: prod_qty,
            purchase_price: purchase_price,
            label_price: label_price,
            selling_price: selling_price,
            mnf_date: mnf_date,
            exp_date: exp_date,
            rack_id: rack_id,
            grn_header_id: grn_header_id
        }, function(response){
           //clear data
            $("#cmb_product").val("");
            $("#cmb_variation").val("1");
            $("#prod_qty").val("");
            $("#purchase_price").val("");
            $("#label_price").val("");
            $("#selling_price").val("");
            $("#mnf_date").val("");
            $("#exp_date").val("");
            $("#cmb_racks").val("1");

            LoadTable(grn_header_id);

            //get total
            setTimeout(() => {
                getFinalTotal();
            }, 1000);
        });//add grn details

    });//add items

//======================= Edit GRN details =====================//
    $("#btn_edit_grn_detail").click(function(){
        var grn_header_id = $("#hide_header_id").val();
        var product_id = $("#ids").val();
        var grn_detail_id = $("#hide_detail_id").val();

        var d = new Date();
        var current_date = d.getFullYear() + "-" + (d.getMonth()+1) + "-" + d.getDate();

        var variation_id = $("#cmb_variation").val() == undefined ? 1 : $("#cmb_variation").val();
        var prod_qty = $("#prod_qty").val();
        var purchase_price = $("#purchase_price").val();
        var label_price = $("#label_price").val() == undefined ? 0 : $("#label_price").val();
        var selling_price = $("#selling_price").val();
        var mnf_date = $("#mnf_date").val() == undefined ? current_date : $("#mnf_date").val();
        var exp_date = $("#exp_date").val() == undefined ? current_date : $("#exp_date").val();
        var rack_id = $("#cmb_racks").val() == undefined ? 1 : $("#cmb_racks").val();

        $.get("../AJAX/GRN/editGRNDetails.php", {
            prod_qty: prod_qty,
            purchase_price: purchase_price,
            label_price: label_price,
            selling_price: selling_price,
            mnf_date: mnf_date,
            exp_date: exp_date,
            variation_id: variation_id,
            product_id: product_id,
            rack_id: rack_id,
            grn_detail_id: grn_detail_id
        }, function(){
            LoadTable(grn_header_id);

            //get total
            setTimeout(() => {
                getFinalTotal();
            }, 1000);
        });//edit grn details

        //clear data
        $("#cmb_product").val("");
        $("#cmb_variation").val("1");
        $("#prod_qty").val("");
        $("#purchase_price").val("");
        $("#label_price").val("");
        $("#selling_price").val("");
        $("#mnf_date").val("");
        $("#exp_date").val("");
        $("#cmb_racks").val("1");

        //hide add button
        $("#btn_add_grn_detail").css('display', 'block');
        $("#btn_edit_grn_detail").css('display', 'none');
    });//edit grn details

$("#tbl_grn_details").on('click', '.btn_grn_edit', function(){
    let row = $(this).closest('tr');
    let id = row.data('id');

    var grn_header_id = $("#hide_header_id").val();
    //hide add button
    $("#btn_add_grn_detail").css('display', 'none');
    $("#btn_edit_grn_detail").css('display', 'block');

    $("#hide_detail_id").val(id);

    $.get("../AJAX/GRN/getOneGRN.php", {
        grn_detail_id: id
    }, function(data){
        const obj = JSON.parse(data);

        var barcode = obj[0]['Barcode'];
        var item_name = obj[0]['ItemName'];
        var item_qty = obj[0]['InitQty'];
        var purchase_price = obj[0]['UnitPurchasePrice'];
        var label_price = obj[0]['UnitLabelPrice'];
        var selling_price = obj[0]['UnitSellPrice'];
        var mnf_date = obj[0]['MnfDate'];
        var exp_date = obj[0]['ExpDate'];
        var productID = obj[0]['PDID'];

        $("#product_detail").css('display', 'block');
        $("#product_detail").text(barcode +" - "+ item_name);
        $("#prod_qty").val(item_qty);
        $("#purchase_price").val(purchase_price);
        $("#label_price").val(label_price);
        $("#selling_price").val(selling_price);
        $("#mnf_date").val(mnf_date);
        $("#exp_date").val(exp_date);
        $("#ids").val(productID);

        LoadTable(grn_header_id);

        //get total
        setTimeout(() => {
            getFinalTotal();
        }, 1000);
    });//get one grn detail
});//table button click

$("#tbl_grn_details").on('click', '.btn_grn_delete', function(){
    let row = $(this).closest('tr');
    let id = row.data('id');

    var grn_header_id = $("#hide_header_id").val();
    $.get("../AJAX/GRN/deleteGRNDetails.php", {
        grn_detail_id: id
    }, function(){
        //clear data
        $("#cmb_product").val("");
        $("#cmb_variation").val("1");
        $("#prod_qty").val("");
        $("#purchase_price").val("");
        $("#label_price").val("");
        $("#selling_price").val("");
        $("#mnf_date").val("");
        $("#exp_date").val("");
        $("#cmb_racks").val("1");

        $("#hide_detail_id").val("0");

        LoadTable(grn_header_id);

        //get total
        setTimeout(() => {
            getFinalTotal();
        }, 1000);

    });//delete row 
});

//========================= Submit GRN ===========================//
$("#btn_submit_grn").click(function(){
    $("#grndetail_modal").modal('toggle');

    var rowCount = $("#tbl_grn_details tr").length - 1;

    var total = 0;
    var totalItems = 0;
    $("#tbl_grn_details tr").each(function(){
        var price = $(this).find("td").eq(7).html();
        var itemCount = $(this).find("td").eq(3).html();

        if(price != undefined)
        {
            total += parseFloat(price); 
            totalItems += parseFloat(itemCount);
        }//has value   
    });//go through table

    $("#data_row_count").text(rowCount);
    $("#data_item_count").text(totalItems);
    $("#data_purchase_price").text(total);

    //get header id
    var grn_header_id = $("#hide_header_id").val();
    $("#hide_grnheader_id").val(grn_header_id);
});//open submit grn

//================= Section & Racks =====================//
$("#cmb_section").change(function(){
    var section_id = $(this).val();
    $.get("../AJAX/Racks/getRackBySection.php", {
        section_id: section_id
    }, function(data){
        $("#cmb_racks").html(data);
        $("#cmb_racks").focus();
    });
});//section changes

//====================== Add Products ====================//
$("#btn_open_grn_products").click(function(){
    $("#grn_product_modal").modal('toggle');
});//open add product modal

$("#cmb_category").change(function(){
    var category_id = $(this).val();
    $.get("../AJAX/AjaxCategory/getSubcategory.php", {
        category_id: category_id
    }, function(data){
        $("#cmb_subcategory").html(data);
        $("#cmb_subcategory").focus();
    });//get subcategory
});//cmb changed

$("#btn_save_product").click(function(){
    var subcategory_id = $("#cmb_subcategory").val();
    var barcode = $("#barcode").val();
    var prod_name = $("#prod_name").val();
    var second_name = $("#second_name").val() == undefined ? "" : $("#second_name").val();
    var prod_description = $("#prod_description").val();
    var prod_carton_qty = $("#prod_carton_qty").val() == undefined ? "" : $("#prod_carton_qty").val();
    var cmb_purchase_unit = $("#cmb_purchase_unit").val();
    var conversion_rate = $("#conversion_rate").val();
    var cmb_selling_unit = $("#cmb_selling_unit").val();
    var prod_purchase_price = $("#prod_purchase_price").val();
    var prod_selling_price = $("#prod_selling_price").val();

    $.get("../AJAX/Products/setProduct.php", {
        barcode: barcode,
        prod_name: prod_name,
        second_name: second_name,
        prod_description: prod_description,
        prod_carton_qty: prod_carton_qty,
        subcategory_id: subcategory_id,
        cmb_purchase_unit: cmb_purchase_unit,
        conversion_rate: conversion_rate,
        cmb_selling_unit: cmb_selling_unit,
        prod_selling_price:prod_selling_price,
        prod_purchase_price:prod_purchase_price
    }, function(data){
        alert(data);

        $("#barcode").val("");
        $("#prod_name").val("");
        $("#second_name").val("");
        $("#prod_description").val("");
        $("#prod_carton_qty").val("");
        $("#cmb_purchase_unit").val("");
        $("#conversion_rate").val("");
        $("#cmb_selling_unit").val("");
    });
});//save product


//===================       Added by Imila on 2025-03-14 for discount calculation=================//
// Attach event listeners to saleDiscount and SaleDiscountType inputs
$("#saleDiscount, #SaleDiscountType").on("input change", function() {
    calculateDiscount();
});

    // Function to calculate discount and update grand total
function calculateDiscount() {
    // Get the total purchase price
    var totalPurchasePrice = parseFloat($("#sub_purchase_price").text()) || 0;

    // Get the discount value and type
    var discountValue = parseFloat($("#saleDiscount").val()) || 0;
    var discountType = $("#SaleDiscountType").val();

    var discount = 0;

    // Calculate discount based on type
    if (discountType == 1) { // Percentage discount
        discount = (totalPurchasePrice * discountValue) / 100;
    } else if (discountType == 2) { // Flat discount
        discount = discountValue;
    }

    // Ensure discount does not exceed the total purchase price
    if (discount > totalPurchasePrice) {
        discount = totalPurchasePrice;
    }

    // Calculate the grand total
    var grandTotal = totalPurchasePrice - discount;

    // Update the total discount and grand total fields
    $("#sub_purchase_price").val(totalPurchasePrice.toFixed(2));
    $("#totalDiscount").val(discount.toFixed(2));
    $("#grand_total").html(`<b>${grandTotal.toFixed(2)}</b>`);

    //update hidden values
    $("#hiddenDiscountType").val(discountType);
    $("#hiddenTotalDiscount").val(discount.toFixed(2));
    $("#hiddenSaleDiscount").val(discountValue.toFixed(2));

}

// Initial calculation on page load
calculateDiscount();


//a scanner upload added lines (Assets/jquery/scan_upload.js): reload the table and totals
$(document).on('scanupload:applied', function(){
    var grn_header_id = $("#hide_header_id").val();
    LoadTable(grn_header_id);
    setTimeout(() => {
        getFinalTotal();
    }, 1000);
});

//===================== Functions ==================//
function getFinalTotal()
{
    var rowCount = $("#tbl_grn_details tr").length - 1

    var total = 0;
    var totalItems = 0;
    $("#tbl_grn_details tr").each(function(){
        var price = $(this).find("td").eq(2).html();
        var itemCount = $(this).find("td").eq(1).html();

        if(price != undefined)
        {
            total += parseFloat(price); 
            totalItems += parseFloat(itemCount);
        }//has value   
    });//go through table
    
    
    var discountValue = parseFloat($("#saleDiscount").val()) || 0; // Get discount input
    var discountType = $("#SaleDiscountType").val();  // Get selected discount type

    console.log(discountValue);
    

    var discount = 0;

    if (discountType === "1") {  // Percentage discount
        discount = (total * discountValue) / 100;
    } else if (discountType === "2") {  // Flat discount
        discount = discountValue;
    }

    total = total - discount;  // Calculate final total

    total = total.toFixed(2);
    //assign in to totalrowCount
    $("h4#sub_row_count").text(rowCount);
    $("h4#sub_item_count").text(totalItems);
    $("h4#sub_purchase_price").text(total);
}//get final total

function LoadTable(grn_header_id)
{
    $.get("../AJAX/GRN/getGRNDetails.php", {
        grn_header_id: grn_header_id
    }, function(data){
        $("#tbl_grn_details").html(data);
    });//load table
}//load table

});//jQuery grn details