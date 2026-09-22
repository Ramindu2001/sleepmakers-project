<div id="Payment_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true"
    role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">Add Payment Methods</h4>
                <button type="button" class="btn-close" id="close_SysFe_modal" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="userShopForm" action="../Controller/AddPaymentController.php" method="POST"
                    enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">Select a Shop</label>
                                <select id="ShopName" name="shop_SHID" class="form-control mb-2" required>
                                    <option value="">Select a Shop</option>
                                    <?php
                                    $ShopObj = new AddPaymentModels();
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
                                <label class="form-label">Payment Method</label>
                                <select id="PaymethodName" name="paymethod_PMID[]" class="form-control mb-2" required multiple>
                                    <option value="">Payment Method</option>
                                    <?php
                                    $PaymentObj = new AddPaymentModels();
                                    $PaymentName = $PaymentObj->getPayMethods();
                                    foreach ($PaymentName as $Payment): ?>
                                        <option value="<?php echo $Payment['PMID']; ?>">
                                            <?php echo $Payment['PaymethodName']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="btn_save_method"
                            class="btn bg-primary-subtle text-primary waves-effect" id="btn_submit_method">Save</button>
                        <button type="button" class="btn bg-danger-subtle text-danger waves-effect"
                            data-bs-dismiss="modal" id="close_SysFe_modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>