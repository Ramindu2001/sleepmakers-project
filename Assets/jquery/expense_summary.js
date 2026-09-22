$(document).ready(function () {
    var shop_name = $("#shop_name").val();
    var shop_address_one = $('#shop_address_one').val();
    var shop_address_two = $('#shop_address_two').val();
    var shop_number = $('#shop_number').val();
    var shop_city = $('#shop_city').val();
    var total_amount = $('#total_amount').text().replace(/[^0-9.-]+/g, "");
    var reportname=$("#title").val();
    var totalAmount = $('#totalAmount').val();
    if(reportname)
        {
            $("title").html(reportname);
        }
    // // Console log to debug values
    // console.log("Shop Name: ", shop_name);
    // console.log("Shop Address One: ", shop_address_one);
    // console.log("Shop Address Two: ", shop_address_two);
    // console.log("Shop Number: ", shop_number);

    // console.log("Total Amount: ", total_amount);

    var table = $('#tbl_expense').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "responsive": true,
        "autoWidth": false,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'pdfHtml5',
                customize: function (doc) {
                    // Adjusting the PDF header
                    doc.styles.title = {
                        color: 'black',
                        fontSize: '14',
                        alignment: 'center'
                    };
                    doc.styles.tableHeader = {
                        bold: true,
                        fontSize: 11,
                        color: 'black',
                        fillColor: '#d3d3d3'
                    };
                    doc.content.splice(0, 1, {
                        text: [
                            { text: shop_name + '\n', bold: true, fontSize: 18 },
                            { text: shop_address_one + '', fontSize: 14 },
                            { text: shop_address_two + '\n', fontSize: 14 },
                            { text: shop_city + '\n', fontSize: 14 },
                            { text: shop_number + '\n', fontSize: 14 },
                            { text: 'Total Amount: ' + total_amount + '\n', fontSize: 14 }
                        ],
                        margin: [0, 0, 0, 12],
                        alignment: 'center'
                    });
                }
            },
            {
                extend: 'print',
                customize: function (win) {
                    var formattedCity = shop_city.charAt(0).toUpperCase() + shop_city.slice(1).toLowerCase();
    
                    // Ensure shop_number is appropriately formatted
                    var formattedNumber = shop_number;
                
                    $(win.document.body)
                        .css('font-size', '10pt')
                        .prepend(
                            '<div style="text-align: center; margin-bottom: 20px;">' +
                            '<h2>' + shop_name + '</h2>' +
                            '<p style="margin-bottom: 5px;">' + shop_address_one + ', ' + shop_address_two + '</p>' +
                            '<p style="margin-bottom: 5px;">' + formattedCity + '</p>' +  // Reduced margin
                            '<p style="margin-bottom: 5px;">' + formattedNumber + '</p>' +  // Reduced margin
                            '</div>'
                        );
                    $(win.document.body).find('table')
                        .after('<p style="text-align: center;"><br>Total Amount: ' + totalAmount + '</p>');

                }
            },
            {
                extend: 'excelHtml5',
                title: '',
                customize: function (xlsx) {
                    var sheet = xlsx.xl.worksheets['sheet1.xml'];
                    var rows = sheet.getElementsByTagName('row');

                    // Add custom rows
                    var newRows = `
                        <row r="1"><c t="inlineStr"><is><t>Shop Name: ${shop_name}</t></is></c></row>
                        <row r="2"><c t="inlineStr"><is><t>Address Line 1: ${shop_address_one}</t></is></c></row>
                        <row r="3"><c t="inlineStr"><is><t>Address Line 2: ${shop_address_two}</t></is></c></row>
                        <row r="4"><c t="inlineStr"><is><t>City: ${shop_city}</t></is></c></row>
                        <row r="5"><c t="inlineStr"><is><t>Contact Details: ${shop_number}</t></is></c></row>
                        <row r="6"><c t="inlineStr"><is><t>Total Amount: ${total_amount}</t></is></c></row>
                    `;

                    var parser = new DOMParser();
                    var xmlDoc = parser.parseFromString(newRows, 'text/xml');
                    var newRowElements = xmlDoc.getElementsByTagName('tr');

                    // Append new rows to the beginning of the sheet
                    for (var i = newRowElements.length - 1; i >= 0; i--) {
                        sheet.insertBefore(newRowElements[i], rows[0]);
                    }
                }
            },
            {
                extend: 'csvHtml5',
                customize: function (csv) {
                    return 'Shop Name: ' + shop_name + '\n' +
                        'Address: ' + shop_address_one + ', ' + shop_address_two + '\n' +
                        'City: ' + shop_city + '\n' +
                        'Contact: ' + shop_number + '\n' +
                        'Total Amount: ' + total_amount + '\n\n' +
                        csv;
                }
            }
        ]
    });

    $(".dt-print-view h1").text("Expense Summary");
    $(".dt-buttons").addClass('w-100 d-flex justify-content-center');
    $(".dt-button").each(function () {
        $(this).addClass('btn btn-default');
        $(this).addClass('w-10');
        $(this).removeClass('dt-button');
    });
});
