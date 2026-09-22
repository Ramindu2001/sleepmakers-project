<?php 

$custObj= new Customer;

$Cus_id=1;
$customer=$custObj->getOneCustomer($Cus_id);
?>
<div id="delivery_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-scrollable modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                
                <h4 class="modal-title" id="ModalLabel">Delivery Note</h4>
                <button type="button" class="btn-close" id="close_Prescription_modal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="p_cust_message" class="bg-danger text-light rounded my-1 p-1" style="display: none;"></p>
                    <label for="cmb_customer_delivery" class="form-label">Customer Name/Phone No</label>
                    <div class="input-group">
                        <select name="cmb_customer_delivery" id="cmb_customer_delivery" class="form-select"></select>
                    </div>
                        <!-- hidden input -->
                        <div class="col-md-12 mt-1" id="d" >
                            <form action="../Public/delivery-note.php" method="POST">
                                <div id="invoices" style="margin-top:10px;">

                                </div>
                                <!-- <input type="submit" value="Print Delivery Note" class="btn btn-primary mt-3"> -->
                        </div>                                                             
                    </div>
                    <div class="modal-footer">
                        <input type="submit" value="Print Delivery Note" class="btn bg-primary-subtle text-primary waves-effect">
                            </form>
                        <!-- Close -->
                        <button type="button" class="btn bg-warning-subtle text-warning  waves-effect" data-bs-dismiss="modal" id="close_Customer_modal">Close</button>
                    </div>
            </div>       
        </div>
    </div>
<script>
    $("#cmb_customer_delivery").select2({
        dropdownParent: $('#delivery_modal'),
        ajax:{
            url: '../AJAX/DeliveryNote/getCustomers.php',
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
    });
    $("#cmb_customer_delivery").on("change",function(){
        var customer_id=$(this).val();
        $("#invoices").load("../Public/get_customers_delivery.php?customer_id="+customer_id);

    })
    $("#invoices").load("../Public/get_customers_delivery.php");
</script>