<div id="adjust_head_modal" class="modal fade show" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-md">
      <div class="modal-content">
        <div class="modal-header d-flex align-items-center">
          <h4 class="modal-title" id="myModalLabel">
            Add New Adjustment
          </h4>
          <button type="button" class="btn-close" id="close_adjust_head_modal" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form action="../Controller/adjustController.php" method="post">
          <div class="row">
            <div class="col-md-12">

              <div class="m-2">
                <label for="" class="form-label">Adjustment No</label>
                <input type="text" name="transfer_no" id="transfer_no" placeholder="AD_000000" class="form-control" disabled>
              </div>

              <div class="m-2">
                <?php 
                //get date
                date_default_timezone_set("Asia/Colombo");
                $transfer_date = date("Y-m-d");
                ?>
                <label for="" class="form-label">Transfer Date</label>
                <input type="date" name="transfer_date" id="transfer_date" value="<?php echo $transfer_date;?>" class="form-control" disabled>
              </div>

              <div class="m-2">
                <div class="row">
                    <div class="col-6 p-2">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="rbd_adjustment_type" id="adjustment_in" value="1" checked>
                        <label class="form-check-label bg-success-subtle rounded p-2" for="adjustment_in">
                            Adjustment IN
                        </label>
                    </div>
                    </div>
                    <div class="col-6 p-2">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="rbd_adjustment_type" id="adjustment_out" value="2">
                        <label class="form-check-label bg-warning-subtle rounded p-2" for="adjustment_out">
                            Adjustment OUT
                        </label>
                    </div>
                    </div>
                </div>
              </div>
            </div>

            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" name="btn_add_adjustment" class="btn bg-primary-subtle text-primary  waves-effect" data-bs-dismiss="modal" id="btn_add_adjustment">
              Create New Adjustment
            </button>
            <button type="button" class="btn bg-warning-subtle text-warning  waves-effect" data-bs-dismiss="modal" id="close_adjust_modal">
              Close
            </button>
          </div>
          </form>
      </div>
      <!-- /.modal-content -->
    </div>
  <!-- /.modal-dialog -->
  </div>