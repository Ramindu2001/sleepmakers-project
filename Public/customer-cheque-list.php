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
        <div class="body-wrapper">
            <!--  Header Start -->
            <?php 
                include '../View/header.php';
            ?>
            <!--  Header End -->
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h5>Customer Cheque List</h5>
                    </div>
                    <div class="card-body">
                        <label for="search_invoice" class="form-label">Search </label>
                        <input type="text" id="search_invoice" class="form-control" placeholder="Invoice No / Cheque No / Customer Name / Bank / .....">

                        <div>
                            <table id="tbl_invoice_list">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Effective Date Time</th>
                                        <th>Cheque Realize Date</th>
                                        <th>System Cheque No</th>
                                        <th>Cheque No</th>
                                        <th>Invoice No</th>
                                        <th>Cheque Type</th>
                                        <th>Status</th>
                                        <th>Transfered To</th>
                                        <th>Customer Name</th>
                                        <th>Bank</th>
                                        <th class="text-end">Cheque Amount</th>
                                        <th class="text-center"><i class="ti ti-edit"></i></th>
                                    </tr>   
                                </thead>
                                <tbody>
                                    <?php 
                                        $shop_id = $_SESSION['shop_id'];
                                        $sql = "SELECT * FROM `custcheq` cc
                                                INNER JOIN custchqdetail ccd ON cc.CCQID=ccd.CCQID
                                                INNER JOIN customers c ON c.CTID=cc.cust_CTID
                                                INNER JOIN invoiceheader i ON i.IHID=cc.invoiceID
                                                WHERE cc.shop_SHID='$shop_id' ORDER BY cc.chq_no DESC ;";
                                        $dbObj = new DBTransactions();
                                        $invData = $dbObj->getData($sql);
                                        $i=1;
                                        foreach($invData as $row)
                                        {
                                            ?>
                                            <tr>
                                                <td><?=$i?></td>
                                                <td><?=$row["effectiveDate"]?></td>
                                                <td><?=$row["chqDate"]?></td>
                                                <td><?=$row["chq_no"]?></td>
                                                <td><?=$row["chqNo"]?></td>
                                                <td><?=$row["BillNo"]?></td>
                                                <td><?php if ($row["type"]==1){?>
                                                    <span class="text-danger" style="font-weight:700;"><i class="ti ti-outbound" style=" display:inline-block; transform:rotate(311deg);"></i> Issued Cheque</span><?php } else{?><span style="font-weight:700; color:green;"> <i class="ti ti-outbound" style=" display: inline-block; transform: rotate(133deg); "></i> Received Cheque </span>  <?php }?></td>
                                                <td><?php 
                                                if($row["chq_stat"]==0)
                                                {
                                                    ?>
                                                    <span class="badge bg-danger">Inactive</span>
                                                    <?php
                                                }
                                                elseif($row["chq_stat"]==1)
                                                {
                                                    ?>
                                                    <span class="badge bg-success">Active</span>
                                                    <?php
                                                }
                                                elseif($row["chq_stat"]==2)
                                                {
                                                    ?>
                                                    <span class="badge bg-warning">Transfered</span>
                                                    <?php
                                                }
                                                elseif($row["chq_stat"]==3)
                                                {
                                                    ?>
                                                    <span class="badge bg-danger">Bounced</span>
                                                    <?php
                                                }
                                                elseif($row["chq_stat"]==4)
                                                {
                                                    ?>
                                                    <span class="badge bg-success">Realized</span>
                                                    <?php
                                                }
                                                ?></td>
                                                <td>
                                                    <?php 
                                                        $shop_id = $_SESSION['shop_id'];
                                                        $tranferedFrom=$row["CCQID"];
                                                        $sql = "SELECT * FROM supcheq sc
                                                                INNER JOIN supchqdetail scd ON sc.SCQID=scd.SCQID
                                                                INNER JOIN suppliers s ON s.SPID=sc.sup_SPID
                                                                INNER JOIN grnheader g ON g.GHID=sc.GRNHeader_GHID
                                                                WHERE sc.transferedFrom='$tranferedFrom' ORDER BY sc.chq_no DESC;";
                                                        $dbObj = new DBTransactions();
                                                        $invData = $dbObj->getData($sql);
                                                        if(isset($invData[0]["chq_no"]))
                                                        {
                                                            echo $invData[0]["chq_no"]." <br> ".$invData[0]["SupplierName"]." <br> ".$invData[0]["chqNo"];
                                                        }
                                                        
                                                    
                                                    ?>
                                                </td>
                                                <td><?=$row["CustName"]?></td>
                                                <td><?=$row["bank"]?></td>
                                                <td class="text-end"><?=number_format($row["chqAmount"],2,".",",")?></td>
                                                <td class="text-center">
                                                    <?php
                                                    if($row["chq_stat"]==1 || $row["chq_stat"]==2)
                                                    {
                                                        ?>
                                                        <a href="../Public/edit-cheque.php?type=2&chq=<?=$row["CCQID"]?>" class="btn btn-primary"><i class="ti ti-edit"></i></a>
                                                        <?php
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php 
                                            $i++;
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
    <!-- footer Start  -->
    <?php include '../View/footer.php';?> 
    <!-- footer End  -->
    <script>
        
        var $rows = $('#tbl_invoice_list tbody tr');
        $('#search_invoice').keyup(function() {
            var val = $.trim($(this).val()).replace(/ +/g, ' ').toLowerCase();

            $rows.show().filter(function() {
                var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
                return !~text.indexOf(val);
            }).hide();
        });
    $(function () {
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
    </script>
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


