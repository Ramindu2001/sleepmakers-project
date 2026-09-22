<div id="Supplier_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">Add New Supplier</h4>
                <button type="button" class="btn-close" id="close_Supplier_modal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="../Controller/SupplierController.php" method="POST">
                    <div class="row">   
                        <!-- hidden input -->
                        <input type="hidden" name="hide_supplier_id" id="hide_supplier_id" value="0">

                        <div class="col-md-12">
                            <label class="form-label" id="lbl_supplier_no">Supplier No </label>    
                            <input type="text" name="SuppNo" id="SuppNo" placeholder="SP_000000" class="form-control mb-2 w-50" disabled>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Distributor</label>
                            <input type="text" name="distributer" id="distributer" placeholder="Distributer Name" class="form-control mb-2" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Supplier Name</label>                                   
                            <input type="text" name="SuppName" id="SuppName" placeholder="Supplier Name" style="width:100%;" class="form-control mb-2" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Contact </label>                                   
                            <input type="text" name="Contact" id="Contact" placeholder="071 234 5678" pattern="^\+?\d{1,3}[-.\s]?\(?\d{1,4}\)?[-.\s]?\d{1,4}[-.\s]?\d{1,4}$" title="Please enter a valid contact number" style="width:100%;" class="form-control mb-2" required>
                            <span id="contact_invalid" class="text-danger" style="display: none;">Not Valid Contact No.</span>
                        </div>   

                        <!-- left column -->
                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label" for=""> Supplier Status</label>
                            </div>

                            <div class="m-2">
                                <div class="form-check">
                                    <input class="form-check-input" name="SuppStatus" id="SuppStatus" type="checkbox">
                                    <label class="form-check-label" for="SuppStatus">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>                       
                    </div>

                    <div class="modal-footer">
                        <!-- delete -->
                        <button type="submit" name="btn_delete_supplier" class="btn bg-danger-subtle text-danger waves-effect" data-bs-dismiss="modal" id="btn_delete_supplier">
                            Delete
                        </button>
                        <!-- update -->
                        <button type="submit" name="btn_Update_Supplier" class="btn bg-primary-subtle text-primary waves-effect" data-bs-dismiss="modal" id="btn_Update_Supplier">
                            Update
                        </button>
                        <!-- save -->
                        <button type="submit" name="btn_save_Supplier" class="btn bg-primary-subtle text-primary waves-effect" data-bs-dismiss="modal" id="btn_save_Supplier">
                            Save
                        </button>
                        <!-- close -->
                        <button type="button" class="btn bg-warning-subtle text-danger  waves-effect" data-bs-dismiss="modal" id="close_Supplier_modal">
                            Close
                        </button>

                        <!-- <?php if(isset($_GET['Supp_Id'])): ?>
                            <button type="submit" name="btn_Update_Supplier" class="btn bg-primary-subtle text-primary waves-effect" data-bs-dismiss="modal">Update</button>
                        <?php else: ?>
                            <button type="submit" name="btn_save_Supplier" class="btn bg-primary-subtle text-primary  waves-effect" data-bs-dismiss="modal" id="update" > Save </button>
                        <?php endif; ?>
                        <button type="button" class="btn bg-danger-subtle text-danger  waves-effect" data-bs-dismiss="modal" id="close_Supplier_modal"> Close </button>
                     -->
                    </div>
                    
                </form>
            </div>       
        </div>
    </div>
</div>
