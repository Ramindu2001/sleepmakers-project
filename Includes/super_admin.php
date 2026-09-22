<?php
//Users, user roles, passwords and shop access are managed by the system (super) admin only -
//see db/SHOP_ACCESS_MODULE.md. It is decided here, on the server, before a page is sent or
//anything is changed; hiding a menu item is not enough on its own.
require_once __DIR__ . '/../Model/shop_access_class.php';
require_once __DIR__ . '/csrf.php';

//the signed-in user is an active super admin
function is_super_admin_session()
{
    return isset($_SESSION['user_id']) && (new ShopAccess())->isSuperAdmin($_SESSION['user_id']);
}//is super admin session

//a page: anyone else is sent to the home page and none of the page is sent
function super_admin_page()
{
    if(!is_super_admin_session())
    {
        header("Location: ../Public/home.php");
        exit;
    }//not a super admin
}//super admin page

//a request that changes something: anyone else gets 403 and nothing is changed
function super_admin_request()
{
    if(!is_super_admin_session())
    {
        http_response_code(403);
        exit("Only a system admin can manage users and roles.");
    }//not a super admin
}//super admin request

//the form was posted from one of our pages in this session (Includes/csrf.php)
function posted_csrf_valid()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST'
        && csrf_validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null);
}//posted csrf valid
