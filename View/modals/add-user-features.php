<div id="SysFeature_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true"
    role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">Add Users</h4>
                <button type="button" class="btn-close" id="close_SysFe_modal" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="userShopForm" action="../Controller/AddUsersToShopsController.php" method="POST"
                    enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">Select a Shop</label>
                                <select id="ShopName" name="shop_SHID" class="form-control mb-2" required>
                                    <option value="">Select a Shop</option>
                                    <?php
                                    $ShopObj = new AddUsersModels();
                                    $ShopName = $ShopObj->getShops();
                                    foreach ($ShopName as $Shop): ?>
                                        <option value="<?php echo $Shop['SHID']; ?>"><?php echo $Shop['ShopName']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">User</label>
                                <select id="UserName" name="user_USID" class="form-control mb-2" required>
                                    <option value="">Select a User</option>
                                    <?php
                                    $UserObj = new AddUsersModels();
                                    $UserName = $UserObj->getUsers();
                                    foreach ($UserName as $User): ?>
                                        <option value="<?php echo $User['USID']; ?>"><?php echo $User['UserName']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="btn_save_Shop"
                            class="btn bg-primary-subtle text-primary waves-effect" id="btn_submit_shops">Save</button>
                        <button type="button" class="btn bg-danger-subtle text-danger waves-effect"
                            data-bs-dismiss="modal" id="close_SysFe_modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>