<?php 

class DBTransactions extends Dbh
{
    //for Select functions   
    public function getData($sql)
    {               
        try
        {
            $stmt = $this->connect()->prepare($sql);           
            $stmt->execute();
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }   
    }
    
    public function getMultipleData($sql, $data)
    {               
        try
        {
            $stmt = $this->connect()->prepare($sql);
            foreach ($data as $key => $value) {
                $stmt->bindParam($key + 1, $data[$key]); 
            }
            
            $stmt->execute($data);
            return $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }   
    }

    public function getColumnWithData($sql, $data)
    {               
        try
        {
            $stmt = $this->connect()->prepare($sql);
            foreach ($data as $key => $value) {
                $stmt->bindParam($key + 1, $data[$key]); 
            }
            
            $stmt->execute($data);
            return $stmt->fetchColumn();
        }
        catch(PDOException $e)
        {
            die("Error: Unable to read from table " . $e->getMessage());
        }   
    }

    public function getDataWithBoolean($sql,$data)
    {   
        try 
        {
            $stmt = $this->connect()->prepare($sql);
            
            // Bind parameters dynamically
            foreach ($data as $key => $value) {
                $stmt->bindParam($key + 1, $data[$key]); 
            }
            
            if ($stmt->execute($data)) 
            {
                return true;
            } 
            else 
            {
                return false;
            }
        } 
        catch (PDOException $e) 
        {
            die("Error: Unable to read data: " . $e->getMessage());
        }
    }

    //For Insert,update,delete functions
    public function executeTransaction($sql)
    {
        try 
        {
            $stmt = $this->connect()->prepare($sql);                    
            $stmt->execute();
            return true;
        } 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }
    }

    public function executeTransactionWithArray($sql, $data)
    {
        try 
        {
            $stmt = $this->connect()->prepare($sql);
                        
            foreach ($data as $key => $value) {
                $stmt->bindParam($key + 1, $data[$key]); 
            }
            
            $stmt->execute($data);
        } 
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }
    }

    public function executeTransactionAndReturnLastInsertID($sql, $data)
    {
        try 
        {
            $stmt = $this->connect()->prepare($sql);

            foreach ($data as $key => $value) {
                $stmt->bindParam($key + 1, $data[$key]); 
            }

            $stmt->execute($data);
            $newId = $this->connect()->lastInsertId();
            return $newId;
        }
        catch (PDOException $e) 
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }
    }

     // Begin transaction
    public function beginTransaction()
    {
        try
        {
            $this->connect()->beginTransaction();
            echo "Transaction started\n";
        }
        catch(PDOException $e)
        {
            die("Error: Unable to begin transaction: " . $e->getMessage());
        }
    }

    // Insert data into a table
    public function insertData($table, $data)
    {
        try
        {
            // Construct column names and placeholders
            $columns = implode(", ", array_keys($data));
            $placeholders = ":" . implode(", :", array_keys($data));

            // Prepare the SQL query
            $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
            $stmt = $this->connect()->prepare($sql);

            // Bind values
            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }

            // Execute the query
            $stmt->execute();
            echo "Data inserted successfully\n";
        }
        catch(PDOException $e)
        {
            die("Error: Unable to insert data: " . $e->getMessage());
        }
    }

    // Commit transaction
    public function commitTransaction()
    {
        try
        {
            $this->connect()->commit();
            echo "Transaction committed\n";
        }
        catch(PDOException $e)
        {
            die("Error: Unable to commit transaction: " . $e->getMessage());
        }
    }

    // Rollback transaction
    public function rollbackTransaction()
    {
        try
        {
            $this->connect()->rollBack();
            echo "Transaction rolled back\n";
        }
        catch(PDOException $e)
        {
            die("Error: Unable to rollback transaction: " . $e->getMessage());
        }
    }
}