$(document).ready(function(){
    
    
    $("#cmb_category").change(function(){
        var category_id = $(this).val();
        $.get("../AJAX/AjaxCategory/getSubcategory.php", {
            category_id: category_id
        }, function(data){
            $("#cmb_subcategory").html(data);
            $("#cmb_subcategory").focus();
        });//get subcategory
    });//cmb changed
    if ($(window).width() >= 1099) {
        $('.left-sidebar').css("margin-left","-270px");
    }
    $('#headerCollapse2').css("display","block");
    $('#headerCollapse3').css("display","none");
    $('.body-wrapper').css("margin-left","0");
    $("#side-closes").css("display","block");
    $(".app-header").css("width","100%");
    $("#pricechange_modal").modal("toggle");
    $("#btn_open_pricechange").click(function(){
        $("#pricechange_modal").modal("toggle");
    });
    $("#cmb_product").select2({
        ajax:{
            url: '../AJAX/WholeSaleInvoice/getItems.php',
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
    $(".select2").addClass("form-control");
});