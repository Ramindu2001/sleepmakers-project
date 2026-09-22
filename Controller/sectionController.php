<?php 
include "../Includes/includes.php";
$shop_id = $_SESSION['shop_id'];
$sectionObj = new Section();

if(isset($_POST['btn_save_section']))
{
    $section_name = $_POST['section_name'];
    //check section name
    if(!empty($section_name))
    {   
        //check duplicates
        $secDuplicate = $sectionObj->getSectionByName($section_name, $shop_id);
        if(empty($secDuplicate))
        {
            //save section
            $secCount = $sectionObj->getSectionCount($shop_id);
            $section_count = intval($secCount[0]['SectionCount']);
            $section_count += 1;

            $commObj = new Common();
            $section_no = $commObj->createCount("SE", $section_count);

            $sectionObj->setSection($section_no, $section_name, $shop_id);
            $_SESSION['section_update'] = 2;
            header("Location: ../Public/sections.php");
        }//no duplicates
        else
        {
            $_SESSION['section_update'] = 1;
            header("Location: ../Public/sections.php");
            die("Error: Section name duplicate!");
        }//has duplicates
    }//has section name
    else
    {
        $_SESSION['section_update'] = 0;
        header("Location: ../../Public/sections.php");
        die("Error: Section name empty!");
    }//no section name
}//save sections

if(isset($_POST['btn_update_section']))
{
    $section_id = $_POST['hide_section_id'];
    $section_name = $_POST['section_name'];
    //check section name
    if(!empty($section_name))
    {   
        //check duplicates
        $secDuplicate = $sectionObj->getSectionByName($section_name, $shop_id);
        if(empty($secDuplicate))
        {
            $sectionObj->editSection($section_name, $section_id);
            $_SESSION['section_update'] = 3;
            header("Location: ../Public/sections.php");
        }//no duplicates
        else
        {
            $_SESSION['section_update'] = 1;
            header("Location: ../Public/sections.php");
            die("Error: Section name duplicate!");
        }//has duplicates
    }//has section name
    else
    {
        $_SESSION['section_update'] = 0;
        header("Location: ../../Public/sections.php");
        die("Error: Section name empty!");
    }//no section name
}//update section

if(isset($_POST['btn_delete_section']))
{
    $section_id = $_POST['hide_section_id'];

    // echo "section - " . $section_id . "<br>";

    $dbObj = new DBTransactions();
    //check racks
    $sql = "SELECT count(RKID) as rack_count FROM `rack` WHERE Sections_SEID = '".$section_id."';";

    $rackData = $dbObj->getData($sql);
    $rack_count = floatval($rackData[0]['rack_count']);

    // echo "rack count - " . $rack_count;

    if($rack_count == 0)
    {
        $secObj = new Section();
        $secObj->deleteSection($section_id);

        $_SESSION['section_update'] = 4;
        header("Location: ../Public/sections.php");
    }//can delete from system
    else
    {
        $_SESSION['section_update'] = 5;
        header("Location: ../Public/sections.php");
    }//can't delete 

}//delete section

//================================== Racks ===================================//

if(isset($_POST['btn_save_rack']))
{
    $section_id = $_POST['cmb_sections'];
    $rack_name = $_POST['rack_name'];

    if($section_id > 0)
    {
        if(!empty($rack_name))
        {
            //check duplicates
            $secDuplicate = $sectionObj->getRackDuplicate($section_id, $rack_name, $shop_id);
            if(empty($secDuplicate))
            {
                
                $rackCount = $sectionObj->getRackCount($shop_id);
                $rack_count = intval($rackCount[0]['RackCount']);
                $rack_count += 1;

                $commObj = new Common();
                $rack_no = $commObj->createCount("RK", $rack_count);

                $sectionObj->setRacks($rack_no, $rack_name, $section_id);

                $_SESSION['rack_update'] = 3;
                header("Location: ../Public/racks.php");
            }//no duplicate
            else
            {
                $_SESSION['rack_update'] = 2;
                header("Location: ../Public/sections.php");
                die("Error: Duplicate entry!");
            }//has duplicate
        }//has rack name
        else
        {
            $_SESSION['rack_update'] = 1;
            header("Location: ../Public/sections.php");
            die("Error: No rack name.");
        }//no rack name
    }//has section
    else
    {
        $_SESSION['rack_update'] = 0;
        header("Location: ../Public/sections.php");
        die("Error: No section selectd!");
    }//no section
}//save racks

if(isset($_POST['btn_update_rack']))
{
    $rack_id = $_POST['hide_rack_id'];
    $section_id = $_POST['cmb_sections'];
    $rack_name = $_POST['rack_name'];

    if($section_id > 0)
    {
        if(!empty($rack_name))
        {
            //check duplicates
            $secDuplicate = $sectionObj->getRackDuplicate($section_id, $rack_name, $shop_id);
            if(empty($secDuplicate))
            {
                //$sectionObj->setRacks($rack_no, $rack_name, $section_id);
                $sectionObj->editRacks($rack_name, $section_id, $rack_id);

                $_SESSION['rack_update'] = 4;
                header("Location: ../Public/racks.php");
            }//no duplicate
            else
            {
                $_SESSION['rack_update'] = 2;
                header("Location: ../Public/racks.php");
                die("Error: Duplicate entry!");
            }//has duplicate
        }//has rack name
        else
        {
            $_SESSION['rack_update'] = 1;
            header("Location: ../Public/racks.php");
            die("Error: No rack name.");
        }//no rack name
    }//has section
    else
    {
        $_SESSION['rack_update'] = 0;
        header("Location: ../Public/racks.php");
        die("Error: No section selectd!");
    }//no section
}//update racks

if(isset($_POST['btn_delete_rack']))
{
    $rack_id = $_POST['hide_rack_id'];

    $dbObj = new DBTransactions();
    //check racks
    $sql = "SELECT count(GDID) as grn_count FROM `grndetails` WHERE Rack_RKID = '".$rack_id."';";

    $rackData = $dbObj->getData($sql);
    $grn_count = floatval($rackData[0]['grn_count']);

    if($grn_count == 0)
    {
        $secObj = new Section();
        $secObj->deleteRack($rack_id);

        $_SESSION['rack_update'] = 5;
        header("Location: ../Public/racks.php");
    }//can delete rack
    else
    {
        $_SESSION['rack_update'] = 6;
        header("Location: ../Public/racks.php");
    }

}//delete rack