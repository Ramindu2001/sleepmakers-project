<div class="modal" tabindex="-1" role="dialog" id="today_sale_modal">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Today Sale</h5>
        <button type="button" id="close_sale_modal" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <?php 
            $shop_id = $_SESSION['shop_id'];

            //effective date
            date_default_timezone_set("Asia/Colombo");
            $current_date = date("Y-m-d");

            $sql = "SELECT sum(NetAmount) as dayTotal FROM invoiceheader WHERE EffectiveDate = '".$current_date."' AND shop_SHID=".$shop_id." AND InvStat = 1;";

            $dbObj = new DBTransactions();
            $invData = $dbObj->getData($sql);

            $day_total = number_format((float)$invData[0]['dayTotal'], 2, '.', '');

            //get active counter
            $user_id = $_SESSION['user_id'];

            $sql_1 = "SELECT * FROM cashcounter WHERE CounterStat = 1 AND user_USID =".$user_id." AND shop_SHID = ".$shop_id.";";

            $counterData = $dbObj->getData($sql_1);
            $counter_id = $counterData[0]['CCID'];

            $sql_2 = "SELECT sum(NetAmount) as counterTotal FROM invoiceheader WHERE CashCounter_CCID = ".$counter_id." AND shop_SHID = ".$shop_id." AND InvStat = 1;";

            $cashData = $dbObj->getData($sql_2);
            $counter_total = number_format((float)$cashData[0]['counterTotal'], 2, '.', '');
        ?>

        <div class="row">
          <div class="col-md-6">
            <p class="bg-primary text-center text-light rounded-pill p-2">Today Total Sales: <b><?php echo $day_total;?></b></p>
          </div>

          <div class="col-md-6">
            <p class="bg-success text-center text-light rounded-pill p-2">Counter Total Sales: <b><?php echo $counter_total;?></b></p>
          </div>
        </div>

        <!--------------------------------------- Payments ---------------------------------->
        <div class="row">
          <?php 
              $sql_3 = "SELECT * FROM shoppaymethod
              INNER JOIN paymethod ON paymethod.PMID = shoppaymethod.paymethod_PMID
              WHERE shop_SHID = ".$shop_id.";";

              $payData = $dbObj->getData($sql_3);
              foreach($payData as $row)
              {
                  $paymethod_id = $row['paymethod_PMID'];

                  $sql_4 = "SELECT sum(NetAmount) as payTotal, PaymethodName, image_path FROM transactions
                  INNER JOIN invoiceheader ON invoiceheader.IHID = transactions.InvoiceHeader_IHID
                  INNER JOIN paymethod ON paymethod.PMID = transactions.paymethod_PMID
                  WHERE EffectiveDate = '".$current_date."' AND invoiceheader.shop_SHID = ".$shop_id." AND paymethod_PMID = ".$paymethod_id." AND InvStat = 1;";

                  $paymentData = $dbObj->getData($sql_4);
                  $paymeth_image = $row['image_path'];
                  $payment_name = $row['PaymethodName'];
                  $payment_total = empty($paymentData[0]['payTotal']) ? 0: $paymentData[0]['payTotal'];

                  $payment_total = number_format((float)$payment_total, 2, '.', '');

                  ?>
                  <div class="col-md-6">
                    <div id="div_pay_type" class="border border-primary rounded m-1">
                        <div class="row p-2">

                            <div class="col-md-4">
                                <img src="../Assets/Images/paymethod_images/<?php echo $paymeth_image;?>" alt="paymethod image" style="height: 50px; width:auto; max-width: 100%;">
                            </div>

                            <div class="col-md-8">
                                <p><b><?php echo $payment_name ." - ". $payment_total;?></b></p>
                            </div>  
                        </div>
                    </div>  
                  </div>
                  <?php 
              }//foreach pay methods
          ?>
        </div>

        <!----------------------------- Sale Chart -------------------------->
        <div>
        <h5 class="card-title mb-2 fw-semibold px-2">
            Today Hourly Sale
            <button class="float-end" id="btn_refresh_bar" style="border: none; background-color: inherit;">
              <img src="../Assets/Images/icons/refresh.png" alt="refresh" class="img_refresh" style="width: 30px; height:auto;">
            </button>
        </h5>
        <!-- line chart -->
        <div id="line_chart"></div>
        </div>

        

      </div>

    </div>
  </div>
</div>