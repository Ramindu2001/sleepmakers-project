<?php 

class credit_customer extends Dbh
{
    public function setCreditCustomer($EffectiveDate, $CreditAmount, $DebitAmount, $Balance, $SubmitDate, $DueDate, $invoice_header_id, $pay_m_id, $CreditStat, $Customers_CTID, $user_USID)
    {
        try 
        {            
            $sql="INSERT INTO creditcustomer(EffectiveDate, CreditAmount, DebitAmount, Balance, SubmitDate, DueDate, invoice_header_id, pay_m_id, CreditStat, Customers_CTID, user_USID) VALUES(?,?,?,?,?,?,?,?,?,?,?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$EffectiveDate, $CreditAmount, $DebitAmount, $Balance, $SubmitDate, $DueDate, $invoice_header_id, $pay_m_id, $CreditStat, $Customers_CTID, $user_USID]);           
        } 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data to Customer: " . $e->getMessage());
        }
    }//set credit customer

    public function getCustomerNames() {
      
        $query = "SELECT CTID, CustName FROM customers C INNER JOIN creditcustomer CC ON CC.Customers_CTID = C.CTID GROUP BY  CTID ORDER BY CustName ASC"; // Replace 'customers' with your table name
        $result = $this->connect()->prepare($query);
        $customerNames = [];

        $result->execute();
        return $result->fetchAll();
       
    }
    public function getOneCustomer($id) {
      
        $query = "SELECT CustName FROM customers WHERE CTID ='$id'"; // Replace 'customers' with your table name
        $result = $this->connect()->prepare($query);
        $result->execute();
        return $result->fetchAll();
       
    }


    public function select_credit_customer($id=null,$type=null)
    {
        $shop_id = $_SESSION['shop_id'];
        try 
        
        {
            $add=" ";
            if($id!=null)
            {
                $add .="AND c.CTID='$id'";
            }    
            $sql = "SELECT c.*, SUM(cc.CreditAmount) AS total_credit, SUM(cc.DebitAmount) AS total_debit, SUM(cc.CreditAmount) - SUM(cc.DebitAmount) AS BALANCE  FROM customers c
                INNER JOIN creditcustomer cc ON c.CTID=cc.Customers_CTID 
                WHERE cc.shop_SHID='$shop_id' AND cc.CreditStat=1 $add 
                GROUP by cc.Customers_CTID;";
            if($type!=null)
            {
                if($type==1)
                {
                    $sql = "SELECT c.*, SUM(cc.CreditAmount) AS total_credit, SUM(cc.DebitAmount) AS total_debit, SUM(cc.CreditAmount) - SUM(cc.DebitAmount) AS BALANCE  FROM customers c
                    INNER JOIN creditcustomer cc ON c.CTID=cc.Customers_CTID 
                    WHERE cc.shop_SHID='$shop_id' AND cc.CreditStat=1   $add
                    GROUP by cc.Customers_CTID HAVING  BALANCE > 0 AND BALANCE > c.MaxCreditAmount;";
                }
                elseif($type==2)
                {
                    $sql = "SELECT c.*, SUM(cc.CreditAmount) AS total_credit, SUM(cc.DebitAmount) AS total_debit, SUM(cc.CreditAmount) - SUM(cc.DebitAmount) AS BALANCE  FROM customers c
                    INNER JOIN creditcustomer cc ON c.CTID=cc.Customers_CTID 
                    WHERE cc.shop_SHID='$shop_id' AND cc.CreditStat=1 $add
                    GROUP by cc.Customers_CTID HAVING  BALANCE < 0 ;";
                }
                elseif($type==3)
                {
                    $sql = "SELECT c.*, SUM(cc.CreditAmount) AS total_credit, SUM(cc.DebitAmount) AS total_debit, SUM(cc.CreditAmount) - SUM(cc.DebitAmount) AS BALANCE  FROM customers c
                    INNER JOIN creditcustomer cc ON c.CTID=cc.Customers_CTID 
                    WHERE cc.shop_SHID='$shop_id' AND cc.CreditStat=1  $add  
                    GROUP by cc.Customers_CTID HAVING BALANCE = 0;";
                }
                elseif($type==4)
                {
                    $sql = "SELECT c.*, SUM(cc.CreditAmount) AS total_credit, SUM(cc.DebitAmount) AS total_debit, SUM(cc.CreditAmount) - SUM(cc.DebitAmount) AS BALANCE  FROM customers c
                    INNER JOIN creditcustomer cc ON c.CTID=cc.Customers_CTID 
                    WHERE cc.shop_SHID='$shop_id' AND cc.CreditStat=1  $add
                    GROUP by cc.Customers_CTID HAVING  BALANCE > 0 AND BALANCE < c.MaxCreditAmount;";   
                }
            }       
            
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        catch (PDOException $e) {
            die("Error: Unable to fetch Customer data " . $e->getMessage());
        }
    }
    public function credit_customer_details($cus_id,$shop_id,$type=0)
    {
        try 
        {          
            $sql = "SELECT cc.*, i.IHID,i.BillNo AS invoice, i.NetAmount AS invoiceAmount, SUM(cc.CreditAmount) AS total_credit, 
                    SUM(cc.DebitAmount) AS total_debit FROM `creditcustomer` cc 
                    LEFT JOIN invoiceheader i ON i.IHID = cc.invoice_header_id
                    WHERE cc.Customers_CTID='$cus_id' AND cc.shop_SHID='$shop_id' AND cc.CreditStat=1 GROUP BY cc.Customers_CTID , cc.invoice_header_id;";
                    if($type==1)
                    {
                        $sql = "SELECT cc.*, i.IHID,i.BillNo AS invoice, i.NetAmount AS invoiceAmount, SUM(cc.CreditAmount) AS total_credit, 
                                SUM(cc.DebitAmount) AS total_debit FROM `creditcustomer` cc 
                                LEFT JOIN invoiceheader i ON i.IHID = cc.invoice_header_id
                                WHERE cc.Customers_CTID='$cus_id' AND cc.CreditStat=1 AND cc.DebitAmount != 0.00 GROUP BY cc.Customers_CTID , cc.invoice_header_id;";
                    }
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        catch (PDOException $e) {
            die("Error: Unable to fetch Customer data " . $e->getMessage());
        }

    }

    public function creditCustomerPDF($cus_id,$shop_id)
    {
                
        try 
        {          
            $sql="SELECT cc.*, i.IHID,i.BillNo AS invoice, i.NetAmount AS invoiceAmount, cc.CreditAmount AS total_debit
                    ,cc.DebitAmount AS total_credit FROM `creditcustomer` cc 
                    LEFT JOIN invoiceheader i ON i.IHID = cc.invoice_header_id
                    WHERE cc.Customers_CTID='$cus_id' AND cc.shop_SHID='$shop_id' AND cc.CreditStat=1;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        catch (PDOException $e) {
            die("Error: Unable to fetch Customer data " . $e->getMessage());
        }
    }

    public function credit_customer_details_Invoice($invoice_id,$cus_id,$shop_id)
    {
        try 
        {          
            $sql = "SELECT cc.*,i.InvoiceNo AS invoice, i.NetAmount AS invoiceAmount, SUM(IFNULL(cc.CreditAmount,0)) AS total_credit, 
                    SUM(IFNULL(cc.DebitAmount,0)) AS total_debit FROM `creditcustomer` cc 
                    LEFT JOIN invoiceheader i ON i.IHID = cc.invoice_header_id
                    WHERE cc.invoice_header_id='$invoice_id' AND cc.shop_SHID='$shop_id' AND cc.Customers_CTID='$cus_id' AND cc.CreditStat = '1' GROUP BY cc.invoice_header_id";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        catch (PDOException $e) {
            die("Error: Unable to fetch Customer data " . $e->getMessage());
        }
    }
    public function credit_repay($date,$amount,$invoice_id,$pay_id,$cus_id,$user,$shop_id)
    {
        try 
        {          
            $sql = "INSERT INTO `creditcustomer`(`EffectiveDate`, `DebitAmount`, `Balance`, `invoice_header_id`, `pay_m_id`, `Customers_CTID`, `user_USID`,`shop_SHID`,`paymentMode`) VALUES ('$date','$amount','$amount','$invoice_id','$pay_id','$cus_id','$user','$shop_id', 4)";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        catch (PDOException $e) {
            die("Error: Unable to Insert Credit data " . $e->getMessage());
        }
    }
}
?>