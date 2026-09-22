<div id="section_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
      <div class="modal-content">
        <div class="modal-header d-flex align-items-center">
          <h4 class="modal-title" id="myModalLabel">
            Select Price
          </h4>
          <button type="button" class="btn-close" id="close_section_modal" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <button class="btn btn-danger" id="clear-select">Clear Select</button>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Select</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Manufacture Date</th>
                        <th>Expired Date</th>
                    </tr>
                </thead>
                <tbody id="product-modal-tbody">
                   
                </tbody>
            </table>
        </div>
        <div class="modal-footer">
            <!-- save -->
            <button type="submit" name="btn_save_section" class="btn bg-primary-subtle text-primary waves-effect" id="btn_save_section">
              Add
            </button>
        
          <button type="button" class="btn bg-danger-subtle text-danger  waves-effect" data-bs-dismiss="modal" id="close_section_modal">
            Close
          </button>

        </div>

        </div>
        
      </div>
      <!-- /.modal-content -->
    </div>
  <!-- /.modal-dialog -->
  </div>