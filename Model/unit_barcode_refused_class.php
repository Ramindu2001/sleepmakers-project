<?php
//Something a unit barcode cannot do: the HTTP status to answer with and the message for the
//user. Printing and the unit pages throw it; nothing is written when it is thrown.
class UnitBarcodeRefused extends RuntimeException
{
    public $status;

    public function __construct($status, $message)
    {
        parent::__construct($message);
        $this->status = $status;
    }//construct
}//UnitBarcodeRefused
