/**
 * Settings > Barcode Settings - live preview.
 * -----------------------------------------------------------------------------
 * Posts the CURRENT form values (not the saved ones) to previewPattern.php and
 * paints the next three barcodes plus every warning the server found.
 *
 * The endpoint only ever PEEKS at the sequence counter, so moving these
 * controls around never uses up a barcode number.
 */
$(function () {

    var previewTimer = null;
    var lastRequest = null;

    /**
     * Everything the preview needs. Checkboxes are sent explicitly because an
     * unchecked box is simply absent from a serialised form, and the server
     * would then fall back to the default rather than to "off".
     */
    function collect() {
        return {
            Pattern:        $("#Pattern").val(),
            FixedPrefix:    $("#FixedPrefix").val(),
            Suffix:         $("#Suffix").val(),
            ShopCode:       $("#ShopCode").val(),
            Separator:      $("#Separator").val(),
            CatCodeLength:  $("#CatCodeLength").val(),
            SubCodeLength:  $("#SubCodeLength").val(),
            SeqScope:       $("#SeqScope").val(),
            SeqStart:       $("#SeqStart").val(),
            SeqStep:        $("#SeqStep").val(),
            SeqLength:      $("#SeqLength").val(),
            SeqPadChar:     $("#SeqPadChar").val(),
            Casing:         $("#Casing").val(),
            Symbology:      $("#Symbology").val(),
            MaxLength:      $("#MaxLength").val(),
            StripInvalid:   $("#StripInvalid").is(":checked") ? 1 : 0,
            AutoGenerate:   $("#AutoGenerate").is(":checked") ? 1 : 0,
            preview_subcat: $("#preview_subcat").val()
        };
    }//collect

    //safe for both text nodes and double quoted attribute values
    function escapeHtml(text) {
        var html = $("<div>").text(text === null || text === undefined ? "" : text).html();
        return html.replace(/"/g, "&quot;").replace(/'/g, "&#39;");
    }//escapeHtml

    function paintWarnings(list) {
        var box = $("#bcs_warnings");
        box.empty();

        if (!list || list.length === 0) {
            return;
        }

        for (var i = 0; i < list.length; i++) {
            box.append(
                '<div class="alert alert-warning py-2 px-3 fs-2 mb-2">' + escapeHtml(list[i]) + '</div>'
            );
        }
    }//paintWarnings

    function refresh() {
        //only ever one request in flight - the operator types faster than the
        //server answers, and a late reply must not overwrite a newer one
        if (lastRequest && lastRequest.readyState !== 4) {
            lastRequest.abort();
        }

        lastRequest = $.ajax({
            url: "../AJAX/Barcode/previewPattern.php",
            type: "POST",
            dataType: "json",
            data: collect(),
            success: function (res) {
                if (!res || res.ok !== true) {
                    $("#bcs_code").text("--");
                    $("#bcs_bars").empty();
                    paintWarnings([(res && res.message) ? res.message : "Preview unavailable."]);
                    return;
                }

                $("#bcs_code").text(res.code || "--");
                $("#bcs_bars").html(res.svg || "");
                $("#bcs_scope").text(res.scope_key || "-");
                $("#bcs_cat").text(res.cat_code || "-");
                $("#bcs_sub").text(res.sub_code || "-");
                $("#bcs_shop").text(res.shop_code || "-");

                if (res.series && res.series.length > 1) {
                    $("#bcs_series").text("then " + res.series.slice(1).join(", ") + " ...");
                } else {
                    $("#bcs_series").text("");
                }

                paintWarnings(res.warnings);
            },
            error: function (xhr, status) {
                if (status === "abort") {
                    return;   //superseded by a newer keystroke
                }
                $("#bcs_code").text("--");
                paintWarnings(["Could not reach the server for the preview."]);
            }
        });
    }//refresh

    /**
     * Wait for a pause in the typing before asking the server - one request per
     * keystroke would be a request per character of the pattern.
     */
    function queueRefresh() {
        window.clearTimeout(previewTimer);
        previewTimer = window.setTimeout(refresh, 250);
    }//queueRefresh

    //--------------------------------------------------------------- bindings
    $("#bcs_form").on("input change",
        "#Pattern, #FixedPrefix, #Suffix, #ShopCode, #Separator, #CatCodeLength, " +
        "#SubCodeLength, #SeqScope, #SeqStart, #SeqStep, #SeqLength, #SeqPadChar, " +
        "#Casing, #Symbology, #MaxLength, #StripInvalid, #AutoGenerate",
        queueRefresh);

    $("#preview_subcat").on("change", refresh);

    /**
     * Insert a token where the cursor is, rather than at the end - the operator
     * is usually fixing the middle of a pattern, not appending to it.
     */
    $(".bcs-token").on("click", function () {
        var token = $(this).data("token");
        var input = $("#Pattern");
        var el = input.get(0);
        var value = input.val();

        var start = (el && typeof el.selectionStart === "number") ? el.selectionStart : value.length;
        var end = (el && typeof el.selectionEnd === "number") ? el.selectionEnd : value.length;

        input.val(value.substring(0, start) + token + value.substring(end));

        if (el && el.setSelectionRange) {
            el.focus();
            el.setSelectionRange(start + token.length, start + token.length);
        }

        queueRefresh();
    });

    //a preset sets the format AND the separator that goes with it
    $(".bcs-preset").on("click", function () {
        $("#Pattern").val($(this).data("pattern"));
        $("#Separator").val($(this).data("sep"));
        refresh();
    });

    //codes are pasted straight into a barcode - keep them alphanumeric
    $("#FixedPrefix, #Suffix, #ShopCode").on("input", function () {
        var clean = $(this).val().replace(/[^A-Za-z0-9]/g, "").toUpperCase();
        if (clean !== $(this).val()) {
            $(this).val(clean);
        }
    });

    refresh();
});
