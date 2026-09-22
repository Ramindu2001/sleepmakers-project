<?php 
include '../Includes/includes.php';
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

<!doctype html>
<html lang="en">

<head>
  <?php 
  include '../View/head.php';
  // include '../View/loader.php';
  ?>
  <style>
    .table>:not(caption)>*>* {
        padding: 10px;
    }
  </style>
</head>

<body data-sidebartype="mini-sidebar">

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
        include "../View/modals/new-company.php";
        ?>
        <!--  Header End -->

        <div class="container-fluid">
            <!-- messages -->
        <div class="container">
            <?php 
            if(isset($_SESSION['company_update']))
            {
                if($_SESSION['company_update'] == 0)
                {
                    ?>
                    <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Company logo should be less than 5MB</strong>
                    </div>
                    <?php 
                }//logo size
                else if($_SESSION['company_update'] == 1)
                {
                    ?>
                    <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>File type doesn't support.</strong>
                    </div>
                    <?php 
                }//file type 
                else if($_SESSION['company_update'] == 2)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>File cannot be found in directory.</strong>
                    </div>
                    <?php
                }//file cannot found 
                else if($_SESSION['company_update'] == 3)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Company updated</strong>Successfully!
                    </div>
                    <?php 
                }//company created successfully
                else if($_SESSION['company_update'] == 4)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Company Name</strong>empty! Please insert necessary information.
                    </div>
                    <?php 
                } //company created successfully

                else if($_SESSION['company_update'] == 5)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Company updated <strong>Successfully !</strong>
                    </div>
                    <?php 
                } //company created successfully
                else
                {
                    ?>
                    <div class="alert alert-info alert-dismissible bg-info text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Oops!</strong>Something  went wrong.
                    </div>
                    <?php 
                } //default
                unset($_SESSION['company_update']);
            } //session set
            ?>
        </div>
            <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Company</h5>
            <?php 
            if(isset($_SESSION['company_update']))
            {
                if ($_SESSION['company_update']==0) 
                {
                    ?>
                    <div class="alert alert-danger" role="alert">Company logo cannot be higher than 5mb</div>
                    <?php
                }
                unset($_SESSION['company_update']);

            }
            ?>
            <div class="card">
                <div class="card-body">
                <button class="btn btn-primary border border-success rounded-pill ml-1" id="btn_add_company">Add Company</button>
                
                <div class="container-fluid">
                    <table class="table table-hover" id="tbl_company">
                        <tr>
                            <th>No</th>
                            <th>Logo</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Version</th>
                            <th>Licence exp date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        <?php 
                            $comObj = new Company();
                            $comData = $comObj->getAllCompany();
                            foreach($comData as $row)
                            {
                                ?>
                                <tr>
                                    <td style="display: none;"><?php echo $row['CMID'];?></td><!-- 0 -->
                                    <td><?php echo $row['CompanyNo'];?></td><!-- 1 -->
                                    <td>
                                        <img src="../Assets/Images/Company_Logos/<?php echo $row['ComLogo'];?>" alt="Comapny Logo" class="img-fluid" style="width:100px; heigh:auto;">
                                    </td><!-- 2 -->
                                    <td><?php echo $row['ComName'];?></td><!-- 3 -->
                                    <td><?php echo $row['CompanyTypeName'];?></td><!-- 4 -->
                                    <td><?php echo $row['VersionNo'];?></td><!-- 5 -->
                                    <td><?php echo $row['ComExpireDate'];?></td><!-- 6 -->
                                    <td>
                                        <?php 
                                        $comStat = "Inactive";
                                        if($row['ComStat'] == 1)
                                        {
                                            ?>
                                            <p class="text-center" style="color:green;font-weight: 700;"><i class="ti ti-checks"></i> Active</p>
                                            <?php 
                                        }//active company
                                        else
                                        {
                                            ?>
                                            <p class="text-center text-danger" style="font-weight: 700;"><i class="ti ti-circle-x"></i> Inactive</p>
                                            <?php 
                                        }//inactive company
                                        ?>
                                    </td><!-- 7 -->
                                    <td>
                                        <button type="button" id="btn_company_<?php echo $row['CMID']?>" class="btn border border-primary btn_edit_company"><i class="ti ti-edit"></i></button>
                                        <button type="button" id="btn_company_delete_<?php echo $row['CMID']?>" class="btn border border-danger"><i class="ti ti-x"></i></button>
                                    </td><!-- 8 -->
                                    <td style="display: none;"><?php echo $row['CompanyType_CTID'];?></td><!-- 9 -->
                                    <td style="display: none;"><?php echo $row['CompanyLocation'];?></td><!-- 10 -->
                                    <td style="display: none;"><?php echo $row['LicenceNo'];?></td><!-- 11 -->
                                    <td style="display: none;"><?php echo $row['ComStartDate'];?></td><!-- 12 -->
                                    <td style="display: none;"><?php echo $row['is_multicategory'];?></td><!-- 13 -->
                                    <td style="display: none;"><?php echo $row['ComStat'];?></td><!-- 14 -->
                                    <td style="display: none;"><?php echo $row['ComLogo'];?></td><!-- 15 -->
                                    <td style="display: none;"><?php echo $row['is_commonStock'];?></td><!-- 16 -->
                                </tr>
                                <?php
                            }//foreach
                        ?>
                    </table>
                </div>

                </div>
            </div>
        </div>
    </div>
</div>
</div>
<!--  Body Wrapper End -->
    <!-- footer Start  -->
    <?php include '../View/footer.php';?> 
    <!-- footer End  -->
    <script src="../Assets/jquery/company.js"></script>
    <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="../Assets/js/dashboard.js"></script>

</body>
</html>