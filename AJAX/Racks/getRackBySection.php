<?php 
include "../../Includes/config.php";
include "../../Model/section_class.php";

$section_id = $_GET['section_id'];

$sql = "SELECT * FROM rack WHERE Sections_SEID = ".$section_id.";";

$sectionObj = new Section();
$rackDate = $sectionObj->getRackBySection($section_id);

if(!empty($rackDate))
{
    foreach($rackDate as $row)
    {
        echo "<option value='".$row['RKID']."'>".$row['RackName']."</option>";
    }//foreach
}//has racks
else
{
    echo "<option value='1'>Default Rack</option>";
}//no racks