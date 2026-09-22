<?php 
include "../Includes/includes.php";
include '../Includes/authcheck.php';
if($userObj->checkusertype($_SESSION["user_id"])==1)
{

}
else
{
    ?>
    <script>
        window.location.href = "../Public/home.php";
    </script>
    <?php
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php 
 include '../View/head.php';
 // include '../View/loader.php';

  ?>
</head>

<body>

<?php 
    //load editor
    $Mod_Id = 0;
    if(isset($_GET['Mod_Id']))
    {
        $Mod_Id = $_GET['Mod_Id'];
        echo "<script>";
        echo "$(document).ready(function(){";
        echo "$('#SysModule_modal').modal('toggle');";
        echo "});";
        echo "</script>";
    }//set edit

    include '../View/modals/SysModules.php';
?>


<!--  Body Wrapper -->
<div class="h-100vh">
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
            data-sidebar-position="fixed" data-header-position="fixed">
            <!-- Sidebar Start -->
            <?php 
             include '../View/sidebar.php';
            ?>
            <!--  Sidebar End -->
            <!--  Main wrapper -->
            <div class="body-wrapper">
                <!--  Header Start -->
                <?php 
                    include '../View/header.php';
                   
                ?>
                <!--  Header End -->
    
                <div class="container-fluid">
                    <h5 class="card-title fw-semibold mb-4">System Module List</h5>
                    
                    <button type="button" class="btn btn-primary rounded-pill ml-1 mb-2" id="Add_SysModule_modal" data-bs-dismiss="modal">Add New Module</button>
                   
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                      <table class="table search-table align-middle text-nowrap" id="tbl_active_store">
                                        <thead class="header-item">  
                                            <tr>
                                                <th>ID</th>
                                                <th>Module Name</th>                                                   
                                            </tr>                                        
                                        </thead>
                                        <tbody>
                                            
                                            <?php
                                                $ModuleObj = new sysModels();
                                                $ModuleName = $ModuleObj->getModules(); 

                                                foreach ($ModuleName as $Module): ?>   
                                                    <tr>
                                                    <td><?php echo $Module['SMID'];?></td>
                                                    <td><?php echo $Module['ModuleName'];?></td>
                                                    <td>
                                                        <!--<input type="hidden" name="" id="SMID" value="<?=$Module['SMID'];?>">
                                                         <a href="javascript:void(0)" id="edit_sysmodule"><i class="ti ti-edit"></i></a> -->
                                                        <a href="SystemModulesList.php?Mod_Id=<?php echo $Module['SMID'];?>">Edit</a>
                                                    </td>    
                                                    </tr>                                         
                                            <?php endforeach; ?>
                                        </tbody>
                                      </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- footer Start  -->
    <?php include '../View/footer.php';?> 
    <!-- footer End  -->
    <script src="../Assets/jquery/SysModule.js"></script>
    <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="../Assets/js/dashboard.js"></script>                                            
</body>

</html>


