<div id="category_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header d-flex align-items-center">
          <h4 class="modal-title" id="myModalLabel">
            Add Category
          </h4>
          <button type="button" class="btn-close" id="close_category_modal" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
        
        <form action="../Controller/categoryController.php" method="POST">
        <div class="row">
          <!-- left column -->
          <div class="col">
            <!-- hidden input -->
            <input type="hidden" name="hide_category_id" id="hide_category_id" value="0">

            <label for="" id="lbl_category">Category No</label>
            <input type="text" name="cat_no" id="cat_no" value="0" class="form-control mb-2" placeholder="Category No" disabled>

            <label for="" class="">Category Name</label>
            <input type="text" name="cat_name" id="cat_name" class="form-control mb-2" placeholder="Category Name" required>
            <span class="text-danger" id="category_danger" style="display: none;">&#9888; Category already exists.</span>

            <!-- Barcode prefix code. Kept short and alphanumeric because it is
                 pasted straight into generated barcodes. -->
            <label for="cat_code" class="mt-2">Barcode Code <small class="text-muted">(optional)</small></label>
            <input type="text" name="cat_code" id="cat_code" class="form-control mb-1 text-uppercase"
                   maxlength="12" placeholder="e.g. BEV">
            <small class="text-muted d-block mb-2">
                Used as the <strong>{CAT}</strong> part of an automatic barcode.
                Leave it empty and the first letters of the category name are used.
            </small>

          </div>
        </div>

        <div class="modal-footer">
          <!-- update -->
          <button type="submit" name="btn_delete_category" class="btn bg-danger-subtle text-danger waves-effect" data-bs-dismiss="modal" id="btn_delete_category">
            Delete
          </button>
          <!-- update -->
          <button type="submit" name="btn_update_category" class="btn bg-primary-subtle text-primary waves-effect" data-bs-dismiss="modal" id="btn_update_category">
            Update
          </button>
          <!-- save -->
          <button type="submit" name="btn_save_category" class="btn bg-primary-subtle text-primary waves-effect" data-bs-dismiss="modal" id="btn_save_category">
            Save
          </button>
          <!-- close -->
          <button type="button" class="btn bg-danger-subtle text-danger  waves-effect" data-bs-dismiss="modal" id="close_category_modal">
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