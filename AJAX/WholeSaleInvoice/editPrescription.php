<?php 
session_start();
include "../../Includes/config.php";
include "../../Model/DB_Class.php";
include "../../Model/wholesale_invoice_class.php";

$dbObj = new DBTransactions();
$wholesale=new wholesale_invoice();

$user_id = $_SESSION['user_id'];
$shop_id = $_SESSION['shop_id'];

//get current date time
date_default_timezone_set("Asia/Colombo");
$effective_date = date("Y-m-d H:i:s");

$invoice_header_id = null;

$customer_id = $_GET['customer_id'];
$prescription_id = $_GET['prescription_id'];

$subjective_ref = empty($_GET['subjective_ref']) ? "" : $_GET['subjective_ref'];

$f_right_sph = empty($_GET['f_right_sph']) ? "" : $_GET['f_right_sph'];
$f_right_cyl = empty($_GET['f_right_cyl']) ? "" : $_GET['f_right_cyl'];
$f_right_axis = empty($_GET['f_right_axis']) ? "" : $_GET['f_right_axis'];
$f_right_none = empty($_GET['f_right_none']) ? "" : $_GET['f_right_none'];

$f_left_sph = empty($_GET['f_left_sph']) ? "" : $_GET['f_left_sph'];
$f_left_cyl = empty($_GET['f_left_cyl']) ? "" : $_GET['f_left_cyl'];
$f_left_axis = empty($_GET['f_left_axis']) ? "" : $_GET['f_left_axis'];
$f_left_none = empty($_GET['f_left_none']) ? "" : $_GET['f_left_none'];

$add_f_right_add_cyl = empty($_GET['add_f_right_add_cyl']) ? "" : $_GET['add_f_right_add_cyl'];
$add_f_right_add_axis = empty($_GET['add_f_right_add_axis']) ? "" : $_GET['add_f_right_add_axis'];
$add_f_right_add_none = empty($_GET['add_f_right_add_none']) ? "" : $_GET['add_f_right_add_none'];
$add_f_left_add_cyl = empty($_GET['add_f_left_add_cyl']) ? "" : $_GET['add_f_left_add_cyl'];

$prs_hb = empty($_GET['prs_hb']) ? "" : $_GET['prs_hb'];
$add_f_left_add_axis = empty($_GET['add_f_left_add_axis']) ? "" : $_GET['add_f_left_add_axis'];
$add_f_left_add_none = empty($_GET['add_f_left_add_none']) ? "" : $_GET['add_f_left_add_none'];
$pres_remarks = empty($_GET['pres_remarks']) ? "" : $_GET['pres_remarks'];

$s_right_sph = empty($_GET['s_right_sph']) ? "" : $_GET['s_right_sph'];
$s_right_cyl = empty($_GET['s_right_cyl']) ? "" : $_GET['s_right_cyl'];
$s_right_axis = empty($_GET['s_right_axis']) ? "" : $_GET['s_right_axis'];
$s_left_sph = empty($_GET['s_left_sph']) ? "" : $_GET['s_left_sph'];
$s_left_cyl = empty($_GET['s_left_cyl']) ? "" : $_GET['s_left_cyl'];
$s_left_axis = empty($_GET['s_left_axis']) ? "" : $_GET['s_left_axis'];

$add_s_right_cyl = empty($_GET['add_s_right_cyl']) ? "" : $_GET['add_s_right_cyl'];
$add_s_right_axis = empty($_GET['add_s_right_axis']) ? "" : $_GET['add_s_right_axis'];
$add_s_left_cyl = empty($_GET['add_s_left_cyl']) ? "" : $_GET['add_s_left_cyl'];
$add_s_left_axis = empty($_GET['add_s_left_axis']) ? "" : $_GET['add_s_left_axis'];

$prs_refraction = empty($_GET['prs_refraction']) ? "" : $_GET['prs_refraction'];
$r_va_uva = empty($_GET['r_va_uva']) ? "" : $_GET['r_va_uva'];
$r_va_ph = empty($_GET['r_va_ph']) ? "" : $_GET['r_va_ph'];
$l_va_uva = empty($_GET['l_va_uva']) ? "" : $_GET['l_va_uva'];
$l_va_ph = empty($_GET['l_va_ph']) ? "" : $_GET['l_va_ph'];
$vision_acuity = empty($_GET['vision_acuity']) ? "" : $_GET['vision_acuity'];

/*
* Clear prescriptiondetails table with headerid
* Clear prescription_va table with headerid
* update header data prescriptionheader
*/

//clear prescriptiondetails
$query = "DELETE FROM `prescriptiondetails` WHERE prescription_PRHID = ".$prescription_id.";";

$dbObj->executeTransaction($query);

//clear prescription_va
$query_1 = "DELETE FROM `prescription_va` WHERE pres_id = ".$prescription_id.";";

$dbObj->executeTransaction($query_1);

$sql1="INSERT INTO `prescriptiondetails`(`prescription_PRHID`, `side`, `prescription_type`, `sph`, `cyl`, `axis`, `none`) VALUES('$prescription_id','1','1','$f_right_sph','$f_right_cyl','$f_right_axis','$f_right_none');";
$dbObj->executeTransaction($sql1);
$sql1="INSERT INTO `prescriptiondetails`(`prescription_PRHID`, `side`, `prescription_type`, `sph`, `cyl`, `axis`, `none`) VALUES ('$prescription_id','2','1','$f_left_sph','$f_left_cyl','$f_left_axis','$f_left_none');";
$dbObj->executeTransaction($sql1);
$sql1="INSERT INTO `prescriptiondetails`(`prescription_PRHID`, `side`, `prescription_type`, `sph`, `cyl`, `axis`, `none`,`add`) VALUES ('$prescription_id','1','1','','$add_f_right_add_cyl','$add_f_right_add_axis','$add_f_right_add_none','1');";
$dbObj->executeTransaction($sql1);
$sql1="INSERT INTO `prescriptiondetails`(`prescription_PRHID`, `side`, `prescription_type`, `sph`, `cyl`, `axis`, `none`,`add`) VALUES ('$prescription_id','2','1','','$add_f_left_add_cyl','$add_f_left_add_axis','$add_f_left_add_none','1');";
$dbObj->executeTransaction($sql1);
$sql1="INSERT INTO `prescriptiondetails`(`prescription_PRHID`, `side`, `prescription_type`, `sph`, `cyl`, `axis`) VALUES ('$prescription_id','1','2','$s_right_sph','$s_right_cyl','$s_right_axis');";
$dbObj->executeTransaction($sql1);
$sql1="INSERT INTO `prescriptiondetails`(`prescription_PRHID`, `side`, `prescription_type`, `sph`, `cyl`, `axis`) VALUES ('$prescription_id','2','2','$s_left_sph','$s_left_cyl','$s_left_axis');";
$dbObj->executeTransaction($sql1);
$sql1="INSERT INTO `prescriptiondetails`(`prescription_PRHID`, `side`, `prescription_type`, `sph`, `cyl`, `axis`,`add`) VALUES ('$prescription_id','1','2','','$add_s_right_cyl','$add_s_right_axis','1');";
$dbObj->executeTransaction($sql1);
$sql1="INSERT INTO `prescriptiondetails`(`prescription_PRHID`, `side`, `prescription_type`, `sph`, `cyl`, `axis`,`add`) VALUES ('$prescription_id','2','2','','$add_s_left_cyl','$add_s_left_axis','1');";
$dbObj->executeTransaction($sql1);
$sql1="INSERT INTO `prescription_va`(`eye`, `uva`, `ph`, `pres_id`) VALUES ('1','$r_va_uva','$r_va_ph','$prescription_id')";
$dbObj->executeTransaction($sql1);
$sql1="INSERT INTO `prescription_va`(`eye`, `uva`, `ph`, `pres_id`) VALUES ('2','$l_va_uva','$l_va_ph','$prescription_id')";
$dbObj->executeTransaction($sql1);

//update prescriptionheader
$query_2 = "UPDATE prescriptionheader SET 
date='".$effective_date."',
pr_subjective_ref='".$subjective_ref."',
pr_hb='".$prs_hb."',
pr_refraction='".$prs_refraction."',
pr_remarks='".$pres_remarks."',
pr_va='".$vision_acuity."'
WHERE PRHID = ".$prescription_id.";";

$dbObj->executeTransaction($query_2);

echo "Prescription updated successfully... ";