<?php
//The scanner upload dialog (Assets/jquery/scan_upload.js). The page sets $scan_upload first:
//  context      grn | transfer_send | transfer_receive
//  doc_id       the GRN or transfer header id
//  title        the document number, e.g. GRN_000012
//  apply_label  Add to GRN / Add to Transfer / Apply received quantities
//No "fade": the dialog is visible at once, so the first keystroke of an upload lands in it.
require_once __DIR__ . '/../../Includes/csrf.php';
?>
<div id="scan_upload_modal" class="modal" tabindex="-1" aria-labelledby="scan_upload_title" aria-hidden="true"
    data-context="<?= htmlspecialchars($scan_upload['context']) ?>" data-doc-id="<?= (int)$scan_upload['doc_id'] ?>">
    <div class="modal-dialog modal-dialog-scrollable modal-xl">
        <div class="modal-content">
            <div class="modal-header d-flex align-items-center">
                <h4 class="modal-title" id="scan_upload_title">
                    <i class="ti ti-barcode"></i> Upload from scanner - <?= htmlspecialchars($scan_upload['title']) ?>
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="scan_csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                <div class="alert alert-info py-2 mb-2">
                    Place the scanner in its cradle and press <strong>Upload</strong>. The codes are read here -
                    you don't need to click anything. Several uploads add up.
                </div>
                <textarea id="scan_capture" class="form-control font-monospace" rows="5" style="min-height:120px;" spellcheck="false" autocomplete="off"
                    placeholder="Scanned codes appear here, one per line"></textarea>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                    <span id="scan_count" class="fw-semibold">0 codes read</span>
                    <span id="scan_busy" class="text-muted" style="display:none;">Checking...</span>
                    <div class="ms-auto d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="scan_check">Check again</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="scan_toggle_raw">Show raw</button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="scan_clear">Clear</button>
                    </div>
                </div>
                <pre id="scan_raw" class="bg-light border rounded p-2 mt-2 small mb-0" style="display:none; max-height:160px; overflow:auto;"></pre>
                <div id="scan_message" class="mt-2"></div>
                <div id="scan_rack_row" class="row mt-2" style="display:none;">
                    <div class="col-md-6">
                        <label for="scan_rack" class="form-label">Section &amp; Rack for these items</label>
                        <select id="scan_rack" class="form-select"></select>
                    </div>
                </div>
                <div class="table-responsive mt-2">
                    <table class="table table-hover align-middle mb-0" id="scan_preview">
                        <thead></thead>
                        <tbody></tbody>
                    </table>
                </div>
                <p id="scan_summary" class="text-end fw-semibold mt-2 mb-0"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="scan_apply" disabled><?= htmlspecialchars($scan_upload['apply_label']) ?></button>
                <button type="button" class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
