<?php 

session_start();
include '../../Includes/config.php';
include '../../Model/wholesale_invoice_class.php';
include '../../Model/user_class.php';
include '../../Model/shop_class.php';
include '../../Includes/authcheck.php';

$wholesale_invoice= new wholesale_invoice();
$shopObj=new Shop();
$shop_id=$_SESSION['shop_id'];
if(isset($_POST["items"]))
{
    $pro_id=$_POST["items"];
    $SelectedID=$_POST["SelectedID"];
    $has=$shopObj->hasMinus($shop_id);
    $shopData=$shopObj->getOneShop($shop_id);
    $product=$wholesale_invoice->getProductInfor($pro_id);
    $commonStock=$shopData[0]["is_commonStock"];
    $com_id=$shopData[0]["CMID"];
    $minus=0;
    if($has==1)
    {
        $minus=1;
    }
    else
    {
        $minus=0;
    }
    $batch=$wholesale_invoice->select_batch_with_pro_id(pro_id: $pro_id,shop_id: $shop_id,commonStock:$commonStock,company:$com_id);
    if($product[0]["ItemType"]=="P")
    {
        if(count($batch)==0)
        {
            
            if($minus==1)
            {
                $batch2=$wholesale_invoice->select_batch_with_pro_id(pro_id: $pro_id,shop_id: $shop_id,minus: $minus,company:$com_id,commonStock:$commonStock);
                if(count($batch2)==0)
                {
                    ?>
                    <script>
                        alert("No Batch Available")
                    </script>
                    <option value="">No Batch</option>
                    <?php
                }
                else
                {
                    ?>
                    <script>
                        $("#prodDes<?=$SelectedID?>").removeAttr("disabled");
                        $("#prodDes<?=$SelectedID?>").val("<?=$product[0]["ProdDescription"]?>");
                    </script>
                    <?php
                    foreach ($batch2 as $row) 
                    {
                        ?>
                        <option value="<?=$row["INID"]?>"><?=$row["batch"]?> - <?=$row["rate"]?></option>
                        <?php
                    }
                }
            }       
            else
            {
                ?>
                <script>
                    alert("No Batch Available")
                </script>
                <option value="">No Batch</option>
                <?php
            }

            
        }
        else
        {
            ?>
            <script>
                $("#prodDes<?=$SelectedID?>").removeAttr("disabled");
                $("#prodDes<?=$SelectedID?>").val("<?=$product[0]["ProdDescription"]?>");
            </script>
            <?php
            foreach ($batch as $row) 
            {
                    ?>
                    <option value="<?=$row["INID"]?>"><?=$row["batch"]?> - <?=$row["rate"]?></option>
                    <?php
            }
        }
    }
    else
    {
        $batch2=$wholesale_invoice->select_batch_with_pro_id(pro_id: $pro_id,shop_id: $shop_id,minus: 1,commonStock:$commonStock,company: $com_id);
        if(count($batch2)==0)
        {
            ?>
            <script>
                alert("No Batch Available")
            </script>
            <option value="">No Batch</option>
            <?php
        }
        else
        {
            ?>
            <script>
                $("#prodDes<?=$SelectedID?>").removeAttr("disabled");
                $("#prodDes<?=$SelectedID?>").val("<?=$product[0]["ProdDescription"]?>");
            </script>
            <?php
            foreach ($batch2 as $row) 
            {
                ?>
                <option value="<?=$row["INID"]?>"><?=$row["batch"]?> - <?=$row["rate"]?></option>
                <?php
            }
        }
    }
    
    
}
elseif(isset($_POST["item_id"]))
{
    $pro_id=$_POST["item_id"];
    $batch_id=$_POST["batch_id"];
    $hasbatchNo=$shopObj->hasbatchNo($shop_id);
    $shopData=$shopObj->getOneShop($shop_id);
    $batch=$wholesale_invoice->select_with_pro_id_batch_id($pro_id,$batch_id,$hasbatchNo);
    if(count($batch)==0)
    {
        
    }
    else
    {
        $itemResult = array();
        foreach ($batch as $row) 
        {
            if($row['UnitConversion']==0.000)
            {
                $row['UnitConversion']=1.000;
            }
            if($hasbatchNo==0)
            {
                $qty=$wholesale_invoice->getAvlQty($pro_id,$shop_id);
                $data['avlQty'] = $qty[0]['avlQty'] * $row['UnitConversion'];
            }
            else
            {
                $data['avlQty'] = $row['avlQty'] * $row['UnitConversion'];
            }
            $data['UnitConversion'] = $row['UnitConversion'];
            $data['rate'] = $row['rate']/$row['UnitConversion'];
            $data['batch'] = $row['batch'];
            $data['DiscountType'] = 1; // percentage discount
            $data['Costrate'] = $row['Costrate'];
            $data['prodDiscount'] = $row['prodDiscount'];
            if($row["prodDiscount"]!="0.00")
            {
                $data['DiscountType'] = 1;
                $data['prodDiscount'] = $row['prodDiscount'];
            }
            elseif($row["prodFlatDiscount"]!="0.00")
            {
                $data['DiscountType'] = 2;
                $data['prodDiscount'] = $row['prodFlatDiscount'];
            }
            else
            {
                $data['DiscountType'] = 1;
                $data['prodDiscount'] = $row['prodDiscount'];
            }
            

            array_push($itemResult, $data);
        }
        echo json_encode($itemResult);
    }
    
}
else
{
    $pro_id=47;
    $batch_id=53;
    $hasbatchNo=$shopObj->hasbatchNo($shop_id);
    $batch=$wholesale_invoice->select_with_pro_id_batch_id($pro_id,$batch_id,$hasbatchNo);
    print_r($batch);  
}

?>