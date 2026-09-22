<?php 
include "../../Includes/config.php";
include "../../Model/product_class.php"; 
session_start();
$shop_id = $_SESSION['shop_id'];
$product_class = new Product();

$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit  = isset($_GET['limit'])  ? intval($_GET['limit'])  : 40;
$show_images = isset($_GET['show_images']) ? intval($_GET['show_images']) : 1;

// Support existing search/filter params
$value   = isset($_GET['value'])   ? $_GET['value']   : null;
$subcat  = isset($_GET['subcat'])  ? $_GET['subcat']  : null;
$product_id_single = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;

// Single product lookup (existing behaviour)
if ($product_id_single) {
    $result = $product_class->getOneProductWithInventory($shop_id, $product_id_single);
    echo json_encode($result);
    exit;
}

$products = $product_class->getproductswithinventory_paged($shop_id, $offset, $limit, $value, $subcat);
$total    = $product_class->getproductswithinventory_count($shop_id, $value, $subcat);

$rendered = 0;
foreach ($products as $product) 
{
    $qty = $product["sum_qty"];
    if ($qty > 0)
    {
        $qty = substr($qty, 0, -4);
        $product_id   = $product['PDID'];
        $product_count = $product_class->getqtycount($shop_id, $product_id);
        $count_qty     = $product_count[0]["count_qty"] ?? 0;
        $sell_price    = $product["ProdSellPrice"] ?? '0.00';
        $item_name     = htmlspecialchars($product["ItemName"]);
        $barcode       = htmlspecialchars($product["Barcode"]);
        $cat_id        = $product['cat_ID'];
        $prod_image    = $product["ProdImage"];
        $rendered++;
        ?>
        <a href="javascript:void(0);" class="col-3 product-tile <?=$cat_id?>" id="single-product">
            <div class="m-1 product2 card p-0 position-relative">
                <div class="d-none" id="hidden-fields">
                    <input type="hidden" name="" value="<?=$product_id?>" id="product-id">
                    <input type="hidden" name="" value="<?=$barcode?>" id="product-barcode">
                    <input type="hidden" name="" value="<?=$count_qty?>" id="product-qty-count">
                    <p id="barcode"><?=$barcode?></p>
                </div>
                <div class="qty-badge">
                    <span><?=$qty?></span>
                </div>
                <?php if ($show_images == 1): ?>
                <img class="rounded product-img-tile" src="../Assets/Images/prod_images/<?=$prod_image?>" alt="<?=$item_name?>">
                <div class="card-body product-tile-body">
                    <label class="card-title m-0 prd-lable"><?=$item_name?></label>
                    <p class="card-text mb-0 product-price"><b>Rs. <?=$sell_price?></b></p>
                </div>
                <?php else: ?>
                <div class="product-name-tile">
                    <span class="product-name-text"><?=$item_name?></span>
                    <span class="product-name-price">Rs. <?=$sell_price?></span>
                </div>
                <?php endif; ?>
            </div>
        </a>
        <?php
    }
}

// Sentinel element for lazy loading
if (($offset + $limit) < $total): ?>
<div class="col-12 lazy-load-sentinel" 
     data-offset="<?=($offset + $limit)?>" 
     data-limit="<?=$limit?>" 
     style="height:1px;">
</div>
<?php endif;

if ($offset == 0): ?>
<script>
    (function() {
        var procount = document.getElementById('procount');
        if (procount) procount.textContent = '<?=$total?>';
    })();
</script>
<?php endif; ?>