//Scanner upload dialog for GRNs and transfers - see
//docs/superpowers/specs/2026-09-22-scanner-upload-design.md, sections 4 and 11.
//
//The scanner's cradle types the stored codes like a keyboard. The dialog's box takes them
//(Enter and Tab become new lines, so codes never run together); the server reads, counts and
//checks them (Controller/ScanUploadController.php) and this script shows the preview and
//sends the user's choices back. Scanner-speed typing while no field has the focus opens the
//dialog by itself.
(function ($) {
    'use strict';

    var ENDPOINT = '../Controller/ScanUploadController.php';
    var modal, box, context, docId;
    var checkTimer = null;
    var request = 0;                    //only the newest check may draw the preview
    var preview = null;
    var leaveOut = {};                  //key => true
    var burst = { text: '', times: [] };

    var COLUMNS = {
        grn: function (o) {
            var c = ['Barcode', 'Product', 'Qty', 'Purchase Price', 'Selling Price'];
            if (o.label_price) { c.push('Label Price'); }
            if (o.expiry) { c.push('Mnf Date', 'Exp Date'); }
            return c.concat(['Status', '']);
        },
        transfer_send: function () { return ['Barcode', 'Product', 'Qty', 'In Stock', 'Batches', 'Status', '']; },
        transfer_receive: function () { return ['Barcode', 'Product', 'Sent', 'Scanned', 'Received', 'Status', '']; }
    };

    function esc(value) {
        return $('<div>').text(value === null || value === undefined ? '' : String(value)).html();
    }

    function isOpen() { return modal.classList.contains('show'); }

    function editable(el) {
        return !!el && (el.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(el.tagName));
    }

    function open(text) {
        bootstrap.Modal.getOrCreateInstance(modal).show();
        if (text) { box.value += text; }
        focusBox();
        updateCount();
        scheduleCheck();
    }

    function focusBox() {
        box.focus();
        box.setSelectionRange(box.value.length, box.value.length);
    }

    //---- capture ----------------------------------------------------------------------------

    function onBoxKey(e) {
        if (e.key === 'Tab') {                          //a Tab suffix would move the focus away
            e.preventDefault();
            var start = box.selectionStart, end = box.selectionEnd;
            box.value = box.value.slice(0, start) + '\n' + box.value.slice(end);
            box.setSelectionRange(start + 1, start + 1);
            onBoxInput();
        }
    }

    function onBoxInput() {
        updateCount();
        scheduleCheck();
    }

    function resetBurst() { burst.text = ''; burst.times = []; }

    //keys pressed while the dialog is closed and no field has the focus: a burst typed at
    //scanner speed and ended by Enter or Tab opens the dialog with it. While the dialog is
    //open, a key outside any field is sent to the box.
    function onDocumentKey(e) {
        if (e.ctrlKey || e.altKey || e.metaKey) { resetBurst(); return; }
        if (isOpen()) {
            if (!editable(e.target)) { focusBox(); }
            return;
        }
        if (editable(e.target)) { resetBurst(); return; }
        var now = performance.now();
        if (burst.times.length && now - burst.times[burst.times.length - 1] > 100) { resetBurst(); }
        if (e.key === 'Enter' || e.key === 'Tab') {
            var n = burst.times.length;
            var fast = burst.text.length >= 4 && (burst.times[n - 1] - burst.times[0]) / (n - 1) <= 35;
            var text = burst.text;
            resetBurst();
            if (fast) {
                e.preventDefault();
                e.stopPropagation();
                open(text + '\n');
            }
            return;
        }
        if (e.key.length === 1) {
            burst.text += e.key;
            burst.times.push(now);
        }
    }

    function updateCount() {
        var n = box.value.split(/[\s,;]+/).filter(function (t) { return t !== ''; }).length;
        $('#scan_count').text(n + (n === 1 ? ' code read' : ' codes read'));
        $('#scan_raw').text(box.value.replace(/\t/g, '⇥\t').replace(/\r?\n/g, '⏎\n'));
    }

    //---- server -----------------------------------------------------------------------------

    function scheduleCheck() {
        clearTimeout(checkTimer);
        checkTimer = setTimeout(check, 400);
    }

    function decisions(action) {
        var data = {
            context: context,
            doc_id: docId,
            action: action,
            raw: box.value,
            csrf_token: $('#scan_csrf_token').val(),
            leave_out: Object.keys(leaveOut)
        };
        $('#scan_preview [data-field]').each(function () {
            var f = $(this);
            data[f.attr('data-group') + '[' + f.attr('data-product') + '][' + f.attr('data-field') + ']'] = f.val();
        });
        if ($('#scan_rack_row').is(':visible')) { data.rack_id = $('#scan_rack').val(); }
        return data;
    }

    function post(data) {
        return $.ajax({ url: ENDPOINT, method: 'POST', data: data, dataType: 'json' });
    }

    function check() {
        clearTimeout(checkTimer);
        if ($.trim(box.value) === '') { render(null); return; }
        var mine = ++request;
        $('#scan_busy').show();
        post(decisions('check'))
            .done(function (res) {
                if (mine !== request) { return; }
                message('');
                render(res.preview);
            })
            .fail(function (xhr) { if (mine === request) { failed(xhr); } })
            .always(function () { if (mine === request) { $('#scan_busy').hide(); } });
    }

    function apply(extra) {
        var sent = $.extend({}, extra || {});
        $('#scan_apply').prop('disabled', true);
        post($.extend(decisions('apply'), sent))
            .done(function (res) {
                bootstrap.Modal.getOrCreateInstance(modal).hide();
                reset();
                $(document).trigger('scanupload:applied', [res.result]);
                alert(res.message);
            })
            .fail(function (xhr) {
                var res = xhr.responseJSON || {};
                if (xhr.status === 409 && res.confirm) {
                    if (res.preview) { render(res.preview); }
                    if (confirm(res.message)) {
                        sent['confirm_' + res.confirm] = 1;
                        apply(sent);
                        return;
                    }
                    message(res.message, 'warning');
                } else {
                    failed(xhr);
                }
                $('#scan_apply').prop('disabled', !(preview && preview.can_apply));
            });
    }

    function failed(xhr) {
        var res = xhr.responseJSON || {};
        if (res.preview) { render(res.preview); }
        message(res.message || 'Something went wrong. Nothing was saved.', 'danger');
    }

    function message(text, kind) {
        $('#scan_message').html(text ? '<div class="alert alert-' + kind + ' py-2 mb-0">' + esc(text) + '</div>' : '');
    }

    function reset() {
        box.value = '';
        leaveOut = {};
        message('');
        render(null);
        updateCount();
    }

    //---- preview ----------------------------------------------------------------------------

    function render(p) {
        preview = p;
        var head = $('#scan_preview thead').empty();
        var body = $('#scan_preview tbody').empty();
        if (!p) {
            $('#scan_summary').text('');
            $('#scan_rack_row').hide();
            $('#scan_apply').prop('disabled', true);
            return;
        }
        var opts = p.options || {};
        $('#scan_count').text(p.scans + (p.scans === 1 ? ' code read' : ' codes read')
            + (p.truncated ? ' - the upload was too long, only the first part was read' : ''));
        head.append('<tr>' + COLUMNS[context](opts).map(function (c) { return '<th>' + esc(c) + '</th>'; }).join('') + '</tr>');
        p.lines.forEach(function (line) { body.append(row(line, opts)); });
        rack(opts, p.rack_id);

        var products = 0, items = 0;
        $.each(p.applied || {}, function (code, qty) { products++; items += Number(qty); });
        $('#scan_summary').text(products + ' product(s), ' + items + ' item(s)'
            + (p.blocking ? ' - ' + p.blocking + ' line(s) need attention' : ''));
        $('#scan_apply').prop('disabled', !p.can_apply);
        if (p.duplicate) {
            message('This scan batch was already added by ' + p.duplicate.user + ' on ' + p.duplicate.at + '.', 'warning');
        }
    }

    function row(line, opts) {
        var cls = line.left_out ? 'table-secondary text-muted'
            : (line.status === 'error' ? 'table-danger' : (line.status === 'warn' ? 'table-warning' : ''));
        var cells = ['<td class="font-monospace">' + esc(line.barcode || line.key) + '</td>', '<td>' + esc(line.name) + '</td>'];
        if (context === 'grn') {
            cells.push(num(line.qty));
            cells.push(field(line, 'prices', 'purchase', line.purchase_price, 'number'));
            cells.push(field(line, 'prices', 'selling', line.selling_price, 'number'));
            if (opts.label_price) { cells.push(field(line, 'prices', 'label', line.label_price, 'number')); }
            if (opts.expiry) {
                cells.push(field(line, 'dates', 'mnf', line.mnf_date, 'date'));
                cells.push(field(line, 'dates', 'exp', line.exp_date, 'date'));
            }
        } else if (context === 'transfer_send') {
            cells.push(num(line.qty), num(line.available), '<td>' + esc((line.batches || []).map(function (b) {
                return b.batch_id + ' × ' + b.qty;
            }).join(', ')) + '</td>');
        } else {
            cells.push(num(line.sent), num(line.qty), num(line.received));
        }
        cells.push('<td>' + badge(line) + ' <span class="small">' + esc(line.message) + '</span></td>');
        cells.push('<td class="text-end">' + action(line) + '</td>');
        return '<tr class="' + cls + '">' + cells.join('') + '</tr>';
    }

    function num(value) {
        return '<td class="text-end">' + esc(value === null || value === undefined ? '' : value) + '</td>';
    }

    function field(line, group, name, value, type) {
        if (!line.editable || line.left_out) { return '<td>' + esc(value) + '</td>'; }
        return '<td><input type="' + type + '"' + (type === 'number' ? ' step="0.01" min="0"' : '')
            + ' class="form-control form-control-sm" style="min-width:110px" data-group="' + group
            + '" data-product="' + esc(line.product_id) + '" data-field="' + name + '" value="' + esc(value) + '"></td>';
    }

    function badge(line) {
        if (line.left_out) { return '<span class="badge text-bg-secondary">Left out</span>'; }
        if (line.status === 'error') { return '<span class="badge bg-danger">Problem</span>'; }
        if (line.status === 'warn') { return '<span class="badge text-bg-warning">Check</span>'; }
        return '<span class="badge text-bg-success">OK</span>';
    }

    function action(line) {
        if (line.left_out) {
            return '<button type="button" class="btn btn-sm btn-outline-secondary scan-leave-out" data-key="' + esc(line.key) + '">Undo</button>';
        }
        if (!line.can_leave_out) { return ''; }
        return '<button type="button" class="btn btn-sm btn-outline-danger scan-leave-out" data-key="' + esc(line.key)
            + '" title="Leave this code out of the upload">Leave out</button>';
    }

    function rack(opts, selected) {
        if (!opts.racks || !opts.racks_list || !opts.racks_list.length) { $('#scan_rack_row').hide(); return; }
        var select = $('#scan_rack').empty();
        opts.racks_list.forEach(function (r) { select.append($('<option>').val(r.id).text(r.name)); });
        select.val(String(selected));
        $('#scan_rack_row').show();
    }

    //---- wiring -----------------------------------------------------------------------------

    $(function () {
        modal = document.getElementById('scan_upload_modal');
        if (!modal) { return; }
        box = document.getElementById('scan_capture');
        context = modal.getAttribute('data-context');
        docId = modal.getAttribute('data-doc-id');

        $(document).on('click', '.btn-scan-upload', function (e) { e.preventDefault(); open(''); });
        box.addEventListener('keydown', onBoxKey);
        box.addEventListener('input', onBoxInput);
        modal.addEventListener('shown.bs.modal', focusBox);
        $('#scan_check').on('click', function () { check(); focusBox(); });
        $('#scan_clear').on('click', function () { reset(); focusBox(); });
        $('#scan_toggle_raw').on('click', function () {
            $('#scan_raw').toggle();
            $(this).text($('#scan_raw').is(':visible') ? 'Hide raw' : 'Show raw');
            focusBox();
        });
        $('#scan_preview').on('click', '.scan-leave-out', function () {
            var key = $(this).attr('data-key');
            if (leaveOut[key]) { delete leaveOut[key]; } else { leaveOut[key] = true; }
            check();
            focusBox();
        });
        $('#scan_preview').on('change', '[data-field]', function () { check(); });
        $('#scan_rack').on('change', function () { check(); focusBox(); });
        $('#scan_apply').on('click', function () { apply(); });
        document.addEventListener('keydown', onDocumentKey, true);
    });
})(jQuery);
