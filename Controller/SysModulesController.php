<?php 
include "../Includes/includes.php";

//system Module 
//Insert
if(isset($_POST['btn_save_Module']))
{
    //fetch data
    $ModuleName = $_POST['ModuleName'];
   
    //create Module
    $ModuleObj = new sysModels();
    $ModuleObj->setsysModels($ModuleName);

    header("Location: ../Public/SystemModulesList.php");
}

//Update
if(isset($_POST['btn_Update_Module']))
{
    //fetch data
    $ModuleName = $_POST['ModuleName'];
    $Mod_Id = $_POST['hide_Mod_id'];

   
    // update Module
    $ModuleObj = new sysModels();
    $ModuleObj->editModule($Mod_Id,$ModuleName);

    header("Location: ../Public/SystemModulesList.php");
}

//System Features
//Insert
if(isset($_POST['btn_save_Features']))
{
    
    //fetch data
    $FeatureName = $_POST['FeatureName'];
    $SysModuleID = $_POST['ModuleName'];
       
    //create Features
    $ModuleObj = new sysModels();
    $ModuleObj->setFeatures($FeatureName,$SysModuleID);

    header("Location: ../Public/SystemFeaturesList.php");
}

//Update
if(isset($_POST['btn_Update_Feature']))
{
    //fetch data
    $FeatureName = $_POST['FeatureName'];
    $Fe_Id = $_POST['hide_Fe_id'];

    // Update feature
    if (!empty($FeatureName) && !empty($Fe_Id)) {
        $ModuleObj = new sysModels();
        $ModuleObj->editFeatures($Fe_Id, $FeatureName);
        header("Location: ../Public/SystemFeaturesList.php");
        exit(); 
    } 
    else 
    {
        echo "Error: Feature name or ID is missing.";  
    }    
}