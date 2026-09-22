<?php
// session_start();
include "../Includes/includes.php";
$shop_id = $_SESSION['shop_id'];
$wholesale=new wholesale_invoice();
$dbObj = new DBTransactions();
$comObj = new Common();
$shop= new Shop();
$shop_SHID=$_SESSION['shop_id'];

$SMS = new SMS();
$cust = new Customer();

if(isset($_POST["btnSMS"]))
{
     // Fetch form data
     $customer_id = $_POST['customer_id'];
     $customerPhone = $_POST['customerPhone'];
     $smsContent = $_POST['smsContent'];
     
     //Sms gateway integration added by Imila Madushan 2024-11-25 ------------------//
    //get the SMS Details
    $sql = "SELECT is_enable,UserName,Password,Mask FROM sms_details";
    $smsData = $dbObj->getData($sql);
    if(count($smsData)>0)
    {
        $isSMSEnable = $smsData[0]['is_enable'];
        $SMSUserName = $smsData[0]['UserName'];
        $SMSPassword = $smsData[0]['Password'];
        $SMSMask = $smsData[0]['Mask'];

        if($isSMSEnable == 1)
        {
            $AccessToken = $SMS->login($SMSUserName,$SMSPassword);
            $MessageBody = $smsContent;
            $phoneNumb = $customerPhone;
            
            //SMS Log
            $sendStatus = $SMS->SendSMS($AccessToken, $customerPhone, $smsContent, $SMSMask);

            // Decode the JSON response
            $response = json_decode($sendStatus, true); // `true` converts JSON into an associative array

            if (isset($response['serverRef'])) {
                $serverRef = $response['serverRef']; // Access serverRef
                echo "SMS sent successfully with server reference: " . $serverRef;

                // Log the serverRef in the SMS log
                $status = 'Sent';
                $error_message = NULL;
                $SMS->SMSLog($customer_id, $customerPhone, $smsContent, $status, $error_message);

                echo "<script>
                alert('SMS sent successfully!');
                window.location.href = '../Public/SendSMSPromotions.php';
                    </script>";
                exit();

            } else {
                // Handle error if serverRef is not present
                $status = 'Failed';
                $error_message = "No server reference returned.";
                $SMS->SMSLog($customer_id, $customerPhone, $smsContent, $status, $error_message);
                echo "Failed to send SMS!";
            }

        }
        
        //---------------SMS ends here---------------------------------------------------//
    }
}

?>