<?php
//Asked at the counter when the bill has anything the shop cannot hand over: who the warehouse
//is delivering to, and where. The fields post with the sale
//(docs/superpowers/specs/2026-09-24-pos-warehouse-fulfilment-design.md).
//Every field here carries form="order_form": the modal is rendered outside the till's form,
//and the sale posts $("#order_form").serialize(), which would otherwise leave them all behind.
?>
<div class="modal fade" id="warehouseOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Who is this being delivered to?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted f12">
                    The warehouse prepares these items and sends them to the customer. It needs to know
                    who they are and where they live.
                </p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Customer name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" form="order_form" name="wh_cust_name" id="wh_cust_name" maxlength="120">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" form="order_form" name="wh_cust_phone" id="wh_cust_phone" maxlength="25">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Send it to</label>
                        <select class="form-select" form="order_form" name="wh_deliver_to" id="wh_deliver_to">
                            <option value="1">The customer's address</option>
                            <option value="2">The customer collects at the shop</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Wanted by</label>
                        <input type="date" class="form-control" form="order_form" name="wh_needed_by" id="wh_needed_by">
                    </div>
                    <div class="col-12" id="wh_address_row">
                        <label class="form-label">Delivery address <span class="text-danger">*</span></label>
                        <textarea class="form-control" form="order_form" name="wh_address" id="wh_address" rows="2" maxlength="255"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Another phone for the driver</label>
                        <input type="text" class="form-control" form="order_form" name="wh_phone" id="wh_phone" maxlength="25">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Directions or landmarks</label>
                        <input type="text" class="form-control" form="order_form" name="wh_note" id="wh_note" maxlength="255">
                    </div>
                    <div class="col-12">
                        <input type="hidden" form="order_form" name="wh_cust_address" id="wh_cust_address" value="">
                        <small class="text-muted">
                            Press <b>Specs</b> beside an item in the bill to tell the warehouse what the
                            customer asked for &mdash; size, colour, firmness.
                        </small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="wh_save_details">Save details</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="customItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Custom-made item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted f12">Something nobody stocks. The warehouse makes it and sends it.</p>
                <div class="mb-3">
                    <label class="form-label">What is it? <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="wh_custom_name" maxlength="255"
                           placeholder="Headboard, walnut, 6ft, buttoned">
                </div>
                <div class="mb-3">
                    <label class="form-label">Price <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" class="form-control" id="wh_custom_price">
                </div>
                <div class="mb-3">
                    <label class="form-label">Anything else the workshop needs to know</label>
                    <textarea class="form-control" id="wh_custom_notes" rows="2" maxlength="255"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="wh_add_custom">Add to the bill</button>
            </div>
        </div>
    </div>
</div>
