<div id="Type_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true"
    role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">Add Expense Type</h4>
                <button type="button" class="btn-close" id="close_SysFe_modal" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="typeForm" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">Add an Expense Type</label>
                                <input type="text" id="expense_type" name="expense_type" class="form-control mb-2"
                                    placeholder="Select the type" required>
                            </div>
                        </div>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn bg-primary-subtle text-primary waves-effect"
                    id="btn_save_type">Save</button>
                <button type="button" class="btn bg-danger-subtle text-danger waves-effect" data-bs-dismiss="modal"
                    id="close_SysFe_modal">Close</button>
            </div>
            </form>
        </div>
    </div>
</div>