<?php 

class Category extends Dbh
{
    public function setCategory($CategoryNo, $CategoryName, $shop_SHID, $CategoryCode = null)
    {
        try 
        {
            //CategoryCode is the short barcode prefix ("BEV"), NULL = derive it
            $sql="INSERT INTO categories(CategoryNo, CategoryName, CategoryCode, shop_SHID) VALUES(?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$CategoryNo, $CategoryName, $CategoryCode, $shop_SHID]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//save category

    public function editCategory($CategoryName, $category_id, $CategoryCode = null)
    {
        try 
        {
            $sql="UPDATE categories SET CategoryName = ?, CategoryCode = ? WHERE CTID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$CategoryName, $CategoryCode, $category_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//update category

    /**
     * Set only the barcode prefix code on a category.
     * Used by "Fill in missing codes" on the barcode settings page.
     */
    public function setCategoryCode($CategoryCode, $category_id)
    {
        try 
        {
            $sql="UPDATE categories SET CategoryCode = ? WHERE CTID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$CategoryCode, $category_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to update data: " . $e->getMessage());
        }//catch
    }//set category code

    public function deleteCategory($category_id)
    {
        try 
        {
            $sql="DELETE FROM categories WHERE CTID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$category_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//update category

    public function getCategoryCount($shop_id)
    {
        try{
            $sql = "SELECT max(CTID) AS CategoryCount FROM categories WHERE shop_SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get category count

    /**
     * How many OTHER categories in this shop already carry this name?
     *
     * $exclude_id is the category being edited. Without it an update that keeps
     * the name matches itself, and the save is rejected as a duplicate - which
     * makes it impossible to change anything else on the category, such as its
     * barcode code.
     */
    public function getCategoryByName($shop_id, $category_name, $exclude_id = 0)
    {
        try
        {
            $sql = "SELECT count(CTID) AS CategoryCount FROM categories
                    WHERE shop_SHID = ? AND CategoryName = ? AND CTID <> ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id, $category_name, (int) $exclude_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }//get category by name

    public function getCategoryByShop($shop_id,$multi=null,$company=null)
    {
        try{
            if($multi==null)
            {
                $sql = "SELECT * FROM categories WHERE shop_SHID = ?;";
                $stmt = $this->connect()->prepare($sql);
                $stmt->execute([$shop_id]);
            }
            else
            {
                if($multi==1)
                {
                    $sql = "SELECT * FROM categories c 
                    INNER JOIN shop s ON s.SHID = c.shop_SHID
                    WHERE s.Company_CMID = ?;";
                    $stmt = $this->connect()->prepare($sql);
                    $stmt->execute([$company]);
                }
                else
                {
                    $sql = "SELECT * FROM categories WHERE shop_SHID = ?;";
                    $stmt = $this->connect()->prepare($sql);
                    $stmt->execute([$shop_id]);
                }
                
            }
            
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }   
    }//get category by shop

    public function getOneCategory($category_id)
    {
        try{
            $sql = "SELECT * FROM categories WHERE CTID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$category_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }   
    }//get category by shop

//=================================== Sub Categories =================================//
    public function setSubCategory($SubCatNo, $SubCatName, $categories_CTID, $SubCatCode = null)
    {
        try 
        {
            //SubCatCode is the short barcode prefix ("JUI"), NULL = derive it
            $sql="INSERT INTO subcategories(SubCatNo, SubCatName, SubCatCode, categories_CTID) VALUES(?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$SubCatNo, $SubCatName, $SubCatCode, $categories_CTID]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }//catch
    }//save category

    public function editSubcategory($subcat_name, $category_id, $subcat_id, $SubCatCode = null)
    {
        try 
        {
            $sql="UPDATE subcategories SET SubCatName=? , SubCatCode=? , categories_CTID=? WHERE SCID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$subcat_name, $SubCatCode, $category_id, $subcat_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to update data: " . $e->getMessage());
        }//catch
    }//update category

    /**
     * Set only the barcode prefix code on a sub category.
     */
    public function setSubcategoryCode($SubCatCode, $subcat_id)
    {
        try 
        {
            $sql="UPDATE subcategories SET SubCatCode = ? WHERE SCID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$SubCatCode, $subcat_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to update data: " . $e->getMessage());
        }//catch
    }//set subcategory code

    public function deleteSubcategory($subcat_id)
    {
        try 
        {
            $sql="DELETE FROM subcategories WHERE SCID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$subcat_id]);
        }//try 
        catch (PDOException $e) 
        {
            die("Error: Unable to update data: " . $e->getMessage());
        }//catch
    }//DELETE category

    public function getOneSubCategory($subcat_id)
    {
        try{
            $sql = "SELECT * FROM subcategories
            INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
            WHERE SCID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$subcat_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }   
    }//get category by shop

    public function getSubcategoryByName($subcat_name, $shop_id)
    {
        try
        {
            $sql = "SELECT * FROM subcategories
            INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
            WHERE SubCatName = ? AND shop_SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$subcat_name, $shop_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }   
    }//get sub category by name

    public function getSubcategoryByShop($shop_id, $multi=null,$company=null)
    {
        try
        {
            
            if($multi==null)
            {
                $sql = "SELECT * FROM subcategories
                INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
                WHERE shop_SHID = ?;";
                $stmt = $this->connect()->prepare($sql);
                $stmt->execute([$shop_id]);
            }
            else
            {
                if($multi==1)
                {
                    $sql = "SELECT * FROM subcategories sc 
                    INNER JOIN categories c ON c.CTID = sc.categories_CTID
                    INNER JOIN shop s ON s.SHID = c.shop_SHID
                    WHERE s.Company_CMID = ?;";
                    $stmt = $this->connect()->prepare($sql);
                    $stmt->execute([$company]);
                }
                else
                {
                    $sql = "SELECT * FROM subcategories
                    INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
                    WHERE shop_SHID = ?;";
                    $stmt = $this->connect()->prepare($sql);
                    $stmt->execute([$shop_id]);
                }                
            }
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }   
    }//get sub category by name

    public function getSubcategoryByCatID($category_id)
    {
        try
        {
            $sql = "SELECT * FROM subcategories
            INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
            WHERE categories_CTID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$category_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }   
    }//get sub category by name

    public function getSubcategoryCount($shop_id)
    {
        try
        {
            $sql = "SELECT max(SCID) as SubcatCount FROM subcategories
            INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
            WHERE shop_SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }   
    }//get sub category by name
    /**
     * Other sub categories under the same category carrying this name.
     * $exclude_id is the sub category being edited - see getCategoryByName().
     */
    public function getSubcatDuplicate($category_id, $subcat_name, $shop_id, $exclude_id = 0)
    {
        $sql = "SELECT * FROM subcategories
        INNER JOIN categories ON categories.CTID = subcategories.categories_CTID
        WHERE categories_CTID = ? AND SubCatName= ? AND shop_SHID = ? AND SCID <> ?;";
        $stmt = $this->connect()->prepare($sql);
        try
        {
            $stmt->execute([$category_id, $subcat_name, $shop_id, (int) $exclude_id]);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }   
    }//get sub category by name
}//class category