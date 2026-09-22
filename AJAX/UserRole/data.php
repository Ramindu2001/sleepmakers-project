<?php
session_start();
include '../../Includes/config.php';
include '../../Model/user_role.php';
require_once '../../Includes/super_admin.php';

//switching a role on or off is for the system admin only, from the role page (Public/edit-role.php)
super_admin_request();
$id = filter_var(isset($_POST['id']) ? $_POST['id'] : null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$status = isset($_POST['status']) ? (string)$_POST['status'] : '';
if(!posted_csrf_valid() || $id === false || ($status !== '0' && $status !== '1'))
{
    http_response_code(400);
    echo '<div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">'
        . '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>'
        . '<strong>Your session expired.</strong> Please reload the page and try again.</div>';
    exit;
}//stale or forged request

$userRole=new UserRole ();
$update_status=$userRole->update_role_status($id,$status);
$data='<div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show"
role="alert"><button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"
aria-label="Close"></button>';
if ($status==1) 
{
    $data.='<strong>Role Activated -</strong> Successfully
    </div>';
    $data.='<script>
    $("#status").val("0");
</script>';

}
else
{
    $data.='<strong>Role Inactivated -</strong> Successfully
    </div>';
    $data.='<script>
    $("#status").val("1");
</script>';

}
echo $data;

?>
