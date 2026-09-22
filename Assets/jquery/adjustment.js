$(document).ready(function(){
    //hide edit button
    $("#btn_edit_adjust_detail").css('display', 'none');
    $("#variation_name").css('display', 'none');
    if ($(window).width() >= 1099) {
        $('.left-sidebar').css("margin-left","-270px");
    }
    $('#headerCollapse2').css("display","block");
    $('#headerCollapse3').css("display","none");
    $('.body-wrapper').css("margin-left","0");
    $("#side-closes").css("display","block");
    $(".app-header").css("width","100%");
    //get adjust type
    var adjustType = $("#hide_adjust_type").val();

    $("#cmb_product").select2({
        ajax:{
            url: '../AJAX/Adjustment/getProdSearch.php',
            dataType: 'json',
            delay: 250,
            data: function(params){
                var query = {
                    search: params.term,
                    adjustType: adjustType,
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
    });//cmb product select2

    $("#btn_open_adjustment").click(function(){
        $("#adjust_head_modal").modal('toggle');

    });//open adjustment modal

    $("#cmb_product").change(function(){
        var product_id = $(this).val();
        $.ajax({
            url:'../AJAX/Adjustment/getProdDetails.php',
                method:'post',
                data:{
                    product_id: product_id
                    },
                success:function(response)
                {
                    const obj = JSON.parse(response);
                    var opt="";
                    if(obj.length==0)
                    {
                        alert("No Stock Available");
                    }
                    else
                    {
                        if(obj[0]['type']=="batch")
                        {
                            $("#cmb_variation").attr("disabled", true);
                            $("#cmb_batch").attr("disabled", false);
                            $("#cmb_batch").focus();
                            for (var i = 0; i < obj.length; i++) {
                                opt += '<option value="' + obj[i]['id'] + '">' + obj[i]['text'] + '</option>';
                            }
                            $("#cmb_batch").html("");
                            $("#cmb_batch").append(opt);
                            $("#cmb_variation").html("");

                        }
                        else
                        {
                            $("#cmb_variation").attr("disabled", false);
                            $("#cmb_batch").attr("disabled", true);
                            for (var i = 0; i < obj.length; i++) {
                                opt += '<option value="' + obj[i]['id'] + '">' + obj[i]['text'] + '</option>';
                            }
                            $("#cmb_batch").html("");
                            $("#cmb_variation").html("");
                            $("#cmb_variation").append(opt);
                            $("#cmb_variation").focus();

                        }
                    }

                }
            });
    });//cmb change

    $("#cmb_variation").change(function(){
        var product_id=$("#cmb_product").val();
        var varriation_id_val=$(this).val();
        var batch=$("#cmb_batch");
        $.ajax({
            url:'../AJAX/Adjustment/getBatch.php',
                method:'post',
                data:{
                    product_id:product_id,
                    varriation_id:varriation_id_val,
                    },
                success:function(response)
                {
                    if(response.length==0 || response=="")
                    {
                        alert("No Batches Available");
                        $("#cmb_batch").html("");
                        $("#cmb_batch").attr("disabled", true);
                        $("#cmb_variation").focus();                                
                    }
                    else
                    {
                        batch.html(response); 
                        batch.removeAttr("disabled");
                        batch.focus();  
                    }
                     
                }
            });
    });

    $("#cmb_batch").change(function(){
        var PHID=$(this).val();
        $.ajax({
            url:'../AJAX/Adjustment/getProdDetails.php',
                method:'post',
                data:{
                    PHID:PHID,
                    },
                success:function(response)
                {
                    const obj = JSON.parse(response);
                    if(response.length==0 || response=="")
                    { 
                        alert("Something Went Wrong Please Try Again");
                    }
                    else
                    {
                        $("#avl-qty").text(obj[0]['CurrentQty']);
                        $("#purchase_price").val(obj[0]['PurchasePrice']);
                        $("#selling_price").val(obj[0]['SellingPrice']);
                        $("#mnf_date").val(obj[0]['MnfDate']);
                        $("#exp_date").val(obj[0]['ExpDate']);
                        $("#purchase_price_span").text(obj[0]['PurchasePrice']);
                        $("#selling_price_span").text(obj[0]['SellingPrice']);
                        $("#mnf_date_span").text(obj[0]['MnfDate']);
                        $("#exp_date_span").text(obj[0]['ExpDate']);
                        $("#prod_qty").focus();
                    }
                     
                }
            });
    })
    
    $("#tbl_items").on('click', 'tr', function(){
        var currentRow = $(this).closest('tr');
        var row_id = currentRow.find('td').eq(0).html();

        var product_id = currentRow.find('td').eq(9).html();
        var barcode = currentRow.find('td').eq(1).html();
        var item_name = currentRow.find('td').eq(2).html();
        var variation = currentRow.find('td').eq(3).html();

        var product_name = barcode +" <br> "+ item_name +" - "+ variation;

        $("#ids").val(product_id);
        $("#product_detail").html(product_name);
        $("#product_detail").css('display', 'block');
    });

    //============================ Add Items =========================//
    $("#btn_add_adjust_detail").click(function(){
        //APID, AdjustProdQty, UnitPurchasePrice, UnitSellingPrice, MnfDate, ExpDate, InventoryID, VariationID, RackID, AdjustStat, AdjustProdAmount, products_PDID, AdjustHeader_AHID

       var adjust_header_id = $("#hide_header_id").val();
       var product_id = $("#cmb_product").val();
       var adjust_qty = parseFloat($("#prod_qty").val());
       var price_history_id = parseFloat($("#cmb_batch").val());

       if(isNaN(price_history_id))
       {
           alert("Please select a batch...");
           return;
       }//select a batch

       $.get("../AJAX/Adjustment/setAdjustDetail.php", {
           adjust_header_id: adjust_header_id,
           adjust_qty: adjust_qty,
           product_id: product_id,
           price_history_id: price_history_id
       }, function(data){
           //alert(data);
           //refresh table
           LoadTable(adjust_header_id);

           $("#cmb_product").val(null).trigger('change');
           $("#avl-qty").text("0.000");
           $("#purchase_price_span").text("0.000");
           $("#mnf_date_span").text("0.000");
           $("#exp_date_span").text("0.000");
           $("#prod_qty").val("0.000");
           //get totals
           getFinalTotal(adjust_header_id);

           $("#cmb_out_batch").empty();
            
       });//pass data to insert
   });//add items 

    //============================ Edit Items =========================//
    $("#btn_edit_adjust_detail").click(function(){
         //APID, AdjustProdQty, UnitPurchasePrice, UnitSellingPrice, MnfDate, ExpDate, InventoryID, VariationID, RackID, AdjustStat, AdjustProdAmount, products_PDID, AdjustHeader_AHID

         var adjust_header_id = $("#hide_header_id").val();
         var product_id = $("#ids").val();
         var variation_id = $("#cmb_variation").val();
         var adjust_qty = parseFloat($("#prod_qty").val());
         var purchase_price = parseFloat($("#purchase_price").val());
         var selling_price = $("#selling_price").val();
         var mnf_date = $("#mnf_date").val();
         var exp_date = $("#exp_date").val();
         var rack_id = $("#cmb_racks").val();
         var adjust_detail_id = $("#hide_adjust_detail_id").val();
 
          var inventory_id = 0;
          var adjust_stat = 0;
          var adjust_amount = adjust_qty * purchase_price;

          $.get("../AJAX/Adjustment/editAdjustDetail.php", {
            adjust_qty: adjust_qty, 
            purchase_price: purchase_price,
            selling_price: selling_price,
            mnf_date: mnf_date,
            exp_date:exp_date,
            inventory_id: inventory_id,
            variation_id: variation_id,
            rack_id: rack_id, 
            adjust_amount: adjust_amount,
            product_id: product_id,
            adjust_header_id: adjust_header_id,
            adjust_detail_id: adjust_detail_id
        }, function(){
            //refresh table
            LoadTable(adjust_header_id);

            $("#product_detail").css('display', 'none');
            $("#cmb_variation").val(1);
            $("#ids").val("0");
            $("#prod_qty").val("");
            $("#purchase_price").val("");
            $("#selling_price").val("");
            $("#mnf_date").val("");
            $("#exp_date").val("");
            $("#cmb_racks").val(1);

            //show add button
            $("#btn_add_adjust_detail").css('display', 'block');
            $("#btn_edit_adjust_detail").css('display', 'none');

            //get totals
            getFinalTotal(adjust_detail_id);
         
        });//pass data to insert
    });//edit adjust item


    // $("#tbl_adjust_detail").on("click", ".btn_adjustout_edit", function(){
    //     let row = $(this).closest('tr');
    //     let id = row.data('id');

    //     $("#hide_adjustout_id").val(id);

    //     $.get("../AJAX/Adjustment/getOneAdjustment.php", {
    //         adjust_id : id
    //     }, function(data){
    //         alert(data);
    //     });
    //     // alert("id = " + id);
    // });//edit adjust out

    $("#tbl_adjust_detail").on('click', '.btn_delete_adjust', function(){
        let row = $(this).closest('tr');
        let id = row.data('id');
        var adjust_header_id = $("#hide_header_id").val();

        $.get("../AJAX/Adjustment/deleteAdjustDetail.php", {
            adjust_detail_id: id
        }, function(data){
            // alert(data);
            //refresh table
            LoadTable(adjust_header_id);
            getFinalTotal(adjust_header_id);
        });
    });//delete adjust 

    //======================== Submit Adjustment =======================//
    $("#btn_submit_adjustment").click(function(){
        $("#adjust_detail_modal").modal('toggle');

        var rowCount = $("#tbl_adjust_detail tr").length - 1;

        var total = 0;
        var totalItems = 0;
        $("#tbl_adjust_detail tr").each(function(){
            var price = $(this).find("td").eq(6).html();
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

        var adjustHeaderID = $("#hide_header_id").val();
        $("#hide_adjustheader_id").val(adjustHeaderID);
    });//open submit modal

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
    //=================== prod qty validate =====================//
    $("#prod_qty").change(function(){
        var input = parseFloat($(this).val());
        if(input <= 0)
        {
            $("#qty_warning").css('display', 'block');
            $(this).css('border-color', 'firebrick');
        }//minus or zero
        else
        {
            $("#qty_warning").css('display', 'none');
            $(this).css('border-color', 'forestgreen');
        }//else
    });//qty changed

    $("#purchase_price").change(function(){
        var input = parseFloat($(this).val());
        if(input < 0)
        {
            $("#pur_warning").css('display', 'block');
            $(this).css('border-color', 'firebrick');
        }//minus
        else
        {
            $("#pur_warning").css('display', 'none');
            $(this).css('border-color', 'forestgreen');
        }//else
    });//purchase price change

    $("#selling_price").change(function(){
        var input = parseFloat($(this).val());
        if(input < 0)
        {
            $("#sel_warning").css('display', 'block');
            $(this).css('border-color', 'firebrick');
        }//minus
        else
        {
            $("#sel_warning").css('display', 'none');
            $(this).css('border-color', 'forestgreen');
        }//else
    });//selling price change

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

//=============================== Adjustment OUT ===============================//
    //select products
    $("#cmb_inventory").select2({
        ajax:{
            url: '../AJAX/Adjustment/getProdSearch.php',
            dataType: 'json',
            delay: 250,
            data: function(params){
                var query = {
                    search: params.term,
                    adjustType: adjustType,
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

    $("#cmb_inventory").change(function(){
        var product_id = $(this).val();
        
        $.get("../AJAX/Adjustment/getProdVariation.php", {
            product_id: product_id
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);

            var txt_data = "";
            txt_data += "<option value='0'> === Select Batch === </option>";

            $.each(obj, function (key, value) { 
                var batch_id = value['BatchID'];
                var available_qty = parseFloat(value['CurrentQty']);
                var purchase_price = value['PurchasePrice'];

                txt_data += "<option value='"+batch_id+"'>"+batch_id+" - Qty:"+available_qty+" - Purchase:"+purchase_price+"</option>";

            });//each

            // console.log(txt_data);
            $("#cmb_out_batch").html(txt_data);
            
        });//get prod variations

        // $.get("../AJAX/Adjustment/getProductVariation.php", {
        //     product_id: product_id
        // }, function(data){
        //     $("#tbl_variations").html(data);
        // });//get

    });//cmb changed

    $("#tbl_variations").on('click', 'tr', function(){
        var adjust_header_id = $("#hide_header_id").val();

        var currentRow = $(this).closest('tr');
        var row_id = currentRow.find('td').eq(0).html();

        $("#hide_price_history_id").val(row_id);

        $.get("../AJAX/Adjustment/getAvailabelQty.php", {
            price_history_id:row_id,
            adjust_header_id: adjust_header_id
        }, function(data){
            // console.log(data);
            $("#div_available_qty").html(data);
        });//get availabel qty

    });

  

//==== testing
    $("#btn_test").click(function(){
        let label = " ";

        label = "<div class='bg-info p-2 mx-1'>";
        label += "pastha";
        label += "</div>";

        for (let i = 0; i < 4; i++) {
            $("#div_variations").append(label);
        }
    });//test

    
    $("#btn_add_adjust_out").click(function(){
        var adjust_header_id = $("#hide_header_id").val();
        var product_id = $("#cmb_inventory").val();
        var price_history_id = $("#hide_price_history_id").val();
        var adjust_out_qty = $("#adjust_out_qty").val();
        var out_batch_id = $("#cmb_out_batch").val();

        $.get("../AJAX/Adjustment/setAdjustOut.php", {
            adjust_header_id:adjust_header_id,
            product_id: product_id,
            price_history_id: price_history_id,
            adjust_out_qty: adjust_out_qty,
            out_batch_id: out_batch_id
        }, function(data){

            //refresh table
            LoadTable(adjust_header_id);

            //get totals
            getFinalTotal(adjust_header_id);

            let div_text = "<input type='hidden' id='hide_available_qty' value='0'>";
            $("#div_available_qty").html(div_text);
            $("#adjust_out_qty").val("");
            $("#tbl_variations").empty();
            // $("#cmb_inventory").empty().trigger('change');
            // $("#adjust_out_qty").val("");

        });//set adjust out
    });//add adjustment out

    //validate adjust qty
    $("#adjust_out_qty").change(function(){
        var value = parseFloat($(this).val());
        var available_qty = $("#hide_available_qty").val();
        if(value <= 0 || value > available_qty)
        {
            $("#adjust_out_warning").css('display', 'block');
            $(this).css('border-color', 'red');
            $(this).val("");
        }//not valid input
        else
        {
            $("#adjust_out_warning").css('display', 'none');
            $(this).css('border-color', 'lime');
        }//valid value
    });

//======================== Functions ============================//
    function LoadVariations(product_id)
    {
        $.get("../AJAX/Products/getCmbVariation.php", {
            product_id: product_id
        }, function(data){
            $("#cmb_variation").html(data);
        });//get data for cmb
    }//load cmb variations

    function LoadTable(adjust_header_id)
    {
        $.get("../AJAX/Adjustment/getAdjustDetail.php", {
            adjust_header_id: adjust_header_id
        }, function(data){
            
            $("#tbl_adjust_detail").html(data);
        });//get data for cmb    
    }//load table

    function getFinalTotal(adjust_header_id)
    {
        var rowCount = $("#tbl_adjust_detail tr").length - 1;

        var total = 0;
        var totalItems = 0;

        $.get("../AJAX/Adjustment/getAdjustment.php", {
            adjust_header_id : adjust_header_id
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);

            $.each(obj, function (key, value) { 
                total += parseFloat(value['AdjustProdAmount']);
                totalItems += parseFloat(value['AdjustProdQty']);
            });

            //assign in to totalrowCount
            $("h4#sub_row_count").text(rowCount);
            $("h4#sub_item_count").text(totalItems);
            $("h4#sub_purchase_price").text(total);

        });
        
    }//get final total
});//jquery adjustment