<div class="modal" tabindex="-1" role="dialog" id="modal_hold_invoice">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Hold Invoice</h5>
        <button type="button" id="close_hold_modal" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body" style="max-height: 500px; overflow:scroll; overflow-x:hidden">
        <input type="text" id="search_invoice" class="form-control" placeholder="Search">

        <div style="max-height: 500px; overflow:scroll; overflow-x:hidden">
        <table id="tbl_hold_sale">
          <tr>
            <th>No</th>
            <th>Bill No</th>
            <th>Action</th>
          </tr>

          <?php 
            $user_id = $_SESSION['user_id'];
            $shop_id = $_SESSION['shop_id'];

            $sql = "SELECT * FROM sellheader WHERE user_id=".$user_id." AND sellheader.shop_id=".$shop_id." AND SellStat = 0;";

            $holdData = $dbObj->getData($sql);
            $count = 0;
            foreach($holdData as $row)
            {
              $count +=1;
              ?>
              <tr data-id="<?php echo $row['SHID'];?>">
                <td><?php echo $count;?></td>
                <td><?php echo $row['tmp_bill_no'];?></td>
                <td>
                  <button class="btn btn-primary btn_open_hold_sale" >Open</button>
                </td>
              </tr>
              <?php
            }//foreach
          ?>
        </table>
        </div>
        
      </div>
    </div>
  </div>
</div>