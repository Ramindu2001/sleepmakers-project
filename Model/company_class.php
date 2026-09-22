<?php 

class Company extends Dbh
{
    public function setCompany($CompanyNo, $ComName, $CompanyLocation, $LicenceNo, $VersionNo, $ComLogo, $ComStartDate, $ComExpireDate, $ComStat, $is_multicategory, $CompanyType_CTID,$common_stock)
    {
        try 
        {
            $sql="INSERT INTO company(CompanyNo, ComName, CompanyLocation, LicenceNo, VersionNo, ComLogo, ComStartDate, ComExpireDate, ComStat, is_multicategory, CompanyType_CTID,is_commonStock) VALUES(?,?,?,?,?,?,?,?,?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$CompanyNo, $ComName, $CompanyLocation, $LicenceNo, $VersionNo, $ComLogo, $ComStartDate, $ComExpireDate, $ComStat, $is_multicategory, $CompanyType_CTID,$common_stock]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//set category

    public function editCompany($ComName, $CompanyLocation, $LicenceNo, $VersionNo, $ComLogo, $ComStartDate, $ComExpireDate, $ComStat, $is_multicategory, $CompanyType_CTID, $CMID,$common_stock)
    {
        try
        {
            $sql="UPDATE company SET ComName=?, CompanyLocation=?, LicenceNo=?, VersionNo=?, ComLogo=?, ComStartDate=?, ComExpireDate=?, ComStat=?, is_multicategory=?, CompanyType_CTID=?,is_commonStock=?
            WHERE CMID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$ComName, $CompanyLocation, $LicenceNo, $VersionNo, $ComLogo, $ComStartDate, $ComExpireDate, $ComStat, $is_multicategory, $CompanyType_CTID,$common_stock, $CMID]);  
        }
        catch(PDOException $e)
        {
            die("Error: Unable to update Features. " . $e->getMessage());
        }   
    }//edit company

    public function editCompanyNoImage($ComName, $CompanyLocation, $LicenceNo, $VersionNo, $ComStartDate, $ComExpireDate, $ComStat, $is_multicategory, $CompanyType_CTID, $CMID,$common_stock)
    {
        try
        {
            $sql="UPDATE company SET ComName=?, CompanyLocation=?, LicenceNo=?, VersionNo=?, ComStartDate=?, ComExpireDate=?, ComStat=?, is_multicategory=?, CompanyType_CTID=?,is_commonStock=?
            WHERE CMID=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$ComName, $CompanyLocation, $LicenceNo, $VersionNo, $ComStartDate, $ComExpireDate, $ComStat, $is_multicategory, $CompanyType_CTID,$common_stock, $CMID]);  
        }
        catch(PDOException $e)
        {
            die("Error: Unable to update Features. " . $e->getMessage());
        }   
    }//edit company

    public function getCompanyCount()
    {
        try{
            $sql = "SELECT count(CMID) as ComapnyCount FROM company;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get company type

    public function getAllCompany()
    {
        try{
            $sql = "SELECT * FROM company
            INNER JOIN companytype ON companytype.CTID = company.CompanyType_CTID;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get company type
    public function getAllShopfeatures($shop_id)
    {
        try{
            $sql = "SELECT * FROM shopfeatures 
            ";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get company type
    public function getOneShopfeatures($shop_id)
    {
        try{
            $sql = "SELECT * FROM shopfeatures sf
            LEFT JOIN shoppermissions sp ON sp.ShopFeature_SPFID=sf.SPFID AND sp.Shop_SHID='$shop_id'
            ";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get company type

    public function getOneCompany($company_id)
    {
        try 
        {
            $sql = "SELECT * FROM company
            INNER JOIN companytype ON companytype.CTID = company.CompanyType_CTID
            WHERE CMID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$company_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get company type

    public function getCompanyByUser($user_id,$UserType=null)
    {
        try
        {
            if($UserType!=null)
            {
                if($UserType==1)
                {
                    $sql = "SELECT *,shop.SHID AS shopID FROM shop
                    LEFT JOIN shopusers ON shop.SHID = shopusers.shop_SHID
                    LEFT JOIN user ON user.USID = shopusers.user_USID
                    INNER JOIN company ON company.CMID = shop.Company_CMID GROUP BY shop.SHID;";
                    $stmt = $this->connect()->prepare($sql);
                    $stmt->execute();
                    return $stmt->fetchAll();
                }
                else
                {
                    $sql = "SELECT * FROM shopusers
                    INNER JOIN shop ON shop.SHID = shopusers.shop_SHID
                    INNER JOIN user ON user.USID = shopusers.user_USID
                    INNER JOIN company ON company.CMID = shop.Company_CMID
                    WHERE user_USID = ? AND shop.ShopStat=1;";
            
                    $stmt = $this->connect()->prepare($sql);
                    $stmt->execute([$user_id]);
                    return $stmt->fetchAll();
                   
                }
            }
            else
            {
                $sql = "SELECT * FROM shopusers
                INNER JOIN shop ON shop.SHID = shopusers.shop_SHID
                INNER JOIN user ON user.USID = shopusers.user_USID
                INNER JOIN company ON company.CMID = shop.Company_CMID
                WHERE user_USID = ?;";
            
                $stmt = $this->connect()->prepare($sql);
                $stmt->execute([$user_id]);
                return $stmt->fetchAll();
                
            }
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
      
    }//get company type

    //===================== get company type =====================//
    public function getCompanyTypes()
    {
        try
        {
            $sql = "SELECT * FROM companytype;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
        
    }//get company type

    protected function getCompanyByQuery($sql)
    {
        try
        {
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get company type
}//class company