<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';
?>

<!doctype html>
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
    $feature_id=14;
    include '../Includes/viewPermission.php';
    ?>
    <!--  Sidebar End -->
    <!--  Main wrapper -->
    <div class="body-wrapper">
        <!--  Header Start -->
        <?php 
        include '../View/header.php';
        include "../View/modals/main-category.php";
        include "../View/modals/main-category.php";
        ?>
        <!--  Header End -->

        <div class="container-fluid">
        <!-- messages -->
        <div class="container">
        <?php 
            if(isset($_SESSION['category_update']))
            {
                if($_SESSION['category_update'] == 0)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Category name empty.</strong>
                    </div>
                    <?php 
                }  //no entry
                else if($_SESSION['category_update'] == 1)
                {
                    ?>
                        <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            <strong>Category name already exists</strong> in database.
                        </div>
                        <?php
                }  //duplicate entry
                else if($_SESSION['category_update'] == 2)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Category created </strong>successfully!
                    </div>
                    <?php
                }//save success
                else if($_SESSION['category_update'] == 3)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Category updated </strong>successfully!
                    </div>
                    <?php
                } //update success
                    else if($_SESSION['category_update'] == 4)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Cannot <strong>Delete</strong>category. 
                    </div>
                    <?php   
                }
                //cannot delete 
                  else if($_SESSION['category_update'] == 5)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Category<strong>deleted </strong>successfully.
                    </div>
                    <?php
                } //cannot delete 
                else
                {
                    ?>
                    <div class="alert alert-danger">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Oops! </strong>something went wrong!
                    </div>
                    <?php 
                } //else
                unset($_SESSION['category_update']);
            } //session set
            ?>
        </div>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title fw-semibold" style="margin-top: 0px;">
                        Main Categories
                        <?php 
                        if($userType==1 || $create==1)
                        {
                            ?>
                            <button class="btn btn-primary rounded-pill float-end" id="btn_add_category">
                                <small>
                                    Add Main Category
                                </small>
                            </button>
                            <?php
                        }
                        ?>
                    </h5>
                </div>

                <div class="card-body">
                   
                <div class="container-fluid">
                    <table class="table table-hover" id="tbl_category">
                        <thead>
                        <tr>
                            <td style="display: none;">0</td>
                            <th>No</th>
                            <th>Category No</th>
                            <th>Category Name</th>
                            <th>Barcode Code</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>

                        
                        <?php 
                            $dbObj = new DBTransactions();
                            //get company stat
                            $sql = "SELECT * FROM shop
                            INNER JOIN company ON company.CMID = shop.Company_CMID
                            WHERE SHID = ".$shop_id.";";

                            $shopData = $dbObj->getData($sql);
                            $multi_category = floatval($shopData[0]['is_multicategory']);
                            $company_id = floatval($shopData[0]['CMID']);
                            $catObj = new Category();
                            $catData = $catObj->getCategoryByShop(shop_id: $shop_id,multi: $multi_category,company: $company_id);
                            $count = 0;
                            foreach($catData as $row)
                            {
                                $count += 1;
                                ?>
                                <tr>
                                    <td style="display: none;"><?php echo $row['CTID'];?></td>
                                    <td><?php echo $count;?></td>
                                    <td><?php echo $row['CategoryNo'];?></td>
                                    <td><?php echo $row['CategoryName'];?></td>
                                    <td>
                                        <?php
                                        $cat_code = trim((string) $row['CategoryCode']);
                                        if($cat_code !== "")
                                        {
                                            ?><span class="badge bg-primary-subtle text-primary"><?php echo htmlspecialchars($cat_code, ENT_QUOTES, "UTF-8");?></span><?php
                                        }//has a code
                                        else
                                        {
                                            ?><span class="text-muted fs-2">auto</span><?php
                                        }//derived from the name
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if($userType==1 || $edit==1)
                                        {
                                            ?>
                                            <button type="button" id="btn_category_<?php echo $row['CTID']?>" class="btn border border-primary"><i class="ti ti-edit"></i></button>
                                            <?php
                                        }
                                        ?>
                                        <?php 
                                        if($userType==1 || $delete==1)
                                        {
                                            ?>
                                            <button type="button" id="btn_category_delete_<?php echo $row['CTID']?>" class="btn border border-danger"><i class="ti ti-x"></i></button>
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

    <script src="../Assets/jquery/category.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>

    <script>
        $(document).ready(function(){
            $("#tbl_category").DataTable({
                paging: true,
                lengthChange: true,
                searching: true,
                // pageLength: 50,
            });
        });
    </script>

</body>
</html>