<div id="grn_product_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
      <div class="modal-content">
        <div class="modal-header d-flex align-items-center">
          <h4 class="modal-title" id="myModalLabel">
            Add Products
          </h4>
          <button type="button" class="btn-close" id="close_products_modal" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
        
        <form action="" method="POST" enctype="multipart/form-data">
        <div class="row">
            <!-- hidden input -->
            <input type="hidden" name="hide_product_id" id="hide_product_id" value="0">

          <div class="col-md-6">
            <div class="mb-3">
              <label for="" id="lbl_product">Product No</label>
              <input type="text" name="product_no" id="product_no" class="form-control mb-2" placeholder="PD_000000" disabled>
            </div>
          </div>
            
            <?php 
            $shopObj = new Shop();
            $shop_id = $_SESSION['shop_id'];

            if($shopObj->hasCategories($shop_id))
            {
              ?>
              <div class="col-md-12">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="">Category <span class="text-danger text-alrt" style="display:none;">*</span></label>
                    <select name="cmb_category" id="cmb_category" class="form-select dropdown-toggle required">
                        <option value="0">=== Select Category ===</option>
                        <?php 
                        $shop_id = $_SESSION['shop_id'];
                        $catObj = new Category();
                        $catData = $catObj->getCategoryByShop($shop_id);
                        foreach($catData as $row)
                        {
                            ?>
                            <option value="<?php echo $row['CTID'];?>">
                              <?php echo $row['CategoryName'];?>
                            </option>
                            <?php 
                        }//foreach
                        ?>
                    </select>
                    <span class="text-danger" id="alrt" style="display:none;">This Field is Required</span>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="">Sub Category <span class="text-danger text-alrt" style="display:none;">*</span></label>
                    <select name="cmb_subcategory" id="cmb_subcategory" class="form-select dropdown-toggle required">
                        <option value="">=== Select Sub Category ===</option>
                    </select>
                    <span class="text-danger" id="alrt" style="display:none;">This Field is Required</span>
                  </div>
                  
                </div>
              </div>
              <?php 
            }//has categories
            ?>

            <div class="col-md-6 mb-3">
              <label for="" class="">Barcode</label>
              <input type="text" name="barcode" id="barcode" class="form-control mb-2 " placeholder="Barcode">
            </div> 
            
            <div class="col-md-6 mb-3">
              <label for="" class="">Product Name <span class="text-danger text-alrt" style="display:none;">*</span></label>
              <input type="text" name="prod_name" id="prod_name" class="form-control mb-2 required" placeholder="Product Name">
              <span class="text-danger" id="alrt" style="display:none;">This Field is Required</span>
            </div>
            
            <?php 
            if($shopObj->hasSecondLanguage($shop_id))
            {
              ?>
                <div class="col-md-6 mb-3">
                  <label for="" class="">Second Name <span class="text-danger text-alrt" style="display:none;">*</span></label>
                  <input type="text" name="second_name" id="second_name" class="form-control mb-2 required" placeholder="Second Name">
                    <span class="text-danger" id="alrt" style="display:none;">This Field is Required</span>
                </div>
              <?php 
            }//has second language
            ?>
            
            <!-- description -->
            <div class="col-md-6 mb-3">
              <label for="" class="">Description (optional)</label>
              <textarea name="prod_description" id="prod_description" cols="30" rows="3" class="form-control" aria-label="Description">
              </textarea>
            </div>

            <?php 
            if($shopObj->hasInventory($shop_id) != 1)
            {
              ?>
              <div class="col-md-12">
                <span class="text-danger">Note: - Prices must be entered in the selling unit price.</span>
              </div>
              <div class="col-md-12">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="" class="">Purchase Price <span class="text-danger text-alrt" style="display:none;">*</span></label>
                    <input type="number" step="0.01" name="prod_purchase_price" id="prod_purchase_price" class="form-control mb-2  required" placeholder="0.00 ">
                    <span class="text-danger" id="alrt" style="display:none;">This Field is Required</span>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="" class="">Selling Price <span class="text-danger text-alrt" style="display:none;">*</span></label>
                    <input type="number" step="0.01" name="prod_selling_price" id="prod_selling_price" class="form-control mb-2  required" placeholder="0.00 ">
                    <span class="text-danger" id="alrt" style="display:none;">This Field is Required</span>
                  </div>
                </div>
              </div>
              <?php 
            }//doesn't have an inventory
            ?>
            
            <?php 
            if($shopObj->hasCartonQty($shop_id))
            {
              ?>
              <div class="col-md-6 mb-3">
                <label for="prod_carton_qty">Carton Qty</label>
                <input type="number" name="prod_carton_qty" id="prod_carton_qty" class="form-control mb-2" placeholder="Carton Qty">
              </div>
              <?php
            }//has carton qty
            ?>
             
            <?php 
            if($shopObj->hasService($shop_id))
            {
              ?>
              <!-- <div class="col-md-6 service">
                <label for="">Service</label>
                <div class="form-check form-switch mb-3">
                  <input class="form-check-input" name="chk_service" id="chk_service" type="checkbox">
                  <label class="form-check-label" for="chk_service">Use as Service</label>
                </div>
              </div> -->
              <?php 
            }//has service
            ?>
            
            <!-- Units -->
            <div class="col-md-12 p-2 border border-primary rounded">
              <div class="row">
                <div class="mb-1 col-4">
                  <label for="" class="">Purchase Unit</label>
                  <select name="cmb_purchase_unit" id="cmb_purchase_unit" class="form-select dropdown-toggle">
                    <?php 
                    $unitObj = new Unit();
                    $unitData = $unitObj->getAllUnits($shop_id);
                    foreach($unitData as $row)
                    {
                      ?>
                      <option value="<?php echo $row['UNID'];?>"><?php echo $row['UnitName'] ." ~ ". $row['ShortName'];?></option>
                      <?php 
                    }//foreach
                    ?>
                  </select>
                </div>

                <div class="mb-1 col-4">
                  <label for="" class="">Conversion Rate</label>
                  <input type="number" step="0.001" value="1" name="conversion_rate" id="conversion_rate" class="form-control mb-2" placeholder="Conversion Rate" required>
                </div>
                  
                <div class="mb-1 col-4">
                  <label for="" class="">Selling Unit</label>
                  <select name="cmb_selling_unit" id="cmb_selling_unit" class="form-select dropdown-toggle">
                    <?php 
                    $unitObj = new Unit();
                    $unitData = $unitObj->getAllUnits($shop_id);
                    foreach($unitData as $row)
                    {
                      ?>
                      <option value="<?php echo $row['UNID'];?>"><?php echo $row['UnitName'] ." ~ ". $row['ShortName'];?></option>
                      <?php 
                    }//foreach
                    ?>
                  </select>
                </div>
              </div>
            </div>

            <!-- <div class="col-md-6 mb-3 image">
              <label for="prod_image" class="form-label mt-2">Product Image</label>
              <input type="file" name="prod_image" id="prod_image" class="form-control">
              <span class="text-success" id="success" style="display: none;">Ok</span>
              <span class="text-danger" style="display:none;" id="danger" style="display: none;"> &#9888; Product image size cannot be higher than 500KB.</span>
              
              <div class="mb-3 rounded">
                <img src="../Assets/Images/icons/product.png" id="img_product" class="shadow img-fluid mx-auto d-block mt-2 rounded bordered" alt="Product Image" accept="image/png, image/gif, image/jpeg">
              </div>
            </div> -->
 
        </div>

        <div class="modal-footer">
          <!-- update -->
            <!-- <button type="submit" name="btn_update_product" class="btn bg-primary-subtle text-primary waves-effect" data-bs-dismiss="modal" id="btn_update_product">
              Update
            </button> -->
            <!-- save -->
            <button type="button" name="btn_save_product" id="btn_save_product" class="btn bg-primary-subtle text-primary waves-effect" data-bs-dismiss="modal" id="btn_save_product">
              Save Product
            </button>
            <!-- <button type="button" class="btn bg-primary-subtle text-primary waves-effect" id="btn_save_product2" style="display:none;">
              Save
            </button> -->
<!--         
          <button type="button" class="btn bg-danger-subtle text-danger  waves-effect" data-bs-dismiss="modal" id="close_product_modal">
            Close
          </button> -->

        </div>

        </form>

        </div>
        
      </div>
      <!-- /.modal-content -->
    </div>
  <!-- /.modal-dialog -->
  </div>