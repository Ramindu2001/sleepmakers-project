<?php

include '../Includes/includes.php';
include '../Includes/authcheck.php';

$category_class= new Category();
$categories=$category_class->getCategoryByShop($shop_id);
$product_class=new Product();
$products=$product_class->getproductswithinventory($shop_id);
?>
<!doctype html>
<html lang="en">

<head>
  <?php
  include '../View/head.php';
  include '../View/modals/pos-GUI-modal.php';
  include '../View/modals/add-customer.php';
  // include '../View/loader.php';
  ?>
</head>

<body>
  <!--  Body Wrapper -->
    <div class="h-100vh">
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="mini-sidebar"
        data-sidebar-position="fixed" data-header-position="fixed">
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
                ?>
                <!--  Header End -->
                <div class="container-fluid">
                <div class="row">
                    <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">GUI POS</h5>
                    <div class="col-md-6 ">
                        <div class="row">
                            <div class="col-md-2 p-2 ">
                                <div id="categories">
                                    <a href="javascript:void(0);" class="btn btn-primary w-100 mb-2 active cat" id="all">All</a>
                                    <?php 
                                    foreach ($categories as $category) 
                                    {
                                        ?>
                                        <a href="javascript:void(0);" class="btn btn-primary w-100 mb-2 cat" id="<?=$category["CTID"]?>"><?=$category["CategoryName"]?></a>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="col-md-10 p-2" id="product-main">
                                <div class="mb-2" id="search-sec">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="row">
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                                                    <input type="text" class="form-control" id="search" placeholder=" Search Products">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <select name="" class="form-select">
                                                <option value="">Select Option</option>
                                                <option value="">Select Option</option>
                                                <option value="">Select Option</option>
                                                <option value="">Select Option</option>
                                                <option value="">Select Option</option>
                                                <option value="">Select Option</option>
                                                <option value="">Select Option</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div id="products">
                                    <div class="row" id="product">
                                        <?php 
                                        foreach ($products as $product) 
                                        {
                                            $qty=$product["sum_qty"];
                                            if($qty>0)
                                            {
                                                $qty=substr($qty,0,-4);
                                                $product_id=$product['PDID'];
                                                $product_count=$product_class->getqtycount($shop_id,$product_id); 

                                                ?>
                                                <a href="javascript:void(0);" class="col-md-3 <?=$product['cat_ID']?>"  id="single-product">
                                                    <div class="m-1 product2 card p-0">
                                                        <div class="d-none" id="hidden-fields">
                                                            <input type="hidden" name="" value="<?=$product["PDID"]?>" id="product-id">
                                                            <input type="hidden" name="" value="<?=$product["Barcode"]?>" id="product-barcode">
                                                            <input type="hidden" name="" value="<?=$product_count[0]["count_qty"]?>" id="product-qty-count">
                                                            <p id="barcode"><?=$product["Barcode"]?></p>
                                                        </div>
                                                        <div class="quantity bg-primary">
                                                            <span> <?=$qty?></span>
                                                        </div>                                                        
                                                        <img class="rounded" style=" width: 100%; height: 100px;" src="../Assets/Images/prod_images/<?=$product["ProdImage"]?>" alt="...">
                                                        <div class="card-body" style="padding: 0 5px 5px 5px !important;">
                                                            <label class="card-title m-0 prd-lable"><?=$product["ItemName"]?></label>
                                                            <p class="card-text mb-1 text-black font-weight-bold"><b>Rs. <?=$product["MAXPRICE"]?></b></p>
                                                            
                                                        </div>
                                                    </div>
                                                </a>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6" id="border-left">
                        <div id="customer-search barcode-search">
                            <div class="row">
                                <div class="col-md-6 p-2">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="ti ti-barcode"></i></span>
                                        <input type="text" class="form-control" id="barcode-input" placeholder="Barcode">
                                    </div>
                                </div>
                                <div class="col-md-6 p-2">
                                    <div class="input-group">
                                        <a href="javascript:void(0);" class="input-group-text" id="add-customer"><i class="ti ti-user-plus" ></i></a>
                                        <input type="text" class="form-control" id="customer" placeholder="Search Customers">
                                        <input type="hidden" name="" id="customer_id" value="">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="cart">
                            <table id="cart-table">
                                <thead>
                                    <tr>
                                        <th>Item Information</th>
                                        <th>Av.Qty</th>
                                        <th style="width:140px;">Qty</th>
                                        <th>Rate</th>
                                        <th>Discount %</th>
                                        <th>Discount Value</th>
                                        <th>Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            </table>
                                <div class="table-responsive">
                                    <table id="cart-table">
                                        <tbody>
                                            <tr id="p_8">
                                                <input type="hidden" name="" id="product_id">
                                                <td> 
                                                    <input type="hidden" name="" id="product_name">
                                                    1 Cocacola 1050ml
                                                </td>
                                                <td>
                                                    <input type="hidden" name="" value="10" id="available_qty">
                                                    10
                                                </td>
                                                <td>
                                                    <input type="hidden" name="" value="1" id="cart-quantity">
                                                    <a href="javascript:void(0)" class="btn btn-success" id="sub-quantity" onclick="">-</a>
                                                    <span id="qty"> 1 </span>
                                                    <a href="javascript:void(0)" class="btn btn-success" id="add-quantity">+</a>
                                                </td>
                                                <td>
                                                    <input type="text" name="" value="1000.00" id="product_price" class="form-control">
                                                    <input type="hidden" name="" value="1000.00" id="original_product_unit_price" class="form-control">
                                                    
                                                </td>
                                                <td>
                                                    <input type="text" name="" value="10" id="discount_percentage" class="form-control">
                                                </td>
                                                <td>
                                                    <input type="text" name="" value="100.00" id="discount_value" class="form-control">
                                                </td>
                                                <td>
                                                    900.00
                                                </td>
                                                <td class="action">
                                                    <div class="row action">
                                                        <div class="col-md-12">
                                                            <a href="javascript:void(0);" id="remove-cart" class="btn btn-danger mb-1"><i class="ti ti-trash"></i></a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td> 2 Cocacola 1050ml</td>
                                                <td>
                                                    <input type="hidden" name="" value="10" id="available_qty">
                                                    10
                                                </td>
                                                <td>
                                                    <input type="hidden" name="" value="1" id="cart-quantity">
                                                    <a href="javascript:void(0)" class="btn btn-success" id="sub-quantity" onclick="">-</a>
                                                    <span id="qty"> 1 </span>
                                                    <a href="javascript:void(0)" class="btn btn-success" id="add-quantity">+</a>
                                                </td>
                                                <td>
                                                    1000.00
                                                </td>
                                                <td>
                                                    10
                                                </td>
                                                <td>
                                                    100.00
                                                </td>
                                                <td>
                                                    900.00
                                                </td>
                                                <td class="action">
                                                    <div class="row action">
                                                        <div class="col-md-12">
                                                            <a href="javascript:void(0);" id="remove-cart" class="btn btn-danger mb-1"><i class="ti ti-trash"></i></a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td> 3 Cocacola 1050ml</td>
                                                <td>
                                                    <input type="hidden" name="" value="10" id="available_qty">
                                                    10
                                                </td>
                                                <td>
                                                    <input type="hidden" name="" value="1" id="cart-quantity">
                                                    <a href="javascript:void(0)" class="btn btn-success" id="sub-quantity" onclick="">-</a>
                                                    <span id="qty"> 1 </span>
                                                    <a href="javascript:void(0)" class="btn btn-success" id="add-quantity">+</a>
                                                </td>
                                                <td>
                                                    1000.00
                                                </td>
                                                <td>
                                                    10
                                                </td>
                                                <td>
                                                    100.00
                                                </td>
                                                <td>
                                                    900.00
                                                </td>
                                                <td class="action">
                                                    <div class="row action">
                                                        <div class="col-md-12">
                                                            <a href="javascript:void(0);" id="remove-cart" class="btn btn-danger mb-1"><i class="ti ti-trash"></i></a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td> 4 Cocacola 1050ml</td>
                                                <td>
                                                    <input type="hidden" name= " " value="10" id="available_qty">
                                                    10
                                                </td>
                                                <td>
                                                    <input type="hidden" name="" value="1" id="cart-quantity">
                                                    <a href="javascript:void(0)" class="btn btn-success" id="sub-quantity" onclick="">-</a> 
                                                    <span id="qty"> 1 </span> 
                                                    <a href="javascript:void(0)" class="btn btn-success" id="add-quantity">+</a>
                                                </td>
                                                <td>
                                                    1000.00
                                                </td>
                                                <td>
                                                    10
                                                </td>
                                                <td>
                                                    100.00
                                                </td>
                                                <td>
                                                    900.00
                                                </td>
                                                <td class="action">
                                                    <div class="row action">
                                                        <div class="col-md-12">
                                                            <a href="javascript:void(0);" id="remove-cart" class="btn btn-danger mb-1"><i class="ti ti-trash"></i></a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                        </div>
                        <div id="cart-foot">
                            <div class="row">
                                    <div class="col-md-6"></div>
                                    <div class="col-md-6">
                                    <table>
                                            <tr class="tr-cart">
                                                <td><Label class="form-lable">Sale Discount :</Label></td>
                                                <td><input type="text" value="0.00" name="" id="sale_disc" class="form-control cart-input" placeholder="0.00"></td>
                                            </tr>
                                            <tr class="tr-cart">
                                                <td><Label class="form-lable">Total Discount :</Label></td>
                                                <td><input type="text" name="" id="total_disc" class="form-control cart-input" placeholder="0.00"></td>
                                            </tr>
                                            <tr class="tr-cart">
                                                <td><Label class="form-lable">VAT :</Label></td>
                                                <td><input type="text" name="" id="vat" class="form-control cart-input" placeholder="0.00"></td>
                                            </tr>
                                            <tr class="tr-cart">
                                                <td><Label class="form-lable">Shipping Cost :</Label></td>
                                                <td><input type="text" name="" id="shippingcost" class="form-control cart-input" placeholder="0.00"></td>
                                            </tr>
                                            <tr class="tr-cart">
                                                <td><Label class="form-lable">Grand Total :</Label></td>
                                                <td><input type="number" name="" id="grand_total" class="form-control cart-input" placeholder="0.00"></td>
                                            </tr>
                                        </table>
                                    </div>
                            </div>
                            
                        </div>
                    </div>
                </div>

        <!-- footer Start  -->
        <?php include '../View/footer.php';?>
        <!-- footer End  -->
        
      </div>
    </div>
  </div>
</div>
    
<script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../Assets/js/sidebarmenu.js"></script>
  <script src="../Assets/js/app.min.js"></script>
  <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
  <script src="../Assets/js/pos.js"></script>
</body>

</html>