<?php 
include "../Includes/includes.php";
$shop_id = $_SESSION['shop_id'];

$dbObj = new DBTransactions();

/**
 * Clean the short barcode code typed on the category form.
 *
 * The code is pasted straight into a barcode, so only letters and digits are
 * allowed - a stray space or slash is unencodable in half the symbologies.
 * An empty result is stored as NULL, which tells the generator to derive the
 * code from the name instead.
 */
function catCleanCode($value, $max = 12)
{
    $value = strtoupper(trim((string) $value));
    $value = preg_replace('/[^A-Z0-9]/', '', $value);

    if ($value === null || $value === '') {
        return null;
    }//nothing usable - derive it later

    return substr($value, 0, $max);
}//catCleanCode

if(isset($_POST['btn_save_category']))
{
    //get company count
    $category_name = $_POST['cat_name'];
    if(!empty($category_name))
    {
        //check duplicates
        $catObj = new Category();
        $catDuplicate = $catObj->getCategoryByName($shop_id, $category_name);
        $catDuplicaeCount = intval($catDuplicate[0]['CategoryCount']);

        if($catDuplicaeCount > 0)
        {
            header("Location: ../Public/category.php");
            $_SESSION['category_update'] = 1; //duplicate entry
            die("Error: Category name already exists.");
            
        }   //has duplicates
        else
        {
            $catData = $catObj->getCategoryCount($shop_id);
            $category_count = intval($catData[0]['CategoryCount']);
            $category_count += 1;
        
            $commObj = new Common();
            $category_no = $commObj->createCount("MC", $category_count);

            $category_code = catCleanCode(isset($_POST['cat_code']) ? $_POST['cat_code'] : '');

            $catObj->setCategory($category_no, $category_name, $shop_id, $category_code);

            header("Location: ../Public/category.php");
            $_SESSION['category_update'] = 2; //save success
        } //unique name
    }     //has category name
    else
    {
        header("Location: ../Public/category.php");
        $_SESSION['category_update'] = 0; //no category name
        die("Error: No category name.");
    } //no category Name
}    //save category

else if(isset($_POST['btn_update_category']))
{
    $shop_id = $_SESSION['shop_id'];
    //get company count
    $category_name = $_POST['cat_name'];
    $category_id = $_POST['hide_category_id'];
    if(!empty($category_name))
    {
        //check duplicates
        $catObj = new Category();
        //skip the row being edited, or renaming nothing counts as a duplicate
        $catDuplicate = $catObj->getCategoryByName($shop_id, $category_name, $category_id);
        $catDuplicaeCount = intval($catDuplicate[0]['CategoryCount']);

        if($catDuplicaeCount > 0)
        {
            header("Location: ../Public/category.php");
            $_SESSION['category_update'] = 1; //duplicate entry
            die("Error: Category name already exists.");
        }//has duplicate
        else
        {
            $category_code = catCleanCode(isset($_POST['cat_code']) ? $_POST['cat_code'] : '');

            $catObj->editCategory($category_name, $category_id, $category_code);

            header("Location: ../Public/category.php");
            $_SESSION['category_update'] = 3; //uPDATE success
        }//no duplicates
    }//has category
    else
    {
        header("Location: ../Public/category.php");
        $_SESSION['category_update'] = 0; //no category name
        die("Error: No category name.");
    } //no category Name
} //update category

if(isset($_POST['btn_delete_category']))
{
    $category_id = $_POST['hide_category_id'];
    
    $sql = "SELECT * FROM subcategories WHERE categories_CTID = ".$category_id.";";
    //check constrains
    $catData = $dbObj->getData($sql);
    if(empty($catData))
    {
        $catObj = new Category();
        $catObj->deleteCategory($category_id);

        $_SESSION['category_update'] = 5;
        header("Location: ../Public/category.php");
        exit();
    } //no subcategory
    else
    {
        $_SESSION['category_update'] = 4;
        header("Location: ../Public/category.php");
        die("Error: cannot delete, Foreign key constrain.");
    } //has sub category
} //delete

//=============================== Sub category ===============================//
else if(isset($_POST['btn_save_subcat']))
{
    $category_id = $_POST['cmb_main_category'];
    $subcat_name = $_POST['subcat_name'];

    if($category_id > 0 and !empty($subcat_name))
    {
        //check duplicates
        $subcatObj = new Category();
        $subcatData = $subcatObj->getSubcatDuplicate($category_id, $subcat_name, $shop_id);
        if(empty($subcatData))
        {
            //get count
            $subcatCount = $subcatObj->getSubcategoryCount($shop_id);
            $subcat_count = intval($subcatCount[0]['SubcatCount']);
            $subcat_count += 1;

            $commObj = new Common();
            $subcat_no = $commObj->createCount("SC", $subcat_count);

            $subcat_code = catCleanCode(isset($_POST['subcat_code']) ? $_POST['subcat_code'] : '');

            $subcatObj->setSubCategory($subcat_no, $subcat_name, $category_id, $subcat_code);

            $_SESSION['subcategory_update'] = 2; //save success
            header("Location: ../Public/subcategory.php");
            exit();
        }//no duplicates
        else
        {
            header("Location: ../Public/subcategory.php");
            $_SESSION['subcategory_update'] = 1; //duplicate entry
            die("Error: Sub Category name already exists.");
        }//has duplicates

    }//has category and name
    else
    {
        header("Location: ../Public/subcategory.php");
        $_SESSION['subcategory_update'] = 0; //no category name
        die("Error: No category name.");
    }//no category or name
}//save category

else if(isset($_POST['btn_update_subcat']))
{
    $subcatObj = new Category();
    $subcat_id = $_POST['hide_subcat_id'];
    $category_id = $_POST['cmb_main_category'];
    $subcat_name = $_POST['subcat_name'];

    if(!empty($subcat_name)) 
    {
        //check duplicates, skipping the row being edited
        $subcatData = $subcatObj->getSubcatDuplicate($category_id, $subcat_name, $shop_id, $subcat_id);

        if(empty($subcatData))
        {
            $subcat_code = catCleanCode(isset($_POST['subcat_code']) ? $_POST['subcat_code'] : '');

            $subcatObj->editSubcategory($subcat_name, $category_id, $subcat_id, $subcat_code);

            $_SESSION['subcategory_update'] = 3; //update success
            header("Location: ../Public/subcategory.php");
        }//no duplicates
        else
        {
            header("Location: ../Public/subcategory.php");
            $_SESSION['subcategory_update'] = 1; //duplicate entry
            die("Error: Sub Category name already exists.");
        }//has duplicates

    }//has category and name
    else
    {
        header("Location: ../Public/subcategory.php");
        $_SESSION['subcategory_update'] = 0; //no category name
        die("Error: No category name.");
    }//no category or name
}    //update subcat

if(isset($_POST['btn_delete_subcat']))
{
    $subcat_id = $_POST['hide_subcat_id'];
    $sql = "SELECT * FROM products WHERE Subcategories_SCID = ".$subcat_id.";";
    $subcatData = $dbObj->getData($sql);
    
    if(empty($subcatData))
    {
        $catObj = new Category();
        $catObj->deleteSubcategory($subcat_id);

        $_SESSION['subcategory_update'] = 5;
        header("Location: ../Public/subcategory.php");
        //delete sub category
    }   //no products
    else
    {
        $_SESSION['subcategory_update'] = 4;
        header("Location: ../Public/subcategory.php");
        die("Error: cannot delete, Foreign key constrain.");
    }//has related products
}//delete subcat

// else
// {
//     header("Location: ../Public/subcategory.php");
//     $_SESSION['subcategory_update'] = 6; //no category name
// }