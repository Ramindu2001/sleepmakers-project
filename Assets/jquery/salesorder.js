$(document).ready(function(){
    //====================== Initialize ========================//
    let totalQty = 0;
    let totalAmount = 0;

    updateTotals();

    $("#btn_edit_salOrder_detail").css('display', 'none');
    $("#product_detail").css('display', 'none');
    
    let customerSelect = document.getElementById("customerCTID");
    
    

    for (let i = 0; i < customerSelect.options.length; i++) {
        if (customerSelect.options[i].value === "1") { // Check if the option value is "1"
            customerSelect.selectedIndex = i; // Set it as selected
            $("#customerCTID").trigger("change"); // Trigger Select2 change event if applicable
            break; // Exit loop once found
        }
    }

    //validate input
    $("#prod_qty").on("keypress", function(event) {
        if (event.key === "Enter") {
            event.preventDefault(); // Prevent form submission

            var prodQty = parseFloat($(this).val());
            if (!isNaN(prodQty) && prodQty > 0) {
                $("#qty_warning").hide();
                $(this).css("border-color", "LimeGreen");

                // Trigger the button click function
                $("#btn_add_salOrder_detail").click();
            } else {
                $("#qty_warning").show();
                $(this).css("border-color", "red").val("").focus();
            }
        }
    });

    // Live input validation (optional)
    $("#prod_qty").on("input", function() {
        this.value = this.value.replace(/[^0-9.]/g, ""); // Remove non-numeric characters
    });
    
    

    //validate selling price
    $("#selling_price").change(function(){
        var sellingPrice = parseFloat($(this).val());
        if(sellingPrice < 0)
        {
            $("#sel_warning").css('display', 'block');
            $("#selling_price").css('border-color', 'red');
            $("#selling_price").val("");
            $("#selling_price").focus();
            return;
        }//purchase price less than 0
        else
        {
            $("#sel_warning").css('display', 'none');
            $("#selling_price").css('border-color', 'LimeGreen');
        }
    });//validate

    

    $("#cmb_product").select2({
        ajax:{
            url: '../AJAX/SalesOrder/getProducts.php',
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

    $("#cmb_product").change(function(){
        var product_id = $(this).val();
        // $("#ids").val(product_id);
        // $.get("../AJAX/Products/getCmbVariation.php", {
        //     product_id: product_id
        // }, function(data){
        //     $("#cmb_variation").html(data);

        //     $("#prod_qty").focus();
        // });//get variations

        $.get("../AJAX/Products/getProductBatch.php", {
            product_id: product_id
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);
            var purchase_price = 0;
            var selling_price = 0;

            $.each(obj, function (key, value) { 
                console.log('val - ' + value['SellingPrice']);
                purchase_price = value['PurchasePrice'];
                selling_price = value['SellingPrice'];
            });

            $("#purchase_price").val(purchase_price);
            $("#selling_price").val(selling_price);
        });
    });//select

    $("#customerCTID").select2({
        ajax:{
            url: '../AJAX/SalesOrder/getCustomers.php',
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
        placeholder: 'Search for Customer',
        minimumInputLength: 1
    });//cmb product
    
           
    $("#btn_save_product").click(function(){
        var subcategory_id = $("#cmb_subcategory").val();
        var barcode = $("#barcode").val();
        var prod_name = $("#prod_name").val();
        var second_name = $("#second_name").val() == undefined ? "" : $("#second_name").val();
        var prod_description = $("#prod_description").val();
        var prod_carton_qty = $("#prod_carton_qty").val() == undefined ? "" : $("#prod_carton_qty").val();
        var cmb_purchase_unit = $("#cmb_purchase_unit").val();
        var conversion_rate = $("#conversion_rate").val();
        var cmb_selling_unit = $("#cmb_selling_unit").val();
    
        $.get("../AJAX/Products/setProduct.php", {
            barcode: barcode,
            prod_name: prod_name,
            second_name: second_name,
            prod_description: prod_description,
            prod_carton_qty: prod_carton_qty,
            subcategory_id: subcategory_id,
            cmb_purchase_unit: cmb_purchase_unit,
            conversion_rate: conversion_rate,
            cmb_selling_unit: cmb_selling_unit
        }, function(data){
            alert(data);
    
            $("#barcode").val("");
            $("#prod_name").val("");
            $("#second_name").val("");
            $("#prod_description").val("");
            $("#prod_carton_qty").val("");
            $("#cmb_purchase_unit").val("");
            $("#conversion_rate").val("");
            $("#cmb_selling_unit").val("");
        });
    });//save product
    
    
    function LoadTable(grn_header_id)
    {
        $.get("../AJAX/GRN/getGRNDetails.php", {
            grn_header_id: grn_header_id
        }, function(data){
            $("#tbl_grn_details").html(data);
        });//load table
    }//load table

    //----------------------Sales order details adding----------------------//
 
    // Function to update the totals at the bottom
    function updateTotals() {
        document.getElementById("sub_item_count").innerText = totalQty;
        document.getElementById("sub_purchase_price").innerText = totalAmount.toFixed(2);

        // Update Row Count
        let rowCount = document.getElementById("tbody").getElementsByTagName("tr").length;
        document.getElementById("sub_row_count").innerText = rowCount;
    }

   
    document.getElementById("btn_add_salOrder_detail").addEventListener("click", function () {
        let customerSelect = document.getElementById("customerCTID");
        let productSelect = document.getElementById("cmb_product");
        let sellingPriceInput = document.getElementById("selling_price");
        let qtyInput = document.getElementById("prod_qty");
        let tbody = document.getElementById("tbody");


         // Get selected customer ID
         let customerID = customerSelect.value;
         if (!customerID) {
             alert("Please select a customer before adding items.");
             customerCTID.focus();
             return;
         }

        // Get selected product details
        let productID = productSelect.value;
        let productName = productSelect.options[productSelect.selectedIndex].text;
        let sellingPrice = parseFloat(sellingPriceInput.value);
        let qty = parseFloat(qtyInput.value);

        // Validation
        if (productID === "" || isNaN(sellingPrice) || sellingPrice <= 0 || isNaN(qty) || qty <= 0) {
            alert("Please select a product and enter valid price and quantity.");
            return;
        }

        let total = (sellingPrice * qty).toFixed(2);

        // Create a new row
        let newRow = document.createElement("tr");
        newRow.innerHTML = `
            <td style="display: none;">${productID}<input type="hidden" name="product_id[]" value="${productID}"></td>
            <td>${productName} <input type="hidden" name="product_name[]" value="${productName}"></td>
            <td>${qty} <input type="hidden" name="qty[]" value="${qty}"></td>
            <td>${sellingPrice} <input type="hidden" name="selling_price[]" value="${sellingPrice}"></td>
            <td>${total} <input type="hidden" name="total[]" value="${total}"></td>
            <td>
                <button type="button" class="btn btn-danger btn-sm btn-remove">
                    <i class="ti ti-trash"></i>
                </button>
            </td>
        `;

        // Append row to table body
        tbody.appendChild(newRow);

        totalQty += qty;
        totalAmount += parseFloat(total);
        updateTotals();
        clearSpecificSelect2Dropdown("cmb_product"); 


         // Clear inputs
         productSelect.value = "";
         sellingPriceInput.value = "";
         qtyInput.value = "";
        
        
        // Remove item on button click
        newRow.querySelector(".btn-remove").addEventListener("click", function () {
            
            totalQty -= qty;
            totalAmount -= parseFloat(total);

            // Remove the row
            newRow.remove();

            // Update the running totals
            updateTotals();
        });

        $("#cmb_product").focus();
    });
  
    function clearSpecificSelect2Dropdown(dropdownID) {
    $("#" + dropdownID).val(null).trigger("change"); // Clear specific dropdown

    
    $(document).ready(function() {
        // Prevent Enter from submitting form
        $(document).on("keydown", "input", function(event) {
            if (event.key === "Enter") {
                event.preventDefault();
            }
        });
    
        $('#salesOrderForm').on('submit', function(e) {
            e.preventDefault(); // Prevent the default form submission
    
            // Gather form data
            const formData = {
                invoice_no: $('#invoiceno').val(),
                customerCTID: $('#customerCTID').val(),
                effective_date: $('input[name="effective_date"]').val(),
                shop_id: $('#shop_no').val(),
                products: [] // Array to store product details
            };

            // Loop through the table rows to gather product details
            $('#tbl_SO_details tbody tr').each(function() {
                const product = {
                    PDID: $(this).find('td:eq(0)').text(), // Product ID
                    PDName: $(this).find('td:eq(1)').text(), // Product Name
                    qty: $(this).find('td:eq(2)').text(), // Quantity
                    selling_price: $(this).find('td:eq(3)').text(), // Selling Price
                    total: $(this).find('td:eq(4)').text() // Total
                };
    
                // Only add products with all required values
                if (product.PDID && product.qty && product.selling_price && product.total) {
                    formData.products.push(product);
                }
            });
    
            // Stop if no valid products are selected
            if (formData.products.length === 0) {
                alert("No valid products to submit!");
                return;
            }
    
            // Send data to the server using AJAX
            $.ajax({
                url: '../AJAX/SalesOrder/savesalesorder.php',
                type: 'POST',
                data: JSON.stringify(formData),
                contentType: 'application/json',
                success: function(response) {
                    alert('Sales order saved successfully!');
                    //console.log(response);
                    //exit();
                    // Reset form after successful submission
                    $('#salesOrderForm')[0].reset();
                    $('#tbl_SO_details tbody').empty();
    
                    // Reset totals
                    totalQty = 0;
                    totalAmount = 0;
                    updateTotals();
    
                    // Reload page after delay
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                },
                error: function(xhr, status, error) {
                    alert('An error occurred while saving the data.');
                    console.error(error);
                }
            });
        });
    });


    //Edit Sales Order Details
    // Handle Edit Button Click
    $(document).on("click", ".btn-edit-order", function () {
        let orderId = $(this).data("id"); // Get Order ID from button

        $.ajax({
            url: "../AJAX/SalesOrder/getSalesOrder.php", // New PHP file to fetch order details
            type: "GET",
            data: { id: orderId },
            dataType: "json",
            success: function (response) {
                if (response.success) {
                    // Populate Form with Existing Data
                    $("#invoiceno").val(response.data.invoice_no);
                    $("#customerCTID").val(response.data.customer_id).trigger("change");
                    $('input[name="effective_date"]').val(response.data.effective_date);
                    $("#shop_no").val(response.data.shop_id);

                    // Clear current product rows
                    $("#tbl_SO_details tbody").empty();

                    // Populate product list
                    response.data.products.forEach(function (product) {
                        let newRow = `
                            <tr>
                                <td style="display: none;">${product.PDID}
                                    <input type="hidden" name="product_id[]" value="${product.PDID}">
                                </td>
                                <td>${product.PDName} 
                                    <input type="hidden" name="product_name[]" value="${product.PDName}">
                                </td>
                                <td>${product.qty} 
                                    <input type="hidden" name="qty[]" value="${product.qty}">
                                </td>
                                <td>${product.selling_price} 
                                    <input type="hidden" name="selling_price[]" value="${product.selling_price}">
                                </td>
                                <td>${product.total} 
                                    <input type="hidden" name="total[]" value="${product.total}">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm btn-remove">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </td>
                            </tr>`;
                        $("#tbl_SO_details tbody").append(newRow);
                    });

                    // Show edit mode
                    $("#btn_edit_salOrder_detail").show();
                    $("#btn_add_salOrder_detail").hide();

                    // Store order ID for editing
                    $("#salesOrderForm").data("edit-id", orderId);
                } else {
                    alert("Failed to fetch order details.");
                }
            },
            error: function () {
                alert("Error fetching sales order details.");
            }
        });
    });
    
    
    
}
});//jQuery SO details