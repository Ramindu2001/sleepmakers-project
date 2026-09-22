<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';
?>

<!doctype html>
<html lang="en">

<head>
  <?php 
  include '../View/head.php';
//   include '../View/loader.php';
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
    $feature_id=17;
    include '../Includes/viewPermission.php';
    ?>
    <!--  Sidebar End -->
    <!--  Main wrapper -->
    <div class="body-wrapper">
        <!--  Header Start -->
        <?php 
        include '../View/header.php';
        // include "../View/modals/prescription.php";

        ?>
        <!--  Header End -->

        <div class="container-fluid">
            <!-- messages -->
        <div class="container">
        <?php 
            if(isset($_SESSION['pres_update']))
            {
                if($_SESSION['pres_update'] == 0)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Please enter <strong>Distributer Name.</strong>
                    </div>
                    <?php 
                }//no entry
                else if($_SESSION['pres_update'] == 1)
                {
                    ?>
                        <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            Please enter <strong>Supplier Name</strong>.
                        </div>
                        <?php
                }//duplicate entry
                else if($_SESSION['pres_update'] == 2)
                {
                    ?>
                    <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Please enter Supplier <strong>Contact</strong>
                    </div>
                    <?php
                }//save success
                else if($_SESSION['pres_update'] == 3)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Supplier <strong>created </strong>successfully!
                    </div>
                    <?php
                }//update success
                else if($_SESSION['pres_update'] == 4)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Supplier <strong>updated </strong>successfully!
                    </div>
                    <?php
                }//update success
                else if($_SESSION['pres_update'] == 5)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Supplier <strong>Deleted </strong>successfully!
                    </div>
                    <?php
                }//update success
                else if($_SESSION['pres_update'] == 6)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Supplier cannot <strong>Delete, </strong>there are items purchased from this supplier.
                    </div>
                    <?php
                }//update success
                else
                {
                    ?>
                    <div class="alert alert-danger">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Oops! </strong>something went wrong!
                    </div>
                    <?php 
                }//else
                unset($_SESSION['pres_update']);
            }//session set
            ?>

        </div>
            
            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title" style="margin-top: 0px;">
                        Prescriptions
                        <?php 
                        if($userType==1 || $create==1)
                        {
                            ?>
                            <button class="btn btn-primary rounded-pill float-end" id="btn_open_prescription"><small>Add Prescription</small></button>
                            <?php
                        }
                        ?>
                    </h5>
                </div>
                <div class="card-body">
               
                <div class="container-fluid">
                    <table class="table table-hover" id="tbl_prescription">
                        <thead>
                            <tr>
                                <th>Prescription No</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Gender</th>
                                <th>Age</th>
                                <th>Subject</th>
                                <th>User</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                            $dbObj = new DBTransactions();
                            //check multi category
                            //get company stat
                            $sql = "SELECT * FROM shop
                            INNER JOIN company ON company.CMID = shop.Company_CMID
                            WHERE SHID = ".$shop_id.";";

                            $shopData = $dbObj->getData($sql);
                            $multi_category = floatval($shopData[0]['is_multicategory']);
                            $company_id = floatval($shopData[0]['CMID']);

                            if($multi_category == 1)
                            {
                                $sql = "SELECT *,prescriptionheader.date AS Presdate FROM `prescriptionheader` 
                                INNER JOIN customers ON customers.CTID = prescriptionheader.customer_CTID
                                INNER JOIN user ON user.USID = prescriptionheader.user_USID
                                INNER JOIN shop ON shop.SHID = prescriptionheader.shop_ID
                                WHERE shop.Company_CMID = ".$company_id.";";
                            }
                            else
                            {
                                $sql = "SELECT *,prescriptionheader.date AS Presdate FROM `prescriptionheader` 
                                INNER JOIN customers ON customers.CTID = prescriptionheader.customer_CTID
                                INNER JOIN user ON user.USID = prescriptionheader.user_USID
                                WHERE shop_ID = ".$shop_id.";";
                            }//no multicategory

                            $supData = $dbObj->getData($sql);

                            foreach($supData as $row)
                            {
                                $gender_id = $row['CustGender'];
                                $gender = $row['CustGender'] == '1' ? "Male" : "Female";
                                $issue_date = substr($row['date'], 0, 10);
                                $cust_dob = $row['CustDOB'];
                                $user_name = $row['CustDOB'];

                                //effective date
                                date_default_timezone_set("Asia/Colombo");
                                $effective_date = date("Y-m-d");
                                $date = new DateTime($row["Presdate"]);
                                $dateString = $date->format('Y-m-d H:i:s');
                                $date = substr($dateString, 0, -9);
                                if($row["CustDOB"]!=null && $row["CustDOB"]!="0000-00-00")
                                {
                                    $dob=$row["CustDOB"];
                                    // Convert the DOB to a DateTime object
                                    $dobDateTime = new DateTime($dob);
                                    // Get the current date
                                    $currentDate = new DateTime();
                                    $age = $dobDateTime->diff($currentDate)->y;
                                }
                                else
                                {
                                    $age="";
                                }

                                ?>
                                <tr data-id="<?php echo $row['PRHID'];?>">
                                    <td><?php echo $row['pr_no'];?></td>
                                    <td><?php echo $issue_date;?></td>
                                    <td><?php echo $row['CustName'] ."<br>". $row['CustContact'];?></td>
                                    <td><?php echo $gender;?></td>
                                    <td>
                                        <?php 
                                        if($age!="")
                                        {
                                            ?>
                                            <?php echo $age;?> Years
                                            <?php
                                        }
                                        else
                                        {
                                            echo "N/A";
                                        }
                                        ?>
                                        
                                    </td>
                                    <td><?php echo $row['pr_subjective_ref'];?></td>
                                    <td><?php echo $row['UserName'];?></td>
                                    <td>
                                        <?php 
                                        if($userType==1 || $view==1)
                                        {
                                            ?>
                                            <a href="../Receipts/prescription.php?pres_id=<?php echo $row['PRHID'];?>" class="btn border border-success text-success"><i class="ti ti-eye"></i></a>
                                            <!-- <button type="button" class="btn border border-success btn_edit_prescription"><i class="ti ti-eye"></i></button> -->
                                            <?php
                                        }
                                        if($userType==1 || $edit==1)
                                        {
                                            ?>
                                            <button type="button" class="btn border border-primary btn_edit_prescription"><i class="ti ti-edit"></i></button>
                                            <?php
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php 
                            }//foreach
                        ?>
                        </tbody>
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

    <script src="../Assets/jquery/prescription.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>

    <script>
        $(document).ready(function(){
            $("#tbl_prescription").DataTable({
                paging: true,
                lengthChange: true,
                searching: true,
                // pageLength: 50,
            });
        });
    </script>
</body>
</html>