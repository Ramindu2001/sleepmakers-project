<div id="pricechange_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
      <div class="modal-content">
        <div class="modal-header d-flex align-items-center">
          <h4 class="modal-title" id="myModalLabel">
            Add Price Change
          </h4>
          <button type="button" class="btn-close" id="close_products_modal" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
        
        <form action="../Controller/productController.php" method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-6">
                <label for="cmb_product" class="form-label">Select Product</label>
                <select name="product_id" id="cmb_product" class="form-control" required></select>
            </div>
            <div class="col-md-6">
                <label for="varriation_id" class="form-label">Varriation</label>
                <select name="varriation_id" id="varriation_id" class="form-control" required disabled></select>
            </div>
            <div class="col-md-6">
                <label for="batch_id" class="form-label">Batch ID</label>
                <select name="batch_id" id="batch_id" class="form-control" required disabled></select>
            </div>
            <div class="col-md-6">
                <label for="old_selling_price" class="form-label">Old Selling Price</label>
                <input name="old_selling_price" id="old_selling_price" class="form-control" readonly>
            </div>
            <div class="col-md-6">
                <label for="new_selling_price" class="form-label">New Selling Price</label>
                <input name="new_selling_price" id="new_selling_price" class="form-control" required disabled>
            </div>
            <div class="col-md-6">
                <label for="old_label_price" class="form-label">Old Label Price</label>
                <input name="old_label_price" id="old_label_price" class="form-control" readonly>
            </div>
            <div class="col-md-12">
                <label for="new_label_price" class="form-label">New Label Price</label>
                <input name="new_label_price" id="new_label_price" class="form-control" required disabled>
            </div>
        </div>

        <div class="modal-footer">
            <!-- save -->
            <button type="submit" name="btn_save_product" class="btn bg-primary-subtle text-primary waves-effect" id="btn_save_product">
              Save
            </button>
        
          <button type="button" class="btn bg-danger-subtle text-danger  waves-effect" data-bs-dismiss="modal" id="close_product_modal">
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
  