<?php

class Common
{
    public function createCount($prefix, $number)
    {
        return $prefix . '_' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

}//class common