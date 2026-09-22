<?php
//Settings -> Assign Users to Shops (Public/AssignUsersToShops.php, Assets/jquery/AddShops.js).
//Decides who may enter which shop and with which role - see db/SHOP_ACCESS_MODULE.md.
//Only a signed-in super admin may use it, every request carries the page's CSRF token, and
//every answer is JSON: {"ok": true|false, "message": "..."}.
include "../Includes/includes.php";
require_once "../Includes/csrf.php";

header('Content-Type: application/json; charset=utf-8');

function assign_respond($ok, $message, $status = 200)
{
    http_response_code($status);
    echo json_encode(['ok' => $ok, 'message' => $message]);
    exit;
}//respond

//a posted positive integer id, or null
function assign_post_id($key)
{
    $id = filter_var(isset($_POST[$key]) ? $_POST[$key] : null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : $id;
}//posted id

$signedIn = isset($_SESSION['user_id']) ? (new User())->getOneUser($_SESSION['user_id']) : [];
if(empty($signedIn) || $signedIn[0]['UserType'] != 1 || $signedIn[0]['UserStat'] != 1)
{
    assign_respond(false, 'Only a system admin can change shop access.', 403);
}//not a super admin

if($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null))
{
    assign_respond(false, 'Your session expired. Please reload the page and try again.', 400);
}//not a form post from our page

$assignObj = new AddUsersModels();
$action = isset($_POST['action']) ? $_POST['action'] : '';

try
{
    if($action === 'save')
    {
        $shop_id = assign_post_id('shop_id');
        $user_id = assign_post_id('user_id');
        $role_id = assign_post_id('role_id');
        if($shop_id === null || $user_id === null || $role_id === null)
        {
            assign_respond(false, 'Please select a shop, a user and a role.', 422);
        }//missing field
        if(!$assignObj->isActiveRole($role_id))
        {
            assign_respond(false, 'Please select an active role.', 422);
        }//inactive or unknown role
        if(!$assignObj->shopExists($shop_id) || !$assignObj->userExists($user_id))
        {
            assign_respond(false, 'That shop or user no longer exists.', 422);
        }//stale page
        if($assignObj->assignUser($shop_id, $user_id, $role_id) === 'exists')
        {
            assign_respond(false, 'User already assigned to this shop. Use Edit to change the role.', 409);
        }//duplicate
        assign_respond(true, 'User assigned.');
    }//add an assignment

    $suid = assign_post_id('suid');
    if($suid === null || $assignObj->getUserShopData($suid) === false)
    {
        assign_respond(false, 'Assignment not found. Please reload the page.', 404);
    }//every other action works on an existing assignment

    if($action === 'update_role')
    {
        $role_id = assign_post_id('role_id');
        if($role_id === null || !$assignObj->isActiveRole($role_id))
        {
            assign_respond(false, 'Please select an active role.', 422);
        }//inactive or unknown role
        $assignObj->updateRole($suid, $role_id);
        assign_respond(true, 'Role updated.');
    }//change the role in that shop
    elseif($action === 'set_active')
    {
        $active = isset($_POST['active']) ? (string)$_POST['active'] : '';
        if($active !== '0' && $active !== '1')
        {
            assign_respond(false, 'Unknown access state.', 422);
        }//not 0/1
        $assignObj->setActive($suid, $active === '1');
        assign_respond(true, $active === '1' ? 'Access restored.' : 'Access revoked.');
    }//revoke or restore
    elseif($action === 'delete')
    {
        if($assignObj->checkUserDelete($suid) !== "User can be deleted.")
        {
            assign_respond(false, 'This user has transactions in this shop, so the assignment cannot be deleted. Use Revoke to remove access.', 409);
        }//history in that shop
        $assignObj->deleteUserShop($suid);
        assign_respond(true, 'Assignment deleted.');
    }//delete an unused assignment

    assign_respond(false, 'Unknown action.', 400);
}
catch(PDOException $e)
{
    error_log("AddUsersToShopsController: " . $e->getMessage());
    assign_respond(false, 'Could not save the change. Please try again.', 500);
}//database error
