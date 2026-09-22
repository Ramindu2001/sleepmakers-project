<div id="customer_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">

            <h4 class="modal-title" id="ModalLabel">Add New Customer</h4>
            <button type="button" class="btn-close" id="close_Customer_modal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="p_cust_message" class="bg-danger text-light rounded my-1 p-1" style="display: none;"></p>

                    <div class="row">
                        <!-- hidden input -->
                        <input type="hidden" name="hide_customer_id" id="hide_customer_id" value="0">
                           
                        <!-- <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">Customer No </label>    
                                <input type="text" name="CusNo" id="CusNo" placeholder="CU_000000" style="width:100%;" class="form-control mb-2"  disabled>
                            </div>
                        </div> -->
                        
                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">Customer Name </label>                                   
                                <input type="text" name="cust_name" id= "cust_name" placeholder="" style="width:100%;" class="form-control mb-2" required>
                            </div>
                        </div>
                                                               
                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">Contact </label>                                   
                                <input type="text" name="cust_contact" id= "cust_contact" placeholder="0712345678" pattern="^\+?\d{1,3}[-.\s]?\(?\d{1,4}\)?[-.\s]?\d{1,4}[-.\s]?\d{1,4}$" title="Please enter a valid contact number" style="width:100%;" class="form-control mb-2" required>
                                <span id="contact_invalid" class="text-danger" style="display: none;">Not a valid contact no.</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">Address </label>                                   
                                <input type="text" name="cust_address" id="cust_address" placeholder="" class="form-control mb-2" >
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">Max Credit Amount </label>                                   
                                <input type="number" step="0.01" name="max_credit" id="max_credit" placeholder="0.00" class="form-control mb-2" >
                                <span id="credit_invalid" class="text-danger" style="display: none;">Not a valid amount.</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">Payment Term (Credit payment duration in Days)</label>                                   
                                <input type="number" name="payment_term" id="payment_term" placeholder="60 days" class="form-control mb-2" >
                                <span id="days_invalid" class="text-danger" style="display: none;">Not a valid number of days.</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label" for=""> Customer Status</label>
                            </div>

                            <div class="m-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" name="cust_stat" type="checkbox" id="cust_stat">
                                    <label class="form-check-label" for="cust_stat">Active</label>
                                </div>
                            </div>
                        </div>
                                                           
                    </div>
                    <div class="modal-footer">
                        <!-- Delete customer -->
                        <!-- <button type="submit" name="btn_delete_customer" class="btn bg-danger-subtle text-danger waves-effect" data-bs-dismiss="modal" id="btn_delete_customer">Delete</button> -->
                        <!-- update customer -->
                        <!-- <button type="submit" name="btn_update_customer" class="btn bg-primary-subtle text-primary waves-effect" data-bs-dismiss="modal" id="btn_update_customer">Update</button> -->
                        <!-- save customer -->
                        <button type="submit" name="btn_save_customer" id="btn_save_customer" class="btn bg-primary-subtle text-primary waves-effect">Save</button>
                        <!-- Close -->
                        <button type="button" class="btn bg-warning-subtle text-warning  waves-effect" data-bs-dismiss="modal" id="close_Customer_modal">Close</button>

                    </div>
                    
            </div>       
        </div>
    </div>
</div>
