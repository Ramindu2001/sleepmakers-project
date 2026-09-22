<div id="transfer_head_modal" class="modal fade show" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-md">
      <div class="modal-content">
        <div class="modal-header d-flex align-items-center">
          <h4 class="modal-title" id="myModalLabel">
            Add new Transfer note
          </h4>
          <button type="button" class="btn-close" id="close_transfer_head_modal" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form action="../Controller/transferController.php" method="post">
          <div class="row">
            <div class="col-md-12">

              <div class="m-2">
                <label for="" class="form-label">Transfer No</label>
                <input type="text" name="transfer_no" id="transfer_no" placeholder="GT_000000" class="form-control" disabled>
              </div>

              <div class="m-2">
                <?php 
                //get date
                date_default_timezone_set("Asia/Colombo");
                $transfer_date = date("Y-m-d");
                ?>
                <label for="" class="form-label">Transfer Date</label>
                <input type="date" name="transfer_date" id="transfer_date" value="<?php echo $transfer_date;?>" class="form-control" disabled>
              </div>

              <div class="m-2">
                <?php 
                $shop_id = $_SESSION['shop_id'];
                $shopObj = new Shop();
                $shopOne = $shopObj->getOneShop($shop_id);
                $shop_name = $shopOne[0]['ShopName'];
                $shop_logo = $shopOne[0]['ShopLogo'];
                $shopLogo="../Assets/Images/shop_images/".$shop_logo;
                if($shopLogo==null)
                {
                    $shopLogo="../Assets/Images/shop_images/no_image.jpg";
                }
                else if(file_exists($shopLogo))
                {
                    $shopLogo="../Assets/Images/shop_images/".$shop_logo;
                }
                else
                {
                    $shopLogo="../Assets/Images/shop_images/no_image.jpg";
                }
                ?>
                <label for="" class="form-label">Transfer From</label>
                <input type="hidden" name="hide_transfer_from" id="hide_transfer_from" value="<?php echo $shop_id;?>">
                <div class="row">
                    <div class="col-md-4">
                        <img src="<?php echo $shopLogo;?>" alt="shop logo" class="img-fluid mx-auto">
                    </div>
                    <div class="col-md-8">
                        <h4><?php echo $shop_name;?></h4>
                    </div>
                </div>
              </div>

              <div class="m-2">
                <label for="" class="form-label">Transfer To</label>
                <select name="cmb_transfer_shop" id="cmb_transfer_shop" class="form-select" required>
                    <?php 
                    $shopObj = new Shop();
                    $shopData = $shopObj->getCompanyfromShop($shop_id);
                    $company_id = $shopData[0]['Company_CMID'];
                    
                    $sql = "SELECT * FROM shop WHERE Company_CMID = ".$company_id." AND SHID != ".$shop_id.";";
                    $dbObj = new DBTransactions();
                    $dbData = $dbObj->getData($sql);
                    if(count($dbData)==0)
                    {
                      ?>
                      <option value=""><b class="text-danger">No Results Found</b></option>
                      <?php 
                    }
                    else
                    {
                      foreach($dbData as $row)
                      {
                          ?>
                          <option value="<?php echo $row['SHID'];?>"><?php echo $row['ShopName'];?></option>
                          <?php 
                      }//foreach 
                    }
                    ?>
                </select>
              </div>
            </div>

            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" name="btn_add_grn_transfer" class="btn bg-primary-subtle text-primary  waves-effect" data-bs-dismiss="modal" id="btn_add_grn_transfer">
              Create New 
            </button>
            <button type="button" class="btn bg-warning-subtle text-warning  waves-effect" data-bs-dismiss="modal" id="close_grn_modal">
              Close
            </button>
          </div>
          </form>
      </div>
      <!-- /.modal-content -->
    </div>
  <!-- /.modal-dialog -->
  </div>