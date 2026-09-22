$(document).ready(function () {
    $('#btn_Add_Pay_modal').click(function () {
        $("#Payment_modal").modal('toggle');
    });

    $("#btn_submit_method").click(function (e) {
        e.preventDefault(); // Prevent the default form submission

        var shop_SHID = $("#ShopName").val();
        var paymethod_PMID = $("#PaymethodName").val();

        if (shop_SHID && paymethod_PMID.length > 0) {
            $.ajax({
                type: 'POST',
                url: '../Controller/AddPaymentController.php',
                data: {
                    btn_save_method: 1,
                    shop_SHID: shop_SHID,
                    paymethod_PMID: paymethod_PMID 
                },
                success: function (data) {
                    alert(data);
                    $("#Payment_modal").modal('toggle');
                    location.reload();
                }
            });
        } else {
            alert("Please select a shop and at least one payment method.");
        }
    });

    $(document).on('click', '.btn-delete', function (e) {
        e.preventDefault(); // Prevent the default anchor behavior
        var SPID = $(this).data('suid'); // Ensure the data attribute name matches

        console.log("Delete button clicked for SPID: " + SPID);

        if (confirm("Are you sure you want to delete this payment method?")) {
            // Proceed to delete the payment method
            $.ajax({
                type: 'POST',
                url: '../Controller/AddPaymentController.php',
                data: {
                    delete_payment: 1,
                    SPID: SPID
                },
                success: function (response) {
                    console.log("Server response: " + response);
                    alert(response);
                    if (response.trim() === "Payment Method Deleted") {
                        location.reload();
                    }
                },
                error: function (xhr, status, error) {
                    console.error("AJAX error: " + status + " - " + error);
                }
            });
        }
    });

    //======================== Add paymethod ========================//
    $("#btn_open_paymethod").click(function(){
        $("#modal_paymethod").modal('toggle');
    });

    $("#paymethod_image").change(function(event){
        var size=this.files[0].size;
        if (size>=500000)
        {
            $("#image_warning").fadeIn();
            $("#image_success").fadeOut();
            $(this).css("border-color","red");
            $("#btn_save_paymethod").attr('disabled', true);
        }//greater than 5 mb
        else
        {
            $("#image_warning").fadeOut();
            $("#image_success").fadeIn();
            $(this).css("border-color","lime");
            $("#btn_save_paymethod").attr('disabled', false);

            var url = URL.createObjectURL(event.target.files[0]);
            $("#img_paymethod").attr("src", url);
        }//less than 5 mb
    });
});//paymenthod jQuery
