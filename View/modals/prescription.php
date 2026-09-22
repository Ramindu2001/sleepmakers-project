<?php 

$custObj= new Customer;

$Cus_id=1;
$customer=$custObj->getOneCustomer($Cus_id);
?>
<div id="prescription_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-scrollable modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                
                <h4 class="modal-title" id="ModalLabel">Add New Prescription</h4>
                <button type="button" class="btn-close" id="close_Prescription_modal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="p_cust_message" class="bg-danger text-light rounded my-1 p-1" style="display: none;"></p>
                    <label for="cmb_customer2" class="form-label">Customer Name/Phone No</label>
                    <div class="input-group">
                        <select name="cmb_customer2" id="cmb_customer2" class="form-select"></select>
                        <input type="hidden" id="hide_customer_id" value="0">
                        <input type="hidden" id="hide_prescription_id" value="0">
                    </div>
                        <!-- hidden input -->
                        <div class="col-md-12 mt-1" id="prescription" >
                            <div class="table-responsive">
                                <table class="table">
                                    <tr>
                                        <td colspan="8">
                                            <p style="display:inline-block;"><b>Subjective Ref: </b></p>
                                            <input type="text" name="subjective-ref" id="subjective-ref" class="form-control" style="display:inline-block; width:92%;">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            <b>Right</b>
                                        </td>
                                        <td colspan="4" class="text-center">
                                            <b>Left</b>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-center"><b>Sph.</b></td>
                                        <td class="text-center"><b>Cyl.</b></td>
                                        <td class="text-center"><b>Axis</b></td>
                                        <td class="text-center">VA</td>
                                        <td class="text-center"><b>Sph.</b></td>
                                        <td class="text-center"><b>Cyl.</b></td>
                                        <td class="text-center"><b>Axis</b></td>
                                        <td class="text-center">VA</td>
                                    </tr>
                                    <tr>
                                        <td class="text-center">
                                            <input type="text" name="f-right-sph" id="f-right-sph" class="form-control">
                                        </td>
                                        <td class="text-center">
                                            <input type="text" name="f-right-cyl" id="f-right-cyl" class="form-control">
                                        </td>
                                        <td class="text-center">
                                            <input type="text" name="f-right-axis" id="f-right-axis" class="form-control">
                                        </td>
                                        <td class="text-center">
                                            <input type="text" name="f-right-none" id="f-right-none" class="form-control">
                                        </td>
                                        <td class="text-center">
                                            <input type="text" name="f-left-sph" id="f-left-sph" class="form-control">
                                        </td>
                                        <td class="text-center">
                                            <input type="text" name="f-left-cyl" id="f-left-cyl" class="form-control">
                                        </td>
                                        <td class="text-center">
                                            <input type="text" name="f-left-axis" id="f-left-axis" class="form-control">
                                        </td>
                                        <td class="text-center">
                                            <input type="text" name="f-left-none" id="f-left-none" class="form-control">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><b>Add</b></td>
                                        <td class="text-center">
                                            <input type="text" name="add-f-right-add-cyl" id="add-f-right-add-cyl" class="form-control">
                                        </td>
                                        <td class="text-center">
                                            <input type="text" name="add-f-right-add-axis" id="add-f-right-add-axis" class="form-control">
                                        </td>
                                        <td class="text-center">
                                            <input type="text" name="add-f-right-add-none" id="add-f-right-add-none" class="form-control">
                                        </td>
                                        <td><b>Add</b></td>
                                        <td class="text-center">
                                            <input type="text" name="add-f-left-add-cyl" id="add-f-left-add-cyl" class="form-control">
                                        </td>
                                        <td class="text-center">
                                            <input type="text" name="add-f-left-add-axis" id="add-f-left-add-axis" class="form-control">
                                        </td>
                                        <td class="text-center">
                                            <input type="text" name="add-f-left-add-none" id="add-f-left-add-none" class="form-control">
                                        </td>
                                    </tr>
                                </table>
                                <div class="row" style="width:100%;">
                                    <div class="col-md-6 table-responsive">
                                        <table class="table">
                                            <tr>
                                                <td colspan="3">
                                                        <p style="display:inline-block;"><b>Vision Acuity: </b></p>
                                                        <input type="text" name="vision-acuity" id="vision-acuity" class="form-control" style="display:inline-block; width:92%;">
                                                </td> 
                                            </tr> 
                                            <tr>
                                                <th>Eye</th>
                                                <th>UVA</th>
                                                <th>PH</th>
                                            </tr>
                                            <tr>
                                                <th><b>Right</b></th>
                                                <td>
                                                    <input type="text" name="r-va-uva" id="r-va-uva" class="form-control">
                                                </td>
                                                <td>
                                                    <input type="text" name="r-va-ph" id="r-va-ph" class="form-control">
                                                </td>
                                            </tr>
                                            <tr>
                                                <th><b>Left</b></th>
                                                <td>
                                                    <input type="text" name="l-va-uva" id="l-va-uva" class="form-control">
                                                </td>
                                                <td>
                                                    <input type="text" name="l-va-ph" id="l-va-ph" class="form-control">
                                                </td>
                                            </tr>
                                        </table>
                                        
                                    </div>
                                    <div class="col-md-6">
                                        <label for="remark" class="form-label">Remarks</label>
                                        <textarea name="pres-remarks" id="pres-remarks" class="form-control" style="height:80%;"></textarea>
                                    </div>
                                </div>
                                <table class="table mt-4">
                                    <tr>
                                        <td colspan="6">
                                            <p style="display:inline-block;"><b>Present Prescription: </b></p>
                                            <input type="text" name="prs-hb" id="prs-hb" class="form-control" style="display:inline-block; width:95%;">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-center">
                                            <b>Right</b>
                                        </td>
                                        <td colspan="3" class="text-center">
                                            <b>Left</b>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Sph.</th>
                                        <th>Cyl.</th>
                                        <th>Axis</th>
                                        <th>Sph.</th>
                                        <th>Cyl.</th>
                                        <th>Axis</th>
                                    </tr>
                                    <tr>
                                        <td><input type="text" name="s-right-sph" id="s-right-sph" class="form-control"></td>
                                        <td><input type="text" name="s-right-cyl" id="s-right-cyl" class="form-control"></td>
                                        <td><input type="text" name="s-right-axis" id="s-right-axis" class="form-control"></td>
                                        <td><input type="text" name="s-left-sph" id="s-left-sph" class="form-control"></td>
                                        <td><input type="text" name="s-left-cyl" id="s-left-cyl" class="form-control"></td>
                                        <td><input type="text" name="s-left-axis" id="s-left-axis" class="form-control"></td>
                                    </tr>
                                    <tr>
                                        <td><b>Add</b></td>
                                        <td><input type="text" name="add-s-right-cyl" id="add-s-right-cyl" class="form-control"></td>
                                        <td><input type="text" name="add-s-right-axis" id="add-s-right-axis" class="form-control"></td>
                                        <td><b>Add</b></td>
                                        <td><input type="text" name="add-s-left-cyl" id="add-s-left-cyl" class="form-control"></td>
                                        <td><input type="text" name="add-s-left-axis" id="add-s-left-axis" class="form-control"></td>
                                    </tr>
                                    
                                    <tr>
                                        <td colspan="6">
                                            <p style="display:inline-block;"><b>Refraction By : </b></p>
                                            <input type="text" name="prs-refraction" id="prs-refraction" class="form-control" style="display:inline-block; width:92%;">
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>                                                             
                    </div>
                    <div class="modal-footer">
                        <!-- update -->
                        <button type="button" name="btn_update_prescription" id="btn_update_prescription" class="btn bg-primary-subtle text-primary waves-effect">Update Prescription</button>
                        <!-- save -->
                        <button type="button" name="btn_save_prescription" id="btn_save_prescription" class="btn bg-primary-subtle text-primary waves-effect">Save Prescription</button>
                        <!-- Close -->
                        <button type="button" class="btn bg-warning-subtle text-warning  waves-effect" data-bs-dismiss="modal" id="close_Customer_modal">Close</button>
                    </div>
            </div>       
        </div>
    </div>
<script>
     $("#btn_update_prescription").css('display', 'none');

    $("#btn_save_prescription").click(function(){
        var customer_id = $("#cmb_customer2").val();

        if(customer_id != null && customer_id != 1)
        {
            var allow=true;
            $("#prescription input").each(function(){
                if($(this).val()!="")
                {
                    allow=true;
                    return false;
                }
                else
                {
                    allow=false
                }
            });
            $("#prescription textarea").each(function(){
                if($(this).val()!="")
                {
                    allow=true;
                    return false;
                }
                else
                {
                    allow=true;
                }
            });
            if(allow==true)
            {
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

                $.get("../AJAX/WholeSaleInvoice/setPrescription.php", {
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
                    vision_acuity: vision_acuity
                }, function(data){
                    alert(data);

                    $("#prescription input").each(function(){
                        $(this).val("");
                    });
                    $("#prescription textarea").each(function(){
                        $(this).val("");
                    });
                    $("#prescription_modal").modal("hide")
                });//set prescription
            }
            else
            {
                alert("Please fill atleast one field!");
            }
            
        }//has customer
        else
        {
            alert("Please select a customer!");
            // $("#prescription_modal").modal('hide');
            $("#cmb_customer2").focus();
        }//no customer
    });//save prescription
    $("#cmb_customer2").select2({
        dropdownParent: $('#prescription_modal'),
        ajax:{
            url: '../AJAX/WholeSaleInvoice/getCustomers.php',
            dataType: 'json',
            delay: 250,
            data: function(params){
                console.log(params);
                
                var query = {
                    search: params.term,
                    type: 'item_search'
                };
                return query;
            },
            processResults: function(data){
                return {
                    results: data
                }
            }
        },
        cache: true,
        placeholder: 'Search for Customer',
        minimumInputLength: 1,
        width: '70%',
    });//get customer search
</script>