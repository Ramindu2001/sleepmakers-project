<div class="modal" tabindex="-1" role="dialog" id="modal_invoice_discount">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Invoice Discounts</h5>
        <button type="button" id="close_discount_modal" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="row">
            <!----------------- Discounts ------------------->
            <div class="col-md-6">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="rdb_inv_discount" id="rbd_inv_percent_discount" checked>
                    <label class="form-check-label" for="rbd_inv_percent_discount">% Discount</label>
                </div>
                <input type="number" step="0.01" name="inv_percent_discount" id="inv_percent_discount" class="form-control">
                <span class="text-danger" style="display:none;" id="inv_percent_warning">Invalid Percentage</span>
            </div>

            <div class="col-md-6">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="rdb_inv_discount" id="rdb_inv_line_discount">
                    <label class="form-check-label" for="rdb_inv_line_discount">Line Discount</label>
                </div>
                <input type="number" step="0.01" name="inv_line_discount" id="inv_line_discount" class="form-control">
                <span class="text-danger" style="display:none;" id="inv_line_warning">Invalid Amount</span>
            </div>

            <!-- gross amount -->
            <div class="col-md-4 mt-3">
                <p class="mt-2 p_desc" id="p_gross_amount">
                    <small>Gross Amount</small><br>
                    <b>Rs: 0.00</b>
                </p>
            </div>

            <div class="col-md-4 mt-3">
                <p class="mt-2 p_desc" id="p_inv_discount">
                    <small>Discount</small><br>
                    <b>Rs: 0.00</b>
                </p>
            </div>

            <div class="col-md-4 mt-3">
                <p class="mt-2 p_desc" id="p_net_amount">
                    <small>Net Amount</small><br>
                    <b>Rs: 0.00</b>
                </p>
            </div>
        </div>
          
      </div>

       <div class="modal-footer">
            <button type="button" class="btn bg-primary-subtle text-dark waves-effect" id="btn_invoice_discount">Add Discount</button>
            <!-- <button type="button" class="btn bg-warning-subtle text-warning waves-effect" data-bs-dismiss="modal" id="close_SysFe_modal">Close</button> -->
        </div>

    </div>
  </div>
</div>