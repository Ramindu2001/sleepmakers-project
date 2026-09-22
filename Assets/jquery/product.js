$(document).ready(function(){

    
    if ($(window).width() >= 1099) {
        $('.left-sidebar').css("margin-left","-270px");
    }    
    $('#headerCollapse2').css("display","block");
    $('#headerCollapse3').css("display","none");
    $('.body-wrapper').css("margin-left","0");
    $("#side-closes").css("display","block");
    $(".app-header").css("width","100%");

    function validateForm() {
        const checkboxes = $('input[type="checkbox"]');
        let isChecked = false;

        checkboxes.each(function() {
            if ($(this).is(':checked')) {
                isChecked = true;
                return false; // break out of the loop
            }
        });

        return true;
    }

    $('#barcode_form').submit(function() {
        return validateForm();
    });
    $("#product_ids").click(function(){
        if($(this).is(':checked'))
        {
            $("body #product_id").prop("checked", true)
        }
        else
        {
            $("body #product_id").prop("checked", false)
        }
    });
    $("body #product_id").click(function(){
        if($(this).is(':checked'))
        {
            // $("body #product_id").prop("checked", true)
            $("body #product_id").each(function(){
                if($(this).is(':checked'))
                {
                    var has=true;
                }
                else
                {
                    var hashes=true;
                }
            })
            if (hashes==true)
            {
                $("body #product_ids").prop("checked", false)
            }
            else
            {
                $("body #product_ids").prop("checked", true)
            }
        }
        else
        {
            $("body #product_ids").prop("checked", false)
        }
    });

    $("#btn_add_products").click(function(){
        $("#product_modal").modal('toggle');

        getRowCount();

        $("#btn_save_product").css('display', 'block');
        $("#btn_update_product").css('display', 'none');

        $("#btn_save_product").attr('disabled', false);
        $("#btn_update_product").attr('disabled', true);

        $("#hide_product_id").val(0);
        $("#product_no").val("");
        $("#cmb_category").val(0);
        $("#cmb_subcategory").val(0);
        $("#barcode").val("");
        $("#prod_name").val("");
        $("#second_name").val("");
        $("#prod_description").val("");
        $("#prod_purchase_price").val("0.00");
        $("#prod_selling_price").val("0.00");
        $("#prod_Item_Dis").val("0.00");
        $("#prod_Item_Dis_flat").val("0.00");
        $("#prod_carton_qty").val("a");
        $("#chk_service").attr('checked', false);

        $("#img_product").attr("src", "../Assets/Images/icons/product.png");
    });//open product

    $("#close_product_modal").click(function(){
        $("#product_modal").modal('hide');
    });//close product

    $("#cmb_category").change(function(){
        var category_id = $(this).val();
        $.get("../AJAX/AjaxCategory/getSubcategory.php", {
            category_id: category_id
        }, function(data){
            $("#cmb_subcategory").html(data);
            $("#cmb_subcategory").focus();
        });//get subcategory
    });//cmb changed

    $("#prod_image").change(function(event){
        var size=this.files[0].size;
        if (size>=500000) 
        {
            $("#success").fadeOut();
            $("#danger").fadeIn();
            $(this).css("border-color","red");
            $('#btn_save_product').attr('disabled','disabled');
        }//greater than 500kb
        else
        {
            $("#success").fadeIn();
            $("#danger").fadeOut();
            $(this).css("border-color","green");
            $('#btn_save_product').removeAttr('disabled', 'disabled');

            var url = URL.createObjectURL(event.target.files[0]);
            $("#img_product").attr("src", url);
        }//else
    });//file changed

//====================== Product Edit ========================//
$("#tbl_products").on('click', '.btn_edit_product', function(){
    let row = $(this).closest('tr');
    let id = row.data('id');

    $.get("../AJAX/Products/getOneProduct.php", {
        product_id: id
    }, function(data){
        const obj = JSON.parse(data);
      
        //get values from json
        var product_id = obj[0]['PDID'];
        var category_id = obj[0]['CTID'];
        var subcategory_id = obj[0]['SCID'];
        var barcode = obj[0]['Barcode'];
        var product_name = obj[0]['ItemName'];
        var second_name = obj[0]['SecondName'];
        var prod_description = obj[0]['ProdDescription'];
        var prod_image = obj[0]['ProdImage'];
        var purchase_price = obj[0]['ProdPurchasePrice'];
        var selling_price = obj[0]['ProdSellPrice'];
        var carton_qty = obj[0]['CartonQty'];
        var is_service = obj[0]['ItemType'];
        var purchase_unit = obj[0]['PurchaseUnit'];
        var convertion_rate = obj[0]['UnitConversion'];
        var selling_unit = obj[0]['SellingUnit'];
        var prodDiscount  = obj[0]['prodDiscount'];
        var prodFlatDiscount  = obj[0]['prodFlatDiscount'];
        var ProductStat  = obj[0]['ProductStat'];
        var is_fixedPrice  = obj[0]['is_fixedPrice'];


        //load subcategory cmb
        $.get("../AJAX/AjaxCategory/getSubcategory.php", {
            category_id: category_id
        }, function(data){
            $("#cmb_subcategory").html(data);
            $("#cmb_subcategory").val(subcategory_id);
        });//get subcategory

        $("#product_modal").modal('toggle');

        //assign values
        $("#hide_product_id").val(product_id);
        $("#cmb_category").val(category_id);
        $("#cmb_subcategory").val(subcategory_id);
        $("#barcode").val(barcode);
        $("#prod_name").val(product_name);
        $("#second_name").val(second_name);
        $("#prod_description").val(prod_description);
        $("#prod_purchase_price").val(purchase_price);
        $("#prod_selling_price").val(selling_price);
        $("#prod_carton_qty").val(carton_qty);
        $("#cmb_purchase_unit").val(purchase_unit);
        $("#conversion_rate").val(convertion_rate);
        $("#cmb_selling_unit").val(selling_unit);
        $("#prod_Item_Dis").val(prodDiscount);
        $("#prod_Item_Dis_flat").val(prodFlatDiscount);

        if(is_service == "P")
        {
            $("#chk_service").attr('checked', false);
        }//is product
        else
        {
            $("#chk_service").attr('checked', true);
        }//is service
        if(is_fixedPrice == "1")
        {
            $("#chk_fp").attr('checked', true);
        }//is product
        else
        {
            $("#chk_fp").attr('checked', false);
        }//is service

        if(ProductStat == "1")
        {
            $("#status").attr('checked', true);
        }//is product
        else
        {
            $("#status").attr('checked', false);
        }//is service

        if(prod_image == null)
        {
            $("#img_product").attr('src', "../Assets/Images/icons/product.png");
        }//no image
        else
        {
            $("#img_product").attr('src', "../Assets/Images/prod_images/" + prod_image);
        }//has image

        //hide save button
        $("#btn_save_product").css('display', 'none');
        $("#btn_update_product").css('display', 'block');

        $("#btn_save_product").attr('disabled', true);
        $("#btn_update_product").attr('disabled', false);

    });//get one product
});//edit button clicked

/* The "Print Barcode" dialog now lives in Assets/jquery/barcode-label.js.
   It owns #product_barcode_modal, the .btn_open_barcode buttons and the row
   tick boxes - nothing here may bind to them as well, or the two handlers
   fight over the same dialog. */

//============================== Variations =============================//
$("#btn_add_variation").click(function(){
    var varName = $("#variation_name").val();
    var product_id = $("#hide_product_id").val();

    if(varName == "")
    {
        $("#var_warning").css('display', 'block');
        $("#variation_name").css('border-color', 'red');
        return;
    }//empty input

    $.get("../AJAX/Products/addVariations.php", {
        variation_name: varName,
        product_id: product_id
    }, function(){
        $.get("../AJAX/Products/getVariations.php", {
            product_id: product_id
        }, function(data){
            $("#tbl_variations").html(data);
        });//get variations
    });//add variation

    $("#btn_add_variation").css('display', 'block');
    $("#btn_edit_variation").css('display', 'none');

    $("#variation_name").val("");
});//add variations

$("#tbl_variations").on('mousedown', 'tr', function(){
    var currentRow = $(this).closest('tr');
    var row_id = currentRow.find('td').eq(0).html();

    var varName = currentRow.find('td').eq(1).html();
    $("#edit_variation_" + row_id).click(function(){
        $("#variation_name").val(varName);
        $("#hide_variation_id").val(row_id);

        $("#btn_add_variation").css('display', 'none');
        $("#btn_edit_variation").css('display', 'block');
    });//edit click
});//table row click

$("#btn_edit_variation").click(function(){
    var varID = $("#hide_variation_id").val();
    var varName = $("#variation_name").val();
    var product_id = $("#hide_product_id").val();

    if(varName == "")
    {
        $("#var_warning").css('display', 'block');
        $("#variation_name").css('border-color', 'red');
        return;
    }//empty input

    $.get("../AJAX/Products/editVariation.php", {
        var_id: varID,
        variation_name: varName
    }, function(){
        $.get("../AJAX/Products/getVariations.php", {
            product_id: product_id
        }, function(data){
            $("#tbl_variations").html(data);
        });//get variations
    });//edit variation

    $("#btn_add_variation").css('display', 'block');
    $("#btn_edit_variation").css('display', 'none');

    $("#variation_name").val("");
});//edit variation

$("#barcode").change(function(){
    var barcode = $(this).val();

    //an empty field is not a duplicate - it is a request for an automatic code
    if ($.trim(barcode) === "") {
        $("#barcode").css('border-color', '');
        $("#barcode_warning").css('display', 'none');
        refreshBarcodeHint();
        return;
    }

    $.get("../AJAX/Products/getBarcode.php", {
        barcode: barcode
    }, function(data){
        if(data == 0)
        {
           $("#barcode").css('border-color', 'lime');
           $("#barcode_warning").css('display', 'none');
        }
        else
        {
            $("#barcode").css('border-color', 'red');
            $("#barcode_warning").css('display', 'block');

            $("#barcode_warning").text("Barcode already exists.");
        }

    });
});//barcode change

//=================== Automatic barcode (Add / Edit Product) ==================//
/**
 * Show the operator what an empty barcode field is going to become.
 *
 * This is a PREVIEW: it peeks at the sequence counter and never uses a number
 * up. The real code is allocated by the controller when the product is saved,
 * or by the Generate button below - which is why opening this dialog a dozen
 * times leaves no gaps in the numbering.
 */
function refreshBarcodeHint() {
    var hint = $("#barcode_auto_hint");
    if (hint.length === 0) {
        return;   //the shop is on an older copy of the dialog
    }

    //a typed barcode wins - there is nothing to preview
    if ($.trim($("#barcode").val()) !== "") {
        hint.html("");
        return;
    }

    hint.html('<span class="text-muted">Checking...</span>');

    $.ajax({
        url: "../AJAX/Products/generateBarcode.php",
        type: "GET",
        dataType: "json",
        data: {
            mode: "preview",
            subcat_id: $("#cmb_subcategory").val() || 0,
            item_name: $("#prod_name").val() || ""
        },
        success: function (res) {
            if (!res || res.ok !== true) {
                hint.html("");
                return;
            }

            if (res.disabled) {
                hint.html('<span class="text-muted">Leave empty and the product number is used.</span>');
                return;
            }

            hint.html('Will be generated as <strong class="bc-auto-code">'
                + $("<div>").text(res.code).html() + '</strong>');
        },
        error: function () {
            hint.html("");
        }
    });
}//refreshBarcodeHint

//the preview depends on the sub category and on the item name
$(document).on("change", "#cmb_subcategory", refreshBarcodeHint);

$(document).on("input", "#barcode", function () {
    if ($.trim($(this).val()) === "") {
        refreshBarcodeHint();
    } else {
        $("#barcode_auto_hint").html("");
    }
});

$(document).on("blur", "#prod_name", refreshBarcodeHint);

/**
 * "Generate" fills the field with a code that is ALREADY RESERVED.
 *
 * Unlike the hint above this really does consume a sequence number - that is
 * the point: the operator can write the code onto the stock before the product
 * is saved and be sure nobody else will get it.
 */
$(document).on("click", "#btn_generate_barcode", function (e) {
    e.preventDefault();

    var button = $(this);
    button.prop("disabled", true);

    $.ajax({
        url: "../AJAX/Products/generateBarcode.php",
        type: "POST",
        dataType: "json",
        data: {
            mode: "generate",
            subcat_id: $("#cmb_subcategory").val() || 0,
            item_name: $("#prod_name").val() || ""
        },
        success: function (res) {
            if (!res || res.ok !== true) {
                $("#barcode_warning").css("display", "block")
                    .text((res && res.message) ? res.message : "Could not generate a barcode.");
                return;
            }

            $("#barcode").val(res.code).css("border-color", "lime");
            $("#barcode_warning").css("display", "none");
            $("#barcode_auto_hint").html('<span class="text-success">Reserved for this product.</span>');
        },
        error: function () {
            $("#barcode_warning").css("display", "block").text("Could not reach the server.");
        },
        complete: function () {
            button.prop("disabled", false);
        }
    });
});



//====================== Search Product =======================//
$("#product_search").keyup(function (e) { 
    var txt_search = $(this).val();

    $.get("../AJAX/Products/getProductSearch.php", {
        txt_input: txt_search
    }, function(data){
        // alert(data);
        $("#tbl_products").html(data);
    });
});

//============================ Functions =========================//
function getRowCount()
{
    var row_count = 0;
    $("#tbl_products tr").each(function(){
        row_count += 1;
    });

    $("#product_no").val(row_count);
}//get row count

});//jQuery Products