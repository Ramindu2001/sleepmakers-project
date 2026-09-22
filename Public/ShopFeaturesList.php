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
    $Fe_Id = 0;
    if(isset($_GET['Fe_Id']))
    {
        $Fe_Id = $_GET['Fe_Id'];
        echo "<script>";
        echo "$(document).ready(function(){";
        echo "$('#SysFeature_modal').modal('toggle');";
        echo "});";
        echo "</script>";
    }//set edit

    include '../View/modals/SysFeatures.php';
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
                    <h5 class="card-title fw-semibold mb-4">Shop Features List</h5>
                    
                    <button type="button" class="btn btn-primary rounded-pill ml-1 mb-2" id="btn_Add_SysFeature_modal" data-bs-dismiss="modal">Add New Feature</button>
                    <br>
                    
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                      <table class="table search-table align-middle text-nowrap" id="tbl_active_store">
                                        <thead class="header-item">  
                                            <tr>
                                                <th>No</th>
                                                <th>ID</th>
                                                <th>Feature Name</th>                                                   
                                            </tr>                                        
                                        </thead>
                                        <tbody>
                                            
                                            <?php
                                                $ModuleObj = new sysModels();
                                                $ModuleName = $ModuleObj->getShopFeatures(); 
                                                $i=1;
                                                foreach ($ModuleName as $Module): ?>   
                                                    <tr>
                                                        <td><?php echo $i;?></td>
                                                        <td><?php echo $Module['SPFID'];?></td>
                                                        <td><?php echo $Module['FeatureName'];?></td>
                                                        <td>
                                                            <a href="ShopFeaturesList.php?Fe_Id=<?php echo $Module['SPFID'];?>">Edit</a>
                                                        </td>    
                                                    </tr>                                         
                                            <?php 
                                        $i++;
                                        endforeach; ?>
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


