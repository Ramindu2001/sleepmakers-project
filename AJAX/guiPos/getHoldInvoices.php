<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/gui_pos_class.php";
$noRight=1;
$dbObj = new DBTransactions();
$shop_id = $_SESSION['shop_id'];
$guiObj= new guiPOS;
$user_id = $_SESSION['user_id'];
$sql="SELECT * FROM `hold_invoice` WHERE shop_SHID='$shop_id' AND InvStat=1";
$invoices=$dbObj->getData($sql);
if(count($invoices)==0)
{
    ?>
    <tr>
        <td colspan="3"> <span class="d-block text-center">No Results Found</span></td>
    </tr>
    <?php
}
else
{
    foreach ($invoices as $row) 
    {
        ?>
        <tr>
            <td><b><?=$row["Temp_No"]?></b></td>
            <td><b><?=$row["NetAmount"]?></b></td>
            <td class="d-flex justify-content-center">
                <button type="button" class="Add_to_cart btn btn-primary" data-holdid="<?= htmlspecialchars($row['HIID']) ?>">Add to Cart</button>
                <button type="button" class="hold_delete btn btn-danger ms-2" data-holdid="<?=$row["HIID"]?>"><i class="ti ti-trash"></i></button>
            </td>
        </tr>
        <?php
    }
}

?>