<div id="modal_paymethod" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true"
    role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">Add Payment Methods</h4>
                <button type="button" class="btn-close" id="close_SysFe_modal" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="../Controller/AddPaymentController.php" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="m-2">
                                <label for="">Payment Method Name</label>
                                <input type="text" name="paymethod_name" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="m-2">
                                <label for="">Payment Method Image</label>
                                <input type="file" name="paymethod_image" id="paymethod_image" class="form-control">
                                <span id="image_success" style="display:none" class="text-success ">Ok</span>
                                <span id="image_warning" style="display:none" class="text-danger">File size greater than 500KB</span>

                                <div class="m-2 rounded">
                                <img src="" alt="Paymethod Image" id="img_paymethod" class="mx-1 w-100 h-auto"  class="shadow img-fluid mx-auto d-block mt-2 p-2 rounded bordered" accept="image/png, image/gif, image/jpeg">
                                </div>
                                
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="btn_save_paymethod" class="btn bg-primary-subtle text-primary waves-effect" id="btn_save_paymethod">Save</button>
                        <button type="button" class="btn bg-danger-subtle text-danger waves-effect"
                            data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>