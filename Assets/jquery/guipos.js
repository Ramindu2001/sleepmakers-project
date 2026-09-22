function allCategory()
{
    var id=1;
    var selected=$("#main-cat").val();
    $.get("../AJAX/guiPos/getmaincat.php", {
        supplier_id : id
    }, function(data){
        // alert(data);
        const obj = JSON.parse(data);

        var html="<option value=''>Main Category</option>";
        obj.forEach(function(item) {
            html += `<option value='${item.CTID}' ${selected == item.CTID ? "selected" : ""}>${item.CategoryNo} - ${item.CategoryName} </option>`;
        });
        $("#main-cat").html(html)
    });
}

function formValidate() {
    var trcount = $("body #cart tr").length;
    var allow = true;

    if (trcount == 0) {
        alert("Cart Cannot Be Empty");
        return false;
    }

    if ($("body .rate-warning").length > 0 || $("body .rate-warnings").length > 0) {
        alert("Please clear the cart alerts");
        return false;
    }

    $("body .qty").each(function () {
        var Qtyvalue = $(this).val();
        if (Qtyvalue == "" || Qtyvalue == 0 || Qtyvalue == "0.00" || Qtyvalue == undefined || isNaN(Qtyvalue)) {
            allow = false;
            var element = $(this); // Store reference to the current element
            element.css("border-color", "red");
            setTimeout(function () {
                element.css("border-color", "#DFE5EF");
            }, 2000);
        }
    });
    
    return allow;
}

function focusBarcode()
{
    $("#barcode-search").focus();
}
function subcat()
{
    // var id=1;
    var maincat=$("#main-cat").val();
    if(maincat=="")
    {
        alert("Select A Main Category");
        $("#main-cat").focus();
    }
    else
    {
        var selected=$("#subcat").val();
        $.get("../AJAX/guiPos/getsubcat.php", {
            maincat : maincat
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);

            var html="<option value=''>Sub Category</option>";
            obj.forEach(function(item) {
                html += `<option value='${item.SCID}' ${selected == item.SCID ? "selected" : ""}>${item.SubCatNo} - ${item.SubCatName} </option>`;
            });
            $("#subcat").html(html)
        });
    }
}

// Image toggle state: 1 = images ON, 0 = images OFF
var posShowImages = 1;
var posLazyObserver = null;

function getPosShowImages() {
    var stored = localStorage.getItem('pos_show_images');
    if (stored !== null) posShowImages = parseInt(stored);
    return posShowImages;
}
function setPosShowImages(val) {
    posShowImages = val;
    localStorage.setItem('pos_show_images', val);
    var btn = $("#toggleImagesBtn");
    if (val == 1) {
        btn.html('<i class="ti ti-photo"></i>').attr('title','Images ON - Click to hide');
    } else {
        btn.html('<i class="ti ti-photo-off"></i>').attr('title','Images OFF - Click to show');
    }
}

function setupLazyLoad() {
    if (posLazyObserver) {
        posLazyObserver.disconnect();
        posLazyObserver = null;
    }
    var sentinelEl = document.querySelector('#product-list .lazy-load-sentinel');
    if (!sentinelEl) return;

    posLazyObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                var sentinel = $(entry.target);
                var offset = parseInt(sentinel.data('offset'));
                var limit  = parseInt(sentinel.data('limit'));
                var mode   = sentinel.data('mode') || '';
                var subcat = sentinel.data('subcat') || '';
                var value  = sentinel.data('value') || '';
                sentinel.remove();
                posLazyObserver.disconnect();
                posLazyObserver = null;

                var params = { offset: offset, limit: limit, show_images: getPosShowImages() };
                if (mode === 'allproducts') {
                    params.allproducts = 1;
                } else {
                    if (subcat) params.subcat = subcat;
                    if (value)  params.value  = value;
                }
                $.get("../AJAX/guiPos/getproducts.php", params, function(data) {
                    $("#product-list").append(data);
                    setupLazyLoad();
                });
            }
        });
    }, { root: document.querySelector('.product-list'), rootMargin: '150px' });

    posLazyObserver.observe(sentinelEl);
}

function loadProducts(params, clearFirst) {
    if (clearFirst !== false) {
        var html = `<div id="loader" class="loader"><i class="ti ti-refresh fs-4 rotate rots"></i></div>`;
        $("#product-list").html(html);
    }
    params = params || {};
    params.offset = params.offset || 0;
    params.limit  = params.limit  || 40;
    params.show_images = getPosShowImages();
    $.get("../AJAX/guiPos/getproducts.php", params, function(data) {
        $("#loader").remove();
        if (clearFirst !== false) {
            $("#product-list").html(data);
        } else {
            $("#product-list").append(data);
        }
        setupLazyLoad();
    });
}

function productloadsubcat()
{
    var subcat = $("#subcat").val();
    if (subcat && subcat !== "") {
        loadProducts({ subcat: subcat });
    } else {
        loadProducts({ allproducts: 1 });
    }
}
function allProduct()
{
    loadProducts({ allproducts: 1 });
}

function productsearch(values) {
    if (values != "") {
        loadProducts({ value: values });
    } else {
        productloadsubcat();
    }
}

function filtersubcat(subcat) {
    $("#product-list .product").stop(true, true).fadeOut(); // Hide all products
    
    var selectedItems = $("#product-list .subcat-" + subcat); // Get matching products
    
    if (selectedItems.length > 0) {
        selectedItems.stop(true, true).fadeIn(); // Show only matching subcategory
    }

    // Update the visible item count
    $("#procount").text(selectedItems.length);
}

function product(productid)
{
    
    $.get("../AJAX/guiPos/getproducts.php", { product_id: productid }, function (data) {
        const obj = JSON.parse(data);

        if (!obj.product || !obj.inventory || obj.inventory.length === 0) {
            alert("Error: No stock available.");
            return;
        }

        let inventories = obj.inventory;
        
        if (inventories.length > 1) {
            // Multiple inventory options: Show modal
            let inventoryOptions = "";
            inventories.forEach((inv, index) => {
            var TotalCurrentQty= inv.TotalCurrentQty;
                inventoryOptions += `
                    <tr>
                        <td class="text-center">${inv.SellingPrice}</td>
                        <td class="text-center"><button class="btn btn-primary select-inventory" data-index="${index}">Select</button></td>
                    </tr>
                `;
            });

            $("#inventoryTableBody").html(inventoryOptions);
            $("#inventoryModal").modal("show");

            // Handle selection
            $(".select-inventory").on("click", function () {
                let selectedIndex = $(this).data("index");
                addToCart(obj.product, inventories[selectedIndex], obj.discountType, obj.discount);
                $("#inventoryModal").modal("hide");
            });
        } else {
            // Only one inventory option: Add directly to cart
            addToCart(obj.product, inventories[0], obj.discountType, obj.discount);
        }
    });
}
function grandTotal()
{
    var total=0;
    var grosstotal=0;
    var originaltotal=0;
    var discount=0;
    var saleDiscount=0;
    var netTotal=0;
    var TotalQty=0;
    var itemTotal=0;
    var totalbeforediscount=0;
    $("body .qty").each(function(){
        var Qtyvalue = parseFloat($(this).val());
        if(isNaN(Qtyvalue)){ Qtyvalue = 0; }
        TotalQty=TotalQty+Qtyvalue;
    });
    $("body .original_total").each(function(){
        var original_totalvalue = $(this).val();
        originaltotal=originaltotal+parseFloat(original_totalvalue);
    });
    $("body .total_before_discount").each(function(){
        var total_before_discount = $(this).val();
        totalbeforediscount=totalbeforediscount+parseFloat(total_before_discount);
    });
    $("body .totals").each(function(){
        var totalsvalue = $(this).val();
        var discountValue = $(this).closest("tr").find(".discount").val();
        var discountTypeValue = $(this).closest("tr").find(".discountType").val();
        var rate_val=parseFloat($(this).closest("tr").find(".rate").val());
        var qty_val=parseFloat($(this).closest("tr").find(".qty").val());
        itemTotal=itemTotal+parseFloat(totalsvalue);
    });
    var invoiceDiscountValue = parseFloat($("#invoice_discount").val());
    if($("#invoice_discount").val()==="" || $("#invoice_discount").val()===undefined || $("#invoice_discount").val()===null || isNaN(invoiceDiscountValue) || invoiceDiscountValue === 0)
    {
        saleDiscount=0;
    }
    else
    {
        if($("#invoice_discount_type").val()==1)
            {
                saleDiscount=totalbeforediscount*invoiceDiscountValue/100;
            }
            else
            {
                saleDiscount=invoiceDiscountValue;
            }
    }
    $("body .discount").each(function(){
        var value = $(this).val();
        var value2= parseFloat($(this).val());
        var discountType=$(this).parent().parent().find(".discountType").val();
        var rate_val=parseFloat($(this).parent().parent().find(".rate").val());
        var qty_val=parseFloat($(this).parent().parent().find(".qty").val());
        if(isNaN(qty_val)){ qty_val = 0; }
        
        if(value=="" || value==null || value==undefined || value2==0)
        {
            value2=0;   
        }
        else
        {
            if(discountType==1)
            {
                tot=rate_val*qty_val;
                value2=tot*value2/100;   
                discount = discount+value2;                 
            }
            else
            {
                value2=value2 * qty_val;
                discount = discount+value2;
            }                    
        }

    });
    var returnamount=$("#returnamount").val() || 0;

    var TotalDiscount=discount+saleDiscount;
    netTotal=itemTotal-returnamount-saleDiscount;
    grosstotal=totalbeforediscount;
     

    $(".total-qty-invoice span b").text(TotalQty.toFixed(2));
    $(".total-gross-invoice span b").text("Rs. "+grosstotal.toFixed(2));
    $(".total-discount-invoice span b").text("Rs. "+TotalDiscount.toFixed(2));
    $(".total-net-invoice span b").text("Rs. "+netTotal.toFixed(2));    
    $("#netamount").val(netTotal.toFixed(2));
    $("#totQty").val(TotalQty.toFixed(2));
    $("#totalDiscount").val(TotalDiscount.toFixed(2));
    $("#totalDiscountLine").val(discount.toFixed(2));
    $("#grossTotal").val(grosstotal.toFixed(2));

    // Sync cart data to custome
    syncCustomerDisplay();

}
function loadHoldInvoices()
{
    var invoice = 1;
    $.ajax({
        url:'../AJAX/guiPos/getHoldInvoices.php',
            method:'post',
            data:{
                invoice:invoice
                },
            success:function(response)
            {            
                $("body #HoldInvoiceItems").html(response);                            
            }
        });
}
function ttotal(rowID)
{
    // $("#qty-"+rowID).val($("#qty-"+rowID).val().replace(/[^0-9.]/, ''));
    // $("#rate-"+rowID).val($("#rate-"+rowID).val().replace(/[^0-9.]/, ''));
    // $("#discount-"+rowID).val($("#discount-"+rowID).val().replace(/[^0-9.]/, ''));
    var is_minus=$("#is_minus").val();
    var is_under_cost=$("#is_under_cost").val();
    var avl_qty = $("#avl_qty-"+rowID);    
    var qty = $("#qty-"+rowID);    
    // var alertqty = $("#alertqty-"+rowID);    
    // var alertrate = $("#alertrate-"+rowID);    
    var rate = $("#rate-"+rowID);    
    var productType = $("#productType-"+rowID).val();
    var cost_total = $("#cost_total-"+rowID);
    var cost = $("#cost-"+rowID);
    var original_rate = $("#original_rate-"+rowID);    
    var total_before_discount = $("#total_before_discount-"+rowID);    
    var discountType = $("#discountType-"+rowID);    
    var discount = $("#discount-"+rowID);    
    var total = $("#total-"+rowID);    
    var original_total = $("#original_total-"+rowID); 
    var original_total = $("#original_total-"+rowID); 
    var qty_val=parseFloat(qty.val());   
    var avl_qty_val=parseFloat(avl_qty.val()); 
    var rate_val = parseFloat(rate.val());    
    var original_rate_val = parseFloat(original_rate.val());  
    var cost_total_val = parseFloat(cost_total.val());
    var cost_rate_val = parseFloat(cost.val());
    var discountType_val = discountType.val();
    var discount_val = 0;  
    var calc_qty = isNaN(qty_val) ? 0 : qty_val; // allow blank/decimal qty (e.g. 0.5, 0.288) without forcing "1"
    var totalBeforeDicsount=calc_qty*rate_val;
    total_before_discount.val(totalBeforeDicsount);
    if(is_under_cost==0) //check if the user is allowed to sell under cost
    {          
        var rateCheckTimeout;
        clearTimeout(rateCheckTimeout); 

        rateCheckTimeout = setTimeout(function() {
            if (rate_val < cost_rate_val) {
                rate.css("border", "2px solid red");                

                if (!$("#rate-warning"+rowID).length) {
                    rate.after(`<small id="rate-warning${rowID}" class="rate-warning" style="color: red;"> Rate cannot be less than cost rate (Rs.${cost_rate_val.toFixed(2)}) </small>`);
                }
            } else {
                rate.css("border", "");
                $("#rate-warning"+rowID).remove();
            }
        }, 500);
    }
    if(isNaN(discount.val()))
    {
        discount_val=0;   
    }
    else
    {
        discount_val=parseFloat(discount.val());
    }
    var discount2=0;
    var full_total=0;
    var original_full_total=0;
    if(discount_val==0 || discount_val=="" || discount_val==undefined || discount_val==null || isNaN(discount_val))
    {
        discount.val("0.00");
        discount2=0; 
    }
    else
    {
        if(discountType_val==1)
        {
            tot=rate_val*calc_qty;
            discount_val=tot*discount_val/100;      
            discount2=discount_val;              
        }
        else
        {
            discount2 = discount_val * calc_qty;
        }
    }
    if(qty_val > avl_qty_val && is_minus==0 && productType=="P")
    {
        alert("Quantity Cannot be greater than the available Quantity! \n Available Qty: -"+avl_qty_val)
        qty.val(avl_qty_val);
        full_total=(rate_val*avl_qty_val)-parseFloat(discount2);
        original_full_total=(original_rate_val*avl_qty_val);
        total.val(full_total.toFixed(2));
        original_total.val(original_full_total);
        qty.focus();
    }
    else
    {
        // alertqty.text("");
        if(qty.val()=="" || qty.val()==null || qty.val()==undefined || qty.val()==0  || qty.val()=="0.00")
        {
            // Do not force the quantity back to "1"; allow blank/decimal (e.g. 0.5, 0.288 for grams).
            full_total=(rate_val*calc_qty)-parseFloat(discount2);
            original_full_total=(original_rate_val*calc_qty);
            total.val(full_total.toFixed(2));
            original_total.val(original_full_total);
        }
        else
        {
            if(rate_val==0 || rate.val()=="" || rate.val()==null || rate.val()==undefined)
            {
                    // alertrate.text("Rate Cannot Be empty or 0");
                    rate.val("1.00");
                    full_total=(1*qty_val)-parseFloat(discount2);
                    original_full_total=(qty_val*original_rate_val);
                    total.val(full_total.toFixed(2));
                    original_total.val(original_full_total);
                    rate.focus();
                
            }
            else
            {
                // alertrate.text("");
                full_total=(rate_val*qty_val)-parseFloat(discount2);
                original_full_total=(qty_val*original_rate_val);
                total.val(full_total.toFixed(2));
                original_total.val(original_full_total);
            }
        }
    }
    cost_total_val=cost_rate_val*calc_qty;
    cost_total.val(cost_total_val);
    if(is_under_cost==0)
    {
        var rateCheckTimeout;
        clearTimeout(rateCheckTimeout); 

        rateCheckTimeout = setTimeout(function() {
            if (full_total < cost_total_val) {
                total.css("border", "2px solid red");                

                if (!$("#rate-warnings"+rowID).length) {
                    total.after(`<small id="rate-warnings${rowID}" class="rate-warnings" style="color: red;">Total Rate cannot be less than cost rate (Rs.${cost_total_val.toFixed(2)}) </small>`);
                }
                else
                {
                    $("#rate-warnings"+rowID).text(`Total Rate cannot be less than cost rate (Rs.${cost_total_val.toFixed(2)} )`)
                }
                
            } else {
                total.css("border", "");
                $("#rate-warnings"+rowID).remove();
            }
        }, 500);
    }
    grandTotal()
    // subtotal
}
// Function to add product to cart
function addToCart(product, inventory, discountType, discount) {
    var is_fixedprice=$("#is_fixedprice").val();
    fixedPrice="";
    if(is_fixedprice==1)
    {
        fixedPrice="readonly";
    }
    else
    {
        if(product.is_fixedPrice==1)
        {
            fixedPrice="readonly";
        }
        else
        {
            fixedPrice="";
        }
    }
    let subtotal = inventory.SellingPrice - discount;
    let PDID = product.PDID;
    let totalCurrentQty = inventory.TotalCurrentQty || 0;
    let sellingPrice = inventory.SellingPrice || 0;
    let PurchasePrice = inventory.PurchasePrice || 0;
    discount=parseFloat(discount).toFixed(2);
    var trCount=$("#cart").find("tr").length;
    var is_minus =$("#is_minus").val();
    trCount=trCount+1;
    subtotal = parseFloat(subtotal).toFixed(2);
    sellingPrice = parseFloat(sellingPrice).toFixed(2);
    let html = `
        <tr id="productid-${PDID}-${sellingPrice.replace(/\./g, '_')}" class="cartItem${trCount}" data-product="${PDID}">
            <td>
                <textarea class="form-control border-none p5 f10 Item_name" name="Item_name[]">${product.Barcode} - ${product.ItemName} - Rs.${sellingPrice}</textarea>
                <input type="hidden" name="item_id[]" id="item-cartItem${trCount}" value="${PDID}">
                <input type="hidden" name="productType[]" id="productType-cartItem${trCount}" value="${product.ItemType}">
            </td>
            <td>
                <div class="input-group">
                    <a href="javascript:void(0)" class="input-group-text increase-qty" onclick="ttotal('cartItem${trCount}')"><i class="ti ti-plus"></i></a>
                    <input type="text" name="qty[]" id="qty-cartItem${trCount}" class="form-control qty border-none text-center preDefault" value="1" onchange="ttotal('cartItem${trCount}')" onkeyup="ttotal('cartItem${trCount}')"  onkeydown="ttotal('cartItem${trCount}')" onkeypress="ttotal('cartItem${trCount}')">
                    <input type="hidden" name="avl_qty[]" id="avl_qty-cartItem${trCount}" value="${totalCurrentQty}">
                    <a href="javascript:void(0)" class="input-group-text decrease-qty" onclick="ttotal('cartItem${trCount}')"><i class="ti ti-minus"></i></a>
                </div>
            </td>
            <td>
                <input type="text" name="rate[]" class="rate form-control border-none text-center" value="${sellingPrice}" onchange="ttotal('cartItem${trCount}')"  onkeyup="ttotal('cartItem${trCount}')" onkeydown="ttotal('cartItem${trCount}')" onkeypress="ttotal('cartItem${trCount}')" id="rate-cartItem${trCount}" ${fixedPrice}>
                <input type="hidden" name="original_rate[]" id="original_rate-cartItem${trCount}" value="${sellingPrice}">
                <input type="hidden" name="cost[]" id="cost-cartItem${trCount}" value="${PurchasePrice}">
            </td>
            <td>
                <select name="discountType[]" id="discountType-cartItem${trCount}" class="discountType form-select border-none text-center preDefault" onchange="ttotal('cartItem${trCount}')"  onkeyup="ttotal('cartItem${trCount}')" onkeydown="ttotal('cartItem${trCount}')" onkeypress="ttotal('cartItem${trCount}')"  >
                    <option value="1" ${discountType == 1 ? "selected" : ""}>%</option>
                    <option value="2" ${discountType == 2 ? "selected" : ""}>Rs.</option>
                </select>
                <input type="hidden" name="Original_discounttype[]" id="Original_discounttype-cartItem${trCount}" value="${discountType}">
            </td>
            <td>
                <input type="text" name="discount[]" class="discount form-control border-none text-center preDefault" value="${discount}"  onchange="ttotal('cartItem${trCount}')"  onkeyup="ttotal('cartItem${trCount}')" onkeydown="ttotal('cartItem${trCount}')" onkeypress="ttotal('cartItem${trCount}')" id="discount-cartItem${trCount}">
                <input type="hidden" name="original_discount[]" id="original_discount-cartItem${trCount}" value="${discount}">
            </td>
            <td>
                <input type="text" name="totals[]" class="totals form-control border-none text-center preDefault" value="${subtotal}"  onchange="ttotal('cartItem${trCount}')"  onkeyup="ttotal('cartItem${trCount}')" onkeydown="ttotal('cartItem${trCount}')" onkeypress="ttotal('cartItem${trCount}')" id="total-cartItem${trCount}">
                <input type="hidden" name="original_total[]" class="original_total" id="original_total-cartItem${trCount}" value="${sellingPrice}">
                <input type="hidden" name="total_before_discount[]" class="total_before_discount" id="total_before_discount-cartItem${trCount}" value="${sellingPrice}">
                <input type="hidden" name="cost_total[]" class="cost_total" id="cost_total-cartItem${trCount}" value="${PurchasePrice}">
            </td>
            <td>
                <button class="btn btn-danger removeid"><i class="ti ti-trash"></i></button>
            </td>
        </tr>
    `;

    var existingRow = $("body #cart").find(`#productid-${PDID}-${sellingPrice.replace(/\./g, '_')}`);
    // alert(existingRow.length);
    if (existingRow.length > 0) {
        var item_qty = existingRow.find("[name='qty[]']");
        var totalQtyInput = existingRow.find("[name='avl_qty[]']");
        var productType = existingRow.find("[name='productType[]']");

        var currentQty = parseFloat(item_qty.val()) || 0;
        var availableQty = parseFloat(totalQtyInput.val()) || 0;
        var newQty = currentQty + 1;

        if (newQty > availableQty && is_minus==0 && productType=="P") {
            alert("Quantity cannot be more than the available quantity.");
        } else {
            item_qty.val(newQty);
            item_qty.trigger("change");
        }
    } else {
        $("#cart").append(html);
        $("body #qty-cartItem"+trCount).trigger("change");
    }
    existingRow.css("background","#00AD46");
    existingRow.css("color","white");
    existingRow.find("input").css("color","white");
    existingRow.find("text").css("color","white");
    setTimeout(function(){
        existingRow.css("background","");  
        existingRow.css("color","");
        existingRow.find("input").css("color","");
        existingRow.find("text").css("color","");
    }, 1000);
    grandTotal();
}
function showProductSelectionModal(products) {
    let modalContent = ``;
    // alert(products);
    products.forEach((product, index) => {
        modalContent += `<tr>
            <td>
                ${product.ItemName} 
            </td>
            <td>
                <button class="select-product btn btn-primary" data-index="${product.PDID}">Select</button>
            </td>
        </tr>`;
    });

    modalContent += ``;

    $("#productTableBody").html(modalContent);
    $("#productModal").modal("toggle");

    // Handle selection
    $(".select-product").click(function () {
        let index = $(this).data("index");
        product(index);
        $("#productModal").modal("hide");
    });

    // Close modal
    $("#closeModal").click(function () {
        $("#productModal").remove();
    });
}
function validatecustomer()
{
    var valid=true
    $("#customer_modal .required").each(function(){
        if($(this).val()=="")
        {
            valid =false
            $(this).parent().find("#alrt").css("display","block");
            // console.log($(this).attr("id"));
            
        }
    })

    return valid
}
function setPredefinedCustomer(customerId, customerName) 
{
    // Create a new option with the predefined value
    var option = new Option(customerName, customerId, true, true);

    // Append the option to Select2 and trigger the change event
    $("#search-customers").append(option).trigger('change');
}
function setPredefinedSalesman(salesmanID, salesmanName) 
{
    // Create a new option with the predefined value
    var option = new Option(salesmanName, salesmanID, true, true);

    // Append the option to Select2 and trigger the change event
    $("#search-salesmen").append(option).trigger('change');
}
function validateItem()
{
    var valid=true
    $("#product_modal .required").each(function(){
        if($(this).val()=="")
        {
            valid =false
            $(this).parent().find("#alrt").css("display","block");
            // console.log($(this).attr("id"));
            
        }
    })

    return valid
}
function clearCart()
{
    $("#cart").html("");
    $("#HIID").val("");
    $("#invoice_discount").val("");
    $("#invoice_discount_type").val("1");
    $("body").find("[name='InvoiceNo']").remove();
    grandTotal();
    $("#PaymentModal").modal("hide");
    $(".paying").not(":first").remove();
    $("#Amount").val("0.00");
    setPredefinedCustomer("1","Common Customer");
    setPredefinedSalesman("1","SM_000001 - Default Salesman");
    var invoice =1;
    $.ajax({
        url:'../AJAX/guiPos/invoiceNo.php',
            method:'post',
            data:{
                invoice:invoice
                },
            success:function(response)
            {
                // console.log(response);
                $("#invoice-no").text(response);                            
            }
        });
        
        $("#return_no").val(null); // Clear the selection
        $("#return_no").select2("destroy"); 
        initialReturn();
        $("#return_id").val("");
        $("#returnamount").val("");
        $("#returnSpan").html("<b>Rs. 0.00</b>")
        grandTotal();
}
function refreshStockBadges(productIds)
{
    if (!productIds || productIds.length === 0) return;
    $.get('../AJAX/guiPos/getStockBadges.php', { product_ids: productIds }, function(data) {
        try {
            var stockMap = (typeof data === 'string') ? JSON.parse(data) : data;
            $('#product-list .product').each(function() {
                var pid = $(this).find('[id="productid"]').val();
                if (pid !== undefined && stockMap.hasOwnProperty(pid)) {
                    $(this).find('.qty-badge').text(stockMap[pid]);
                }
            });
        } catch(e) {}
    });
}
function checkCart()
{
    var trcount=$("body #cart tr").length;
    if(trcount==0)
    {
        alert("Cart Cannot Be Empty");
        focusBarcode();
        return false;
    }
    else
    {
        return true;
    }
}
function checkMinus()
{
    var netAmount = $("#netamount").val();
    if(netAmount >= 0)
    {
        return true;
    }
    else 
    {
        return false;
    }
}
function checkCart2()
{
    var trcount=$("body #cart tr").length;
    if(trcount==0)
    {
        focusBarcode();
        return true;
    }
    else
    {
        alert("Please Clear The Existing Invoice");
        return false;
    }
}
function payment()
{
    var totalPayment=0;
    var netTot=$("#netamount").val();
    $("body .Amount").each(function(){
        var thiss=$(this).val();
        if(thiss==undefined || thiss=="" || isNaN(thiss))
        {
            thiss=0;
        }
        totalPayment=totalPayment+parseFloat(thiss);
    });
    var difference = netTot - totalPayment;
    var credit=0;
    var change=0;
    if(difference > 0)
    {
        credit=difference;
    }
    else
    {
        difference=totalPayment-netTot;
        change=difference;
    }
    $(".totPaymentSpan").text(totalPayment.toFixed(2));
    $(".creditSpan").text(credit.toFixed(2));
    $(".changeReturnSpan").text(change.toFixed(2));
}
function multipay()
{
    var totQty=$("#totQty").val();
    var grossAmount=$("#grossTotal").val();
    var totDisc=$("#totalDiscount").val();
    var netTot=$("#netamount").val();
    var returnTot=$("#returnamount").val() || 0;
    totQty=parseFloat(totQty);
    grossAmount=parseFloat(grossAmount);
    totDisc=parseFloat(totDisc);
    netTot=parseFloat(netTot);
    if(isNaN(returnTot) || returnTot=="" || returnTot==0)
    {
        returnTot=0;
    }
    else
    {
        returnTot=parseFloat(returnTot);
    }
    // alert(parseFloat(totQty)+"\n"+parseFloat(grossAmount)+"\n"+parseFloat(totDisc)+"\n"+parseFloat(netTot))
    $(".totItemSpan").text(totQty.toFixed(2));
    $(".totSpan").text(grossAmount.toFixed(2));
    $(".totDiscountSpan").text(totDisc.toFixed(2));
    $(".totReturnSpan").text(returnTot.toFixed(2));
    $(".netTotSpan").text(netTot.toFixed(2));
    $("#PaymentModal").modal("toggle");
    $("#Amount").val(netTot.toFixed(2));
    payment();
}
function addProduct()
{
    $("#btn_update_product").css('display', 'none');
    $("#btn_save_product").css('display', 'none');
    $(".image").css('display', 'none');
    $(".service").css('display', 'none');
    $("#btn_save_product2").css('display', 'block');
    $(".text-alrt").css('display', 'inline-block');
    $("#product_modal").modal("toggle");
    $("#cmb_category").focus();
}
function holdInvoiceAddtoCart(HoldID) {
    console.log("HoldID: "+HoldID);
    var is_fixedprice=$("#is_fixedprice").val();
    fixedPrice="";
    
    $.ajax({
        url: '../AJAX/guiPos/add_to_cart.php',
        method: 'post',
        data: { HoldID: HoldID },
        dataType: 'json',
        success: function(response) {
            console.log("response: "+response);
            
            
            if (response.success) {
                var customerId = response.HeaderData[0]["CTID"];
                var customername = response.HeaderData[0]["CustName"];
                var CustContact = response.HeaderData[0]["CustContact"];
                var SalesmanID = response.HeaderData[0]["SLID"];
                var SalesmanNo = response.HeaderData[0]["SalesmanNo"];
                var SalesmansName = response.HeaderData[0]["SalesmansName"];
                var SalesmansContact = response.HeaderData[0]["SalesmansContact"];
                var HPercentDiscount = response.HeaderData[0]["PercentDiscount"];
                var HFixedDiscount = response.HeaderData[0]["FixedDiscount"];
                var hdiscountType = HPercentDiscount != "0.00" ? 1 : 2;
                var hdiscount = HPercentDiscount != "0.00" ? HPercentDiscount : HFixedDiscount;
                $("#invoice_discount_type").val(hdiscountType).trigger("change");
                $("#invoice_discount").val(hdiscount).trigger("change");
                var HIID = response.HeaderData[0]["HIID"];
                $("#HIID").val(HIID);

                setPredefinedCustomer(customerId, customername + " - " + CustContact);
                setPredefinedSalesman(SalesmanID, SalesmanNo + " - " + SalesmansName + " - " + SalesmansContact);

                var trCount = $("#cart").find("tr").length + 1;
                var html = "";

                response.DetailData.forEach(function(item) {
                    var PDID = item.PDID ;
                    var sellingPrice = item.SellingPrice || 0;
                    var Item_Name = item.Item_Name || "";
                    var ItemType = item.ItemType || 0;
                    var qty = item.SellQty || 0;
                    var totalQty = item.totalQty || 0;
                    var UnitPrice = item.UnitPrice || 0;
                    var origi_UnitPrice = item.origi_UnitPrice || 0;
                    var PurchasePrice = item.PurchasePrice || 0;
                    var SellAmount = item.SellAmount || 0;
                    var disc_type = item.disc_type || 1;
                    var PercentDiscount = item.PercentDiscount || 0;
                    var DirectDiscount = item.DirectDiscount || 0;
                    var discount = disc_type == 1 ? PercentDiscount : DirectDiscount;
                    var SellAmount = item.SellAmount || 0;
                    var subtotal = item.SoldAmount || 0;
                    var cost_total=PercentDiscount * qty;
                    var total_before_discount=UnitPrice*qty;
                    if(is_fixedprice==1)
                    {
                        fixedPrice="readonly";
                    }
                    else
                    {
                        if(item.is_fixedPrice==1)
                        {
                            fixedPrice="readonly";
                        }
                        else
                        {
                            fixedPrice="";
                        }
                    }

                    html += `
                        <tr id="productid-${PDID}-${sellingPrice.toString().replace(/\./g, '_')}" class="cartItem${trCount}" data-product="${PDID}">
                            <td>
                                <textarea class="form-control border-none p5 f10 Item_name" name="Item_name[]">${Item_Name} - Rs.${UnitPrice}</textarea>
                                <input type="hidden" name="item_id[]" id="item-cartItem${trCount}" value="${PDID}">
                                <input type="hidden" name="productType[]" id="productType-cartItem${trCount}" value="${ItemType}">
                            </td>
                            <td>
                                <div class="input-group">
                                    <a href="javascript:void(0)" class="input-group-text increase-qty" onclick="ttotal('cartItem${trCount}')"><i class="ti ti-plus"></i></a>
                                    <input type="text" name="qty[]" id="qty-cartItem${trCount}" class="form-control qty border-none text-center preDefault" value="${qty}" onchange="ttotal('cartItem${trCount}')" onkeyup="ttotal('cartItem${trCount}')"  onkeydown="ttotal('cartItem${trCount}')" onkeypress="ttotal('cartItem${trCount}')">
                                    <input type="hidden" name="avl_qty[]" id="avl_qty-cartItem${trCount}" value="${totalQty}">
                                    <a href="javascript:void(0)" class="input-group-text decrease-qty" onclick="ttotal('cartItem${trCount}')"><i class="ti ti-minus"></i></a>
                                </div>
                            </td>
                            <td>
                                <input type="text" name="rate[]" class="rate form-control border-none text-center" value="${UnitPrice}" onchange="ttotal('cartItem${trCount}')"  onkeyup="ttotal('cartItem${trCount}')" onkeydown="ttotal('cartItem${trCount}')" onkeypress="ttotal('cartItem${trCount}')" id="rate-cartItem${trCount}" ${fixedPrice}>
                                <input type="hidden" name="original_rate[]" id="original_rate-cartItem${trCount}" value="${origi_UnitPrice}">
                                <input type="hidden" name="cost[]" id="cost-cartItem${trCount}" value="${PurchasePrice}">
                            </td>
                            <td>
                                <select name="discountType[]" id="discountType-cartItem${trCount}" class="discountType form-select border-none text-center preDefault" onchange="ttotal('cartItem${trCount}')"  onkeyup="ttotal('cartItem${trCount}')" onkeydown="ttotal('cartItem${trCount}')" onkeypress="ttotal('cartItem${trCount}')"  >
                                    <option value="1" ${disc_type == 1 ? "selected" : ""}>%</option>
                                    <option value="2" ${disc_type == 2 ? "selected" : ""}>Rs.</option>
                                </select>
                                <input type="hidden" name="Original_discounttype[]" id="Original_discounttype-cartItem${trCount}" value="${disc_type}">
                            </td>
                            <td>
                                <input type="text" name="discount[]" class="discount form-control border-none text-center preDefault" value="${discount}"  onchange="ttotal('cartItem${trCount}')"  onkeyup="ttotal('cartItem${trCount}')" onkeydown="ttotal('cartItem${trCount}')" onkeypress="ttotal('cartItem${trCount}')" id="discount-cartItem${trCount}">
                                <input type="hidden" name="original_discount[]" id="original_discount-cartItem${trCount}" value="${discount}">
                            </td>
                            <td>
                                <input type="text" name="totals[]" class="totals form-control border-none text-center preDefault" value="${subtotal}"  onchange="ttotal('cartItem${trCount}')"  onkeyup="ttotal('cartItem${trCount}')" onkeydown="ttotal('cartItem${trCount}')" onkeypress="ttotal('cartItem${trCount}')" id="total-cartItem${trCount}">
                                <input type="hidden" name="original_total[]" class="original_total" id="original_total-cartItem${trCount}" value="${SellAmount}">
                                <input type="hidden" name="total_before_discount[]" class="total_before_discount" id="total_before_discount-cartItem${trCount}" value="${total_before_discount}">
                                <input type="hidden" name="cost_total[]" class="cost_total" id="cost_total-cartItem${trCount}" value="${cost_total}">
                            </td>
                            <td>
                                <button class="btn btn-danger removeid"><i class="ti ti-trash"></i></button>
                            </td>
                        </tr>`;
                        trCount++;
                });

                $("#cart").append(html);
                grandTotal();
                $("#HoldListModal").modal("hide");
                toastr.success("Invoice Recalled to Cart", "Success");
            } else {
                console.log("Error:", response.message);
            }
        },
        error: function(xhr, status, error) {
            console.log("AJAX Error:", status, error);
            console.log("Response:", xhr.responseText);
            toastr.error("An error occurred while processing.", "Error");
        }
    });
}
function holdInvoiceDelet(HoldID) {
    console.log("HoldID: "+HoldID);
    
    $.ajax({
        url: '../AJAX/guiPos/add_to_cart.php',
        method: 'post',
        data: { HoldIDs: HoldID },
        dataType: 'json',
        success: function(response) {
            console.log("response: "+response);
            
            
            if (response.success) {
                toastr.success("Hold Invoice Deleted Successfully", "Success");
            } else {
                console.log("Error:", response.message);
            }
        },
        error: function(xhr, status, error) {
            console.log("AJAX Error:", status, error);
            console.log("Response:", xhr.responseText);
            toastr.error("An error occurred while processing.", "Error");
        }
    });
}

var _isProcessingSale = false;
function payCash()
{
    if(_isProcessingSale) { toastr.warning("Please wait, sale is being processed...", "Processing"); return; }
    _isProcessingSale = true;
    $("#pay_cash, #btn_submit_invoice, #btn_submit, #invoiceHold").addClass("disabled").css("pointer-events","none");
    var invoices=$("#invoices").val();
    if (typeof CKEDITOR !== 'undefined') {
        for (instance in CKEDITOR.instances) {
            CKEDITOR.instances[instance].updateElement();
        }
    }
    var _soldIds = [];
    $("#cart tr[data-product]").each(function() { var p=$(this).data('product'); if(p) _soldIds.push(String(p)); });
    $.ajax({
        url: '../Controller/guiPosController.php?cash=1',
        method: 'post',
        data: $("#order_form").serialize(),
        dataType: 'json',  // Ensure that jQuery automatically parses the JSON
        success: function(response) {
            // Show success messages if any
            if (response["Success"]) {
                if (Array.isArray(response["Success"])) {
                    response["Success"].forEach(function(successMsg) {
                        toastr.success(successMsg, "Success");
                    });
                } else {
                    toastr.success(response["Success"], "Success");
                }
                if (response["Success"].includes("Invoice Created Successfully")) 
                    {
                        clearCart(); // Call the function to clear the cart
                        refreshStockBadges(_soldIds);
                        // Clear CKEditor fields
                        if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances['details']) {
                            CKEDITOR.instances['details'].setData('');
                        }
                        if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances['internalRemark']) {
                            CKEDITOR.instances['internalRemark'].setData('');
                        }

                        // Also clear the original textareas (if CKEditor is disabled at any point)
                        $("#details").val('');
                        $("#internalRemark").val('');
                    }
            }
            
            if(response["invoiceID"])
            {
                var domain= window.location.hostname;
                //an A4 invoice needs a wider print window than the 80mm receipt
                var strWindowFeatures = $("#invoice_format").val() == "a4"
                    ? "location=yes,height=700,width=900,scrollbars=yes,status=yes"
                    : "location=yes,height=700,width=520,scrollbars=yes,status=yes";
                var URL = "../Receipts/"+invoices+"?invoice="+response["invoiceID"]+"&print=1";
                var win = window.open(URL, "_blank", strWindowFeatures);
            }
            // Show error messages if any
            if (response["Error"]) {
                if (Array.isArray(response["Error"])) {
                    response["Error"].forEach(function(errorMsg) {
                        toastr.error(errorMsg, "Error");
                    });
                } else {
                    toastr.error(response["Error"], "Error");
                }
            }
        },
        error: function(xhr, status, error) {
            console.log("AJAX Error:", status, error);
            console.log("Response:", xhr.responseText);
            toastr.error("An error occurred while processing.", "Error");
        },
        complete: function() {
            _isProcessingSale = false;
            $("#pay_cash, #btn_submit_invoice, #btn_submit, #invoiceHold").removeClass("disabled").css("pointer-events","");
        }
    });
}

function submitinvoice(invoice)
{
    if(_isProcessingSale) { toastr.warning("Please wait, sale is being processed...", "Processing"); return; }
    _isProcessingSale = true;
    $("#pay_cash, #btn_submit_invoice, #btn_submit, #invoiceHold").addClass("disabled").css("pointer-events","none");
    var invoices=$("#invoices").val();
    if (typeof CKEDITOR !== 'undefined') {
        for (instance in CKEDITOR.instances) {
            CKEDITOR.instances[instance].updateElement();
        }
    }
    var _soldIds2 = [];
    $("#cart tr[data-product]").each(function() { var p=$(this).data('product'); if(p) _soldIds2.push(String(p)); });
    $.ajax({
        url: '../Controller/guiPosController.php?'+invoice+'=1',
        method: 'post',
        data: $("#order_form").serialize(),
        dataType: 'json',  // Ensure that jQuery automatically parses the JSON
        success: function(response) {
            // Show success messages if any
            if (response["Success"]) {
                if (Array.isArray(response["Success"])) {
                    response["Success"].forEach(function(successMsg) {
                        toastr.success(successMsg, "Success");
                    });
                } else {
                    toastr.success(response["Success"], "Success");
                }
                if (response["Success"].includes("Invoice Created Successfully")) 
                    {
                        // Clear CKEditor fields
                        if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances['details']) {
                            CKEDITOR.instances['details'].setData('');
                        }
                        if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances['internalRemark']) {
                            CKEDITOR.instances['internalRemark'].setData('');
                        }

                        // Also clear the original textareas (if CKEditor is disabled at any point)
                        $("#details").val('');
                        $("#internalRemark").val('');
                    }
                    clearCart();
                    if (response["Success"] && response["Success"].includes("Invoice Created Successfully")) {
                        refreshStockBadges(_soldIds2);
                    }
            }
            
            if(response["invoiceID"])
            {
                var domain= window.location.hostname;
                //an A4 invoice needs a wider print window than the 80mm receipt
                var strWindowFeatures = $("#invoice_format").val() == "a4"
                    ? "location=yes,height=700,width=900,scrollbars=yes,status=yes"
                    : "location=yes,height=700,width=520,scrollbars=yes,status=yes";
                var URL = "../Receipts/"+invoices+"?invoice="+response["invoiceID"]+"&print=1";
                var win = window.open(URL, "_blank", strWindowFeatures);
            }
            // Show error messages if any
            if (response["Error"]) {
                if (Array.isArray(response["Error"])) {
                    response["Error"].forEach(function(errorMsg) {
                        toastr.error(errorMsg, "Error");
                    });
                } else {
                    toastr.error(response["Error"], "Error");
                }
            }
        },
        error: function(xhr, status, error) {
            console.log("AJAX Error:", status, error);
            console.log("Response:", xhr.responseText);
            toastr.error("An error occurred while processing.", "Error");
        },
        complete: function() {
            _isProcessingSale = false;
            $("#pay_cash, #btn_submit_invoice, #btn_submit, #invoiceHold").removeClass("disabled").css("pointer-events","");
        }
    });
}

function invoiceHold()
{
    if(_isProcessingSale) { toastr.warning("Please wait, processing...", "Processing"); return; }
    _isProcessingSale = true;
    $("#pay_cash, #btn_submit_invoice, #btn_submit, #invoiceHold").addClass("disabled").css("pointer-events","none");
    if (typeof CKEDITOR !== 'undefined') {
        for (instance in CKEDITOR.instances) {
            CKEDITOR.instances[instance].updateElement();
        }
    }
    $.ajax({
        url: '../Controller/guiPosController.php?invoiceHold=1',
        method: 'post',
        data: $("#order_form").serialize(),
        dataType: 'json',  // Ensure that jQuery automatically parses the JSON
        success: function(response) {             
            // Show success messages if any
            if (response["Success"]) {
                if (Array.isArray(response["Success"])) {
                    response["Success"].forEach(function(successMsg) {
                        toastr.success(successMsg, "Success");
                    });
                } else {
                    toastr.success(response["Success"], "Success");
                }
            }
            if (response["Success"].includes("Hold Invoice Created Successfully") || response["Success"].includes("Hold Invoice Updated Successfully")) {
                clearCart(); // Call the function to clear the cart
            }
            // Show error messages if any
            if (response["Error"]) {
                if (Array.isArray(response["Error"])) {
                    response["Error"].forEach(function(errorMsg) {
                        toastr.error(errorMsg, "Error");
                    });
                } else {
                    toastr.error(response["Error"], "Error");
                }
            }
        },
        error: function(xhr, status, error) {
            console.log("AJAX Error:", status, error);
            console.log("Response:", xhr.responseText);
            toastr.error("An error occurred while processing.", "Error");
        },
        complete: function() {
            _isProcessingSale = false;
            $("#pay_cash, #btn_submit_invoice, #btn_submit, #invoiceHold").removeClass("disabled").css("pointer-events","");
        }
    });
}

function refresh_main_cat()
{
    let icon = $("#refresh-main-cat").find(".ti"); // Find the <i> tag inside clicked <a>
    icon.addClass("rotate"); // Add rotation class
    allCategory();

    setTimeout(function(){
        icon.removeClass("rotate"); // Remove class after 1 second
    }, 1000);
}
function refresh_sub_cat()
{
    let icon = $("#refresh-sub-cat").find(".ti"); // Find the <i> tag inside clicked <a>
        icon.addClass("rotate"); // Add rotation class
        subcat();
        setTimeout(function(){
            icon.removeClass("rotate"); // Remove class after 1 second
        }, 1000);
}
function refresh_pro()
{
    let icon = $("#refresh-pro").find(".ti"); // Find the <i> tag inside clicked <a>
    icon.addClass("rotate"); // Add rotation class
    productloadsubcat();
    setTimeout(function(){
        icon.removeClass("rotate"); // Remove class after 1 second
    }, 1000);
}
function checkPayment() {
    let isValid = true; // Flag to track validation status
    var netamount = parseFloat($("#netamount").val());
    var customerid = $("#customerid").val();
    var totalPaid = 0;
    // Check Amount fields
    $("body .Amount").each(function(){
        totalPaid+=parseFloat($(this).val());
        var thiss = $(this).val().trim();
        if (thiss === undefined || thiss === "" || isNaN(thiss)) {
            $(this).css("border-color", "#ff0000").focus();
            isValid = false;
        } else {
            $(this).css("border-color", ""); // Reset border if valid
        }
    });

    if (!isValid) return false; // Stop execution if Amount is invalid

    // Check paymentType fields
    $("body .paymentType").each(function(){
        var thiss = $(this).val().trim();
        if (thiss === undefined || thiss === "") {
            $(this).css("border-color", "#ff0000").focus();
            isValid = false;
        } else {
            $(this).css("border-color", ""); // Reset border if valid
        }
    });
    if(netamount > totalPaid)
    {
        if(customerid==1)
        {
            isValid = false;
            alert("Common Customer Cannot Have Credit");
        }
        else
        {
            if(confirm("Are you sure you want to go credit?"))
            {
                
            }
            else
            {
                isValid = false;
            }
        }
        
    }
    return isValid;
}
function initialReturn()
{
    $("#return_no").select2({
        ajax:{
            url: '../AJAX/WholeSaleInvoice/returnsales.php',
            dataType: 'json',
            delay: 250,
            data: function(params){
                var query = {
                    search: params.term,
                    type: 'item_search'
                };
                return query;
            },
            processResults: function(data){
                return {
                    results: data
                }
            }
        },
        cache: true,
        placeholder: 'Return No',
        minimumInputLength: 1,
        width: '90%',
    });//get return search\
}
$(document).ready(function(){
    $('[data-toggle="tooltip"]').tooltip();
    setInterval(function(){
        loadHoldInvoices();
    }, 1000);    
    initialReturn();
    $("#returnamount").change(function(){
        grandTotal();
    })
    $("#return_no").on("change",function(){
        $("#return_id").val("");
        $("#returnamount").val("");
        var return_id=$(this).val();
        $("#return_id").val(return_id);
        $.ajax({
            url:'../AJAX/WholeSaleInvoice/returninvoice.php',
                method:'post',
                data:{
                    return_id:return_id
                    },
                success:function(response)
                {
                    const obj = JSON.parse(response);
                    var amount=obj[0]["amount"];
                    var id=obj[0]["id"];
                    $("#return_id").val(id);
                    $("#returnamount").val(amount).trigger('change');
                    $("#returnSpan").html("<b>Rs. "+amount.toFixed(2)+"</b>")
                    grandTotal();

                }
            });
        grandTotal();
    });
    $(document).on('click', '.Add_to_cart', function () {
        var HoldID = $(this).data("holdid");
        // console.log("HoldID:", HoldID);
        if(checkCart2()==true)
        {
            holdInvoiceAddtoCart(HoldID);
        }

    });
    $(document).on('click', '.hold_delete', function () {
        var HoldID = $(this).data("holdid");
        // console.log("HoldID:", HoldID);
        
        holdInvoiceDelet(HoldID);

    });

    $("body").on("click",".remove-payment",function(){
        $(this).parent().parent().parent().parent().remove();
        payment();
    });
    // Shortcut keys start
    $(document).keydown(function (e) {
        if (e.ctrlKey && e.key === "1") { 
            e.preventDefault(); // Prevent default browser action (if any)
            $("#search-salesmen").focus();
        }
        if (e.ctrlKey && e.key === "2") { 
            e.preventDefault(); // Prevent default browser action (if any)
            $("#search-customers").focus();
        }
        if (e.ctrlKey && e.key === "3") { 
            e.preventDefault(); // Prevent default browser action (if any)
            $("#customer_modal").modal("toggle");
        }
        if (e.ctrlKey && e.key === "4") { 
            e.preventDefault(); // Prevent default browser action (if any)
            focusBarcode();
        }
        if (e.ctrlKey && e.key === "5") { 
            e.preventDefault(); // Prevent default browser action (if any)
            addProduct();
        }
        if (e.ctrlKey && e.key === "6") { 
            e.preventDefault(); // Prevent default browser action (if any)
            if(checkCart()==true)
            {
                invoiceHold();
            }
            
        }
        if (e.ctrlKey && e.key === "7") { 
            e.preventDefault(); // Prevent default browser action (if any)
            if(checkCart()==true)
            {
                multipay();
            }
        }
        if (e.ctrlKey && e.key === "8") { 
            e.preventDefault(); // Prevent default browser action (if any)
            if(formValidate()==true)
            {
                if(checkMinus()==true)
                {
                    payCash();
                }
                else
                {
                    alert("Net Total Cannot Be In Minus Value")
                }
                
            }
        }
        if (e.ctrlKey && e.key === "9") { 
            e.preventDefault(); // Prevent default browser action (if any)
            if(checkCart()==true)
            {
                if(confirm("Are you sure you want to clear the cart?"))
                {
                    clearCart();
                }
            }
        }
        if (e.ctrlKey && e.key === "0") { 
            e.preventDefault(); // Prevent default browser action (if any)
            $("#main-cat").focus();
        }
        if(e.keyCode == 27)
        {
            refresh_main_cat();
            // escape press
        }
        if (e.key === "F1") { 
            e.preventDefault(); // Prevents browser default (e.g., renaming files in Windows)
            $("#subcat").focus();
        }
        if (e.key === "F2") { 
            e.preventDefault(); // Prevents browser default (e.g., renaming files in Windows)
            refresh_sub_cat();
        }
        if (e.key === "F3") { 
            e.preventDefault(); // Prevents browser default (e.g., renaming files in Windows)
            $("#search-product").focus();
        }
        if (e.key === "F4") { 
            e.preventDefault(); // Prevents browser default (e.g., renaming files in Windows)
            refresh_pro();
        }
        if (e.key === "F5") { 
            e.preventDefault(); // Prevents browser default (e.g., renaming files in Windows)
            $("body .product:first").focus();
        }
        if (e.key === "F6") { 
            e.preventDefault(); // Prevents browser default (e.g., renaming files in Windows)
            $("body .Item_name:first").focus();
        }
    });
    // Shortcut keys end

    $("#Multi-pay").click(function(){
        if(checkCart()==true)
        {
            multipay();
        }
        
    })
    $("#clearCart").click(function(){
        if(confirm("Are you sure you want to clear the cart?"))
        {
            clearCart();
        }
        
    });
    var invoice =1;
    $.ajax({
        url:'../AJAX/guiPos/invoiceNo.php',
            method:'post',
            data:{
                invoice:invoice
                },
            success:function(response)
            {
                // console.log(response);
                $("#invoice-no").text(response);                            
            }
        });
    
    $(".tc").click(function(){
        $("#termConditionModal").modal("toggle");
    })
    
    focusBarcode();
    allCategory();
    $("#showHoldList").click(function(){
        $("#HoldListModal").modal("toggle");
    });
    $("#showShortcutList").click(function(){
        $("#ShortcutListModal").modal("toggle");
    });
    $("body").on("click", ".removeid", function(){
        $(this).closest("tr").remove();
        grandTotal();
    });
    $("#add-customer").click(function(){
        $("#customer_modal").modal("toggle");
    });
    $("#add-item").click(function(){
        addProduct();
    })
    $("#btn_save_product2").click(function(){
        var cmb_category=$("#cmb_category").val();
        var cmb_subcategory=$("#cmb_subcategory").val();
        var prod_name=$("#prod_name").val();
        var barcode=$("#barcode").val();
        var second_name=$("#second_name").val();
        var prod_description=$("#prod_description").val();
        var prod_purchase_price=$("#prod_purchase_price").val();
        var prod_selling_price=$("#prod_selling_price").val();
        var prod_carton_qty=$("#prod_carton_qty").val();
        var cmb_purchase_unit=$("#cmb_purchase_unit").val();
        var conversion_rate=$("#conversion_rate").val();
        var cmb_selling_unit=$("#cmb_selling_unit").val();
        if(validateItem()==true)
        {
            $.ajax({
                url:'../Controller/productController.php',
                    method:'post',
                    data:{
                        cmb_category:cmb_category,
                        cmb_subcategory:cmb_subcategory,
                        prod_name:prod_name,
                        barcode:barcode,
                        second_name:second_name,
                        prod_description:prod_description,
                        prod_purchase_price:prod_purchase_price,
                        prod_selling_price:prod_selling_price,
                        prod_carton_qty:prod_carton_qty,
                        cmb_purchase_unit:cmb_purchase_unit,
                        conversion_rate:conversion_rate,
                        cmb_selling_unit:cmb_selling_unit,
                        btn_save_product:1
                        },
                    success:function(response)
                    {
                        $("#cmb_category").val("");
                        $("#cmb_subcategory").val("");
                        $("#prod_name").val("");
                        $("#barcode").val("");
                        $("#second_name").val("");
                        $("#prod_description").val("");
                        $("#prod_purchase_price").val("");
                        $("#prod_selling_price").val("");
                        $("#prod_carton_qty").val("");
                        $("#conversion_rate").val(1);  
                        $("#product_modal").modal("hide")                  
                    }
                });
        }
        else
        {
            alert("Please Fill The Required Fields");
        }
        
    });
    $("#btn_save_customer").click(function(){
        var cust_name=$("#cust_name").val();
        var cust_contact=$("#cust_contact").val();
        var cust_address=$("#cust_address").val();
        var cust_dob=$("#cust_dob").val();
        var gender=$("#gender").val();
        if(validatecustomer()==true)
        {
            $.ajax({
                url:'../Controller/CustomerController.php',
                    method:'post',
                    data:{
                        CusName:cust_name,
                        Contact:cust_contact,
                        Address:cust_address,
                        DOB:cust_dob,
                        gender:gender,
                        CustomerStat:1,
                        WS_btn_save_customer:1
                        },
                    success:function(response)
                    {
                        // console.log(response);
                        $("#print").html(response);                        
                        $("#cust_name").val("");
                        $("#cust_contact").val("");
                        $("#cust_address").val(""); 
                        $("#customer_modal").modal("hide")                  
                    }
                });
        }
        else
        {
            alert("Please Fill The Required Fields");
        }
        
    })
    $("#barcode-search").focus();
    // allProduct();
    if ($(window).width() >= 1099) {
        $('.left-sidebar').css("margin-left","-270px");
    }    
    $('#headerCollapse2').css("display","block");
    $('#headerCollapse3').css("display","none");
    $('.body-wrapper').css("margin-left","0");
    $("#side-closes").css("display","block");
    $(".app-header").css("width","100%");
    $("#refresh-main-cat").click(function(event){
        event.preventDefault(); // Prevent default action of <a> tag
        refresh_main_cat();
        
    });
    $("#refresh-sub-cat").click(function(event){
        event.preventDefault(); // Prevent default action of <a> tag
        refresh_sub_cat();
    });
    $("#refresh-pro").click(function(event){
        event.preventDefault(); // Prevent default action of <a> tag
        refresh_pro();
        
    });
    $("#main-cat").change(function(){
        subcat();
    });
    $("body").on("click",".increase-qty",function(){
        var is_minus=$("#is_minus").val();
        var existingRow = $(this).closest("tr");
        var itemRow = existingRow;
        var item_qty = itemRow.find("[name='qty[]']");
        var totalQtyInput = itemRow.find("[name='avl_qty[]']");
        var productType = itemRow.find("[name='productType[]']");

        var currentQty = parseFloat(item_qty.val()) || 0;
        var availableQty = parseFloat(totalQtyInput.val()) || 0;
        var newQty = currentQty + 1;

        if ((newQty > availableQty) && (productType=="P") && is_minus==0) {
            alert("Quantity cannot be more than the available quantity.");
        } else {
            item_qty.val(newQty);
            item_qty.trigger("change");
        }
    });
    $("body").on("click",".decrease-qty",function(){
        var existingRow = $(this).closest("tr");
        var itemRow = existingRow;
        var item_qty = itemRow.find("[name='qty[]']");
        var totalQtyInput = itemRow.find("[name='avl_qty[]']");

        var currentQty = parseFloat(item_qty.val()) || 0;
        var availableQty = parseFloat(totalQtyInput.val()) || 0;
        var newQty = currentQty - 1;

        if (newQty == 0 || newQty < 0) {
            alert("Quantity cannot be zero.");
            item_qty.val(1);
        } else {
            item_qty.val(newQty);
            item_qty.trigger("change");
        }
    });
    $("body").on("click", ".product", function () {
        var productid = $(this).find("#productid").val();
        product(productid);
    });
    
    var barcodeSearchTimeout;
    var isProcessingBarcodeSearch = false;
    
    $("body").on("keyup", ".barcode-search", function (event) {
        var barcodevalue = $(this).val();
        
        clearTimeout(barcodeSearchTimeout);
        
        if (event.which === 13 || event.keyCode === 13) {
            event.preventDefault();
            
            if(isProcessingBarcodeSearch) {
                return;
            }
            
            if (barcodevalue.trim() !== "") {
                isProcessingBarcodeSearch = true;
                
                // Send the scanned value exactly as a string (leading zeros preserved).
                // The server tries the exact value plus leading-zero variants so it
                // matches whether or not the scanner/database keeps the leading 0.
                var processedBarcode = barcodevalue.trim();
                
                $.get("../AJAX/guiPos/getbarcodevalue.php", { barcodevalue: processedBarcode }, function (data) {
                    const obj = JSON.parse(data);
                    let products = obj;
        
                    if (products.length > 0) {
                        if (products.length === 1) {
                            var productid=products[0]["PDID"];
                            $(".barcode-search").val("");
                            product(productid);
                        } else {
                            $(".barcode-search").val("");
                            showProductSelectionModal(products);
                        }
                    }
                    isProcessingBarcodeSearch = false;
                }).fail(function() {
                    isProcessingBarcodeSearch = false;
                });
            } else {
                isProcessingBarcodeSearch = false;
            }
        }
    });
    
    
    $("#subcat").change(function(){
        var subcatid = $(this).val();
        if(subcatid!="")
        {
            productloadsubcat();
        }
        else
        {
            $("#product-list").html("");
        }
    });

    $("#search-product").on("keyup", function() {
        var values = $(this).val();
        productsearch(values);
        });

        $("#search-salesmen").select2({
            ajax:{
                url: '../AJAX/guiPos/getSalesman.php',
                dataType: 'json',
                delay: 250,
                data: function(params){
                    // console.log(params);
                    
                    var query = {
                        search: params.term,
                        type: 'item_search'
                    };
                    return query;
                },
                processResults: function(data){
                    return {
                        results: data
                    }
                }
            },
            cache: true,
            placeholder: 'Search for Salesman',
            minimumInputLength: 1,
            width: '70%',
        });//get customer search\
    
        $("#search-salesmen").change(function(){
            var saleaman_id = $(this).val();
                $.get("../AJAX/guiPos/getSalesmanDetail.php", {
                    saleaman_id: saleaman_id
                }, function(data){
                    const obj = JSON.parse(data);
                    var SalesmansContact = obj[0]['SalesmansContact'];
                    var SalesmansName = obj[0]['SalesmansName'];
                    var SalesmanNo = obj[0]['SalesmanNo'];
                    var SLID = obj[0]['SLID'];
                    var txt = "<small>Salesman -</small><b> "+SalesmanNo+ " - " + SalesmansName +" - "+ SalesmansContact +"</b><br>";
                    cust_credit=0;
                    txt+="";
                    $("#sales_man").html(txt);
                    $("#salesmanid").val(SLID);
                });//get customer detail 
        });//cmb changes
        
        $("#search-customers").select2({
            ajax:{
                url: '../AJAX/guiPos/getCustomers.php',
                dataType: 'json',
                delay: 250,
                data: function(params){
                    // console.log(params);
                    
                    var query = {
                        search: params.term,
                        type: 'item_search'
                    };
                    return query;
                },
                processResults: function(data){
                    return {
                        results: data
                    }
                }
            },
            cache: true,
            placeholder: 'Search for Customer',
            minimumInputLength: 1,
            width: '70%',
        });//get customer search
        
        $("#search-customers").change(function(){
            var cust_id = $(this).val();
            var cust_text = $(this).text();
            var excessFlag = $("#excessFlag").val();
            if(cust_id=="Add")
            {
              $("#customer_modal").modal("toggle"); 
            }
            else
            {
                $.get("../AJAX/guiPos/getCustDetail.php", {
                    cust_id: cust_id
                }, function(data){
                    const obj = JSON.parse(data);
                    var cust_contact = obj[0]['cust_contact'];
                    var cust_name = obj[0]['cust_name'];
                    var cust_credit = obj[0]['cust_credit'];
                    var CTID = obj[0]['CTID'];
                    var txt = "<small>Customer -</small><b> "+cust_name+ " - " + cust_contact +"</b><br>";
                    $("#excessamount").val("0.00");
                    if(cust_credit<0)
                    {
                        cust_credit=Math.abs(cust_credit);
                        txt+=" - <b>" + cust_credit +"</b><br>";
                        if(excessFlag==1)
                        {
                            $("#use_exccess").css("display","block");
                        }
                        else
                        {
                            $("#use_exccess").css("display","none");                                
                        }                            
                    }
                    else if(cust_credit > 0)
                    {
                        txt+="<b> Credit Balance - "+cust_credit+"</b><br>";
                    }
                    else
                    {
                        cust_credit=0;
                        txt+="";
                        $("#use_exccess").css("display","none");
                    }
                    txt+="<a href='../Public/customerProfile.php?cus_id="+CTID+"' target='_blank'> View Customer Profile</a><br>";
                    $("#p_customer").html(txt);
                    $("#customer_excess_amount").val(cust_credit);
                    $("#customerid").val(cust_id);
                    // grandTotal();
                });//get customer detail 
            }
               
        });//cmb changes
        $("body").on("change", ".paymentType", function () {
            let selectedValues = [];
            let currentValue = $(this).val(); // Get the current dropdown value
        
            $(".paymentType").not(this).each(function () { // Exclude 'this' element
                let value = $(this).val();
                if (value) {
                    selectedValues.push(value);
                }
            });
        
            // Check if the current selection already exists in other dropdowns
            if (selectedValues.includes(currentValue)) {
                alert("Duplicate payment type selected!");
                $(this).val(""); // Reset only the duplicate selection
            }
        });
        $("#pay_cash").click(function() {
            if (formValidate()==true) {
                if(checkMinus()==true)
                    {
                        payCash();
                    }
                    else
                    {
                        alert("Net Total Cannot Be In Minus Value")
                    }
            } else {
                // Handle form validation failure (if needed)
            }
        });
        $("#btn_submit_invoice").click(function() {
            if (formValidate()==true) {
                if(checkPayment())
                {
                    if(checkMinus()==true)
                    {
                        submitinvoice("btn_submit_invoice");
                    }
                    else
                    {
                        alert("Net Total Cannot Be In Minus Value")
                    }
                }
                
            } else {
                // Handle form validation failure (if needed)
            }
        });
        $("#btn_submit").click(function() {
            if (formValidate()==true) {
                if(checkPayment())
                {
                    if(checkMinus()==true)
                    {
                        submitinvoice("btn_submit");
                    }
                    else
                    {
                        alert("Net Total Cannot Be In Minus Value")
                    }
                    
                }
            } else {
                // Handle form validation failure (if needed)
            }
        });
        $("#invoiceHold").click(function() {
            // alert("")
            if (formValidate()==true) {
                invoiceHold();
            } 
            else {
                // Handle form validation failure (if needed)
            }
        });
        
})

document.addEventListener('DOMContentLoaded', () => {
    const inputs = document.getElementsByClassName('preDefault');
        // Loop through each input with the class 'qty'
        for (let i = 0; i < inputs.length; i++) {
            inputs[i].addEventListener('keypress', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault(); // Prevent form submission
                // document.getElementById("nextInput").focus();
            }
            });
        }
    });

// ==================== Customer Display ====================
var posChannel = null;
var customerDisplayWindow = null;

// Initialize BroadcastChannel
try {
    posChannel = new BroadcastChannel('pos_customer_display');
    posChannel.onmessage = function(event) {
        if (event.data.type === 'pong') {
            // Customer display is alive
            $("#customerDisplayBtn").removeClass("btn-outline-secondary").addClass("btn-outline-success");
        }
    };
} catch(e) {
    console.log("BroadcastChannel not supported, using localStorage fallback.");
}

// Sync cart data to customer display
function syncCustomerDisplay() {
    var items = [];
    $("#cart tr").each(function() {
        var name = $(this).find(".Item_name").val() || "";
        var qty = parseFloat($(this).find(".qty").val()) || 0;
        var rate = parseFloat($(this).find(".rate").val()) || 0;
        var total = parseFloat($(this).find(".totals").val()) || 0;
        items.push({ name: name, qty: qty, rate: rate, total: total });
    });

    var cartData = {
        type: 'cart_update',
        items: items,
        totals: {
            totalQty: $("#totQty").val() || "0.00",
            grossTotal: $("#grossTotal").val() || "0.00",
            totalDiscount: $("#totalDiscount").val() || "0.00",
            netTotal: $("#netamount").val() || "0.00"
        }
    };

    if (posChannel) {
        try { posChannel.postMessage(cartData); } catch(e) {}
    }
    // localStorage fallback
    try { localStorage.setItem('pos_cart_data', JSON.stringify(cartData)); } catch(e) {}
}

// Notify customer display to clear
function syncCustomerDisplayClear() {
    var clearData = { type: 'cart_clear' };
    if (posChannel) {
        try { posChannel.postMessage(clearData); } catch(e) {}
    }
    try { localStorage.setItem('pos_cart_data', JSON.stringify(clearData)); } catch(e) {}
}

// Open customer display window on second screen
function openCustomerDisplay() {
    if (customerDisplayWindow && !customerDisplayWindow.closed) {
        customerDisplayWindow.focus();
        return;
    }

    var displayUrl = '../Public/customer-display.php';

    // Try Window Management API for auto second screen detection
    if ('getScreenDetails' in window) {
        window.getScreenDetails().then(function(screenDetails) {
            var screens = screenDetails.screens;
            var secondScreen = null;

            // Find a screen that is not the primary/current screen
            for (var i = 0; i < screens.length; i++) {
                if (!screens[i].isPrimary) {
                    secondScreen = screens[i];
                    break;
                }
            }

            if (secondScreen) {
                // Open on second screen in fullscreen
                var features = 'left=' + secondScreen.availLeft +
                    ',top=' + secondScreen.availTop +
                    ',width=' + secondScreen.availWidth +
                    ',height=' + secondScreen.availHeight +
                    ',fullscreen=yes,menubar=no,toolbar=no,location=no,status=no,scrollbars=no';
                customerDisplayWindow = window.open(displayUrl, 'CustomerDisplay', features);
            } else {
                // No second screen, open normally
                customerDisplayWindow = window.open(displayUrl, 'CustomerDisplay', 'width=1024,height=768,menubar=no,toolbar=no,location=no,status=no');
            }

            updateCustomerDisplayBtnStatus();
            // Sync current cart immediately
            setTimeout(function() { syncCustomerDisplay(); }, 1000);
        }).catch(function() {
            // Permission denied or error, open normally
            customerDisplayWindow = window.open(displayUrl, 'CustomerDisplay', 'width=1024,height=768,menubar=no,toolbar=no,location=no,status=no');
            updateCustomerDisplayBtnStatus();
            setTimeout(function() { syncCustomerDisplay(); }, 1000);
        });
    } else {
        // Fallback: use screen properties to detect dual monitor
        var leftPos = window.screen.availWidth;
        var topPos = 0;

        if (window.screen.availWidth < window.screen.width * 2) {
            // Likely single monitor, open as popup
            customerDisplayWindow = window.open(displayUrl, 'CustomerDisplay', 'width=1024,height=768,menubar=no,toolbar=no,location=no,status=no');
        } else {
            // Dual monitor detected via extended desktop
            var features = 'left=' + leftPos + ',top=' + topPos +
                ',width=' + window.screen.availWidth + ',height=' + window.screen.availHeight +
                ',fullscreen=yes,menubar=no,toolbar=no,location=no,status=no,scrollbars=no';
            customerDisplayWindow = window.open(displayUrl, 'CustomerDisplay', features);
        }

        updateCustomerDisplayBtnStatus();
        setTimeout(function() { syncCustomerDisplay(); }, 1000);
    }
}

function updateCustomerDisplayBtnStatus() {
    if (customerDisplayWindow && !customerDisplayWindow.closed) {
        $("#customerDisplayBtn i").removeClass("ti-device-tv").addClass("ti-device-tv");
        $("#customerDisplayBtn").attr("title", "Customer Display (Active)");
    }
}

// Auto-detect second monitor and open customer display automatically
function autoDetectCustomerDisplay() {
    if ('getScreenDetails' in window) {
        window.getScreenDetails().then(function(screenDetails) {
            var screens = screenDetails.screens;
            var hasSecondScreen = false;

            for (var i = 0; i < screens.length; i++) {
                if (!screens[i].isPrimary) {
                    hasSecondScreen = true;
                    break;
                }
            }

            if (hasSecondScreen) {
                openCustomerDisplay();
            }
        }).catch(function(err) {
            console.log("Screen detection permission denied:", err);
        });
    }
}

// Monitor customer display window status
setInterval(function() {
    if (customerDisplayWindow && customerDisplayWindow.closed) {
        customerDisplayWindow = null;
        $("#customerDisplayBtn").attr("title", "Customer Display");
    }
}, 2000);

// Auto-detect on page load
$(document).ready(function() {
    setTimeout(function() {
        autoDetectCustomerDisplay();
    }, 2000);
    // Restore image toggle state
    setPosShowImages(getPosShowImages());
});

// Toggle product images ON/OFF
function toggleProductImages() {
    var current = getPosShowImages();
    setPosShowImages(current == 1 ? 0 : 1);
    // Reload product list with new image setting
    var value = $("#search-product").val();
    if (value && value != "") {
        loadProducts({ value: value });
    } else {
        productloadsubcat();
    }
}

// Toggle fullscreen
function toggleFullscreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().then(function() {
            $("#fullscreenBtn i").removeClass("ti-maximize").addClass("ti-minimize");
            $("#fullscreenBtn").attr("title", "Exit Fullscreen");
        }).catch(function() {});
    } else {
        document.exitFullscreen().then(function() {
            $("#fullscreenBtn i").removeClass("ti-minimize").addClass("ti-maximize");
            $("#fullscreenBtn").attr("title", "Toggle Fullscreen");
        }).catch(function() {});
    }
}

// Monitor customer display window status
setInterval(function() {
    if (customerDisplayWindow && customerDisplayWindow.closed) {
        customerDisplayWindow = null;
        $("#customerDisplayBtn").attr("title", "Customer Display");
    }
}, 2000);

// Auto-detect on page load
$(document).ready(function() {
    setTimeout(function() {
        autoDetectCustomerDisplay();
    }, 2000);
    // Restore image toggle state
    setPosShowImages(getPosShowImages());
});