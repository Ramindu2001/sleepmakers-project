<?php 

class SaleSettings extends Dbh
{
    public function setSaleSettings($billAddOption, $qtyAddDuration, $billNoHeader, $settingStat, $shop_id, $countertype_id,$Winvoice_header_text)
    {
        try 
        {
            $sql="INSERT INTO salesettings(billAddOption, qtyAddDuration, billNoHeader, settingStat, shop_id, countertype_id,WbillNoHeader) VALUES(?,?,?,?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$billAddOption, $qtyAddDuration, $billNoHeader, $settingStat, $shop_id, $countertype_id,$Winvoice_header_text]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//save sale setting

    public function editSaleSettings($billAddOption, $qtyAddDuration, $billNoHeader, $settingStat, $shop_id, $countertype_id, $Winvoice_header_text, $setting_id)
    {
        try 
        {
            $sql="UPDATE salesettings SET billAddOption=?, qtyAddDuration=?, billNoHeader=?, settingStat=?, shop_id=?, countertype_id=?,WbillNoHeader=? WHERE SSID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$billAddOption, $qtyAddDuration, $billNoHeader, $settingStat, $shop_id, $countertype_id, $Winvoice_header_text, $setting_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//UPDATE sale settings

    public function deleteSaleSettings($setting_id)
    {
        try 
        {
            $sql="DELETE FROM salesettings WHERE SSID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$setting_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//UPDATE sale settings

}//sale settings