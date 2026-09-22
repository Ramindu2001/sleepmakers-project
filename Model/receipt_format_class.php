<?php

//Which print template a shop's bill is printed with.
//
//A shop prints on an 80mm receipt printer unless "A4 Invoice Print" is switched on in its
//shop settings, in which case its bills print as an A4 invoice. The setting belongs to the
//shop, so switching it on for one shop never changes any other shop.
//
//Order of choice:
//  1. a receipt file uploaded for this shop in Sale Settings (an explicit choice for that shop)
//  2. the shop's print format   (A4 when switched on)
//  3. the built in 80mm receipt (what every shop printed before this setting existed)
//
//To add another paper size later: add a format constant, add its templates to $templates and
//return it from getShopFormat(). The pages that print bills do not have to change.
class ReceiptFormat extends Dbh
{
    const FORMAT_RECEIPT_80MM = 0;   //default: 80mm receipt printer
    const FORMAT_A4           = 1;   //A4 invoice

    //shopreceipts.RecieptType
    const TYPE_RETAIL    = 1;        //GUI / retail POS bill
    const TYPE_WHOLESALE = 2;        //wholesale invoice

    //built in template of each format, per bill type
    private static $templates = [
        self::FORMAT_RECEIPT_80MM => [
            self::TYPE_RETAIL    => "retail-invoice.php",
            self::TYPE_WHOLESALE => "wholesaleInvoice.php",
        ],
        self::FORMAT_A4 => [
            self::TYPE_RETAIL    => "a4-invoice.php",
            self::TYPE_WHOLESALE => "wholesaleInvoice.php",   //already an A4 sized invoice
        ],
    ];

    //the format this shop prints in. A database that does not have the setting yet keeps
    //the 80mm receipt, so the code is safe to deploy before the column is added.
    public function getShopFormat($shop_id)
    {
        try
        {
            $sql = "SELECT is_a4invoice FROM shop WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id]);
            $data = $stmt->fetchAll();

            if(!empty($data) && $data[0]['is_a4invoice'] == 1)
            {
                return self::FORMAT_A4;
            }//prints A4
        }
        catch (PDOException $e)
        {
            //setting not available: print the way this shop printed before
        }//catch

        return self::FORMAT_RECEIPT_80MM;
    }//get shop format

    //remember the format a shop prints in. The setting lives with the printing feature rather
    //than with the shop columns, so a database that does not have it yet simply keeps the 80mm
    //receipt: saving a shop still works and every other shop setting is saved as usual.
    //Returns true when the format was stored, false when the setting is not available.
    public function setShopFormat($shop_id, $format)
    {
        $format = ($format == self::FORMAT_A4) ? self::FORMAT_A4 : self::FORMAT_RECEIPT_80MM;

        try
        {
            $sql = "UPDATE shop SET is_a4invoice = ? WHERE SHID = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$format, $shop_id]);
            return true;
        }
        catch (PDOException $e)
        {
            //setting not available: this shop keeps printing the way it prints now
            return false;
        }//catch
    }//set shop format

    //file name inside Receipts/ to print this shop's bill with
    public function getTemplate($shop_id, $receipt_type = self::TYPE_RETAIL)
    {
        $receipt_type = ($receipt_type == self::TYPE_WHOLESALE) ? self::TYPE_WHOLESALE : self::TYPE_RETAIL;

        $uploaded = $this->getUploadedTemplate($shop_id, $receipt_type);
        if($uploaded !== null)
        {
            return $uploaded;
        }//the shop has its own receipt file

        $format = $this->getShopFormat($shop_id);
        return self::$templates[$format][$receipt_type];
    }//get template

    //the active receipt file uploaded for this shop, or null when it has none
    private function getUploadedTemplate($shop_id, $receipt_type)
    {
        try
        {
            $sql = "SELECT ReceiptPath FROM shopreceipts WHERE ReceiptStat = 1 AND shop_id = ? AND RecieptType = ? ORDER BY SRID ASC LIMIT 1;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$shop_id, $receipt_type]);
            $data = $stmt->fetchAll();

            if(!empty($data) && trim((string)$data[0]['ReceiptPath']) !== '')
            {
                return basename(trim($data[0]['ReceiptPath']));
            }//has a receipt file
        }
        catch (PDOException $e)
        {
            //fall back to the built in template
        }//catch

        return null;
    }//get uploaded template

}//class receipt format
