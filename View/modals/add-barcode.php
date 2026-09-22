<div class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog" id="add_barcode_modal">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">Add Barcode Label</h4>
                <button type="button" class="btn-close" id="close_SysFe_modal" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <form id="userShopForm" action="../Controller/LabelController.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="hide_label_id" id="hide_label_id" value="0">
                
            <div class="modal-body" style="max-height: 500px; overflow:auto;">
                <div class="row">
                    <div class="col-md-12">
                        <label for="" class="form-label">DPI (dots per inch)</label>
                        <input type="file" name="barcode_file" class="form-control">
                        <span id="file_name"></span>
                    </div>

                    <!-- name and dpi -->
                    <div class="col-md-6 mt-2">
                        <label for="" class="form-label">Label Name</label>
                        <input type="text" name="lbl_name" id="lbl_name" class="form-control">
                    </div>
                    <div class="col-md-6 mt-2">
                        <label for="" class="form-label">DPI (dots per inch)</label>
                        <input type="number" name="dpi" id="dpi" class="form-control">
                    </div>

                    <!-- rows and columns -->
                    <div class="col-md-6 mt-2">
                        <label for="" class="form-label">Number or Rows</label>
                        <input type="number" name="num_row" id="num_row" class="form-control">
                    </div>
                    <div class="col-md-6 mt-2">
                        <label for="" class="form-label">Number of columns</label>
                        <input type="number" name="num_col" id="num_col" class="form-control">
                    </div>

                    <!-- Label width & height -->
                    <div class="col-md-6 mt-2">
                        <label for="" class="form-label">Label Width (mm)</label>
                        <input type="number" step="0.01" name="lbl_width" id="lbl_width" class="form-control">
                    </div>
                    <div class="col-md-6 mt-2">
                        <label for="" class="form-label">Label Height (mm)</label>
                        <input type="number" step="0.01" name="lbl_height" id="lbl_height" class="form-control">
                    </div>

                    <!-- sticker width & height -->
                    <div class="col-md-6 mt-2">
                        <label for="" class="form-label">Sticker Width (mm)</label>
                        <input type="number" step="0.01" name="stk_width" id="stk_width" class="form-control">
                    </div>
                    <div class="col-md-6 mt-2">
                        <label for="" class="form-label">Sticker Height (mm)</label>
                        <input type="number" step="0.01" name="stk_height" id="stk_height" class="form-control">
                    </div>

                    <!-- sticker margin -->
                    <div class="col-md-6 mt-2">
                        <label for="" class="form-label">Sticker Margin Left (mm)</label>
                        <input type="number" step="0.01" name="stk_margin_left" id="stk_margin_left" class="form-control">
                    </div>
                    <div class="col-md-6 mt-2">
                        <label for="" class="form-label">Sticker Margin Right (mm)</label>
                        <input type="number" step="0.01" name="stk_margin_right" id="stk_margin_right" class="form-control">
                    </div>

                    <div class="col-md-6 mt-2">
                        <label for="" class="form-label">Sticker Margin Top (mm)</label>
                        <input type="number" step="0.01" name="stk_margin_top" id="stk_margin_top" class="form-control">
                    </div>
                    <div class="col-md-6 mt-2">
                        <label for="" class="form-label">Sticker Margin Bottom (mm)</label>
                        <input type="number" step="0.01" name="stk_margin_bottom" id="stk_margin_bottom" class="form-control">
                    </div>

                    <div class="col-md-6 mt-2">
                        <label for="" class="form-label">Shop</label>
                        <select name="cmb_shop" id="cmb_shop" class="form-select">
                            <?php 
                            $sql = "SELECT * FROM shop WHERE ShopStat = 1;";
                            $dbObj = new DBTransactions();
                            $shopData = $dbObj->getData($sql);
                            foreach($shopData as $row)
                            {
                                ?>
                                <option value="<?php echo $row['SHID'];?>"><?php echo $row['ShopName'];?></option>
                                <?php 
                            }//foreach
                            ?>
                        </select>
                    </div>

                    <div class="col-md-6 mt-2">
                        <label for="" class="form-label">Label Status</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="chk_lbl_stat" id="chk_lbl_stat">
                            <label class="form-check-label" for="chk_lbl_stat">Active</label>
                        </div>
                    </div>

                </div> 
            </div>
            <div class="modal-footer">
                <button type="submit" name="btn_save_label" class="btn btn-primary" id="btn_save_label">Save</button>

                <button type="submit" name="btn_update_label" class="btn btn-primary" id="btn_update_label">Update</button>
            </div>
            </form>
        </div>
    </div>
</div>
</div>