<?php 

class SMS extends Dbh
{
    public function login($_username,$_password)
    {
        

        //Load sensitive data from environment variables or a secure source
        $username = $_username;  // replace with a secured source
        $password = $_password;  

        if (!$username || !$password) {
            die("Username or password is missing from the environment.");
        }
        // Prepare POST data
        $post_data = array("username" => $username, "password" => $password);

        // Initialize cURL
        $ch = curl_init('https://bsms.hutch.lk/api/login');
        curl_setopt_array($ch, array(
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: */*',
                'X-API-VERSION: v1'
            ),
            CURLOPT_POSTFIELDS => json_encode($post_data)
    ));

    // Execute request and check for errors
    $response = curl_exec($ch);
    if ($response === FALSE) {
        die("cURL Error: " . curl_error($ch));
    }

    // Decode JSON response
    $responseData = json_decode($response, TRUE);
    if (json_last_error() !== JSON_ERROR_NONE) {
        die("JSON Decode Error: " . json_last_error_msg());
    }

    // Close cURL
    curl_close($ch);

    // Return the access token if available
    if (isset($responseData['accessToken'])) {
        echo "Login successful, access token retrieved.\n";
        return $responseData['accessToken'];
    } else {
        die("Access token not found in response.");
    }

    }

    public function SendSMS($access_token, $phonenumber, $Message, $Mask)
    {
        $ch = curl_init('https://bsms.hutch.lk/api/sendsms');

        $post_data = array(          
            "campaignName" => "Synnex SMS",
            "mask" => $Mask,
            "numbers" => $phonenumber,   
            "content" => $Message
        );

        curl_setopt_array($ch, array(
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: */*',
                'X-API-VERSION: v1',
                'Authorization: Bearer '.$access_token
            ),
            CURLOPT_POSTFIELDS => json_encode($post_data)
        ));
       
        $response = curl_exec($ch);
        if ($response === FALSE) {
            die("cURL Error: " . curl_error($ch));
        }
        
        curl_close($ch);
        return $response;
    }

    public function SMSLog($customer_id, $phone_number, $message, $status, $error_message)
    {
        $Sql = "INSERT INTO sms_log (customer_id, phone_number, message, status, error_message) 
        VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->connect()->prepare($Sql);
        $stmt->execute([$customer_id, $phone_number, $message, $status, $error_message]);
    }

}

?>
