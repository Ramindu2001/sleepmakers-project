<div id="bs-example-modal-md" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
      <div class="modal-content">
        <div class="modal-header d-flex align-items-center">
          <h4 class="modal-title" id="myModalLabel">
            Edit Item
          </h4>
          <button type="button" class="btn-close" id="modal" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Barcode: <span></span></p>
          <p>Item Name: <span></span></p>
          <div class="row">
            <div class="col-md-6">
                <div class="m-2">
                    <label for="" class="form-label">Select Section</label>
                    <select name="" id="" class="form-select">
                        <option value="1">=== Select Section ===</option>
                    </select>
                </div>
                <div class="m-2">
                    <label for="" class="form-label">Select Rack</label>
                    <select name="" id="" class="form-select">
                        <option value="1">=== Select Rack ===</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="m-2">
                    <label for="" class="form-label">Manufacture Date</label>
                    <input type="date" name="" id="" class="form-control">
                </div>
                <div class="m-2">
                    <label for="" class="form-label">Expiry Date</label>
                    <input type="date" name="" id="" class="form-control">
                </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn bg-primary-subtle text-primary  waves-effect" data-bs-dismiss="modal" id="update">
            Update
          </button>
          <button type="button" class="btn bg-danger-subtle text-danger  waves-effect" data-bs-dismiss="modal" id="modal">
            Close
          </button>
        </div>
      </div>
      <!-- /.modal-content -->
    </div>
  <!-- /.modal-dialog -->
  </div>