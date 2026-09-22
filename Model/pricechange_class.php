<?php 

class Pricechange extends Dbh
{
    public function insert_price_change($product_id,$varriation_id,$batch_id,$old_selling_price,$old_label_price,$new_selling_price,$new_label_price,$user,$price_history_id,$shop_id)
    {
        try 
        {
            $date=date("Y-m-d H:i:s");
            $sql="INSERT INTO `pricechangelog`( `product_PDID`, `varriation_id`, `batch_id`, `old_selling_price`, `old_label_price`, `new_selling_price`, `new_label_price`, `user_USID`, `priceHistory_PHID`, `status`,`shop_id`,`date`) VALUES ('$product_id','$varriation_id','$batch_id','$old_selling_price','$old_label_price','$new_selling_price','$new_label_price','$user','$price_history_id','1','$shop_id','$date');";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
        }//try 
        catch (PDOException $e)
        {
            die("Error: Unable to insert data to pricelog: " . $e->getMessage());
        }//catch
    }

    public function update_price_history($new_selling_price,$new_label_price,$price_history_id)
    {
        try 
        {
            $sql="UPDATE `pricehistory` SET `SellingPrice`='$new_selling_price',`labelPrice`='$new_label_price' WHERE PHID='$price_history_id';";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
        }//try 
        catch (PDOException $e)
        {
            die("Error: Unable to Update data: " . $e->getMessage());
        }//catch

    }

    public function select_pricechange($shop_id)
    {
        try
        {
            $sql = "SELECT * FROM `pricechangelog` pl
            INNER JOIN products p ON p.PDID=pl.product_PDID
            LEFT JOIN variations v ON v.VRID=pl.varriation_id
            INNER JOIN user u ON u.USID=pl.user_USID
            WHERE pl.shop_id='$shop_id' ORDER BY pl.PPCID DESC;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            $data = $stmt->fetchAll();

            return $data;
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from pricelog " . $e->getMessage());
        }
    }
}

?>