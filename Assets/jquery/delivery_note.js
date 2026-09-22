$(document).ready(function(){
    $("#cmb_customer_delivery").select2({
        ajax:{
            url: '../AJAX/DeliveryNote/getCustomers.php',
            dataType: 'json',
            delay: 250,
            data: function(params){
                console.log(params);
                
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
        placeholder: 'Search for Customer',
        minimumInputLength: 1,
        width: '70%',
    });
    $("#cmb_customer_delivery").on("change",function(){
        var customer_id=$(this).val();
        $("#invoices").load("../Public/get_customers_delivery.php?customer_id="+customer_id);

    })
    $("#invoices").load("../Public/get_customers_delivery.php");
})