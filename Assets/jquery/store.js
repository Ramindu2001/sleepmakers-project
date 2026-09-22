$(document).ready(function(){
    $("#tbl_inventory").on('click', '.btn_view_price', function(){
        let row = $(this).closest('tr');
        let id = row.data('id');

        $.get("../AJAX/Store/getBatchPrice.php", {
            product_id: id
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);

            var barcode = obj[0]['Barcode'];
            var product_name = obj[0]['ItemName'];
            var content = "";

            content += "<p style='margin:2px; font-weight:bold;'>Barcode - "+barcode+"<br>";
            content += "Item - " + product_name ;
            content +="</p>";

            content +="<table>";
            content +="<tr>";
            content +="<th>Batch No</th>";
            // content +="<th>Qty</th>";
            content +="<th>Price</th>";
            content +="</tr>";

            $.each(obj, function (key, value) { 
                var batch_id = value['BatchID'];
                var selling_price = value['SellingPrice'];
                var batch_qty = parseFloat(value['CurrentQty']) * 1;

                console.log("batch = " + batch_id +" - "+ selling_price);

                content +="<tr>";
                content +="<td>"+ batch_id +"</td>";
                // content +="<td>"+ batch_qty +"</td>";
                content +="<td>"+ selling_price +"</td>";
                content +="</tr>";

            });

            content +="</table>";

            $("#modal_batch_price").modal('toggle');

            $("#div_batch_price").html(content);
        });
    });//show batch and qty

    $("#close_batch_price").click(function(){
        $("#modal_batch_price").modal('toggle');
    });

});//jQuery