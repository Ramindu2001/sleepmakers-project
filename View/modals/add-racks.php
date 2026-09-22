<div id="racks_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
      <div class="modal-content">
        <div class="modal-header d-flex align-items-center">
          <h4 class="modal-title" id="myModalLabel">
            Add Sub Category
          </h4>
          <button type="button" class="btn-close" id="close_racks_modal" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
        
        <form action="../Controller/sectionController.php" method="POST">
          <!-- hidden input -->
          <input type="hidden" name="hide_rack_id" id="hide_rack_id" value="0">
        <div class="row">
          
          <div class="col-md-12">
            <label for="rack_no" id="lblracks">Rack No</label>
            <input type="text" name="rack_no" id="rack_no" value="0" class="form-control mb-2 w-50" placeholder="Rack Name" disabled>
          </div>

          <div class="col-md-6">
            
            <label for="cmb_sections">Select Section</label>
            <select name="cmb_sections" id="cmb_sections" class="form-select dropdown-toggle mb-2">
                <?php 
                $sectionObj = new Section();
                $secData = $sectionObj->getAllSections($shop_id);
                foreach($secData as $row)
                {
                    ?>
                    <option value="<?php echo $row['SEID']?>"><?php echo $row['SectionName'];?></option>
                    <?php 
                }//foreach
                ?>
            </select>
          </div>

          <div class="col-md-6">
              <label for="rack_name" class="">Rack Name</label>
              <input type="text" name="rack_name" id="rack_name" class="form-control mb-2" placeholder="Rack Name" required>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" name="btn_delete_rack" class="btn bg-danger-subtle text-danger waves-effect" id="btn_delete_rack">
            Delete
          </button>

          <button type="submit" name="btn_update_rack" class="btn bg-primary-subtle text-primary waves-effect" id="btn_update_rack">
            Update
          </button>

          <button type="submit" name="btn_save_rack" class="btn bg-primary-subtle text-primary waves-effect" id="btn_save_rack">
            Save
          </button>

          <button type="button" class="btn bg-danger-subtle text-danger  waves-effect" data-bs-dismiss="modal" id="close_racks_modal">
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