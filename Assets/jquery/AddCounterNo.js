$(document).ready(function () {
    $('#btn_Add_Counter_modal').click(function () {
        $("#Counter_modal").modal('toggle');
    });

    $("#btn_save_counter").click(function (e) {
        e.preventDefault(); // Prevent the default form submission

        var counterNo = $("#counterNo").val();

        if (counterNo.length > 0) {
            $.ajax({
                type: 'POST',
                url: '../Controller/AddCounterController.php',
                data: {
                    btn_save_counter: 1,
                    counterNo: counterNo
                },
                success: function (data) {
                    alert(data);
                    $("#Counter_modal").modal('toggle');
                    location.reload();
                },
                error: function (xhr, status, error) {
                    console.error("AJAX error: " + status + " - " + error);
                    alert("Error: Unable to save the counter.");
                }
            });
        } else {
            alert("Please enter a counter number");
        }
    });

    $(document).on('click', '.btn_delete', function (e) {
        e.preventDefault(); // Prevent the default anchor behavior
        var CTID = $(this).data('suid'); // Ensure the data attribute name matches

        console.log("Delete button clicked for CTID: " + CTID);

        if (confirm("Are you sure you want to delete this counter?")) {
            // Proceed to delete the counter
            $.ajax({
                type: 'POST',
                url: '../Controller/AddCounterController.php',
                data: {
                    delete_counter: 1,
                    CTID: CTID
                },
                success: function (response) {
                    console.log("Server response: " + response);
                    alert(response);
                    if (response.trim() === "Counter Number Deleted Successfully") {
                        location.reload();
                    } else {
                        alert("Error: Unable to delete the counter.");
                    }
                },
                error: function (xhr, status, error) {
                    console.error("AJAX error: " + status + " - " + error);
                    alert("Error: Unable to delete the counter.");
                }
            });
        }
    });


});
