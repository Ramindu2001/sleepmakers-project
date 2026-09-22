<?php
include "../Includes/includes.php";
include '../Includes/authcheck.php';
require_once "../Includes/super_admin.php";
super_admin_page(); //system admin only, decided before any of the page is sent

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
    if (isset($_GET['Fe_Id'])) {
        $Fe_Id = $_GET['Fe_Id'];
        echo "<script>";
        echo "$(document).ready(function(){";
        echo "$('#add-user-features').modal('toggle');";
        echo "});";
        echo "</script>";
    }
    include '../View/modals/add-user-features.php';
    ?>
    <div class="h-100vh">
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
            data-sidebar-position="fixed" data-header-position="fixed">
            <?php include '../View/sidebar.php'; ?>
            <div class="body-wrapper">
                <?php include '../View/header.php'; ?>
                <div class="container-fluid">
                    <h5 class="card-title fw-semibold mb-4">Assign Users to Shops</h5>
                    <p class="mb-3">Each row lets one user into one shop, with the role they hold in that shop. Revoke blocks access but keeps the row and its history.</p>

                    <button type="button" class="btn btn-primary rounded-pill ml-1 mb-2"
                        id="btn_Add_SysFeature_modal">Add New Users</button>
                    <input type="hidden" id="assign_csrf_token" value="<?=htmlspecialchars(csrf_token())?>">
                    <br>
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table class="table search-table align-middle text-nowrap"
                                            id="tbl_active_store">
                                            <thead class="header-item">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Shop</th>
                                                    <th>User</th>
                                                    <th>Role</th>
                                                    <th>Access</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $ShopObj = new AddUsersModels();
                                                foreach ($ShopObj->getAssignedUsers() as $Shop): ?>
                                                    <tr data-suid="<?php echo $Shop['SUID']; ?>" data-shop-id="<?php echo $Shop['shop_SHID']; ?>"
                                                        data-user-id="<?php echo $Shop['user_USID']; ?>" data-role-id="<?php echo $Shop['UserRoles_URID']; ?>">
                                                        <td><?php echo $Shop['SUID']; ?></td>
                                                        <td><?php echo htmlspecialchars($Shop['ShopName']); ?></td>
                                                        <td><?php echo htmlspecialchars($Shop['UserName']); ?></td>
                                                        <td><?php echo $Shop['UserRoleName'] !== null ? htmlspecialchars($Shop['UserRoleName']) : '<span class="text-danger">No role</span>'; ?></td>
                                                        <td>
                                                            <?php if ($Shop['is_active'] == 1) { ?>
                                                                <span class="mb-1 badge text-bg-success">Active</span>
                                                            <?php } else { ?>
                                                                <span class="mb-1 badge bg-danger">Revoked</span>
                                                            <?php } ?>
                                                        </td>
                                                        <td>
                                                            <a href="javascript:void(0)" class="btn-edit-assignment me-2" title="Edit Role"><i class="ti ti-edit"></i></a>
                                                            <?php if ($Shop['is_active'] == 1) { ?>
                                                                <a href="javascript:void(0)" class="btn-set-active text-warning me-2" data-active="0" title="Revoke Access"><i class="ti ti-user-off"></i></a>
                                                            <?php } else { ?>
                                                                <a href="javascript:void(0)" class="btn-set-active text-success me-2" data-active="1" title="Restore Access"><i class="ti ti-user-check"></i></a>
                                                            <?php } ?>
                                                            <a href="javascript:void(0)" class="btn-delete text-danger" title="Delete"><i class="ti ti-trash"></i></a>
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
    <?php include '../View/footer.php'; ?>
    <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/jquery/AddShops.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="../Assets/js/dashboard.js"></script>
</body>

</html>