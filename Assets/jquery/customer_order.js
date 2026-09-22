//Customer orders pages (Public/customer-orders.php, Public/customer-order.php) - see
//docs/superpowers/specs/2026-09-22-customer-orders-design.md. Every change goes to
//Controller/CustomerOrderController.php, which checks it again; after an action the page
//reloads and shows the message the controller left.
(function ($) {
    'use strict';

    var ENDPOINT = '../Controller/CustomerOrderController.php';

    function esc(value) {
        return $('<div>').text(value === null || value === undefined ? '' : String(value)).html();
    }

    function post(action, fields) {
        return $.ajax({
            url: ENDPOINT,
            method: 'POST',
            dataType: 'json',
            data: $.extend({ action: action, csrf_token: $('#co_csrf_token').val() }, fields || {})
        });
    }

    function failure(xhr) {
        return (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong. Nothing was saved.';
    }

    //---- the list: status filter --------------------------------------------------------------

    function initList() {
        $('#co_status_filter').on('change', function () {
            var wanted = $(this).val();
            $('.co-row').each(function () {
                $(this).toggle(wanted === '' || $(this).attr('data-status') === wanted);
            });
        });
    }

    //---- the form: new and edit ------------------------------------------------------------

    function supplierId() { return $('#co_supplier').val(); }

    //a product search over the supplier's catalog (with its stock) or this shop's own products
    function productSelect($select, scope) {
        $select.select2({
            width: '100%',
            placeholder: scope === 'supplier' ? 'Search the catalog' : 'Search our products (optional)',
            allowClear: scope !== 'supplier',
            minimumInputLength: 1,
            ajax: {
                url: ENDPOINT,
                type: 'POST',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { action: 'products', scope: scope, supplier_id: supplierId(), term: params.term, csrf_token: $('#co_csrf_token').val() };
                },
                processResults: function (res) {
                    return {
                        results: (res.products || []).map(function (p) {
                            return { id: p.id, text: (p.barcode ? p.barcode + ' - ' : '') + p.name + (scope === 'supplier' ? ' (in stock: ' + p.in_stock + ')' : '') };
                        })
                    };
                }
            }
        });
    }

    function removeButton() {
        return '<button type="button" class="btn btn-sm btn-outline-danger co-remove" title="Remove this line"><i class="ti ti-trash"></i></button>';
    }

    function addGiven(line) {
        line = line || {};
        var $row = $('<tr class="co-line" data-source="GIVEN" data-kind="product">'
            + '<td><select class="co-product"></select>'
            + '<input type="text" class="form-control mt-1 co-description" maxlength="255" placeholder="or describe the item" value="' + esc(line.product_id ? '' : line.text) + '"></td>'
            + '<td><input type="number" step="any" min="0" class="form-control co-qty" value="' + esc(line.qty || 1) + '"></td>'
            + '<td><input type="text" class="form-control co-invoice" maxlength="60" value="' + esc(line.invoice_no) + '"></td>'
            + '<td class="text-end">' + removeButton() + '</td></tr>');
        $('#co_given tbody').append($row);
        var $select = $row.find('.co-product');
        if (line.product_id) { $select.append(new Option(line.text, line.product_id, true, true)); }
        productSelect($select, 'own');
        return $row;
    }

    function addWarehouse(line, kind) {
        line = line || {};
        var custom = kind === 'custom';
        var item = custom
            ? '<span class="badge text-bg-light mb-1">Custom-made</span><input type="text" class="form-control co-description" maxlength="255" placeholder="Describe the item" value="' + esc(line.text) + '">'
            : '<select class="co-product"></select>';
        var $row = $('<tr class="co-line" data-source="WAREHOUSE" data-kind="' + (custom ? 'custom' : 'product') + '">'
            + '<td>' + item + '</td>'
            + '<td><input type="number" step="any" min="0" class="form-control co-qty" value="' + esc(line.qty || 1) + '"></td>'
            + '<td><input type="text" class="form-control co-notes" maxlength="255" placeholder="Size, colour, firmness..." value="' + esc(line.notes) + '"></td>'
            + '<td class="text-end">' + removeButton() + '</td></tr>');
        $('#co_warehouse tbody').append($row);
        if (!custom) {
            var $select = $row.find('.co-product');
            if (line.product_id) { $select.append(new Option(line.text, line.product_id, true, true)); }
            productSelect($select, 'supplier');
        }
        return $row;
    }

    function collect() {
        var lines = [];
        $('.co-line').each(function () {
            var $row = $(this);
            var product = $row.find('.co-product').length ? ($row.find('.co-product').val() || '') : '';
            lines.push({
                source: $row.attr('data-source'),
                product_id: product,
                description: $row.find('.co-description').val() || '',
                qty: $row.find('.co-qty').val(),
                notes: $row.find('.co-notes').val() || '',
                invoice_no: $row.find('.co-invoice').val() || ''
            });
        });
        return {
            supplier_id: supplierId(),
            cust_name: $('#co_cust_name').val(),
            cust_phone: $('#co_cust_phone').val(),
            cust_address: $('#co_cust_address').val(),
            needed_by: $('#co_needed_by').val(),
            advance: $('#co_advance').val(),
            notes: $('#co_notes').val(),
            lines: lines
        };
    }

    function initForm() {
        var $form = $('#co_form');
        var mode = $form.attr('data-mode');
        var orderId = $form.attr('data-order-id');
        var initial = JSON.parse($('#co_initial').text() || '[]');

        function showSupplier() { $('#co_supplier_name').text($('#co_supplier option:selected').text()); }
        showSupplier();
        var lastSupplier = supplierId();
        $('#co_supplier').on('change', function () {
            var chosen = $('#co_warehouse .co-product').filter(function () { return $(this).val(); }).length;
            if (chosen && !confirm('Products are chosen from ' + $('#co_supplier option[value="' + lastSupplier + '"]').text()
                + '. Change the shop and clear them?')) {
                $(this).val(lastSupplier);
                return;
            }
            $('#co_warehouse .co-product').val(null).trigger('change');
            lastSupplier = supplierId();
            showSupplier();
        });

        initial.forEach(function (line) {
            if (line.source === 'GIVEN') { addGiven(line); } else { addWarehouse(line, line.kind); }
        });
        if (!initial.length) { addWarehouse({}, 'product'); }

        $('#co_add_given').on('click', function () { addGiven({}); });
        $('#co_add_product').on('click', function () { addWarehouse({}, 'product'); });
        $('#co_add_custom').on('click', function () { addWarehouse({}, 'custom').find('.co-description').focus(); });
        $form.on('click', '.co-remove', function () { $(this).closest('tr').remove(); });

        $('#co_save').on('click', function () {
            var $button = $(this).prop('disabled', true);
            $('#co_error').hide();
            post(mode === 'edit' ? 'update' : 'create', { id: orderId, order: JSON.stringify(collect()) })
                .done(function (res) { location = 'customer-order.php?id=' + res.id; })
                .fail(function (xhr) {
                    $('#co_error').text(failure(xhr)).show();
                    $button.prop('disabled', false);
                });
        });
    }

    //---- the order: actions -----------------------------------------------------------------

    var ACTIONS = {
        accept: { title: 'Accept order', text: 'Accept this order? The showroom sees that you are preparing it.', ok: 'Accept' },
        reject: { title: 'Reject order', text: 'Tell the showroom why the order cannot be supplied.', ok: 'Reject',
            fields: '<textarea class="form-control" id="co_field_reason" rows="3" maxlength="255" placeholder="Reason"></textarea>' },
        cancel: { title: 'Cancel order', text: 'Cancel this order? The supplier sees it as cancelled.', ok: 'Cancel order' },
        create_transfer: { title: 'Create transfer', ok: 'Create transfer',
            text: 'Create a transfer (on hold) for what this order still needs, from the oldest stock? You then check and verify it on the Transfer page.' },
        handover: { title: 'Handed over', ok: 'Handed over',
            text: 'The customer took everything? Invoice the items in POS as usual, then note the invoice number here.',
            fields: '<input type="text" class="form-control" id="co_field_invoice" maxlength="60" placeholder="Invoice No (optional)">' },
        custom_sent: { title: 'Mark custom-made item sent', ok: 'Save',
            text: 'How many of this item have been sent to the showroom?',
            fields: '<div class="row g-2"><div class="col-4"><input type="number" step="any" min="0" class="form-control" id="co_field_qty"></div>'
                + '<div class="col-8"><input type="text" class="form-control" id="co_field_note" maxlength="255" placeholder="Note (vehicle, date...)"></div></div>' }
    };

    function initOrder() {
        var orderId = $('#co_order').attr('data-order-id');
        var modal = document.getElementById('co_action_modal');
        var current = null;

        $('#co_order').on('click', '.co-action', function () {
            var $button = $(this);
            var config = ACTIONS[$button.attr('data-action')];
            current = { action: $button.attr('data-action'), line: $button.attr('data-line-id') };
            $('#co_modal_title').text(config.title);
            $('#co_modal_text').text(config.text);
            $('#co_modal_fields').html(config.fields || '');
            $('#co_modal_ok').text(config.ok).prop('disabled', false);
            $('#co_modal_error').hide();
            if (current.action === 'custom_sent') {
                $('#co_field_qty').val($button.attr('data-sent') === '0' ? $button.attr('data-qty') : $button.attr('data-sent'))
                    .attr('max', $button.attr('data-qty'));
                $('#co_field_note').val($button.attr('data-note') || '');
            }
            bootstrap.Modal.getOrCreateInstance(modal).show();
        });

        $('#co_modal_ok').on('click', function () {
            var fields = { id: orderId };
            if (current.action === 'reject') { fields.reason = $('#co_field_reason').val(); }
            if (current.action === 'handover') { fields.invoice_no = $('#co_field_invoice').val(); }
            if (current.action === 'custom_sent') {
                fields.line_id = current.line;
                fields.qty = $('#co_field_qty').val();
                fields.note = $('#co_field_note').val();
            }
            var $ok = $(this).prop('disabled', true);
            post(current.action, fields)
                .done(function () { location.reload(); })
                .fail(function (xhr) {
                    $('#co_modal_error').text(failure(xhr)).show();
                    $ok.prop('disabled', false);
                });
        });
    }

    $(function () {
        if ($('#co_status_filter').length) { initList(); }
        if ($('#co_form').length) { initForm(); }
        if ($('#co_order').length) { initOrder(); }
    });
})(jQuery);
