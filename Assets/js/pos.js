
$(document).ready(function ()
{
    
    $("#add-customer").click(function(){
        $("#customer_modal").modal("toggle")
    });
    $("#customerName").keyup(function(){
        $(this).val($(this).val().replace(/[^a-z0-9]/, ''));
        var length = $(this).val();
        var lengthx = $(this).val().length;
        var maxlength = 25;
        if(lengthx>maxlength)
        {
            $(this).val(length.substring(0, maxlength));
        }
        if($(this).val()=="")
        {
            $(this).val("");
        }
    })
    $("#customerNumber").keyup(function(){
        $(this).val($(this).val().replace(/[^0-9+]/, ''));
        var length = $(this).val();
        var lengthx = $(this).val().length;
        var maxlength = 12;
        if(lengthx>maxlength)
        {
            $(this).val(length.substring(0, maxlength));
        }
    })
    $("#customerAddres").keyup(function(){
        $(this).val($(this).val().replace(/[^a-z0-9,.]/, ''));
        var length = $(this).val();
        var lengthx = $(this).val().length;
        var maxlength = 25;
        if(lengthx>maxlength)
        {
            $(this).val(length.substring(0, maxlength));
        }
        if($(this).val()=="")
        {
            $(this).val("");
        }
    })
    $("#barcode-input").focus();
    $("#total_disc").prop('disabled', true);
    var $btns = $('.cat').click(function() {
        if (this.id == 'all') {
            $('#product > a').fadeIn(450);
        } else {
            var $el = $('.' + this.id).fadeIn(450);
            $('#product > a').not($el).fadeOut(450);
        }
        $btns.removeClass('active');
        $(this).addClass('active');
        });
        $("body").on("keyup","input[type=text]",function(){
            // alert($(this).val());
            if($(this).attr('id')=="search")
            {

            }
            else if($(this).attr('id')=="barcode-input")
                {

                }
            else if($(this).attr('id')=="customerName")
                {

                }
            else
            {
                if($(this).val()=="")
                {
                    $(this).val("0");
                }
                else
                {
                    var val=parseFloat($(this).val().replace(/[^0-9.]/,''))
                    $(this).val(val);
                    grand_total();
                }
            }
            

        });
        $("#new_payment_method").click(function(){
            $("#payment_methods").append("<div class='row mb-3' id='payment_methods'>"+
            "<div class='col-md-6 mb-2'>"+
            "<label for='' class='form-label'>Payment Type</label>"+
            "<select name='' id='' class='form-select'>"+
                "<option value=''>Cash</option>"+
                "<option value=''>Credit Card</option>"+
                "<option value=''>Payment Card</option>"+
            "</select>"+
        "</div>"+
        "<div class='col-md-4 mb-2'>"+
            "<label for='' class='form-label'>Paid</label>"+
            "<input type='number' class='form-control' name='' id='' placeholder='0.00'>"+
        "</div>"+
        "<div class='col-md-2 mb-2'>"+
            "<label class='form-label'>Action</label><br>"+
            "<a href='javascript:void(0);' id='remove-payment' class='btn btn-danger mb-1'><i class='ti ti-trash'></i></a>"+
        "</div>"+
        "</div>")
        })
    $("#right-sidebar").css("display","none");
    if ($(window).width() >= 1099) {
        $('.left-sidebar').css("margin-left","-270px");
    }
    $(".app-header").css("width","100%");
    $(".body-wrapper").css("margin-left","0");
    $("#side-closes").css("display","block");


    var barcodeTimeout;
    var isProcessingBarcode = false;
    
    $("#barcode-input").keyup(function(e){
        var barcode = $(this).val();
        $(this).css("border-color","#DFE5EF");
        
        clearTimeout(barcodeTimeout);
        
        if(e.which === 13 || e.keyCode === 13) {
            e.preventDefault();
            
            if(isProcessingBarcode) {
                return;
            }
            
            var tr_id="b_"+barcode;
            var charcount=barcode.length;
            
            if(charcount>12)
            {
                isProcessingBarcode = true;
                
                if(document.getElementById(tr_id))
                {
                    var cart_qunatity = parseInt($("#"+tr_id+"").first().parent().parent().find("td #cart-quantity").val());
                    var new_qunatity = cart_qunatity+1;
                    var avl_qty = parseInt($("#"+tr_id).first().parent().parent().find("td #available_qty").val());
                    var product_price = parseInt($("#"+tr_id).first().parent().parent().find("#product_price").val());
                    var discount_percentage = parseInt($("#"+tr_id).first().parent().parent().find("#discount_percentage").val());
                    var rate = product_price*new_qunatity;
                    var discount_value=discount_percentage/100*rate;
                    discount_value=discount_value.toFixed(2);
                    var rate=rate-(discount_percentage/100*rate)
                    var new_rate=rate.toFixed(2);
                    var product_count=parseInt($("#"+tr_id).first().parent().parent().find("#product-qty-count2").val());
                    if(product_count>1)
                        {
                            
                            $("#section_modal").modal('toggle');
                            $.ajax({
                                url:'../AJAX/gui_pos/product.php?modal=1',
                                    method:'post',
                                    data:{id:barcode},
                                    success:function(response)
                                    {
                                        $("#product-modal-tbody").append(response);
                                        console.log(response);
                                        isProcessingBarcode = false;
                                    },
                                    error:function(){
                                        isProcessingBarcode = false;
                                    }
                            })
                            $("#barcode-input").val("");
                            $("#barcode-input").focus();
                        }
                        else
                        {
                            if(avl_qty>=new_qunatity)
                            {
                                $("#"+tr_id).first().parent().parent().find("#cart-quantity").val(new_qunatity);
                                $("#"+tr_id).first().parent().parent().find("#qty").text(" "+new_qunatity+" ");
                                $("#"+tr_id).first().parent().parent().find("#total_price").val(new_rate);
                                $("#"+tr_id).first().parent().parent().find("#total_price_span").text(new_rate);
                                $("#"+tr_id).first().parent().parent().find("#discount_value").val(discount_value);
                                $("#barcode-input").val("");
                                $("#barcode-input").focus();
                                isProcessingBarcode = false;
                            }
                            else
                            {
                                alert("Maximum Quantity Reached");
                                $("#barcode-input").val("");
                                $("#barcode-input").focus();
                                isProcessingBarcode = false;
                            }
                            grand_total();

                        }
                    
                }
                else
                {
                    $.ajax({
                        url:'../AJAX/gui_pos/product.php?modal=4',
                            method:'post',
                            data:{barcode:barcode},
                            success:function(response)
                            {
                                $("#cart-table tbody").append(response);
                                $("#barcode-input").val("");
                                $("#barcode-input").focus();
                                grand_total();
                                isProcessingBarcode = false;
                            },
                            error:function(){
                                isProcessingBarcode = false;
                            }
                    });
                }
            }
            else
            {
                $(this).css("border-color","red");
                isProcessingBarcode = false;
            }
        }
        else {
            barcodeTimeout = setTimeout(function() {
                if(barcode.length > 12) {
                    $("#barcode-input").css("border-color","#DFE5EF");
                }
            }, 100);
        }
        
    })
    $("body").on("click","#remove-cart",function(){
        if(confirm("Remove item?"))
            {
                $(this).parent().parent().parent().parent().remove();
            }
            grand_total();
    })
    $("body").on("click","#remove-payment",function(){
        $(this).parent().parent().remove();
    })

    // $("body #remove-cart").click(function(){

    // })
    $("body").on("keyup","#product_price", function(){
        var quantity = parseInt($(this).parent().parent().find("#cart-quantity").val());
        var product_price = parseFloat($(this).val());
        var discount_percentage = parseInt($(this).parent().parent().find("#discount_percentage").val());
        var rate = product_price*quantity;
        var discount_value=discount_percentage/100*rate;
        discount_value=discount_value.toFixed(2);
        rate=rate-(discount_percentage/100*rate);
        var new_rate=rate.toFixed(2);
        $(this).parent().parent().find("#total_price").val(new_rate);
        $(this).parent().parent().find("#total_price_span").text(new_rate);
        $(this).parent().parent().find("#discount_value").val(discount_value);
        grand_total();

    });
    $("body").on("keyup","#discount_percentage",function(){
        var quantity = parseInt($(this).parent().parent().find("#cart-quantity").val());
        var product_price = parseFloat($(this).parent().parent().find("#product_price").val());
        var discount_percentage = parseInt($(this).val());
        var rate = product_price*quantity;
        var discount_value=discount_percentage/100*rate;
        discount_value=discount_value.toFixed(2);
        var rate=rate-(discount_percentage/100*rate);
        var new_rate=rate.toFixed(2);
        $(this).parent().parent().find("#cart-quantity").val(quantity);
        $(this).parent().parent().find("#qty").text(" "+quantity+" ");
        $(this).parent().parent().find("#total_price").val(new_rate);
        $(this).parent().parent().find("#discount_value").val(discount_value);
        $(this).parent().parent().find("#total_price_span").text(new_rate);
        grand_total()

    })
    $("#clear-select").click(function(){
        
            $("body #item-chcek").prop("checked", false);
    })
    $("body #single-product").click(function(){
        var product_qty_count=$(this).find("#product-qty-count").val();
        var product_id=$(this).find("#product-id").val();
        var tr_id="p_"+product_id;
        var product_count=parseInt(product_qty_count);
        if(product_count>1)
        {
            $("#section_modal").modal('toggle');
            $.ajax({
                url:'../AJAX/gui_pos/product.php?modal=1',
                    method:'post',
                    data:{id:product_id},
                    success:function(response)
                    {
                        $("#product-modal-tbody").html(response);
                        // alert(response);


                    }
            })
        }
        else
        {
            // alert(tr_id)
            if(document.getElementById(tr_id))
            {
                // add quantity
                var cart_qunatity = parseInt($("#"+tr_id+" td #cart-quantity").val());
                var new_qunatity = cart_qunatity+1;
                var avl_qty = parseInt($("#"+tr_id).find("td #available_qty").val());
                var product_price = parseInt($("#"+tr_id).find("#product_price").val());
                var discount_percentage = parseInt($("#"+tr_id).find("#discount_percentage").val());
                var rate = product_price*new_qunatity;
                var discount_value=discount_percentage/100*rate;
                discount_value=discount_value.toFixed(2);
                var rate=rate-(discount_percentage/100*rate)
                var new_rate=rate.toFixed(2);
                if(avl_qty>=new_qunatity)
                {
                    $("#"+tr_id).find("#cart-quantity").val(new_qunatity);
                    $("#"+tr_id).find("#qty").text(" "+new_qunatity+" ");
                    $("#"+tr_id).find("#total_price").val(new_rate);
                    $("#"+tr_id).find("#total_price_span").text(new_rate);
                    $("#"+tr_id).find("#discount_value").val(discount_value);
                }
                else
                {

                }
                grand_total();

            }
            else
            {
                $.ajax({
                    url:'../AJAX/gui_pos/product.php?modal=2',
                        method:'post',
                        data:{id:product_id},
                        success:function(response)
                        {
                            $("#cart-table tbody").append(response);
                            grand_total();
                            // alert(response);


                        }
                });


            }
            grand_total();
        }
        grand_total();


    });
    $("body #btn_save_section").click(function(){
        if(!$('input[name="item-chcek"]:checked').val())
        {
            alert("No Item selected");
            $("#barcode-input").val("");
            $("#barcode-input").focus();

        }
        else
        {
            // alert();
            var price_id=$('input[name="item-chcek"]:checked').val();
            var p_id=$('input[name="item-chcek"]:checked').parent().find("#p_id").val();
            var tr_id="p_"+p_id+"_"+price_id;
            if(document.getElementById(tr_id))
            {
                // add quantity
                var cart_qunatity = parseInt($("#"+tr_id+" td #cart-quantity").val());
                var new_qunatity = cart_qunatity+1;
                var avl_qty = parseInt($("#"+tr_id).find("td #available_qty").val());
                var product_price = parseInt($("#"+tr_id).find("#product_price").val());
                var discount_percentage = parseInt($("#"+tr_id).find("#discount_percentage").val());
                var rate = product_price*new_qunatity;
                var discount_value=discount_percentage/100*rate;
                discount_value=discount_value.toFixed(2);
                var rate=rate-(discount_percentage/100*rate)
                var new_rate=rate.toFixed(2);
                if(avl_qty>=new_qunatity)
                {
                    $("#"+tr_id).find("#cart-quantity").val(new_qunatity);
                    $("#"+tr_id).find("#qty").text(" "+new_qunatity+" ");
                    $("#"+tr_id).find("#total_price").val(new_rate);
                    $("#"+tr_id).find("#total_price_span").text(new_rate);
                    $("#"+tr_id).find("#discount_value").val(discount_value);
                    $("#section_modal").modal('hide');
                    grand_total();
                    $("#barcode-input").val("");
                    $("#barcode-input").focus();
                }
                else
                {
    
                }
                grand_total();
                $("#barcode-input").val("");
                $("#barcode-input").focus();
    
            }
            else
            {
                $.ajax({
                    url:'../AJAX/gui_pos/product.php?modal=3',
                        method:'post',
                        data:{
                            price_id:price_id,
                            id:p_id
                        },
                        success:function(response)
                        {
                            $("#cart-table tbody").append(response);
                            grand_total();
                            $("#section_modal").modal('hide');
                            $("#barcode-input").val("");
                            $("#barcode-input").focus();
                            // alert(response);
                        }
                });
            }
            $("#barcode-input").val("");
            $("#barcode-input").focus();
            // alert(price_id);
            
            grand_total()
            $("#barcode-input").val("");
            $("#barcode-input").focus();
        }
        $("#barcode-input").val("");
        $("#barcode-input").focus();
    });
    $("#cancel-order").click(function(){
        if (confirm("Are you sure to cancel order?")) 
        {
            $("#cart-table tbody").html("");
            $("#barcode-input").val("");
            $("#barcode-input").focus();
            grand_total();
        }
        
    })
    $("#search").on("keyup", function(e) {
        e.preventDefault();
        var value = $(this).val().toLowerCase();
            $("#products #single-product").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
        $("body").on("click","#sub-quantity",function(){
            var quantity = parseInt($(this).parent().find("#cart-quantity").val());
            var new_quantity = quantity - 1;
            var product_price = parseInt($(this).parent().parent().parent().find("#product_price").val());
            var discount_percentage = parseInt($(this).parent().parent().parent().find("#discount_percentage").val());
            var rate = product_price*new_quantity;
            var discount_value=discount_percentage/100*rate;
            discount_value=discount_value.toFixed(2);
            var rate=rate-(discount_percentage/100*rate)
            var new_rate=rate.toFixed(2);
            if(new_quantity>0)
            {
                $(this).parent().find("#cart-quantity").val(new_quantity);
                $(this).parent().find("#qty").text(" "+new_quantity+" ");
                $(this).parent().parent().parent().find("#total_price").val(new_rate);
                $(this).parent().parent().parent().find("#total_price_span").text(new_rate);
                $(this).parent().parent().parent().find("#discount_value").val(discount_value);
            }
            else
            {

            }
            grand_total()

        })


        $("body").on("click","#add-quantity",function(){
            var product_price = parseInt($(this).parent().parent().parent().find("#product_price").val());
            var discount_percentage = parseInt($(this).parent().parent().parent().find("#discount_percentage").val());
            var quantity = parseInt($(this).parent().parent().find("#cart-quantity").val());
            var avl_qty = parseInt($(this).parent().parent().parent().find("td #available_qty").val());
            var new_quantity = quantity + 1;
            var rate = product_price*new_quantity;
            var discount_value=discount_percentage/100*rate;
            discount_value=discount_value.toFixed(2);
            var rate=rate-(discount_percentage/100*rate)
            var new_rate=rate.toFixed(2);
            if(avl_qty>=new_quantity)
            {
                $(this).parent().find("#cart-quantity").val(new_quantity);
                $(this).parent().find("#qty").text(" "+new_quantity+" ");
                $(this).parent().parent().parent().find("#total_price").val(new_rate);
                $(this).parent().parent().parent().find("#total_price_span").text(new_rate);
                $(this).parent().parent().parent().find("#discount_value").val(discount_value);
            }
            else
            {

            }
            grand_total()

        })
        $("#sale_disc").keyup(function(){
            var val=0;
            if ($(this).val()==0||$(this).val()=="0"||$(this).val()=="") {
                val=0;
            }
            else
            {
                val=$(this).val();
            }
            var sale_disc = parseFloat(val);
            var total_discount=0;
            $("body #discount_value").each(function(){
                total_discount= total_discount + parseFloat($(this).val());
            });
            total_discount=parseFloat(total_discount)+sale_disc;
            total_discount=total_discount.toFixed(2);
            $("#total_disc").val(total_discount);
            var total=0;
            var total2=0;
            $("body #total_price").each(function(){
                total= total + parseFloat($(this).val()) ;
                // console.log(total);
            })
            total=total-parseFloat(sale_disc)
            total2=total.toFixed(2);
            $("#grand_total").val(total2);

        })
        function total_discount()
        {
            var sale_disc = parseFloat($("#sale_disc").val());
            var total_discount=0;
            $("body #discount_value").each(function(){
                total_discount= total_discount + parseFloat($(this).val());
            });
            total_discount=total_discount+sale_disc;
            total_discount=total_discount.toFixed(2);
            $("#total_disc").val(total_discount);
        }
        function grand_total()
        {

            var total=0;
            var total2=0;
            $("body #total_price").each(function(){
                total= total + parseFloat($(this).val()) ;
                // console.log(total);
            })
            total=total-parseFloat($("#sale_disc").val())
            total2=total.toFixed(2);
            $("#grand_total").val(total2);
            $("#paid-input").val(total2);
            total_discount();
            $("#product-modal-tbody").html("");
        }

})