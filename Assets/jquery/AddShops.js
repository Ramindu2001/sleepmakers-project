//Settings -> Assign Users to Shops. Every change goes to Controller/AddUsersToShopsController.php,
//which answers {ok, message} JSON - see db/SHOP_ACCESS_MODULE.md.
$(document).ready(function () {
    var modal = $("#SysFeature_modal");

    function send(data, done) {
        data.csrf_token = $("#assign_csrf_token").val();
        $.ajax({
            type: 'POST',
            url: '../Controller/AddUsersToShopsController.php',
            data: data,
            dataType: 'json'
        }).done(function (response) {
            done(response);
        }).fail(function (xhr) {
            done(xhr.responseJSON || { ok: false, message: 'Could not reach the server. Please try again.' });
        });
    }//send

    function showError(message) {
        $("#assign_error").text(message).toggle(!!message);
    }//showError

    $('#btn_Add_SysFeature_modal').click(function () {
        $("#assign_modal_title").text("Add Users");
        $("#assign_suid").val('');
        $("#ShopName, #UserName").prop('disabled', false).val('');
        $("#RoleName").val('');
        showError('');
        modal.modal('show');
    });

    //a new assignment starts from the user's default role
    $("#UserName").on('change', function () {
        var role = String($(this).find(':selected').data('default-role') || '');
        if (role && $("#RoleName option[value='" + role + "']").length) {
            $("#RoleName").val(role);
        }
    });

    $(".btn-edit-assignment").click(function () {
        var row = $(this).closest('tr');
        $("#assign_modal_title").text("Edit Shop Role");
        $("#assign_suid").val(row.data('suid'));
        $("#ShopName").val(String(row.data('shop-id'))).prop('disabled', true);
        $("#UserName").val(String(row.data('user-id'))).prop('disabled', true);
        $("#RoleName").val(String(row.data('role-id')));
        showError('');
        modal.modal('show');
    });

    $("#userShopForm").on('submit', function (e) {
        e.preventDefault();
        var suid = $("#assign_suid").val();
        var data = suid
            ? { action: 'update_role', suid: suid, role_id: $("#RoleName").val() }
            : { action: 'save', shop_id: $("#ShopName").val(), user_id: $("#UserName").val(), role_id: $("#RoleName").val() };
        send(data, function (response) {
            if (response.ok) {
                location.reload();
            } else {
                showError(response.message);
            }
        });
    });

    $(".btn-set-active").click(function () {
        var row = $(this).closest('tr');
        var active = String($(this).data('active'));
        var question = active === '1'
            ? "Restore this user's access to the shop?"
            : "Revoke this user's access to the shop? They leave it on their next click.";
        if (!confirm(question)) {
            return;
        }
        send({ action: 'set_active', suid: row.data('suid'), active: active }, function (response) {
            alert(response.message);
            if (response.ok) {
                location.reload();
            }
        });
    });

    $(".btn-delete").click(function (e) {
        e.preventDefault();
        var row = $(this).closest('tr');
        if (!confirm("Delete this assignment?")) {
            return;
        }
        send({ action: 'delete', suid: row.data('suid') }, function (response) {
            alert(response.message);
            if (response.ok) {
                location.reload();
            }
        });
    });
});
