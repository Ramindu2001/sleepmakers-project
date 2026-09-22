<div class="modal" tabindex="-1" role="dialog" id="open-bill-counter" style="background:#00000080;">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Bill Counters</h5>
        <button type="button" class="btn btn-danger" data-dismiss="modal" aria-label="Close" id="btn_close_bill_counter">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">

        <?php
        $counterObj = new Counter();
        $counterData = $counterObj->getCounterByUserID($user_id,$shop_id);
        if (empty($counterData)) {
          ?>
          <p style="margin: 2px;">Hi <?php echo $user[0]['UserName']; ?>, Do you want to create a Bill Counter?</p>

          <form action="../Controller/counterController.php" method="POST" id="counter-form">
            <!-- <select name="counter" id="counter" class="form-select mb-2" placeholder="Select a Counter" required>
              <?php
              // $counterObj = new AddCounterModels();
              // $Data = $counterObj->getCounters();
              // foreach ($Data as $row) {
                ?>
                <option value="<?php //echo $row['CTID']; ?>" data-counter-no="<?php //echo $row['counterNo']; ?>" class="form-control mb-2">
                  <?php //echo $row['counterNo']; ?>
                </option>
                <?php
              //}//foreach
              ?>
            </select> -->

            <!-- <input type="hidden" name="CounterNo" id="CounterNo"> -->
            <!-- <input type="number" step="0.01" name="start_amount" id="start_amount" placeholder="Start Amount" class="form-control" required> -->
            <!-- <button type="submit" name="btn_start_counter" class="btn btn-primary">Start Counter</button> -->
          </form>
            <?php 
            }//if no bill counter
            else
            {
              header("Location: ../../Public/home.php");
            }//has counter
        ?>

        <!------------------- denomination -------------------->
        <form action="../Controller/counterController.php" method="POST">
        <div class="row">
            <div class="col-md-6">
              <table>
                <tr>
                  <td>Rs: 5000</td>
                  <td style="margin: 0px;">
                    <input type="number"  class="form-control" name="rs_5000" id="rs_5000">
                  </td>
                  <td><p id="val_5000" style="margin: 0px; text-align:right;">0</p></td>
                </tr>
                <tr>
                  <td>Rs: 1000</td>
                  <td>
                    <input type="number"  class="form-control" name="rs_1000" id="rs_1000">
                  </td>
                  <td><p id="val_1000" style="margin: 0px; text-align:right;">0</p></td>
                </tr>
                <tr>
                  <td>Rs: 500</td>
                  <td>
                    <input type="number"  class="form-control" name="rs_500" id="rs_500">
                  </td>
                  <td><p id="val_500" style="margin: 0px; text-align:right;">0</p></td>
                </tr>
                <tr>
                  <td>Rs: 100</td>
                  <td>
                    <input type="number"  class="form-control" name="rs_100" id="rs_100">
                  </td>
                  <td><p id="val_100" style="margin: 0px; text-align:right;">0</p></td>
                </tr>
                <tr>
                  <td>Rs: 50</td>
                  <td>
                    <input type="number"  class="form-control" name="rs_50" id="rs_50">
                  </td>
                  <td><p id="val_50" style="margin: 0px; text-align:right;">0</p></td>
                </tr>
                <tr>
                  <td>Rs: 20</td>
                  <td>
                    <input type="number"  class="form-control" name="rs_20" id="rs_20">
                  </td>
                  <td><p id="val_20" style="margin: 0px; text-align:right;">0</p></td>
                </tr>
                <tr>
                  <td>Rs: 10</td>
                  <td>
                    <input type="number"  class="form-control" name="rs_10" id="rs_10">
                  </td>
                  <td><p id="val_10" style="margin: 0px; text-align:right;">0</p></td>
                </tr>
                <tr>
                  <td>Rs: 5</td>
                  <td>
                    <input type="number"  class="form-control" name="rs_5" id="rs_5">
                  </td>
                  <td><p id="val_5" style="margin: 0px; text-align:right;">0</p></td>
                </tr>
                <tr>
                  <td>Rs: 2</td>
                  <td>
                    <input type="number"  class="form-control" name="rs_2" id="rs_2">
                  </td>
                  <td><p id="val_2" style="margin: 0px; text-align:right;">0</p></td>
                </tr>
                <tr>
                  <td>Rs: 1</td>
                  <td>
                    <input type="number" class="form-control" name="rs_1" id="rs_1">
                  </td>
                  <td><p id="val_1" style="margin: 0px; text-align:right;">0</p></td>
                </tr>

              </table>
            </div>
            <div class="col-md-6">
                <label for="" class="form-label mt-3">Start Amount</label>
                <input type="number" step="0.01" class="form-control" name="start_amount" id="start_amount" placeholder="0.00">

                <label for="" class="form-label mt-3">End Amount</label>
                <input type="number" step="0.01" class="form-control" name="end_amount" placeholder="0.00" disabled>

                <button class="btn btn-primary w-100 mt-3" name="btn_start_counter">Start Counter</button>
            </div>
        </div>
        </form>

      </div>
    </div>
  </div>
</div>
