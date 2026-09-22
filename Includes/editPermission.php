<?php 
if($userType==0)
{
    $userObj=new User();
    $checkview=$userObj->userAcces($userRole_id,$feature_id);
    $create=$checkview[0]["is_create"];
    $view=$checkview[0]["is_view"];
    $edit=$checkview[0]["is_edit"];
    $delete=$checkview[0]["is_delete"];
    $verify=$checkview[0]["is_verify"];
    $print=$checkview[0]["is_print"];
    if($edit==1 || $verify==1)
    {
        
    }
    else
    {
        ?>
        <script>
            window.location.href = "./home.php";
        </script>
        <?php
    }
}
?>