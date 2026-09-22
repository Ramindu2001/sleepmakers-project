<div class="modal" tabindex="-1" role="dialog" id="customer_payment_modal">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Customer Credit Payment</h5>
        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close" id="btn_close_credit_payment">
          <!-- <span aria-hidden="true">&times;</span> -->
        </button>
      </div>

      <div class="modal-body">
        <form action="../Controller/CustomerCreditController.php" method="post">
          <!-- Using hidden fields to match database column names -->
          <input type="hidden" name="cus_id" id="cus_id" value="<?php echo isset($_GET['cus_id']) ? $_GET['cus_id'] : '0'; ?>">
          <input type="hidden" name="shop_id" id="shop_id" value="<?php echo $shop_id; ?>">
          
          <div class="row mb-3">
            <div class="col-md-6">
              <h5 id="customer_name">Payment for: <?php 
                if(isset($_GET['cus_id'])) {
                  $credit = new credit_customer();
                  $customer = $credit->select_credit_customer($_GET['cus_id']);
                  echo !empty($customer) ? $customer[0]['CustomerName'] : 'Customer';
                }
              ?></h5>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="multi_payment_type" class="form-label">Payment Method</label>
                <select name="payment_type" id="multi_payment_type" class="form-select">
                <?php 
                    $sql = "SELECT * FROM shoppaymethod
                    INNER JOIN paymethod ON paymethod.PMID = shoppaymethod.paymethod_PMID
                    WHERE shop_SHID = ".$shop_id.";";

                    $dbObj = new DBTransactions();
                    $dbPaymethods = $dbObj->getData($sql);
                    foreach($dbPaymethods as $row) {
                        ?>
                        <option value="<?php echo $row['paymethod_PMID'];?>"><?php echo $row['PaymethodName'];?></option>
                        <?php 
                    }
                ?>
                </select>
              </div>
            </div>
          </div>
          
          <!-- Payment details for cheque if selected -->
          <div id="cheque_details" class="row mb-3" style="display: none;">
            <div class="col-md-4">
              <div class="form-group">
                <label for="multi_cheque_number" class="form-label">Cheque Number</label>
                <input type="text" name="cheque_number" id="multi_cheque_number" class="form-control">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label for="multi_cheque_date" class="form-label">Cheque Date</label>
                <input type="date" name="cheque_date" id="multi_cheque_date" class="form-control">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label for="multi_bank_name" class="form-label">Bank Name</label>
                <input type="text" name="bank_name" id="multi_bank_name" class="form-control">
              </div>
            </div>
          </div>
          
          <div class="table-responsive">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th>Invoice</th>
                  <th>Due Amount</th>
                  <th>Payment Amount</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="tbody">
                <!-- Rows will be added dynamically -->
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="2" class="text-end"><strong>Total Payment:</strong></td>
                  <td colspan="2"><strong id="total_payment">0.00</strong></td>
                </tr>
              </tfoot>
            </table>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <button type="button" class="btn btn-success" id="add">Add Invoice</button>
            </div>
            <div class="col-md-6">
              <button type="submit" name="submit_payment" class="btn btn-primary float-end">Submit Payment</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  // Show/hide cheque details based on payment method
  $(document).ready(function() {
    $("#multi_payment_type").change(function() {
      // Assuming payment method ID 2 is for cheque (adjust as needed)
      if($(this).val() == "2") {
        $("#cheque_details").show();
      } else {
        $("#cheque_details").hide();
      }
    });
  });
</script>