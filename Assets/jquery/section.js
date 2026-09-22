$(document).ready(function(){
    $("#btn_open_section").click(function(){
        $("#section_modal").modal('toggle');

        $("#section_no").css('display', 'none');
        $("#lbl_section").css('display', 'none');

        $("#btn_delete_section").css('display', 'none');
        $("#btn_delete_section").attr('disabled', true);

        $("#btn_update_section").css('display', 'none');
        $("#btn_update_section").attr('disabled', true);

        $("#btn_save_section").css('display', 'block');  
        $("#btn_save_section").attr('disabled', false);

    });//open section modal

    $("#close_section_modal").click(function(){
        $("#section_modal").modal('hide');
    });//close modal

    $("#tbl_section").on('click', '.btn_edit_section', function(){
        let row = $(this).closest('tr');
        let id = row.data('id');

        $.get("../AJAX/Sections/getOneSection.php", {
            section_id: id
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);

            var section_name = obj[0]['SectionName'];
            var section_no = obj[0]['SectionNo'];

            $("#section_modal").modal('toggle');

            $("#hide_section_id").val(id);
            $("#section_no").val(section_no);
            $("#section_name").val(section_name);

            $("#btn_delete_section").css('display', 'none');
            $("#btn_delete_section").attr('disabled', true);
    
            $("#btn_update_section").css('display', 'block');
            $("#btn_update_section").attr('disabled', false);
    
            $("#btn_save_section").css('display', 'none');  
            $("#btn_save_section").attr('disabled', true);

        });//get section
    });//edit section

    $("#tbl_section").on('click', '.btn_delete_section', function(){
        let row = $(this).closest('tr');
        let id = row.data('id');

        $.get("../AJAX/Sections/getOneSection.php", {
            section_id: id
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);

            var section_name = obj[0]['SectionName'];
            var section_no = obj[0]['SectionNo'];

            $("#section_modal").modal('toggle');

            $("#hide_section_id").val(id);
            $("#section_no").val(section_no);
            $("#section_name").val(section_name);

            $("#btn_delete_section").css('display', 'block');
            $("#btn_delete_section").attr('disabled', false);
    
            $("#btn_update_section").css('display', 'none');
            $("#btn_update_section").attr('disabled', true);
    
            $("#btn_save_section").css('display', 'none');
            $("#btn_save_section").attr('disabled', true);

        });//get section
    });//delete section

    // $("#tbl_section").on('mousedown', 'tr', function(){
    //     var currentRow = $(this).closest('tr');
    //     var row_id = currentRow.find('td').eq(0).html();

    //     var sectionNo = currentRow.find('td').eq(2).html();
    //     var sectionName = currentRow.find('td').eq(3).html();

    //     $("#btn_section_" + row_id).click(function(){
    //         $("#section_modal").modal('toggle');

    //         $("#hide_section_id").val(row_id);
            
    //         $("#section_no").val(sectionNo);
    //         $("#section_name").val(sectionName);

    //         $("#section_no").css('display', 'block');
    //         $("#lbl_section").css('display', 'block');
    
    //         $("#btn_update_section").css('display', 'block');
    //         $("#btn_save_section").css('display', 'none');
    
    //         $("#btn_update_section").attr('disabled', false);
    //         $("#btn_save_section").attr('disabled', true);
    //     });//click
    // });//row mouse down

//================================== Racks ====================================//
    $("#btn_open_racks").click(function(){
        $("#racks_modal").modal('toggle');

        $("#rack_no").css('display', 'none');
        $("#lblracks").css('display', 'none');

        $("#btn_delete_rack").css('display', 'none');
        $("#btn_delete_rack").attr('disabled', true);

        $("#btn_update_rack").css('display', 'none');
        $("#btn_update_rack").attr('disabled', true);
        
        $("#btn_save_rack").css('display', 'block');
        $("#btn_save_rack").attr('disabled', false);
    });//open section

    $("#close_racks_modal").click(function(){
        $("#racks_modal").modal('hide');
    });//hide modal

    $("#tbl_racks").on('click', '.btn_edit_rack', function(){
        let row = $(this).closest('tr');
        let id = row.data('id');

        $.get("../AJAX/Sections/getOneRack.php", {
            rack_id : id
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);
            var rackNo = obj[0]['RackNo'];
            var section_id = obj[0]['Sections_SEID'];
            var rackName = obj[0]['RackName'];

            $("#racks_modal").modal('toggle');
            $("#hide_rack_id").val(id);

            $("#rack_no").val(rackNo);
            $("#cmb_sections").val(section_id);
            $("#rack_name").val(rackName);

            $("#rack_no").css('display', 'block');
            $("#lblracks").css('display', 'block');

            $("#btn_delete_rack").css('display', 'none');
            $("#btn_delete_rack").attr('disabled', true);
    
            $("#btn_update_rack").css('display', 'block');
            $("#btn_update_rack").attr('disabled', false);
            
            $("#btn_save_rack").css('display', 'none');
            $("#btn_save_rack").attr('disabled', true);
        });

    });//edit racks

    $("#tbl_racks").on('click', '.btn_delete_rack', function(){
        let row = $(this).closest('tr');
        let id = row.data('id');

        $.get("../AJAX/Sections/getOneRack.php", {
            rack_id : id
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);
            var rackNo = obj[0]['RackNo'];
            var section_id = obj[0]['Sections_SEID'];
            var rackName = obj[0]['RackName'];

            $("#racks_modal").modal('toggle');
            $("#hide_rack_id").val(id);

            $("#rack_no").val(rackNo);
            $("#cmb_sections").val(section_id);
            $("#rack_name").val(rackName);

            $("#rack_no").css('display', 'block');
            $("#lblracks").css('display', 'block');

            $("#btn_delete_rack").css('display', 'block');
            $("#btn_delete_rack").attr('disabled', false);
    
            $("#btn_update_rack").css('display', 'none');
            $("#btn_update_rack").attr('disabled', true);
            
            $("#btn_save_rack").css('display', 'none');
            $("#btn_save_rack").attr('disabled', true);
        });
    });

    // $("#tbl_racks").on('mousedown', 'tr', function(){
    //     var currentRow = $(this).closest('tr');
    //     var row_id = currentRow.find('td').eq(0).html();

    //     var section_id = currentRow.find('td').eq(4).html();
    //     var rackName = currentRow.find('td').eq(3).html();
    //     var rackNo = currentRow.find('td').eq(1).html();

    //     $("#btn_rack_" + row_id).click(function(){
    //         $("#racks_modal").modal('toggle');

    //         $("#hide_rack_id").val(row_id);
    //         $("#rack_no").val(rackNo);
    //         $("#cmb_sections").val(section_id);
    //         $("#rack_name").val(rackName);

    //         $("#rack_no").css('display', 'block');
    //         $("#lblracks").css('display', 'block');

    //         $("#btn_update_rack").css('display', 'block');
    //         $("#btn_save_rack").css('display', 'none');

    //         $("#btn_update_rack").attr('disabled', false);
    //         $("#btn_save_rack").attr('disabled', true);
    //     });//edit button click
    // });//row mouse down

});//section jQuery