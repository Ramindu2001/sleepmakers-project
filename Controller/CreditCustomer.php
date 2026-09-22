<?php 

include "../Includes/includes.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$credit_cus = new credit_customer();
$dbObj = new DBTransactions();
$wholesale = new wholesale_invoice();
$shop_SHID = $_SESSION['shop_id'];
$user_USID = $_SESSION["user_id"];

if (isset($_POST["credit_repay"]) || isset($_POST["credit_repayi"])) 
{

     // Check if the token exists and is valid
     if (isset($_SESSION['form_token']) && $_SESSION['form_token'] === $_POST['form_token']) {
        exit(); 
    }

    // Generate a new token and store it in the session
    $_SESSION['form_token'] = $_POST['form_token'];

    $cus_id = $_POST["cus_id"];
    if (empty($_POST["invoice_id"]) || empty($_POST["due"]) || empty($_POST["amount"]) || empty($_POST["total_paid"]) || empty($_POST["paid"])) {
        $_SESSION["credit_customer"] = 0;
        header("Location: ../Public/credit-pay.php?cus_id=$cus_id");
        exit();
    }

    foreach ($_POST["invoice_id"] as $i => $invoice_id) {
        $amount = $_POST["amount"][$i];
        $pay_id = $_POST["pay_id"][0];
        $user = $_SESSION["user_id"];
        $date = $_POST["date"] ?? date("Y-m-d");

        $credit_cus->credit_repay($date, $amount, $invoice_id, $pay_id, $cus_id, $user,$shop_SHID);
    }
    $sql="SELECT MAX(CCID) AS CCID FROM creditcustomer LIMIT 1";
    $creditcustomerData = $dbObj->getData($sql);
    $CCID=$creditcustomerData[0]["CCID"];
    foreach ($_POST["paid"] as $i => $TransferAmount) {
        $paymethod_PMID = $_POST["pay_id"][$i];
        $invoice_id = $_POST["invoice_id"][0];

        $sql = "INSERT INTO `cuscredittransactions`(`cuscreditTransactionAmount`, `paymethod_id`, `invoice_id`,`CreditCustomer_CCID`) 
                VALUES ('$TransferAmount','$paymethod_PMID','$invoice_id','$CCID')";
        $dbObj->executeTransaction($sql);

        if ($paymethod_PMID == 5 && !empty($_POST["chqNo"])) {
            foreach ($_POST["chqNo"] as $j => $chequeNo) {
                $sql = "SELECT COUNT(*) AS ChqNo FROM custcheq";
                $dbData = $dbObj->getData($sql);
                $count = $dbData[0]["ChqNo"] + 1;
                $chq_no = "CCHQ-" . $wholesale->getSequence($count);
                $date = date("Y-m-d H:i:s");

                $chqdate = $_POST["chqdate"][$j] ?? "N/A";
                $bank = $_POST["chqbank"][$j] ?? "N/A";
                $chqAmount = $_POST["chqAmount"][$j] ?? "N/A";
                $EffectiveDate = date("Y-m-d");

                $sql = "INSERT INTO `custcheq`(`type`, `chq_stat`, `chq_no`, `cust_CTID`, `effectiveDate`, `user_USID`, `shop_SHID`, `createdDate`, `invoiceID`) 
                        VALUES ('2','1','$chq_no','$cus_id','$EffectiveDate','$user_USID','$shop_SHID','$date','$invoice_id')";
                $dbObj->executeTransaction($sql);

                $sql = "SELECT MAX(CCQID) AS CCQID FROM custcheq";
                $CCQID = $dbObj->getData($sql)[0]["CCQID"];

                $sql = "INSERT INTO `custchqdetail`(`bank`, `chqAmount`, `chqNo`, `chqDate`, `invoiceID`, `CCQID`) 
                        VALUES ('$bank','$chqAmount','$chequeNo','$chqdate','$invoice_id','$CCQID')";
                $dbObj->executeTransaction($sql);
            }
        }
    }

    $_SESSION["credit_customer"] = 1;

    $sql = "SELECT * FROM shopreceipts WHERE ReceiptStat = 1 AND shop_id='$shop_SHID' AND RecieptType=2";
    $dbData = $dbObj->getData($sql);
    $invoice = empty($dbData) ? "wholesaleInvoice.php" : $dbData[0]['ReceiptPath'];

    // Reset session variable to allow new submissions
    $_SESSION["form_submitted"] = false;
    session_regenerate_id(true); // Prevent session fixation attacks

    if (isset($_POST["credit_repayi"])) {
        $id=$_POST["invoice_id"][0];
        header("Location: ../Receipts/$invoice?invoice=$id");
    } else {
        header("Location: ../Public/credit-pay.php?cus_id=$cus_id");
    }
    exit();
}
?>
