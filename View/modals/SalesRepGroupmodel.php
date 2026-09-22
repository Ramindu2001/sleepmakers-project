<div id="SalesRepGroup_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">Add New Group</h4>
                <button type="button" class="btn-close" id="close_SalesRep_modal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="../Controller/SalesRepGroupController.php" method="POST">
                    <div class="row">   
                        <!-- hidden input -->
                        <input type="hidden" name="hide_SalesRep_id" id="hide_SalesRep_id" value="0">
                        <!-- left column -->
                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">Sales Rep No </label>    
                                <input type="text" name="SalRepNo" id="SalRepNo" placeholder="SRP_000000" style="width:100%;" class="form-control mb-2" disabled>
                            </div>

                            <!-- distributer -->
                            <div class="m-2">
                                <label class="form-label">Distributor</label>
                                <input type="text" name="distributor" id="distributor" placeholder="Distributer Name" class="form-control mb-2" required>
                            </div>

                            <div class="m-2">
                            <label for="cmb_Sale_Group">Sales Group</label>
                            <select name="cmb_Sale_Group" id="cmb_Sale_Group" class="form-control dropdown-toggle mb-2">                            
                                <?php 
                                $subcatObj = new Category();
                                $subcatData = $subcatObj->getCategoryByShop($shop_id);
                                foreach($subcatData as $row)
                                {
                                $is_selected = "";
                                if($row['CTID'] == $category_id){$is_selected = "selected";}
                                else{$is_selected = "";}
                                ?>
                                <option value="<?php echo $row['CTID']?>" <?php echo $is_selected;?>><?php echo $row['CategoryName'];?></option>
                                <?php 
                                }//foreach
                                ?>
                            </select>
                            </div>

                            <div class="m-2">
                                <label class="form-label" for=""> Status</label>
                            </div>

                            <div class="m-2">
                                <div class="form-check">
                                    <input class="form-check-input" name="SalRepStatus" id="SalRepStatus" type="checkbox">
                                    <label class="form-check-label" for="SalRepStatus">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>
                            
                        <!-- right column -->
                        <div class="col-md-6">
                            <!-- supplier name -->
                            <div class="m-2">
                                <label class="form-label">Sales Rep Name</label>                                   
                                <input type="text" name="SuppName" id="SuppName" placeholder="Supplier Name" style="width:100%;" class="form-control mb-2" required>
                            </div>

                            <!-- contact -->
                            <div class="m-2">
                                <label class="form-label">Contact </label>                                   
                                <input type="text" name="Contact" id="Contact" placeholder="071 234 5678" pattern="^\+?\d{1,3}[-.\s]?\(?\d{1,4}\)?[-.\s]?\d{1,4}[-.\s]?\d{1,4}$" title="Please enter a valid contact number" style="width:100%;" class="form-control mb-2" required>
                                <span id="contact_invalid" class="text-danger" style="display: none;">Not Valid Contact No.</span>
                            </div>
                        </div>                           
                    </div>

                    <div class="modal-footer">
                        <!-- delete -->
                        <button type="submit" name="btn_delete_SalesRep" class="btn bg-danger-subtle text-danger waves-effect" data-bs-dismiss="modal" id="btn_delete_SalesRep">
                            Delete
                        </button>
                        <!-- update -->
                        <button type="submit" name="btn_Update_SalesRep" class="btn bg-primary-subtle text-primary waves-effect" data-bs-dismiss="modal" id="btn_Update_SalesRep">
                            Update
                        </button>
                        <!-- save -->
                        <button type="submit" name="btn_save_SalesRep" class="btn bg-primary-subtle text-primary waves-effect" data-bs-dismiss="modal" id="btn_save_SalesRep">
                            Save
                        </button>
                        <!-- close -->
                        <button type="button" class="btn bg-warning-subtle text-danger  waves-effect" data-bs-dismiss="modal" id="close_SalesRep_modal">
                            Close
                        </button>
                    
                    </div>
                    
                </form>
            </div>       
        </div>
    </div>
</div>
