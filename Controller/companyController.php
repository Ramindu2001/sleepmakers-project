<?php 
include "../Includes/includes.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if(isset($_POST['btn_save_company']))
{
    $com_type = $_POST['cmb_company_type'];
    $com_name = $_POST['com_name'];
    $com_location = $_POST['com_location'];
    $com_licence = $_POST['com_licence'];
    $com_version = $_POST['com_version'];

    $com_start_date = $_POST['com_start_date'];
    $com_end_date = $_POST['com_expire_date'];

    $is_multicategory = 0;
    if(isset($_POST['chk_multi_category'])){$is_multicategory = 1;}

    $active_company = 0;
    if(isset($_POST['chk_active_company'])){$active_company = 1;}
    $common_stock = 0;
    if(isset($_POST['chk_commonStock_category'])){$common_stock = 1;}

    $size = floatval($_FILES['company_logo']['size']);
    //file upload directry
    $target_dir = "../Assets/Images/Company_Logos/";
    if(!empty($_FILES['company_logo']['name']))
    {
        if($size > 5000000)
        {
            $_SESSION['company_update']=0;
        }//size large
        else
        {
            //set unique logo name
            $comObj = new Company();
            $comData = $comObj->getCompanyCount();
            $company_count = floatval($comData[0]['ComapnyCount']);
            $company_count += 1;

            //naming
            $commonObj = new Common();
            $company_no = $commonObj->createCount("COM", $company_count);
            $company_logo_name = $commonObj->createCount("CL", $company_count);

            $target_file_path = $target_dir . $company_logo_name . basename($_FILES['company_logo']['name']);
            $file_type = pathinfo($target_file_path, PATHINFO_EXTENSION);

            $company_logo_name = $company_logo_name . "." . $file_type;
            $target_file_path = $target_dir . $company_logo_name;

            // Allow certain file formats
            $allow_types = array('jpg','png','PNG','jpeg');
            if(in_array($file_type, $allow_types))
            {
                if(move_uploaded_file($_FILES["company_logo"]["tmp_name"], $target_file_path))
                {
                    $comObj = new Company();
                    $comObj->setCompany($company_no, $com_name, $com_location, $com_licence, $com_version, $company_logo_name, $com_start_date, $com_end_date, $active_company, $is_multicategory, $com_type,$common_stock);

                    header("Location: ../Public/company.php");
                    $_SESSION['company_update'] = 3; //updated successfully
                    exit();
                }//file move
            }//valid file format
            else
            {
                //not supported file type
                $_SESSION['company_update']=1;
                header("Location: ../Public/company.php");
                die;
                
            }//not file type

        }//size ok
    }//has image 
    else
    {
        $comObj = new Company();
        $comData = $comObj->getCompanyCount();
        $company_count = floatval($comData[0]['ComapnyCount']);
        $company_count += 1;

        //naming
        $commonObj = new Common();
        $company_no = $commonObj->createCount("COM", $company_count);
        $company_logo_name ="";

        // Allow certain file formats
        $allow_types = array('jpg','png','PNG','jpeg');
        $comObj = new Company();
        $comObj->setCompany($company_no, $com_name, $com_location, $com_licence, $com_version, $company_logo_name, $com_start_date, $com_end_date, $active_company, $is_multicategory, $com_type,$common_stock);

        header("Location: ../Public/company.php");
        $_SESSION['company_update'] = 3; //updated successfully
        exit();
    }
}//save comapny
elseif(isset($_POST['btn_update_company']))
{
    $com_id = $_POST['hide_com_id'];
    $com_type = $_POST['cmb_company_type'];
    $com_name = $_POST['com_name'];
    $com_location = $_POST['com_location'];
    $com_licence = $_POST['com_licence'];
    $com_version = $_POST['com_version'];

    $com_start_date = $_POST['com_start_date'];
    $com_end_date = $_POST['com_expire_date'];

    $is_multicategory = 0;
    if(isset($_POST['chk_multi_category'])){$is_multicategory = 1;}

    $active_company = 0;
    if(isset($_POST['chk_active_company'])){$active_company = 1;}
    
    $common_stock = 0;
    if(isset($_POST['chk_commonStock_category'])){$common_stock = 1;}

    $size = floatval($_FILES['company_logo']['size']);

    if($size == 0)
    {
        //check name
        if(!empty($com_name))
        {
            $comObj = new Company();
            $comObj->editCompanyNoImage($com_name, $com_location, $com_licence, $com_version, $com_start_date, $com_end_date, $active_company, $is_multicategory, $com_type, $com_id,$common_stock);

            header("Location: ../Public/company.php");
            $_SESSION['company_update'] = 3; //updated successfully
            exit();
        }//has com name
        else
        {
            $_SESSION['company_update'] = 4; //company name empty
            header("Location: ../Public/company.php");
            die("Error: Comapny name empty");
        }//no com name       
    }//size 0 no image
    else
    {
        $size = floatval($_FILES['company_logo']['size']);
        //file upload directry
        $target_dir = "../Assets/Images/Company_Logos/";
        if(!empty($_FILES['company_logo']['name']))
        {
            if($size > 5000000)
            {
                $_SESSION['company_update']=0;
            }//size large
            else
            {
                //delete previous image
                $comObj = new Company();
                $comDataOne = $comObj->getOneCompany($com_id);
                $com_logo_name = $comDataOne[0]['ComLogo'];
                $delete_path = "../Assets/Images/Company_Logos/" . $comDataOne[0]['ComLogo'];

                echo "delete path " . $delete_path . "<br>";
                
                if(!empty($comDataOne[0]['ComLogo']))
                {
                    if(file_exists($delete_path))
                    {
                        unlink($delete_path);
                    }//has file
                    $com_logo_name = $_FILES['company_logo']['name'];

                    $target_file_path = $target_dir . $com_logo_name;
                    $file_type = pathinfo($target_file_path, PATHINFO_EXTENSION);

                    //naming
                    $commonObj = new Common();
                    $com_logo_name = $commonObj->createCount("CL", $com_id);

                    //$com_logo_name = $com_logo_name . "." . $file_type;
                    $target_file_path = $target_dir . $com_logo_name . "." . $file_type;

                    $company_logo_name = $com_logo_name . "." . $file_type;

                    // Allow certain file formats 
                    $allow_types = array('jpg','png','PNG','jpeg');
                    if(in_array($file_type, $allow_types))
                    {
                        if(move_uploaded_file($_FILES["company_logo"]["tmp_name"], $target_file_path))
                        {
                            $comObj = new Company();
                            $comObj->editCompany($com_name, $com_location, $com_licence, $com_version, $company_logo_name, $com_start_date, $com_end_date, $active_company, $is_multicategory, $com_type, $com_id,$common_stock);

                            header("Location: ../Public/company.php");
                            $_SESSION['company_update'] = 5; //updated successfully
                            exit();
                        }//file move
                    }//valid file format
                    else
                    {
                        //------------------------------------------------------------------------- >>>add here
                        //not supported file type
                        //header("Location: ../Public/company.php");
                        $_SESSION['company_update'] = 1;
                        die;
                    }//not file type
                }//has image path
                else
                {
                    $com_logo_name = $_FILES['company_logo']['name'];

                    $target_file_path = $target_dir . $com_logo_name;
                    $file_type = pathinfo($target_file_path, PATHINFO_EXTENSION);

                    //naming
                    $commonObj = new Common();
                    $com_logo_name = $commonObj->createCount("CL", $com_id);

                    //$com_logo_name = $com_logo_name . "." . $file_type;
                    $target_file_path = $target_dir . $com_logo_name . "." . $file_type;

                    $company_logo_name = $com_logo_name . "." . $file_type;

                    // Allow certain file formats 
                    $allow_types = array('jpg','png','PNG','jpeg');
                    if(in_array($file_type, $allow_types))
                    {
                        if(move_uploaded_file($_FILES["company_logo"]["tmp_name"], $target_file_path))
                        {
                            $comObj = new Company();
                            $comObj->editCompany($com_name, $com_location, $com_licence, $com_version, $company_logo_name, $com_start_date, $com_end_date, $active_company, $is_multicategory, $com_type, $com_id,$common_stock);

                            header("Location: ../Public/company.php");
                            $_SESSION['company_update'] = 5; //updated successfully
                            exit();
                        }//file move
                    }//valid file format
                    else
                    {
                        //------------------------------------------------------------------------- >>>add here
                        //not supported file type
                        //header("Location: ../Public/company.php");
                        $_SESSION['company_update'] = 1;
                        die;
                    }//not file type
                }//deleted previous image
            }//size ok
        }//has image 
    }//has new Image

}//update company
else
{
    echo "Something";
}