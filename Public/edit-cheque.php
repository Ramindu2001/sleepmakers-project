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
  <style>
    .table>:not(caption)>*>* 
    {
        padding: 10px;
    }
    td 
    {
        padding: 5px;
    }
    th 
    {
        padding: 10px;
    }
  </style>
</head>
<body>
<?php 
    include '../View/modals/SysFeatures.php';
    if(isset($_GET["type"]) && isset($_GET["chq"]) && ($_GET["type"]==1 || $_GET["type"]==2))
    {
        $type=$_GET["type"]; // type 1 = supplier cheque , type 2 = customer cheque
        $chq=$_GET["chq"];

        if($type==1)
        {
            $sql = "SELECT * FROM supcheq sc
                    INNER JOIN supchqdetail scd ON sc.SCQID=scd.SCQID
                    INNER JOIN suppliers s ON s.SPID=sc.sup_SPID
                    INNER JOIN grnheader g ON g.GHID=sc.GRNHeader_GHID
                    WHERE sc.SCQID='$chq' ORDER BY sc.chq_no DESC;";
                    $dbObj = new DBTransactions();
                    $invData = $dbObj->getData($sql);
                    $chq_no=$invData[0]["chq_no"];
                    $chqNo=$invData[0]["chqNo"];
                    $chqDate=$invData[0]["chqDate"];
                    $bank=$invData[0]["bank"];
                    $chqAmount=$invData[0]["chqAmount"];
                    $chq_stat=$invData[0]["chq_stat"];
        }
        else
        {
            $sql = "SELECT * FROM custcheq sc
                    INNER JOIN custchqdetail scd ON sc.CCQID=scd.CCQID
                    INNER JOIN customers s ON s.CTID=sc.cust_CTID
                    INNER JOIN invoiceheader g ON g.IHID=sc.invoiceID
                    WHERE sc.CCQID='$chq' ORDER BY sc.chq_no DESC;";
                    $dbObj = new DBTransactions();
                    $invData = $dbObj->getData($sql);
                    $chq_no=$invData[0]["chq_no"];
                    $chqNo=$invData[0]["chqNo"];
                    $chqDate=$invData[0]["chqDate"];
                    $bank=$invData[0]["bank"];
                    $chqAmount=$invData[0]["chqAmount"];
                    $chq_stat=$invData[0]["chq_stat"];
        }
    }
    else
    {

    }
?>
<!--  Body Wrapper -->
<div class="h-100vh">
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
        <!-- Sidebar Start -->
        <?php 
            include '../View/sidebar.php';
            $feature_id=56;
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
                <div class="card">
                    <div class="card-header">
                        <h5>Edit Cheque <?=$chq_no?></h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        if($type==1)
                        {
                            ?>
                            <div id="supplier">
                                <form action="../Controller/supplier-cheque.php" method="POST">
                                    <input type="hidden" name="chqid" value="<?=$chq?>">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="" class="form-label">Cheque No</label>
                                            <input type="text" name="chqNo" placeholder="00001" class="form-control" value="<?=$chqNo?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="" class="form-label">Bank</label>
                                            <input type="text" name="bank" class="form-control" placeholder="Eg: Seylan" value="<?=$bank?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="" class="form-label">Cheque Realized Date</label>
                                            <input type="date" name="chqDate" class="form-control" placeholder="dd-mm-yyyy" value="<?=$chqDate?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="" class="form-label">Cheque Amount</label>
                                            <input type="text" name="chqAmount" class="form-control" value="<?=$chqAmount?>" placeholder="Eg: 5000">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="" class="form-label">Cheque Status</label>
                                            <select name="chq_stat" id="" class="form-select">
                                                <option value="">Select Status</option>
                                                <option value="4" <?php 
                                                if($chq_stat==4)
                                                {
                                                    echo "Selected";
                                                }
                                                ?>>Realized Cheque</option>
                                                <option value="3" <?php 
                                                if($chq_stat==3)
                                                {
                                                    echo "Selected";
                                                }
                                                ?>>Bounced Cheque</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 d-flex justify-content-end p-3">
                                            <input type="submit" value="Submit" name="editSupChq" class="btn btn-primary">
                                        </div>
                                    </div>
                                </form>    
                            </div> 
                            <?php
                        }
                        else
                        {
                            ?>
                            <div id="customer">
                                <form action="../Controller/customer-cheque.php" method="POST">
                                    <input type="hidden" name="chqid" value="<?=$chq?>">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="" class="form-label">Cheque No</label>
                                            <input type="text" name="chqNo" placeholder="00001" class="form-control" value="<?=$chqNo?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="" class="form-label">Bank</label>
                                            <input type="text" name="bank" class="form-control" placeholder="Eg: Seylan" value="<?=$bank?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="" class="form-label">Cheque Realized Date</label>
                                            <input type="date" name="chqDate" class="form-control" placeholder="dd-mm-yyyy" value="<?=$chqDate?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="" class="form-label">Cheque Amount</label>
                                            <input type="text" name="chqAmount" class="form-control" value="<?=$chqAmount?>" placeholder="Eg: 5000">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="" class="form-label">Cheque Status</label>
                                            <select name="chq_stat" id="" class="form-select">
                                                <option value="">Select Status</option>
                                                <option value="4" <?php 
                                                if($chq_stat==4)
                                                {
                                                    echo "Selected";
                                                }
                                                ?>>Realized Cheque</option>
                                                <option value="3" <?php 
                                                if($chq_stat==3)
                                                {
                                                    echo "Selected";
                                                }
                                                ?>>Bounced Cheque</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 d-flex justify-content-end p-3">
                                            <input type="submit" value="Submit" name="editCusChq" class="btn btn-primary">
                                        </div>
                                    </div> 
                                </form>  
                            </div> 
                            <?php
                        }
                        ?>                       
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    <!-- footer Start  -->
    <?php include '../View/footer.php';?> 
    <!-- footer End  -->
    <script>
    $(function () {
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
    </script>
    <script src="../Assets/jquery/invoicelist.js"></script>
    <script>
        $(document).ready(function(){
            if ($(window).width() >= 1099) 
            {
                $('.left-sidebar').css("margin-left","-270px");
            }
            $('#headerCollapse2').css("display","block");
            $('#headerCollapse3').css("display","none");
            $('.body-wrapper').css("margin-left","0");
            $("#side-closes").css("display","block");
            $(".app-header").css("width","100%");
    
        });
    </script>
    <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
</body>

</html>


