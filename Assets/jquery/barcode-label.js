/**
 * Product barcode LABEL module.
 * -----------------------------------------------------------------------------
 * Drives the multi select on the product table and the "Print Barcode" dialog.
 *
 * Kept in its own file, away from product.js, so the label module can be
 * deployed and cache busted on its own.
 *
 * Version marker: product_barcode.php checks this so a browser holding a cached
 * copy reports it instead of opening an empty dialog. Bump it whenever the
 * dialog changes shape.
 */
window.BC_LABEL_JS = 4;

(function () {

    var BC_KEY = "sleepmakers_barcode_selection";   //ticked products, per tab
    var BC_PREF_KEY = "sleepmakers_barcode_options"; //label options, per browser
    var MIN_MODULE_MM = 0.19;   //thinnest bar most thermal printers render reliably

    /* Every option the dialog owns, with the id of the control that holds it.
       One list drives loading, saving and resetting, so a new option only ever
       has to be added in one place. */
    var BC_FIELDS = [
        //---------------------------------------------------------- geometry
        { name: "size",        type: "radio",  group: "#bc_size_group" },
        { name: "custom_w",    type: "value",  id: "#bc_custom_w" },
        { name: "custom_h",    type: "value",  id: "#bc_custom_h" },
        { name: "across",      type: "radio",  group: "#bc_across_group" },
        { name: "col_gap",     type: "value",  id: "#bc_col_gap" },
        { name: "row_gap",     type: "value",  id: "#bc_row_gap" },
        { name: "skip",        type: "value",  id: "#bc_skip" },
        { name: "padding",     type: "value",  id: "#bc_padding" },
        { name: "guides",      type: "check",  id: "#bc_guides" },
        { name: "auto_print",  type: "check",  id: "#bc_auto_print" },
        //----------------------------------------------------------- content
        { name: "show_shop",   type: "check",  id: "#bc_show_shop" },
        { name: "show_name",   type: "check",  id: "#bc_show_name" },
        { name: "show_second", type: "check",  id: "#bc_show_second" },
        { name: "show_cat",    type: "check",  id: "#bc_show_cat" },
        { name: "show_sku",    type: "check",  id: "#bc_show_sku" },
        { name: "show_bars",   type: "check",  id: "#bc_show_bars" },
        { name: "show_code",   type: "check",  id: "#bc_show_code" },
        { name: "show_price",  type: "check",  id: "#bc_show_price" },
        { name: "show_batch",  type: "check",  id: "#bc_show_batch" },
        { name: "show_date",   type: "check",  id: "#bc_show_date" },
        { name: "show_footer", type: "check",  id: "#bc_show_footer" },
        { name: "shop_text",   type: "value",  id: "#bc_shop_text" },
        { name: "footer_text", type: "value",  id: "#bc_footer_text" },
        { name: "price_label", type: "value",  id: "#bc_price_label" },
        { name: "price_dec",   type: "value",  id: "#bc_price_dec" },
        { name: "date_format", type: "value",  id: "#bc_date_format" },
        { name: "name_len",    type: "value",  id: "#bc_name_len" },
        { name: "name_upper",  type: "check",  id: "#bc_name_upper" },
        { name: "symbology",   type: "value",  id: "#bc_symbology" },
        { name: "bar_height",  type: "value",  id: "#bc_bar_height" },
        { name: "bar_scale",   type: "value",  id: "#bc_bar_scale" },
        //-------------------------------------------------------- typography
        { name: "font",        type: "value",  id: "#bc_font" },
        { name: "align",       type: "value",  id: "#bc_align" },
        { name: "bold_name",   type: "check",  id: "#bc_bold_name" },
        { name: "bold_price",  type: "check",  id: "#bc_bold_price" },
        { name: "shop_font",   type: "value",  id: "#bc_shop_font" },
        { name: "name_font",   type: "value",  id: "#bc_name_font" },
        { name: "code_font",   type: "value",  id: "#bc_code_font" },
        { name: "price_font",  type: "value",  id: "#bc_price_font" },
        { name: "small_font",  type: "value",  id: "#bc_small_font" },
        { name: "line_gap",    type: "value",  id: "#bc_line_gap" },
        { name: "letter_sp",   type: "value",  id: "#bc_letter_sp" },
        { name: "bar_color",   type: "value",  id: "#bc_bar_color" },
        { name: "copies",      type: "value",  id: "#bc_copies" }
    ];

    //what the dialog looks like with nothing loaded - captured on first open
    var factoryOptions = null;

    //=========================================================== options =====

    function readOptions() {
        var out = {};

        $.each(BC_FIELDS, function (i, field) {
            if (field.type === "radio") {
                out[field.name] = $(field.group + " input:checked").val();
            } else if (field.type === "check") {
                out[field.name] = $(field.id).is(":checked") ? 1 : 0;
            } else {
                out[field.name] = $(field.id).val();
            }
        });

        return out;
    }//readOptions

    function writeOptions(options) {
        if (!options) {
            return;
        }

        $.each(BC_FIELDS, function (i, field) {
            if (!(field.name in options)) {
                return;
            }

            var value = options[field.name];

            if (field.type === "radio") {
                /* Coerce first: the shop default arrives as JSON, where
                   "across" is the NUMBER 2 rather than the string "2", and a
                   type check alone would silently skip it. */
                var key = String(value === null || value === undefined ? "" : value);

                //the value goes into a selector - only ever accept a plain key
                if (/^[0-9a-zA-Z_x-]+$/.test(key)) {
                    var target = $(field.group + " input[value='" + key + "']");
                    if (target.length) {
                        target.prop("checked", true);
                    }
                }
            } else if (field.type === "check") {
                $(field.id).prop("checked", !!Number(value));
            } else if (value !== null && value !== undefined) {
                $(field.id).val(value);
            }
        });
    }//writeOptions

    /* The roll physically loaded in the printer does not change between jobs,
       so the options are remembered per browser. Getting them wrong is what
       wastes stock, and re-picking them every time is how they get got wrong.

       _saved_at is the timestamp these choices were made. It is what lets a
       newly saved SHOP default win over stale browser choices - see
       applyStoredOptions(). It is never sent to the server: readOptions() only
       ever returns the fields in BC_FIELDS. */
    function savePrefs() {
        try {
            var payload = readOptions();
            payload._saved_at = Math.floor(Date.now() / 1000);
            window.localStorage.setItem(BC_PREF_KEY, JSON.stringify(payload));
        } catch (e) {
            /* private browsing - the options simply will not be remembered */
        }
    }//savePrefs

    function readPrefs() {
        try {
            var saved = JSON.parse(window.localStorage.getItem(BC_PREF_KEY));
            return (saved && typeof saved === "object") ? saved : null;
        } catch (e) {
            return null;
        }
    }//readPrefs

    function loadPrefs() {
        writeOptions(readPrefs());
    }//loadPrefs

    /**
     * Decide between the shop default and this browser's own choices.
     *
     * The operator's choices normally win - they are the person looking at the
     * roll in the printer. But when an administrator saves a NEW shop default,
     * that has to actually reach everybody, so a shop default stamped later
     * than the browser's own choices wins once and is then adopted as local.
     */
    function applyStoredOptions(shopDefaults) {
        var local = readPrefs();

        var shopStamp = Number((shopDefaults && shopDefaults.saved_at) || 0);
        var localStamp = Number((local && local._saved_at) || 0);

        if (shopDefaults && !$.isEmptyObject(shopDefaults) && shopStamp >= localStamp) {
            writeOptions(shopDefaults);
            savePrefs();          //adopt it, so it is not force applied every time
            return;
        }

        writeOptions(local);
    }//applyStoredOptions

    //============================================================ layout =====

    /**
     * The sticker size currently chosen, in millimetres. "Custom" reads the two
     * measurement boxes; every preset reads the data attributes on its tile.
     */
    function labelSize() {
        var picked = $("#bc_size_group input:checked");

        if (picked.val() === "custom") {
            return {
                w: parseFloat($("#bc_custom_w").val()) || 0,
                h: parseFloat($("#bc_custom_h").val()) || 0,
                custom: true
            };
        }

        return {
            w: parseFloat(picked.data("width")) || 0,
            h: parseFloat(picked.data("height")) || 0,
            custom: false
        };
    }//labelSize

    function across() {
        var n = parseInt($("#bc_across_group input:checked").val(), 10);
        return (n > 0) ? n : 1;
    }//across

    function gap(selector) {
        var mm = parseFloat($(selector).val());
        return (mm >= 0) ? mm : 0;
    }//gap

    //drop trailing zeros so 102.0 reads as 102
    function mm(value) {
        return String(Math.round(value * 100) / 100);
    }//mm

    /**
     * Recalculate the page size and tell the operator exactly what to set the
     * printer to. This is the number that stops a 2 across roll printing down
     * one column only, so it is shown as a headline and not as a footnote.
     */
    function updateLayout() {
        var size = labelSize();
        var cols = across();
        var colGap = gap("#bc_col_gap");
        var rowGap = gap("#bc_row_gap");

        //the measurement boxes only make sense on the custom tile
        $("#bc_custom_wrap").toggleClass("bc-hidden", !size.custom);

        //a single column has nothing to sit between, and nothing to skip past
        $("#bc_col_gap_wrap").toggleClass("bc-hidden", cols <= 1);
        $("#bc_skip_wrap").toggleClass("bc-hidden", cols <= 1);

        if (cols === 1) {
            colGap = 0;
            $("#bc_skip").val(0);
        }

        //never offer to skip a whole row - that is just not printing it
        $("#bc_skip").attr("max", cols - 1);
        if (parseInt($("#bc_skip").val(), 10) > cols - 1) {
            $("#bc_skip").val(cols - 1);
        }

        var pageW = (size.w * cols) + (colGap * (cols - 1));
        var pageH = size.h + rowGap;

        if (size.w > 0 && size.h > 0) {
            $("#bc_page_size").text(mm(pageW) + " x " + mm(pageH) + " mm");
        } else {
            $("#bc_page_size").text("--");
        }

        if (cols > 1) {
            $("#bc_page_hint").html(
                "One page = one row of <strong>" + cols + "</strong> labels, so every sticker " +
                "across the roll gets printed. Leaving the printer set to a single label is what " +
                "skips the other column and wastes half the roll."
            );
        } else {
            $("#bc_page_hint").text("One label per page.");
        }

        /* Deliberately does NOT savePrefs(): this runs once on every page
           load, and stamping the browser prefs then would make them permanently
           newer than any shop default - see applyStoredOptions(). Saving is the
           job of the handlers below, which only fire on a real user action. */
        refreshTotals();
    }//updateLayout

    //========================================================= selection =====

    function readSelection() {
        try {
            var raw = window.sessionStorage.getItem(BC_KEY);
            var ids = raw ? JSON.parse(raw) : [];
            if (!Array.isArray(ids)) {
                return [];
            }

            var out = [];
            $.each(ids, function (i, id) {
                id = parseInt(id, 10);
                if (id > 0 && $.inArray(id, out) === -1) {
                    out.push(id);
                }
            });
            return out;
        } catch (e) {
            return [];
        }
    }//readSelection

    function writeSelection(ids) {
        try {
            window.sessionStorage.setItem(BC_KEY, JSON.stringify(ids));
        } catch (e) {
            /* private browsing - the selection simply will not persist */
        }
    }//writeSelection

    function toggleSelection(id, on) {
        id = parseInt(id, 10);
        if (!(id > 0)) {
            return;
        }

        var ids = readSelection();
        var at = $.inArray(id, ids);

        if (on && at === -1) {
            ids.push(id);
        } else if (!on && at !== -1) {
            ids.splice(at, 1);
        }

        writeSelection(ids);
    }//toggleSelection

    function syncSelection() {
        var ids = readSelection();
        var rows = $("#tbl_products .bc-select");

        rows.each(function () {
            var id = parseInt($(this).closest("tr").data("id"), 10);
            $(this).prop("checked", $.inArray(id, ids) !== -1);
        });

        var total = rows.length;
        var checked = rows.filter(":checked").length;

        $("#bc_select_all")
            .prop("checked", total > 0 && checked === total)
            .prop("indeterminate", checked > 0 && checked < total);

        $("#bc_selected_count").text(ids.length);
        $("#btn_print_selected").prop("disabled", ids.length === 0);
        $("#btn_clear_selection").toggle(ids.length > 0);
    }//syncSelection

    //============================================================= modal =====

    function modal(action) {
        var el = document.getElementById("product_barcode_modal");
        if (!el) {
            return;
        }

        if (window.bootstrap && window.bootstrap.Modal) {
            var instance = window.bootstrap.Modal.getOrCreateInstance(el);
            if (action === "hide") {
                instance.hide();
            } else {
                instance.show();
            }
            return;
        }

        if ($.fn.modal) {
            $(el).modal(action);
        }
    }//modal

    //safe for both text nodes and double quoted attribute values
    function escapeHtml(text) {
        var html = $("<div>").text(text === null || text === undefined ? "" : text).html();
        return html.replace(/"/g, "&quot;").replace(/'/g, "&#39;");
    }//escapeHtml

    function showError(message) {
        $("#bc_loading").hide();
        $("#bc_items_wrap").hide();
        $("#bc_stale_warning").hide();
        $("#bc_size_warning").hide();
        $("#bc_error").text(message).show();
        $("#bc_btn_print").prop("disabled", true);
        $("#bc_total_summary").html("&nbsp;");
    }//showError

    function buildRow(item) {
        var html = '<tr data-bc-id="' + item.id + '">';

        html += '<td>';
        html += '<div class="fw-semibold">' + escapeHtml(item.name) + '</div>';

        if (item.printable) {
            html += '<div class="bc-code-text">' + escapeHtml(item.barcode) + '</div>';
            html += '<input type="hidden" name="item_id[]" value="' + item.id + '">';
        } else {
            html += '<span class="badge bg-danger mt-1">No barcode</span>';
        }
        html += '</td>';

        if (item.printable) {
            html += '<td>';
            if (item.prices && item.prices.length > 1) {
                html += '<select class="form-select form-select-sm mb-1 bc-batch">';
                for (var p = 0; p < item.prices.length; p++) {
                    html += '<option value="' + item.prices[p].price + '" data-batch="'
                          + escapeHtml(item.prices[p].batch) + '">'
                          + escapeHtml(item.prices[p].batch) + ' &ndash; ' + item.prices[p].price
                          + '</option>';
                }
                html += '</select>';
            }
            html += '<input type="number" step="0.01" min="0" name="item_price[]" '
                  + 'class="form-control form-control-sm bc-price" value="' + escapeHtml(item.price) + '">';

            //the batch that goes with the chosen price, so the label can show it
            var firstBatch = (item.prices && item.prices.length) ? item.prices[0].batch : "";
            html += '<input type="hidden" name="item_batch[]" class="bc-batch-value" value="'
                  + escapeHtml(firstBatch) + '">';
            html += '</td>';

            html += '<td><input type="number" min="1" max="999" name="item_qty[]" '
                  + 'class="form-control form-control-sm bc-qty" value="1"></td>';
        } else {
            html += '<td colspan="2" class="text-muted fs-2">Add a barcode to this product first.</td>';
        }

        html += '<td class="text-end">'
              + '<button type="button" class="btn btn-sm btn-link text-danger p-0 bc-remove" title="Remove">'
              + '<i class="ti ti-trash"></i></button></td>';

        html += '</tr>';

        return html;
    }//buildRow

    var MAX_QTY = 999;

    /**
     * Read a quantity box WITHOUT touching it.
     *
     * A half typed or empty box reads as 0 here, so the running total is
     * honest, and the operator can clear the field and type a new number. It
     * is corrected to a real quantity by clampQty() when the box loses focus.
     */
    function readQty(field) {
        var qty = parseInt(field.val(), 10);

        if (!(qty > 0)) {
            return 0;
        }//empty or being retyped

        return (qty > MAX_QTY) ? MAX_QTY : qty;
    }//readQty

    /**
     * Put a usable number back in the box. Called on blur, never on input.
     */
    function clampQty(field, fallback) {
        var qty = parseInt(field.val(), 10);

        if (!(qty > 0)) {
            qty = fallback;
        }//nothing usable was typed
        if (qty > MAX_QTY) {
            qty = MAX_QTY;
        }//cap

        field.val(qty);

        return qty;
    }//clampQty

    function refreshTotals() {
        var products = 0;
        var labels = 0;
        var maxModules = 0;

        var copies = parseInt($("#bc_copies").val(), 10);
        if (!(copies > 0)) {
            copies = 1;
        }//being retyped - count as one, but leave the box alone

        $("#bc_items_body tr").each(function () {
            var qtyField = $(this).find(".bc-qty");
            if (qtyField.length === 0) {
                return;
            }

            products++;
            labels += readQty(qtyField) * copies;

            var modules = parseFloat($(this).data("bc-modules"));
            if (modules > maxModules) {
                maxModules = modules;
            }
        });

        //the bullet is written as an escape so this file stays pure ASCII,
        //whatever charset the server serves the script with
        $("#bc_total_summary").text(
            products + " product" + (products === 1 ? "" : "s") + " \u2022 " +
            labels + " label" + (labels === 1 ? "" : "s")
        );

        $("#bc_btn_print").prop("disabled", products === 0);

        //warn when a long code is squeezed onto a small sticker
        var warning = $("#bc_size_warning");
        var width = labelSize().w;

        if (products > 0 && maxModules > 0 && width > 0 && $("#bc_show_bars").is(":checked")) {
            var perModule = (width - 3) / maxModules;
            if (perModule < MIN_MODULE_MM) {
                warning.html("The selected barcode is long for a " + width +
                    "mm sticker, so the bars become very thin. Choose a wider size " +
                    "if your scanner has trouble reading it.").show();
            } else {
                warning.hide();
            }
        } else {
            warning.hide();
        }
    }//refreshTotals

    function openDialog(ids) {
        if (!ids || ids.length === 0) {
            return;
        }

        $("#bc_error").hide();
        $("#bc_stale_warning").hide();
        $("#bc_size_warning").hide();
        $("#bc_items_wrap").hide();
        $("#bc_loading").show();
        $("#bc_btn_print").prop("disabled", true);
        $("#bc_total_summary").html("&nbsp;");
        $("#bc_items_body").empty();

        modal("show");

        $.ajax({
            url: "../AJAX/Products/getBarcodeItems.php",
            type: "POST",
            dataType: "json",
            data: { product_ids: ids },
            success: function (res) {
                if (!res || res.ok !== true) {
                    showError((res && res.message) ? res.message : "Unable to load the barcode data.");
                    return;
                }

                /* The shop sets the house style, the operator adjusts for the
                   roll actually loaded in front of them - whichever was chosen
                   more recently wins. */
                applyStoredOptions(res.defaults);

                //the shop name is the placeholder for the header line
                if (res.shop_name) {
                    $("#bc_shop_text").attr("placeholder", res.shop_name);
                }

                var html = "";
                for (var i = 0; i < res.items.length; i++) {
                    html += buildRow(res.items[i]);
                }

                $("#bc_items_body").html(html);

                //store the module count for the sticker size warning
                for (var j = 0; j < res.items.length; j++) {
                    $("#bc_items_body tr[data-bc-id='" + res.items[j].id + "']")
                        .data("bc-modules", res.items[j].modules);
                }

                $("#bc_loading").hide();
                $("#bc_error").hide();
                $("#bc_items_wrap").show();

                updateLayout();
            },
            error: function () {
                showError("Unable to reach the server. Please check your connection and try again.");
            }
        });
    }//openDialog

    //========================================================== bindings =====

    $(function () {

        //remember what the dialog looks like untouched, for "Reset options"
        factoryOptions = readOptions();

        //----------------------------------------------------- product table
        $("#tbl_products").on("change", ".bc-select", function () {
            toggleSelection($(this).closest("tr").data("id"), $(this).is(":checked"));
            syncSelection();
        });

        $(document).on("change", "#bc_select_all", function () {
            var on = $(this).is(":checked");
            $("#tbl_products .bc-select").each(function () {
                toggleSelection($(this).closest("tr").data("id"), on);
            });
            syncSelection();
        });

        $(document).on("click", "#btn_clear_selection", function () {
            writeSelection([]);
            syncSelection();
        });

        //single product - print just this one, ignoring the tick boxes
        $("#tbl_products").on("click", ".btn_open_barcode", function (e) {
            e.preventDefault();
            var id = parseInt($(this).closest("tr").data("id"), 10);
            if (id > 0) {
                openDialog([id]);
            }
        });

        //bulk - print everything that is ticked
        $(document).on("click", "#btn_print_selected", function (e) {
            e.preventDefault();
            openDialog(readSelection());
        });

        //------------------------------------------------------------ dialog
        $("#product_barcode_modal").on("change", ".bc-batch", function () {
            var cell = $(this).closest("td");
            cell.find(".bc-price").val($(this).val());
            cell.find(".bc-batch-value").val($(this).find("option:selected").data("batch") || "");
        });

        /* input: recalculate the total, but NEVER rewrite what is being typed -
           that is what made the Labels box impossible to clear. */
        $("#product_barcode_modal").on("input", ".bc-qty, #bc_copies", refreshTotals);

        /* blur: now that the operator has finished, put a usable number back */
        $("#product_barcode_modal").on("blur", ".bc-qty", function () {
            clampQty($(this), 1);
            refreshTotals();
        });

        $("#product_barcode_modal").on("blur", "#bc_copies", function () {
            var copies = parseInt($(this).val(), 10);
            if (!(copies > 0)) {
                copies = 1;
            }
            if (copies > 100) {
                copies = 100;
            }
            $(this).val(copies);
            refreshTotals();
            savePrefs();
        });

        function layoutChanged() {
            updateLayout();
            savePrefs();
        }//layoutChanged

        $("#product_barcode_modal").on("change",
            "#bc_size_group input, #bc_across_group input", layoutChanged);

        $("#product_barcode_modal").on("input change",
            "#bc_custom_w, #bc_custom_h, #bc_col_gap, #bc_row_gap, #bc_skip", layoutChanged);

        //any other option only needs remembering, not re-measuring
        $("#product_barcode_modal").on("input change",
            "#bc_tab_content input, #bc_tab_content select, " +
            "#bc_tab_style input, #bc_tab_style select, " +
            "#bc_padding, #bc_guides, #bc_auto_print", function () {
            savePrefs();
            refreshTotals();
        });

        $("#product_barcode_modal").on("click", ".bc-remove", function () {
            $(this).closest("tr").remove();
            refreshTotals();
        });

        $("#bc_btn_reset_options").on("click", function () {
            writeOptions(factoryOptions);
            updateLayout();
            savePrefs();
        });

        //-------------------------------------------- save the shop default
        $("#bc_btn_save_default").on("click", function () {
            var button = $(this);
            var original = button.text();

            button.prop("disabled", true).text("Saving...");

            $.ajax({
                url: "../AJAX/Barcode/saveLabelDefaults.php",
                type: "POST",
                dataType: "json",
                data: { options: JSON.stringify(readOptions()) },
                success: function (res) {
                    button.text((res && res.ok) ? "Saved" : ((res && res.message) ? res.message : "Failed"));
                },
                error: function () {
                    button.text("Failed");
                },
                complete: function () {
                    window.setTimeout(function () {
                        button.prop("disabled", false).text(original);
                    }, 1800);
                }
            });
        });

        //------------------------------------------------------------ submit
        $("#barcode_print_form").on("submit", function () {
            if ($("#bc_items_body .bc-qty").length === 0) {
                return false;
            }

            /* Somebody can hit Print with a quantity box still empty, because
               blur never fired. Fill those in rather than sending a blank. */
            $("#bc_items_body .bc-qty").each(function () {
                clampQty($(this), 1);
            });

            //a custom sticker smaller than this cannot hold a scannable barcode,
            //and a blank box would send the printer a 0mm page
            var size = labelSize();
            if (!(size.w >= 15) || !(size.h >= 15)) {
                $("#bc_error").text("Enter a sticker size of at least 15mm x 15mm before printing.").show();
                return false;
            }

            savePrefs();

            //close the dialog - the print page opens in a new tab
            window.setTimeout(function () {
                modal("hide");
            }, 200);

            return true;
        });

        $("#btn_close_barcode_modal").on("click", function () {
            modal("hide");
        });

        //re-apply the tick boxes after the product table is re-drawn by the search
        $(document).ajaxComplete(function (event, xhr, settings) {
            if (settings && settings.url && settings.url.indexOf("getProductSearch.php") !== -1) {
                syncSelection();
            }
        });

        loadPrefs();
        updateLayout();
        syncSelection();
    });

})();
