$(document).ready(function () {

    var html = $("#tbl_invoice_list").html();
    $("#invoiceSrchBtn").click(function(){
        var txt_search = $("#search_invoice").val(); // Trim to remove extra spaces
        var tdate = $("#tdate").val();
        var fdate = $("#fdate").val();
        if(txt_search !== "" && txt_search !== undefined) {
            $.get("../AJAX/Invoice/getReceiptPrint.php", {
                invoice_no: txt_search,
                tdate: tdate,
                fdate: fdate
            }, function(data){
                $("#tbl_invoice_list").html(data);
            });
        } else {
            $("#search_invoice").focus();
            $("#search_invoice").css("border-color", "red");
                $("#tbl_invoice_list").html(html);
            setTimeout(function(){
                $("#search_invoice").css("border-color", "#DFE5EF");
            }, 2000);
        }
    });

});
