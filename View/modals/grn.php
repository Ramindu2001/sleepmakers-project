<div id="grn_modal" class="modal fade show" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
      <div class="modal-content">
        <div class="modal-header d-flex align-items-center">
          <h4 class="modal-title" id="myModalLabel">
            Add GRN
          </h4>
          <button type="button" class="btn-close" id="close_grn_modal" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form action="../Controller/grnController.php" method="post">
          <div class="row">
            <div class="col-md-6">
              <div class="m-2">
                <label for="" class="form-label">GRN No</label>
                <input type="text" name="grn_no" id="grn_no" class="form-control" disabled>
              </div>
            </div>

            <?php 
            $shopObj = new Shop();
            if($shopObj->hasSuppliers($shop_id))
            {
                ?>
                <div class="col-md-6">
                  <div class="m-2">
                    <label for="" class="form-label">Select Supplier</label><br>
                    <select name="cmb_supplier" id="cmb_supplier" class="form-select">
                      <?php 
                      $shop_id = $_SESSION['shop_id'];
                      $supObj = new Supplier();
                      $status=1;
                      $supData = $supObj->getAllActiveSuppliers(shop_id: $shop_id, status:$status);
                      foreach($supData as $row)
                      {
                        ?>
                        <option value="<?php echo $row['SPID'];?>"><?php echo $row['SupplierName'] ." ~ ". $row['Contact'];?></option>
                        <?php 
                      }//foreach
                      ?>
                    </select>
                  </div>
                </div>
                <?php 
            }//has suppliers
            ?>

            <div class="col-md-6">
              <div class="m-2">
                <label for="" class="form-label">Supplier Invoice No</label>
                <input type="text" name="invoice_no" id="invoice_no" class="form-control">
              </div>
            </div>

            <div class="col-md-6">
              <div class="m-2">
                <label for="" class="form-label">Reference</label>
                <input type="text" name="Reference" id="Reference" class="form-control">
              </div>
            </div>

            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" name="btn_add_grn" class="btn bg-primary-subtle text-primary  waves-effect" data-bs-dismiss="modal" id="btn_add_grn">
              Create New GRN
            </button>
            <button type="button" class="btn bg-warning-subtle text-warning  waves-effect" data-bs-dismiss="modal" id="close_grn_modal">
              Close
            </button>
          </div>
          </form>
      </div>
      <!-- /.modal-content -->
    </div>
  <!-- /.modal-dialog -->
  </div>