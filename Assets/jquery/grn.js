$(document).ready(function(){
    //supplier select option
    $("#cmb_supplier").select2({
        dropdownParent: $("#grn_modal"),
        width: '100%' 
    });
    
    $("#btn_open_grn").click(function(){
        $("#grn_modal").modal('toggle');

        //get grn no
        $.get("../AJAX/GRN/getGRNNo.php", 
        function(data){
            $("#grn_no").val(data);
        });//get grn no

        var d = new Date();
        var strDate = d.getFullYear() + "-" + (d.getMonth()+1) + "-" + d.getDate();
        $("#grn_date").val(strDate);

    });//open grn modal
    $("#close_grn_modal").click(function(){
        $("#grn_modal").modal('toggle');
    });//close grn modal

});//grn jquery