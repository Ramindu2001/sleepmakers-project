<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';
$slrtObj=new Sales_return_class();
?>

<!doctype html>
<html lang="en">

<head>
  <?php 
  include '../View/head.php';
  $start=null;
  $end=null;
  $shop_id = $_SESSION['shop_id'];
  $user_id=$_SESSION['user_id'];
  $userObj = new User();
  $shopObj= new Shop();
  $user = $userObj->getOneUser($user_id);
  $userType=$user[0]["UserType"];
  $customer=null;
  if(isset($_POST["start"]) && isset($_POST["end"]) && isset($_POST["shop_id"]))
  {
    $start=$_POST["start"];
    $end=$_POST["end"];
    $shop_id=$_POST["shop_id"];
  }
  if(isset($_POST["customer"]))
  {
    $customer=$_POST["customer"];
  }
  $salereturns=$slrtObj->SelectAllFromSalesReturn($shop_id, $start, $end,$customer);
  $shops=$slrtObj->selectShop($userType,$user_id);
  $shopData=$shopObj->getOneShop($shop_id);
  ?>
  <style>
    .card-header 
    {
        background: #fff;
        border-bottom: 1px solid #f5f5f5;
        padding: 10px;
    }
    .accordion-button:not(.collapsed)
    {
        background-color: #5C86FF;
        color: #fff !important;
    }
    .accordion-button:not(.collapsed):after
    {
        color: #fff !important;
    }
    .accordion 
    {
        padding: 0 !important;
    }
  </style>
</head>

<body>
<!--  Body Wrapper -->
<div class="h-100vh">
<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    <!-- Sidebar Start -->
    <?php 
    include '../View/sidebar.php';
    $feature_id=10;
    include '../Includes/viewPermission.php';
    ?>
    <!--  Sidebar End -->
    <!--  Main wrapper -->
    <div class="body-wrapper">
        <!--  Header Start -->
        <?php 
        include '../View/header.php';
        include "../View/modals/main-category.php";
        ?>
        <!--  Header End -->

        <div class="container-fluid">
            <!-- messages -->
        <div class="container">
        <?php 
            if(isset($_SESSION['salesreturn_update']))
            {
                if($_SESSION['salesreturn_update'] == 0)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        No Results Found, <strong>Please Try Again.</strong> 
                    </div>
                    <?php 
                }//no date
                else if($_SESSION['salesreturn_update'] == 1)
                {
                    ?>
                        <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>Invoice Already Returned</strong>
                        </div>
                        <?php
                }//no invoice
                else if($_SESSION['salesreturn_update'] == 2)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Item/Invoice Returned </strong>Successfully!
                    </div>
                    <?php
                }//save success
                else if($_SESSION['salesreturn_update'] == 3)
                {
                    ?>
                    <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Please select a <strong>GRN</strong> to add items
                    </div>
                    <?php
                }//update success
                else if($_SESSION['salesreturn_update'] == 4)
                {
                    ?>
                        <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>No Product Found</strong>
                        </div>
                        <?php
                }//no invoice
                else if($_SESSION['salesreturn_update'] == 6)
                {
                    ?>
                        <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>No Sale Was Made For The Product</strong>
                        </div>
                        <?php
                }//no invoice
                else
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Oops! </strong>something went wrong!
                    </div>
                    <?php 
                }//else
                unset($_SESSION['salesreturn_update']);
            }//session set
            ?>

        </div>
            <div class="row">
                <div class="accordion col-md-12" id="accordionExample">
                    <div class="accordion-item">
                        <h2 class="accordion-header " id="headingTwo">
                            <button class="accordion-button <?php if(isset($_POST["start"])){ } else{ echo"collapsed";}?> " type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo" style="color: #000;">
                                <strong>Filteration</strong>
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse <?php if(isset($_POST["start"])){ echo"collapse show";} else{ echo"collapse";}?> " aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <form action="" method="post">
                                    <div class="d-none">
                                        <input type="hidden" name="" class="d-none" id="shop_name" value="<?=$shopData[0]["ShopName"]?>">
                                        <input type="hidden" name="" class="d-none" id="shop_address_one" value="<?=$shopData[0]["AddressLineOne"]?>">
                                        <input type="hidden" name="" class="d-none" id="shop_address_two" value="<?=$shopData[0]["AddressLineTwo"]?>">
                                        <input type="hidden" name="" class="d-none" id="shop_city" value="<?=$shopData[0]["City"]?>">
                                        <input type="hidden" name="" class="d-none" id="shop_number" value="<?=$shopData[0]["PhoneNumber"]?>">
                                        <input type="hidden" name="" class="d-none" id="start" value="<?=$start?>">
                                        <input type="hidden" name="" class="d-none" id="end" value="<?=$end?>">
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mt-2">
                                            <label for="start" class="form-label">From Date <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <a href="javascript:void(0)" class="input-group-text">
                                                    <i class="ti ti-calendar"></i>
                                                </a>
                                                <input type="date" name="start" id="start" class="form-control" value="<?=$start?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mt-2">
                                            <label for="end" class="form-label">To Date <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <a href="javascript:void(0)" class="input-group-text">
                                                    <i class="ti ti-calendar"></i>
                                                </a>
                                                <input type="date" name="end" id="end" class="form-control" value="<?=$end?>" required>
                                            </div>                                    
                                        </div>
                                        <div class="col-md-6 mt-2">
                                            <label for="search-customers" class="form-label">Search Customer</label>
                                            <div class="input-group">
                                                <a href="javascript:void(0)" class="input-group-text">
                                                    <i class="ti ti-user"></i>
                                                </a>
                                                <select name="customer" id="search-customers" class="form-select">
                                                    <option value="">Common Customer</option>
                                                </select>
                                            </div> 
                                        </div>
                                        <div class="col-md-6 mt-2">
                                            <label for="select-shop" class="form-label">Select Shop <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <a href="javascript:void(0)" class="input-group-text">
                                                    <i class="ti ti-home"></i>
                                                </a>
                                                <select name="shop_id" id="select-shop" class="form-select" required>
                                                    <?php 
                                                    foreach($shops AS $row)
                                                    {
                                                        ?>
                                                        <option value="<?=$row["SHID"]?>" <?php 
                                                        if($row["SHID"]==$shop_id)
                                                        {
                                                            echo "selected";
                                                        }
                                                        ?>><?=$row["ShopName"]?></option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </div> 
                                        </div>
                                        <div class="col-md-12 mt-3 d-flex justify-content-center">
                                                <input type="submit" value="Filter" class="btn btn-primary me-2">
                                                <a href="../Public/salesReturn.php" class="btn btn-danger" id="clearFilter">Clear Filter</a>
                                        </div>
                                    </div>
                                </form> 
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 card p-2 mt-3">
                    <div class="card-header d-flex align-items-center gap-2">
                        <h5 class="card-title fw-semibold mb-3">Sales Return</h5>
                        <a href="../Public/create-sales-return.php" class="btn btn-primary ms-auto me-5"><i class="ti ti-plus"></i> Create New</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive mt-3">
                            <table class="table" id="tbl_sales_return">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Return No</th>
                                        <th>Invoice No</th>
                                        <th>Return Date</th>
                                        <th>Return Status</th>
                                        <th>Return Type</th>
                                        <th>Customer</th>
                                        <th>Total Amount</th>
                                        <th>Reason</th>
                                        <th>Item Count</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // echo $salereturns;
                                    $i=1;
                                    foreach ($salereturns as $row) 
                                    {
                                        ?>
                                        <tr>
                                            <td><?=$i?></td>
                                            <td><?=$row["return_no"]?></td>
                                            <td><?=$row["BillNo"]?></td>
                                            <td><?=$row["EffectiveDate"]?></td>
                                            <td><?php 
                                            if($row["return_header_stat"]==1)
                                            {
                                                ?>
                                                <span class="badge bg-primary">Returned</span>
                                                <?php
                                            }
                                            else
                                            {
                                                ?>
                                                <span class="badge bg-warning">Pending</span>
                                                <?php
                                            }
                                            ?></td>
                                            <td><?=$row["SRT_Name"]?></td>
                                            <td><?=$row["CustName"]?></td>
                                            <td><?=$row["return_amount"]?></td>
                                            <td><?=$row["reason"]?></td>
                                            <td><?=$row["return_count"]?></td>
                                            <td>
                                                <a href="javascript:void(0)" data-rihid="<?=$row["RIHID"]?>" class="printRIHID btn btn-primary me-2">
                                                    <i class="ti ti-printer"></i>
                                                </a>
                                                <a href="../Public/ReturnEdit.php?RIHID=<?=$row["RIHID"]?>" class="btn btn-primary">
                                                    <i class="ti ti-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php    
                                        $i++;
                                    }
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
<div id="print"></div>
<!--  Body Wrapper End -->
    <!-- footer Start  -->
    <?php include '../View/footer.php';?> 
    <!-- footer End  -->
    <script src="../Assets/jquery/salesReturn.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    




</body>
</html>