    <?php 
include "../Includes/includes.php";
$shop_id = $_SESSION['shop_id'];
$user_id = $_SESSION['user_id'];
$SupplierCreditObj= new SupplierCredit();
$dbObj = new DBTransactions();

if(isset($_POST['btn_submit_payment']))
{
    $credit_supplier_id = $_POST['hide_credit_supplier_id'];
    $grn_header_id = $_POST['hide_grn_header_id'];
    $supplier_id = $_POST['hide_supplier_id'];
// Process the payment logic based on these values


    //update creditsupplier table
    $sql = "SELECT sum(TransferAmount) as TotalTransferAmount FROM `suppliertransactions` WHERE TransactionStat = 0 AND GRNHeader_GHID = ".$grn_header_id.";";

    $transData = $dbObj->getData($sql);
    if(count($transData)>0)
    {
        $total_transfer_amount = $transData[0]['TotalTransferAmount'];
        //update here
        $supObj = new SupplierCredit();

        $supObj->editSupplierCredit($total_transfer_amount, $total_transfer_amount, $total_transfer_amount, $credit_supplier_id);

        //update supplier transaction 
        $supObj->editSupplierTransaction($grn_header_id);
    }
    

    header("Location: ../Public/credit-supplier-pay.php?sup_id=" . $supplier_id);
}
else if(isset($_POST['btn_submit_payment2']))
{
    $grn_header_id = $_POST['hide_grn_header_id'];
    $supplier_id = $_POST['hide_supplier_id'];
    $sql="SELECT * FROM supcredittransactions WHERE supcreditTransactionStat=0 AND grn_GHI='$grn_header_id' AND supplier_id='$supplier_id'";
    $transData = $dbObj->getData($sql);
    if(count($transData)>0)
    {
        foreach($transData AS $row)
        {
            $pay_m_id=$row["paymethod_id"];
            $Balance=$row["supcreditTransactionAmount"];
            $CreditAmount=$row["supcreditTransactionAmount"];
            $insert=$SupplierCreditObj->supplierCreditPay($CreditAmount,$Balance,$grn_header_id,$pay_m_id,$supplier_id,$user_id,$shop_id);
            $SCTID=$row["SCTID"];
            $update=$SupplierCreditObj->updatesupplierTran($insert[0]["SCID"],$SCTID);
        }
    }
    header("Location: ../Public/credit-supplier-pay.php?sup_id=" . $supplier_id);
}