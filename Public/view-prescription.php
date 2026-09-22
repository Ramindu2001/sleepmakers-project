<?php 
include "../Includes/includes.php";
include '../Includes/authcheck.php';
$dbObj = new DBTransactions();
$pres_id = 0;
if(!isset($_GET["pres_id"]))
{
    header("Location:../Reports/customer-profiles.php");
}
else
{
    $pres_id=$_GET["pres_id"];
}
$sql = "SELECT * FROM prescriptionheader WHERE PRHID='$pres_id' ";
$itemData = $dbObj->getData($sql);
if(count($itemData)==0)
{
    header("Location:../Reports/customer-profiles.php");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php 
 include '../View/head.php';
 // include '../View/loader.php';

  ?>
  <style>
    .table>:not(caption)>*>* 
    {
        padding: 10px;
    }
    input[readonly]
    {
        background-color: rgb(235, 235, 235);
        border-color: rgb(235, 235, 235);
    }
    
    @media print 
    {
        *{
          visibility: hidden;
       }
       table tr td
       {
           visibility: visible;
       }
       th
       {
           visibility: visible;
       }
       p
       {
           visibility: visible;
       }
       b
       {
           visibility: visible;
       }       
       #tbl_prescription
       {
           visibility: visible;
       }
    }
  </style>
</head>

<body>
<!--  Body Wrapper -->
<div class="h-100vh">
        <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
            data-sidebar-position="fixed" data-header-position="fixed">
            <!-- Sidebar Start -->
            <?php 
             include '../View/sidebar.php';
            ?>
            <!--  Sidebar End -->
            <!--  Main wrapper -->
            <div class="body-wrapper">
                <!--  Header Start -->
                <?php 
                    include '../View/header.php';
                    
                ?>
                <!--  Header End -->
    
                <div class="container-fluid">
                    <h5 class="card-title fw-semibold mb-4">View Prescription</h5>
                    <?php 
                    if(isset($_SESSION["credit_customer"]))
                    {
                        if($_SESSION["credit_customer"]==0)
                        {
                            ?>
                            <div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                <strong>Oops! </strong> Something went wrong, Please try again!
                            </div>
                            <?php
                        }
                        elseif ($_SESSION["credit_customer"]==1) 
                        {
                            ?>
                            <div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                <strong>Success </strong> Payment Added Successfully!
                            </div>
                            <?php
                        }
                        unset($_SESSION["credit_customer"]);
                    }
                    ?>
                    
                    <!-- <button type="button" class="btn btn-primary rounded-pill ml-1 mb-2" id="btn_Add_SysFeature_modal" data-bs-dismiss="modal">Add New Feature</button> -->
                    <br>
                    
                    <div class="card">
                        <div class="card-body">
                            <h4>Customer Prescription - <?=$itemData[0]["pr_no"]?></h4>
                            <?php 
                            $sql2 = "SELECT * FROM prescriptiondetails WHERE prescription_PRHID='$pres_id' AND side='1' AND prescription_type='1' AND `add`='0'";
                            $itemData2 = $dbObj->getData($sql2);    
                            
                            $sql3 = "SELECT * FROM prescriptiondetails WHERE prescription_PRHID='$pres_id' AND side='2' AND prescription_type='1' AND `add`='0'";
                            $itemData3 = $dbObj->getData($sql3); 
                            
                            $sql4 = "SELECT * FROM prescriptiondetails WHERE prescription_PRHID='$pres_id' AND side='1' AND prescription_type='1' AND `add`='1'";
                            $itemData4 = $dbObj->getData($sql4);  
                            
                            $sql5 = "SELECT * FROM prescriptiondetails WHERE prescription_PRHID='$pres_id' AND side='2' AND prescription_type='1' AND `add`='1'";
                            $itemData5 = $dbObj->getData($sql5); 
                            
                            //second
                            $sql6 = "SELECT * FROM prescriptiondetails WHERE prescription_PRHID='$pres_id' AND side='1' AND prescription_type='2' AND `add`='0'";
                            $itemData6 = $dbObj->getData($sql6); 
                            
                            $sql7 = "SELECT * FROM prescriptiondetails WHERE prescription_PRHID='$pres_id' AND side='2' AND prescription_type='2' AND `add`='0'";
                            $itemData7 = $dbObj->getData($sql7); 
                            
                            $sql8 = "SELECT * FROM prescriptiondetails WHERE prescription_PRHID='$pres_id' AND side='1' AND prescription_type='2' AND `add`='1'";
                            $itemData8 = $dbObj->getData($sql8); 
                            
                            $sql9 = "SELECT * FROM prescriptiondetails WHERE prescription_PRHID='$pres_id' AND side='2' AND prescription_type='2' AND `add`='1'";
                            $itemData9 = $dbObj->getData($sql9); 
                            ?>
                            <div class="table-responsive">
                                <table class="table" id="tbl_prescription">
                                    <tr>
                                        <td colspan="8">
                                            <p style="display:inline-block;"><b>Subjective Ref: </b></p>  <?=$itemData[0]["pr_subjective_ref"]?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            <b>Right</b>
                                        </td>
                                        <td colspan="4" class="text-center">
                                            <b>Left</b>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-center"><b>Sph.</b></td>
                                        <td class="text-center"><b>Cyl.</b></td>
                                        <td class="text-center"><b>Axis</b></td>
                                        <td></td>
                                        <td class="text-center"><b>Sph.</b></td>
                                        <td class="text-center"><b>Cyl.</b></td>
                                        <td class="text-center"><b>Axis</b></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td class="text-center">
                                            <?=$itemData2[0]["sph"]?>
                                        </td>
                                        <td class="text-center">
                                            <?=$itemData2[0]["cyl"]?>
                                        </td>
                                        <td class="text-center">
                                            <?=$itemData2[0]["axis"]?>
                                        </td>
                                        <td class="text-center">
                                            <?=$itemData2[0]["none"]?>
                                        </td>
                                        <td class="text-center">
                                            <?=$itemData3[0]['sph']?>
                                        </td>
                                        <td class="text-center">
                                            <?=$itemData3[0]['cyl']?>
                                        </td>
                                        <td class="text-center">
                                            <?=$itemData3[0]['axis']?>
                                        </td>
                                        <td class="text-center">
                                            <?=$itemData3[0]['none']?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><b>Add</b></td>
                                        <td class="text-center">
                                            <?=$itemData4[0]['cyl']?>
                                        </td>
                                        <td class="text-center">
                                            <?=$itemData4[0]['axis']?>
                                        </td>
                                        <td class="text-center">
                                            <?=$itemData4[0]['none']?>
                                        </td>
                                        <td><b>Add</b></td>
                                        <td class="text-center">
                                             <?=$itemData5[0]['cyl']?>
                                        </td>
                                        <td class="text-center">
                                            <?=$itemData5[0]['axis']?>
                                        </td>
                                        <td class="text-center">
                                            <?=$itemData5[0]['none']?>
                                        </td>
                                    </tr>
                                </table>
                                <label for="remark" class="form-label">Remarks: </label>
                                <?=$itemData[0]["pr_remarks"]?>
                                <table class="table mt-4">
                                    <tr>
                                        <td colspan="6">
                                            <p style="display:inline-block;"><b>HB: </b></p>
                                            <?=$itemData[0]["pr_hb"]?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-center">
                                            <b>Right</b>
                                        </td>
                                        <td colspan="3" class="text-center">
                                            <b>Left</b>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Sph.</th>
                                        <th>Cyl.</th>
                                        <th>Axis</th>
                                        <th>Sph.</th>
                                        <th>Cyl.</th>
                                        <th>Axis</th>
                                    </tr>
                                    <tr>
                                        <td><?=$itemData6[0]['sph']?></td>
                                        <td><?=$itemData6[0]['cyl']?></td>
                                        <td><?=$itemData6[0]['axis']?></td>
                                        <td><?=$itemData7[0]['sph']?></td>
                                        <td><?=$itemData7[0]['cyl']?></td>
                                        <td><?=$itemData7[0]['axis']?></td>
                                    </tr>
                                    <tr>
                                        <td><b>Add</b></td>
                                        <td><?=$itemData8[0]['cyl']?></td>
                                        <td><?=$itemData8[0]['axis']?></td>
                                        <td><b>Add</b></td>
                                        <td><?=$itemData9[0]['cyl']?></td>
                                        <td><?=$itemData9[0]['axis']?></td>
                                    </tr>
                                    
                                    <tr>
                                        <td colspan="6">
                                            <p style="display:inline-block;"><b>Refraction By : </b></p>
                                            <input type="text" name="prs-refraction" id="refraction" class="form-control" style="display:inline-block; width:92%;">
                                        </td>
                                    </tr>
                                </table>
                                
                                <?php 
                                
                                $presdetails1="SELECT * FROM `prescription_va` WHERE pres_id='$pres_id' AND eye=1;";
                                $presdetails1Data  = $dbObj->getData($presdetails1);
                                $presdetails2="SELECT * FROM `prescription_va` WHERE pres_id='$pres_id' AND eye=2;";
                                $presdetails2Data  = $dbObj->getData($presdetails2);
                                ?>
                                <div class="mt-1 w-50 ">
                                    <h5><b>Vision Acuity:</b></h5>    
                                    <table class="table">
                                        <tr>
                                            <th  class="text-center">Eye</th>
                                            <th  class="text-center">UVA</th>
                                            <th  class="text-center">PH</th>
                                        </tr>
                                        <tr>
                                            <th  class="text-center"><b>Right</b></th>
                                            <td class="text-center">
                                                <?=$presdetails1Data[0]["uva"]?>
                                            </td>
                                            <td class="text-center">
                                                <?=$presdetails1Data[0]["ph"]?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th  class="text-center"><b>Left</b></th>
                                            <td class="text-center">
                                                <?=$presdetails2Data[0]["uva"]?>
                                            </td>
                                            <td class="text-center">
                                                <?=$presdetails2Data[0]["ph"]?>
                                            </td>
                                        </tr>
                                    </table>            
                                </div>
                                <a href="../Receipts/prescription.php?pres_id=<?=$pres_id?>" class="btn btn-primary">Print</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="hidden"></div>
    <!-- footer Start  -->
    <?php include '../View/footer.php';?> 
    <!-- footer End  -->
    
    <script>
        $(document).ready(function(){
            $("#btn_print_prescription").click(function(){
                 window.print();
            });
        });//jQuery
    </script>
    <!-- <script src="../Assets/jquery/credit-pay.js"></script> -->
    <script src="../Assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>                                           
</body>

</html>


