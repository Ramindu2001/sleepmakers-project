<?php 
include "../Includes/includes.php";
include '../Includes/authcheck.php';
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

<!--  Body Wrapper -->
<div class="h-100vh">
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
            data-sidebar-position="fixed" data-header-position="fixed">
            <!-- Sidebar Start -->
            <?php 
             include '../View/sidebar.php';
             $feature_id=19;
             include '../Includes/viewPermission.php';
            ?>
            <!--  Sidebar End -->
            <!--  Main wrapper -->
            <div class="body-wrapper">
                <!--  Header Start -->
                <?php 
                    include '../View/header.php';
                    include '../View/modals/Salesmanmodel.php';
                ?>

                <!--  Header End -->
                <div class="container-fluid">
                    <div class="container">
                        <?php 
                        if(isset($_SESSION['salesman_update']))
                        {
                            if($_SESSION['salesman_update'] == 0)
                            {
                                ?>
                                <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                    Please enter <strong>Salesman name.</strong>
                                </div>
                                <?php 
                            }//no salesman name
                            else if($_SESSION['salesman_update'] == 1)
                            {
                                ?>
                                <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                    Please enter Salesman <strong>Conatct No.</strong>
                                </div>
                                <?php 
                            }//no contact name

                            else if($_SESSION['salesman_update'] == 2)
                            {
                                ?>
                                <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                    Salesman <strong>saved </strong>successfully!
                                </div>
                                <?php 
                            }//no contact name

                            else if($_SESSION['salesman_update'] == 3)
                            {
                                ?>
                                <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                    Salesman <strong>updated </strong>successfully!
                                </div>
                                <?php 
                            }//no contact name

                            else if($_SESSION['salesman_update'] == 4)
                            {
                                ?>
                                <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                    Salesman cannot<strong> Delete </strong>from system.
                                </div>
                                <?php 
                            }//cannot delete 

                            else if($_SESSION['salesman_update'] == 5)
                            {
                                ?>
                                <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                    Salesman <strong> Deleted </strong>successfully!
                                </div>
                                <?php 
                            }//cannot delete 

                            else
                            {
                                ?>
                                <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                    Oops! something went <strong>Wrong! </strong>
                                </div>
                                <?php 
                            }//else

                            unset($_SESSION['salesman_update']);
                        }//has session
                        ?>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title fw-semibold ">
                                Salesman List
                                <?php 
                                if($userType==1 || $create==1)
                                {
                                    ?>
                                    <button type="button" class="btn btn-primary rounded-pill float-end" id="btn_Add_Salesman_modal" data-bs-dismiss="modal"><small>Add New Salesman</small></button>
                                    <?php
                                }
                                ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                      <table class="table search-table align-middle text-nowrap" id="tbl_salesman">
                                        <thead>
                                            <tr>
                                                <th>Salesman No</th>                                            
                                                <th>Salesman Name</th>                                                                                                                    
                                                <th>Contact</th>  
                                                <th>Commision</th>                                                  
                                                <th>Status</th>                                                                                                                                
                                                <th>Action</th>                                                                                               
                                            </tr> 
                                        </thead>
                                        <tbody>
                                               
                                                <?php
                                                $SalObj = new Salesman();
                                                $Salesman = $SalObj->getSalesmans($shop_id); 

                                                foreach ($Salesman as $row):   
                                                ?>   
                                                <tr data-id="<?php echo $row['SLID'];?>">
                                                    <td><?php echo $row['SalesmanNo'];?></td>
                                                    <td><?php echo $row['SalesmansName'];?></td>
                                                    <td><?php echo $row['SalesmansContact'];?></td>
                                                    <td><?php echo $row['commision_rate'];?>%</td>                       
                                                    <td>
                                                        <?php 
                                                        if($row['SalesmanStat']== 1)
                                                        {
                                                            ?>
                                                            <p class="text-success" style="font-weight: bold;"><i class="ti ti-check"></i> Active</p>
                                                            <?php 
                                                        }//active
                                                        else
                                                        {
                                                            ?>
                                                            <p class="text-danger" style="font-weight: bold;"><i class="ti ti-x"></i> Inactive</p>
                                                            <?php 
                                                        }//inactive
                                                        ?>
                                                    </td><!-- 4 -->
                                                    <td>
                                                        <?php 
                                                        if($userType==1 || $edit==1)
                                                        {
                                                            ?>
                                                            <button type="button" class="btn border border-primary btn_edit_salesman"><i class="ti ti-edit"></i></button>
                                                            <?php
                                                        }
                                                        if($userType==1 || $delete==1)
                                                        {
                                                            ?>
                                                            <button type="button" class="btn border border-danger btn_delete_salesman"><i class="ti ti-x"></i></button>
                                                            <?php
                                                        }
                                                        ?>
                                                    </td><!-- 6 -->  
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
    <script src="../Assets/jquery/Salesman.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>

    <script>
         $(document).ready(function(){
            $("#tbl_salesman").DataTable({
                paging: true,
                lengthChange: true,
                searching: true,
                // pageLength: 50,
            });
        });
    </script>
</body>

</html>


