<div id="variation_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-md">
      <div class="modal-content">
        <div class="modal-header d-flex align-items-center">
          <h4 class="modal-title" id="myModalLabel">
            Add Variations
          </h4>
          <button type="button" class="btn-close" id="close_variation_modal" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">

        <h5 id="product_details"></h5>

        <div class="row">
          <!-- left column -->
          <div class="col-md-9">
            <input type="hidden" id="hide_variation_id" value="0">
            <label for="rack_no" id="lblracks">Variation Name</label>
            <input type="text" name="variation_name" id="variation_name" class="form-control mb-2" placeholder="Variation Name">
            <span id="var_warning" class="text-danger" style="display: none;">Please enter this field.</span>

          </div>
          <div class="col-md-3 pt-4">
            <button type="button" name="btn_add_variation" id="btn_add_variation" class="btn bg-primary-subtle text-primary waves-effect">
                Add
            </button>
            <button type="button" name="btn_edit_variation" id="btn_edit_variation" class="btn bg-success-subtle text-success waves-effect">
                Edit
            </button>
          </div>
        </div>

        <!-------------------------------- Add variations ----------------------------------->
        <div class="table-responsive">
            <table id="tbl_variations" style="max-height: 300px;">
                <tr>
                    <th>Variation</th>
                    <th>Action</th>
                </tr>
            </table>
        </div>

        <div class="modal-footer"> 
          <button type="button" class="btn bg-danger-subtle text-danger  waves-effect" data-bs-dismiss="modal" id="close_variation_modal">
            Close
          </button>
        </div>

        </div>
        
      </div>
      <!-- /.modal-content -->
    </div>
  <!-- /.modal-dialog -->
  </div>