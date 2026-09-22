<div id="grndetail_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header d-flex align-items-center">
          <h4 class="modal-title" id="myModalLabel">
            Submit GRN
          </h4>
          <button type="button" class="btn-close" id="close_grndetail_modal" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
        
        <form action="../Controller/grnController.php" method="POST">
        <div class="row">
          <!-- left column -->
          <div class="col">
            <p class="lead">Do you want to submit GRN?</p>

            <div>
                <dl class="row">
                    <dt class="col-sm-6">Row Count: </dt>
                    <dd class="col-sm-6" id="data_row_count"></dd>

                    <dt class="col-sm-6">Item Count: </dt>
                    <dd class="col-sm-6" id="data_item_count">3</dd>

                    <dt class="col-sm-6">Purchase Price: </dt>
                    <dd class="col-sm-6" id="data_purchase_price">3</dd>
                </dl>
            </div>

            <!-- hidden input -->
            <input type="hidden" name="hide_grnheader_id" id="hide_grnheader_id" value="0">

          </div>
        </div>

        <div class="modal-footer">
            <?php 
            if($grn_header_stat == '0')
            {
              ?>
              <!-- Add to pending -->
              <button type="submit" name="btn_pending_grn" class="btn bg-primary-subtle text-primary waves-effect" data-bs-dismiss="modal" id="btn_pending_grn">  
                  Add to Pending
              </button>
              <?php 
            }//on hold make submit
            else if($grn_header_stat == '1')
            {
              ?>
              <!-- Add to store -->
              <button type="submit" name="btn_verify_grn" class="btn bg-success-subtle text-success waves-effect" data-bs-dismiss="modal" id="btn_verify_grn">  
                  Add to Store
              </button>
              <!-- Add to cancle -->
              <button type="submit" name="btn_cancle_grn" class="btn bg-danger-subtle text-danger waves-effect" data-bs-dismiss="modal" id="btn_cancle_grn">  
                  Add to Cancle
              </button>
              <?php 
            }//on pending make verify or cancle
            else if($grn_header_stat == '2')
            {
              ?>
              <!-- Add to pending -->
              <button type="button" name="btn_print_grn" class="btn bg-warning-subtle text-warning waves-effect" data-bs-dismiss="modal" id="btn_print_grn">  
                  Print
              </button>

              <?php 
            }//verified make print available
            else
            {
              ?>
              <button type="button" class="btn bg-danger-subtle text-danger  waves-effect" data-bs-dismiss="modal" id="close_submit_grndetail">
                Close
              </button>
              <?php 
            }//cancled or undefined
            ?>

            
            
            

        </div>

        </form>

        </div>
        
      </div>
      <!-- /.modal-content -->
    </div>
  <!-- /.modal-dialog -->
  </div>