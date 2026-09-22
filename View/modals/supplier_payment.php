<div class="modal" tabindex="-1" role="dialog" id="supplier_payment_modal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Supplier credit payment</h5>
        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close" id="btn_close_credit_payment">
          <!-- <span aria-hidden="true">&times;</span> -->
        </button>
      </div>
      <div class="modal-body">
        <form action="../Controller/SupplierCreditController.php" method="post">

        <p id="p_sup_details" class="row" style="font-size: 12px;"></p>

        <table class="table" id="tbl_grn_details"></table>
        
        <div class="row mt-3" id="payment_method">
            <input type="hidden" name="hide_grn_header_id" id="hide_grn_header_id" value="0">
            <input type="hidden" name="hide_credit_supplier_id" id="hide_credit_supplier_id" value="0">
            <input type="hidden" name="hide_grn_total" id="hide_grn_total" value="0">
            <input type="hidden" name="hide_supplier_id" id="hide_supplier_id" value="0">

            <div class="col-md-5">
                <label for="" class="form-label">Payment Method</label>
                <select name="cmb_paymethod" id="cmb_paymethod" class="cmb_paymethod form-select">
                <?php 
                    $sql = "SELECT * FROM shoppaymethod
                    INNER JOIN paymethod ON paymethod.PMID = shoppaymethod.paymethod_PMID
                    WHERE shop_SHID = ".$shop_id."  AND (paymethod.PMID!=6 AND paymethod.PMID!=4  AND paymethod.PMID!=9  AND paymethod.PMID!=10 AND paymethod.PMID!=12);";

                    $dbObj = new DBTransactions();
                    $dbPaymethods = $dbObj->getData($sql);
                    $count = 0;
                    foreach($dbPaymethods as $row)
                    {
                        $is_checked = $count==0 ? 'checked' : '';
                        ?>
                        <option value="<?php echo $row['paymethod_PMID'];?>"><?php echo $row['PaymethodName'];?></option>
                        <?php 
                        $count += 1;
                    }//foreach
                ?>
                </select>
            </div>
            <div class="col-md-5">
                <label for="" class="form-label">Paying Amount</label>
                <input type="number" step="0.01" id="credit_payment" class="form-control">
            </div>

            <div class="col-md-2">
                <button type="button" class="btn btn-primary mt-3" id="btn_add_payment">Add</button>
            </div>
        </div>
        <table class="table" id="tbl_transaction"></table>
        <div class="row">
            <div class="col-md-6">
                <p style="text-align: left;" id="p_balance">Balance</p>
            </div>
            <div class="col-md-6">
                <button type="submit" name="btn_submit_payment2" class="btn btn-primary float-end">Submit Payment</button>
            </div>
        </div>
        </form>
      </div>
      <!-- <div class="modal-footer">
        <button type="button" class="btn btn-primary">Submit Payment</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div> -->
      
    </div>
  </div>
</div>