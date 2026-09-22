$(document).ready(function(){
    $("#customerCTID").select2({      
        ajax:{
            url: '../AJAX/CreditPay/getCustomers.php',
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

})