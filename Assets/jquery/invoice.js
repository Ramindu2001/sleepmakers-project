$(document).ready(function(){
    //initialize
    $("#barcode_input").focus();

    $("#item_line_discount").attr('disabled', true);
    $("#inv_line_discount").attr('disabled', true);
    
    $('.left-sidebar').css("margin-left","-270px");
    $('#headerCollapse2').css("display","block");
    $('#headerCollapse3').css("display","none");

    //hide return field
    $("#div_return_invoice").css('display', 'none');                                                                                                                                                       

    $("#close_edititem_modal").click(function(){
        $("#modal_edit_item").modal('toggle');
    });//close edit item modal

    
    //search barcode
    $("#barcode_input").keyup(function(event){
        //enter key pressed
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13')
        {
            var txt_input = $(this).val();

            $.get("../AJAX/Invoice/getSearchStat.php", {
                txt_search: txt_input
            }, function(data){
                console.log("Search Stat - " + data);
                
                switch (data) {
                    case '1':
                        $.get("../AJAX/Invoice/getOneSearch.php", {
                            txt_search: txt_input
                        }, function(ph_data){
                            console.log(ph_data);
                            
                            const arrData = JSON.parse(ph_data);
                            var pricehistory_id = arrData['pricehistory_id'];
                            var sell_price = arrData['sell_price'];

                            $("#hide_pricehistory_id").val(pricehistory_id);
                            $("#hide_sell_price").val(sell_price);
                            $("#sell_amount").val(sell_price);

                            $.get("../AJAX/Invoice/getItemDetails.php", {
                                pricehistory_id: pricehistory_id
                            }, function(data){
                                $("#div_item_info").html(data);

                                $("#modal_add_qty").modal('toggle');

                                $("#item_qty").focus();
                                $("#item_qty").val('1');
                                $("#item_qty").select();
                            });//get item into qty
                        });//get one search
                    break;

                    case '2':
                        $.get("../AJAX/Invoice/getMoreSearch.php", {
                            txt_search: txt_input
                        }, function(data){
                            $("#tbl_more_search").html(data);
                            $("#modal_add_item").modal('toggle');
                        });//get one search
                    break;

                    case '3':
                        $.get("../AJAX/Invoice/getOneSearch.php", {
                            txt_search: txt_input
                        }, function(ph_data){
                            const arrData = JSON.parse(ph_data);
                            var pricehistory_id = arrData['pricehistory_id'];
                            var sell_price = arrData['sell_price'];

                            $("#hide_pricehistory_id").val(pricehistory_id);
                            $("#hide_sell_price").val(sell_price);
                            $("#sell_amount").val(sell_price);

                            $.get("../AJAX/Invoice/getItemDetails.php", {
                                pricehistory_id: pricehistory_id
                            }, function(data){
                                $("#div_item_info").html(data);

                                $("#modal_add_qty").modal('toggle');

                                $("#item_qty").focus();
                                $("#item_qty").val('1');
                                $("#item_qty").select();
                            });//get item into qty
                        });//get one search
                    break;

                    case '4':
                        $.get("../AJAX/Invoice/getMoreSearch.php", {
                            txt_search: txt_input
                        }, function(data){
                            $("#tbl_more_search").html(data);
                            $("#modal_add_item").modal('toggle');
                        });//get one search
                    break;

                    case '5':
                        $("#modal_add_item").modal('toggle');
                    break;
                }//switch
            });//get search stat

        }//enter key pressed
    });//barcode key up

    $("#item_search_modal").keyup(function(){
        var txt_input = $(this).val();
        $.get("../AJAX/Invoice/getMoreSearch.php", {
            txt_search: txt_input
        }, function(data){
            $("#tbl_more_search").html(data);
        });//get one search
    });//search items in modal

    //multiselect table clicked
    $("#tbl_more_search").on('click', 'tr', function(){
        var currentRow = $(this).closest('tr');
        var row_id = currentRow.find("td").eq(0).html();
        var sell_price = currentRow.find("td").eq(1).html();

        $("#hide_pricehistory_id").val(row_id);
        $("#hide_sell_price").val(sell_price);
        $("#sell_amount").val(sell_price);


        $.get("../AJAX/Invoice/getItemDetails.php", {
            pricehistory_id: row_id
        }, function(data){
            $("#div_item_info").html(data);
        });

        $("#modal_add_item").modal('hide');
        $("#modal_add_qty").modal('toggle');

        $("#item_qty").focus();
        $("#item_qty").val('1');
        $("#item_qty").select();
    });

    $("#modal_add_qty").on('shown.bs.modal', function(){
        let progress = 0;
        var duration = parseFloat($("#item_add_duration").val());
        
        var progress_bar_time = duration * 10;
        var item_add_time = duration * 1000; //in miliseconds

        $("#progress-bar").css('width', "0%");


        let interval = setInterval(() => {
            if(progress >= 100)
            {
                progress = 100;
                clearInterval(interval);
            }
            else
            {
                progress += 1;
                $("#progress-bar").css('width', progress + "%");
            }
        }, progress_bar_time);//for 2 second -> 2000 = 20 * 100

        //check timer checkbox
        if($("#chk_auto_add").is(':checked'))
        {
            setTimeout(() => {
                addItems();
    
                setTimeout(() => {
                    getTotals();
                }, 100);
    
            }, item_add_time);
        }//timer on
 
    });//modal get focus

//======================= Add Item ========================//
    $("#btn_add_item").click(function(){
        addItems();
    });//add items

    //enter key press
    $("#sell_amount").keyup(function(event){
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13')
        {
            addItems();
        }//enter key press
    });//sell amount key up

    //add grid items qty
    $("#btn_minus_qty").click(function(){
        var input_qty = parseFloat($("#item_qty").val());

        if(input_qty < 0)
        {
            $("#item_qty").val('1');
        }
        else
        {
            var new_qty = input_qty - 1;
            $("#item_qty").val(new_qty);
        }
    });//minus

    //add grid items qty
    $("#btn_add_qty").click(function(){
        var input_qty = parseFloat($("#item_qty").val());

        var new_qty = input_qty + 1;

        $("#item_qty").val(new_qty);
    });//minus

//====================== Edit items =======================//
    $("#tbl_cart").on('click', ".tbl_cart_row", function(){
        let row = $(this).closest('tr');
        let id = row.data('id');

        $.get('../AJAX/Invoice/getCartEdit.php', {
            sell_id:id
        }, function(data){
            $("#modal_edit_item").modal('toggle');

            const obj = JSON.parse(data);

            var prod_image = obj[0]['ProdImage'];
            var prod_detail = "Barcode: <b>" + obj[0]['Barcode'] + "</b><br>" + "Item: <b>" + obj[0]['ItemName'] + "</b><br> Avl Qty: <b>" + obj[0]['CurrentQty'] + "</b><br> Price: <b>"+obj[0]['SellingPrice'] +"</b><br> Unit Rate: <b>"+obj[0]['UnitConversion']+"</b>" ;

            var soldAmount = parseFloat(obj[0]['soldAmount']);
            var soldQty = parseFloat(obj[0]['sellQty']);
            var unit_price = soldAmount / soldQty;

            //check the discount amount
            var valPercDisc = parseFloat(obj[0]['prodDiscount']);
            var valFlatDisc = parseFloat(obj[0]['itemWiseDiscount']);

            if(valPercDisc != 0)
            {                
                $("#item_discount").val(valPercDisc);
                $("#rdb_percent_discount").prop("checked", true);
            }

            if(valFlatDisc != 0)
            {                
                $("#item_discount").val(valFlatDisc);                
                $("#rdb_line_discount").prop("checked", true);
            }

            //add items
            if(prod_image == null)
            {
                $("#img_cart_edit").attr("src", "../Assets/Images/icons/product.png");
            }
            else
            {
                $("#img_cart_edit").attr("src", "../Assets/Images/prod_images/" + prod_image);
            }

            $("#hide_selldetail_id").val(obj[0]['SDID']);

            $("#p_cart_edit_details").html(prod_detail);

            $("#edit_cart_qty").val(obj[0]['sellQty']);

            $("#item_unit_price").val(unit_price);

            $("h5#discounted_price").text("Amount: " + obj[0]['soldAmount']);

            $("#item_current_qty").val(obj[0]['CurrentQty']);

            $("#hide_sellamount").val(obj[0]['sellAmount']);

            $("#item_conversion_rate").val(obj[0]['UnitConversion']);

            $("#item_percent_discount").val(obj[0]['prodDiscount']);

            $("#item_line_discount").val(obj[0]['itemWiseDiscount']);


        });
    });//cart row click

    $("#tbl_cart").on('click', '.cart_row_delete', function(){
        let row = $(this).closest('tr');
        let id = row.data('id');
        var header_id = $('#hide_header_id').val();
        var tmp_bill_no = $('#hide_tmp_no').val();

        $.get("../AJAX/Invoice/deleteSellDetail.php", {
            selldetail_id: id
        }, function(){
            //load table
            loadCartTable(tmp_bill_no);

            //get total
            setTimeout(() => {
                getTotals();
            }, 100);
        });

    });//cart row delte

    //sell qty change
    $("#edit_cart_qty").keyup(function(){
        var sell_qty = parseFloat($(this).val());
        var item_price = parseFloat($("#item_unit_price").val());
        var current_qty = parseFloat($("#item_current_qty").val());
        var conversion_rate = parseFloat($("#item_conversion_rate").val());
        var selldetail_id = $('#hide_selldetail_id').val();

        var unit_sell_qty = sell_qty / conversion_rate;

        // if(sell_qty < 0 || isNaN(sell_qty) || unit_sell_qty>current_qty)
        // {
        //     $("#item_qty_warning").css('display', 'block');
        //     $(this).css('border-color', 'red');
        //     $(this).val('');
        //     $("#btn_edit_item").attr('disabled', true);
        //     return;
        // }
        // else
        // {
        //     $("#btn_edit_item").attr('disabled', false);
        //     $("#item_qty_warning").css('display', 'none');
        //     $(this).css('border-color', 'lime');

        //     var total_amount = unit_sell_qty * item_price;
        //     $("h5#discounted_price").text("Amount: " + total_amount);
        //     $("#hide_sellamount").val(total_amount);
        // }

        //get available qty
        var price_id = $("#hide_pricehistory_id").val();
        // alert("sell detail - " + selldetail_id);
        $.get("../AJAX/Invoice/getAvailableQty.php", {
            selldetail_id:selldetail_id
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);
            var qty_stat = obj['qty_stat'];
            var avl_qty = parseFloat(obj['avl_qty']);
            // alert("stat = " + qty_stat);

            if(qty_stat == 1)
            {
                if(sell_qty <= avl_qty && sell_qty > 0)
                {
                    $("#btn_edit_item").attr('disabled', false);
                    $("#item_qty_warning").css('display', 'none');
                    $("#edit_cart_qty").css('border-color', 'lime');
                }//has qty
                else
                {
                    $("#item_qty_warning").css('display', 'block');
                    $("#edit_cart_qty").css('border-color', 'red');
                    $("#edit_cart_qty").val('');
                    $("#btn_edit_item").attr('disabled', true);
                    return;
                }//no available qty
            }
            else
            {
                if(sell_qty > 0)
                {
                    $("#btn_edit_item").attr('disabled', false);
                    $("#item_qty_warning").css('display', 'none');
                    $("#edit_cart_qty").css('border-color', 'lime');
                }//greater than 0
                else
                {
                    $("#item_qty_warning").css('display', 'block');
                    $("#edit_cart_qty").css('border-color', 'red');
                    $("#edit_cart_qty").val('');
                    $("#btn_edit_item").attr('disabled', true);
                    return;
                }//less than 0 
            }
        });

    });//edit cart qty

    $("#btn_edit_item").click(function () {
        var item_qty = $("#edit_cart_qty").val();
        var discount_amount = $("#item_discount").val();
        var percent_discount = $("#item_percent_discount").val();
        var direct_discount = $("#item_line_discount").val();
        var selldetail_id = $('#hide_selldetail_id').val();
        var sell_amount = $("#hide_sellamount").val();
        var header_id = $("#hide_header_id").val(); // Not used here
        var tmp_bill_no = $("#hide_tmp_no").val();
    
        // First AJAX call: Edit item details
        $.get("../AJAX/Invoice/editSellDetail.php", {
            selldetail_id: selldetail_id,
            item_qty: item_qty,
            discount_amount: discount_amount,
            sell_amount: sell_amount,
            percent_discount: percent_discount,
            direct_discount: direct_discount
        })
        .done(function () {
            $("#modal_edit_item").modal('hide');
    
            // Second AJAX call: Update cart table
            $.get("../AJAX/Invoice/getCartTable.php", {
                tmp_bill_no: tmp_bill_no
            })
            .done(function (data) {
                $("#tbl_cart").html(data);
                getTotals(); // Call getTotals directly after cart update
            })
            .fail(function () {
                alert("Failed to refresh cart table.");
            });
    
        })
        .fail(function () {
            alert("Failed to edit item details.");
        });
    });
    //edit item

//========================= Grid Search =========================//
    $("#search").keyup(function(){
        var txt_input = $(this).val();
        let tbl_data = "";

        $.get("../AJAX/Invoice/getGridSearch.php", {
            txt_input: txt_input
        }, function(data){
            const obj = JSON.parse(data);
           
            $.each(obj, function(key, value){
                var id = value.PHID;
                var avl_qty = parseFloat(value.CurrentQty);
                var barcode = value.Barcode;
                var item_name = value.ItemName;
                var item_price = value.SellingPrice;

                var image_name = "";
                var prod_image = value.ProdImage;
                if(prod_image == null)
                {
                    image_name = "../Assets/Images/icons/product.png";
                }
                else
                {
                    image_name = "../Assets/Images/prod_images/" + prod_image;
                }

                tbl_data += "<div class='col-md-3 card shadow border-primary div_item' data-id='"+id+"'>";
                // tbl_data += "<p class='m-1 p-1 bg-primary text-light text-center rounded'><b>"+ avl_qty +"</b></p>";
                tbl_data += "<img src='"+image_name+"' class='img_grid_item'>";
                tbl_data += "<p class='m-1 p-1 text-center' style='overflow:hidden;'><small>" + barcode + "<br>" + item_name + "</small><br>";
                tbl_data += "<b>Rs: "+ item_price +"</b></p>";
                tbl_data += " ";
                tbl_data += "</div>";
            });//each

            $("#div_item_grid").html(tbl_data);
        });//get grid
    });//search in grid

    $("#div_item_grid").on('click', '.div_item', function(){
        let div = $(this).closest('div');
        let id = div.data('id');
        let item_id = div.data('item');

        $.get("../AJAX/Invoice/getOneGrid.php", {
            pricehistory_id: id,
            item_id: item_id
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);

            var stock_type = obj['stock_type'];
            var price_id = obj['price_id'];
            var barcode = obj['barcode'];
            var item_name = obj['item_name'];
            var item_image = obj['item_image'];
            var current_qty = obj['current_qty'];
            var selling_price = obj['selling_price'];
            var purchase_unit = obj['purchase_unit'];
            var selling_unit = obj['selling_unit'];
            var unit_conversion_rate = obj['unit_conversion_rate'];

            // alert("item_id = " + item_id);

            $("#hide_pricehistory_id").val(price_id);
            $("#hide_item_id").val(item_id);

            $("#hide_sell_price").val(selling_price);
            $("#sell_amount").val(selling_price);

            var item_desc = "";
            item_desc += "<div class='row'>";
            item_desc += "<div class='col-md-4'>";
            if(item_image == null)
            {
                item_desc += "<img src='../Assets/Images/icons/product.png' style='width:90%; height:auto;'>";
            }//no image
            else
            {
                item_desc += "<img src='../Assets/Images/prod_images/"+ item_image +"' style='width:100%; height:auto;'>"; 
            }//has image
            item_desc += "</div>";
            item_desc += "<div class='col-md-8'>";

            item_desc += "<p>";
            item_desc += "Barcode: "+ barcode +"<br>";
            item_desc += "Item Name: "+ item_name +"<br>";
            item_desc += "Current Qty: "+ current_qty +"<br>";
            item_desc += "Price: "+ selling_price +"<br>";
            item_desc +=  purchase_unit +" = "+selling_unit+" X "+unit_conversion_rate;
            item_desc += "</p>";

            item_desc += "</div>";
            item_desc += "</div>";

            $("#div_item_info").html(item_desc);

            $("#modal_add_qty").modal('toggle');

            $("#item_qty").focus();
            $("#item_qty").val('1');
            $("#item_qty").select();

        });//get one grid
    });//grid item click

    $(".cat").click(function(){
        var cat_id = $(this).data("id");
        let tbl_data = "";

        $.get("../AJAX/Invoice/getCategorySearch.php", {
            category_id: cat_id
        }, function(data){
            const obj = JSON.parse(data);
           
            $.each(obj, function(key, value){
                var id = value.PHID;
                var avl_qty = parseFloat(value.CurrentQty);
                var barcode = value.Barcode;
                var item_name = value.ItemName;
                var item_price = value.SellingPrice;

                var image_name = "";
                var prod_image = value.ProdImage;
                if(prod_image == null)
                {
                    image_name = "../Assets/Images/icons/product.png";
                }
                else
                {
                    image_name = "../Assets/Images/prod_images/" + prod_image;
                }

                tbl_data += "<div class='col-md-3 card shadow border-primary div_item' data-id='"+id+"'>";
                //tbl_data += "<p class='m-1 p-1 bg-primary text-light text-center rounded'><b>"+ avl_qty +"</b></p>";
                tbl_data += "<img src='"+image_name+"' class='img_grid_item'>";
                tbl_data += "<p class='m-1 p-1 text-center' style='overflow:hidden;'><small>" + barcode + "<br>" + item_name + "</small><br>";
                tbl_data += "<b>Rs: "+ item_price +"</b></p>";
                tbl_data += " ";
                tbl_data += "</div>";
            });//each

            $("#div_item_grid").html(tbl_data);

        });//get category search
    });

//==================== Radio Button Change =====================//
    $("#rdb_percent_discount").change(function(){
        
        var rdb_value = $(this).val();
        var sell_amount = $("#hide_sellamount").val();
        if(rdb_value == 'on')
        {
            $("h5#discounted_price").text("Amount: " + sell_amount);
            $("#item_percent_discount").attr('disabled', false);
            $("#item_line_discount").attr('disabled', true);
            $("#item_line_discount").val("");
        }
    });//check percent discount

    $("#rdb_line_discount").change(function(){
        var rdb_value = $(this).val();
        var sell_amount = $("#hide_sellamount").val();
        if($(this).is(":checked"))
        {
            $("h5#discounted_price").text("Amount: " + sell_amount);
            $("#item_percent_discount").attr('disabled', true);
            $("#item_line_discount").attr('disabled', false);
            $("#item_percent_discount").val("");
        }
    });//check line discount

    $("#item_percent_discount").keyup(function(){
        var item_price = parseFloat($("#item_unit_price").val());
        var item_percent = parseFloat($(this).val());
        var sell_qty = parseFloat($("#edit_cart_qty").val());
        var conversion_rate = 1;

        conversion_rate = conversion_rate==0 ? 1 : parseFloat($("#item_conversion_rate").val());

        var unit_price = item_price / conversion_rate;

        if(item_percent < 0 || item_percent>100)
        {
            $("#item_percent_warning").css('display', 'block');
            $(this).css('border-color', 'red');
            $(this).val('');
            return;
        }//invalid percent
        else
        {
            $("#item_percent_warning").css('display', 'none');
            $(this).css('border-color', 'lime');

            var unit_discount = (unit_price * (100 - item_percent)) / 100;
            
            var item_sell_price = sell_qty * unit_discount;

            if(isNaN(item_percent))
            {
                $("h5#discounted_price").text("Amount: " + (sell_qty * item_price));
                $("#item_discount").val('0');
            }
            else
            {
                $("h5#discounted_price").text("Amount: " + item_sell_price);
                var Item_discount = ((item_price * item_percent)/100) * sell_qty;
                $("#item_discount").val(Item_discount);
            }   
        }//valid input
    });//item percent discount

    $("#item_line_discount").keyup(function(){
        var item_price = parseFloat($("#item_unit_price").val());
        var item_line = parseFloat($(this).val());
        var sell_qty = parseFloat($("#edit_cart_qty").val());
        var conversion_rate = 1;

        conversion_rate = conversion_rate==0 ? 1 : parseFloat($("#item_conversion_rate").val());

        var unit_price = item_price / conversion_rate;

        if(item_line < 0 || item_line > item_price)
        {
            $("#item_line_warning").css('display', 'block');
            $(this).css('border-color', 'red');
            $(this).val('');
            $("#item_discount").val('0');
            return;
        }//invalid amount
        else
        {
            $("#item_line_warning").css('display', 'none');
            $(this).css('border-color', 'lime');

            var unit_discount = unit_price - item_line;

            $("h5#discounted_price").text("Amount: " + (sell_qty * unit_discount));
            $("#item_discount").val(sell_qty * item_line);
        }//valid input
    });//item line discount

//=========================== Discount ===========================//
$("#btn_open_discount").click(function(){
    $("#modal_invoice_discount").modal('toggle');
    var sell_header_id = $("#hide_sell_header_id").val();

    //get discount from table
    $.get("../AJAX/Invoice/getSellDiscount.php", {
        sell_header_id: sell_header_id
    }, function(data){
        // alert(data);
        const obj = JSON.parse(data);

        var sell_percent_discount = obj[0]['PercentDiscount']==null ? 0 : obj[0]['PercentDiscount'];
        var sell_fixed_discount = obj[0]['FixedDiscount']==null ? 0 : obj[0]['FixedDiscount'];
        var sell_discount = obj[0]['DiscountAmount']==null ? 0 : obj[0]['DiscountAmount'];
        sell_discount = parseFloat(sell_discount);

        $("#inv_percent_discount").val(sell_percent_discount);
        $("#inv_line_discount").val(sell_fixed_discount);

        var invoice_total = getCartTotal();
        var net_total = invoice_total - sell_discount;
        //show sell total
        var txt_sell = "<small>Gross Amount</small><br><b>Rs: "+invoice_total+"</b>";
        $("#p_gross_amount").html(txt_sell);

        //show discount total
        var txt_discount = "<small>Discount</small><br><b>"+sell_discount+"</b>";
        $("#p_inv_discount").html(txt_discount);

        //show net total
        var txt_total = "<small>Net Amount</small><br><b>Rs: "+ net_total +"</b>";
        $("#p_net_amount").html(txt_total);
    });//get discount
});//open discount

$("#btn_invoice_discount").click(function(){
    var sell_header_id = $("#hide_sell_header_id").val();
    var percent_discount = parseFloat($("#inv_percent_discount").val());
    var fixed_discount = parseFloat($("#inv_line_discount").val());

    var invoice_total = getCartTotal();
    var cart_row_count = getCartRowCount();

    var net_amount = 0;
    var sell_discount = 0;

    if(percent_discount > 0)
    {
        net_amount = (invoice_total * (100-percent_discount))/100;
        sell_discount = (invoice_total * percent_discount)/100;
    }//is percent

    if(fixed_discount > 0)
    {
        net_amount = invoice_total - fixed_discount;
        sell_discount = fixed_discount;
    }//fixed discount

    $.get("../AJAX/Invoice/editSellHeader.php", {
        row_count: cart_row_count,
        sub_total: invoice_total,
        percent_discount:percent_discount,
        fixed_discount: fixed_discount,
        sell_discount: sell_discount,
        net_total: net_amount,
        sell_header_id: sell_header_id
    }, function(data){
        //alert(data);
        $("#modal_invoice_discount").modal('hide');
    });//get data

});//add discount   

//=========================== Payment ============================//
$("#modal_payment").on('shown.bs.modal', function(){
    $("#cust_payment").focus();
    $("#cust_payment").select();
});//modal opened

$("#cmb_customer").select2({
    ajax:{
        url: '../AJAX/Invoice/getCustomers.php',
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
    minimumInputLength: 1,
    width: '100%',
    dropdownParent: $("#modal_payment")
});//get customer search

$("#cmb_customer").change(function(){
    var cust_id = $(this).val();
    var cust_text = $(this).text();
    $.get("../AJAX/Invoice/getCustDetail.php", {
        cust_id: cust_id
    }, function(data){
        const obj = JSON.parse(data);
        var cust_contact = obj[0].cust_contact;
        var cust_name = obj[0].cust_name;
        var max_credit = obj[0].max_credit;
        var cust_credit = obj[0].cust_credit;

        $("#hide_max_credit").val(max_credit);
        $("#hide_customer_credit").val(cust_credit);

        var txt = "<small>Customer</small><br><b>"+cust_contact+ " - " + cust_name +" - Credit: "+ cust_credit +"</b>";
        $("#p_customer").html(txt);

    });//get customer detail    
});//cmb changes

$("#cmb_salesman").change(function(){
    var sal_id = $(this).val();
    var sal_name = $(this).text();
    $.get("../AJAX/Invoice/getSalesman.php", {
        sal_id: sal_id
    }, function(data){
        const obj = JSON.parse(data);
        
        var sal_name = obj[0]['SalesmansName'];
        var txt = "<small>Salesman</small><br><b>"+ sal_name +"</b>";
        $("#p_salesman").html(txt);
    });//get salesman
    $("#p_salesman").text(sal_name);
});//cmb salesman change

//discounts
$("#rbd_inv_percent_discount").change(function(){
    var rdb_value = $(this).val();
    var sell_total = parseFloat($("#hide_sell_total").val());
    if(rdb_value == 'on')
    {
        $("#inv_percent_discount").attr('disabled', false);
        $("#inv_line_discount").attr('disabled', true);
        $("#inv_line_discount").val("");

        $("#hide_discounted_total").val(sell_total);

        //show discount total
        var txt_discount = "<small>Discount</small><br><b>Rs: 0.00</b>";
        $("#p_inv_discount").html(txt_discount);

        //show net total
        var txt_total = "<small>Net Amount</small><br><b>Rs: "+ sell_total +"</b>";
        $("#p_net_amount").html(txt_total);
    }
});//show percent discount

$("#rdb_inv_line_discount").change(function(){
    var rdb_value = $(this).val();
    var sell_total = parseFloat($("#hide_sell_total").val());
    if(rdb_value == 'on')
    {
        $("#inv_percent_discount").attr('disabled', true);
        $("#inv_line_discount").attr('disabled', false);
        $("#inv_percent_discount").val("");

        $("#hide_discounted_total").val(sell_total);
        //show discount total
        var txt_discount = "<small>Discount</small><br><b>Rs: 0.00</b>";
        $("#p_inv_discount").html(txt_discount);

        //show net total
        var txt_total = "<small>Net Amount</small><br><b>Rs: "+ sell_total +"</b>";
        $("#p_net_amount").html(txt_total);
    }
});//show percent discount

$("#inv_percent_discount").keyup(function(){
    //get cart table total
    var invoice_total = 0;
    $("#tbl_cart tr").each(function(){
        var price = $(this).find("td").eq(7).html();

        if(price != undefined)
        {
            invoice_total += parseFloat(price); 
        }//has value
    });//go through table

    $("#hide_sell_total").val(invoice_total);

    var inv_percent = parseFloat($(this).val());

    //validate input
    if(inv_percent < 0 || inv_percent >100)
    {
        $("#inv_percent_warning").css('display', 'block');
        $(this).css('border-color', 'red');
        $(this).val('');

        //show discount total
        var txt_discount = "<small>Discount</small><br><b>Rs: 0.00</b>";
        $("#p_inv_discount").html(txt_discount);

        //show net total
        var txt_total = "<small>Net Amount</small><br><b>Rs: "+ invoice_total +"</b>";
        $("#p_net_amount").html(txt_total);
        return;
    }//invalid input
    else
    {
        $("#inv_percent_warning").css('display', 'none');
        $(this).css('border-color', 'lime');

        //calculate percent total
        var inv_discount = (invoice_total * inv_percent) / 100;
        var discounted_amount = (invoice_total * (100-inv_percent))/100;

        //show sell total
        var txt_sell = "<small>Gross Amount</small><br><b>Rs: "+invoice_total+"</b>";
        $("#p_gross_amount").html(txt_sell);

        if(isNaN(inv_percent))
        {
            //show discount total
            var txt_discount = "<small>Discount</small><br><b>Rs: 0.00</b>";
            $("#p_inv_discount").html(txt_discount);

            //show net total
            var txt_total = "<small>Net Amount</small><br><b>Rs: "+ invoice_total +"</b>";
            $("#p_net_amount").html(txt_total);
        }//is not a number
        else
        {
            $("#hide_discounted_total").val(discounted_amount);
            $("#hide_invoice_discount").val(inv_discount);
            //show discount total
            var txt_discount = "<small>Discount</small><br><b>Rs: "+ inv_discount +"</b>";
            $("#p_inv_discount").html(txt_discount);

            //show net total
            var txt_total = "<small>Net Amount</small><br><b>Rs: "+ discounted_amount +"</b>";
            $("#p_net_amount").html(txt_total);
        }//is a number
    }//else
});//invoice percent discount

$("#inv_line_discount").keyup(function(){
    //get cart table total
    var invoice_total = 0;
    $("#tbl_cart tr").each(function(){
        var price = $(this).find("td").eq(7).html();

        if(price != undefined)
        {
            invoice_total += parseFloat(price); 
        }//has value

    });//go through table

    $("#hide_sell_total").val(invoice_total);

    var inv_line = parseFloat($(this).val());
    var sell_total = parseFloat($("#hide_sell_total").val());

    //validate input
    if(inv_line < 0 || inv_line > invoice_total)
    {
        $("#inv_line_warning").css('display', 'block');
        $(this).css('border-color', 'red');
        $(this).val('');
        
        //show discount total
        var txt_discount = "<small>Discount</small><br><b>Rs: 0.00</b>";
        $("#p_inv_discount").html(txt_discount);

        //show net total
        var txt_total = "<small>Net Amount</small><br><b>Rs: "+ invoice_total +"</b>";
        $("#p_net_amount").html(txt_total);

        return;
    }//invalid amount
    else
    {
        $("#inv_line_warning").css('display', 'none');
        $(this).css('border-color', 'lime');

        if(isNaN(inv_line))
        {
            //show discount total
            var txt_discount = "<small>Discount</small><br><b>Rs: 0.00</b>";
            $("#p_inv_discount").html(txt_discount);

            //show net total
            var txt_total = "<small>Net Amount</small><br><b>Rs: "+ invoice_total +"</b>";
            $("#p_net_amount").html(txt_total);
        }//not a number
        else
        {
            var discounted_amount = invoice_total - inv_line;

            $("#hide_discounted_total").val(discounted_amount);
            $("#hide_invoice_discount").val(inv_line);

            //show discount total
            var txt_discount = "<small>Discount</small><br><b>Rs: "+ inv_line +"</b>";
            $("#p_inv_discount").html(txt_discount);

            //show net total
            var txt_total = "<small>Net Amount</small><br><b>Rs: "+ discounted_amount +"</b>";
            $("#p_net_amount").html(txt_total);

        }//is a number 
    }//valid amount 
});//invoice line discount

$('#cust_payment').on('focus', function(){
    $(this).select();
});//set focus to suct payment

//============================ Customer ==========================//
$("#btn_open_customers").click(function(){
    $("#customer_modal").modal('toggle');
});//open customer

$("#btn_save_customer").click(function(){
    var txt_stat = 0;
    var cust_name = $("#cust_name").val()=="" ? txt_stat=1 : $("#cust_name").val();
    var cust_contact = $("#cust_contact").val()=="" ? txt_stat=1 : $("#cust_contact").val();
    var cust_address = $("#cust_address").val()=="" ? txt_stat=1 : $("#cust_address").val();
    var max_credit = $("#max_credit").val()=="" ? txt_stat=1 : $("#max_credit").val();
    var payment_term = $("#payment_term").val()=="" ? txt_stat=1 : $("#payment_term").val();

    var cust_stat = $("#cust_stat").is(":checked") ? 1 : 0;

    if(txt_stat == 1)
    {
        $("#p_cust_message").css('display', 'block');
        $("#p_cust_message").html("Please fill the required <b>fields</b>");
        return;
    }//empty field
    else
    {

    }//not empty

    $.get("../AJAX/Invoice/setCustomer.php", {
        cust_name: cust_name,
        cust_contact: cust_contact,
        cust_address: cust_address,
        max_credit: max_credit,
        payment_term: payment_term,
        cust_stat: cust_stat
    }, function(data){
        $("#customer_modal").modal('hide');

        $("#cust_name").val("");
        $("#cust_contact").val("");
        $("#cust_address").val("");
        $("#max_credit").val("");
        $("#payment_term").val("");
    });
});//save customer

//========================= Hold Invoice =========================//
$("#btn_open_hold").click(function(){
    $("#modal_hold_invoice").modal('toggle');
});//open hold invoice

$("#tbl_hold_sale").on('click', '.btn_open_hold_sale', function(){
    let row = $(this).closest('tr');
    let id = row.data('id');

    $.get("../AJAX/Invoice/getHoldSale.php", {
        sale_header_id: id
    }, function(data){
        const obj = JSON.parse(data);

        var header_id = obj[0]['SHID'];
        var tmp_bill_no = obj[0]['tmp_bill_no'];

        //top header_id
        $("#hide_sell_header_id").val(header_id);
        //form header id
        $("#hide_header_id").val(header_id);
        //payment
        $("#hide_sales_header").val(header_id);

        $("#hide_tmp_no").val(tmp_bill_no);

        $("h5#h5_bill_no").text("Bill No: " + tmp_bill_no);

        //hide modal
        $("#modal_hold_invoice").modal('hide');
        //reload table
        loadCartTable(tmp_bill_no);

    });
});//table hold click

$("#search_invoice").keyup(function(){
    var txt_input = $(this).val();

    $.get("../AJAX/Invoice/getAllHoldSale.php", {
        txt_input: txt_input
    }, function(data){
        const obj = JSON.parse(data);
        var row_count = 0;
        var tmp_bill_no = "";
        var header_id = 0;

        var tbl_data = "";
        tbl_data += "<tr>";
        tbl_data += "<th>No</th>";
        tbl_data += "<th>Bill No</th>";
        tbl_data += "<th>Action</th>";
        tbl_data += "</tr>";

        $.each(obj, function(i, item){
            tmp_bill_no = item.tmp_bill_no;
            header_id = item.SHID;

            row_count += 1;
            tbl_data += "<tr data-id='"+header_id+"'>";
            tbl_data += "<td>"+row_count+"</td>";
            tbl_data += "<td>"+tmp_bill_no+"</td>";
            tbl_data += "<td><button class='btn btn-primary btn_open_hold_sale'>Open</button></td>";
            tbl_data += "</tr>";
        });

        $("#tbl_hold_sale").html(tbl_data);

    });

});//search hold invoice

//========================== Service ===========================//
$("#btn_open_service").click(function(){
    $("#modal_service").modal('toggle');

    $("#service_charge").val("");
});

$("#btn_add_service").click(function(){
    var service_product_id = $("#cmb_services").val();
    var service_charge = parseFloat($("#service_charge").val());
    var tmp_bill_no = $("#hide_tmp_no").val();

    $.get("../AJAX/Invoice/setService.php", {
        service_product_id: service_product_id,
        service_charge: service_charge,
        tmp_bill_no: tmp_bill_no
    }, function(data){
        $("#modal_service").modal('hide');
        //load table
        loadCartTable(tmp_bill_no);

        //get totals
        setTimeout(() => {
            getTotals();
        }, 100);
    });
    
});

$("#service_charge").keyup(function(){
    var service_charge = $(this).val();

    if( isNaN(service_charge) || service_charge == undefined || service_charge <= 0)
    {
        $("#service_charge_warning").css('display', 'block');
        $(this).css('border-color', 'red');
        $(this).focus();
        $(this).val("");
    }//invalid input
    else
    {
        $("#service_charge_warning").css('display', 'none');
        $(this).css('border-color', 'lime');
    }//valid input
});//service charge

//========================= Expense =========================//
$("#btn_open_expense").click(function(){
    $("#invoice_expense_modal").modal('toggle');

    var dt = new Date();
    var date_time = dt.getFullYear() +"-"+ (dt.getMonth()+1) +"-"+ dt.getDate() +" "+ dt.getHours()+":"+dt.getMinutes()+":"+dt.getSeconds();

    $("#current_date").text(date_time);

    $("#p_expense_message").text("");
    $("#p_expense_message").css('display', 'none');
    $("#p_expense_message").css('color', 'lime');

});//open expense

$("#btn_add_expense").click(function(){
    var expense_category_id = $("#cmb_expense_category").val();
    var expense_amount = $("#expense_amount").val();
    var expense_reason = $("#expense_reason").val();
    var expense_paymethod = $("#cmb_exp_paymethod").val();

    $.get("../AJAX/Invoice/setExpenses.php", {
        expense_category_id: expense_category_id,
        expense_amount: expense_amount,
        expense_reason: expense_reason,
        expense_paymethod : expense_paymethod
    }, function(data){

        $("#p_expense_message").text("Expense added successfully.");
        $("#p_expense_message").css('display', 'block');
        $("#p_expense_message").css('color', 'lime');

        $("#expense_amount").val("");
        $("#expense_reason").val("");
    });

});//add expense

//============================== Receipt Print ==========================//
$("#btn_open_receiptprint").click(function(){
    $("#receipt_print_modal").modal('toggle');
});//open receipt print modal

$("#search_issued_invoice").keyup(function(){
    var invoice_no = $(this).val();
    
    $.get("../AJAX/Invoice/getIssuedInvoice.php", {
        invoice_no: invoice_no
    }, function(data){
        // alert(data);
        $("#tbl_receipt_print").html(data);
    });

});//search invoice

//================================ multipay =================================//
$("#btn_open_multipay").click(function(){
    $("#multipay_modal").modal('toggle');
    
    var row_count = 0;
    var total = 0;
    var totalItems = 0;
    $("#tbl_cart tr").each(function(){
        row_count += 1;
        var price = $(this).find("td").eq(7).html();
        var itemCount = $(this).find("td").eq(1).html();

        if(price != undefined) 
        {
            total += parseFloat(price); 
            totalItems += parseFloat(itemCount);
        }//has value   
    });//go through table

    $("#hide_sell_total").val(total);
    $("#p_sell_total").text("Invoice Total: " + total);

    //load multipay table
    loadMultipayTable();

});//multi pay

$("#btn_multi_pay").click(function(){
    var cust_payment = parseFloat($("#multipay_value").val());
    var discounted_total = parseFloat($("#hide_sell_total").val());
    var sell_header_id = $("#hide_sales_header").val();
    var paymethod_id = $('input[name="rdb_multi_paymethod"]:checked').val();

    var balance = discounted_total - cust_payment;

    //add to multipay
    $.get("../AJAX/Invoice/setMultipay.php", {
        cust_payment: cust_payment,
        discounted_total: discounted_total,
        sell_header_id: sell_header_id,
        paymethod_id: paymethod_id
    }, function(data){
        //load multi pay table
        loadMultipayTable();
    });
});//add multipay

//========================== Close Modals ==========================//
$("#close_payment_modal").click(function(){
    $("#modal_payment").modal('hide');
});

$("#close_hold_modal").click(function(){
    $("#modal_hold_invoice").modal('hide');
});

$("#close_service_modal").click(function(){
    $("#modal_service").modal('hide');
});

$("#close_reprint_modal").click(function(){
    $("#receipt_print_modal").modal('hide');
});

$("#close_sale_modal").click(function(){
    $("#today_sale_modal").modal('hide');
});

$("#close_discount_modal").click(function(){
    $("#modal_invoice_discount").modal('hide');
});

$("#close_qty_modal").click(function(){
    $("#modal_add_qty").modal('hide');
});

$("#close_item_modal").click(function(){
    $("#modal_add_item").modal('hide');
});

//========================== Functions ===========================//
function addItems()
{
    var counter_id = $("#hide_counter_id").val();
    var pricehistory_id = $("#hide_pricehistory_id").val();
    var sell_qty = parseFloat($("#item_qty").val());
    var sell_price = $("#hide_sell_price").val();
    var tmp_bill_no = $("#hide_tmp_no").val();
    var manual_price = $("#sell_amount").val();
    var header_id = 0;

    if(sell_qty > 0)
    {
        $.get("../AJAX/Invoice/setSellDetail.php", {
            pricehistory_id:pricehistory_id,
            sell_qty: sell_qty,
            sell_price: sell_price,
            tmp_bill_no: tmp_bill_no,
            manual_price: manual_price,
            counter_id: counter_id
        }, function(data){
            // alert(data);
            $("#hide_header_id").val(data);

            $("#modal_add_qty").modal('hide');

            loadCartTable(tmp_bill_no);

            $("#barcode_input").val("");
            $("#barcode_input").focus();
        });
    }//valid amount
    else
    {
        alert("Qty Warning...");
        return;
    }
}//add items

function loadCartTable(tmp_bill_no)
{
    $.get("../AJAX/Invoice/getCartTable.php", {
        tmp_bill_no: tmp_bill_no
    }, function(data){
        $("#tbl_cart").html(data);
        getTotals();
    });
}//load cart table

function getTotals()
{
    var row_count = 0;
    var total = 0;
    var totalItems = 0;
    $("#tbl_cart tr").each(function(){
        row_count += 1;
        var price = $(this).find("td").eq(7).html();
        var itemCount = $(this).find("td").eq(1).html();

        if(price != undefined)
        {
            total += parseFloat(price); 
            totalItems += parseFloat(itemCount);
        }//has value
    });//go through table

    $("#btn_open_payment").text("Pay: " + total.toFixed(2));
    let desc = "Row Count: " + row_count + "<br> Item Count: " + totalItems;
    $("#p_cart_detail").html(desc);
}//get totals

function getBalance()
{
    var header_id = $("#hide_sell_header_id").val();
    var invoice_total = parseFloat($("#hide_sell_total").val());
    var cust_payment = parseFloat($("#cust_payment").val());

    var balance = (cust_payment - invoice_total);
    var multipay_total = 0;

    $.get("../AJAX/Invoice/getMultipay.php", {
        sale_header_id: header_id
    }, function(data){
        const obj = JSON.parse(data);

        $.each(obj, function(i, item){
            var paymethod = item.PaymethodName;
            var amount = item.paidAmount;
            var paymethod_id = item.MPID;
            
            multipay_total += parseFloat(amount);
        });//iterate

        var pending_amount = invoice_total - multipay_total;

        let details = "Paid Amount: <b>" + multipay_total + "</b><br> Total Amount: <b>" + invoice_total + "</b><br>Pending Amount: <b>" + pending_amount + "</b>";

        $("#p_multipay_details").html(details);
        $("#hide_pending_amount").val(pending_amount);

    });//get multipay
}//get balance

function loadMultipayTable()
{
    var header_id = $("#hide_sell_header_id").val();
    var bill_total = parseFloat($("#hide_sell_total").val());
    var payment_total = 0;

    $.get("../AJAX/Invoice/getMultipay.php", {
        sale_header_id: header_id
    }, function(data){
        const obj = JSON.parse(data);

        let tbl_data = "";

        tbl_data += "<tr>";
        tbl_data += "<th>Paymethod</th>";
        tbl_data += "<th>Amount</th>";
        tbl_data += "<th>Action</th>";
        tbl_data += "</tr>";

        $.each(obj, function(i, item){
            var paymethod = item.PaymethodName;
            var amount = item.paidAmount;
            var paymethod_id = item.MPID;
            payment_total += parseFloat(item.paidAmount);

            tbl_data += "<tr data-id='"+paymethod_id+"'>";
            tbl_data += "<td>"+ paymethod +"</td>";
            tbl_data += "<td>"+ amount +"</td>";
            tbl_data += "<td><button type='button' class='btn btn_delete_multipay'>";
            tbl_data += "<img src='../Assets/Images/icons/close.png' style='height:25px; width:auto;'>";
            tbl_data += "</td>";
            tbl_data += "</tr>";
        });//iterate

        $("#tbl_multipay_modal").html(tbl_data);

        var balance = bill_total - payment_total;

        let show_balance = "Bill Total: " + bill_total + "<br> Paid Total: " + payment_total + "<br> Balance: " + balance;
        $("#p_multipay_total").html(show_balance);

        getBalance();
    });

}//load multi pay table

function getCartTotal()
{
    var row_count = 0;
    var cart_total = 0;
    var totalItems = 0;
    $("#tbl_cart tr").each(function(){
        row_count += 1;
        var price = $(this).find("td").eq(7).html();
        var itemCount = $(this).find("td").eq(1).html();

        if(price != undefined)
        {
            cart_total += parseFloat(price); 
            totalItems += parseFloat(itemCount);
        }//has value
    });//go through table

    return cart_total;
}//get cart total

function getCartRowCount()
{
    var row_count = 0;
    var cart_total = 0;
    var totalItems = 0;
    $("#tbl_cart tr").each(function(){
        row_count += 1;
        var price = $(this).find("td").eq(7).html();
        var itemCount = $(this).find("td").eq(1).html();

        if(price != undefined)
        {
            cart_total += parseFloat(price); 
            totalItems += parseFloat(itemCount);
        }//has value
    });//go through table

    return row_count;
}//get cart row count

});//invoice jQuery