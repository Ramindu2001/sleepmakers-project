<div id="subcategory_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
      <div class="modal-content">
        <div class="modal-header d-flex align-items-center">
          <h4 class="modal-title" id="myModalLabel">
            Add Sub Category
          </h4>
          <button type="button" class="btn-close" id="close_subcategory_modal" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
        
        <form action="../Controller/categoryController.php" method="POST">
          <!-- hidden input -->
          <input type="hidden" name="hide_subcat_id" id="hide_subcat_id" value="0">
        <div class="row">
          <div class="col-lg-12">
            <label for="" id="lblSubcategory">Select Category No</label>
            <input type="text" name="subcat_no" id="subcat_no" value="0" class="form-control mb-2 w-50" placeholder="Sub Category Name" disabled>
          </div>
          <!-- left column -->
          <div class="col-lg-6">
            
            <label for="cmb_main_category">Select Category</label>
            <select name="cmb_main_category" id="cmb_main_category" class="form-select dropdown-toggle mb-2">

            <?php 
            $dbObj = new DBTransactions();
            $sql = "SELECT * FROM shop
            INNER JOIN company ON company.CMID = shop.Company_CMID
            WHERE SHID = ".$shop_id.";";

            $shopData = $dbObj->getData($sql);
            $multi_category = floatval($shopData[0]['is_multicategory']);
            $company_id = floatval($shopData[0]['CMID']);

            if($multi_category == 1)
            {
              $sql = "SELECT * FROM `categories`
              INNER JOIN shop ON shop.SHID = categories.shop_SHID
              WHERE shop.Company_CMID = ".$company_id.";";
            }//has multi category
            else
            {
              $sql = "SELECT * FROM `categories` WHERE shop_SHID = ".$shop_id.";";
            }//no multi category

            $catData = $dbObj->getData($sql);

            // $subcatObj = new Category();
            // $subcatData = $subcatObj->getCategoryByShop($shop_id,$multi_category,$company_id);

            foreach($catData as $row)
            {
                // $is_selected = "";
                // if($row['CTID'] == $category_id){$is_selected = "selected";}
                // else{$is_selected = "";}
                ?>
                <option value="<?php echo $row['CTID']?>"><?php echo $row['CategoryName'];?></option>
                <?php 
            }//foreach
            ?>
            </select>
          </div>

          <!--- right column -->
          <div class="col-lg-6">
              <label for="" class="">Sub category Name</label>
              <input type="text" name="subcat_name" id="subcat_name" class="form-control mb-2" placeholder="Sub Category Name" required>
          </div>

          <!-- Barcode prefix code. Kept short and alphanumeric because it is
               pasted straight into generated barcodes. -->
          <div class="col-lg-6">
              <label for="subcat_code" class="">Barcode Code <small class="text-muted">(optional)</small></label>
              <input type="text" name="subcat_code" id="subcat_code" class="form-control mb-1 text-uppercase"
                     maxlength="12" placeholder="e.g. JUI">
              <small class="text-muted d-block mb-2">
                  Used as the <strong>{SUB}</strong> part of an automatic barcode.
                  Leave it empty and the first letters of the sub category name are used.
              </small>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" name="btn_delete_subcat" class="btn bg-danger-subtle text-danger waves-effect" data-bs-dismiss="modal" id="btn_delete_subcat">
            Delete
          </button>
          <button type="submit" name="btn_update_subcat" class="btn bg-primary-subtle text-primary waves-effect" data-bs-dismiss="modal" id="btn_update_subcat">
            Update
          </button>
          <button type="submit" name="btn_save_subcat" class="btn bg-primary-subtle text-primary waves-effect" data-bs-dismiss="modal" id="btn_save_subcat">
            Save
          </button>
          <button type="button" class="btn bg-warning-subtle text-warning  waves-effect" data-bs-dismiss="modal" id="close_company_modal">
            Close
          </button>
        </div>

        </form>

        </div>
        
      </div>
      <!-- /.modal-content -->
    </div>
  <!-- /.modal-dialog -->
  </div>