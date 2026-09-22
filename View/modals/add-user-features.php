<!-- Settings -> Assign Users to Shops: add an assignment, or edit the role of one (Assets/jquery/AddShops.js) -->
<div id="SysFeature_modal" class="modal fade show" tabindex="-1" aria-labelledby="assign_modal_title" aria-modal="true"
    role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="assign_modal_title">Add Users</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="userShopForm">
                    <input type="hidden" id="assign_suid" value="">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="m-2">
                                <label class="form-label" for="ShopName">Select a Shop</label>
                                <select id="ShopName" class="form-control mb-2" required>
                                    <option value="">Select a Shop</option>
                                    <?php
                                    $ShopObj = new AddUsersModels();
                                    foreach ($ShopObj->getShops() as $Shop): ?>
                                        <option value="<?php echo $Shop['SHID']; ?>"><?php echo htmlspecialchars($Shop['ShopName']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="m-2">
                                <label class="form-label" for="UserName">User</label>
                                <select id="UserName" class="form-control mb-2" required>
                                    <option value="">Select a User</option>
                                    <?php
                                    foreach ($ShopObj->getUsers() as $User): ?>
                                        <option value="<?php echo $User['USID']; ?>" data-default-role="<?php echo $User['UserRoles_URID']; ?>"><?php echo htmlspecialchars($User['UserName']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="m-2">
                                <label class="form-label" for="RoleName">Role in this Shop</label>
                                <select id="RoleName" class="form-control mb-2" required>
                                    <option value="">Select a Role</option>
                                    <?php
                                    foreach ($ShopObj->getActiveRoles() as $Role): ?>
                                        <option value="<?php echo $Role['URID']; ?>"><?php echo htmlspecialchars($Role['UserRoleName']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <p class="text-danger fw-bold m-2" id="assign_error" style="display:none;"></p>
                    <div class="modal-footer">
                        <button type="submit" class="btn bg-primary-subtle text-primary waves-effect" id="btn_submit_shops">Save</button>
                        <button type="button" class="btn bg-danger-subtle text-danger waves-effect" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
