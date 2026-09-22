<div class="modal" tabindex="-1" role="dialog" id="wholesale_shop_receipt_modal">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Wholesale Receipt</h5>
        <button type="button" class="btn btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
          <!-- <span aria-hidden="true">&times;</span> -->
        </button>
      </div>

      <form action="../Controller/shopReceiptController.php" method="POST" enctype="multipart/form-data">
      <div class="modal-body">
        <div class="row">
          <input type="hidden" name="wholehide_receipt_id" id="wholehide_receipt_id" value="0">

            <div class="col-md-6">
                <label for="">Select Shop</label>
                <select name="cmb_shop_receipts" id="wholesale_cmb_shop_receipts" class="form-select">
                    <?php 
                    $sql = "SELECT * FROM shop WHERE ShopStat = 1 AND SHID NOT in (SELECT shop_id FROM `shopreceipts` WHERE ReceiptStat=1 AND RecieptType=2)";
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
                <input type="text" name="wholereceipt_name" id="wholereceipt_name" class="form-control" placeholder="80mm" required>
            </div>
            <div class="col-md-6 mt-2">
                <label for="">Shop Receipt</label>
                <input type="file" name="shop_receipt2" id="shop_receipt2" class="form-control" accept=".php">
                <span id="wholesalespan_receipt" style="display: none;">Shop Receipt</span>
            </div>
            <div class="col-md-6 mt-2">
                <label for="">Active Receipt</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" name="wholesaleactive_receipt" type="checkbox" id="wholesaleactive_receipt">
                    <label class="form-check-label" for="active_receipt">Active</label>
                </div>
            </div>
            <div class="col-md-6 mt-2" id="preview">
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="btn_delete_wholesale_receipt" id="btn_delete_wholesale_receipt" class="btn btn-danger">Delete</button>
        <button type="submit" name="btn_update_wholesale_receipt" id="btn_update_wholesale_receipt" class="btn btn-success">Update</button>
        <button type="submit" name="btn_save_wholesale_receipt" id="btn_save_wholesale_receipt" class="btn btn-primary">Save</button>
        <button type="button" class="btn btn-danger" data-dismiss="modal" data-bs-dismiss="modal" >Close</button>
      </div>
      </form>

    </div>
  </div>
</div>
<script>
  $(document).ready(function() {
      $('#shop_receipt2').on('change', function() {
          var file = this.files[0];
          var reader = new FileReader();
              reader.onload = function(e) {
                  $('#preview').text(e.target.result);
              }
           reader.readAsText(file);
      });
  });
</script>