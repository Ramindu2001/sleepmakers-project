<div id="edit_user_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true"
    role="dialog" style="background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header d-flex align-items-center">
                <h4 class="modal-title" id="myModalLabel">
                    Edit User
                </h4>
                <button type="button" class="btn-close" id="btn-close" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="../Controller/userController.php" method="POST" id="form-user" enctype="multipart/form-data">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="status" name="status" value="1">
                        <label class="form-check-label" for="status">Status</label>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="m-2">
                                <input type="hidden" name="euserid" id="euserid">
                                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" id="eusername" class="form-control" placeholder="Eg: Example" required>
                            </div>
                            <div class="m-2">
                                <label for="userEmail" class="form-label">User Email <span class="text-danger">*</span></label>
                                <input type="email" name="userEmail" id="euserEmail" class="form-control" placeholder="Eg: Example@gmail.com" required>
                            </div>
                            <div class="m-2">
                                <label for="propic" class="form-label">User Profile </label>
                                <input type="hidden" name="eprofile" id="eprofile">
                                <input type="file" name="epropic" id="epropic" class="form-control" accept="image/png,image/jpg,image/jpeg" placeholder="Eg: +94-777-123-456" >
                            </div>
                            <div class="m-3">
                                <img src="../Assets/Images/synnex_logo.png" alt="" style="" id="eilogo">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="m-2">
                                <label for="userContact" class="form-label">User Contact <span class="text-danger">*</span></label>
                                <input type="tel" name="userContact" id="euserContact" class="form-control" placeholder="Eg: +94-777-123-456" required>
                            </div>
                            <div class="m-2">
                                <label for="userRole" class="form-label">Select User Role <span class="text-danger">*</span></label>
                                <select name="userRole" id="euserRole" class="form-select" required >
                                    <option value=""  disabled>Select User Role</option>
                                    <?php 
                          foreach ($userroles as $key) 
                          {
                            ?>
                                    <option id="ud_<?=$key['URID']?>" value="<?=$key['URID']?>"><?=$key['UserRoleName']?></option>
                                    <?php
                          }
                          ?>
                                </select>
                            </div>                            
                            <div class="m-2">
                                <label for="epaylimit" class="form-label">Daily Bill Limit</label>
                                <input type="text" name="epaylimit" id="epaylimit" class="form-control" value="0.00">
                            </div> 
                        </div>
                    </div>
            </div>
            <div class="modal-footer">
                <input type="submit" value="Update User" name="edit-user" id="" class="btn bg-primary-subtle text-primary waves-effect">
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