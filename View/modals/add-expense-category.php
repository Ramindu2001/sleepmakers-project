<div id="Category_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true"
    role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">Add Expense Category</h4>
                <button type="button" class="btn-close" id="close_SysFe_modal" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm" action="../Controller/AddExpenseCtgController.php" method="POST"
                    enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">Select a Type</label>
                                <select id="expense_ETID" name="expense_ETID" class="form-control mb-2" required>
                                    <option value="">Select a Type</option>
                                    <?php
                                    $TypeObj = new AddExpenseCtgModels();
                                    $TypeName = $TypeObj->getTypes();
                                    foreach ($TypeName as $Type): ?>
                                        <option value="<?php echo $Type['ETID']; ?>"><?php echo $Type['expense_type']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">Add Category</label>
                                <input type="text" id="expense_ctg" name="expense_ctg" class="form-control mb-2"
                                    placeholder="Type a Category" required>
                            </div>
                        </div>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="btn_save_category" class="btn bg-primary-subtle text-primary waves-effect"
                    id="btn_save_category">Save</button>
                <button type="button" class="btn bg-danger-subtle text-danger waves-effect" data-bs-dismiss="modal"
                    id="close_SysFe_modal">Close</button>
            </div>
            </form>
        </div>
    </div>
</div>
</div>
