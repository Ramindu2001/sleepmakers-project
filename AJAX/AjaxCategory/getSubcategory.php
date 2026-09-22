<?php 
include "../../Includes/config.php";
include "../../Model/category_class.php";

$category_id = $_GET['category_id'];

$catObj = new Category();
$catData = $catObj->getSubcategoryByCatID($category_id);

    echo "<option value='0'>=== Select Sub Category ===</option>";
foreach($catData as $row)
{
    echo "<option value='".$row['SCID']."'>".$row['SubCatName']."</option>";
}//foreach