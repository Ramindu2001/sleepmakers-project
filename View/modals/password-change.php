<div id="pasword_change_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true"
    role="dialog" style="background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header d-flex align-items-center">
                <h4 class="modal-title" id="myModalLabel">
                    Change Password
                </h4>
                <button type="button" class="btn-close" id="btn-close" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="../Controller/userController.php" method="POST" id="form-user" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="hidden" name="euid" id="e-pwd-change-id">
                            <div class="m-2">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" name="epassword" id="epassword" class="form-control" required>
                                <div class="m-1">
                                    <input type="checkbox" name="" id="e-password" class="form-check-box">
                                    <label for="e-password" class="form-label" id="show-password">Show Password</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="m-2">
                                <label for="e-cpassword" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" name="e-cpassword" id="e-cpassword" class="form-control" required>
                                <span class="text-danger" id="epno" style="display:none;">Password does not match!</span>
                                <span class="text-success" id="epyes" style="display:none;">Password match!</span>
                            </div>
                        </div>
                    </div>
            </div>
            <div class="modal-footer">
                <input type="submit" value="Change Password" name="chng-pwd" id="chng-pwd" disabled class="btn bg-primary-subtle text-primary waves-effect">
                <button type="button" class="btn bg-danger-subtle text-danger  waves-effect " data-bs-dismiss="modal"
                    id="btn-close">
                    Close
                </button>
            </div>
            </form>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>