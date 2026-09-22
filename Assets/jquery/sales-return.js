$(document).ready(function()
{
    $("#invoiceNo").focus();
    $('#invoiceNo').on('input', function() {
        $(this).val($(this).val().replace(/[^a-zA-Z0-9_-]/, ''));
    });
    if ($(window).width() >= 1099) {
        $('.left-sidebar').css("margin-left","-270px");
    }
    $('#headerCollapse2').css("display","block");
    $('#headerCollapse3').css("display","none");
    $('.body-wrapper').css("margin-left","0");
    $("#side-closes").css("display","block");
    $('input:text[readonly]').css("background-color","#ebebeb");
    $('input:text[readonly]').css("border-color","#ebebeb");
    $('input:hidden[readonly]').css("background-color","#ebebeb");
    $('input:hidden[readonly]').css("border-color","#ebebeb");
    $("body #returnqty").on('input', function() {
        $(this).val($(this).val().replace(/[^0-9.]/, ''));
    });
    $(".app-header").css("width","100%");
    $("body").on("change","#return-type", function(){
        var returnType = $(this).val();
        var id="";
        var tr="";
        var product_name="";
        var productID="";
        var invoicedetailsid="";
        if(returnType==2)
        {
            $("#btnInvoice").fadeIn();
            if(validateForm()!=true)
            {
                $(this).val(1).trigger("change");
                $("#returnTableTbody").html("");
                $("#returnItem").fadeOut();
            }
            else
            {
                $("#returnItem").fadeIn();    
                const checkboxes = $('input[type="checkbox"]');
                checkboxes.each(function ()
                {
                    if($(this).is(':checked'))
                    {
                        id = $(this).attr("id") ;
                        item=id.substring(6);
                        product_name=$("#product_name"+item).val();
                        productID=$("#productID"+item).val();
                        invoicedetailsid=$("#invoicedetailsid"+item).val();
                        tr="<tr id='exchangeItem"+invoicedetailsid+"'>"+
                            "<td>"+product_name+
                            "<input type='hidden' name='actualItem[]' id='actualItem"+invoicedetailsid+"' value='"+productID+"'>"+
                            "</td>"+
                            "<td>"+
                            "<select name='changeitem[]' id='changeitem"+invoicedetailsid+"' class='form-control' required onchange='changeitemBatch("+invoicedetailsid+")'></select>"+
                            "</td>"+
                            "<td>"+
                            "<select name='changeitemBatch[]' id='changeitemBatch"+invoicedetailsid+"' class='form-control' required ></select>"+
                            "</td>"+
                        
                        "</tr>";
                        $("#returnTableTbody").append(tr);
                        $("#changeitem"+invoicedetailsid).select2({
                            ajax:{
                                url: '../AJAX/salesreturn/getItems.php?id='+productID,
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
                            placeholder: 'Search for Items',
                            minimumInputLength: 1,
                            width: '100%',
                        });//get item search\
                        
                    }                     
                })
            }
            
        }
        else
        {
            $("#btnInvoice").fadeOut();
            $("#returnTableTbody").html("");
            $("#returnItem").fadeOut();
        }
        // alert(returnType);KvalidateForm()
    })
})