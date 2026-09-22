<?php
//A customer order action that cannot go ahead: the HTTP status to answer with and the message
//for the user (Controller/CustomerOrderController.php).
class CustomerOrderRefused extends RuntimeException
{
    public $status;

    public function __construct($status, $message)
    {
        parent::__construct($message);
        $this->status = $status;
    }//construct
}//CustomerOrderRefused
