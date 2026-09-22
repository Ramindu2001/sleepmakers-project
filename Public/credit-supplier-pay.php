<?php 
include "../Includes/includes.php";
include '../Includes/authcheck.php';
$shop_id = $_SESSION['shop_id'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php 
    $supplier_id = 0;
    if(isset($_GET['sup_id']))
    {
        $supplier_id = $_GET['sup_id'];
    }//supplier id
    else
    {
        $_SESSION["credit"]=5;
        header("Location:../Public/credit-supplier.php");
    }

 include '../View/head.php';
 // include '../View/loader.php';
  ?>
    <style>
    .table>:not(caption)>*>* {
        padding: 10px;
    }

    input[readonly] {
        background-color: rgb(235, 235, 235);
        border-color: rgb(235, 235, 235);
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
            $feature_id=66;
            include '../Includes/editPermission.php';
        ?>
            <!--  Sidebar End -->
            <!--  Main wrapper -->
            <div class="body-wrapper">
                <!--  Header Start -->
                <?php 
                include '../View/header.php';
                include "../View/modals/supplier_payment.php";
            ?>
                <!--  Header End -->

                <div class="container-fluid">
                    <?php 
                if(isset($_SESSION["credit_customer"]))
                {
                    if($_SESSION["credit_customer"]==0)
                    {
                        ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show"
                        role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"
                            aria-label="Close"></button>
                        <strong>Oops! </strong> Something went wrong, Please try again!
                    </div>
                    <?php
                    }
                    elseif ($_SESSION["credit_customer"]==1) 
                    {
                        ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show"
                        role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"
                            aria-label="Close"></button>
                        <strong>Success </strong> Payment Added Successfully!
                    </div>
                    <?php
                    }
                    unset($_SESSION["credit_customer"]);
                }
                ?>
                    <!-- <button type="button" class="btn btn-primary rounded-pill ml-1 mb-2" id="btn_Add_SysFeature_modal" data-bs-dismiss="modal">Add New Feature</button> -->

                    <div class="card">
                        <div class="card-header" style="display: flex; justify-content: center;">
                            <h5 style="width:49%;">Supplier Credits</h5>
                            <div style="display: flex; justify-content: flex-end;width:49%;">
                                <button class="btn btn-success" onclick="generatePDF()">Download PDF</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive" >
                                        <input type="hidden" name="supplier_id" id="supplier_id"
                                            value="<?=$supplier_id?>">
                                        <table class="table" id="tbl_suppier_credit" style="height:500px; overflow-y:auto;">
                                            <thead class="header-item">
                                                <tr>
                                                    <th>SL</th>
                                                    <th>GRN Date</th>
                                                    <th>GRN No</th>
                                                    <th>GRN Amount</th>
                                                    <th class="text-end">Credit Amount (Rs.)</th>
                                                    <th class="text-end">Settled Amount (Rs.)</th>
                                                    <th class="text-end">Due Amount (Rs.)</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php 
                                                $dbObj = new DBTransactions();
                                                $sql = "SELECT gh.GHID,gh.GRNHeaderNo,gh.TotalPurchasePrice, gh.EffectiveDate AS GRNdate,SUM(cs.DebitAmount) AS DebitAmount, SUM(cs.CreditAmount) AS CreditAmount, cs.SCID FROM `creditsupplier` cs
                                                LEFT JOIN grnheader gh ON gh.GHID=cs.invoice_header_id
                                                WHERE cs.Supplier_ID='$supplier_id' AND cs.CreditStat=1 AND cs.shop_SHID='$shop_id' GROUP BY cs.Supplier_ID,cs.invoice_header_id;";
                                                $suppData = $dbObj->getData($sql);
                                                $i=1;
                                                $Totalbalance=0;
                                                $count=count($suppData);
                                                if($count > 0)
                                                {
                                                    foreach ($suppData as $key => $value) 
                                                    {
                                                        $balance=$value["DebitAmount"]-$value["CreditAmount"];
                                                        $Totalbalance=$Totalbalance+$balance;
                                                        // Check if CreditAmount is null or 0 before formatting
                                                        $CreditAmount = !empty($value["CreditAmount"]) ? number_format($value["CreditAmount"], 2, '.', ',') : '';
                                                        
                                                        // Check if DebitAmount is null or 0 before formatting
                                                        $DebitAmount = !empty($value["DebitAmount"]) ? number_format($value["DebitAmount"], 2, '.', ',') : '';
                                                        $GRNdate="N/A";
                                                        $GRNHeaderNo="N/A";
                                                        $TotalPurchasePrice="N/A";
                                                        if(isset($value["GRNdate"]))
                                                        {
                                                            $GRNdate=$value["GRNdate"];
                                                        }
                                                        if(isset($value["GRNHeaderNo"]))
                                                        {
                                                            $GRNHeaderNo=$value["GRNHeaderNo"];
                                                        }
                                                        if(isset($value["TotalPurchasePrice"]))
                                                        {
                                                            $TotalPurchasePrice=$value["TotalPurchasePrice"];
                                                        }
                                                        ?>
                                                        <tr data-id="<?=$value["SCID"]?>">
                                                            <td>
                                                                <div class="d-none">
                                                                    <input type="hidden" name="" id="GHID" value="<?=$value["GHID"]?>">
                                                                    <input type="hidden" name="" id="supplier_id" value="<?=$supplier_id?>">
                                                                </div>
                                                                <?=$i?>
                                                            </td>
                                                            <td><?=$GRNdate?></td>
                                                            <td><?=$GRNHeaderNo?></td>
                                                            <td><?=$TotalPurchasePrice?></td>
                                                            <td class="text-end"><?=$DebitAmount ?></td>
                                                            <td class="text-end"><?= $CreditAmount?></td>
                                                            <td class="text-end"><?= number_format($balance, 2, '.', ',') ?></td>
                                                            <td><button class="supplier_pay btn btn-primary" id="supplier_pay">Pay</button></td>
                                                        </tr>
                                                        <?php
                                                        $i++;
                                                    }
                                                }
                                                else
                                                {
                                                    ?>
                                                    <tr>
                                                        <td colspan="8">
                                                            <span class="w-100 d-block text-danger text-center">No Results Found</span>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                                
                                            ?>                                                 
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="6" class="text-end"><b>B/C/F</b></td>
                                                <td class="text-end">
                                                    <?=number_format($Totalbalance, 2, '.', ',')?>
                                                </td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                        </table>
                                          </div>
                                             <div class="table-responsive">
                                        <table class="table">
                                            
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
    <div id="hidden"></div>
    <!-- footer Start  -->
    <?php include '../View/footer.php';?>
    <!-- footer End  -->


    <script>
    function addChqsettlement() {
        var payment = `
                        <div id="chequeDivS" class="chequeDivS col-md-12 mb-2 row">
                                <div class="col-md-8">
                                    <label for="transferCheque" class="form-label">Select Cheque</label>
                                    <select class="transferCheque form-select" name="transferCheque[]" id="transferCheque">
                                    <option value="" > Select Cheque</option>
                                    <?php 
                                   $sql = "SELECT * FROM custcheq
                                   INNER JOIN custchqdetail ON custchqdetail.CCQID = custcheq.CCQID
                                   INNER JOIN customers c ON c.CTID=custcheq.cust_CTID
                                   WHERE custcheq.shop_SHID = ".$shop_id." AND custcheq.chq_stat=1;";
                           $dbObj = new DBTransactions();
                           $dbPaymethods = $dbObj->getData($sql);
                           foreach($dbPaymethods as $row) {
                           ?>
                               <option value="<?=$row["CCQID"]?>" data-amount="<?=$row["chqAmount"]?>">
                                   <?=$row["bank"]?> - <?=$row["chqNo"]?> - <?=$row["CustName"]?> - <?=$row["chqAmount"]?>
                               </option>
                           <?php } ?>
                       </select>
                       <input type="hidden" class="cheque_id" id="cheque_id">
                                </div>
                                <div class="col-md-4">
                                <a href='javascript:void(0);' class='remove-payment btn btn-danger mt-4' id='remove-payment' onclick='remove_cheque(this)' ><i class='ti ti-trash'></i></a>
                                <a href='javascript:void(0);' class='btn btn-primary mt-4' onclick='addChqsettlement()' ><i class='ti ti-plus'></i></a>
                                </div>
                            </div>
                            `;
        $("#payment_method").append(payment);
    }
            function generatePDF() {
                var supplierId = <?php echo $supplier_id; ?>;
                
                window.location.href = 'supplierduepdf.php?id=' + supplierId;
            }

    $(document).ready(function() {
        $(document).on("change", ".transferCheque", function() {
            var selectedValue = $(this).val();
            var isDuplicate = false;
            if (selectedValue != "") {
                $('.transferCheque').not(this).each(function() {
                    if ($(this).val() === selectedValue) {
                        isDuplicate = true;
                        return false; // break out of the loop
                    }
                });

                if (isDuplicate) {
                    alert('Duplicate value selected!');
                    $(this).val("");
                }
            }
        });
        $(document).on("change", ".transferCheque", function() {
            var selectedOption = $(this).find(":selected");
            var chequeAmount = selectedOption.data("amount");
            $(this).closest(".chequeDivS").find(".cheque_id").val(chequeAmount);
        });
        $(document).on("change", ".transferCheque", function() {
            updateTotalChequeAmount();
        });

        $(document).on("click", ".remove-payment", function() {
            $(this).closest(".remove-payment").remove();
            updateTotalChequeAmount();
        });

        function updateTotalChequeAmount() {
            var totalAmount = 0;
            // Iterate over all selected cheques and sum their amounts
            $(".transferCheque").each(function() {
                var selectedOption = $(this).find(":selected");
                var chequeAmount = parseFloat(selectedOption.data("amount")) || 0;
                totalAmount += chequeAmount;
            });

            // Update the Paying Amount input field
            $("#credit_payment").val(totalAmount.toFixed(2));

            // Optionally, update a hidden field if required
            $("#hidden_total_amount").val(totalAmount.toFixed(2));
        }

        $(document).on("change", ".cmb_paymethod", function() {
            var selectedValue = $(this).val();
            if (selectedValue != "") {
                $("#credit_payment").val(" ");
                if (selectedValue == 5) {
                    $("body .chequeDivS").each(function() {
                        $(this).remove()
                    });
                    var payment = `
                <div id="chequeDiv" class="payment-method chequeDiv col-md-12 mb-2 row">
                    <div class="col-md-6">
                        <label for="chqNo" class="form-label">Cheque No</label>
                        <input type="text" name="chqNo[]" id="chqNo" class="form-control" placeholder="Cheque No">
                    </div>
                    <div class="col-md-6">
                        <label for="chqdate" class="form-label">Cheque Date</label>
                        <input type="date" name="chqdate[]" id="chqdate" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="chqbank" class="form-label">Bank</label>
                        <input type="text" name="chqbank[]" id="chqbank" class="form-control" placeholder="Ex: BOC">
                    </div>
                    <div class="col-md-4">
                        <label for="chqamount" class="form-label">Cheque Amount</label>
                        <input type="text" name="chqAmount[]" id="chqamount" class="form-control" placeholder="Ex: 2000">
                    </div>
                    <div class="col-md-4">
                        <a href="javascript:void(0);" class="remove-payment btn btn-danger mt-4" id="remove-payment" onclick="remove_cheque(this)"><i class="ti ti-trash"></i></a>
                        <a href="javascript:void(0);" class="btn btn-primary mt-4" onclick="addChq()"><i class="ti ti-plus"></i></a>
                    </div>
                </div>`;
                    $("#payment_method").append(payment);
                } else if (selectedValue == 13) {
                    $("body .payment-method").each(function() {
                        $(this).remove()
                    });
                    addChqsettlement();
                } else {
                    $("body .chequeDivS").each(function() {
                        $(this).remove()
                    });
                    $("body .payment-method").each(function() {
                        $(this).remove()
                    });
                }
            }
        });
    });

   
    </script>


    <script src="../Assets/jquery/supplier_credit.js"></script>
    <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
</body>

</html>


