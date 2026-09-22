<?php 

class SalesOrder extends Dbh
{

    public function getAllSOHeader()
    {
        try{
            $sql = "SELECT id,SO.SalesOrderNo,SO.SalesOrderDate,C.CustName,SO.total_amount,SO.status FROM sales_orders SO 
            INNER JOIN customers C ON C.CTID = SO.customer_id ORDER BY SO.id DESC LIMIT 50;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }
    }
}
?>