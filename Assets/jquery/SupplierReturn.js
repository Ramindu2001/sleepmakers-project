$(document).ready(function () {

    let totalQuantity = 0; // Initialize total quantity
    let totalAmount1 = 0;   // Initialize total amount

    $("#btn_edit_srn_detail").css('display', 'none');    
    
    $("#btn_open_return").click(function(){
        $("#return_head_modal").modal('toggle');      

        //get srn no
        $.get("../AJAX/SupplierReturn/getSRNno.php", 
        function(data){
            $("#srn_no").val(data);
        });//get srn no        
    });//open supplier return modal

    $("#close_return_modal").click(function(){
        $("#return_head_modal").modal('toggle');
    });//close supplier return modal

      // Function to update the summary totals
    function updateSummary() 
    {       
        // Update the totals in the DOM
        $("#sub_purchase_price").text(totalAmount1.toFixed(2));  // Assuming this is the field for total price
        $("#sub_item_count").text(totalQuantity.toFixed(2));  // Assuming this is the field for item count
    }


     // Event listeners for input fields
     $('#prod_qty, #purchase_price').on('input', function() {
        calculateTotal(); // Calculate current total
    });

    // Function to calculate total amount for the current input
    function calculateTotal() {
        let qty = parseFloat($('#prod_qty').val()) || 0; // Get current quantity
        let price = parseFloat($('#purchase_price').val()) || 0; // Get current price

        let total = (qty * price).toFixed(2); // Calculate current total
        $('#total_amount').val(total); // Update current total field
    }
    
    $('#btn_add_srn_detail').click(function () {    
            
        let returnQty = $('#prod_qty').val();
        let purchasePrice = $('#purchase_price').val();       
        let inventoryId = $('#inventory_id').val();
        let variationId = $('#cmb_variation').val();
        let batchId = $('#Batchid').val();
        let headerId = $('#hide_header_id').val();
        let AvaQty = $('#Ava_qty').val();
        
        let qty = parseFloat($('#prod_qty').val()) || 0; // Get current quantity
        let total = parseFloat($('#total_amount').val()) || 0; // Get current total amount

        // Update overall totals
        if (!isNaN(qty)) 
        {
            totalQuantity += qty; // Add to total quantity
        }

        if (!isNaN(total)) 
        {
            totalAmount1 += total; // Add to total amount
        }
        
        // Update summary display
        updateSummary();

        // Validation checks
        if (!returnQty || returnQty <= 0) {
            alert("Please enter a valid quantity.");
            return;
        }
    
        if (!purchasePrice || purchasePrice <= 0) {
            alert("Please enter a valid purchase price.");
            return;
        }

        if (AvaQty < parseFloat(returnQty)) {
            alert("Please enter a valid Stock quantity.");
            return;
        }
          
        if (!batchId) {
            alert("Please select a batch.");
            return;
        }
          
        let totalAmount = calculateTotalAmount(returnQty, purchasePrice);
    
        $.post("../AJAX/SupplierReturn/return.php", {
            return_qty: returnQty,
            purchase_price: purchasePrice,           
            inventory_id: inventoryId,
            variation_id: variationId,
            total_amount: totalAmount,
            header_id: headerId,
            batchId: batchId
        }, function(){
            // Clear data
            $("#cmb_product").val("");
            $("#cmb_Batch").val("");
            $("#Batchid").val("");
            $("#cmb_variation").val("");
            $("#prod_qty").val("");
            $("#purchase_price").val("");
            $("#ids").val("");
            $("#inventory_id").val("");
            $("#Ava_qty").val("0");   
            $("#total_amount").val("0.00");   

            LoadTable(headerId);
    
            getFinalTotal();
        }); // add SRN details

        $("#cmb_Batch").change();
        
    });
    
    $("#tbl_srn_details").on('click', '.btn_srn_remove', function(){
        let row = $(this).closest('tr');
        let id = row.data('id');

        var srn_header_id = $("#hide_header_id").val();
        $.get("../AJAX/SupplierReturn/deleteSRN.php", {
            srn_detail_id: id
        }, function(){
            //clear data
            $("#cmb_product").val("");
            $("#cmb_variation").val("1");
            $("#prod_qty").val("");
            $("#purchase_price").val("");         
            $("#hide_detail_id").val("0");
            $("#ids").val("");
            $("#Batchid").val("");
            $("#Ava_qty").val("0");
    
            LoadTable(srn_header_id);
            
            getFinalTotal();
        });//delete row
    });

     //===================== total ===================//
     function getFinalTotal() {
        var total = 0;
        var totalItems = 0;

        var srn_header_id = $("#hide_header_id").val();

        // alert("srn_header = " + srn_header_id);

        $.get("../AJAX/SupplierReturn/getSupplierReturn.php", {
            srn_header_id: srn_header_id
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);

            var return_amount = 0;
            var item_count = 0;
            $.each(obj, function (key, value) { 
                return_amount += parseFloat(value['return_detail_amount']);
                item_count += parseFloat(value['ReturnQty']);
            });

            return_amount = return_amount.toFixed(2);

            $("#sub_item_count").text(item_count);
            $("#sub_purchase_price").text(return_amount);
            
        });
    }//get final total
    
  

    function LoadTable(srn_header_id)
    {
        $.get("../AJAX/SupplierReturn/getreturn.php", {
            srn_header_id: srn_header_id
        }, function(data){
            $("#tbl_srn_details").html(data);
        });//load table
    }//load table

    $('.btn-delete-return-detail').on('click', function () {
        if (confirm("Are you sure you want to delete this item?")) {
            let row = $(this).closest('tr');
            let return_detail_id = row.find('td:first').text();

            console.log('Deleting return_detail_id:', return_detail_id); // Debugging

            $.ajax({
                url: '../AJAX/SupplierReturn/return.php',
                type: 'POST',
                data: { return_detail_id: return_detail_id },
                success: function (response) {
                    console.log('Response:', response);
                    if (response === 'success') {
                        row.remove();
                    } else {
                        console.error('Failed to delete the item.');
                        alert('Failed to delete the item.');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error during AJAX request', status, error);
                    alert('Error deleting the item.');
                }
            });
                   
        }
    });

    //Added by Imila on 2024-07-29

    $("#cmb_product").select2({
        ajax:{
            url: '../AJAX/SupplierReturn/getProducts.php',
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
        minimumInputLength: 1
    });//cmb product

    $("#cmb_product").change(function() {
        var product_id = $(this).val();
        $("#ids").val(product_id);
        product_id=$("#ids").val();
        // Fetch variations
        $.get("../AJAX/Products/getCmbVariation.php", { product_id: product_id }, function(data) {
            $("#cmb_variation").html(data);
            
            console.log("Variation data received:", data); 
            // Check if cmb_variation did not receive data
            if (data.trim() === "") {
                
            }
            else
            {
                $("#cmb_variation").change();
            }
            // Fetch batches only if cmb_variation has no data
            $.get("../AJAX/Products/getBatch.php", { product_id: product_id })
            .done(function(batchData) {
                console.log("Batch data received:", batchData); // Debugging line
                $("#cmb_Batch").html(batchData);
                // Trigger change event on #cmb_Batch to execute the next function
                $("#cmb_Batch").change();
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                console.error("Error fetching batches:", textStatus, errorThrown); // Debugging line
            });

        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error("Error fetching variations:", textStatus, errorThrown); // Debugging line
        });
    });
    
    $("#cmb_variation").change(function() {     
        var VariationID = $(this).val();
        var ProductID = $("#ids").val();

        console.log("data received:", VariationID,ProductID); // Debugging line

        $.get("../AJAX/Products/getBatchbyVariation.php", {
            product_id: ProductID,
            Variation_id: VariationID
        })
        .done(function(batchData) {
            console.log("Batch data received:", batchData); // Debugging line
            $("#cmb_Batch").html(batchData);
            // Trigger change event on #cmb_Batch to execute the next function
            $("#cmb_Batch").change();
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            console.error("Error fetching batches:", textStatus, errorThrown); // Debugging line
        });       
    });

    $("#cmb_Batch").change(function() {
        var ProductID = $("#ids").val();
        var BatchID = $(this).val();
        $("#Batchid").val(BatchID);

        console.log("Batch ID selected:", BatchID); // Debugging line

        $.get("../AJAX/Products/getProductdata.php", {
            product_id: ProductID,
            batch_id: BatchID
        })
        .done(function(data) {
            console.log("Product data received:", data); // Debugging line
            
            // Parse the JSON data           
            const obj = JSON.parse(data);

            var Purchase_Price = parseFloat(obj[0]['PurchasePrice']) || 0;
            var AvailableQty = parseFloat(obj[0]['CurrentQty']) || 0;
            var InventoryID = obj[0]['INID'] ;

            // Update the text boxes with the received data
            $("#purchase_price").val(Purchase_Price.toFixed(2));          
            $("#prod_qty").val(1);
            $("#inventory_id").val(InventoryID);
            $("#Ava_qty").val(AvailableQty.toFixed(2));      
            $("#prod_qty").focus();                      
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            console.error("Error fetching product data:", textStatus, errorThrown); // Debugging line
        });
    });

    function formatDate(dateString) {
        if (!dateString) return '';
    
        // Assuming dateString is in format 'YYYY-MM-DD' or similar
        var date = new Date(dateString);
    
        // Check if the date is valid
        if (isNaN(date.getTime())) return '';
    
        // Convert date to 'YYYY-MM-DD'
        var year = date.getFullYear();
        var month = ('0' + (date.getMonth() + 1)).slice(-2);
        var day = ('0' + date.getDate()).slice(-2);
    
        return `${year}-${month}-${day}`;
    }

     //validate input
     $("#prod_qty").change(function(){
        var prodQty = parseFloat($(this).val());
        if(prodQty <= 0)
        {
            $("#qty_warning").css('display', 'block');
            $("#prod_qty").css('border-color', 'red');
            $("#prod_qty").val("");
            $("#prod_qty").focus();
            return;
        }//no qty
        else
        {
            $("#qty_warning").css('display', 'none');
            $("#prod_qty").css('border-color', 'LimeGreen');
        }
    });//qty changed

    //validate purchase price
    $("#purchase_price").change(function(){
        var purchasePrice = parseFloat($(this).val());
        if(purchasePrice < 0)
        {
            $("#pur_warning").css('display', 'block');
            $("#purchase_price").css('border-color', 'red');
            $("#purchase_price").val("");
            $("#purchase_price").focus();
            return;
        }//purchase price less than 0
        else
        {
            $("#pur_warning").css('display', 'none');
            $("#purchase_price").css('border-color', 'LimeGreen');
        }
    });//purchase price changes
    

    $("#btn_edit_srn_detail").click(function(){
        var srn_header_id = $("#hide_header_id").val();
        var product_id = $("#ids").val();
        var srn_detail_id = $("#hide_detail_id").val();      
        var variation_id = $("#cmb_variation").val() == undefined ? 1 : $("#cmb_variation").val();
        var prod_qty = $("#prod_qty").val();
        var purchase_price = $("#purchase_price").val();
        var BatchID = $("#Batchid").val();        

        $.post("../AJAX/SupplierReturn/editSRNDetails.php", {
            prod_qty: prod_qty,
            purchase_price: purchase_price,                      
            variation_id: variation_id,
            product_id: product_id,            
            srn_detail_id: srn_detail_id,
            BatchID: BatchID
        }, function(){
           
            LoadTable(srn_header_id);

            // Clear the form fields and reset everything
            clearFormFields();
            enableDropdown();

            //hide add button
            $("#btn_add_srn_detail").css('display', 'block');
            $("#btn_edit_srn_detail").css('display', 'none');

            getFinalTotal();
        });//edit srn details
           
    });//edit grn details


    // Function to clear form fields after edit or add operation
    function clearFormFields() {
        $("#cmb_product").val("");
        $("#cmb_variation").val("0");
        $("#prod_qty").val("");
        $("#purchase_price").val("");
        $("#Batchid").val("");
        $("#ids").val("");
        $("#product_detail").css('display', 'None');
        $("#product_detail").text("");
        $("#Batch_detail").css('display', 'None');
        $("#Batch_detail").text("");
        $("#Ava_qty").val("0");
    }

    $("#tbl_srn_details").on('click', '.btn_srn_edit', function(){
        let row = $(this).closest('tr');
        let id = row.data('id');
    
        // Fetch the existing values from the table row
        let existingQty = row.find('td').eq(2).text(); // Assuming quantity is in the second column
        let existingPrice = row.find('td').eq(3).text(); // Assuming purchase price is in the fourth column
                  
        totalQuantity = $('#sub_item_count').text(); // Get current quantity
        totalAmount1 = $('#sub_purchase_price').text(); // Get current total amount
        totalAmount1 = parseFloat(totalAmount1.replace(/,/g, ''));

        // Update overall totals
        totalQuantity -= existingQty; // Add to total quantity
        totalAmount1 -= (existingPrice * existingQty); // Add to total amount
    
        // Update summary display
        updateSummary();

        var srn_header_id = $("#hide_detail_id").val();
        //hide add button
        $("#btn_add_srn_detail").css('display', 'none');
        $("#btn_edit_srn_detail").css('display', 'block');
            
        $("#hide_detail_id").val(id);
    
        $.get("../AJAX/SupplierReturn/getOneSupplierReturn.php", {
            srn_detail_id: id
        }, function(data){
            const obj = JSON.parse(data);
             
            var item_name = obj[0]['ItemName'];
            var item_qty = obj[0]['ReturnQty'];
            var purchase_price = obj[0]['UnitPurchasePrice'];
            var productID = obj[0]['PDID'];
            var Batch = obj[0]['Batch'];
            var InventoryID = obj[0]['InventoryID'];
          

            $("#product_detail").css('display', 'block');
            $("#product_detail").text(item_name);
            $("#Batch_detail").css('display', 'block');
            $("#Batch_detail").text(Batch);
            $("#Batchid").val(Batch);
            $("#inventory_id").val(InventoryID);
            $("#prod_qty").val(item_qty);
            $("#purchase_price").val(purchase_price);                        
            $("#ids").val(productID);
    
            LoadTable(srn_header_id);
            
            disableDropdown();
                        
        });//load srn detail to uppler line
    });//table button click
    
    //========================= Submit SRN ===========================//
    $("#btn_submit_srn").click(function(){

        var rowCount = $("#tbl_srn_details tr").length - 1;
        
        if(rowCount > 0)
        {
            $("#srndetail_modal").modal('toggle');
     
            //get header id
            var srn_header_id = $("#hide_header_id").val();
             $("#hide_srnheader_id").val(srn_header_id);
        }
        else
        {
            alert("Please add items to the cart!.");
            return;
        }

    });//open submit srn

     // Function to disable the dropdown
     function disableDropdown() {
        $('#cmb_product').prop('disabled', true);
        $('#cmb_variation').prop('disabled', true);
        $('#cmb_Batch').prop('disabled', true);
    }

    // Function to enable the dropdown
    function enableDropdown() {
        $('#cmb_product').prop('disabled', false);
        $('#cmb_variation').prop('disabled', false);
        $('#cmb_Batch').prop('disabled', false);
    }
});

function calculateTotalAmount(returnQty, purchasePrice) {
    return returnQty * purchasePrice;
}
