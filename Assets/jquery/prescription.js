$(document).ready(function () {
    $("#btn_open_prescription").click(function(){
        // alert("open");
        $("#prescription_modal").modal('toggle');
        
        $("#btn_save_prescription").css('display', 'block');
        $("#btn_update_prescription").css('display', 'none');
    });

    $("#tbl_prescription").on('click', '.btn_edit_prescription', function(){
        let row = $(this).closest('tr');
        let id = row.data('id');

        // alert("id - " + id);

        $.get("../AJAX/WholeSaleInvoice/getOnePrescription.php", {
            pres_header_id: id
        }, function(data){
            // alert(data);
            $("#prescription_modal").modal('toggle');
            const obj = JSON.parse(data);

            //subject
            var f_right_sph = 0;
            var f_right_cyl = 0;
            var f_right_axis = 0;
            var f_right_none = 0;

            var f_left_sph = 0;
            var f_left_cyl = 0;
            var f_left_axis = 0;
            var f_left_none = 0;

            var add_f_right_cyl = 0;
            var add_f_right_axis = 0;
            var add_f_right_none = 0;

            var add_f_left_cyl = 0;
            var add_f_left_axis = 0;
            var add_f_left_none = 0;

            //type 2 present
            var s_right_sph = 0;
            var s_right_cyl = 0;
            var s_right_axis = 0;

            var s_left_sph = 0;
            var s_left_cyl = 0;
            var s_left_axis = 0;

            var add_s_right_cyl = 0;
            var add_s_right_axis = 0;

            var add_s_left_cyl = 0;
            var add_s_left_axis = 0;

            $.each(obj, function (key, value) { 
                var side_id = value['side'];
                var type_id = value['prescription_type'];
                var add_id = value['add'];

                if(side_id ==1 && type_id ==1 && add_id == 0)
                {
                    f_right_sph = value['sph'];
                    f_right_cyl = value['cyl'];
                    f_right_axis = value['axis'];
                    f_right_none = value['none'];
                }//right-side subject 

                if(side_id ==2 && type_id ==1 && add_id == 0)
                {
                    f_left_sph = value['sph'];
                    f_left_cyl = value['cyl'];
                    f_left_axis = value['axis'];
                    f_left_none = value['none'];
                }//left-side subject 

                //add
                if(side_id ==1 && type_id ==1 && add_id == 1)
                {
                    add_f_right_cyl = value['cyl'];
                    add_f_right_axis = value['axis'];
                    add_f_right_none = value['none'];
                }//right-side subject add

                if(side_id ==2 && type_id ==1 && add_id ==1)
                {
                    add_f_left_cyl = value['cyl'];
                    add_f_left_axis = value['axis'];
                    add_f_left_none = value['none'];
                }//left-side subject add
                
                //type 2 present 
                if(side_id ==1 && type_id ==2 && add_id == 0)
                {
                    s_right_sph = value['sph'];
                    s_right_cyl = value['cyl'];
                    s_right_axis = value['axis'];
                }//right-side subject 

                if(side_id ==2 && type_id ==2 && add_id == 0)
                {
                    s_left_sph = value['sph'];
                    s_left_cyl = value['cyl'];
                    s_left_axis = value['axis'];
                }//left-side subject 

                if(side_id ==1 && type_id ==2 && add_id == 1)
                {
                    add_s_right_cyl = value['cyl'];
                    add_s_right_axis = value['axis'];
                }//right-side subject 

                if(side_id ==2 && type_id ==2 && add_id == 1)
                {
                    add_s_left_cyl = value['cyl'];
                    add_s_left_axis = value['axis'];
                }//left-side subject 

            });//loop

            $("#f-right-sph").val(f_right_sph);
            $("#f-right-cyl").val(f_right_cyl);
            $("#f-right-axis").val(f_right_axis);
            $("#f-right-none").val(f_right_none);

            $("#f-left-sph").val(f_left_sph);
            $("#f-left-cyl").val(f_left_cyl);
            $("#f-left-axis").val(f_left_axis);
            $("#f-left-none").val(f_left_none);

            $("#add-f-right-add-cyl").val(add_f_right_cyl);
            $("#add-f-right-add-axis").val(add_f_right_axis);
            $("#add-f-right-add-none").val(add_f_right_none);

            $("#add-f-left-add-cyl").val(add_f_left_cyl);
            $("#add-f-left-add-axis").val(add_f_left_axis);
            $("#add-f-left-add-none").val(add_f_left_none);

            //type 2 present
            $("#s-right-sph").val(s_right_sph);
            $("#s-right-cyl").val(s_right_cyl);
            $("#s-right-axis").val(s_right_axis);

            $("#s-left-sph").val(s_left_sph);
            $("#s-left-cyl").val(s_left_cyl);
            $("#s-left-axis").val(s_left_axis);

            $("#add-s-right-cyl").val(add_s_right_cyl);
            $("#add-s-right-axis").val(add_s_right_axis);

            $("#add-s-left-cyl").val(add_s_left_cyl);
            $("#add-s-left-axis").val(add_s_left_axis);

            $.get("../AJAX/WholeSaleInvoice/getPrescriptionVA.php", {
                pres_header_id: id
            }, function(data){
                // alert(data);
                const obj1 = JSON.parse(data);

                var r_va_uva = 0;
                var r_va_ph = 0;

                var l_va_uva = 0;
                var l_va_ph = 0;

                $.each(obj1, function (kay1, value1) { 
                    var eye_id = value1['eye'];
                    if(eye_id == 1)
                    {
                        r_va_uva = value1['uva'];
                        r_va_ph = value1['ph'];
                    }//right eye
                    if(eye_id == 2)
                    {
                        l_va_uva = value1['uva'];
                        l_va_ph = value1['ph'];
                    }//left eye
                });//loop

                $("#r-va-uva").val(r_va_uva);
                $("#r-va-ph").val(r_va_ph);

                $("#l-va-uva").val(l_va_uva);
                $("#l-va-ph").val(l_va_ph);
            });//get prescription VA

            //get prescription header
            $.get("../AJAX/WholeSaleInvoice/getPrescriptionHeader.php", {
                pres_header_id: id
            }, function(data){
                // alert(data);

                const obj2 = JSON.parse(data);
                var subject = obj2[0]['pr_subjective_ref'];
                var pr_vava = obj2[0]['pr_va'];
                var pr_remarks = obj2[0]['pr_remarks'];
                var pr_hb = obj2[0]['pr_hb'];
                var pr_refraction = obj2[0]['pr_refraction'];
                var customer_id = obj2[0]['customer_CTID'];

                $("#subjective-ref").val(subject);
                $("#vision-acuity").val(pr_vava);
                $("#pres-remarks").val(pr_remarks);
                $("#prs-hb").val(pr_hb);
                $("#prs-refraction").val(pr_refraction);

                $("#hide_customer_id").val(customer_id);
                $("#hide_prescription_id").val(id);
            });

            $("#btn_save_prescription").css('display', 'none');
            $("#btn_update_prescription").css('display', 'block');
            

        });//get one prescription
    });

    $("#btn_update_prescription").click(function(){
        var customer_id = $("#hide_customer_id").val();
        var prescription_id = $("#hide_prescription_id").val();
        
        var subjective_ref = $("#subjective-ref").val();

        var f_right_sph = $("#f-right-sph").val();
        var f_right_cyl = $("#f-right-cyl").val();
        var f_right_axis = $("#f-right-axis").val();
        var f_right_none = $("#f-right-none").val();

        var f_left_sph = $("#f-left-sph").val();
        var f_left_cyl = $("#f-left-cyl").val();
        var f_left_axis = $("#f-left-axis").val();
        var f_left_none = $("#f-left-none").val();

        var add_f_right_add_cyl = $("#add-f-right-add-cyl").val();  
        var add_f_right_add_axis = $("#add-f-right-add-axis").val();  
        var add_f_right_add_none = $("#add-f-right-add-none").val();  
        var add_f_left_add_cyl = $("#add-f-left-add-cyl").val();  

        var prs_hb = $("#prs-hb").val();
        var add_f_left_add_axis = $("#add-f-left-add-axis").val();
        var add_f_left_add_none = $("#add-f-left-add-none").val();
        var pres_remarks = $("#pres-remarks").val();

        var s_right_sph = $("#s-right-sph").val();
        var s_right_cyl = $("#s-right-cyl").val();
        var s_right_axis = $("#s-right-axis").val();
        var s_left_sph = $("#s-left-sph").val();
        var s_left_cyl = $("#s-left-cyl").val();
        var s_left_axis = $("#s-left-axis").val();

        var add_s_right_cyl = $("#add-s-right-cyl").val();
        var add_s_right_axis = $("#add-s-right-axis").val();
        var add_s_left_cyl = $("#add-s-left-cyl").val();
        var add_s_left_axis = $("#add-s-left-axis").val();

        var prs_refraction = $("#prs-refraction").val();
        var r_va_uva = $("#r-va-uva").val();
        var r_va_ph = $("#r-va-ph").val();
        var l_va_uva = $("#l-va-uva").val();
        var l_va_ph = $("#l-va-ph").val();
        var vision_acuity = $("#vision-acuity").val();

        $.get("../AJAX/WholeSaleInvoice/editPrescription.php", {
            customer_id: customer_id,
            subjective_ref: subjective_ref,
            f_right_sph: f_right_sph,
            f_right_cyl: f_right_cyl,
            f_right_axis: f_right_axis,
            f_right_none: f_right_none,
            f_left_sph: f_left_sph,
            f_left_cyl: f_left_cyl,
            f_left_axis: f_left_axis,
            f_left_none: f_left_none,
            add_f_right_add_cyl: add_f_right_add_cyl,
            add_f_right_add_axis: add_f_right_add_axis,
            add_f_right_add_none: add_f_right_add_none,
            add_f_left_add_cyl: add_f_left_add_cyl,
            prs_hb: prs_hb, 
            add_f_left_add_axis: add_f_left_add_axis,
            add_f_left_add_none: add_f_left_add_none,
            pres_remarks: pres_remarks,
            s_right_sph: s_right_sph,
            s_right_cyl: s_right_cyl,
            s_right_axis: s_right_axis,
            s_left_sph: s_left_sph,
            s_left_cyl: s_left_cyl,
            s_left_axis: s_left_axis,
            add_s_right_cyl: add_s_right_cyl,
            add_s_right_axis: add_s_right_axis,
            add_s_left_cyl: add_s_left_cyl,
            add_s_left_axis: add_s_left_axis,
            prs_refraction: prs_refraction,
            r_va_uva: r_va_uva,
            r_va_ph: r_va_ph,
            l_va_uva: l_va_uva,
            l_va_ph: l_va_ph,
            vision_acuity: vision_acuity,
            prescription_id: prescription_id
        }, function(data){
            alert(data);

            $("#prescription input").each(function(){
                $(this).val("");
            });
            $("#prescription textarea").each(function(){
                $(this).val("");
            });

            $("#prescription_modal").modal("hide")

        });//edit prescription

    });//update prescription

});//prescription jquery