<div id="transferdetail_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header d-flex align-items-center">
          <h4 class="modal-title" id="myModalLabel">
            Submit GRN Transfer
          </h4>
          <button type="button" class="btn-close" id="close_transferdetail_modal" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
        
        <form action="../Controller/transferController.php" method="POST">
        <div class="row">
          <!-- left column -->
          <div class="col">
            <p class="lead">Do you want to submit GRN Transfer?</p>

            <div>
                <dl class="row">
                    <dt class="col-sm-6">Row Count: </dt>
                    <dd class="col-sm-6" id="data_row_count"></dd>

                    <dt class="col-sm-6">Transfer Qty: </dt>
                    <dd class="col-sm-6" id="transfer_item_count"></dd>

                    <dt class="col-sm-6">Receive Qty: </dt>
                    <dd class="col-sm-6" id="receive_item_count"></dd>

                    <dt class="col-sm-6">Purchase Price: </dt>
                    <dd class="col-sm-6" id="data_purchase_price"></dd>
                </dl>
            </div>

            <!-- hidden input -->
            <input type="hidden" name="hide_transferheader_id" id="hide_transferheader_id" value="0">

          </div>
        </div>

        <div class="modal-footer">
            <?php 
            if($transfer_header_stat == '0')
            {
              ?>
              <!-- Add to pending -->
              <button type="submit" name="btn_pending_transfer" class="btn bg-primary-subtle text-primary waves-effect" data-bs-dismiss="modal" id="btn_pending_grn">  
                  Transfer Items
              </button>
              <?php 
            }//on hold make submit
            else if($transfer_header_stat == '1')
            {
              $transObj = new Transfer();
              $transData = $transObj->getOneTransferHeader($transfer_header_id);
              $from_shop = $transData[0]['TransferFrom'];
              $to_shop = $transData[0]['TransferTo'];

              if($from_shop == $shop_id)
              {
                ?>
                <!-- Add to cancle -->
                <button type="submit" name="btn_cancle_transfer" class="btn bg-danger-subtle text-danger waves-effect" data-bs-dismiss="modal" id="btn_cancle_transfer">  
                  Cancle Transfer
                </button>
                <?php 
              }//transfer shop

              if($to_shop == $shop_id)
              {
                ?>
                <!-- Add to store -->
                <button type="submit" name="btn_verify_transfer" class="btn bg-success-subtle text-success waves-effect" data-bs-dismiss="modal" id="btn_verify_transfer">  
                  Verify Transfer
                </button>
                <!-- Add to cancle -->
                <button type="submit" name="btn_cancle_transfer" class="btn bg-danger-subtle text-danger waves-effect" data-bs-dismiss="modal" id="btn_cancle_transfer">  
                  Cancle Transfer
                </button>
                <?php 
              }//receive shop
              
            }//on pending make verify or cancle
            else if($transfer_header_stat == '2')
            {
              ?>
              <!-- Add to pending -->
              <button type="button" name="btn_print_transfer" class="btn bg-warning-subtle text-warning waves-effect" data-bs-dismiss="modal" id="btn_print_grn">  
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