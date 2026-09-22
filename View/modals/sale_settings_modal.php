<div class="modal" tabindex="-1" role="dialog" id="sale_settings_modal">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Sale Settings</h5>
        <button type="button" class="btn btn-close" data-dismiss="modal" aria-label="Close" id="modal_close">
          <!-- <span aria-hidden="true">&times;</span> -->
        </button>
      </div>

      <form action="../Controller/saleSettingController.php" method="POST">
      <div class="modal-body">
        <div class="row">
          <input type="hidden" name="hide_setting_id" id="hide_setting_id" value="0">

            <div class="col-md-6">
                <label for="">Select Shop</label>
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
            <div class="col-md-6">
                <label for="">Item add method</label>
                <select name="cmb_add_option" id="cmb_add_option" class="form-select">
                    <option value="0">Auto add item qty</option>
                    <option value="1">Manually add item qty</option>
                </select>
            </div>
            <div class="col-md-6 mt-2">
                <label for="">Item add duration (seconds) <span class="text-danger">*</span></label>
                <input type="number" name="item_add_duration" id="item_add_duration" class="form-control" required>
            </div>
            <div class="col-md-6 mt-2">
                <label for="">Wholesale Invoice Prefix(10 charactors max)<span class="text-danger">*</span></label>
                <input type="text" name="Winvoice_header_text" id="Winvoice_header_text" class="form-control" placeholder="INV" required>
            </div>
            <div class="col-md-6 mt-2">
                <label for="">Invoice header text (3 charactors max) <span class="text-danger">*</span></label>
                <input type="text" name="invoice_header_text" id="invoice_header_text" class="form-control" placeholder="INV" required>
            </div>
            <div class="col-md-6 mt-2">
                <label for="">Counter type</label>
                <select name="cmb_counter_type" id="cmb_counter_type" class="form-select">
                    <option value="0">Touch Only</option>
                    <option value="1">Touch with keyboard</option>
                    <option value="2">Mouse & Keyboard</option>
                </select>
            </div>
            <div class="col-md-6 mt-2">
              <label for="">Setting Stat</label>
              <div class="form-check form-switch">
                <input class="form-check-input" name="setting_stat" type="checkbox" id="setting_stat" checked>
                <label class="form-check-label" for="setting_stat">Active</label>
              </div>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="btn_delete_salesettings" id="btn_delete_salesettings" class="btn btn-danger">Delete</button>
        <button type="submit" name="btn_update_salesettings" id="btn_update_salesettings" class="btn btn-success">Update</button>
        <button type="submit" name="btn_save_salesettings" id="btn_save_salesettings" class="btn btn-primary">Save</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal" id="modal_close">Close</button>
      </div>
      </form>
    </div>
  </div>
</div>