<?php
/**
 * "Print Barcode" dialog.
 * -----------------------------------------------------------------------------
 * Sticker size, roll layout, what goes on the label, how it is typeset, and the
 * per product price / quantity - then straight to the printer.
 *
 * The controls are rendered SERVER SIDE and are always present, so the dialog
 * can never open as an empty white box even if the browser is holding on to an
 * old copy of barcode-label.js.
 *
 * Every checkbox is preceded by a hidden input of the same name carrying 0.
 * An unchecked box sends nothing at all, and without the hidden partner the
 * server would fall back to the DEFAULT for that option instead of to "off" -
 * which is how a switched off item name comes back on at the printer.
 *
 * RESPONSIVE: the dialog is used on the shop floor from a phone or a tablet as
 * well as from the counter PC. It goes full screen below 576px, the size and
 * "across the roll" tiles reflow from one row to 2/3 per row, every measurement
 * field stacks its label, and the product table scrolls sideways instead of
 * crushing the quantity boxes. Nothing is hidden on a small screen - a setting
 * you cannot reach is a setting you cannot fix.
 */
require_once __DIR__ . '/../../Includes/barcode_helper.php';

$bc_sizes       = bcGetLabelSizes();
$bc_default     = bcOptionDefaults();
$bc_max_across  = bcMaxLabelsAcross();
$bc_fonts       = bcFontStacks();
$bc_symbologies = bcgSymbologiesSafe();
$bc_dates       = bcDateFormats();

/**
 * One checkbox plus its hidden "off" partner.
 */
if (!function_exists('bcChk')) {
function bcChk($name, $label, $checked, $extra_class = '')
{
    $id = 'bc_' . $name;
    ?>
    <div class="form-check <?php echo $extra_class; ?>">
        <input type="hidden" name="<?php echo $name; ?>" value="0">
        <input class="form-check-input" type="checkbox" name="<?php echo $name; ?>"
               id="<?php echo $id; ?>" value="1" <?php echo $checked ? 'checked' : ''; ?>>
        <label class="form-check-label" for="<?php echo $id; ?>"><?php echo $label; ?></label>
    </div>
    <?php
}//bcChk
}//function guard
?>

<style>
    /* ================================================== shared / desktop == */
    #product_barcode_modal .bc-size-tile  { min-width: 100px; padding: 9px 8px; line-height: 1.2; }
    #product_barcode_modal .bc-size-tile .bc-size-mm { font-size: 11px; opacity: .75; }
    #product_barcode_modal .bc-across-tile { min-width: 70px; padding: 9px 8px; line-height: 1.2; }

    /* a small diagram of the roll so the choice is unambiguous */
    #product_barcode_modal .bc-across-art { display: flex; gap: 2px; justify-content: center; margin-bottom: 4px; }
    #product_barcode_modal .bc-across-art i {
        display: block; width: 8px; height: 12px;
        border: 1px solid currentColor; border-radius: 1px; opacity: .8;
    }

    #product_barcode_modal .bc-mm-input { width: 88px; }

    /* Bootstrap .d-flex is display:flex !important, which jQuery .css() cannot
       reliably switch off - these panels use their own classes instead. */
    #product_barcode_modal .bc-optional {
        display: flex; flex-wrap: wrap; align-items: center; gap: .5rem;
    }
    #product_barcode_modal .bc-optional.bc-hidden { display: none; }

    /* one measurement: label, box, unit */
    #product_barcode_modal .bc-field {
        display: flex; align-items: center; gap: .5rem;
    }

    #product_barcode_modal .bc-page-readout {
        border: 1px solid #b6d4fe; background: #eaf3ff; border-radius: 6px; padding: 8px 12px;
    }
    #product_barcode_modal .bc-page-size {
        font-family: Consolas, "Courier New", monospace;
        font-size: 16px; font-weight: 700; color: #0b4a8f; white-space: nowrap;
    }

    /* vertical scroll for a long list, horizontal so the boxes never crush */
    #product_barcode_modal .bc-items-wrap { max-height: 240px; overflow: auto; }
    #product_barcode_modal table.bc-items { margin-bottom: 0; min-width: 440px; }
    #product_barcode_modal table.bc-items th {
        position: sticky; top: 0; background: #f8f9fb; z-index: 2; white-space: nowrap;
    }
    #product_barcode_modal table.bc-items td,
    #product_barcode_modal table.bc-items th { padding: 8px !important; vertical-align: middle; }

    #product_barcode_modal .bc-code-text {
        font-family: Consolas, "Courier New", monospace; font-size: 12px; color: #5a6a7a;
        word-break: break-all;
    }

    #product_barcode_modal .bc-tab-pane { padding-top: 18px; }
    #product_barcode_modal .bc-group-title {
        font-size: 12px; font-weight: 600; text-transform: uppercase;
        letter-spacing: .4px; color: #5a6a7a; margin-bottom: 8px;
    }

    /* the tab strip scrolls rather than wrapping into a ragged block */
    #product_barcode_modal .bc-tabs {
        flex-wrap: nowrap; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch;
    }
    #product_barcode_modal .bc-tabs .nav-link { white-space: nowrap; }
    #product_barcode_modal .bc-tab-short { display: none; }

    /* the row / header tick boxes must read as check boxes, not radios */
    #tbl_products input.bc-select,
    #tbl_products input#bc_select_all {
        width: 17px; height: 17px; border-radius: 3px !important;
        cursor: pointer; vertical-align: middle;
    }

    /* ========================================== tablet and below (<992px) == */
    @media (max-width: 991.98px) {
        /* tiles share the row evenly instead of leaving a ragged tail */
        #product_barcode_modal .bc-size-tile   { flex: 1 1 calc(25% - .5rem); min-width: 0; }
        #product_barcode_modal .bc-across-tile { flex: 1 1 calc(12.5% - .5rem); min-width: 56px; }
    }

    /* ============================================= phones (<768px) ========= */
    @media (max-width: 767.98px) {
        #product_barcode_modal .bc-size-tile   { flex: 1 1 calc(33.333% - .5rem); }
        #product_barcode_modal .bc-across-tile { flex: 1 1 calc(25% - .5rem); }

        /* two measurements per row, each with its label above the box */
        #product_barcode_modal .bc-field {
            flex: 1 1 calc(50% - .75rem);
            flex-direction: column;
            align-items: stretch;
            gap: .15rem;
        }
        #product_barcode_modal .bc-field .bc-mm-input { width: 100%; }
        /* the trailing unit sits under the box, so keep it quiet */
        #product_barcode_modal .bc-field .bc-unit { font-size: 11px; }

        #product_barcode_modal .bc-tab-full  { display: none; }
        #product_barcode_modal .bc-tab-short { display: inline; }

        #product_barcode_modal .bc-page-size { font-size: 15px; }
    }

    /* ============================================ small phones (<576px) ==== */
    @media (max-width: 575.98px) {
        #product_barcode_modal .bc-size-tile   { flex: 1 1 calc(50% - .5rem); }
        #product_barcode_modal .bc-across-tile { flex: 1 1 calc(25% - .5rem); }
        #product_barcode_modal .bc-field       { flex: 1 1 100%; }

        #product_barcode_modal .bc-tab-pane { padding-top: 14px; }

        /* the footer becomes two full width rows - a 44px tap target each */
        #product_barcode_modal .modal-footer {
            flex-direction: column-reverse;
            align-items: stretch;
            gap: .5rem;
        }
        #product_barcode_modal .modal-footer > * { width: 100%; }
        #product_barcode_modal .bc-footer-actions {
            display: flex; gap: .5rem;
        }
        #product_barcode_modal .bc-footer-actions .btn { flex: 1 1 50%; }
        #product_barcode_modal .bc-footer-meta {
            display: flex; flex-wrap: wrap; align-items: center;
            justify-content: space-between; gap: .5rem;
        }
    }
</style>

<div class="modal fade" tabindex="-1" role="dialog" id="product_barcode_modal" aria-hidden="true">
    <!-- full screen on a phone: a scrolled-inside-a-scroll dialog is unusable
         on a small screen, and every control here has to stay reachable -->
    <div class="modal-dialog modal-xl modal-fullscreen-sm-down modal-dialog-scrollable" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Print Barcode Labels</h5>
                <button type="button" class="btn-close" id="btn_close_barcode_modal"
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="../Public/print-barcode.php" method="post" id="barcode_print_form" target="_blank">
                <div class="modal-body">

                    <ul class="nav nav-tabs bc-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#bc_tab_layout"
                                    type="button" role="tab">
                                1. <span class="bc-tab-full">Size &amp; Layout</span><span class="bc-tab-short">Size</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#bc_tab_content"
                                    type="button" role="tab">
                                2. <span class="bc-tab-full">What goes on the label</span><span class="bc-tab-short">Content</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#bc_tab_style"
                                    type="button" role="tab">
                                3. <span class="bc-tab-full">Style</span><span class="bc-tab-short">Style</span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">

                        <!-- =========================================== 1. layout -->
                        <div class="tab-pane fade show active bc-tab-pane" id="bc_tab_layout" role="tabpanel">

                            <label class="form-label fw-semibold mb-2">Sticker size</label>
                            <div class="d-flex flex-wrap gap-2 mb-2" id="bc_size_group">
                                <?php
                                foreach ($bc_sizes as $key => $size) {
                                    $input_id = 'bc_size_' . $key;
                                    $checked = ($key === $bc_default['size']) ? 'checked' : '';
                                    ?>
                                    <input type="radio" class="btn-check" name="size" id="<?php echo $input_id; ?>"
                                           value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"
                                           data-width="<?php echo $size['width']; ?>"
                                           data-height="<?php echo $size['height']; ?>" <?php echo $checked; ?>>
                                    <label class="btn btn-outline-primary bc-size-tile" for="<?php echo $input_id; ?>">
                                        <span class="d-block fw-semibold"><?php echo bcMm($size['width']); ?> &times; <?php echo bcMm($size['height']); ?></span>
                                        <span class="bc-size-mm">millimetres</span>
                                    </label>
                                    <?php
                                }//foreach size
                                ?>

                                <input type="radio" class="btn-check" name="size" id="bc_size_custom" value="custom">
                                <label class="btn btn-outline-primary bc-size-tile" for="bc_size_custom">
                                    <span class="d-block fw-semibold">Custom</span>
                                    <span class="bc-size-mm">measure it</span>
                                </label>
                            </div>

                            <div class="bc-optional bc-hidden mb-4" id="bc_custom_wrap">
                                <label class="form-label mb-0 text-muted fs-2">One sticker:</label>
                                <input type="number" step="0.1" min="15" max="297" name="custom_w" id="bc_custom_w"
                                       class="form-control form-control-sm bc-mm-input" value="50" placeholder="width">
                                <span class="text-muted">&times;</span>
                                <input type="number" step="0.1" min="15" max="297" name="custom_h" id="bc_custom_h"
                                       class="form-control form-control-sm bc-mm-input" value="25" placeholder="height">
                                <span class="text-muted fs-2">mm (the printed area of ONE sticker, not the whole roll)</span>
                            </div>

                            <label class="form-label fw-semibold mb-2">How many labels sit across the roll</label>
                            <div class="d-flex flex-wrap gap-2 mb-3" id="bc_across_group">
                                <?php
                                for ($across = 1; $across <= $bc_max_across; $across++) {
                                    $input_id = 'bc_across_' . $across;
                                    $checked = ($across === 1) ? 'checked' : '';
                                    ?>
                                    <input type="radio" class="btn-check" name="across" id="<?php echo $input_id; ?>"
                                           value="<?php echo $across; ?>" <?php echo $checked; ?>>
                                    <label class="btn btn-outline-primary bc-across-tile" for="<?php echo $input_id; ?>">
                                        <span class="bc-across-art">
                                            <?php for ($n = 0; $n < $across; $n++) { echo '<i></i>'; } ?>
                                        </span>
                                        <span class="d-block fw-semibold"><?php echo $across; ?></span>
                                    </label>
                                    <?php
                                }//foreach across
                                ?>
                            </div>

                            <div class="d-flex flex-wrap align-items-start gap-3 mb-3">
                                <div class="bc-optional bc-field bc-hidden" id="bc_col_gap_wrap">
                                    <label class="form-label mb-0 text-muted fs-2" for="bc_col_gap">Gap between columns</label>
                                    <input type="number" step="0.1" min="0" max="100" name="col_gap" id="bc_col_gap"
                                           class="form-control form-control-sm bc-mm-input" value="2">
                                    <span class="text-muted fs-2 bc-unit">mm</span>
                                </div>
                                <div class="bc-field">
                                    <label class="form-label mb-0 text-muted fs-2" for="bc_row_gap">Gap between rows</label>
                                    <input type="number" step="0.1" min="0" max="100" name="row_gap" id="bc_row_gap"
                                           class="form-control form-control-sm bc-mm-input" value="0">
                                    <span class="text-muted fs-2 bc-unit">mm</span>
                                </div>
                                <div class="bc-optional bc-field bc-hidden" id="bc_skip_wrap">
                                    <label class="form-label mb-0 text-muted fs-2" for="bc_skip">Skip stickers</label>
                                    <input type="number" min="0" max="7" name="skip" id="bc_skip"
                                           class="form-control form-control-sm bc-mm-input" value="0">
                                    <span class="text-muted fs-2 bc-unit">already used on the first row</span>
                                </div>
                                <div class="bc-field">
                                    <label class="form-label mb-0 text-muted fs-2" for="bc_padding">Inner margin</label>
                                    <input type="number" step="0.1" min="0" max="20" name="padding" id="bc_padding"
                                           class="form-control form-control-sm bc-mm-input" value="0"
                                           placeholder="auto">
                                    <span class="text-muted fs-2 bc-unit">mm (0 = automatic)</span>
                                </div>
                            </div>

                            <p class="text-muted fs-2 mb-3" id="bc_gap_tip">
                                Not sure about the gap? Measure the <strong>full width of the roll</strong> and
                                subtract the labels &mdash; two 50mm labels on a 102mm roll means a 2mm gap.
                            </p>

                            <div class="bc-page-readout mb-3">
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div>
                                        <div class="fw-semibold fs-2">Set the printer paper size to</div>
                                        <div class="bc-page-size" id="bc_page_size">50 x 25 mm</div>
                                    </div>
                                    <div class="text-muted fs-2 flex-grow-1" id="bc_page_hint">
                                        One label per page.
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-4">
                                <?php bcChk('guides', 'Print a thin cut line around every sticker', $bc_default['guides']); ?>
                                <?php bcChk('auto_print', 'Open the printer dialog automatically', $bc_default['auto_print']); ?>
                            </div>
                        </div>

                        <!-- ========================================== 2. content -->
                        <div class="tab-pane fade bc-tab-pane" id="bc_tab_content" role="tabpanel">

                            <div class="row g-4">
                                <div class="col-12 col-md-5">
                                    <div class="bc-group-title">Lines on the sticker</div>
                                    <div class="row g-0">
                                        <div class="col-6 col-md-12">
                                            <?php bcChk('show_shop',   'Shop name',               $bc_default['show_shop'],   'mb-2'); ?>
                                            <?php bcChk('show_name',   'Item name',               $bc_default['show_name'],   'mb-2'); ?>
                                            <?php bcChk('show_second', 'Second name',             $bc_default['show_second'], 'mb-2'); ?>
                                            <?php bcChk('show_cat',    'Category / sub category', $bc_default['show_cat'],    'mb-2'); ?>
                                            <?php bcChk('show_sku',    'Product number (SKU)',    $bc_default['show_sku'],    'mb-2'); ?>
                                            <?php bcChk('show_bars',   'Barcode bars',            $bc_default['show_bars'],   'mb-2'); ?>
                                        </div>
                                        <div class="col-6 col-md-12">
                                            <?php bcChk('show_code',   'Barcode number',          $bc_default['show_code'],   'mb-2'); ?>
                                            <?php bcChk('show_price',  'Price',                   $bc_default['show_price'],  'mb-2'); ?>
                                            <?php bcChk('show_batch',  'Batch',                   $bc_default['show_batch'],  'mb-2'); ?>
                                            <?php bcChk('show_date',   'Printed date',            $bc_default['show_date'],   'mb-2'); ?>
                                            <?php bcChk('show_footer', 'Footer line',             $bc_default['show_footer'], 'mb-2'); ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-7">
                                    <div class="bc-group-title">Text</div>

                                    <div class="row g-2 mb-3">
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label fs-2 text-muted mb-1" for="bc_shop_text">Shop line text</label>
                                            <input type="text" name="shop_text" id="bc_shop_text" maxlength="80"
                                                   class="form-control form-control-sm" placeholder="the shop name">
                                        </div>
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label fs-2 text-muted mb-1" for="bc_footer_text">Footer text</label>
                                            <input type="text" name="footer_text" id="bc_footer_text" maxlength="80"
                                                   class="form-control form-control-sm" placeholder="e.g. Thank you">
                                        </div>
                                        <div class="col-6 col-sm-4">
                                            <label class="form-label fs-2 text-muted mb-1" for="bc_price_label">Price prefix</label>
                                            <input type="text" name="price_label" id="bc_price_label" maxlength="12"
                                                   class="form-control form-control-sm" value="Rs">
                                        </div>
                                        <div class="col-6 col-sm-4">
                                            <label class="form-label fs-2 text-muted mb-1" for="bc_price_dec">Decimals</label>
                                            <select name="price_dec" id="bc_price_dec" class="form-select form-select-sm">
                                                <option value="0">0 &ndash; 1,250</option>
                                                <option value="2" selected>2 &ndash; 1,250.00</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-sm-4">
                                            <label class="form-label fs-2 text-muted mb-1" for="bc_date_format">Date format</label>
                                            <select name="date_format" id="bc_date_format" class="form-select form-select-sm">
                                                <?php
                                                foreach ($bc_dates as $format => $example) {
                                                    $sel = ($format === $bc_default['date_format']) ? 'selected' : '';
                                                    ?>
                                                    <option value="<?php echo htmlspecialchars($format, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $sel; ?>>
                                                        <?php echo htmlspecialchars($example, ENT_QUOTES, 'UTF-8'); ?>
                                                    </option>
                                                    <?php
                                                }//foreach format
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label fs-2 text-muted mb-1" for="bc_name_len">Shorten the item name to</label>
                                            <input type="number" min="0" max="120" name="name_len" id="bc_name_len"
                                                   class="form-control form-control-sm" value="0" placeholder="0 = full name">
                                        </div>
                                        <div class="col-12 col-sm-6 d-flex align-items-end">
                                            <?php bcChk('name_upper', 'Item name in CAPITALS', $bc_default['name_upper'], 'mb-2'); ?>
                                        </div>
                                    </div>

                                    <div class="bc-group-title">Barcode</div>
                                    <div class="row g-2">
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label fs-2 text-muted mb-1" for="bc_symbology">Barcode type</label>
                                            <select name="symbology" id="bc_symbology" class="form-select form-select-sm">
                                                <option value="AUTO">Automatic (match the code)</option>
                                                <?php
                                                foreach ($bc_symbologies as $key => $spec) {
                                                    ?>
                                                    <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>">
                                                        <?php echo htmlspecialchars($spec['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </option>
                                                    <?php
                                                }//foreach symbology
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-6 col-sm-3">
                                            <label class="form-label fs-2 text-muted mb-1" for="bc_bar_height">Bar height (mm)</label>
                                            <input type="number" step="0.1" min="0" max="60" name="bar_height" id="bc_bar_height"
                                                   class="form-control form-control-sm" value="0" placeholder="auto">
                                        </div>
                                        <div class="col-6 col-sm-3">
                                            <label class="form-label fs-2 text-muted mb-1" for="bc_bar_scale">Bar width (%)</label>
                                            <input type="number" min="40" max="100" name="bar_scale" id="bc_bar_scale"
                                                   class="form-control form-control-sm" value="100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ============================================ 3. style -->
                        <div class="tab-pane fade bc-tab-pane" id="bc_tab_style" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-12 col-md-6">
                                    <div class="bc-group-title">Typeface</div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-12 col-sm-7">
                                            <label class="form-label fs-2 text-muted mb-1" for="bc_font">Font</label>
                                            <select name="font" id="bc_font" class="form-select form-select-sm">
                                                <?php
                                                foreach ($bc_fonts as $key => $font) {
                                                    $sel = ($key === $bc_default['font']) ? 'selected' : '';
                                                    ?>
                                                    <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $sel; ?>>
                                                        <?php echo htmlspecialchars($font['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </option>
                                                    <?php
                                                }//foreach font
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-12 col-sm-5">
                                            <label class="form-label fs-2 text-muted mb-1" for="bc_align">Align</label>
                                            <select name="align" id="bc_align" class="form-select form-select-sm">
                                                <option value="center" selected>Centre</option>
                                                <option value="left">Left</option>
                                                <option value="right">Right</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-wrap gap-4">
                                        <?php bcChk('bold_name',  'Item name in bold', $bc_default['bold_name']); ?>
                                        <?php bcChk('bold_price', 'Price in bold',     $bc_default['bold_price']); ?>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="bc-group-title">Text sizes (mm) &mdash; 0 means automatic</div>
                                    <div class="row g-2">
                                        <div class="col-6 col-sm-4">
                                            <label class="form-label fs-2 text-muted mb-1" for="bc_shop_font">Shop</label>
                                            <input type="number" step="0.1" min="0" max="30" name="shop_font" id="bc_shop_font"
                                                   class="form-control form-control-sm" value="0">
                                        </div>
                                        <div class="col-6 col-sm-4">
                                            <label class="form-label fs-2 text-muted mb-1" for="bc_name_font">Item name</label>
                                            <input type="number" step="0.1" min="0" max="30" name="name_font" id="bc_name_font"
                                                   class="form-control form-control-sm" value="0">
                                        </div>
                                        <div class="col-6 col-sm-4">
                                            <label class="form-label fs-2 text-muted mb-1" for="bc_code_font">Barcode no.</label>
                                            <input type="number" step="0.1" min="0" max="30" name="code_font" id="bc_code_font"
                                                   class="form-control form-control-sm" value="0">
                                        </div>
                                        <div class="col-6 col-sm-4">
                                            <label class="form-label fs-2 text-muted mb-1" for="bc_price_font">Price</label>
                                            <input type="number" step="0.1" min="0" max="30" name="price_font" id="bc_price_font"
                                                   class="form-control form-control-sm" value="0">
                                        </div>
                                        <div class="col-6 col-sm-4">
                                            <label class="form-label fs-2 text-muted mb-1" for="bc_small_font">Small lines</label>
                                            <input type="number" step="0.1" min="0" max="30" name="small_font" id="bc_small_font"
                                                   class="form-control form-control-sm" value="0">
                                        </div>
                                        <div class="col-6 col-sm-4">
                                            <label class="form-label fs-2 text-muted mb-1" for="bc_line_gap">Line gap</label>
                                            <input type="number" step="0.1" min="0" max="20" name="line_gap" id="bc_line_gap"
                                                   class="form-control form-control-sm" value="0">
                                        </div>
                                        <div class="col-6 col-sm-4">
                                            <label class="form-label fs-2 text-muted mb-1" for="bc_letter_sp">Code tracking</label>
                                            <input type="number" step="0.01" min="0" max="3" name="letter_sp" id="bc_letter_sp"
                                                   class="form-control form-control-sm" value="0.12">
                                        </div>
                                        <div class="col-6 col-sm-4">
                                            <label class="form-label fs-2 text-muted mb-1" for="bc_bar_color">Bar colour</label>
                                            <input type="color" name="bar_color" id="bc_bar_color"
                                                   class="form-control form-control-sm form-control-color w-100" value="#000000">
                                        </div>
                                        <div class="col-6 col-sm-4">
                                            <label class="form-label fs-2 text-muted mb-1" for="bc_copies">Copies of each</label>
                                            <input type="number" min="1" max="100" name="copies" id="bc_copies"
                                                   class="form-control form-control-sm" value="1">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <p class="text-muted fs-2 mt-3 mb-0">
                                Leave the sizes on 0 unless a label really needs tuning &mdash; every sticker size
                                already carries typography that fits it, and a hand set size that is too big
                                pushes content off a smaller sticker.
                            </p>
                        </div>
                    </div>

                    <!-- ============================================= products -->
                    <hr class="my-3">
                    <label class="form-label fw-semibold mb-2">Products &amp; quantity</label>

                    <!-- visible by default: replaced as soon as the data arrives -->
                    <div id="bc_loading" class="text-center border rounded py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-3 mb-0">Loading product details&hellip;</p>
                    </div>

                    <div id="bc_error" class="alert alert-danger mb-0" style="display:none;"></div>

                    <div id="bc_stale_warning" class="alert alert-warning mb-0" style="display:none;">
                        <strong>Your browser is using an old copy of this page.</strong>
                        <div class="mt-1">Press <kbd>Ctrl</kbd> + <kbd>F5</kbd> (or <kbd>Cmd</kbd> + <kbd>Shift</kbd> + <kbd>R</kbd> on a Mac) to reload, then try again.</div>
                    </div>

                    <div id="bc_items_wrap" class="border rounded bc-items-wrap" style="display:none;">
                        <table class="table table-sm align-middle bc-items">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th style="width:180px;">Label price</th>
                                    <th style="width:100px;">Labels</th>
                                    <th style="width:44px;"></th>
                                </tr>
                            </thead>
                            <tbody id="bc_items_body"></tbody>
                        </table>
                    </div>

                    <div class="alert alert-warning mt-3 mb-0 py-2 fs-2" id="bc_size_warning" style="display:none;"></div>
                </div>

                <div class="modal-footer justify-content-between">
                    <div class="d-flex flex-wrap align-items-center gap-3 bc-footer-meta">
                        <span class="text-muted fs-2" id="bc_total_summary">&nbsp;</span>
                        <button type="button" class="btn btn-sm btn-light border" id="bc_btn_save_default"
                                title="Every Print Barcode dialog in this shop will open on these settings">
                            Save as shop default
                        </button>
                        <button type="button" class="btn btn-sm btn-link text-muted p-0" id="bc_btn_reset_options">
                            Reset options
                        </button>
                    </div>
                    <div class="bc-footer-actions">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="btn_print_barcode" value="1"
                                id="bc_btn_print" disabled>
                            <i class="ti ti-printer"></i> Print Labels
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
    /* Safety net: if the browser served a barcode-label.js from before this
       dialog existed, say so instead of showing a dead dialog. */
    $(function () {
        if (window.BC_LABEL_JS !== 4) {
            $("#bc_loading").hide();
            $("#bc_stale_warning").show();
        }
    });
</script>
