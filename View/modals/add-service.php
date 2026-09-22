<div class="modal" tabindex="-1" role="dialog" id="modal_service">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Services</h5>
        <button type="button" id="close_service_modal" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
                <select name="cmb_services" id="cmb_services" class="form-select">
                  <?php 
                    $sql = "SELECT * FROM products WHERE shop_SHID = ".$shop_id." AND ItemType = 'S';";

                    $dbObj = new DBTransactions();
                    $serviceData = $dbObj->getData($sql);

                    foreach($serviceData as $row)
                    {
                      ?>
                      <option value="<?php echo $row['PDID'];?>"><?php echo $row['Barcode'] ." - ". $row['ItemName'];?></option>
                      <?php 
                    }//foreach
                  ?>
                </select>
                  
                <label for="service_charge" class="mt-2">Service Charge</label>
                <input type="number" step="0.01" name="service_charge" id="service_charge" class="form-control">
                <span class="text-danger" style="display: none;" id="service_charge_warning">Invalid input value</span>
            </div>
            <div class="col-md-12">
                <button class="btn btn-primary mt-2" id="btn_add_service">Add Service</button>
            </div>
        </div>
      </div>

      <!-- <div class="modal-footer">
        <button type="button" class="btn btn-primary">Save changes</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div> -->

    </div>
  </div>
</div>