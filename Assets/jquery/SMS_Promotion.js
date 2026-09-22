$(document).ready(function(){
    $("#cmb_customer_SMS").select2({      
        ajax:{
            url: '../AJAX/SMS/getCustomers.php',
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

    // Event to handle customer selection
    $("#cmb_customer_SMS").on('select2:select', function (e) {
        const selectedData = e.params.data; // Get the selected customer's data

        // Assuming the server sends an object with an id, name, and phone
        if (selectedData && selectedData.phone) {
            $("#customerPhone").val(selectedData.phone); // Set the phone number input
            $("#smsContent").focus();

        } else {
            $("#customerPhone").val(''); // Clear if no phone is available
        }

        $("#smsContent").focus();
    });
})