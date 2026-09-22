<div id="Shop_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true"
    role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">

                <?php if (isset($_GET['Shop_Id'])): ?>
                    <h4 class="modal-title" id="myModalLabel"> Edit Shop</h4>
                <?php else: ?>
                    <h4 class="modal-title" id="myModalLabel"> Add New Shop</h4>
                <?php endif; ?>

                <button type="button" class="btn-close" id="close_Shop_modal" data-bs-dismiss="modal"
                    aria-label="Close"></button>

            </div>
            <div class="modal-body">
                <form action="../Controller/shopController.php" id="shopAdd" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <!-- hidden input -->
                        <input type="hidden" name="hide_Shop_id" id="hide_Shop_id" value="<?php echo $shop_id; ?>">

                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">Company Name <span class="text-danger">*</span></label>
                                <select name="cmb_company" id="cmb_company"class="required form-select mb-2">
                                    <option value="" selected>=== Select Company ===</option>
                                    <?php
                                    $comObj = new Company();
                                    $Data = $comObj->getAllCompany();
                                    foreach ($Data as $row) {
                                        ?>
                                        <option value="<?php echo $row['CMID']; ?>" class="form-control mb-2">
                                            <?php echo $row['ComName']; ?>
                                        </option>
                                        <?php
                                    }//foreach
                                    ?>
                                </select>
                                <span class="text-danger" id="require-span" style="display:none;">This Field is Required</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">Shop No </label>

                                <input type="text" name="ShopNo" id="ShopNo" placeholder="" class="form-control mb-2" disabled>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">Shop Name <span class="text-danger">*</span></label>
                              
                                <input type="text" name="ShopName" id="ShopName" placeholder="Shop Name" style="width:100%;"class="required form-control mb-2">
                                <span class="text-danger" id="require-span" style="display:none;">This Field is Required</span>

                            </div>
                        </div>

                        <!--Stock type selector-->
                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">Stock type <span class="text-danger">*</span></label>
                                <select name="cmb_stock_type" id="cmb_stock_type"class="required form-select mb-2">
                                    <option value="0">=== Select Stock Type ===</option>
                                    <?php
                                    $shopObj = new Shop();
                                    $Data = $shopObj->getStockTypes();
                                    foreach ($Data as $row) {
                                        ?>
                                        <option value="<?php echo $row['STID']; ?>" class="form-control mb-2">
                                            <?php echo $row['StockTypeName']; ?>
                                        </option>
                                        <?php
                                    }//foreach
                                    ?>
                                </select>
                                <span class="text-danger" id="require-span" style="display:none;">This Field is Required</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">Address Line 01  <span class="text-danger">*</span></label>
                              
                                <input type="text" name="AddressLineOne" id="AddressLineOne" placeholder="Eg: 80/5" style="width:100%;"class="required form-control mb-2">
                                <span class="text-danger" id="require-span" style="display:none;">This Field is Required</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">Address Line 02  <span class="text-danger">*</span></label>
                                <input type="text" name="AddressLineTwo" id="AddressLineTwo" placeholder="Eg: HighLevel Rd." style="width:100%;"class="required form-control mb-2">
                                <span class="text-danger" id="require-span" style="display:none;">This Field is Required</span>

                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">City  <span class="text-danger">*</span></label>
                                <input type="text" name="City" id="City" placeholder="Eg: Homagama" style="width:100%;"class="required form-control mb-2">
                                <span class="text-danger" id="require-span" style="display:none;">This Field is Required</span>

                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">Phone Number <span class="text-danger">*</span></label>

                                <input type="tel" name="PhoneNumber" id="PhoneNumber" placeholder="Eg: " style="width:100%;"class="required form-control mb-2">
                                <span class="text-danger" id="require-span" style="display:none;">This Field is Required</span>

                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>

                                <input type="tel" name="EmailAddress" id="EmailAddress" placeholder="Eg: synnex@synnex.lk" class="required form-control mb-2">
                                <span class="text-danger" id="require-span" style="display:none;">This Field is Required</span>

                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label" for=""> Shop Status</label>
                            </div>

                            <div class="m-2">
                                <div class="form-check">
                                    <input class="form-check-input" name="ShopStatus" type="checkbox" id="ShopStatus">
                                    <label class="form-check-label" for="ShopStatus">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- shop wholesale or Retail -->
                        <div class="col-md-6">
                        <div class="m-2">
                            <label class="form-label" for="">Shop Sale Type</label>
                        </div>
                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_wholesale">Wholesale Invoice</label>
                                <input class="form-check-input" name="chk_wholesale" type="checkbox" id="chk_wholesale">
                            </div>
                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_retail">Retail Invoice</label>
                                <input class="form-check-input" name="chk_retail" type="checkbox" id="chk_retail" checked>
                            </div>
                        </div>

                        <div class="m-2">
                            <label class="form-label" for=""> Shop Access Features</label>
                        </div>

                        <!--Left side options list -->

                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_inventory">Inventory</label>
                                <input class="form-check-input" name="chk_inventory" type="checkbox" id="chk_inventory">
                            </div>

                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_minus">Allow Minus</label>
                                <input class="form-check-input" name="chk_minus" type="checkbox" id="chk_minus">
                            </div>

                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_expire">Expire Date</label>
                                <input class="form-check-input" name="chk_expire" type="checkbox" id="chk_expire">
                            </div>

                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_fixed_price">Fixed Price</label>
                                <input class="form-check-input" name="chk_fixed_price" type="checkbox" id="chk_fixed_price">
                            </div>

                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_variations">Variations</label>
                                <input class="form-check-input" name="chk_variations" type="checkbox" id="chk_variations">
                            </div>

                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_second_language">Second Language</label>
                                <input class="form-check-input" name="chk_second_language" type="checkbox" id="chk_second_language">
                            </div>

                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_label_price">Label Price</label>
                                <input class="form-check-input" name="chk_label_price" type="checkbox" id="chk_label_price">
                            </div>

                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_carton">Carton Qty</label>
                                <input class="form-check-input" name="chk_carton" type="checkbox" id="chk_carton">
                            </div>

                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_warranty">Warranty</label>
                                <input class="form-check-input" name="chk_warranty" type="checkbox" id="chk_warranty">
                            </div>

                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_credit">Credit</label>
                                <input class="form-check-input" name="chk_credit" type="checkbox" id="chk_credit">
                            </div>
                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_counter">Counter</label>
                                <input class="form-check-input" name="chk_counter" type="checkbox" id="chk_counter">
                            </div>
                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_batch">Allow Batch No</label>
                                <input class="form-check-input" name="chk_batch" type="checkbox" id="chk_batch">
                            </div>
                        </div>

                        <!--Right side options list -->
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_category">Category</label>
                                <input class="form-check-input" name="chk_category" type="checkbox" id="chk_category">
                            </div>

                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_supplier">Suppliers</label>
                                <input class="form-check-input" name="chk_supplier" type="checkbox" id="chk_supplier">
                            </div>

                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_service">Service Provide</label>
                                <input class="form-check-input" name="chk_service" type="checkbox" id="chk_service">
                            </div>

                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_salesman">Salesman</label>
                                <input class="form-check-input" name="chk_salesman" type="checkbox" id="chk_salesman">
                            </div>

                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_expenses">Expenses</label>
                                <input class="form-check-input" name="chk_expenses" type="checkbox" id="chk_expenses">
                            </div>

                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_customers">Customers</label>
                                <input class="form-check-input" name="chk_customers" type="checkbox" id="chk_customers">
                            </div>

                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_quotation">Quotations</label>
                                <input class="form-check-input" name="chk_quotation" type="checkbox" id="chk_quotation">
                            </div>

                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_promotion">Promotions</label>
                                <input class="form-check-input" name="chk_promotion" type="checkbox" id="chk_promotion">
                            </div>

                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_racks">Racks</label>
                                <input class="form-check-input" name="chk_racks" type="checkbox" id="chk_racks">
                            </div>

                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_prescription">Prescription</label>
                                <input class="form-check-input" name="chk_prescription" type="checkbox" id="chk_prescription">
                            </div>
                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_excess">Allow Excess Amount</label>
                                <input class="form-check-input" name="chk_excess" type="checkbox" id="chk_excess">
                            </div>
                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_invoice_print">Invoice Print</label>
                                <input class="form-check-input" name="chk_invoice_print" type="checkbox" id="chk_invoice_print">
                            </div>
                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_is_under_cost">Under Cost</label>
                                <input class="form-check-input" name="chk_is_under_cost" type="checkbox" id="chk_is_under_cost">
                            </div>
                            <div class="form-check form-switch">
                                <label class="form-check-label" for="chk_a4_invoice">A4 Invoice Print (off = 80mm receipt)</label>
                                <input class="form-check-input" name="chk_a4_invoice" type="checkbox" id="chk_a4_invoice">
                            </div>
                        </div>
                        <?php 
                        $shopFeatures = $comObj->getAllShopfeatures($shop_id);
                        foreach ($shopFeatures as $features) 
                        {
                            ?>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="featureID[]" id="featureID" value="<?=$features["SPFID"]?>">
                                    <label class="form-check-label" for="chk_<?=$features["FeatureName"]?>"><?=$features["FeatureName"]?></label>
                                    <input class="form-check-input" name="features[]" type="checkbox" id="chk_<?=$features["FeatureName"]?>" >
                                </div>
                            </div>
                            <?php
                        }
                        ?>

                        <!--Shop logo-->
                        <div class="col-md-6">
                            <div class="">
                                <label for="shop_logo" class="form-label">Shop Logo:</label>
                                <input type="file" name="shop_logo" id="shop_logo" class="form-control mb-2"  accept="image/*" >
                                <span class="text-success" id="logo_success" style="display: none;">Ok</span>
                                <span class="text-danger" id="logo_danger" style="display: none;"> &#9888; Shop logo
                                    size cannot be higher than 5mb.</span>
                            </div>

                            <div class="mb-3 rounded">
                                <img src="../Assets/Images/synnex_logo.png" id="img_shop_logo"
                                    class="shadow img-fluid mx-auto d-block mt-2 p-2 rounded bordered" alt="Shop Logo"
                                    accept="image/png, image/gif, image/jpeg">
                            </div>
                        </div>

                        <!-- Shop Receipt -->
                        <div class="col-md-6">
                            <div class="">
                                <label for="shop_receipt" class="form-label">Shop Receipt:</label>
                                <input type="file" name="shop_receipt" id="shop_receipt" class="form-control mb-2"   accept="image/*" >
                                <span class="text-success" id="receipt_success" style="display: none;">Ok</span>
                                <span class="text-danger" id="receipt_danger" style="display: none;"> &#9888; Shop
                                    Receipt size cannot be higher than 5mb.</span>
                            </div>

                            <div class="mb-3 rounded">
                                <img src="../Assets/Images/synnex_logo.png" id="img_receipt_logo"
                                    class="shadow img-fluid mx-auto d-block mt-2 p-2 rounded bordered" alt="Shop Logo"
                                    accept="image/png, image/gif, image/jpeg">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <!-- <button type="submit" name="btn_test">Test</button> -->
                        <!-- update shop -->

                        <button type="submit" name="btn_update_shop" id="btn_update_shop" class="btn bg-primary-subtle text-primary waves-effect">
                            Update
                        </button>
                        <!-- Save shop -->
                        <button type="submit" name="btn_save_shop" id="btn_save_shop" class="btn bg-primary-subtle text-primary waves-effect">
                            Save
                        </button>
                        <!-- cancle modal -->
                        <button type="button" class="btn bg-danger-subtle text-danger  waves-effect" data-bs-dismiss="modal" id="close_Shop_modal"> Close </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>