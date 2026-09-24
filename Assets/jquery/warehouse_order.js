// The buttons on the warehouse order job sheet (Public/customer-order.php).
// Every action posts to Controller/WarehouseOrderController.php and reloads the page, so what
// is on the screen is always what is in the database - never a guess made in the browser.

$(function () {
    function post(fields, button) {
        button.prop("disabled", true);
        $.ajax({
            url: "../Controller/WarehouseOrderController.php",
            method: "post",
            dataType: "json",
            data: $.extend({
                csrf_token: $("#wo_csrf_token").val(),
                id: $("#wo_order_id").val()
            }, fields)
        }).done(function () {
            window.location.reload();
        }).fail(function (xhr) {
            button.prop("disabled", false);
            var message = "Something went wrong. Please try again.";
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            // The customer still owes something. Delivering on balance payment is normal here,
            // so it is a question, not a wall - but somebody has to answer it.
            if (xhr.status === 409 && fields.action === "complete_dispatch" && !fields.confirm_balance) {
                if (confirm(message)) {
                    post($.extend({}, fields, { confirm_balance: 1 }), button);
                }
                return;
            }
            if (typeof toastr !== "undefined") {
                toastr.error(message, "Not done");
            } else {
                alert(message);
            }
        });
    }//post

    $("body").on("click", ".wo-action", function () {
        var button = $(this);
        var action = button.data("action");
        var fields = { action: action };

        if (button.data("line")) {
            fields.line_id = button.data("line");
        }

        if (action === "cannot_supply") {
            var reason = prompt("Why can this item not be supplied? The shop will see this.");
            if (reason === null || $.trim(reason) === "") {
                return;
            }
            fields.reason = reason;
        }

        if (button.data("dispatch")) {
            fields.id = button.data("dispatch");
        }//dispatch actions name the trip, not the order

        // Starting a trip: everything ready goes by default, but a line of several can be split
        // when the van will not hold it all.
        if (action === "open_dispatch") {
            var ready = button.data("ready") || [];
            fields.line_id = [];
            fields.line_qty = [];
            for (var i = 0; i < ready.length; i++) {
                var going = ready[i].qty;
                if (ready[i].qty > 1) {
                    var answer = prompt("How many " + ready[i].name + " go on this trip? (up to "
                        + ready[i].qty + ", 0 to leave it behind)", ready[i].qty);
                    if (answer === null) {
                        return;
                    }
                    going = parseFloat(answer);
                    if (isNaN(going) || going < 0) {
                        toastr.warning("That is not a quantity.", "Not started");
                        return;
                    }
                }
                fields.line_id.push(ready[i].id);
                fields.line_qty.push(going);
            }
        }

        if (button.data("ask-note")) {
            var note = prompt(button.data("ask-note"), "");
            if (note === null) {
                return;
            }
            fields.note = note;
        }

        if (button.data("confirm") && !confirm(button.data("confirm"))) {
            return;
        }

        post(fields, button);
    });

    $("body").on("click", "#wo_scan", function () {
        var modal = document.getElementById("scan_upload_modal");
        if (modal) {
            bootstrap.Modal.getOrCreateInstance(modal).show();
        }
    });

    // the scanner dialog reloads the page once its items are in the dispatch
    $(document).on("scanupload:applied", function () {
        window.location.reload();
    });
});
