<div id="srndetail_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header d-flex align-items-center">
          <h4 class="modal-title" id="myModalLabel">
            Submit Supplier Return
          </h4>
          <button type="button" class="btn-close" id="close_srndetail_modal" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
        
        <form action="../Controller/SupplierReturnController.php" method="POST">
        <div class="row">
          <!-- left column -->
          <div class="col">
            <p class="lead">Do you want to submit SRN?</p>

            <!-- hidden input -->
            <input type="hidden" name="hide_srnheader_id" id="hide_srnheader_id" value="0">

          </div>
        </div>

        <div class="modal-footer">
            <?php 
            if($return_header_stat == '0')
            {
              ?>
              <!-- Add to pending -->
              <button type="submit" name="btn_pending_srn" class="btn bg-primary-subtle text-primary waves-effect" data-bs-dismiss="modal" id="btn_pending_srn">  
                  Add to Pending
              </button>
              <?php 
            }//on hold make submit
            else if($return_header_stat == '1')
            {
              ?>
              <!-- Add to store -->
              <button type="submit" name="btn_verify_srn" class="btn bg-success-subtle text-success waves-effect" data-bs-dismiss="modal" id="btn_verify_srn">  
                  Proceed
              </button>
              <!-- Add to cancle -->
              <button type="submit" name="btn_cancel_srn" class="btn bg-danger-subtle text-danger waves-effect" data-bs-dismiss="modal" id="btn_cancel_srn">  
                  Cancel
              </button>
              <?php 
            }//on pending make verify or cancel
            else if($return_header_stat == '2')
            {
              ?>
              <!-- Add to pending -->
              <button type="button" name="btn_print_srn" class="btn bg-warning-subtle text-warning waves-effect" data-bs-dismiss="modal" id="btn_print_srn">  
                  Print
              </button>

              <?php 
            }//verified make print available
            else
            {
              ?>
              <button type="button" class="btn bg-danger-subtle text-danger  waves-effect" data-bs-dismiss="modal" id="close_submit_srndetail">
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