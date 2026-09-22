function validateForm()
{
    var isVerified = false;
    $("#shopAdd .required").each(function(){
        if($(this).val()=="" || $(this).val()==0)
        {
            isVerified=true;
            
            $(this).css("border-color","#ff0000");
            $(this).parent().find("#require-span").css("display","block");
        }
        else
        {
            $(this).css("border-color","#dfe5ef");
            $(this).parent().find("#require-span").css("display","none");
        }
        console.log("Something");
    });
    if (isVerified) {
        alert('Please fill the required filds.');
        $(".required").focus();
        return false;
    }
    return true;
}
function checkImageExistsimg_shop_logo(imageUrl, defaultImageUrl) {
    $.ajax({
        url: imageUrl,
        type: 'HEAD',
        success: function () {
            $('#img_shop_logo').attr('src', imageUrl);
        },
        error: function () {
            $('#img_shop_logo').attr('src', defaultImageUrl);
        }
    });
}
function checkImageExistsimg_receipt_logoo(imageUrl, defaultImageUrl) {
    $.ajax({
        url: imageUrl,
        type: 'HEAD',
        success: function () {
            $('#img_receipt_logo').attr('src', imageUrl);
        },
        error: function () {
            $('#img_receipt_logo').attr('src', defaultImageUrl);
        }
    });
}

$(document).ready(function(){
    $('#shopAdd').submit(function() {
        return validateForm();
    });
    $('.required').on('keyup', function() {
        $(this).css('border-color', '#dfe5ef');
        $(this).parent().find("#require-span").css("display","none");
    });
    $('.required').on('change', function() {
        $(this).css('border-color', '#dfe5ef');
        $(this).parent().find("#require-span").css("display","none");
    });
    $('#btn_Add_Shop_modal').click(function(){      
        $("#myModalLabel").text("Add Shop") 
        $("#Shop_modal").modal('toggle');

        $("#btn_update_shop").css('display', 'none');
        $("#btn_update_shop").attr('disabled', true);

        $("#btn_save_shop").css('display', 'block');
        $("#btn_save_shop").attr('disabled', false);

        //show default values
        $("#ShopNo").val('SH_000000');
        $("#ShopName").val("");
        $("#cmb_stock_type").val('0');
        $("#cmb_company").val('');
        $(".form-check-input").attr('checked', false);

        var url = "../Assets/Images/synnex_logo.png";
        $("#img_shop_logo").attr("src", url);
        $("#img_receipt_logo").attr("src", url);

    });//open modal Shop module add new

    $("#close_Shop_modal").click(function(){
        $("#Shop_modal").modal('hide');
    });//close shop modal

    $("#shop_logo").change(function(event){
        var size=this.files[0].size;
        if (size>=500000)
        {
            $("#logo_danger").fadeIn();
            $("#logo_success").fadeOut();
            $(this).css("border-color","red");
            $("#btn_save_shop").attr('disabled', true);
        }//greater than 5mb
        else
        {
            $("#logo_danger").fadeOut();
            $("#logo_success").fadeIn();
            $(this).css("border-color","lime");
            $("#btn_save_shop").attr('disabled', false);

            var url = URL.createObjectURL(event.target.files[0]);
            $("#img_shop_logo").attr("src", url);
        }//less than 5mb
    });//shop image change

    $("#shop_receipt").change(function(event){
        var size=this.files[0].size;
        if (size>=500000)
        {
            $("#receipt_danger").fadeIn();
            $("#receipt_success").fadeOut();
            $(this).css("border-color","red");
            $("#btn_save_shop").attr('disabled', true);
        }//greater than 5 mb
        else
        {
            $("#receipt_danger").fadeOut();
            $("#receipt_success").fadeIn();
            $(this).css("border-color","lime");
            $("#btn_save_shop").attr('disabled', false);

            var url = URL.createObjectURL(event.target.files[0]);
            $("#img_receipt_logo").attr("src", url);
        }//less than 5 mb
    });//receipt image change

    $("#tbl_shop").on('mousedown', 'tr', function(){
        var currentRow = $(this).closest('tr');
        var row_id = currentRow.find('td').eq(0).html();

        var comapnyID =  currentRow.find('td').eq(6).html();
        var shopNo =  currentRow.find('td').eq(7).html();
        var shopName =  currentRow.find('td').eq(8).html();
        var stockType =  currentRow.find('td').eq(9).html();
        var shopStat =  currentRow.find('td').eq(10).html();

        var inventory = currentRow.find('td').eq(11).html();
        var minus = currentRow.find('td').eq(12).html();
        var expire = currentRow.find('td').eq(13).html();
        var fixed = currentRow.find('td').eq(14).html();
        var variation = currentRow.find('td').eq(15).html();
        var secondLan = currentRow.find('td').eq(16).html();
        var labelPrice = currentRow.find('td').eq(17).html();
        var carton = currentRow.find('td').eq(18).html();
        var warranty = currentRow.find('td').eq(19).html();
        var category = currentRow.find('td').eq(20).html();
        
        var suppliers = currentRow.find('td').eq(21).html();
        var service = currentRow.find('td').eq(22).html();
        var salesman = currentRow.find('td').eq(23).html();
        var expenses = currentRow.find('td').eq(24).html();
        var customers = currentRow.find('td').eq(25).html();
        var quotations = currentRow.find('td').eq(26).html();
        var promotions = currentRow.find('td').eq(27).html();
        var racks = currentRow.find('td').eq(30).html();
        var credit = currentRow.find('td').eq(31).html();

        var shopLogo = currentRow.find('td').eq(28).html();
        var receiptLogo = currentRow.find('td').eq(29).html();
        var AddressLineOne = currentRow.find('td').eq(32).html();
        var AddressLineTwo = currentRow.find('td').eq(33).html();
        var City = currentRow.find('td').eq(34).html();
        var PhoneNumber = currentRow.find('td').eq(35).html();

        var Wholesale = currentRow.find('td').eq(36).html();
        var Retail = currentRow.find('td').eq(37).html();
        
        $("#btn_shop_" + row_id).click(function(){
            $("#myModalLabel").text("Edit Shop")
            $("#Shop_modal").modal('toggle');

            $("#hide_Shop_id").val(row_id);
            $("#AddressLineOne").val(AddressLineOne);
            $("#AddressLineTwo").val(AddressLineTwo);
            $("#City").val(City);
            $("#PhoneNumber").val(PhoneNumber);

            $("#btn_update_shop").css('display', 'block');
            $("#btn_update_shop").attr('disabled', false);

            $("#btn_save_shop").css('display', 'none');
            $("#btn_save_shop").attr('disabled', true);

            //comapny
            $("#cmb_company").val(comapnyID);
            $("#ShopNo").val(shopNo);
            
            $("#ShopName").val(shopName);
            $("#cmb_stock_type").val(stockType);

            //stock stat
            if(shopStat == 1)
            {
                $("#ShopStatus").attr('checked', true);
            }
            else
            {
                $("#ShopStatus").attr('checked', false);
            }

            Wholesale == 1 ? $("#chk_wholesale").attr('checked', true) : $("#chk_wholesale").attr('checked', false);
            Retail == 1 ? $("#chk_retail").attr('checked', true) : $("#chk_retail").attr('checked', false);

            inventory == 1 ? $("#chk_inventory").attr('checked', true) : $("#chk_inventory").attr('checked', false);
            minus == 1 ? $("#chk_minus").attr('checked', true) : $("#chk_minus").attr('checked', false);
            expire == 1 ? $("#chk_expire").attr('checked', true) : $("#chk_expire").attr('checked', false);
            fixed == 1 ? $("#chk_fixed_price").attr('checked', true) : $("#chk_fixed_price").attr('checked', false);
            variation == 1 ? $("#chk_variations").attr('checked', true) : $("#chk_variations").attr('checked', false);

            secondLan == 1 ? $("#chk_second_language").attr('checked', true) : $("#chk_second_language").attr('checked', false);
            labelPrice == 1 ? $("#chk_label_price").attr('checked', true) : $("#chk_label_price").attr('checked', false);
            carton == 1 ? $("#chk_carton").attr('checked', true) : $("#chk_carton").attr('checked', false);
            warranty == 1 ? $("#chk_warranty").attr('checked', true) : $("#chk_warranty").attr('checked', false);
            category == 1 ? $("#chk_category").attr('checked', true) : $("#chk_category").attr('checked', false);

            suppliers == 1 ? $("#chk_supplier").attr('checked', true) : $("#chk_supplier").attr('checked', false);
            service == 1 ? $("#chk_service").attr('checked', true) : $("#chk_service").attr('checked', false);
            salesman == 1 ? $("#chk_salesman").attr('checked', true) : $("#chk_salesman").attr('checked', false);
            expenses == 1 ? $("#chk_expenses").attr('checked', true) : $("#chk_expenses").attr('checked', false);
            customers == 1 ? $("#chk_customers").attr('checked', true) : $("#chk_customers").attr('checked', false);

            quotations == 1 ? $("#chk_quotation").attr('checked', true) : $("#chk_quotation").attr('checked', false);
            promotions == 1 ? $("#chk_promotion").attr('checked', true) : $("#chk_promotion").attr('checked', false);
            racks == 1 ? $("#chk_racks").attr('checked', true) : $("#chk_racks").attr('checked', false);
            credit == 1 ? $("#chk_credit").attr('checked', true) : $("#chk_credit").attr('checked', false);
            customers == 1 ? $("#chk_customers").attr('checked', true) : $("#chk_customers").attr('checked', false);

            //show shop image
            if(shopLogo == '')
            {
                var url = "../Assets/Images/synnex_logo.png";
                $("#img_shop_logo").attr("src", url);
            }//no shop logo
            else
            {
                var url = "../Assets/Images/shop_images/" + shopLogo;
                $("#img_shop_logo").attr("src", url);
            }//has shop logo

            //show receipt image
            if(receiptLogo == '')
            {
                var url = "../Assets/Images/synnex_logo.png";
                $("#img_receipt_logo").attr("src", url);
            }//no shop logo
            else
            {
                var url = "../Assets/Images/shop_images/" + receiptLogo;
                $("#img_receipt_logo").attr("src", url);
            }//has shop logo

        });//button clicck
    });//mouse down

    $("#tbl_shop").on('click', '.btn_edit_shop', function(){
        let row = $(this).closest('tr');
	    let id = row.data('id');

        $.get("../AJAX/Shops/getOneShop.php", {
            edit_shop_id: id
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);

            $("#btn_update_shop").css('display', 'block');
            $("#btn_update_shop").attr('disabled', false);

            $("#btn_save_shop").css('display', 'none');
            $("#btn_save_shop").attr('disabled', true);

            console.log(obj);

            var comapnyID =  obj[0]['Company_CMID'];
            var shopNo =  obj[0]['ShopNo'];
            var shopName =  obj[0]['ShopName'];
            var stockType =  obj[0]['StockTypes_STID'];
            var shopStat =  obj[0]['ShopStat'];

            var inventory = obj[0]['is_inventory'];
            var minus = obj[0]['is_minus'];
            var expire = obj[0]['is_expire'];
            var fixed = obj[0]['is_fixedprice'];
            var variation = obj[0]['is_variation'];
            var secondLan = obj[0]['is_secondlan'];
            var labelPrice = obj[0]['is_labelprice'];
            var carton = obj[0]['is_carton'];
            var warranty = obj[0]['is_warranty'];
            var category = obj[0]['is_category'];

            var suppliers = obj[0]['is_suppliers'];
            var service = obj[0]['is_service'];
            var salesman = obj[0]['is_salesman'];
            var expenses = obj[0]['is_expenses'];
            var customers = obj[0]['is_customers'];
            var quotations = obj[0]['is_quotation'];
            var promotions = obj[0]['is_promotions'];
            var promotions = obj[0]['is_promotions'];
            var racks = obj[0]['is_racks'];
            var credit = obj[0]['is_credit'];
            var prescription = obj[0]['is_prescription'];
            var counter = obj[0]['is_counter'];
            var excessAmount = obj[0]['is_excessAmount'];
            var batchNo = obj[0]['is_BatchNo'];

            var shopLogo = obj[0]['ShopLogo'];
            var receiptLogo = obj[0]['ReceiptLogo'];
            var AddressLineOne = obj[0]['AddressLineOne'];
            var AddressLineTwo = obj[0]['AddressLineTwo'];
            var City = obj[0]['City'];
            var PhoneNumber = obj[0]['PhoneNumber'];
            var EmailAddress = obj[0]['emailAddress'];

            var Wholesale = obj[0]['WholesaleShop'];
            var Retail = obj[0]['RetailShop'];
            var is_under_cos = obj[0]['is_under_cos'];
            var invoice_print = obj[0]['invoice_print'];
            var a4_invoice = obj[0]['is_a4invoice'];

            $("#hide_Shop_id").val(id);
            $("#AddressLineOne").val(AddressLineOne);
            $("#AddressLineTwo").val(AddressLineTwo);
            $("#City").val(City);
            $("#EmailAddress").val(EmailAddress);
            $("#PhoneNumber").val(PhoneNumber);

            //comapny
            $("#cmb_company").val(comapnyID);
            $("#ShopNo").val(shopNo);
            
            $("#ShopName").val(shopName);
            $("#cmb_stock_type").val(stockType);

            shopStat == 1 ? $("#ShopStatus").attr('checked', true) : $("#ShopStatus").attr('checked', false);

            Wholesale == 1 ? $("#chk_wholesale").attr('checked', true) : $("#chk_wholesale").attr('checked', false);
            Retail == 1 ? $("#chk_retail").attr('checked', true) : $("#chk_retail").attr('checked', false);

            inventory == 1 ? $("#chk_inventory").attr('checked', true) : $("#chk_inventory").attr('checked', false);
            minus == 1 ? $("#chk_minus").attr('checked', true) : $("#chk_minus").attr('checked', false);
            expire == 1 ? $("#chk_expire").attr('checked', true) : $("#chk_expire").attr('checked', false);
            fixed == 1 ? $("#chk_fixed_price").attr('checked', true) : $("#chk_fixed_price").attr('checked', false);
            variation == 1 ? $("#chk_variations").attr('checked', true) : $("#chk_variations").attr('checked', false);

            secondLan == 1 ? $("#chk_second_language").attr('checked', true) : $("#chk_second_language").attr('checked', false);
            labelPrice == 1 ? $("#chk_label_price").attr('checked', true) : $("#chk_label_price").attr('checked', false);
            carton == 1 ? $("#chk_carton").attr('checked', true) : $("#chk_carton").attr('checked', false);
            warranty == 1 ? $("#chk_warranty").attr('checked', true) : $("#chk_warranty").attr('checked', false);
            category == 1 ? $("#chk_category").attr('checked', true) : $("#chk_category").attr('checked', false);

            suppliers == 1 ? $("#chk_supplier").attr('checked', true) : $("#chk_supplier").attr('checked', false);
            service == 1 ? $("#chk_service").attr('checked', true) : $("#chk_service").attr('checked', false);
            salesman == 1 ? $("#chk_salesman").attr('checked', true) : $("#chk_salesman").attr('checked', false);
            expenses == 1 ? $("#chk_expenses").attr('checked', true) : $("#chk_expenses").attr('checked', false);
            customers == 1 ? $("#chk_customers").attr('checked', true) : $("#chk_customers").attr('checked', false);

            quotations == 1 ? $("#chk_quotation").attr('checked', true) : $("#chk_quotation").attr('checked', false);
            promotions == 1 ? $("#chk_promotion").attr('checked', true) : $("#chk_promotion").attr('checked', false);
            racks == 1 ? $("#chk_racks").attr('checked', true) : $("#chk_racks").attr('checked', false);
            credit == 1 ? $("#chk_credit").attr('checked', true) : $("#chk_credit").attr('checked', false);
            customers == 1 ? $("#chk_customers").attr('checked', true) : $("#chk_customers").attr('checked', false);

            prescription == 1 ? $("#chk_prescription").attr('checked', true) : $("#chk_prescription").attr('checked', false);
            counter == 1 ? $("#chk_counter").attr('checked', true) : $("#chk_counter").attr('checked', false);
            excessAmount == 1 ? $("#chk_excess").attr('checked', true) : $("#chk_excess").attr('checked', false);
            batchNo == 1 ? $("#chk_batch").attr('checked', true) : $("#chk_batch").attr('checked', false);
            invoice_print == 1 ? $("#chk_invoice_print").attr('checked', true) : $("#chk_invoice_print").attr('checked', false);
            is_under_cos == 1 ? $("#chk_is_under_cost").attr('checked', true) : $("#chk_is_under_cost").attr('checked', false);
            //prop, not attr: attr leaves the box ticked from the shop opened before it
            $("#chk_a4_invoice").prop('checked', a4_invoice == 1);
            
            var defaulteUrl  = "../Assets/Images/synnex_logo.png";
            var imageUrl   = "../Assets/Images/shop_images/" + shopLogo;
            url = checkImageExistsimg_shop_logo(imageUrl,defaulteUrl);
            // $("#img_shop_logo").attr("src", url);
            
            var defaulteUrl  = "../Assets/Images/synnex_logo.png";
            var imageUrl   = "../Assets/Images/shop_images/" + receiptLogo;
            url = checkImageExistsimg_receipt_logoo(imageUrl,defaulteUrl);

            // $("#img_receipt_logo").attr("src", url);
            
            $("#myModalLabel").text("Edit Shop");
            $("#Shop_modal").modal('toggle');
        });

    });//edit shop
});//jquery shop