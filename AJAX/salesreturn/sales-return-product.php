<?php 
include "../../Includes/config.php";
include "../../Model/sales_return_class.php";

$salesreturn= new Sales_return_class();
if(isset($_POST["id"]))
{
    $id=$_POST["id"];
    $rate=$salesreturn->pricehistorywithID($id);
    $BillQty=$rate[0]["BillQty"];
    $rate=$rate[0]["SellingPrice"];
    echo $rate;
    $item=$_GET["item"];
    ?>
    <script>
        $(document).ready(function(){
            var item = <?=$item?>;
            var data = <?=$rate?>;
            var BillQty = <?=$BillQty?>;
            var data2=data.toFixed(2);
            $("#rate_"+item).val(data2);
            $("#BillQty"+item).val(BillQty);
            $("#BillQty_"+item).text(BillQty);
            $("#SellAmount_"+item).text(data2);
            if($("#returnqty"+item).val()==0 || $("#returnqty"+item).val()=="" ||  $("#returnqty"+item).val()==undefined)
            {
                $("#total_"+item).val("");
            }
            else
            {
                var rate = parseFloat(data);
                var total =parseFloat($("#returnqty"+item).val());
                var total2=rate*total;
                var tot=total2.toFixed(2);
                $("#total_"+item).val(tot);
            }
            $("#returnqty"+item).focus();
        })
        
    </script>
    <?php
}

?>