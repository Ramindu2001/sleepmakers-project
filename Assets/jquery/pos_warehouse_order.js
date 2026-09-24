// POS to warehouse fulfilment, the counter's half of it.
// (docs/superpowers/specs/2026-09-24-pos-warehouse-fulfilment-design.md)
//
// A cart line is one of two things: something handed over now, or something the warehouse owes
// the customer. The second kind carries four hidden fields that travel with item_id[] in the
// same order, so the checkout can tell them apart line by line:
//   wh_line[]  wh_custom[]  wh_supplier_product[]  wh_notes[]
//
// Nothing here touches how the shop's own items are sold.

// ---- adding a warehouse item ---------------------------------------------------------------

// Clicked a tile the warehouse holds. It has no stock here and no batch price, so the
// warehouse's own selling price is what the customer pays.
function warehouseProduct(productid) {
    $.get("../AJAX/guiPos/getproducts.php", { product_id: productid, warehouse: 1 }, function (data) {
        var obj = typeof data === "string" ? JSON.parse(data) : data;
        if (!obj.product || obj.error) {
            toastr.error((obj.error && obj.error.message) || "That item cannot be ordered.", "Not available");
            return;
        }

        var before = $("#cart tr").length;
        addToCart(obj.product, obj.inventory[0], obj.discountType, obj.discount);
        if ($("#cart tr").length === before) {
            return;
        }//the cart refused it

        markWarehouseRow($("#cart tr").last(), {
            supplier_product: obj.supplier_product_id,
            supplier_shop: obj.supplier_shop_id,
            supplier_name: obj.supplier_name,
            custom: false
        });
        warehouseChanged();
    });
}//warehouseProduct

// A custom-made item: nobody stocks it, so it is described in words and priced by hand.
function addCustomItem(name, price, notes) {
    name = $.trim(name);
    price = parseFloat(price);
    if (name === "" || isNaN(price) || price < 0) {
        toastr.warning("A custom item needs a description and a price.", "Not added");
        return false;
    }

    var before = $("#cart tr").length;
    addToCart(
        { PDID: "C" + Date.now(), Barcode: "CUSTOM", ItemName: name, ItemType: "S",
          UnitConversion: 1, is_fixedPrice: 0, prodDiscount: 0, prodFlatDiscount: 0 },
        { INID: 0, BatchID: "", SellingPrice: price, PurchasePrice: 0, TotalCurrentQty: 99999 },
        1, 0
    );
    if ($("#cart tr").length === before) {
        return false;
    }

    var row = $("#cart tr").last();
    row.find(".Item_name").val(name);
    markWarehouseRow(row, { supplier_product: "", supplier_shop: $("#wh_supplier_shop").val(), custom: true });
    row.find(".wh_notes").val(notes || "");
    warehouseChanged();
    return true;
}//addCustomItem

// Flags a cart row as the warehouse's job and makes its row id its own, so a shop product that
// happens to share the number can never merge into it. That is also how a cashier splits a line:
// tick the row, click the same item again, and the second row starts fresh as shop stock.
function markWarehouseRow(row, opts) {
    if (row.attr("data-cart-id") === undefined) {
        row.attr("data-cart-id", row.attr("id") || "");
    }// remember what the row was called, so unticking can put it back
    row.attr("data-warehouse", "1")
        .attr("id", "whitem-" + (opts.custom ? "custom-" : (opts.own ? "own-" : "")) + (opts.supplier_product || "x") + "-" + $("#cart tr").length)
        .addClass("table-warning");
    row.find(".wh_line").val("1");
    row.find(".wh_custom").val(opts.custom ? "1" : "");
    row.find(".wh_own").val(opts.own ? "1" : "");
    row.find(".wh_supplier_product").val(opts.supplier_product || "");
    if (opts.supplier_shop) {
        $("#wh_supplier_shop").val(opts.supplier_shop);
    }
    // A tile or a custom item has nothing on the shelf to hand over, so its tick is not a choice.
    // A line the shop itself sells stays the cashier's to change back.
    row.find(".wh-toggle").prop("checked", true).prop("disabled", !opts.own);
    row.find(".wh-badge, .wh-note-btn").remove();
    // the shop name is set by an admin, but it is still data: put it in as text, never markup
    var badge = $('<span class="badge bg-warning text-dark mt-1 wh-badge"></span>')
        .text(opts.custom ? "Custom-made" : "From " + (opts.supplier_name || "the warehouse"));
    var specs = $('<a href="javascript:void(0)" class="badge bg-secondary mt-1 wh-note-btn"></a>').text("Specs");
    row.find(".Item_name").after(badge, " ", specs);
}//markWarehouseRow

// Back to an ordinary cart row: the shop hands this one over after all.
function unmarkWarehouseRow(row) {
    row.removeAttr("data-warehouse").removeClass("table-warning");
    row.find(".wh_line, .wh_custom, .wh_own, .wh_supplier_product, .wh_notes").val("");
    row.find(".wh-badge, .wh-note-btn").remove();
    var original = row.attr("data-cart-id") || "";
    if (original !== "" && $("#cart").find("tr[id='" + original + "']").length === 0) {
        row.attr("id", original);
    }// unless a second row of the same item already carries that name
    var shelf = row.attr("data-shop-qty");
    if (shelf !== undefined) {
        row.find("[name='avl_qty[]']").val(shelf);
        row.removeAttr("data-shop-qty");
    }// the shelf caps the quantity again
    var rowId = (row.attr("class") || "").match(/cartItem\d+/);
    if (rowId) {
        ttotal(rowId[0]);
    }
}//unmarkWarehouseRow

// The shop is not giving this line, so what is on its shelf does not limit it. The warehouse's
// own stock is checked when the goods are actually scanned out.
function liftShelfCap(row) {
    var stock = row.find("[name='avl_qty[]']");
    if (row.attr("data-shop-qty") === undefined) {
        row.attr("data-shop-qty", stock.val());
    }
    stock.val(99999);
}//liftShelfCap

// ---- the order details ----------------------------------------------------------------------

function warehouseRows() {
    return $("#cart tr[data-warehouse='1']");
}//warehouseRows

// Is the cart ready to be paid for? A warehouse line needs a customer to deliver to.
function warehouseReady() {
    if (warehouseRows().length === 0) {
        return true;
    }
    if ($.trim($("#wh_cust_name").val() || "") === "" || $.trim($("#wh_cust_phone").val() || "") === "") {
        return false;
    }
    if ($("#wh_deliver_to").val() === "1" && $.trim($("#wh_address").val() || "") === "") {
        return false;
    }
    return true;
}//warehouseReady

// Keeps the banner and the button label honest as rows come and go.
function warehouseChanged() {
    var count = warehouseRows().length;
    $("#wh_banner").toggle(count > 0);
    $("#wh_banner_count").text(count);
    $("#wh_banner_state").text(warehouseReady() ? "ready" : "needs the customer's details");
    $("#wh_open_details").toggleClass("btn-danger", !warehouseReady()).toggleClass("btn-outline-secondary", warehouseReady());
}//warehouseChanged

$(function () {
    // The pay buttons are handled by guipos.js. This listener runs in the capture phase, before
    // any of that, so a sale with nothing to deliver to cannot start at all.
    document.addEventListener("click", function (e) {
        var button = e.target.closest("#pay_cash, #btn_submit_invoice, #btn_submit");
        if (!button || warehouseReady()) {
            return;
        }
        e.stopImmediatePropagation();
        e.preventDefault();
        toastr.warning("Tell us who the warehouse is delivering to first.", "Warehouse order");
        $("#warehouseOrderModal").modal("show");
    }, true);

    $("body").on("click", "#wh_open_details", function () {
        $("#warehouseOrderModal").modal("show");
    });

    $("body").on("click", "#wh_save_details", function () {
        if (!warehouseReady()) {
            toastr.warning("The customer's name, phone and address are needed.", "Not saved");
            warehouseChanged();
            return;
        }
        // the order keeps the customer's own address; where it is being sent may differ
        if ($.trim($("#wh_cust_address").val() || "") === "") {
            $("#wh_cust_address").val($("#wh_address").val() || "");
        }
        $("#warehouseOrderModal").modal("hide");
        warehouseChanged();
        toastr.success("The warehouse will deliver to " + $("#wh_cust_name").val() + ".", "Noted");
    });

    // collecting at the shop needs no address
    $("body").on("change", "#wh_deliver_to", function () {
        $("#wh_address_row").toggle($(this).val() === "1");
        warehouseChanged();
    });

    // the customer picked in POS fills the delivery details once
    $("body").on("change", "#customer", function () {
        var name = $("#customer option:selected").text();
        if ($.trim($("#wh_cust_name").val() || "") === "" && $.trim(name) !== "") {
            $("#wh_cust_name").val($.trim(name.split(" - ")[0]));
            $("#wh_customer_id").val($(this).val());
        }
        warehouseChanged();
    });

    $("body").on("click", ".wh-note-btn", function () {
        var row = $(this).closest("tr");
        var notes = prompt("What does the customer want? (size, colour, firmness)", row.find(".wh_notes").val() || "");
        if (notes !== null) {
            row.find(".wh_notes").val(notes);
        }
    });

    // The checkbox in front of an item name. Ticking it hands a line the shop itself sells over
    // to the warehouse: the customer is still billed here, but the goods come from there.
    $("body").on("change", ".wh-toggle", function () {
        var box = $(this);
        var row = box.closest("tr");

        if (!box.prop("checked")) {
            unmarkWarehouseRow(row);
            warehouseChanged();
            return;
        }

        box.prop("disabled", true);
        $.get("../AJAX/guiPos/getproducts.php",
            { product_id: row.find("[name='item_id[]']").val(), warehouse_match: 1 },
            function (data) {
                var obj = typeof data === "string" ? JSON.parse(data) : data;
                box.prop("disabled", false);
                if (!obj || obj.error) {
                    box.prop("checked", false);
                    toastr.error((obj && obj.message) || "That item cannot be delivered from the warehouse.",
                        "Not ordered");
                    return;
                }
                markWarehouseRow(row, {
                    supplier_product: obj.supplier_product_id,
                    supplier_shop: obj.supplier_shop_id,
                    supplier_name: obj.supplier_name,
                    custom: false,
                    own: true
                });
                liftShelfCap(row);
                warehouseChanged();
            }
        ).fail(function () {
            box.prop("disabled", false).prop("checked", false);
            toastr.error("The warehouse could not be asked just now. Try again.", "Not ordered");
        });
    });

    $("body").on("click", "#wh_add_custom", function () {
        if (addCustomItem($("#wh_custom_name").val(), $("#wh_custom_price").val(), $("#wh_custom_notes").val())) {
            $("#wh_custom_name, #wh_custom_price, #wh_custom_notes").val("");
            $("#customItemModal").modal("hide");
        }
    });

    // a row removed may have been the last warehouse line
    $("body").on("click", ".removeid", function () {
        setTimeout(warehouseChanged, 50);
    });

    warehouseChanged();
});
