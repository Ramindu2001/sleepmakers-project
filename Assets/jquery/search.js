$(document).ready(function(){

//=========== Search Inventory ===============//
$("#searchInput").keyup(function(){
    var txt_search = $(this).val();

    setTimeout(() => {
        $.get("../AJAX/Search/searchInventory.php",{
            txt_search : txt_search
        },
        function (data) {
            const obj = JSON.parse(data);

            var row_count = 0;
            var tbl_data = "";

            tbl_data += "<tr>";
            tbl_data += "<th>No<th>";
            tbl_data += "<th>Image<th>";
            tbl_data += "<th>Barcode<th>";
            tbl_data += "<th>Item Name<th>";
            tbl_data += "<th>Current Qty<th>";
            tbl_data += "<th>Sold Qty<th>";
            tbl_data += "<th>Return Qty<th>";
            tbl_data += "<th>Transfer In Qty<th>";
            tbl_data += "<th>Transfer Out Qty<th>";
            tbl_data += "</tr>";

            $.each(obj, function (key, value) { 
                row_count += 1;
                var Barcode = value['Barcode'];
                var ItemName = value['ItemName'];
                var ProdImage = value['ProdImage'];
                var totalCurrentQty = value['totalCurrentQty'];
                var totalBillQty = value['totalBillQty'];
                var totalReturnQty = value['totalReturnQty'];
                var totalTransferIn = value['totalTransferIn'];
                var totalTransferOut = value['totalTransferOut'];

                tbl_data += "<tr>";
                tbl_data += "<td>"+ row_count +"</td>";
                tbl_data += "<td></td>";
                tbl_data += "<td>";
                if(ProdImage != null)
                {
                    tbl_data += "<img src='../Assets/Images/prod_images/"+ ProdImage +"' alt='product image' style='width:auto; height:50px;'>";
                }
                else
                {
                    tbl_data += "<img src='../Assets/Images/icons/product.png' alt='product image' style='width:auto; height:50px;'>";
                }
                tbl_data += "</td>";
                tbl_data += "<td></td>";
                tbl_data += "<td>"+ Barcode +"</td>";
                tbl_data += "<td></td>";
                tbl_data += "<td>"+ ItemName +"</td>";
                tbl_data += "<td></td>";
                tbl_data += "<td>"+ totalCurrentQty +"</td>";
                tbl_data += "<td></td>";
                tbl_data += "<td>"+ totalBillQty +"</td>";
                tbl_data += "<td></td>";
                tbl_data += "<td>"+ totalReturnQty +"</td>";
                tbl_data += "<td></td>";
                tbl_data += "<td>"+ totalTransferIn +"</td>";
                tbl_data += "<td></td>";
                tbl_data += "<td>"+ totalTransferOut +"</td>";
                tbl_data += "<td></td>";
                tbl_data += "</tr>";
            });

            $("#tbl_inventory").html(tbl_data);
            // $("#tbl_inventory").css('width', '100%');
        });
    }, 250);//set time out
    
});

});//search jquery