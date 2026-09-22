function addChq()
{
    var payment =`
                <div id="chequeDiv" class="payment-method chequeDiv col-md-12 mb-2 row">
                    <div class="col-md-6">
                        <label for="chqNo" class="form-label">Cheque No</label>
                        <input type="text" name="chqNo[]" id="chqNo" class="form-control" placeholder="Cheque No">
                    </div>
                    <div class="col-md-6">
                        <label for="chqdate" class="form-label">Cheque Date</label>
                        <input type="date" name="chqdate[]" id="chqdate" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="chqbank" class="form-label">Bank</label>
                        <input type="text" name="chqbank[]" id="chqbank" class="form-control" placeholder="Ex: BOC">
                    </div>
                    <div class="col-md-4">
                        <label for="chqamount" class="form-label">Cheque Amount</label>
                        <input type="text" name="chqAmount[]" id="chqamount" class="form-control" placeholder="Ex: 2000">
                    </div>
                    <div class="col-md-4">
                    <a href='javascript:void(0);' class='remove-payment btn btn-danger mt-4' id='remove-payment' onclick='remove_cheque(this)' ><i class='ti ti-trash'></i></a>
                    <a href='javascript:void(0);' class='btn btn-primary mt-4' onclick='addChq()' ><i class='ti ti-plus'></i></a>
                    </div>
                </div>
                `;
                    $("#payment_method").append(payment);
}

function remove_cheque(item)
{
    var value = $(item).attr("id");
    $(item).parent().parent().remove();            
}
$(document).ready(function(){
    
    $("#tbl_suppier_credit").on('click', '.supplier_pay', function(){
        let row = $(this).closest('tr');
        let supplier_id=row.find("#supplier_id").val();
        let GHID=row.find("#GHID").val();
        $.get("../AJAX/Suppliers/getOneCreditSupplier.php", {
            supplier_id: supplier_id,
            GHID:GHID
        }, function(data){
            const obj = JSON.parse(data);
            var GRNdate = parseFloat(obj[0]['GRNdate']);
            var GRNHeaderNo = obj[0]['GRNHeaderNo'];
            var total_amount = parseFloat(obj[0]['TotalPurchasePrice']);

            var paid_amount = parseFloat(obj[0]['CreditAmount']); 
            var pending_amount = parseFloat(obj[0]['DebitAmount']) - parseFloat(obj[0]['CreditAmount']); 
            $("#p_sup_details").html("<div class='col-md-6'><b>GRN No : " + GRNHeaderNo + "</b></div> <div class='col-md-6'><b>GRN Date: " + GRNdate+" </b></div>");
            var tbl_data = "";

            tbl_data += "<tr>";
            tbl_data += "<td><b>Total Amount</b></td>";
            tbl_data += "<td style='text-align:right;'>" + total_amount.toFixed(2) + "</td>";
            tbl_data += "</tr>";

            tbl_data += "<tr>";
            tbl_data += "<td><b>Paid Amount</b></td>";
            tbl_data += "<td style='text-align:right;'>" + paid_amount.toFixed(2) + "</td>";
            tbl_data += "</tr>";

            tbl_data += "<tr>";
            tbl_data += "<td><b>Pending Amount</b></td>";
            tbl_data += "<td style='text-align:right;'><b>" + pending_amount.toFixed(2) + "</b></td>";
            tbl_data += "</tr>";
            load_transaction(GHID);
            
            $("#p_balance").html("<h5>Balance: <b>" + pending_amount.toFixed(2) + "</b></h5>")
            $("#tbl_grn_details").html(tbl_data);
            $("#supplier_payment_modal").modal('toggle');

        });
    });
    $("#tbl_suppier_credit").on('click', '.btn_credit_payment', function(){
        let row = $(this).closest('tr');
	    let id = row.data('id');

        $("#supplier_payment_modal").modal('toggle');

        $.get("../AJAX/Suppliers/getOneCreditSupplier.php", {
            credit_supplier_id: id
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);

            var supplier_id = obj[0]['SPID'];
            var sup_name = obj[0]['SupplierName'];
            var sup_contact = obj[0]['Contact'];
            var invoice_no = obj[0]['InvoiceNo'];
            var effective_date = obj[0]['EffectiveDate'];

            var total_amount = parseFloat(obj[0]['TotalPurchasePrice']);
            var credit_amount = parseFloat(obj[0]['CreditAmount']);
            var pending_amount = parseFloat(0);
            if(obj[0]['DebitAmount']!=null || obj[0]['DebitAmount']==0 || obj[0]['DebitAmount']!="")
            {
                pending_amount = parseFloat(obj[0]['DebitAmount']);
            }
            if(isNaN(pending_amount))
            {
                pending_amount = parseFloat(0);
            }
            // alert(pending_amount);
            
            var grn_header_id = parseFloat(obj[0]['invoice_header_id']);

            var paid_amount = total_amount - pending_amount; 

            //load tables
            load_transaction(grn_header_id);

            $("#p_sup_details").html("Invoice : " + invoice_no + "<br>Date: " + effective_date);

            var tbl_data = "";

            tbl_data += "<tr>";
            tbl_data += "<td>Total Amount</td>";
            tbl_data += "<td style='text-align:right;'>" + total_amount.toFixed(2) + "</td>";
            tbl_data += "</tr>";

            tbl_data += "<tr>";
            tbl_data += "<td>Paid Amount</td>";
            tbl_data += "<td style='text-align:right;'>" + paid_amount.toFixed(2) + "</td>";
            tbl_data += "</tr>";

            tbl_data += "<tr>";
            tbl_data += "<td>Pending Amount</td>";
            tbl_data += "<td style='text-align:right;'><b>" + pending_amount.toFixed(2) + "</b></td>";
            tbl_data += "</tr>";

            $("#tbl_grn_details").html(tbl_data);
            $("#hide_grn_header_id").val(grn_header_id);
            $("#hide_grn_total").val(total_amount);
            $("#hide_credit_supplier_id").val(id);
            $("#hide_supplier_id").val(supplier_id);

            //get balance
            setTimeout(() => {
                get_transaction_balance(total_amount);
            }, 500);
        });

    });//payment


    //payment
    
    $("#btn_add_payment").click(function(){
        var grn_header_id = $("#hide_grn_header_id").val();
        var grn_total_amount = $("#hide_grn_total").val();
        var paymethod_id = $("#cmb_paymethod").val();
        var pay_amount = $("#credit_payment").val();
        var supplier_id = $("#supplier_id").val();
        var chqNos = [];
        $("input[id^='chqNo']").each(function() { // Use starts-with selector for dynamically generated IDs
            var val = $(this).val();
            if (val) {
                chqNos.push(val);
            }
        });
        var chqdate = [];
        $("input[id^='chqdate']").each(function() { // Use starts-with selector for dynamically generated IDs
            var val = $(this).val();
            if (val) {
                chqdate.push(val);
            }
        });
        var chqbank = [];
        $("input[id^='chqbank']").each(function() { // Use starts-with selector for dynamically generated IDs
            var val = $(this).val();
            if (val) {
                chqbank.push(val);
            }
        });
        var chqamount = [];
        $("input[id^='chqamount']").each(function() { // Use starts-with selector for dynamically generated IDs
            var val = $(this).val();
            if (val) {
                chqamount.push(val);
            }
        });
        var transferCheque = [];
        $("select[id^='transferCheque']").each(function() { // Use starts-with selector for dynamically generated IDs
            var val = $(this).val();
            if (val) {
                transferCheque.push(val);
            }
        });

        $.get("../AJAX/Suppliers/setSupplierTransaction.php", {
            supplier_id:supplier_id,
            grn_header_id : grn_header_id,
            paymethod_id : paymethod_id,
            pay_amount : pay_amount,
            chqNo:chqNos,
            chqdate:chqdate,
            chqbank:chqbank,
            chqamount:chqamount,
            transferCheque:transferCheque
        }, function(data){
            // alert(data);
            load_transaction(grn_header_id);

            $("#credit_payment").val("");
            

            //get balance
            setTimeout(() => {
                get_transaction_balance(grn_total_amount);
                if(paymethod_id==13)
                {
                    location.reload();
                }
                if(paymethod_id==5)
                {
                    location.reload();
                }
            }, 500);
        });

    });//add payment

    $("#tbl_transaction").on('click', '.btn_delete_transaction', function(){
        let row = $(this).closest('tr');
	    let id = row.data('id');
        var grn_header_id = $("#hide_grn_header_id").val();
        var grn_total_amount = $("#hide_grn_total").val();

        $.get("../AJAX/Suppliers/deleteSupplierTransaction.php", {
            supplier_transaction_id: id
        }, function(data){
            //alert(data);

            load_transaction(grn_header_id);

            //get balance
            setTimeout(() => {
                get_transaction_balance(grn_total_amount);
            }, 500);
        });
    });//delete click

    $("#btn_close_credit_payment").click(function(){
        $("#supplier_payment_modal").modal('hide');
    });//close modal

//========================== Functions =========================//

    function load_transaction(grn_header_id)
    {
        $.get("../AJAX/Suppliers/getGrnTransaction.php", {
            grn_header_id : grn_header_id
        }, function(data){
            // alert(data);
            const obj = JSON.parse(data);
            var tbl_data = "";

            tbl_data += "<tr>";
            tbl_data += "<th>Paymethod</th>";
            tbl_data += "<th>Amount</th>";
            tbl_data += "<th>Status</th>";
            tbl_data += "<th>Action</th>";
            tbl_data += "</tr>";

            $.each(obj, function (key, value) { 
                var trans_stat = value['TransactionStat'] == 0 ? "Pending" : "Paid";
                var is_disabled = value['TransactionStat'] == 0 ? "" : "disabled";

                tbl_data += "<tr data-id='"+ value['TRID'] +"'>";
                tbl_data += "<td>"+ value['PaymethodName'] +"</td>";
                tbl_data += "<td>"+ value['TransferAmount'] +"</td>";
                if(value['TransactionStat'] == 0)
                {
                    tbl_data += "<td class='text-warning'>"+ trans_stat +"</td>";
                }
                else
                {
                    tbl_data += "<td class='text-primary'>"+ trans_stat +"</td>";
                }
                tbl_data += "<td>";
                tbl_data += "<button type='button' class='btn border-danger btn_delete_transaction' "+ is_disabled +"><i class='ti ti-x'></i></button>";
                tbl_data += "</td>";
                tbl_data += "</tr>";

                $("#tbl_transaction").html(tbl_data);
            });
        });

    }//load table

    function get_transaction_balance(grn_total_amount)
    {
        var total = 0;
        $("#tbl_transaction tr").each(function(){
            var paid_amount = $(this).find("td").eq(1).html();
    
            if(paid_amount != undefined)
            {
                total += parseFloat(paid_amount); 
            }//has value
        });//go through table

        var balance = parseFloat(grn_total_amount) - total;

        $("#p_balance").html("<h5>Balance: <b>" + balance.toFixed(2) + "</b></h5>");

    }//get transaction balance

    function grand_total()
    {
        var total=0;
        var amount=0;
        $("body #amount").each(function(){
            if($(this).val()=="" || $(this).val()==undefined)
            {
                amount=0;   
            }
            else
            {
                amount=parseFloat($(this).val());   
            }
            total = total + amount;
        });
        var total2=total.toFixed(2);

        $("#total_paid").val(total2);
        $("#paid-input").val(total2);
    }//get total
});//supplier credit jquery