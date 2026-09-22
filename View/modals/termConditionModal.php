<!-- Terms and condition Modal -->
<div class="modal fade" id="termConditionModal" tabindex="-1" aria-labelledby="termConditionModalLabel" aria-hidden="true" >
    <div class="modal-dialog modal-dialog-scrollable modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termConditionModalLabel">Terms And Condition</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mt-3">
                        <label for="details" class="form-label w-100 det">Sale Details</label>
                        <textarea name="detail" id="details" class="form-control" style="height:100px;"></textarea>                                                      
                        <script>
                            CKEDITOR.replace( 'details' );
                        </script>
                    </div>
                    <div class="col-md-6 mt-3">
                        <label for="internalRemark" class="form-label w-100 det">Internal Remark</label>
                        <textarea name="internalRemark" id="internalRemark" class="form-control" style="height:100px;"></textarea>                                                      
                        <script>
                            CKEDITOR.replace( 'internalRemark' );
                        </script>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
