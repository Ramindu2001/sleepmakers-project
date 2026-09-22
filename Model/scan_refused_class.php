<?php
//A scanner upload that cannot go ahead: the HTTP status to answer with, the message for the
//user, the preview to show again (when there is one) and what the user may confirm to go
//ahead anyway ('duplicate' or 'short'; null when nothing can be confirmed).
class ScanRefused extends RuntimeException
{
    public $status;
    public $preview;
    public $confirm;

    public function __construct($status, $message, ?array $preview = null, $confirm = null)
    {
        parent::__construct($message);
        $this->status = $status;
        $this->preview = $preview;
        $this->confirm = $confirm;
    }//construct
}//ScanRefused
