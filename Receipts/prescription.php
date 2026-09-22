<?php 

include '../Includes/includes.php';
include '../Includes/authcheck.php';
$pres_id=$_GET["pres_id"];
$dbObj = new DBTransactions();
$user_id = $_SESSION['user_id'];
$shop_id = $_SESSION['shop_id'];//counter check
//get shop details
$sql = "SELECT * FROM `shop` WHERE SHID='$shop_id';";
$headData  = $dbObj->getData($sql);
$receipt_logo_name = $headData[0]['ReceiptLogo'];
$ShopLogo = $headData[0]['ShopLogo'];
$shop_name = $headData[0]['ShopName'];
$address_1 = $headData[0]['AddressLineOne'];
$address_2 = $headData[0]['AddressLineTwo'];
$contact = $headData[0]['PhoneNumber'];
$email = $headData[0]['emailAddress'];
$presSQL="SELECT * FROM prescriptionheader WHERE PRHID='$pres_id'";
$presData  = $dbObj->getData($presSQL);
$pr_subjective_ref=$presData[0]['pr_subjective_ref'];
$pr_refraction=$presData[0]['pr_refraction'];
$pr_hb=$presData[0]['pr_hb'];
$pr_remarks=$presData[0]['pr_remarks'];
$pr_va=$presData[0]['pr_va'];
$custID=$presData[0]['customer_CTID'];
$custSQL="SELECT * FROM customers WHERE CTID='$custID'";
$custsData  = $dbObj->getData($custSQL);
// Create DateTime object from the date value in $presData array
$date = new DateTime($presData[0]["date"]);

// Convert DateTime object to a string with the desired format, for example 'Y-m-d H:i:s'
$dateString = $date->format('Y-m-d H:i:s');

// Use substr to remove the last 5 characters (for example, to remove seconds and the space)
$date = substr($dateString, 0, -9);
if($custsData[0]["CustDOB"]!=null && $custsData[0]["CustDOB"]!="0000-00-00")
{
    $dob=$custsData[0]["CustDOB"];
    // Convert the DOB to a DateTime object
    $dobDateTime = new DateTime($dob);
    // Get the current date
    $currentDate = new DateTime();
    $age = $dobDateTime->diff($currentDate)->y;
}
else
{
    $age="";
}

if($custsData[0]["CustGender"]==1)
{
    $gender="Male";
}
elseif($custsData[0]["CustGender"]==2)
{
    $gender="Female";
}
else
{
    $gender="Other";
}
?>

<!doctype html>
<html lang="en">

<head>
  <?php 
  include '../View/head.php';
  ?>
  <style>
    *
    {
        color:#000;
        font-size: 11px;
        font-weight:500;
    }
    td,
    th
    {
        border: 1px solid #000;
    }
    textarea
    {
        border-color: #000;
    }
    .table>:not(caption)>*>* 
    {
        padding: 0 10px;
    }
  </style>
</head>
<body class="p-3">
    <div class="row" style="margin-bottom:100px;">
        <div class="w-50"></div>
        <div class="w-50">
            <h2 style="text-align:right; margin:5px; text-decoration:underline; "><b style="font-size:20px;">Prescription</b></h2>
        </div>
    </div>
    <div class="row">
        <div class="w-100">
            <table>
                <tr style="border:none;">
                    <td style="border:none; padding: 0 16px;"><b><?=$address_1?></b>  <br>
                    <b><?=$address_2?></b></td>
                </tr>
                <tr style="border:none;">
                    <td style="border:none; padding: 0 16px;"><b><?=$contact?></b></td>
                </tr>
                <tr style="border:none;">
                    <td style="border:none; padding: 0 16px;"><b><?=$email?></b></td>
                </tr>
            </table> 
        </div>
        <div class="w-50 mb-1">
            <h5><b>Name:</b></h5>   
            <table>
                <tr>
                    <td style="border:1px solid black;  padding: 5px 81px 50px 5px;">
                        <?=$custsData[0]["CustName"]?> <br>
                        <?=$custsData[0]["CustContact"]?><br>
                    </td>
                </tr>
            </table>
        </div>
        <div class="w-50 mb-1" style="display:flex; Justify-content:end;    align-items: end;">
            <table id="tbl_rent_detail" style="width:auto; height:fit-content;">
                <tr>
                    <td style="padding:0 10px;border:1px solid black;">
                       <b>Date</b> 
                    </td>
                    <td style="padding:0 10px;border:1px solid black;">
                        <?=$date?>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 10px;border:1px solid black;">
                        <b>Age</b>
                    </td>
                    <td style="padding:0 10px;border:1px solid black;">
                        <?=$age?>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 10px;border:1px solid black;">
                        <b>Gender</b>
                    </td>
                    <td style="padding:0 10px;border:1px solid black;">
                        <?=$gender?>
                    </td>
                </tr>
            </table>
        </div>
        <?php    
        $presdetails1="SELECT * FROM `prescriptiondetails` WHERE prescription_PRHID='$pres_id' AND side=1 AND prescription_type=1 AND `add`=0;";
        $presdetails1Data  = $dbObj->getData($presdetails1);
        $presdetails2="SELECT * FROM `prescriptiondetails` WHERE prescription_PRHID='$pres_id' AND side=2 AND prescription_type=1 AND `add`=0;";
        $presdetails2Data  = $dbObj->getData($presdetails2);
        $presdetails3="SELECT * FROM `prescriptiondetails` WHERE prescription_PRHID='$pres_id' AND side=1 AND prescription_type=1 AND `add`=1;";
        $presdetails3Data  = $dbObj->getData($presdetails3);
        $presdetails4="SELECT * FROM `prescriptiondetails` WHERE prescription_PRHID='$pres_id' AND side=2 AND prescription_type=1 AND `add`=1;";
        $presdetails4Data  = $dbObj->getData($presdetails4);
        ?>
        <div class="w-100">
            <h5><b>Subjective Refraction:</b></h5>    
            <table class="table">
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
                    <td class="text-center"><b>VA</b></td>
                    <td class="text-center"><b>Sph.</b></td>
                    <td class="text-center"><b>Cyl.</b></td>
                    <td class="text-center"><b>Axis</b></td>
                    <td class="text-center"><b>VA</b></td>
                </tr>
                <tr>
                    <td class="text-center">
                        <?=$presdetails1Data[0]["sph"]?>
                    </td>
                    <td class="text-center">
                        <?=$presdetails1Data[0]["cyl"]?>
                    </td>
                    <td class="text-center">
                        <?=$presdetails1Data[0]["axis"]?>
                    </td>
                    <td class="text-center">
                        <?=$presdetails1Data[0]["none"]?>
                    </td>
                    <td class="text-center">
                        <?=$presdetails2Data[0]["sph"]?>
                    </td>
                    <td class="text-center">
                        <?=$presdetails2Data[0]["cyl"]?>
                    </td>
                    <td class="text-center">
                        <?=$presdetails2Data[0]["axis"]?>
                    </td>
                    <td class="text-center">
                        <?=$presdetails2Data[0]["none"]?>
                    </td>
                </tr>
                <tr>
                    <td class="text-center"><b>Add</b></td>
                    <td class="text-center">
                        <?=$presdetails3Data[0]["cyl"]?>
                    </td>
                    <td class="text-center">
                        <?=$presdetails3Data[0]["axis"]?> 
                    </td>
                    <td class="text-center">
                        <?=$presdetails3Data[0]["none"]?>
                    </td>
                    <td class="text-center"><b>Add</b></td>
                    <td class="text-center">
                        <?=$presdetails4Data[0]["cyl"]?>
                    </td>
                    <td class="text-center">
                        <?=$presdetails4Data[0]["axis"]?> 
                    </td>
                    <td class="text-center">
                        <?=$presdetails4Data[0]["none"]?>    
                    </td>
                </tr>
            </table>
        </div>    
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
        <div class="mt-1 w-50">
            <label for="remark" class="form-label">Remarks</label>
            <textarea name="pres-remarks" id="" class="form-control" style="height:80%; border-color:#000; color:#000;"><?=$pr_remarks?></textarea>
        </div>    
        <?php    
        $presdetails1="SELECT * FROM `prescriptiondetails` WHERE prescription_PRHID='$pres_id' AND side=1 AND prescription_type=2 AND `add`=0;";
        $presdetails1Data  = $dbObj->getData($presdetails1);
        $presdetails2="SELECT * FROM `prescriptiondetails` WHERE prescription_PRHID='$pres_id' AND side=2 AND prescription_type=2 AND `add`=0;";
        $presdetails2Data  = $dbObj->getData($presdetails2);
        $presdetails3="SELECT * FROM `prescriptiondetails` WHERE prescription_PRHID='$pres_id' AND side=1 AND prescription_type=2 AND `add`=1;";
        $presdetails3Data  = $dbObj->getData($presdetails3);
        $presdetails4="SELECT * FROM `prescriptiondetails` WHERE prescription_PRHID='$pres_id' AND side=2 AND prescription_type=2 AND `add`=1;";
        $presdetails4Data  = $dbObj->getData($presdetails4);
        ?>
        <div class="mt-3 w-50 ">
            <h5><b>Present Prescription:</b></h5>    
            <table class="table mt-4">
                <tr>
                    <td colspan="3" class="text-center">
                        <b>Right</b>
                    </td>
                    <td colspan="3" class="text-center">
                        <b>Left</b>
                    </td>
                </tr>
                <tr>
                    <th class="text-center">Sph.</th>
                    <th class="text-center">Cyl.</th>
                    <th class="text-center">Axis</th>
                    <th class="text-center">Sph.</th>
                    <th class="text-center">Cyl.</th>
                    <th class="text-center">Axis</th>
                </tr>
                <tr>
                    <td class="text-center">
                        <?=$presdetails1Data[0]["sph"]?>
                    </td>
                    <td class="text-center">
                        <?=$presdetails1Data[0]["cyl"]?>
                    </td>
                    <td class="text-center">
                        <?=$presdetails1Data[0]["axis"]?>
                    </td>
                    <td class="text-center">
                        <?=$presdetails2Data[0]["sph"]?>
                    </td>
                    <td class="text-center">
                        <?=$presdetails2Data[0]["cyl"]?>
                    </td>
                    <td class="text-center">
                        <?=$presdetails2Data[0]["axis"]?>
                    </td>
                </tr>
                <tr>
                    <td class="text-center"><b>Add</b></td>
                    <td class="text-center"><?=$presdetails3Data[0]["cyl"]?></td>
                    <td class="text-center"><?=$presdetails3Data[0]["axis"]?></td>
                    <td class="text-center"><b>Add</b></td>
                    <td class="text-center"><?=$presdetails4Data[0]["cyl"]?></td>
                    <td class="text-center"><?=$presdetails4Data[0]["axis"]?></td>
                </tr>
            </table>           
        </div>
        <h6><b>OPTOMETRIST: <?=$pr_refraction?></b></h6>
    </div>
</body>
<script>
    window.print();
    setTimeout(function(){
        history.back();
    }, 200);
</script>