<div id="units_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header d-flex align-items-center">
          <h4 class="modal-title" id="lbl_modal_title">
            Add Units
          </h4>
          <button type="button" class="btn-close" id="close_units_modal" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
        
        <form action="../Controller/unitController.php" method="POST">
        <div class="row">
          <!-- left column -->
          <div class="col">
            <!-- hidden input -->
            <input type="hidden" name="hide_unit_id" id="hide_unit_id" value="<?php echo $category_id;?>">

            <label for="" id="lbl_unit">Unit Name</label>
            <input type="text" name="unit_name" id="unit_name" class="form-control mb-2" placeholder="Unit Name" required>

            <label for="" class="">Short Name</label>
            <input type="text" name="short_name" id="short_name" class="form-control mb-2" placeholder="Short Name" required>
            <span class="text-danger" id="category_danger" style="display: none;">&#9888; Unit name already exists.</span>

          </div>
        </div>

        <div class="modal-footer">
            <!-- delete -->
            <button type="submit" name="btn_delete_units" class="btn bg-danger-subtle text-danger waves-effect" data-bs-dismiss="modal" id="btn_delete_units">
                Delete
            </button>

            <!-- update -->
            <button type="submit" name="btn_update_unit" class="btn bg-primary-subtle text-primary waves-effect" data-bs-dismiss="modal" id="btn_update_unit">
                Update
            </button>
            <!-- save -->
            <button type="submit" name="btn_save_unit" class="btn bg-primary-subtle text-primary waves-effect" data-bs-dismiss="modal" id="btn_save_unit">
                Save
            </button>
        
            <button type="button" class="btn bg-danger-subtle text-danger  waves-effect" data-bs-dismiss="modal" id="close_units_modal">
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