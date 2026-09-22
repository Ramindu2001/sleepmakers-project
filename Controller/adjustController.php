<?php 
include "../Includes/includes.php";
$shop_id = $_SESSION['shop_id'];
$user_id = $_SESSION['user_id'];

if(isset($_POST['btn_add_adjustment']))
{
    $adjustObj = new Adjustment();
    $adjustData = $adjustObj->getAdjustMax($shop_id);

    $commObj = new Common();
    $adjust_no = 0;
    if(!empty($adjustData))
    {
        $max_value = floatval($adjustData[0]['MaxAdjust']);
        $max_value += 1;
        $adjust_no = $commObj->createCount("AD", $max_value);
    }//has adjustment
    else
    {
        $adjust_no = $commObj->createCount("AD", 1);
    }//else

    //effective date
    date_default_timezone_set("Asia/Colombo");
    $effective_date = date("Y-m-d");

    $adjust_count = 0;
    $adjust_amount = 0;
    $adjust_stat = 0;

    $adjust_type = $_POST['rbd_adjustment_type'];

    $adjustObj->setAdjustHeader($adjust_no, $effective_date, $adjust_count, $adjust_amount, $adjust_stat, $adjust_type, $shop_id, $user_id);

    header("Location: ../Public/adjust-header.php");
}//create new adjustment

if(isset($_POST['btn_pending_adjust']))
{
    $adjust_header_id = $_POST['hide_adjustheader_id'];
    $adjust_stat = 1;
    //add hold to pending
    $adjustObj = new Adjustment();

    //update adjust header
    $adjustObj->editAdjustHeaderStat($adjust_stat, $adjust_header_id);

    //update adjust detail stat
    $adjustObj->editAdjustDetailStat($adjust_stat, $adjust_header_id);

    header("Location: ../Public/adjust-header.php");
}//update hold to pending

if(isset($_POST['btn_verify_adjust']))
{
    /*
    * get Items from adjust detail
    * if(IN) {add items to inventory and price history}
    * else{deduct items from inventory}
    */
    $adjust_header_id = $_POST['hide_adjustheader_id'];

    $sql = "SELECT * FROM adjustheader WHERE AHID = ".$adjust_header_id.";";
    $dbObj =  new DBTransactions();
    $headerData = $dbObj->getData($sql);

    $adjust_type = $headerData[0]['AdjustmentType_ITID']; //if(type==1){IN}else{OUT}

    $sql = "SELECT * FROM adjustproddetails WHERE AdjustHeader_AHID = ".$adjust_header_id.";";
    $dbObj =  new DBTransactions();
    $dbData = $dbObj->getData($sql);

    //get current date time
    date_default_timezone_set("Asia/Colombo");
    $effective_date = date("Y-m-d");

    $row_count = 0;
    $item_count = 0;
    $total_price = 0;

    $invObj = new Inventory();
    if($adjust_type == '1')
    {
        $proddetails=count($dbData);
        for ($i=0; $i <$proddetails ; $i++) 
        { 
            $qty=$dbData[$i]["AdjustProdQty"];
            $INID=$dbData[$i]["InventoryID"];

            $item_count += floatval($dbData[$i]['AdjustProdQty']);
            $total_price += floatval($dbData[$i]['AdjustProdAmount']);

            $invObj->update_qty($qty,$INID);
            // echo "Set " . $i . "<br>";
        }
    }//adjust IN
    else
    {
        foreach($dbData as $row)
        {
            $row_count += 1;
            $item_count += floatval($row['AdjustProdQty']);
            $total_price += floatval($row['AdjustProdAmount']);

            //update inventory reduce inventory qty
            $adjust_out_qty = floatval($row['AdjustProdQty']);
            $product_id = $row['products_PDID'];
            $batch_id = $row['batch_id'];

            $purchase_price = $row['UnitPurchasePrice'];
            $selling_price = $row['UnitSellingPrice'];

            $mnf_date = empty($row['MnfDate']) ? date("Y-m-d") : $row['MnfDate'];
            $exp_date = empty($row['ExpDate']) ? date("Y-m-d") : $row['ExpDate'];

            $variation_id = $row['VariationID'];

            //get inventory id
            $sql_1 = "SELECT * FROM `inventory` WHERE products_PDID = ".$product_id." AND BatchID = '".$batch_id."';";
            $invData = $dbObj->getData($sql_1);
            $inventory_id = $invData[0]['INID'];
            $current_qty = floatval($invData[0]['CurrentQty']);

            $new_qty =  $current_qty - $adjust_out_qty;

            $invObj->editInvCurrentQty($new_qty,$inventory_id);

        }//foreach adjust detail
    }//adjust OUT

    //update totals
    $adjustObj = new Adjustment();
    $adjustObj->editAdjustHeaderTotal($effective_date, $item_count, $total_price, $adjust_header_id);

    //update stat
    $adjust_stat = 2;
    $adjustObj->editAdjustHeaderStat($adjust_stat, $adjust_header_id);

    $adjustObj->editAdjustDetailStat($adjust_stat, $adjust_header_id);

    header("Location: ../Public/adjust-header.php");
    
}//varify adjustment