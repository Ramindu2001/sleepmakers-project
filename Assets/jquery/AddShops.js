$(document).ready(function () {
    $('#btn_Add_SysFeature_modal').click(function () {
        $("#SysFeature_modal").modal('toggle');
    });

    $("#btn_submit_shops").click(function (e) {
        e.preventDefault(); // Prevent the default form submission

        var shop_SHID = $("#ShopName").val();
        var user_USID = $("#UserName").val();

        if (shop_SHID && user_USID) {
            $.ajax({
                type: 'POST',
                url: '../Controller/AddUsersToShopsController.php',
                data: {
                    check_user_shop: 1,
                    shop_SHID: shop_SHID,
                    user_USID: user_USID
                },
                success: function (response) {
                    if (response.trim() === "User already assigned to this shop.") {
                        alert("User already assigned to this shop.");
                    } else {
                        // Proceed to save the user to the shop
                        $.ajax({
                            type: 'POST',
                            url: '../Controller/AddUsersToShopsController.php',
                            data: {
                                btn_save_Shop: 1,
                                shop_SHID: shop_SHID,
                                user_USID: user_USID
                            },
                            success: function (data) {
                                alert(data);
                                // Optionally, close the modal and refresh the table
                                $("#SysFeature_modal").modal('toggle');
                                location.reload();
                            }
                        });
                    }
                }
            });
        } else {
            alert("Please select both a shop and a user.");
        }
    });

    $(".btn-delete").click(function (e) {
        e.preventDefault(); // Prevent the default anchor behavior
        var SUID = $(this).data('suid');
        console.log("Delete button clicked for SUID: " + SUID);

        // Send an AJAX request to check if the user can be deleted
        $.ajax({
            type: 'POST',
            url: '../Controller/AddUsersToShopsController.php',
            data: {
                check_user_delete: 1,
                SUID: SUID
            },
            success: function (response) {
                if (response.trim() === "Cannot delete. This user-shop pair exists in related tables.") {
                    alert(response);
                } else {
                    if (confirm("Are you sure you want to delete this user?")) {
                        $.ajax({
                            type: 'POST',
                            url: '../Controller/AddUsersToShopsController.php',
                            data: {
                                delete_user: 1,
                                SUID: SUID
                            },
                            success: function (response) {
                                console.log("Server response: " + response);
                                alert(response);
                                if (response.trim() === "User deleted successfully.") {
                                    location.reload(); // Refresh the page to reflect the deletion
                                }
                            },
                            error: function (xhr, status, error) {
                                console.error("AJAX error: " + status + " - " + error);
                            }
                        });
                    }
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX error: " + status + " - " + error);
            }
        });
    });
});
