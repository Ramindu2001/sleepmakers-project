<div id="Counter_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true"
    role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">Add Payment Methods</h4>
                <button type="button" class="btn-close" id="close_SysFe_modal" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="userShopForm" action="../Controller/AddCounterController.php" method="POST"
                    enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">Add a Counter</label>
                                <input type="number" id="counterNo" name="counterNo" class="form-control mb-2"
                                    placeholder="Type a counter number" required>
                            </div>
                        </div>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="btn_save_counter" class="btn bg-primary-subtle text-primary waves-effect"
                    id="btn_save_counter">Save</button>
                <button type="button" class="btn bg-danger-subtle text-danger waves-effect" data-bs-dismiss="modal"
                    id="close_SysFe_modal">Close</button>
            </div>
            </form>
        </div>
    </div>
</div>
</div>