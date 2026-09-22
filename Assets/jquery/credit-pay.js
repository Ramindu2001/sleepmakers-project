$(document).ready(function(){
    $("#add").click(function () {
        var tr ="<tr>"+
                    "<td>"+
                        "<select name='invoice_id[]' class='form-control invoice_id' required>"+
                        "<option value=''>Select Invoice</option>"+
                        "</select>"+
                    "</td>"+
                    "<td>"+
                        "<input type='text' name='due[]' id='due' class='form-control' readonly>"+
                    "</td>"+
                    "<td>"+
                        "<input type='text' name='amount[]' id='amount' class='form-control amount'>"+
                    "</td>"+
                    "<td> <a href='javascript:void(0)' class='btn btn-danger' id='remove'><i class='ti ti-trash'></i></a></td>"+
                "</tr>";
        $("#tbody").append(tr);
        
        // Load invoices via AJAX
        var cus_id = $("#cus_id").val();
        var shop_id = $("#shop_id").val();
        
        $.ajax({
            url: "../Controller/AjaxController.php",
            type: "POST",
            data: {
                action: "get_customer_invoices",
                cus_id: cus_id,
                shop_id: shop_id
            },
            dataType: "json",
            success: function(data) {
                var select = $("#tbody tr:last-child .invoice_id");
                select.empty();
                select.append('<option value="">Select Invoice</option>');
                
                $.each(data, function(index, invoice) {
                    if (parseFloat(invoice.pending) > 0) {
                        select.append('<option value="' + invoice.invoice_header_id + '" data-due="' + invoice.pending + '">' + invoice.invoice + '</option>');
                    }
                });
            },
            error: function(xhr, status, error) {
                console.error("Error loading invoices:", error);
            }
        });
    });
    
    $(document).on('click', '#remove', function(){
        $(this).closest('tr').remove();
        calculateTotal();
    });
    
    $(document).on('change', '.invoice_id', function(){
        var selectedOption = $(this).find('option:selected');
        var due = selectedOption.data('due');
        var dueInput = $(this).closest('tr').find('#due');
        var amountInput = $(this).closest('tr').find('#amount');
        
        dueInput.val(due);
        amountInput.val(due);
        
        calculateTotal();
    });
    
    $(document).on('keyup', '.amount', function(){
        calculateTotal();
    });
    
    function calculateTotal() {
        var total = 0;
        $('.amount').each(function(){
            var amount = parseFloat($(this).val()) || 0;
            total += amount;
        });
        $('#total_payment').text(total.toFixed(2));
    }
});