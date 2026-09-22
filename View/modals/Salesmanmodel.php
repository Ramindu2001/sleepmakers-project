<div id="Salesman_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="NewSalesman"> Add New Salesman</h4>
               
                <button type="button" class="btn-close" id="close_Salesman_modal" data-bs-dismiss="modal" aria-label="Close"></button>
            
            </div>
            <div class="modal-body">
                <form action="../Controller/Salesmancontroller.php" method="POST">
                    <!-- hidden input -->
                    <input type="hidden" name="hide_salesman_id" id="hide_salesman_id" value="0">
                    
                    <div class="m-2">
                        <label class="form-label">Salesman No</label>    
                        <input type="text" name="SalNo" id="SalNo" placeholder="SM_000000" class="form-control mb-2" disabled>
                    </div>
                    
                    <div class="m-2">
                        <label class="form-label">Salesman Name</label>                                   
                        <input type="text" name="SalsName" id= "SalsName" placeholder="" class="form-control mb-2" required>
                    </div>
                            
                    <div class="m-2">
                        <label class="form-label">Contact</label>                                   
                        <input type="text" name="Contact" id= "Contact" placeholder="0712345678" pattern="^\+?\d{1,3}[-.\s]?\(?\d{1,4}\)?[-.\s]?\d{1,4}[-.\s]?\d{1,4}$" title="Please enter a valid contact number" class="form-control mb-2" required>
                        <span class="text-danger" id="contact_warning" style="display: none;">Not a valid contact no.</span>
                    </div>

                    <div class="m-2">
                        <label class="form-label">Commision Percentage</label>                                   
                        <input type="number" step="0.01" name="commision_rate" id= "commision_rate" placeholder="0.00%" class="form-control mb-2">
                        <span class="text-danger" id="commision_warning" style="display: none;">Please enter valid percentage.</span>
                    </div>

                    <div class="m-2">
                        <label class="form-label" for="">Salesman Status</label>
                    </div>

                    <div class="m-2">
                        <div class="form-check">
                            <input class="form-check-input" name="SalesmanStat" type="checkbox" id="SalesmanStat" checked>
                            <label class="form-check-label" for="SalesmanStat">
                                Active
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <!-- Delete -->
                        <button type="submit" name="btn_delete_salesman" id="btn_delete_salesman" class="btn bg-danger-subtle text-danger waves-effect">
                            Delete
                        </button>

                        <!-- update -->
                        <button type="submit" name="btn_Update_Salesman" id="btn_Update_Salesman" class="btn bg-primary-subtle text-primary waves-effect">
                            Update
                        </button>

                        <!-- save -->
                        <button type="submit" name="btn_save_Salesman" id="btn_save_Salesman" class="btn bg-primary-subtle text-primary waves-effect">
                            Save
                        </button>

                        <!-- close -->
                        <button type="button" class="btn bg-danger-subtle text-danger  waves-effect" data-bs-dismiss="modal" id="close_Salesman_modal"> Close </button>
                        
                    </div>
                    
                </form>
            </div>       
        </div>
    </div>
</div>
