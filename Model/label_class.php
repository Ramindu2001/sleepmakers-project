<?php 

class Label extends Dbh
{
    public function setLabel($LabelName, $LabelPath, $dpi, $numRow, $numCol, $lblWidth, $lblHeight, $stkWidth, $stkHeight, $stkMarginLeft, $stkMarginRight, $stkMarginTop, $stkMarginBottom, $lblStat, $shop_id)
    {
        try 
        {
            $sql="INSERT INTO label(LabelName, LabelPath, dpi, numRow, numCol, lblWidth, lblHeight, stkWidth, stkHeight, stkMarginLeft, stkMarginRight, stkMarginTop, stkMarginBottom, lblStat, shop_id) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$LabelName, $LabelPath, $dpi, $numRow, $numCol, $lblWidth, $lblHeight, $stkWidth, $stkHeight, $stkMarginLeft, $stkMarginRight, $stkMarginTop, $stkMarginBottom, $lblStat, $shop_id]);
        }//try 
        catch (PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//save label

    public function editLabel($LabelName, $LabelPath, $dpi, $numRow, $numCol, $lblWidth, $lblHeight, $stkWidth, $stkHeight, $stkMarginLeft, $stkMarginRight, $stkMarginTop, $stkMarginBottom, $lblStat, $shop_id, $label_id)
    {
        try 
        {
            $sql="UPDATE label SET LabelName=?, LabelPath=?, dpi=?, numRow=?, numCol=?, lblWidth=?, lblHeight=?, stkWidth=?, stkHeight=?, stkMarginLeft=?, stkMarginRight=?, stkMarginTop=?, stkMarginBottom=?, lblStat=?, shop_id=? WHERE LBID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$LabelName, $LabelPath, $dpi, $numRow, $numCol, $lblWidth, $lblHeight, $stkWidth, $stkHeight, $stkMarginLeft, $stkMarginRight, $stkMarginTop, $stkMarginBottom, $lblStat, $shop_id, $label_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//edit label

    public function editLabelStatus($lblStat, $label_id)
    {
        try 
        {
            $sql="UPDATE label SET lblStat=? WHERE LBID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$lblStat, $label_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//edit label


}//label class