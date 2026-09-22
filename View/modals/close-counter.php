<div class="modal" tabindex="-1" role="dialog" id="close-bill-counter" style="background:#00000080;">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Close Bill Counter</h5>
        <button type="button" class="btn btn-close" data-bs-dismiss="modal" aria-label="Close" id="btn_close_bill_counter">
          <!-- <span aria-hidden="true">&times;</span> -->
        </button>
      </div>
      <div class="modal-body">
        <?php 
            $counterData = $counterObj->getCounterByUserID($user_id,$shop_id);
            if(empty($counterData))
            {}//if no bill counter
            else
            {
              $counterObj = new Counter();
              $counterData = $counterObj->getCounterTotalByUser($user_id);

              $counter_total = floatval($counterData[0]['CounterTotal']);

              $counterData_1 = $counterObj->getOneCounterByUser($user_id);
              $start_amount = floatval($counterData_1[0]['StartBalance']);
              $end_balance = $start_amount + $counter_total;

              $userObj = new User();
              $user = $userObj->getOneUser($user_id);
              $bill_counter_id=$counterData_1[0]["CCID"];
              ?>

              <p>Hi <?php echo $user[0]['UserName'];?>, Do you want to close the counter?</p>

              <!---------------------- Denomination ----------------------->
              <form action="../Controller/counterController.php" method="POST">
              <div class="row">
                <!-- left -->
                <div class="col-md-6">
                    <table>
                      <tr>
                        <td>Rs: 5000</td>
                        <td style="margin: 0px;">
                          <input type="number"  class="form-control" name="rs_5000_c" id="rs_5000_c">
                        </td>
                        <td><p id="val_5000_c" style="margin: 0px; text-align:right;">0</p></td>
                      </tr>
                      <tr>
                        <td>Rs: 1000</td>
                        <td>
                          <input type="number"  class="form-control" name="rs_1000_c" id="rs_1000_c">
                        </td>
                        <td><p id="val_1000_c" style="margin: 0px; text-align:right;">0</p></td>
                      </tr>
                      <tr>
                        <td>Rs: 500</td>
                        <td>
                          <input type="number"  class="form-control" name="rs_500_c" id="rs_500_c">
                        </td>
                        <td><p id="val_500_c" style="margin: 0px; text-align:right;">0</p></td>
                      </tr>
                      <tr>
                        <td>Rs: 100</td>
                        <td>
                          <input type="number"  class="form-control" name="rs_100_c" id="rs_100_c">
                        </td>
                        <td><p id="val_100_c" style="margin: 0px; text-align:right;">0</p></td>
                      </tr>
                      <tr>
                        <td>Rs: 50</td>
                        <td>
                          <input type="number"  class="form-control" name="rs_50_c" id="rs_50_c">
                        </td>
                        <td><p id="val_50_c" style="margin: 0px; text-align:right;">0</p></td>
                      </tr>
                      <tr>
                        <td>Rs: 20</td>
                        <td>
                          <input type="number"  class="form-control" name="rs_20_c" id="rs_20_c">
                        </td>
                        <td><p id="val_20_c" style="margin: 0px; text-align:right;">0</p></td>
                      </tr>
                      <tr>
                        <td>Rs: 10</td>
                        <td>
                          <input type="number" class="form-control" name="rs_10_c" id="rs_10_c">
                        </td>
                        <td><p id="val_10_c" style="margin: 0px; text-align:right;">0</p></td>
                      </tr>
                      <tr>
                        <td>Rs: 5</td>
                        <td>
                          <input type="number" class="form-control" name="rs_5_c" id="rs_5_c">
                        </td>
                        <td><p id="val_5_c" style="margin: 0px; text-align:right;">0</p></td>
                      </tr>
                      <tr>
                        <td>Rs: 2</td>
                        <td>
                          <input type="number" class="form-control" name="rs_2_c" id="rs_2_c">
                        </td>
                        <td><p id="val_2_c" style="margin: 0px; text-align:right;">0</p></td>
                      </tr>
                      <tr>
                        <td>Rs: 1</td>
                        <td>
                          <input type="number" class="form-control" name="rs_1_c" id="rs_1_c">
                        </td>
                        <td><p id="val_1_c" style="margin: 0px; text-align:right;">0</p></td>
                      </tr>
                    </table>
                </div>

                <!-- right -->
                <div class="col-md-6">
                  <!-- close counter -->
                  <p>
                      Start Amount : <b><?php echo $start_amount;?></b><br>
                      End Amount : <b><?php echo $end_balance;?></b><br>
                      Net Balance : <b><?php echo $counter_total;?></b>
                  </p>

                  <p id="red_notice">Please confirm Net Balance before Closing Counter.</p>

                  <!-- sales by paymethods -->
                   <?php 
                    $dbObj = new DBTransactions();

                    $sql = "SELECT SUM(transactions.TransferAmount) AS transfer_amount, paymethod.PaymethodName FROM `invoiceheader`
                    INNER JOIN transactions ON transactions.InvoiceHeader_IHID = invoiceheader.IHID
                    INNER JOIN paymethod ON paymethod.PMID = transactions.paymethod_PMID
                    WHERE CashCounter_CCID = ".$bill_counter_id." GROUP BY transactions.paymethod_PMID;";

                    $payData = $dbObj->getData($sql);

                    foreach($payData as $row)
                    {
                      ?>
                      <p style="margin: 0px;">
                        <?php echo $row['PaymethodName'] . " : <b>" . $row['transfer_amount'] . "</b>";?> <br>
                      </p>
                      <?php 
                    }//foreach
                   ?>

                  <!-- <form action="../Controller/counterController.php" method="POST">
                      
                      <button class="btn btn-primary" name="btn_end_counter">Close Counter</button>
                  </form> -->

                  <input type="hidden" name="hide_bill_counter_id" value="<?php echo $bill_counter_id;?>">
                  <input type="hidden" name="hide_end_balance" value="<?php echo $end_balance;?>">
                  <input type="hidden" name="hide_counter_total" value="<?php echo $counter_total;?>">

                  <label for="" class="form-label mt-3">Start Amount</label>
                  <input type="number" step="0.01" class="form-control" name="start_amount" id="start_amount_c" value="<?php echo $start_amount;?>" disabled>

                  <label for="" class="form-label mt-3">End Amount</label>
                  <input type="number" step="0.01" class="form-control" name="end_amount" id="end_amount" placeholder="0.00">

                  <button class="btn btn-success w-100 mt-3" name="btn_end_counter">Close Counter</button>
                </div>
              </div>
              </form>

              <?php 
            }//if bill counter is not closed
        ?>
      </div>
    </div>
  </div>
</div>
