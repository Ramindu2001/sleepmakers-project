<?php 
include "../../Includes/config.php";
include "../../Model/product_class.php";
session_start();
$shop_id = $_SESSION['shop_id'];

$product= new Product();
$product_class= new Product();
if(isset($_GET["modal"]))
{
    if ($_GET["modal"]==1) 
    {
        $id=$_POST["id"];
        $products=$product->getpricehistorywithinventory($id,$shop_id);
        $count=count($products);
        if($count> 0)
        {
            foreach ($products as $row) 
            {
                $qty=$row["CurrentQty"];
                $qty=substr($qty,0,-4);
                ?>
                    <tr id="check_<?=$row["PHID"]?>">
                        <td>
                            <input type="hidden" name="" value="<?=$row["PDID"]?>" id="p_id">
                            <input type="hidden" name="" value="<?=$row["PHID"]?>" id="PHID">
                            <input type="radio" class="form-check" name="item-chcek" value="<?=$row["PHID"]?>" id="item-chcek">
                        </td>
                        <td>
                            <a href="javascript:void(0);" id="add-product-modal"><?=$qty?></a>
                            
                        </td>
                        <td>
                            <a href="javascript:void(0);" id="add-product-modal"><?=$row["SellingPrice"]?></a>
                            
                        </td>
                        <td>
                            <a href="javascript:void(0);" id="add-product-modal"><?=$row["MnfDate"]?></a>
                            
                        </td>
                        <td>
                            <a href="javascript:void(0);" id="add-product-modal"><?=$row["ExpDate"]?></a>
                            
                        </td>
                    </tr>
                
                
                <?php
            }
        }
        else
        {
            ?>
            <tr>
                <td colspan="5"><h1 class="text-danger">No Products Found.</h1></td>
            </tr>
            <?php 
        }
        
        ?>
        <?php
    }
    elseif ($_GET["modal"]==2) 
    {
        $id=$_POST["id"];
        $products=$product->getproductwithinventorypricehistory($id,$shop_id);
        foreach ($products as $product) 
        {
            $qty=$product["CurrentQty"];
            $qty=substr($qty,0,-4);
            $product_count=$product_class->getqtycount($shop_id,$id); 
            ?>
            <tr id="p_<?=$product["PDID"]?>">
                <input type="hidden" name="" id="product_id" value="<?=$product["PDID"]?>">
                <input type="hidden" name="" id="price_id" value="<?=$product["price_id"]?>">
                <input type="hidden" name="" id="barcode" value="<?=$product["Barcode"]?>">
                <input type="hidden" name="" value="<?=$product_count[0]["count_qty"]?>" id="product-qty-count2">
                <td> 
                <div id="b_<?=$product["Barcode"]?>"></div>
                    <input type="hidden" name="product_name[]" id="product_name" value="<?=$product["ItemName"]?>">
                    <?=$product["ItemName"]?>
                </td>
                <td id="w-100">
                    <input type="hidden" name="" value="<?=$qty?>" id="available_qty">
                    <?=$qty?>
                </td>
                <td id="w-140">
                    <di class="center">
                        <input type="hidden" name="cart-quantity[]" value="1" id="cart-quantity">
                        <a href="javascript:void(0)" class="btn btn-success" id="sub-quantity" onclick="">-</a>
                        <span id="qty"> 1 </span>
                        <a href="javascript:void(0)" class="btn btn-success" id="add-quantity">+</a>
                    </di>
                    
                </td>
                <td id="text-right">
                    <input type="text" name="product_price[]" value="<?=$product["SellingPrice"]?>" id="product_price" class="form-control">
                    <input type="hidden" name="original_product_unit_price[]" value="<?=$product["SellingPrice"]?>" id="original_product_unit_price" class="form-control">
                    
                </td>
                <td>
                    <input type="text" name="discount_percentage[]" value="0" id="discount_percentage" class="form-control">
                </td>
                <td id="text-right">
                    <input type="text" name="discount_value[]" value="0.00" id="discount_value" class="form-control" disabled>
                </td>
                <td id="text-right">
                    <input type="hidden" name="total_price[]" id="total_price" value="<?=$product["SellingPrice"]?>">
                    <span id="total_price_span"> <?=$product["SellingPrice"]?></span>
                   
                </td>
                <td class="action">
                    <div class="row action">
                        <div class="col-md-12">
                            <a href="javascript:void(0);" id="remove-cart" class="btn btn-danger mb-1"><i class="ti ti-trash"></i></a>
                        </div>
                    </div>
                </td>
            </tr>
            <?php
        }        
    }
    elseif($_GET["modal"]==3)
    {
        $price_id=$_POST["price_id"];
        $id=$_POST["id"];
        $products=$product->getproductwithinventorypricehistory2($id,$shop_id,$price_id);
        foreach ($products as $product) 
        {
            $qty=$product["CurrentQty"];
            $qty=substr($qty,0,-4);
            $product_count=$product_class->getqtycount($shop_id,$id); 
            ?>
            <tr id="p_<?=$product["PDID"]?>_<?=$price_id?>">
                <input type="hidden" name="" id="product_id" value="<?=$product["PDID"]?>">
                <input type="hidden" name="" id="price_id" value="<?=$product["price_id"]?>">
                <input type="hidden" name="" id="barcode" value="<?=$product["Barcode"]?>">
                <input type="hidden" name="" value="<?=$product_count[0]["count_qty"]?>" id="product-qty-count2">
                <td> 
                <div id="b_<?=$product["Barcode"]?>"></div>
                    <input type="hidden" name="product_name[]" id="product_name" value="<?=$product["ItemName"]?>">
                    <?=$product["ItemName"]?>
                </td>
                <td id="w-100">
                    <input type="hidden" name="" value="<?=$qty?>" id="available_qty">
                    <?=$qty?>
                </td>
                <td id="w-140">
                    <di class="center">
                        <input type="hidden" name="cart-quantity[]" value="1" id="cart-quantity">
                        <a href="javascript:void(0)" class="btn btn-success" id="sub-quantity" onclick="">-</a>
                        <span id="qty"> 1 </span>
                        <a href="javascript:void(0)" class="btn btn-success" id="add-quantity">+</a>
                    </di>
                    
                </td>
                <td id="text-right">
                    <input type="text" name="product_price[]" value="<?=$product["SellingPrice"]?>" id="product_price" class="form-control">
                    <input type="hidden" name="original_product_unit_price[]" value="<?=$product["SellingPrice"]?>" id="original_product_unit_price" class="form-control">
                    
                </td>
                <td>
                    <input type="text" name="discount_percentage[]" value="0" id="discount_percentage" class="form-control">
                </td>
                <td id="text-right">
                    <input type="text" name="discount_value[]" value="0.00" id="discount_value" class="form-control" disabled>
                </td>
                <td id="text-right">
                    <input type="hidden" name="total_price[]" id="total_price" value="<?=$product["SellingPrice"]?>">
                    <span id="total_price_span"> <?=$product["SellingPrice"]?></span>
                   
                </td>
                <td class="action">
                    <div class="row action">
                        <div class="col-md-12">
                            <a href="javascript:void(0);" id="remove-cart" class="btn btn-danger mb-1"><i class="ti ti-trash"></i></a>
                        </div>
                    </div>
                </td>
            </tr>
            <?php
        }
    }
    elseif($_GET["modal"]== 4)
    {
        $barcode=$_POST["barcode"];
        $products=$product->get_products_with_barcode($barcode,$shop_id);
        if(count($products)==1)
        {
            foreach ($products as $product) 
            {
                $qty=$product["CurrentQty"];
                $qty=substr($qty,0,-4);
                $product_count=$product_class->getqtycount($shop_id,$product["PDID"]); 
                ?>
                <tr id="p_<?=$product["PDID"]?>_<?=$product["price_id"]?>">
                    <input type="hidden" name="" id="product_id" value="<?=$product["PDID"]?>">
                    <input type="hidden" name="" id="price_id" value="<?=$product["price_id"]?>">
                    <input type="hidden" name="" id="barcode" value="<?=$product["Barcode"]?>">
                    <input type="hidden" name="" value="<?=$product_count[0]["count_qty"]?>" id="product-qty-count2">
                    <td> 
                    <div id="b_<?=$product["Barcode"]?>"></div>
                        <input type="hidden" name="product_name[]" id="product_name" value="<?=$product["ItemName"]?>">
                        <?=$product["ItemName"]?>
                    </td>
                    <td id="w-100">
                        <input type="hidden" name="" value="<?=$qty?>" id="available_qty">
                        <?=$qty?>
                    </td>
                    <td id="w-140">
                        <di class="center">
                            <input type="hidden" name="cart-quantity[]" value="1" id="cart-quantity">
                            <a href="javascript:void(0)" class="btn btn-success" id="sub-quantity" onclick="">-</a>
                            <span id="qty"> 1 </span>
                            <a href="javascript:void(0)" class="btn btn-success" id="add-quantity">+</a>
                        </di>
                        
                    </td>
                    <td id="text-right">
                        <input type="text" name="product_price[]" value="<?=$product["SellingPrice"]?>" id="product_price" class="form-control">
                        <input type="hidden" name="original_product_unit_price[]" value="<?=$product["SellingPrice"]?>" id="original_product_unit_price" class="form-control">
                        
                    </td>
                    <td>
                        <input type="text" name="discount_percentage[]" value="0" id="discount_percentage" class="form-control">
                    </td>
                    <td id="text-right">
                        <input type="text" name="discount_value[]" value="0.00" id="discount_value" class="form-control" disabled>
                    </td>
                    <td id="text-right">
                        <input type="hidden" name="total_price[]" id="total_price" value="<?=$product["SellingPrice"]?>">
                        <span id="total_price_span"> <?=$product["SellingPrice"]?></span>
                    
                    </td>
                    <td class="action">
                        <div class="row action">
                            <div class="col-md-12">
                                <a href="javascript:void(0);" id="remove-cart" class="btn btn-danger mb-1"><i class="ti ti-trash"></i></a>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php
            }        
        }
        elseif(count($products)>1)
        {
            ?>
            <script>
            $("#section_modal").modal('toggle');
            var product_id = <?=$barcode?>;
            $.ajax({
                url:'../AJAX/gui_pos/product.php?modal=1',
                    method:'post',
                    data:{id:product_id},
                    success:function(response)
                    {
                        $("#product-modal-tbody").append(response);


                    }
            });
            </script>
            <?php
        }
        else
        {
            ?>
            <script>
                $("#barcode-input").val("");
                $("#barcode-input").focus();
            </script>
            <?php
        }
    }
}




?>