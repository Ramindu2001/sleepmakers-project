<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";

$shop_id = $_GET['shop_id'];
if(isset($_POST["PDID"]))
{
    $PDID=$_POST["PDID"];
    $trCount=$_POST["trCount"]+1;
    $sql = "SELECT * FROM products p
    INNER JOIN inventory i ON i.products_PDID=p.PDID
    INNER JOIN pricehistory ph ON ph.Inventory_INID=i.INID
    WHERE i.shop_SHID='$shop_id' AND p.PDID='$PDID' AND p.ItemType='P'  ORDER BY i.INID DESC LIMIT 1 ;";
    $dbObj = new DBTransactions();
    $itemData = $dbObj->getData($sql);

    if(!empty($itemData))
    {
        ?>
            <tr id="productid-<?=$itemData[0]["PDID"]?>-<?=$itemData[0]["SellingPrice"]?>" class="cartItem<?=$trCount?>" data-product="<?=$itemData[0]["PDID"]?>">
                <td>
                    <textarea class="form-control border-none p5 f10 Item_name" name="Item_name[]" required><?=$itemData[0]["Barcode"]?> - <?=$itemData[0]["ItemName"]?></textarea>
                    <input type="hidden" name="item_id[]" id="item-cartItem<?=$trCount?>" value="<?=$itemData[0]["PDID"]?>">
                    <input type="hidden" name="productType[]" id="productType-cartItem<?=$trCount?>" value="<?=$itemData[0]["ItemType"]?>">
                </td>
                <td>
                    <div class="input-group">
                        <a href="javascript:void(0)" class="input-group-text increase-qty" onclick="ttotal('cartItem<?=$trCount?>')"><i class="ti ti-plus"></i></a>
                        <input type="text" name="qty[]" id="qty-cartItem<?=$trCount?>" class="form-control qty border-none text-center preDefault" value="1" onchange="ttotal('cartItem<?=$trCount?>')" onkeyup="ttotal('cartItem<?=$trCount?>')"  onkeydown="ttotal('cartItem<?=$trCount?>')" onkeypress="ttotal('cartItem<?=$trCount?>')" required>
                        <a href="javascript:void(0)" class="input-group-text decrease-qty" onclick="ttotal('cartItem<?=$trCount?>')"><i class="ti ti-minus"></i></a>
                    </div>
                </td>
                <td>
                    <input type="text" name="rate[]" class="rate form-control border-none text-center" value="<?=$itemData[0]["SellingPrice"]?>" onchange="ttotal('cartItem<?=$trCount?>')"  onkeyup="ttotal('cartItem<?=$trCount?>')" onkeydown="ttotal('cartItem<?=$trCount?>')" onkeypress="ttotal('cartItem<?=$trCount?>')" id="rate-cartItem<?=$trCount?>"required>
                    <input type="hidden" name="original_rate[]" id="original_rate-cartItem<?=$trCount?>" value="<?=$itemData[0]["SellingPrice"]?>">
                </td>
                <td>
                    <select name="discountType[]" id="discountType-cartItem<?=$trCount?>" class="discountType form-select border-none text-center preDefault" onchange="ttotal('cartItem<?=$trCount?>')"  onkeyup="ttotal('cartItem<?=$trCount?>')" onkeydown="ttotal('cartItem<?=$trCount?>')" onkeypress="ttotal('cartItem<?=$trCount?>')"  required>
                        <option value="1" >%</option>
                        <option value="2" >Rs.</option>
                    </select>
                </td>
                <td>
                    <input type="text" name="discount[]" class="discount form-control border-none text-center preDefault" value="0.00"  onchange="ttotal('cartItem<?=$trCount?>')"  onkeyup="ttotal('cartItem<?=$trCount?>')" onkeydown="ttotal('cartItem<?=$trCount?>')" onkeypress="ttotal('cartItem<?=$trCount?>')" id="discount-cartItem<?=$trCount?>" required>
                </td>
                <td>
                    <input type="text" name="totals[]" class="totals form-control border-none text-center preDefault" value="<?=$itemData[0]["SellingPrice"]?>"  onchange="ttotal('cartItem<?=$trCount?>')"  onkeyup="ttotal('cartItem<?=$trCount?>')" onkeydown="ttotal('cartItem<?=$trCount?>')" onkeypress="ttotal('cartItem<?=$trCount?>')" id="total-cartItem<?=$trCount?>" required>
                    <input type="hidden" name="original_total[]" class="original_total" id="original_total-cartItem<?=$trCount?>" value="<?=$itemData[0]["SellingPrice"]?>">
                </td>
                <td>
                    <button class="btn btn-danger removeid"><i class="ti ti-trash"></i></button>
                </td>
            </tr>
        <?php
    }
    else
    {
        echo "No Inventory Found";
    }

}

else if($_GET['type'] == 'item_search')
{
    $txt_search = !empty($_GET['search']) ? $_GET['search']: '';
    $shop_id = isset($_SESSION['shop_id']) ? $_SESSION['shop_id']: '0';
    if(isset($_GET["id"]))
    {
        $sql = "SELECT * FROM products p
        INNER JOIN inventory i ON i.products_PDID=p.PDID
        WHERE i.shop_SHID=".$shop_id." AND (p.Barcode LIKE '%".$txt_search."%' OR p.ItemName LIKE '%".$txt_search."%'  OR p.ProductNo LIKE '%".$txt_search."%' ) AND p.PDID!='$_GET[id]' GROUP BY i.products_PDID;";
    }
    else
    {
        $sql = "SELECT * FROM products p
                INNER JOIN inventory i ON i.products_PDID = p.PDID
                INNER JOIN pricehistory ph ON ph.Inventory_INID = i.INID
                WHERE i.shop_SHID = '$shop_id'
                AND (p.Barcode LIKE '%$txt_search%' 
                    OR p.ItemName LIKE '%$txt_search%'  
                    OR p.ProductNo LIKE '%$txt_search%')
                AND i.INID = (SELECT MAX(i2.INID) 
                            FROM inventory i2 
                            WHERE i2.products_PDID = p.PDID 
                            AND i2.shop_SHID = i.shop_SHID)
                ORDER BY i.INID DESC;";
    }

   // $sql = "SELECT * FROM products WHERE concat(Barcode, ItemName) LIKE '%".$txt_search."%' AND shop_SHID=".$shop_id.";";
    
    
    $dbObj = new DBTransactions();
    $itemData = $dbObj->getData($sql);

    if(!empty($itemData))
    {
        $itemResult = array();
        foreach($itemData as $row)
        {
            $data['id'] = $row['PDID'];
            $data['text'] = $row['ItemName'];
            $data['text'] .= " - ".$row['Barcode'];

            array_push($itemResult, $data);
        }//foreach

    }//has items
    echo json_encode($itemResult);
}//has type