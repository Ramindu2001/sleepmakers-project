<?php 
include "../Includes/includes.php";
include '../Includes/authcheck.php';
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
                        <div class="card-header">
                            <h5>Supplier Credits</h5>
                            <div style="display: flex; justify-content: flex-end;">
                                <button class="btn btn-success" onclick="generatePDF()">Download PDF</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <input type="hidden" name="supplier_id" id="supplier_id"
                                            value="<?=$supplier_id?>">
                                        <table class="table" id="tbl_suppier_credit">
                                            <thead class="header-item">
                                                <tr>
                                                    <th>SL</th>
                                                    <th>Date</th>
                                                    <th>Invoice No</th>
                                                    <th class="text-end">GRN Amount</th>
                                                    <th class="text-end">Credit Amount</th>
                                                    <th class="text-end">Settled Amount</th>
                                                    <th class="text-end">Total Due</th>
                                                    <th class="text-end">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                        $dbObj = new DBTransactions();

                                        $sql = "SELECT *, SUM(creditsupplier.DebitAmount) AS totalCredit,SUM(grnheader.SuppPayment) AS SuppPayment FROM `creditsupplier` 
                                        INNER JOIN grnheader ON grnheader.GHID = creditsupplier.invoice_header_id AND grnheader.GRNStat=2
                                        WHERE Supplier_ID = '$supplier_id' GROUP BY grnheader.GHID;";

                                        $creditData = $dbObj->getData($sql);
                                        $i=1;
                                        $amount=0;
                                        foreach($creditData as $row)
                                        {
                                            $GHID=$row["GHID"];
                                            $sql1="SELECT sum(TransferAmount) AS TotalPaid FROM `suppliertransactions` 
                                                    WHERE GRNHeader_GHID=$GHID AND TransactionStat=1";
                                            $totalpaid = $dbObj->getData($sql1);
                                            $totalpaid = $totalpaid[0]["TotalPaid"];
                                            $pending = floatval($row['TotalPurchasePrice']) - floatval($row['DebitAmount']);
                                            $balance = floatval($row['totalCredit']) - floatval($totalpaid);
                                            $row_style = "";
                                            if($balance > 0)
                                            {
                                                $row_style = "style='color: firebrick;'";
                                            }
                                            else if($balance == 0)
                                            {
                                                $row_style = "style='color: black;'";
                                            }
                                            else
                                            {
                                                $row_style = "style='color: lime;'";
                                            }
                                            if(isset($row['totalCredit']))
                                            {
                                                $totalCredit=$row['totalCredit'];
                                            }
                                            else
                                            {
                                                $totalCredit=0;
                                            }
                                            ?>
                                                <tr data-id="<?php echo $row['SCID'];?>" <?php echo $row_style;?>>
                                                    <td><?= $i;?></td>
                                                    <td><?= $row['EffectiveDate'];?></td>
                                                    <td><?= $row['GRNHeaderNo'];?></td>
                                                    <td class="text-end"><?= $row['TotalPurchasePrice'];?></td>
                                                    <td class="text-end"><?= number_format((float)$totalCredit, 2, '.', '');?></td>

                                                    <td class="text-end">
                                                        <?= number_format((float)$totalpaid, 2, '.', '');?>
                                                    </td>
                                                    <td class="text-end">
                                                        <?= number_format((float)$balance, 2, '.', '');?>
                                                    </td>
                                                    <td>
                                                        <button type="button"
                                                            class="btn border-primary float-end btn_credit_payment">Pay</button>
                                                    </td>
                                                </tr>
                                                <?php
                                            $i+=1;
                                            $amount += floatval($row['Balance']);
                                        }//foreach

                                        ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="6" class="text-end"><b>Total Due</b></td>
                                                    <td class="text-end">
                                                        <b><?=number_format((float)$amount, 2,'.',',')?></b>
                                                    </td>
                                                </tr>
                                            </tfoot>
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


    <script src="../Assets/jquery/supplier_credit2.js"></script>
    <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
</body>

</html>



<!-- // Function to handle adding cheque settlements
function addChqsettlement() {
    var settlement = `
        <div id="chequeSettlementDiv" class="chequeSettlementDiv col-md-12 mb-2 row">
            <div class="col-md-12">
                <label for="settlementDetails" class="form-label">Settlement Details</label>
                <input type="text" name="settlementDetails" id="settlementDetails" class="form-control" placeholder="Enter settlement details">
            </div>
        </div>`;
    $("#payment_method").append(settlement);
} -->