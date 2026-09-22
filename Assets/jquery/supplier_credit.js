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
	    let id = row.data('id');
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
            load_transaction2(GHID,supplier_id);
            
            $("#p_balance").html("<h5>Balance: <b>" + pending_amount.toFixed(2) + "</b></h5>");
            $("#tbl_grn_details").html(tbl_data);
            $("#hide_grn_header_id").val(GHID);
            $("#hide_grn_total").val(total_amount);
            $("#hide_credit_supplier_id").val(id);
            $("#hide_supplier_id").val(supplier_id);
            $("#supplier_payment_modal").modal('toggle');

        });
    });

    
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
            transferCheque:transferCheque,
            type:2
        }, function(data){
            alert(data);
            load_transaction2(grn_header_id,supplier_id);

            $("#credit_payment").val("");
            if(paymethod_id==13)
            {
                // location.reload();
            }
            if(paymethod_id==5)
            {
                // location.reload();
            }
            

        });

    });//add payment
    
    $("#tbl_transaction").on('click', '.btn_delete_transaction', function(){
        let row = $(this).closest('tr');
	    let id = row.data('id');
        var grn_header_id = $("#hide_grn_header_id").val();
        var grn_total_amount = $("#hide_grn_total").val();
        var hide_supplier_id = $("#hide_supplier_id").val();

        $.get("../AJAX/Suppliers/deleteSupplierTransaction.php", {
            supplier_transaction_id: id,
            type:2
        }, function(data){
            alert(data);

            load_transaction2(grn_header_id,hide_supplier_id);

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

    function balance(pending_amount)
    {
        $("#p_balance").html("<h5>Balance: <b>" + pending_amount.toFixed(2) + "</b></h5>");

    }
    function load_transaction2(GHID,supplier_id)
    {
        $("#tbl_transaction").html("");
        $.get("../AJAX/Suppliers/getGrnTransaction.php", {
            GHID : GHID,
            supplier_id:supplier_id
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
                var trans_stat = value['supcreditTransactionStat'] == 0 ? "Pending" : "Paid";
                var is_disabled = value['supcreditTransactionStat'] == 0 ? 0 : 1;

                tbl_data += "<tr data-id='"+ value['SCTID'] +"'>";
                tbl_data += "<td>"+ value['PaymethodName'] +"</td>";
                tbl_data += "<td>"+ value['supcreditTransactionAmount'] +"</td>";
                if(value['supcreditTransactionStat'] == 0)
                {
                    tbl_data += "<td class='text-warning'><i class='ti ti-alert-triangle'></i>"+ trans_stat +"</td>";
                }
                else
                {
                    tbl_data += "<td class='text-success'><i class='ti ti-circle-check'></i>"+ trans_stat +"</td>";
                }
                tbl_data += "<td>";
                if(is_disabled==0)
                {
                    tbl_data += "<button type='button' class='btn btn-danger btn_delete_transaction' style='background:#f70000;'><i class='ti ti-trash-x'></i></button>";
                }
                else
                {
                    tbl_data += "<button type='button' class='btn btn-danger' disabled ><i class='ti ti-trash-x'></i></button>";
                }                
                tbl_data += "</td>";
                tbl_data += "</tr>";

                $("#tbl_transaction").html(tbl_data);
            });
        });

    }
});//supplier credit jquery