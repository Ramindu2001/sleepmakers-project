<div id="section_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header d-flex align-items-center">
          <h4 class="modal-title" id="myModalLabel">
            Add Section
          </h4>
          <button type="button" class="btn-close" id="close_section_modal" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
        
        <form action="../Controller/sectionController.php" method="POST">
        <div class="row">
          <!-- left column -->
          <div class="col">
            <!-- hidden input -->
            <input type="hidden" name="hide_section_id" id="hide_section_id" value="0">

            <label for="" id="lbl_section">Section No</label>
            <input type="text" name="section_no" id="section_no" value="0" class="form-control mb-2" placeholder="SE_000000" disabled>

            <label for="" class="">Section Name</label>
            <input type="text" name="section_name" id="section_name" class="form-control mb-2" placeholder="Section Name" required>
            <span class="text-danger" id="category_danger" style="display: none;">&#9888; Section already exists.</span>

          </div>
        </div>

        <div class="modal-footer">
            <!-- delete -->
            <button type="submit" name="btn_delete_section" class="btn bg-danger-subtle text-danger waves-effect" data-bs-dismiss="modal" id="btn_delete_section">
              Delete
            </button>
            <!-- update -->
            <button type="submit" name="btn_update_section" class="btn bg-primary-subtle text-primary waves-effect" data-bs-dismiss="modal" id="btn_update_section">
              Update
            </button>
            <!-- save -->
            <button type="submit" name="btn_save_section" class="btn bg-primary-subtle text-primary waves-effect" data-bs-dismiss="modal" id="btn_save_section">
              Save
            </button>
        
          <button type="button" class="btn bg-danger-subtle text-danger  waves-effect" data-bs-dismiss="modal" id="close_section_modal">
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