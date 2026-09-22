<?php 

class sysModels extends Dbh
{
    public function setsysModels($ModuleName)
    {
        try 
        {
            $sql = "INSERT INTO sysmodules(ModuleName) VALUES(?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$ModuleName]);
        }
        catch (PDOException $e) {
            die("Error: Unable to Add Modules. " . $e->getMessage());
        }
    }

    public function getModules()
    {
        try 
        {
            $stmt = $this->connect()->prepare("SELECT SMID, ModuleName FROM sysmodules");
            $stmt->execute();            
            $Modules = $stmt->fetchAll();
            return $Modules;
        } 
        catch (PDOException $e) {
            die("Error: Unable to fetch Modules. " . $e->getMessage());
        }
    }

    public function editModule($SMID, $ModuleName)
    {
        try 
        {
            $sql="UPDATE sysmodules SET ModuleName=? WHERE SMID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$ModuleName,$SMID]);    
        }
        catch (PDOException $e) {
            die("Error: Unable to update Modules. " . $e->getMessage());
        }
    }//edit module

    public function getOneModule($SMID)
    {
        $sql = "SELECT * FROM sysmodules WHERE SMID = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$SMID]);
        return $stmt->fetchAll();
    }//get one module

    public function setFeatures($FeatureName,$SysModuleID)
    {
        try 
        {
            $stmt = $this->connect()->prepare("INSERT INTO sysfeatures(FeatureName,SystemModules_SMID) VALUES(?,?)");
            $stmt->execute([$FeatureName,$SysModuleID]);
        } 
        catch (PDOException $e) {
            die("Error: Unable to Add features. " . $e->getMessage());
        }
    }

    public function getFeatures()
    {
        try 
        {
            $stmt = $this->connect()->prepare("SELECT * FROM `sysfeatures` AS SF INNER JOIN `sysmodules` SM ON SF.SystemModules_SMID = SM.SMID ORDER BY SF.SystemModules_SMID");
            $stmt->execute();            
            $Modules = $stmt->fetchAll();
            return $Modules;
        } 
        catch (PDOException $e) {
            die("Error: Unable to fetch Features. " . $e->getMessage());
        }
    }
    public function getShopFeatures()
    {
        try 
        {
            $stmt = $this->connect()->prepare("SELECT * FROM `shopfeatures`");
            $stmt->execute();            
            $Modules = $stmt->fetchAll();
            return $Modules;
        } 
        catch (PDOException $e) {
            die("Error: Unable to fetch Features. " . $e->getMessage());
        }
    }

    public function editFeatures($SFID, $FeatureName)
    {
        try 
        {
            $sql="UPDATE sysfeatures SET FeatureName=? WHERE SFID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$FeatureName,$SFID]);    
        }
        catch (PDOException $e) {
            die("Error: Unable to update Features. " . $e->getMessage());
        }
    }//edit Features

    public function getOneFeature($SFID)
    {
        try 
        {
            $sql = "SELECT * FROM `sysfeatures` AS SF INNER JOIN `sysmodules` SM ON SF.SystemModules_SMID = SM.SMID WHERE SF.SFID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$SFID]);
            return $stmt->fetchAll();
        }
        catch (PDOException $e) {
            die("Error: Unable to update Features. " . $e->getMessage());
        }
    }//get one Feature
}