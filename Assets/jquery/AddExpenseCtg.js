$(document).ready(function () {
    $('#btn_Add_Category_modal').click(function () {
        console.log("Add Category button clicked");
        $("#Category_modal").modal('toggle');
    });

    $("#btn_save_category").click(function (e) {
        e.preventDefault(); // Prevent the default form submission

        var expense_ctg = $("#expense_ctg").val();
        var expense_ETID = $("#expense_ETID").val(); // Capture the expense_ETID

        console.log("Save button clicked");
        console.log("Category: " + expense_ctg);
        console.log("ETID: " + expense_ETID);

        if (expense_ctg.length > 0 && expense_ETID.length > 0) {
            $.ajax({
                type: 'POST',
                url: '../Controller/AddExpenseCtgController.php',
                data: {
                    btn_save_category: 1,
                    expense_ctg: expense_ctg,
                    expense_ETID: expense_ETID // Send the expense_ETID
                },
                success: function (data) {
                    console.log("AJAX success: " + data);
                    alert(data);
                    $("#Category_modal").modal('toggle');
                    location.reload();
                },
                error: function (xhr, status, error) {
                    console.error("AJAX error: " + status + " - " + error);
                    alert("Error: Unable to save the category.");
                }
            });
        } else {
            alert("Please enter both category and ETID");
        }
    });

    $(document).on('click', '.btn_delete', function (e) {
        e.preventDefault(); // Prevent the default anchor behavior
        var ECID = $(this).data('ecid'); // Ensure the data attribute name matches

        console.log("Delete button clicked for ECID: " + ECID);

        if (confirm("Are you sure you want to delete this category?")) {
            // Proceed to delete the counter
            $.ajax({
                type: 'POST',
                url: '../Controller/AddExpenseCtgController.php',
                data: {
                    delete_category: 1,
                    ECID: ECID
                },
                success: function (response) {
                    console.log("Server response: " + response);
                    alert(response);
                    if (response.trim() === "Category Deleted Successfully") {
                        location.reload();
                    } else {
                        // alert("Error: Unable to delete the category.");
                    }
                },
                error: function (xhr, status, error) {
                    console.error("AJAX error: " + status + " - " + error);
                    alert("Error: Unable to delete the category.");
                }
            });
        }
    });
});
