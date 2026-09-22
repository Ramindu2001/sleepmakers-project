$(document).ready(function () {
    function setCurrentDate() {
        var today = new Date();
        var date = today.getFullYear() + '-' + (today.getMonth() + 1).toString().padStart(2, '0') + '-' + today.getDate().toString().padStart(2, '0');
        $('#current_date').text(date);
        $('#date').val(date); // Set the value of hidden date input
    }

    $('#Expense_modal').on('show.bs.modal', function () {
        setCurrentDate();
    });

    $('#btn_Add_Expense_modal').click(function () {
        console.log("Add Expense button clicked");
        $("#Expense_modal").modal('toggle');
    });
    $("#cmb_paymethod").on("change", function(){
        
    })

    // $("#btn_save_expense").click(function (e) {
    //     e.preventDefault(); // Prevent the default form submission

    //     var expense_ctg = $("#expense_ctg").val();
    //     var amount = $("#amount").val();
    //     var remark = $("#remark").val();
    //     var date = $("#date").val(); // Get the current date

    //     console.log("Save button clicked");
    //     console.log("Category: " + expense_ctg);
    //     console.log("Amount: " + amount);
    //     console.log("Remark: " + remark);
    //     console.log("Date: " + date);

    //     if (expense_ctg.length > 0 && amount.length > 0 && remark.length > 0) {
    //         $.ajax({
    //             type: 'POST',
    //             url: '../Controller/AddExpenseController.php',
    //             data: {
    //                 btn_save_expense: 1,
    //                 expense_ctg: expense_ctg,
    //                 amount: amount,
    //                 remark: remark,
    //                 date: date,
    //                 shop_SHID: $("#shop_SHID").val(),
    //                 user_USID: $("#user_USID").val()
    //             },
    //             success: function (data) {
    //                 console.log("AJAX success: " + data);
    //                 alert(data);
    //                 $("#Expense_modal").modal('toggle');
    //                 location.reload();
    //             },
    //             error: function (xhr, status, error) {
    //                 console.error("AJAX error: " + status + " - " + error);
    //                 alert("Error: Unable to save the expense.");
    //             }
    //         });
    //     } else {
    //         alert("Please fill all fields");
    //     }
    // });


    $(document).ready(function () {
        $(document).on("click", "#btn_edit", function () {
            var EPID = $(this).data("epid"); 
            console.log(EPID);
            
            if (!EPID) {
                alert("Expense ID not found!");
                return;
            }
    
            $.ajax({
                url: "../Controller/AddExpenseController.php?edit=1", 
                type: "POST",
                data: { EPID: EPID },
                dataType: "json",
                success: function (response) {
                        console.log(response);
                        $("#edit_expense_id").val(response[0]["EPID"]);
                        $("#edit_expense_date").val(response[0]["EffectiveDate"]);
                        $("#edit_expense_amount").val(response[0]["ExpenseAmount"]);
                        $("#edit_expense_category").val(response[0]["expensecategory_id"]);
                        $("#edit_expense_remark").val(response[0]["ExpenseReason"]);
                        $("#ecmb_paymethod").val(response[0]["paymethod_id"]);
                },
                error: function(xhr, status, error) {
                    console.log("AJAX Error:", status, error);
                    console.log("Response:", xhr.responseText);
                    toastr.error("An error occurred while processing.", "Error");
                }
            });
            $("#edit-expenses-modal").modal("toggle");
        });
    });
    
 
    $(document).on('click', '.btn_delete', function (e) {
        e.preventDefault(); // Prevent the default anchor behavior
        var EPID = $(this).data('epid');

        console.log("Delete button clicked for EPID: " + EPID);

        if (confirm("Are you sure you want to delete this expense row?")) {
            // Proceed to delete the counter
            $.ajax({
                type: 'POST',
                url: '../Controller/AddExpenseController.php',
                data: {
                    delete_expense: 1,
                    EPID: EPID
                },
                success: function (response) {
                    console.log("Server response: " + response);
                    alert(response);
                    if (response.trim() === "Expense Deleted Successfully") {
                        location.reload();
                    } else {
                        alert("Error: Unable to delete the expense.");
                    }
                },
                error: function (xhr, status, error) {
                    console.error("AJAX error: " + status + " - " + error);
                    alert("Error: Unable to delete the expense.");
                }
            });
        }
    });
});
