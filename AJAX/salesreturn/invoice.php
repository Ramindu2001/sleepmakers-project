<?php 

session_start();
include '../../Includes/config.php';
include '../../Model/wholesale_invoice_class.php';
include '../../Model/user_class.php';
include '../../Model/shop_class.php';
include '../../Includes/authcheck.php';

$wholesale_invoice= new wholesale_invoice();
$shopObj=new Shop();
if(isset($_POST["items"]))
{
    $pro_id=$_POST["items"];
    $shop_id=$_SESSION['shop_id'];
    $has=$shopObj->hasMinus($shop_id);
    $minus=0;
    if($has==1)
    {
        $minus=1;
    }
    else
    {
        $minus=0;
    }
    $batch=$wholesale_invoice->select_batch_with_pro_id($pro_id,$shop_id);
    if(count($batch)==0)
    {
        
        if($minus==1)
        {
            $batch2=$wholesale_invoice->select_batch_with_pro_id($pro_id,$shop_id,$minus);
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
                <option value="">Select Batch</option>
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
        <option value="">Select Batch</option>
        <?php
        foreach ($batch as $row) 
        {
                ?>
                <option value="<?=$row["INID"]?>"><?=$row["batch"]?> - <?=$row["rate"]?></option>
                <?php
        }
    }
    
}
elseif(isset($_POST["item_id"]))
{
    $pro_id=$_POST["item_id"];
    $batch_id=$_POST["batch_id"];
    $batch=$wholesale_invoice->select_with_pro_id_batch_id($pro_id,$batch_id);
    if(count($batch)==0)
    {
        
    }
    else
    {
        $itemResult = array();
        foreach ($batch as $row) 
        {
            
            $data['rate'] = $row['rate'];
            $data['avlQty'] = $row['avlQty'];
            $data['batch'] = $row['batch'];

            array_push($itemResult, $data);
        }
        echo json_encode($itemResult);
    }
    
}

?>