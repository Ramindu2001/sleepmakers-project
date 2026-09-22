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
                        
                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">Customer Name <span class="text text-danger">*</span></label>                                   
                                <input type="text" name="cust_name" id= "cust_name" style="width:100%;" class="form-control mb-2 required" placeholder="Eg: Jhon Fernando" required>
                                <span class="text-danger" id="alrt" style="display: none;">This Field is Required</span>
                            </div>
                        </div>
                                                               
                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">Contact  <span class="text text-danger">*</span></label>                                   
                                <input type="text" name="cust_contact" id= "cust_contact" placeholder="0712345678" pattern="^\+?\d{1,3}[-.\s]?\(?\d{1,4}\)?[-.\s]?\d{1,4}[-.\s]?\d{1,4}$" title="Please enter a valid contact number" style="width:100%;" class="form-control mb-2 required" required>                    
                                <span class="text-danger" id="alrt" style="display: none;">This Field is Required</span>
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
                                <label class="form-label">Date of Birth </label>                                   
                                <input type="date" name="cust_dob" id="cust_dob" placeholder="" class="form-control mb-2" >
                            </div>
                        </div>
                        <div class="col-md-6">
                             
                            <div class="m-2">
                                <label class="form-label">Gender</label> 
                                <select name="gender" id="gender" class="form-select">
                                    <option value="1">Male</option>
                                    <option value="2">Female</option>
                                    <option value="0">Other</option>
                                </select>
                            </div>                            
                        </div>                                                           
                    </div>
                    <div class="modal-footer">
                        <button type="button" name="btn_save_customer" id="btn_save_customer" class="btn bg-primary-subtle text-primary waves-effect">Save</button>
                        <!-- Close -->
                        <button type="button" class="btn bg-warning-subtle text-warning  waves-effect" data-bs-dismiss="modal" id="close_Customer_modal">Close</button>

                    </div>
                    
            </div>       
        </div>
    </div>
</div>
