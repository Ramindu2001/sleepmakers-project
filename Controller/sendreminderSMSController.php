<?php
include "../Includes/includes.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = $_POST['customerId'];
    $customerPhone = $_POST['customerPhone'];
    $customerName = $_POST['customerName'];
    $balance = $_POST['customerBalance'];

    $SMS = new SMS();
    $dbObj = new DBTransactions();

    // Get SMS gateway details
    $sql = "SELECT is_enable, UserName, Password, Mask FROM sms_details";
    $smsData = $dbObj->getData($sql);

    if (count($smsData) > 0 && $smsData[0]['is_enable'] == 1) {
        $AccessToken = $SMS->login($smsData[0]['UserName'], $smsData[0]['Password']);
        $MessageBody = "Dear $customerName,\nYour due balance is Rs. $balance. Please make payment at your earliest convenience.";

        $sendStatus = $SMS->SendSMS($AccessToken, $customerPhone, $MessageBody, $smsData[0]['Mask']);
        $response = json_decode($sendStatus, true);

        if (isset($response['serverRef'])) {
            // Log success in the database
            $SMS->SMSLog($customerId, $customerPhone, $MessageBody, 'Sent', NULL);
            echo json_encode(['status' => 'success', 'message' => 'SMS sent successfully']);
        } else {
            // Log failure in the database
            $SMS->SMSLog($customerId, $customerPhone, $MessageBody, 'Failed', 'No server reference returned');
            echo json_encode(['status' => 'error', 'message' => 'Failed to send SMS']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'SMS gateway is not enabled']);
    }
}
?>
