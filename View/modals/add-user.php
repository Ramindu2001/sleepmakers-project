<div id="add_user_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true"
    role="dialog" style="background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header d-flex align-items-center">
                <h4 class="modal-title" id="myModalLabel">
                    Add New User
                </h4>
                <button type="button" class="btn-close" id="btn-close" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="../Controller/userController.php" method="POST" id="form-user" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="m-2">
                                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" id="username" class="form-control" placeholder="Eg: Example" required>
                            </div>
                            <div class="m-2">
                                <label for="userEmail" class="form-label">User Email <span class="text-danger">*</span></label>
                                <input type="email" name="userEmail" id="userEmail" class="form-control" placeholder="Eg: Example@gmail.com" required>
                            </div>
                            <div class="m-2">
                                <label for="userContact" class="form-label">User Contact <span class="text-danger">*</span></label>
                                <input type="tel" name="userContact" id="userContact" class="form-control" placeholder="Eg: +94-777-123-456" required>
                            </div>
                            <div class="m-2">
                                <label for="propic" class="form-label">User Profile </label>
                                <input type="file" name="propic" id="propic" class="form-control" accept="image/png,image/jpg,image/jpeg" placeholder="Eg: +94-777-123-456" >
                            </div>
                            <div class="m-2">
                                <label for="paylimit" class="form-label">Daily Bill Limit</label>
                                <input type="text" name="paylimit" id="paylimit" class="form-control" value="0.00">
                            </div> 
                            <div class="m-2">
                                <img src="../Assets/Images/synnex_logo.png" alt="" style="width:50%; " id="ilogo">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="m-3">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" id="password" class="form-control" required>
                                <div class="m-1">
                                    <input type="checkbox" name="" id="s-password" class="form-check-box">
                                    <label for="s-password" class="form-label" id="show-password">Show Password</label>
                                </div>
                            </div>
                            <div class="m-3">
                                <label for="cpassword" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" name="cpassword" id="cpassword" class="form-control" required>
                                <span class="text-danger" id="pno" style="display:none;">Password doesnot match!</span>
                                <span class="text-success" id="pyes" style="display:none;">Password match!</span>
                            </div>
                            <div class="m-3">
                                <label for="userRole" class="form-label">Default Role <span class="text-danger">*</span></label>
                                <select name="userRole" id="userRole" class="form-select" required  multiple>
                                    <option value="" disabled>Select User Role</option>
                                    <?php 
                          foreach ($userroles as $key) 
                          {
                            ?>
                                    <option value="<?=$key['URID']?>"><?=$key['UserRoleName']?></option>
                                    <?php
                          }
                          ?>
                                </select>
                                <small class="text-muted">Rights in each shop are set under Settings &rarr; Assign Users to Shops.</small>
                            </div>
                            <?php 
                            if($userType==1)
                            {
                                ?>
                                <div class="m-3">
                                    <label for="superadmin" class="form-label">Super Admin </label>
                                    <input type="checkbox" name="superadmin" id="superadmin" class="form-check-input">
                                </div>    
                                <?php
                            }
                            ?> 
                        </div>
                    </div>
            </div>
            <div class="modal-footer">
                <input type="submit" value="Add User" name="add-user" id="add-user" class="btn bg-primary-subtle text-primary waves-effect">
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