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
  $shop_id = $_SESSION['shop_id'];
  $priceChange=new Pricechange();
  $fetch=$priceChange->select_pricechange($shop_id);
  ?>
  <style>
    .table>:not(caption)>*>* 
    {
        padding: 10px;
    }
    th 
    {
        font-size: 0.8rem;
    }    
    input[disabled]
    {
        cursor: not-allowed;
    }
    select[disabled]
    {
        cursor: not-allowed;
    }
    input[readonly]
    {
        background-color: rgb(235, 235, 235) !important;
        border-color: rgb(235, 235, 235) !important;
    }
    .select2-container--default .select2-selection--single
    {
        border: none !important;
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
    $feature_id=6;
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
            if(isset($_SESSION['grnheader_update']))
            {
                if($_SESSION['grnheader_update'] == 0)
                {
                    ?>
                    <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Please select an <strong>Effective Date.</strong> 
                    </div>
                    <?php 
                }//no date
                else if($_SESSION['grnheader_update'] == 1)
                {
                    ?>
                        <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                            Please enter <strong>GRN Invoice No.</strong>
                        </div>
                        <?php
                }//no invoice
                else if($_SESSION['grnheader_update'] == 2)
                {
                    ?>
                    <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>GRN created </strong>successfully!
                    </div>
                    <?php
                }//save success
                else if($_SESSION['grnheader_update'] == 3)
                {
                    ?>
                    <div class="alert alert-warning alert-dismissible bg-warning text-white border-0 fade show" role="alert">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        Please select a <strong>GRN</strong> to add items
                    </div>
                    <?php
                }//update success
                else
                {
                    ?>
                    <div class="alert alert-danger">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>Oops! </strong>something went wrong!
                    </div>
                    <?php 
                }//else
                unset($_SESSION['grnheader_update']);
            }//session set
            ?>

        </div>
            <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Price Change</h5>
            
            <div class="card mt-5">
                <div class="card-body">
                    <?php 
                    if($userType==1 || $create==1 || $edit==1)
                    {
                        ?><form action="../Controller/pricechangeController.php" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mt-2">
                                <label for="cmb_product" class="form-label">Select Product</label>
                                <select name="product_id" onchange="item_id()" id="cmb_product" class="form-control" required></select>
                            </div>
                            <div class="col-md-6 mt-2">
                                <label for="varriation_id" class="form-label">Varriation</label>
                                <select name="varriation_id" onchange="varriation()" id="varriation_id" class="form-control" disabled></select>
                            </div>
                            <div class="col-md-6">
                                <label for="batch_id" class="form-label">Batch ID</label>
                                <select name="batch_id" onchange="batchss()" id="batch_id" class="form-control" disabled></select>
                                <input type="hidden" name="batch" id="batch">
                            </div>
                            <div class="col-md-6 mt-2"></div>
                            <div class="col-md-6 mt-2">
                                <label for="old_selling_price" class="form-label">Current Selling Price</label>
                                <input name="old_selling_price" id="old_selling_price" class="form-control" readonly>
                            </div>
                            <div class="col-md-6 mt-2">
                                <label for="new_selling_price" class="form-label">New Selling Price</label>
                                <input name="new_selling_price" id="new_selling_price" class="form-control" required disabled>
                            </div>
                            <div class="col-md-6 mt-2">
                                <label for="old_label_price" class="form-label">Current Label Price</label>
                                <input name="old_label_price" id="old_label_price" class="form-control" readonly>
                            </div>
                            <div class="col-md-6 mt-2">
                                <label for="new_label_price" class="form-label">New Label Price</label>
                                <input name="new_label_price" id="new_label_price" class="form-control" disabled>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <!-- save -->
                            <button type="submit" name="btn_save_product" class="btn btn-primary mt-3" id="btn_save_product">
                            Add Price Change
                            </button>

                        </div>
                    </form>
                        
                        <?php
                    }
                    ?>
                    <div class="container-fluid table-responsive" style="height:450px; overflow-y:auto;">
                        <table class="table table-hover mt-5" id="tbl_category">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Product Name</th>
                                    <th>Varriation</th>
                                    <th>Old Selling Price</th>
                                    <th>New Selling Price</th>
                                    <th>Old Label Price</th>
                                    <th>New Label Price</th>
                                    <th>Selling Price Difference</th>
                                    <th>Label Price Difference</th>
                                    <th>Added By</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $sl=1;
                                if(count($fetch)==0)
                                {
                                    ?>
                                    <tr>
                                        <td colspan="11" class="text-danger text-center">No Results To Show</td>
                                    </tr>
                                    <?php
                                }
                                else
                                {
                                    foreach ($fetch as $result) 
                                    {
                                        ?>
                                        <tr>
                                            <td><?=$sl?></td>
                                            <td><?=$result["ItemName"]?></td>
                                            <td><?=$result["VariationName"]?></td>
                                            <td><?=number_format($result["old_selling_price"],2,'.',',')?></td>
                                            <td><?=number_format($result["new_selling_price"],2,'.',',')?></td>
                                            <td><?=number_format($result["old_label_price"],2,'.',',')?></td>
                                            <td><?=number_format($result["new_label_price"],2,'.',',')?></td>
                                            <td>
                                                <?php 
                                                $sellingChange=$result["new_selling_price"]-$result["old_selling_price"];
                                                if($result["new_selling_price"]==$result["old_selling_price"])
                                                {
                                                    ?>
                                                    <span class="text-warning" style="font-weight:700;">
                                                        <i class="ti ti-equal"></i> No Change
                                                    </span>
                                                    <?php
                                                }
                                                elseif($sellingChange>0)
                                                {
                                                    ?>
                                                    <span style="font-weight:700; color:green;"><i class="ti ti-outbound" style=" display:inline-block; transform:rotate(311deg);"></i> <?=number_format($sellingChange,2,'.',',')?></span><?php
                                                }
                                                else
                                                {
                                                    ?>
                                                    <span class="text-danger" style="font-weight:700;">
                                                        <i class="ti ti-outbound" style=" display: inline-block; transform: rotate(133deg); "></i> <?=number_format($sellingChange,2,'.',',')?>
                                                    </span>
                                                    
                                                    <?php
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $labelChange=$result["new_label_price"]-$result["old_label_price"];
                                                if($result["new_label_price"]==$result["old_label_price"])
                                                {
                                                    ?>
                                                    <span class="text-warning" style="font-weight:700;">
                                                        <i class="ti ti-equal"></i> No Change
                                                    </span>
                                                    <?php
                                                }
                                                elseif($labelChange>0)
                                                {
                                                    ?>
                                                    <span style="font-weight:700; color:green;"><i class="ti ti-outbound" style=" display:inline-block; transform:rotate(311deg);"></i> <?=number_format($labelChange,2,'.',',')?></span>
                                                     
                                                    <?php
                                                }
                                                else
                                                {
                                                    ?>
                                                    <span class="text-danger" style="font-weight:700;">
                                                        <i class="ti ti-outbound" style=" display: inline-block; transform: rotate(133deg); "></i> <?=number_format($labelChange,2,'.',',')?>
                                                    </span>
                                                    <?php
                                                }
                                                ?>
                                            </td>
                                            <td><?=$result["UserName"]?></td>
                                            <td><?=$result["date"]?></td>
                                        </tr>
                                        <?php
                                        $sl++;
                                    }
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
<!--  Body Wrapper End -->

    <!-- footer Start  -->
    <?php include '../View/footer.php';?> 
    <!-- footer End  -->

    <script src="../Assets/jquery/priceChange.js"></script>
    <script>
        function item_id()
        {
            var batch = $("#batch_id");
            var items = $("#cmb_product");
            var varriation_id = $("#varriation_id");
            var selectedValue = items.val();
            var opt="";
            if(items.val()!=0 || items.val()=="")
            {
                $.ajax({
                url:'../AJAX/PriceChange/PriceChange.php',
                    method:'post',
                    data:{
                        items:selectedValue
                        },
                    success:function(response)
                    {
                        const obj = JSON.parse(response);

                        if(obj.length==0)
                        {
                            alert("No Stock Available");
                            varriation_id.html("");
                            batch.html("");
                            varriation_id.attr("disabled", true);
                            batch.attr("disabled", true);
                        }
                        else
                        {
                            if(obj[0]['type']=="batch")
                            {
                                batch.removeAttr("disabled");
                                varriation_id.attr("disabled", true);
                                batch.attr("required");
                                varriation_id.removeAttr("required");
                                for (var i = 0; i < obj.length; i++) {
                                    opt += '<option value="' + obj[i]['id'] + '">' + obj[i]['text'] + '</option>';
                                }
                                batch.html("");
                                batch.append(opt);
                                varriation_id.html("");
                                batch.focus();
                            }
                            else
                            {
                                varriation_id.removeAttr("disabled");
                                batch.attr("disabled", true);
                                varriation_id.attr("required");
                                batch.removeAttr("required");
                                for (var i = 0; i < obj.length; i++) {
                                    opt += '<option value="' + obj[i]['id'] + '">' + obj[i]['text'] + '</option>';
                                }
                                varriation_id.html("");
                                varriation_id.append(opt);
                                batch.html("");
                                varriation_id.focus();
                            }
                        }
                    }
                });
            }
            disable();
        }
        function disable()
        {
            $("#new_selling_price").attr("disabled", true);
            $("#new_label_price").attr("disabled", true);
            $("#new_selling_price").val("");
            $("#new_label_price").val("");
            $("#old_selling_price").val("");
            $("#old_label_price").val("");
        }
        function varriation()
        {
            var batch = $("#batch_id");
            var items = $("#cmb_product");
            var varriation_id = $("#varriation_id");
            var product_id=items.val();
            var varriation_id_val=varriation_id.val();
            disable();
            
            if(varriation_id_val!=0 || varriation_id_val!="")
            {
                $.ajax({
                    url:'../AJAX/PriceChange/PriceChange.php',
                        method:'post',
                        data:{
                            product_id:product_id,
                            varriation_id:varriation_id_val,
                            },
                        success:function(response)
                        {
                            console.log(response);
                            if(response.length==0 || response=="")
                            {
                                batch.html(""); 
                                alert("No Batches Available");
                                batch.attr("disabled", true);
                                varriation_id.focus();                                
                            }
                            else
                            {
                                batch.html(response); 
                                batch.removeAttr("disabled");
                                batch.focus();  
                            }
                             
                        }
                    });
            }
            else
            {
                batch.html(""); 
                batch.attr("disabled", true);
                varriation_id.focus();
            }
        }
        function batchss()
        {
            var batch = $("#batch_id");
            var items = $("#cmb_product");
            var varriation_id = $("#varriation_id");
            var product_id=items.val();
            var varriation_id_val=varriation_id.val();
            var batch_value=batch.val();
            if(batch_value!=0 || batch_value!="")
            {
                $.ajax({
                    url:'../AJAX/PriceChange/PriceChange.php',
                        method:'post',
                        data:{
                            PHID:batch_value,
                            },
                        success:function(response)
                        {
                            const obj = JSON.parse(response);
                            $("#old_selling_price").val(obj[0]["SellingPrice"]);
                            $("#old_label_price").val(obj[0]["labelPrice"]);
                            $("#batch").val(obj[0]["batch"]);
                            $("#new_selling_price").val(obj[0]["SellingPrice"]);
                            $("#new_label_price").val(obj[0]["labelPrice"]);
                            $("#new_selling_price").removeAttr("disabled");
                            $("#new_label_price").removeAttr("disabled");
                            $("#new_selling_price").focus();
                        }
                    });
            }
            else
            {
                $("#old_selling_price").val("");
                $("#old_label_price").val("");
                $("#new_selling_price").val("");
                $("#new_label_price").val("");
                $("#new_selling_price").attr("disabled", true);
                $("#new_label_price").attr("disabled", true);
            }
            
        }
    </script>
    <!-- <script src="../Assets/libs/jquery/dist/jquery.min.js"></script> -->
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    
</body>
</html>