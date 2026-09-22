$(document).ready(function(){
    $("#btn_add_category").click(function(){
        $("#category_modal").modal('toggle');
        //focus on text
        $("#cat_name").val("");
        $("#cat_code").val("");
        $("#cat_name").focus();

        $("#lbl_category").css('display', 'none')
        $("#cat_no").css('display', 'none');

        $("h4#myModalLabel").text("Add New Category");

        $("#btn_save_category").css('display', 'block');
        $("#btn_save_category").attr('disabled', false);

        $("#btn_update_category").css('display', 'none');
        $("#btn_update_category").attr('disabled', true);

        $("#btn_delete_category").css('display', 'none');
        $("#btn_delete_category").attr('disabled', true);
    });//open category modal

    $("#close_category_modal").click(function(){
        $("#category_modal").modal('hide');
    });//close category modal

    $("#cat_name").change(function(){
        var catName = $(this).val();
        $.get("../AJAX/AjaxCategory/getDupliCategory.php", {
            cat_name: catName
        }, function(data){
            var catCount = data;
            if(catCount > 0)
            {
                $("#category_danger").fadeIn();
                $("#cat_name").css("border-color","red");
                $("#cat_name").val("");
                $("#category_save").attr('disabled', true);
            }//has category
        });
    });//category name changed

    var row_id = 0;
    $("#tbl_category").on('mousedown', 'tr', function(){
        var currentRow = $(this).closest('tr');
        row_id = currentRow.find('td').eq(0).html();

        var CategoryNo = currentRow.find('td').eq(2).html();
        var CategoryName = currentRow.find('td').eq(3).html();

        /* Column 4 is the barcode code. It renders as a badge when a code is
           stored and as the word "auto" when the generator derives one, so an
           "auto" cell has to come back as an empty box - not as the text. */
        var CategoryCode = $.trim(currentRow.find('td').eq(4).text());
        if (CategoryCode === "auto") {
            CategoryCode = "";
        }

        $("#btn_category_" + row_id).click(function(){
            $("#category_modal").modal('toggle');
            if(row_id > 0)
            {
                $("#cat_name").val(CategoryName);
                $("#cat_code").val(CategoryCode);
                $("#hide_category_id").val(row_id);

                $("#lbl_category").css('display', 'block')
                $("#cat_no").css('display', 'block');
                $("#cat_no").val(CategoryNo);

                $("h4#myModalLabel").text("Edit Category");

                $("#btn_save_category").css('display', 'none');
                $("#btn_save_category").attr('disabled', true);

                $("#btn_update_category").css('display', 'block');
                $("#btn_update_category").attr('disabled', false);

                $("#btn_delete_category").css('display', 'none');
                $("#btn_delete_category").attr('disabled', true);
            }//has values
        });//row button click edit

        $("#btn_category_delete_" + row_id).click(function(){
            $("#category_modal").modal('toggle');
            if(row_id > 0)
            {
                $("#cat_name").val(CategoryName);
                $("#cat_code").val(CategoryCode);
                $("#hide_category_id").val(row_id);

                $("#lbl_category").css('display', 'block');
                $("#cat_no").css('display', 'block');
                $("#cat_no").val(CategoryNo);

                $("h4#myModalLabel").text("Delete Category");

                $("#btn_save_category").css('display', 'none');
                $("#btn_save_category").attr('disabled', true);

                $("#btn_update_category").css('display', 'none');
                $("#btn_update_category").attr('disabled', true);

                $("#btn_delete_category").css('display', 'block');
                $("#btn_delete_category").attr('disabled', false);
            }//has values
        });//delete category
    });//table category mousedown

    $("#close_category_modal").click(function(){
        $("#category_modal").modal('hide');
    });//close category

//========================================== Sub Category ======================================//
    $("#btn_open_subcategory").click(function(){
        $("#subcategory_modal").modal('toggle');

        $("#subcat_name").val("");
        $("#subcat_code").val("");



        $("#lblSubcategory").css('display', 'none');
        $("#subcat_no").css('display', 'none');

        $("h4#myModalLabel").text("Add New Subcategory");

        $("#btn_save_subcat").css('display', 'block');
        $("#btn_save_subcat").attr('disabled', false);

        $("#btn_update_subcat").css('display', 'none');
        $("#btn_update_subcat").attr('disabled', true);

        $("#btn_delete_subcat").css('display', 'none');
        $("#btn_delete_subcat").attr('disabled', true);
    });//open sub category modal

    $("#close_subcategory_modal").click(function(){
        $("#subcategory_modal").modal('hide');
    });//cloase subcategory modal

    //edit subcategory
    $("#tbl_subcategory").on('click', '.btn_edit_subcat', function(){
        let row = $(this).closest('tr');
	    let id = row.data('id');

        $.get("../AJAX/AjaxCategory/getOneSubcategory.php", {
            subcat_id: id
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);

            var category_id = obj[0]['categories_CTID'];
            var subcategory_no = obj[0]['SubCatNo'];
            var subcate_name = obj[0]['SubCatName'];
            var subcate_code = obj[0]['SubCatCode'] || "";

            $("#subcategory_modal").modal('toggle');

            $("#lblSubcategory").css('display', 'block');
            $("#subcat_no").css('display', 'block');

            $("#hide_subcat_id").val(id);
            $("#cmb_main_category").val(category_id);
            $("#subcat_no").val(subcategory_no);
            $("#subcat_name").val(subcate_name);
            $("#subcat_code").val(subcate_code);

            $("h4#myModalLabel").text("Update Subcategory");

            $("#btn_save_subcat").css('display', 'none');
            $("#btn_save_subcat").attr('disabled', true);

            $("#btn_update_subcat").css('display', 'block');
            $("#btn_update_subcat").attr('disabled', false);

            $("#btn_delete_subcat").css('display', 'none');
            $("#btn_delete_subcat").attr('disabled', true);
        });

    });//edit subcategory

    //delete subcategory
    $("#tbl_subcategory").on('click', '.btn_delete_subcat', function(){
        let row = $(this).closest('tr');
	    let id = row.data('id');

        $.get("../AJAX/AjaxCategory/getOneSubcategory.php", {
            subcat_id: id
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);

            var category_id = obj[0]['categories_CTID'];
            var subcategory_no = obj[0]['SubCatNo'];
            var subcate_name = obj[0]['SubCatName'];
            var subcate_code = obj[0]['SubCatCode'] || "";

            $("#subcategory_modal").modal('toggle');

            $("#lblSubcategory").css('display', 'block');
            $("#subcat_no").css('display', 'block');

            $("#hide_subcat_id").val(id);
            $("#cmb_main_category").val(category_id);
            $("#subcat_no").val(subcategory_no);
            $("#subcat_name").val(subcate_name);
            $("#subcat_code").val(subcate_code);

            $("h4#myModalLabel").text("Delete Subcategory");

            $("#btn_save_subcat").css('display', 'none');
            $("#btn_save_subcat").attr('disabled', true);

            $("#btn_update_subcat").css('display', 'none');
            $("#btn_update_subcat").attr('disabled', true);

            $("#btn_delete_subcat").css('display', 'block');
            $("#btn_delete_subcat").attr('disabled', false);
        });
    });//delete sub cat

});//jQuery Category