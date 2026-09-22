<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';

?>

<!doctyincvenpe html>
<html lang="en">

<head>
  <?php 
  include '../View/head.php';
  // include '../View/loader.php';
  $salesreturn=new Sales_return_class;
  if(!isset($_GET['product_id'])||$_GET['product_id']==0||$_GET['product_id']==''||$_GET['product_id']==null)
  {
    $_SESSION['salesreturn_update'] = 5;
    header('Location:./sales-return.php');
  }
  else
  {
    $product_id=$_GET['product_id'];
    $check_product=$salesreturn->check_product_sale($product_id);
    if(count($check_product)==0)
    {
        $_SESSION["salesreturn_update"] = 4;
        header("Location:../Public/sales-return.php");
    }
    else
    {
        $barcode=$check_product[0]["Barcode"];
        $check_barcode_sale=$salesreturn->check_barcode_sale($barcode);
        if(count($check_barcode_sale)==0)
        {
            $_SESSION["salesreturn_update"] = 6;
            header("Location:../Public/sales-return.php");
        }
    }
  }
  ?>
  <style>
    .card-header 
    {
        background: #fff;
        border-bottom: 1px solid #e7e7e7;
        padding: 10px;
    }
    td ,th
    {
        border: 1px solid #e7e7e7;
    }
    .table>:not(caption)>*>*
    {
        padding: 10px !important;
    }
  </style>
</head>

<body>
<!--  Body Wrapper -->
<div class="h-100vh">
<div class="page-wrapper mini-sidebar" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="mini-sidebar" data-sidebar-position="fixed" data-header-position="fixed">
    <!-- Sidebar Start -->
    <?php 
    include '../View/sidebar.php';
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
        ?>
        </div>
            <div class="row">
                <div class="col-md-12">
                    <?php 
                    if(isset($_SESSION["salesreturn_update"]))
                    {
                        if($_SESSION["salesreturn_update"]==1)
                        {

                        }
                        elseif ($_SESSION["salesreturn_update"]==2) 
                        {
                            # code...
                        }
                        else
                        {
                            ?>
                            <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                <strong>Oops!Something Went Wrong,</strong>Please Try Again
                            </div>
                            <?php
                        }
                        unset($_SESSION["salesreturn_update"]);
                    }
                    ?>
                    <div class="card p-2">
                        <div class="card-header">
                            <h5 class="card-title fw-semibold mb-3">Sales Return :-  <span style="color: #d71920;font-weight: 900;"><?=$barcode?></span> </h5>
                            <?php 
                            ?>
                        </div>
                        <div class="card-body">
                            <form id="sales-return-form" action="../Controller/SalesReturnControl.php" method="post">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>SN <?=$shop_id?></th>
                                                <th>Item Information</th>
                                                <th>Batch <span class="text-danger">*</span></th>
                                                <th>Return Quantity</th>
                                                <th>Bill Quantity</th>
                                                <th>Rate</th>
                                                <th>Total</th>
                                                <th>Check Return</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <input type="hidden" name="p_id" id="p_id<?=$product_id?>" value="<?=$product_id?>" readonly class="form-control">
                                            <?php 
                                            $sl=1;
                                            foreach ($check_product as $row) 
                                            {
                                                $batchs=$salesreturn->select_batch($row['PDID'],$shop_id);
                                                
                                                ?>
                                                <tr id="idid_<?=$row["PDID"]?>">
                                                    <td>
                                                        <input type="hidden" name="rate[]" id="rate_<?=$row["PDID"]?>" readonly class="form-control">
                                                        <input type="hidden" name="product_id[]" id="product_id<?=$row["PDID"]?>" value="<?=$row["PDID"]?>" readonly class="form-control">
                                                        <input type="hidden" name="BillQty[]" id="BillQty<?=$row["PDID"]?>" value="0" readonly class="form-control">
                                                        <?php 
                                                        echo $sl;
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?=$row["ItemName"]?>
                                                    </td>
                                                    <td>
                                                        <select name="batch_id[]" id="batch_id_<?=$row['PDID']?>" onchange="selectbatch(<?=$row['PDID']?>)" class="form-control" required>
                                                            <option value=""> === Select Batch === &#8595;</option>
                                                            <?php 
                                                                foreach ($batchs as $rows) 
                                                                {
                                                                    ?>
                                                                    <option value="<?=$rows["PHID"]?>"><?=$rows["pro_batch"]?> - <?=$rows["Pro_SellingPrice"]?></option>
                                                                    <?php
                                                                }
                                                            ?>
                                                        </select>
                                                    </td>
                                                    <td style="padding: 10px;">
                                                        <input type="text" name="returnqty[]" onkeypress="returnCheck(<?=$row['PDID']?>)" onkeyup="returnCheck(<?=$row['PDID']?>)" id="returnqty<?=$row['PDID']?>" class="form-control" readonly>
                                                        <span class="danger-alert" id="alert-text<?=$row['PDID']?>"></span>
                                                    </td>
                                                    <td id="BillQty_<?=$row['PDID']?>">

                                                    </td>
                                                    <td id="SellAmount_<?=$row['PDID']?>">

                                                    </td>
                                                    <td>
                                                        <input type="text" name="item_total[]" id="total_<?=$row['PDID']?>" class="total form-control" readonly>
                                                    </td>
                                                    <td class="d-flex justify-content-center">
                                                        <input type="checkbox" name="check[]" value="<?=$row['PDID']?>" onclick="checkbox(<?=$row['PDID']?>)" id="check_<?=$row['PDID']?>" class="check from-check" style="width: 40px; height: 40px;">
                                                    </td>
                                                </tr>
                                                <?php 
                                                $sl=$sl+1;
                                            }
                                            ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="5" rowspan="2">
                                                    <label for="reason" class="form-lable">Reason <span class="text-danger">*</span></label>
                                                    <textarea name="reason" id="reason" class="form-control" required></textarea>
                                                    <span class="form-label w-100 d-block">Usability</span>
                                                    <input type="radio" name="usability" id="usability1" class="form-radio" value="1" checked>
                                                    <span class="form-label">Adjust Stock</span><br>
                                                    <input type="radio" name="usability" id="usability2" class="form-radio" value="0">
                                                    <span class="form-label">Damage Stock</span>
                                                </td>
                                                <td>Total Return Amount</td>
                                                <td><input type="text" name="total_return" id="total_return" class="form-control" readonly></td>
                                                <td rowspan="3"></td>
                                            </tr>
                                            <tr>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="return-type" class="form-label">Return Type <span class="text-danger">*</span></label>
                                            <select name="returntype" id="return-type" class="form-select">
                                                <option value="1">Cash Refund</option>
                                                <option value="2">Exchange</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 d-flex justify-content-end">
                                            <input type="submit" value="Submit" name="sales_return_product" id="btn" class="btn btn-primary mt-4">
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<div id="ajaxshow"></div>
<!--  Body Wrapper End -->

    <!-- footer Start  -->
    <?php include '../View/footer.php';?> 
    <!-- footer End  -->

    <script src="../Assets/jquery/sales-return.js"></script>
    <script>
        function selectbatch(item)
        {
            var inventoryID=$("#batch_id_"+item).val();
            if(inventoryID=="" || inventoryID==0 || inventoryID==undefined)
            {
                $("#SellAmount_"+item).text("");
                $("#total_"+item).val("");
                $("#returnqty"+item).val("");
                $("#rate_"+item).val("");
                $("#BillQty"+item).val("");
                $("#BillQty_"+item).text("");
            }
            else
            {
                $("#returnqty"+item).val("");
                $.ajax({
                    type: 'POST',
                    url: '../AJAX/salesreturn/sales-return-product.php?item='+item,
                    data: {id:inventoryID},  
                    success: function(result)  
                    {
                        $("#ajaxshow").html(result);
                    }
                });
            }
            
        }
        function returnCheck(item) 
        {
            if($("#returnqty"+item).val()==0 || $("#returnqty"+item).val()=="")
            {
                $("#total_"+item).val(0);
                $("#total_return").val(0);
                $("#alert-text"+item).text("Return Quantity Must Be Greater Than 0");
                
            }
            else
            {
                var inventoryID=$("#batch_id_"+item).val();
                if(inventoryID=="" || inventoryID==0 || inventoryID==undefined)
                {
                    $("#SellAmount_"+item).text("");
                    $("#total_"+item).val("");
                    $("#returnqty"+item).val("");
                    $("#rate_"+item).val("");
                    alert("Please Select a Batch");
                    $("#batch_id_"+item).focus();
                }
                else
                {
                    var rate = parseFloat($("#rate_"+item).val());
                    var total =parseFloat($("#returnqty"+item).val());
                    var returnqty =parseFloat($("#returnqty"+item).val());
                    var BillQty =parseFloat($("#BillQty"+item).val());
                    if(returnqty > BillQty)
                    {
                        $("#alert-text"+item).text("Return Quantity Must Be Lesser Than Bill Quantity");
                        $("#total_"+item).val("0.00");
                        $("#total_return").val("0.00");
                        $("#returnqty"+item).val("")
                    }
                    else
                    {
                        $("#alert-text"+item).text("");
                        var total2=rate*total;
                        var tot=total2.toFixed(2);
                        $("#total_"+item).val(tot);
                        $("#total_return").val(tot);

                    }

                }
                $("#returnqty" + item).on('input', function() {
                    $("#returnqty" + item).val($("#returnqty" + item).val().replace(/[^0-9.]/, ''));
                });
            }
            

        }
        function validateForm() {
            const checkboxes = $('input[type="checkbox"]');
            let isChecked = false;

            checkboxes.each(function() {
                if ($(this).is(':checked')) {
                    isChecked = true;
                    return false; // break out of the loop
                }
            });

            if (!isChecked) {
                alert('Please select at least one item to return.');
                $(".check").focus();
                return false;
            }

            return true;
        }
        function checkbox(item)
        {
            var checkbox = $("#check_"+item);
            var container = $("#idid_" + item);

            if(checkbox.prop('checked'))
            {
                container.find("input").attr("required", true);
                $("#returnqty" + item).removeAttr("readonly");
                $("#returnqty" + item).css("background-color","transparent");
                $("#returnqty" + item).css("border-color","#DFE5EF");
                $("#returnqty" + item).focus();
            }
            else
            {
                container.find("input").removeAttr("required");
                $("#total_"+item).val("");
                $("#discount_"+item).val("");
                $("#total_amount_"+item).val("");
                $("#returnqty" + item).val("");
                $("#alert-text"+item).fadeOut();
                $("#returnqty" + item).attr("readonly",true);
                $("#returnqty" + item).css("background-color","rgb(235, 235, 235)");
                $("#returnqty" + item).css("border-color","rgb(235, 235, 235)");
                // grand_total();
            }
        }
        $(document).ready(function() {
            $('#sales-return-form').submit(function() {
                return validateForm();
            });
        });
    </script>
    <!-- <script src="../Assets/libs/jquery/dist/jquery.min.js"></script> -->
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/apexcharts/dist/apexcharts.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
    <script src="../Assets/js/dashboard.js"></script>

</body>
</html>