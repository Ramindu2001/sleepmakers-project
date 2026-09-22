<div class="modal" tabindex="-1" role="dialog" id="receipt_print_modal">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Re-print Invoices</h5>
        <button type="button" id="close_reprint_modal" class="btn-close" data-dismiss="modal" aria-label="Close"> </button>
      </div>

      <div class="modal-body">
        <label for="">Search Invoice</label>
        <input type="text" name="search_issued_invoice" id="search_issued_invoice" class="form-control" placeholder="Invoice Number">
        
        <div style="max-height: 500px; overflow:scroll; overflow-x:hidden;">
        <table id="tbl_receipt_print">
            <tr>
                <th>Date</th>
                <th>Invoice No</th>
                <th>Amount</th>
                <th>Action</th>
            </tr>
            <?php 
                $shop_id = $_SESSION['shop_id'];
                $sql = "SELECT * FROM invoiceheader WHERE shop_SHID = ".$shop_id." ORDER BY IHID DESC LIMIT 20;";
                $dbObj = new DBTransactions();
                $invData = $dbObj->getData($sql);

                foreach($invData as $row)
                {
                    ?>
                    <tr>
                        <td><?php echo $row['InvEndTime'];?></td>
                        <td><?php echo $row['BillNo'];?></td>
                        <td><?php echo $row['NetAmount'];?></td>
                        <td>
                            <?php 
                            //get receipts
                            $sql_1 = "SELECT * FROM shopreceipts WHERE shop_id = ".$shop_id.";";
                            $receiptData = $dbObj->getData($sql_1);
                            foreach($receiptData as $row_1)
                            {
                              $receipt_file_name = $row_1['ReceiptPath'];
                              $receipt_name = $row_1['receiptName'];
                              ?>
                              <a href="../Receipts/<?php echo $receipt_file_name;?>?invoice_id=<?php echo $row['IHID'];?>" class="btn btn-info"><i class="ti ti-printer"></i> <?php echo $receipt_name;?></a>
                              <?php 
                            }//foreach 1
                            ?>
                            
                        </td>
                    </tr>
                    <?php 
                }//foreach
            ?>
        </table>
        </div>
        
      </div>

      <!-- <div class="modal-footer">
        <button type="button" class="btn btn-primary">Save changes</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div> -->

    </div>
  </div>
</div>