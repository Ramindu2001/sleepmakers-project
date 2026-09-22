$(document).ready(function(){

    //open modal
    $("#btn_add_company").click(function(){
        $("#company_modal").modal('show');

        //show and hide
        $("#btn_update_company").css('display', 'none');
        $("#btn_save_company").css('display', 'block');

        //disable
        $("#btn_update_company").attr('disabled', true);
        $("#btn_save_company").attr('disabled', false);

        //clear modal
        $("#com_name").val("");
        $("#com_location").val("");
        $("#com_licence").val("");
        $("#com_version").val("");
        $("#com_start_date").val("");
        $("#com_expire_date").val("");
        
        $("#chk_multi_category").attr('checked', false);
        $("#chk_commonStock_category").attr('checked', false);
        $("#chk_active_company").attr('checked', false);

        $("#img_com_logo").attr("src", "../Assets/Images/synnex_logo.png");
    });//open modal

    $("#close_company_modal").click(function(){
        $("#company_modal").fadeOut();
        $("#company_modal").modal('hide');
    });//close modal

    $("#tbl_company").on('mousedown', 'tr', function(){
        var currentRow = $(this).closest('tr');
        var row_id = currentRow.find('td').eq(0).html();

        var comName = currentRow.find('td').eq(3).html();
        var comTypeID = currentRow.find('td').eq(9).html();
        var comLocation = currentRow.find('td').eq(10).html();
        var comLicence = currentRow.find('td').eq(11).html();
        var comVersion = currentRow.find('td').eq(5).html();
        var comStart = currentRow.find('td').eq(12).html();
        var comExpire = currentRow.find('td').eq(6).html();
        var comMultiCat = currentRow.find('td').eq(13).html();
        var comActive = currentRow.find('td').eq(14).html();
        var comLogo = currentRow.find('td').eq(15).html();
        var is_commonStock = currentRow.find('td').eq(16).html();

        $("#btn_company_" + row_id).click(function(){
            $("#company_modal").modal('toggle');
            $("#hide_com_id").val(row_id);

            //show and hide
            $("#btn_update_company").css('display', 'block');
            $("#btn_save_company").css('display', 'none');

            //disable
            $("#btn_update_company").attr('disabled', false);
            $("#btn_save_company").attr('disabled', true);

            $("#cmb_company_type").val(comTypeID);
            $("#com_name").val(comName);
            $("#com_location").val(comLocation);
            $("#com_licence").val(comLicence);
            $("#com_version").val(comVersion);
            $("#com_start_date").val(comStart);
            $("#com_expire_date").val(comExpire);

            //multi category
            if(comMultiCat == 1)
            {
                $("#chk_multi_category").attr('checked', true);
            }
            else
            {
                $("#chk_multi_category").attr('checked', false);
            }
            if(is_commonStock == 1)
            {
                $("#chk_commonStock_category").attr('checked', true);
            }
            else
            {
                $("#chk_commonStock_category").attr('checked', false);
            }

            //active company
            if(comActive == 1)
            {
                $("#chk_active_company").attr('checked', true);
            }
            else
            {
                $("#chk_active_company").attr('checked', false);
            }

            if(comLogo == "")
            {
                var imagePath = "../Assets/Images/synnex_logo.png";
                $("#img_com_logo").attr("src", imagePath);
            }
            else
            {
                var imagePath = "../Assets/Images/Company_Logos/" + comLogo;
                $("#img_com_logo").attr("src", imagePath);
            }
        });//edit button click
    });//table row mousedown

    //load image
    $("#company_logo").change(function(event){
        var size=this.files[0].size;
        if (size>=5000000) 
        {
            $("#success").fadeOut();
            $("#danger").fadeIn();
            $(this).css("border-color","red");
            $('#company_save').attr('disabled','disabled');
        }//greater than 5 mb
        else
        {
            $("#success").fadeIn();
            $("#danger").fadeOut();
            $(this).css("border-color","green");
            $('#company_save').removeAttr('disabled', 'disabled');

            var url = URL.createObjectURL(event.target.files[0]);
            $("#img_com_logo").attr("src", url);
        }//less than 5mb
    });//image change

    //validate start date
    $("#com_start_date").change(function(){
        var startDate = $(this).val();
        startDate = new Date(startDate);

        var d = new Date();
        var currentDate = d.getFullYear() + "-" + (d.getMonth()+1) + "-" + d.getDate();
        currentDate = new Date(currentDate);

        var diff = new Date(currentDate - startDate);
        var days = diff/1000/60/60/24;
        
        if(days >= -1)
        {
            $("#start_danger").fadeOut();
            $(this).css('border-color', 'green');

            var expireDate = $("#com_expire_date").val();
            expireDate = new Date(expireDate);
            if(Date.parse(expireDate))
            {
                var diff = new Date(expireDate - startDate);
                var expDays = diff/1000/60/60/24;
                if(expDays < 0)
                {
                    $("#start_danger").fadeIn();
                    $(this).css('border-color', 'red');
                    $(this).val(currentDate);
                }//previous day
            }//has expire date
            else
            {
                $("#com_expire_date").focus();
                $("#com_expire_date").css('border-color', 'blue');
            }//no expire date

        }//future date
        else
        {
            $("#start_danger").fadeIn();
            $(this).css('border-color', 'red');
            $(this).val(currentDate);
        }

    });//start date changed

    $("#com_expire_date").change(function(){
        var startDate = $("#com_start_date").val();
        startDate = new Date(startDate);

        var expireDate = $(this).val();
        expireDate = new Date(expireDate);

        var diff = new Date(expireDate - startDate);
        var days = diff/1000/60/60/24;

        if(Date.parse(startDate))
        {
            if(days >= 0)
            {
                $("#expire_danger").fadeOut();
                $(this).css('border-color', 'green');
            }//has days
            else
            {
                $("#expire_danger").fadeIn();
                $(this).css('border-color', 'red');
                $(this).val(startDate.toString());
            }//nodays
        }
        else
        {
            $("#start_danger").fadeIn();
            $("#com_start_date").css('border-color', 'red');
            $("#com_start_date").val(currentDate.toString());
        }

    });//expire date changed

});//comapny jQuery