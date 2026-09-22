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
<style>
    .table>:not(caption)>*>* {
        padding: 10px 10px;
    }
</style>
<body>

<?php 
    //load editor
    $Cus_Id = 0;
    include '../View/modals/Customermodel.php';
?>
<!--  Body Wrapper -->
<div class="h-100vh">
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
            data-sidebar-position="fixed" data-header-position="fixed">
            <!-- Sidebar Start -->
            <?php 
             include '../View/sidebar.php';
             $feature_id=18;
             include '../Includes/viewPermission.php';
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
                            <!-- messages -->
        <div class="container">
        <?php 
            if(isset($_SESSION['customer_update']))
            {
                if($_SESSION['customer_update'] == 0)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Please enter <strong>Customer name & Conact No.</strong>
                    </div>
                    <?php 
                }//no entry
                else if($_SESSION['customer_update'] == 1)
                {
                    ?>
                        <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            Please enter <strong>Maximum Credit Amount.</strong>
                        </div>
                        <?php
                }//duplicate entry
                else if($_SESSION['customer_update'] == 2)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Customer <strong>Saved </strong>successfully!
                    </div>
                    <?php
                }//save success
                else if($_SESSION['customer_update'] == 3)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Customer updated </strong>successfully!
                    </div>
                    <?php
                }//update success
                else if($_SESSION['customer_update'] == 4)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Customer cannot be<strong> Deleted</strong> from system.
                    </div>
                    <?php
                }//delete customer
                else if($_SESSION['customer_update'] == 5)
                {
                    ?>

                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Customer <strong> Deleted</strong> successfully!

                    </div>

                    <?php
                }//delete customer
                else
                {
                    ?>
                    <div class="alert alert-danger">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Oops! </strong>something went wrong!
                    </div>
                    <?php 
                }//else
                unset($_SESSION['customer_update']);
            }//session set
            ?>
        </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title fw-semibold">
                                Customers List
                                <?php 
                                if($userType==1 || $create==1)
                                {
                                    ?>
                                    <button type="button" class="btn btn-primary rounded-pill float-end" id="btn_Add_Customer_modal" data-bs-dismiss="modal"><small>Add New Customer</small></button>
                                    <?php
                                }
                                ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table class="table search-table align-middle text-nowrap" id="tbl_customer">
                                        <thead class="header-item">  
                                            <tr>
                                                <th>Customer No</th>
                                                <th>Customer Name</th>                                                 
                                                <th>Address</th>                                                 
                                                <th>Contact</th>      
                                                <th>Gender</th>  
                                                <th>Date of Birth</th> 
                                                <th>Credit Amount</th>                                           
                                                <th>Max Credit Amount</th>   
                                                <th>Status</th>                                                                                         
                                                <th>Action</th>
                                            </tr>                                        
                                        </thead>
                                        <tbody>
                                            
                                            <?php
                                                $shop_id = $_SESSION['shop_id'];
                                                $CusObj = new Customer();
                                                $dbObj = new DBTransactions();
                                                //get company stat
                                                $sql = "SELECT * FROM shop
                                                INNER JOIN company ON company.CMID = shop.Company_CMID
                                                WHERE SHID = ".$shop_id.";";

                                                $sql_1 = "SELECT SUM(DebitAmount) AS TotalDebit, SUM(CreditAmount) AS TotalCredit  
                                                FROM creditcustomer 
                                                WHERE Customers_CTID = ".$Cus_Id." AND CreditStat=1;";

                                                $credit_total = 0;
                                                $debit_total = 0;
                                                $cust_credit = 0;

                                                $creditData = $dbObj->getData($sql_1);

                                                if(!empty($creditData))
                                                {
                                                    foreach($creditData as $row)
                                                {

                                                $credit_total = floatval($row['TotalCredit']);
                                                $debit_total = floatval($row['TotalDebit']);
                                                }
                                                $cust_credit = $credit_total - $debit_total;
                                                }

                                                if ($cust_credit > 0) 
                                                {
                                
                                                } elseif ($cust_credit < 0) {
                                                $excessAmount = abs($cust_credit);
                                                }

                                                $shopData = $dbObj->getData($sql);
                                                $multi_category = floatval($shopData[0]['is_multicategory']);
                                                $company_id = floatval($shopData[0]['CMID']);
                                                $CustData = $CusObj->getCustomers($shop_id,$multi_category,$company_id); 

                                                foreach($CustData as $row)
                                                {
                                                    ?>
                                                    <tr data-id="<?php echo $row['CTID'];?>">
                                                        <td>
                                                            <?php echo $row['CustomerNo'];?>
                                                        </td>
                                                        <td>
                                                            <?php echo $row['CustName'];?> 
                                                        </td>              
                                                        <td>
                                                            <?php echo $row['CustAddress'];?>
                                                        </td>   
                                                        <td>
                                                            <?php echo $row['CustContact'];?>
                                                        </td> 
                                                        <td> 
                                                            <?php 
                                                            $gender = floatval($row['CustGender']) == 1 ? "Male" : "Female";
                                                            echo $gender;
                                                            ?>

                                                        </td> 
                                                        <td>
                                                            <?php echo $row['CustDOB'];?>
                                                        </td> 
                                                        <td>
                                                        <?php echo $cust_credit; ?>                                                       
                                                     </td>
                                                        <td>
                                                            <?php echo $row['MaxCreditAmount'];?> 
                                                        </td>                                                      
                                                        <td>
                                                            <?php 
                                                            if($row['CustStat']== 1)
                                                            {
                                                                ?>
                                                                <p class="text-primary" style="font-weight: bold;"><i class="ti ti-check"></i> Active</p>
                                                                <?php 
                                                            }
                                                            else
                                                            {
                                                                ?>
                                                                <p class="text-danger" style="font-weight: bold;"><i class="ti ti-x"></i> Inactive</p>
                                                                <?php 
                                                            }
                                                            ?>
                                                        </td> 
                                                        <td>
                                                            <?php 
                                                            if($userType==1 || $edit==1 || $delete==1)
                                                            {
                                                                ?>
                                                                <input type="hidden" name="" id="customerid" value="<?php echo $row['CTID']?>">
                                                                <input type="hidden" name="" id="CustStat" value="<?php echo $row['CustStat']?>">
                                                                <?php
                                                            }
                                                            if($userType==1 || $edit==1)
                                                            {
                                                                ?>
                                                                <button type="button" class="btn border border-primary btn_edit_customer">
                                                                    <i class="ti ti-edit"></i>
                                                                </button>
                                                                <?php
                                                            }
                                                            
                                                            if($userType==1 || $delete==1)
                                                            {
                                                                ?>
                                                                <button type="button" class="ms-2 btn border border-danger btn_delete_customer">
                                                                    <i class="ti ti-x"></i>
                                                                </button>
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
        </div>
    </div>
    <!-- footer Start  -->
    <?php include '../View/footer.php';?> 
    <!-- footer End  -->
    <script src="../Assets/jquery/Customer.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>     
    
    <script>
        $(document).ready(function(){
            $("#tbl_customer").DataTable({
                paging: true,
                lengthChange: true,
                searching: true,
                // pageLength: 50,
            });
        });
    </script>
</body>
</html>


