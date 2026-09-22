$(document).ready(function(){
    $("#btn_open_unit").click(function(){
        $("#units_modal").modal('toggle');

        $("#btn_delete_units").css('display', 'none');
        $("#btn_delete_units").attr('disabled', true);

        $("#btn_update_unit").css('display', 'none');
        $("#btn_update_unit").attr('disabled', true);

        $("#btn_save_unit").css('display', 'block');
        $("#btn_save_unit").attr('disabled', false);

        $("#unit_name").val("");
        $("#short_name").val("");
    });//open unit modal

    $("#close_units_modal").click(function(){
        $("#units_modal").modal('hide');
    });//close modal

    $("#tbl_unit").on('click', '.btn_edit_unit', function(){
        let row = $(this).closest('tr');
	    let id = row.data('id');

        $.get("../AJAX/Units/getOneUnit.php", {
            unit_id : id
        }, function(data){
            const obj = JSON.parse(data);

            $("#units_modal").modal('toggle');
            $("h4#lbl_modal_title").text("Edit Unit");

            var unit_name = obj[0]['UnitName'];
            var short_name = obj[0]['ShortName'];

            $("#hide_unit_id").val(id);
            $("#unit_name").val(unit_name);
            $("#short_name").val(short_name);

            //hide buttons
            $("#btn_delete_units").css('display', 'none');
            $("#btn_delete_units").attr('disabled', true);

            $("#btn_update_unit").css('display', 'block');
            $("#btn_update_unit").attr('disabled', false);

            $("#btn_save_unit").css('display', 'none');
            $("#btn_save_unit").attr('disabled', true);

        });
    });//edit unit

    $("#tbl_unit").on('click', '.btn_delete_unit', function(){
        let row = $(this).closest('tr');
	    let id = row.data('id');

        $.get("../AJAX/Units/getOneUnit.php", {
            unit_id : id
        }, function(data){
            const obj = JSON.parse(data);

            $("#units_modal").modal('toggle');
            $("h4#lbl_modal_title").text("Delete Unit");

            var unit_name = obj[0]['UnitName'];
            var short_name = obj[0]['ShortName'];

            $("#hide_unit_id").val(id);
            $("#unit_name").val(unit_name);
            $("#short_name").val(short_name);

            //hide buttons
            $("#btn_delete_units").css('display', 'block');
            $("#btn_delete_units").attr('disabled', false);

            $("#btn_update_unit").css('display', 'none');
            $("#btn_update_unit").attr('disabled', true);

            $("#btn_save_unit").css('display', 'none');
            $("#btn_save_unit").attr('disabled', true);

        });
    });//delete unit

});//unit jQuery