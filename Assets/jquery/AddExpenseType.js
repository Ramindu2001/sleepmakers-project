$(document).ready(function () {
    $('#btn_Add_Type_modal').click(function () {
        $("#Type_modal").modal('toggle');
    });

    $("#typeForm").submit(function (e) {
        e.preventDefault(); // Prevent the default form submission

        var expense_type = $("#expense_type").val();

        if (expense_type.length > 0) {
            $.ajax({
                type: 'POST',
                url: '../Controller/AddExpenseTypeController.php',
                data: {
                    btn_save_type: 1, 
                    expense_type: expense_type
                },
                success: function (data) {
                    alert(data);
                    $("#Type_modal").modal('toggle');
                    location.reload();
                },
                error: function (xhr, status, error) {
                    console.error("AJAX error: " + status + " - " + error);
                    alert("Error: Unable to save the type.");
                }
            });
        } else {
            alert("Please enter a type");
        }
    });

    $(document).on('click', '.btn_delete', function (e) {
        e.preventDefault(); // Prevent the default anchor behavior
        var ETID = $(this).data('etid'); // Ensure the data attribute name matches

        console.log("Delete button clicked for ETID: " + ETID);

        if (confirm("Are you sure you want to delete this type?")) {
            // Proceed to delete the type
            $.ajax({
                type: 'POST',
                url: '../Controller/AddExpenseTypeController.php',
                data: {
                    delete_type: 1, // Correct key to match the PHP script
                    ETID: ETID
                },
                success: function (response) {
                    console.log("Server response: " + response);
                    alert(response);
                    if (response.trim() === "Type Deleted Successfully") {
                        location.reload();
                    } else 
                    {
                        
                    }
                },
                error: function (xhr, status, error) {
                    console.error("AJAX error: " + status + " - " + error);
                    alert("Error: Unable to delete the type.");
                }
            });
        }
    });
});