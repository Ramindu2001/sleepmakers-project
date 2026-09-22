// Daily Sales Functionality
// Function to load daily sales data
function loadDailySales() {
    var selectedDate = $('#dailySaleDate').val();
    
    // Show loading state
    $('#userWiseSalesBody').html(`
        <tr>
            <td colspan="5" class="text-center text-muted">
                <i class="ti ti-loader rotate"></i> Loading data...
            </td>
        </tr>
    `);
    
    $.ajax({
        url: '../AJAX/guiPos/getDailySales.php',
        method: 'POST',
        data: {
            date: selectedDate
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Update daily summary
                $('#totalSalesAmount').text('Rs. ' + response.daily_summary.total_sales.toFixed(2));
                $('#totalInvoices').text(response.daily_summary.total_invoices);
                $('#totalDiscount').text('Rs. ' + response.daily_summary.total_discount.toFixed(2));
                $('#avgSaleAmount').text('Rs. ' + response.daily_summary.avg_sale.toFixed(2));
                
                // Update user-wise sales table
                var userSalesHtml = '';
                if (response.user_sales.length > 0) {
                    response.user_sales.forEach(function(user) {
                        userSalesHtml += `
                            <tr>
                                <td>${user.user_name}</td>
                                <td>${user.invoices}</td>
                                <td>Rs. ${user.total_amount.toFixed(2)}</td>
                                <td>Rs. ${user.avg_amount.toFixed(2)}</td>
                                <td>Rs. ${user.discount_given.toFixed(2)}</td>
                            </tr>
                        `;
                    });
                } else {
                    userSalesHtml = `
                        <tr>
                            <td colspan="5" class="text-center text-muted">No sales found for this date</td>
                        </tr>
                    `;
                }
                
                $('#userWiseSalesBody').html(userSalesHtml);
            } else {
                // Show error message
                $('#userWiseSalesBody').html(`
                    <tr>
                        <td colspan="5" class="text-center text-danger">
                            <i class="ti ti-alert-circle"></i> Error loading sales data
                        </td>
                    </tr>
                `);
                
                // Show toast notification
                if (typeof toastr !== 'undefined') {
                    toastr.error('Failed to load daily sales data');
                }
            }
        },
        error: function(xhr, status, error) {
            // Show error message
            $('#userWiseSalesBody').html(`
                <tr>
                    <td colspan="5" class="text-center text-danger">
                        <i class="ti ti-alert-circle"></i> Network error occurred
                    </td>
                </tr>
            `);
            
            // Show toast notification
            if (typeof toastr !== 'undefined') {
                toastr.error('Network error occurred while loading sales data');
            }
        }
    });
}

// Function to print daily sales report
function printDailySalesReport() {
    var selectedDate = $('#dailySaleDate').val();
    var totalSales = $('#totalSalesAmount').text();
    var totalInvoices = $('#totalInvoices').text();
    var totalDiscount = $('#totalDiscount').text();
    var avgSale = $('#avgSaleAmount').text();
    
    // Get user sales data
    var userSalesHtml = '';
    $('#userWiseSalesBody tr').each(function() {
        if (!$(this).find('.text-muted').length) { // Skip "No sales found" message
            userSalesHtml += '<tr>' + $(this).html() + '</tr>';
        }
    });
    
    // Create print content
    var printContent = `
        <div style="padding: 20px; font-family: Arial, sans-serif;">
            <h2 style="text-align: center; margin-bottom: 20px;">Daily Sales Report</h2>
            <p style="text-align: center; margin-bottom: 30px;"><strong>Date:</strong> ${selectedDate}</p>
            
            <div style="margin-bottom: 30px;">
                <h3 style="margin-bottom: 15px;">Daily Summary</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><strong>Total Sales:</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">${totalSales}</td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><strong>Total Invoices:</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">${totalInvoices}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><strong>Total Discount:</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">${totalDiscount}</td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><strong>Average Sale:</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">${avgSale}</td>
                    </tr>
                </table>
            </div>
            
            <div>
                <h3 style="margin-bottom: 15px;">User-wise Sales</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: #f5f5f5;">
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">User Name</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Invoices</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">Total Amount</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">Average Amount</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">Discount Given</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${userSalesHtml}
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 30px; text-align: center; color: #666; font-size: 12px;">
                <p>Generated on: ${new Date().toLocaleString()}</p>
            </div>
        </div>
    `;
    
    // Create print window
    var printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Daily Sales Report - ${selectedDate}</title>
            <style>
                @media print {
                    body { margin: 0; }
                }
            </style>
        </head>
        <body>
            ${printContent}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Add event handlers for daily sales
$(document).ready(function() {
    // Daily Sale Button Click Handler
    $('#dailySaleBtn').on('click', function() {
        $('#dailySaleModal').modal('show');
        loadDailySales();
    });
    
    // Load Daily Sales Button Click Handler
    $('#loadDailySales').on('click', function() {
        loadDailySales();
    });
    
    // Print Daily Sales Button Click Handler
    $('#printDailySales').on('click', function() {
        printDailySalesReport();
    });
    
    // Auto-load sales when date changes
    $('#dailySaleDate').on('change', function() {
        loadDailySales();
    });
});
