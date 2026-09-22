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
    <?php
    //load editor
    $Fe_Id = 0;
    if (isset($_GET['Fe_Id'])) {
        $Fe_Id = $_GET['Fe_Id'];
        echo "<script>";
        echo "$(document).ready(function(){";
        echo "$('#add-counters').modal('toggle');";
        echo "});";
        echo "</script>";
    }
    include '../View/modals/add-counters.php';
    ?>

    <div class="h-100vh">
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
            data-sidebar-position="fixed" data-header-position="fixed">
            <?php include '../View/sidebar.php'; ?>
            <div class="body-wrapper">
                <?php include '../View/header.php'; ?>
                <div class="container-fluid">
                    <h5 class="card-title fw-semibold mb-4">Add Counters</h5>

                    <button type="button" class="btn btn-primary border border-success rounded-pill ml-1"
                        id="btn_Add_Counter_modal" data-bs-dismiss="modal">Add Counters</button>
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
                                                    <th>Counter Number</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $CounterObj = new AddCounterModels();
                                                $CounterName = $CounterObj->getCounters();
                                                foreach ($CounterName as $Counter): ?>
                                                    <tr>
                                                        <td><?php echo $Counter['CTID']; ?></td>
                                                        <td><?php echo $Counter['counterNo']; ?></td>
                                                        <td>
                                                            <a class="btn_delete" id="btn_Add_Counter_modal"
                                                                data-suid="<?php echo $Counter['CTID']; ?>">Delete</a>
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
    <script src="../Assets/jquery/AddCounterNo.js"></script>
    <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="../Assets/js/dashboard.js"></script>
</body>

</html>