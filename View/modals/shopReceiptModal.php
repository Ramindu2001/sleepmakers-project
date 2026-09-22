<div class="modal" tabindex="-1" role="dialog" id="shop_receipt_modal">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Shop Receipt</h5>
        <button type="button" class="btn close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form action="../Controller/shopReceiptController.php" method="POST" enctype="multipart/form-data">
      <div class="modal-body">
        <div class="row">
          <input type="hidden" name="hide_receipt_id" id="hide_receipt_id" value="0">

            <div class="col-md-6">
                <label for="">Select Shop</label>
                <select name="cmb_shop_receipts" id="cmb_shop_receipts" class="form-select">
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
                <label for="">Receipt Name</label>
                <input type="text" name="receipt_name" id="receipt_name" class="form-control" placeholder="80mm" required>
            </div>

            <div class="col-md-6 mt-2">
                <label for="">Shop Receipt</label>
                <input type="file" name="shop_receipt" id="shop_receipt" class="form-control">
                <span id="span_receipt" style="display: none;">Shop Receipt</span>
            </div>

            <div class="col-md-6 mt-2">
                <label for="">Use as Default Receipt</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" name="default_receipt" type="checkbox" id="default_receipt">
                    <label class="form-check-label" for="default_receipt">Dafault</label>
                </div>
            </div>

            <div class="col-md-6 mt-2">
                <label for="">Active Receipt</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" name="active_receipt" type="checkbox" id="active_receipt">
                    <label class="form-check-label" for="active_receipt">Active</label>
                </div>
            </div>
            <div class="col-md-6 mt-2">
                <label for="">Wholesale POS Recipt</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" name="wholesaleRecipt" type="checkbox" id="wholesaleRecipt">
                    <label class="form-check-label" for="wholesaleRecipt">Active</label>
                </div>
            </div>

        </div>
      </div>

      <div class="modal-footer">
        <button type="submit" name="btn_delete_receipt" id="btn_delete_receipt" class="btn btn-danger">Delete</button>
        <button type="submit" name="btn_update_receipt" id="btn_update_receipt" class="btn btn-success">Update</button>
        <button type="submit" name="btn_save_receipt" id="btn_save_receipt" class="btn btn-primary">Save</button>
        <button type="button" class="btn btn-danger" data-dismiss="modal" data-bs-dismiss="modal" >Close</button>
      </div>
      </form>

    </div>
  </div>
</div>