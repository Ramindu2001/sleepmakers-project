<?php 
include '../../Includes/config.php';
include '../../Model/user_role.php';
$userRole=new UserRole ();
$id=$_POST['id'];
$status=$_POST['status'];
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
