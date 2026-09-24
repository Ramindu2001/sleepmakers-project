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

        if (button.data("confirm") && !confirm(button.data("confirm"))) {
            return;
        }

        post(fields, button);
    });
});
